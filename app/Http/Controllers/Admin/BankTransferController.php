<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'transfers' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (BankTransfer $transfer) => $this->serializeTransfer($transfer)),
        ]);
    }

    public function show(BankTransfer $bankTransfer): Response
    {
        $bankTransfer->load(['user', 'order.items.product', 'reviewer']);

        return Inertia::render('Admin/Detail', [
            'title' => $bankTransfer->reference_number ?: __('admin.common.no_reference'),
            'subtitle' => $bankTransfer->user?->name ?? $bankTransfer->order?->user?->name ?? __('admin.common.guest'),
            'backHref' => route('admin.bank-transfers.index'),
            'stats' => [
                ['label' => __('admin.common.amount'), 'value' => number_format($bankTransfer->amount_cents / 100, 2).' '.$bankTransfer->currency],
                ['label' => __('admin.common.status'), 'value' => __('admin.common.statuses.'.$bankTransfer->status)],
                ['label' => __('admin.common.order'), 'value' => $bankTransfer->order?->order_number ?: '-'],
                ['label' => __('admin.layout.admin_role'), 'value' => $bankTransfer->reviewer?->name ?: '-'],
            ],
            'fields' => [
                ['label' => __('admin.common.reference'), 'value' => $bankTransfer->reference_number],
                ['label' => __('admin.common.customer'), 'value' => $bankTransfer->user?->name ?? $bankTransfer->order?->user?->name ?? __('admin.common.guest')],
                ['label' => __('admin.common.email'), 'value' => $bankTransfer->user?->email ?? $bankTransfer->order?->user?->email],
                ['label' => __('admin.common.created'), 'value' => $bankTransfer->created_at?->format('Y-m-d H:i')],
                ['label' => __('admin.common.date'), 'value' => $bankTransfer->reviewed_at?->format('Y-m-d H:i')],
                ['label' => __('admin.common.value'), 'value' => $bankTransfer->admin_note],
            ],
            'sections' => [
                [
                    'title' => __('admin.common.order'),
                    'columns' => [
                        ['key' => 'order_number', 'label' => __('admin.common.order')],
                        ['key' => 'status', 'label' => __('admin.common.status')],
                        ['key' => 'total', 'label' => __('admin.common.total')],
                    ],
                    'rows' => $bankTransfer->order ? [[
                        'id' => $bankTransfer->order->id,
                        'order_number' => $bankTransfer->order->order_number,
                        'status' => __('admin.common.statuses.'.$bankTransfer->order->status),
                        'total' => number_format($bankTransfer->order->total_cents / 100, 2).' '.$bankTransfer->order->currency,
                        'href' => route('admin.orders.show', $bankTransfer->order),
                    ]] : [],
                    'showLinks' => true,
                ],
                [
                    'title' => __('admin.common.items'),
                    'columns' => [
                        ['key' => 'title', 'label' => __('admin.dashboard.product')],
                        ['key' => 'quantity', 'label' => __('admin.common.items')],
                        ['key' => 'total', 'label' => __('admin.common.total')],
                    ],
                    'rows' => $bankTransfer->order?->items?->map(fn ($item) => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'quantity' => $item->quantity,
                        'total' => number_format($item->total_cents / 100, 2).' '.$bankTransfer->order->currency,
                        'href' => $item->product ? route('admin.products.show', $item->product) : null,
                    ])->values() ?? [],
                    'showLinks' => true,
                ],
            ],
        ]);
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

    private function serializeTransfer(BankTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'reference_number' => $transfer->reference_number,
            'customer' => $transfer->user?->name ?? $transfer->order?->user?->name ?? __('admin.common.guest'),
            'email' => $transfer->user?->email ?? $transfer->order?->user?->email,
            'order_number' => $transfer->order?->order_number,
            'items' => $transfer->order?->items?->pluck('title')->join(', ') ?: __('admin.common.no_items'),
            'status' => $transfer->status,
            'amount' => number_format($transfer->amount_cents / 100, 2).' '.$transfer->currency,
            'reviewer' => $transfer->reviewer?->name,
            'created_at' => $transfer->created_at?->format('Y-m-d H:i'),
            'reviewed_at' => $transfer->reviewed_at?->format('Y-m-d H:i'),
        ];
    }
}
