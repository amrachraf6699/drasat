<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\BankTransferDetailResource;
use App\Http\Resources\Admin\BankTransferResource;
use App\Models\BankTransfer;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BankTransferController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'status', 'sort']);
        $query = BankTransfer::query()->with(['user', 'order.items.product', 'reviewer']);

        $query
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('order', fn ($query) => $query->where('order_number', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status));

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(),
            'total_high' => $query->orderByDesc('amount_cents'),
            'total_low' => $query->orderBy('amount_cents'),
            default => $query->latest(),
        };

        return Inertia::render('Admin/BankTransfers', [
            'filters' => $filters,
            'transfers' => BankTransferResource::collection($query->paginate(10)->withQueryString()),
        ]);
    }

    public function show(Request $request, BankTransfer $bankTransfer): Response
    {
        $bankTransfer->load(['user', 'order.items.product', 'reviewer']);

        return Inertia::render('Admin/Detail', BankTransferDetailResource::make($bankTransfer)->resolve($request));
    }

    public function approve(Request $request, BankTransfer $bankTransfer): RedirectResponse
    {
        DB::transaction(function () use ($request, $bankTransfer) {
            $bankTransfer->loadMissing('order.items');
            $bankTransfer->update([
                'status' => 'approved',
                'reviewed_by' => $request->user('admin')->id,
                'reviewed_at' => now(),
            ]);

            if ($bankTransfer->order) {
                $bankTransfer->order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                foreach ($bankTransfer->order->items as $item) {
                    if ($bankTransfer->order->user_id && $item->product_id) {
                        Purchase::updateOrCreate(
                            [
                                'user_id' => $bankTransfer->order->user_id,
                                'product_id' => $item->product_id,
                            ],
                            [
                                'order_id' => $bankTransfer->order_id,
                                'purchased_at' => now(),
                            ],
                        );
                    }
                }
            }
        });

        return back()->with('status', __('admin.flash.transfer_approved'));
    }

    public function reject(Request $request, BankTransfer $bankTransfer): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $bankTransfer->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user('admin')->id,
            'reviewed_at' => now(),
            'admin_note' => $data['admin_note'] ?? null,
        ]);

        $bankTransfer->order?->update(['status' => 'cancelled']);

        return back()->with('status', __('admin.flash.transfer_rejected'));
    }
}
