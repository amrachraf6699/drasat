<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OrderDetailResource;
use App\Http\Resources\Admin\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'status', 'payment_method', 'sort']);
        $query = Order::query()
            ->with(['user', 'items'])
            ->withCount('items');

        $query
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('items', fn ($query) => $query->where('title', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['payment_method'] ?? null, fn ($query, string $method) => $query->where('payment_method', $method));

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(),
            'total_high' => $query->orderByDesc('total_cents'),
            'total_low' => $query->orderBy('total_cents'),
            default => $query->latest(),
        };

        return Inertia::render('Admin/Orders', [
            'filters' => $filters,
            'orders' => OrderResource::collection($query->paginate(10)->withQueryString()),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $order->load(['user', 'items.product', 'bankTransfer.reviewer']);

        $payments = Payment::query()
            ->where('order_id', $order->id)
            ->latest()
            ->get();

        $purchases = Purchase::query()
            ->with(['product', 'user'])
            ->where('order_id', $order->id)
            ->latest('purchased_at')
            ->get();

        return Inertia::render('Admin/Detail', (new OrderDetailResource($order, $payments, $purchases))->resolve($request));
    }
}
