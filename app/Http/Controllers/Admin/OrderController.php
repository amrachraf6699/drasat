<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'orders' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Order $order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer' => $order->user?->name ?? __('admin.common.guest'),
                    'email' => $order->user?->email,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'total' => number_format($order->total_cents / 100, 2).' '.$order->currency,
                    'items' => $order->items->pluck('title')->join(', ') ?: __('admin.common.no_items'),
                    'items_count' => $order->items_count,
                    'created_at' => $order->created_at?->format('Y-m-d H:i'),
                ]),
        ]);
    }

    public function show(Order $order): Response
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

        return Inertia::render('Admin/Detail', [
            'title' => $order->order_number,
            'subtitle' => $order->user?->name ?? __('admin.common.guest'),
            'backHref' => route('admin.orders.index'),
            'stats' => [
                ['label' => __('admin.common.total'), 'value' => number_format($order->total_cents / 100, 2).' '.$order->currency],
                ['label' => __('admin.common.status'), 'value' => __('admin.common.statuses.'.$order->status)],
                ['label' => __('admin.common.method'), 'value' => str_replace('_', ' ', $order->payment_method ?: 'N/A')],
                ['label' => __('admin.common.items'), 'value' => $order->items->count()],
            ],
            'fields' => [
                ['label' => __('admin.common.customer'), 'value' => $order->user?->name ?? __('admin.common.guest')],
                ['label' => __('admin.common.email'), 'value' => $order->user?->email],
                ['label' => __('admin.common.created'), 'value' => $order->created_at?->format('Y-m-d H:i')],
                ['label' => __('admin.common.date'), 'value' => $order->paid_at?->format('Y-m-d H:i')],
                ['label' => __('admin.common.amount'), 'value' => number_format($order->subtotal_cents / 100, 2).' '.$order->currency],
                ['label' => __('admin.common.total'), 'value' => number_format($order->total_cents / 100, 2).' '.$order->currency],
            ],
            'sections' => [
                [
                    'title' => __('admin.common.items'),
                    'columns' => [
                        ['key' => 'title', 'label' => __('admin.dashboard.product')],
                        ['key' => 'quantity', 'label' => __('admin.common.items')],
                        ['key' => 'unit_price', 'label' => __('admin.common.price')],
                        ['key' => 'total', 'label' => __('admin.common.total')],
                    ],
                    'rows' => $order->items->map(fn ($item) => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'quantity' => $item->quantity,
                        'unit_price' => number_format($item->unit_price_cents / 100, 2).' '.$order->currency,
                        'total' => number_format($item->total_cents / 100, 2).' '.$order->currency,
                        'href' => $item->product ? route('admin.products.show', $item->product) : null,
                    ])->values(),
                    'showLinks' => true,
                ],
                [
                    'title' => __('admin.users.purchases'),
                    'columns' => [
                        ['key' => 'product', 'label' => __('admin.dashboard.product')],
                        ['key' => 'customer', 'label' => __('admin.common.customer')],
                        ['key' => 'purchased_at', 'label' => __('admin.common.date')],
                    ],
                    'rows' => $purchases->map(fn ($purchase) => [
                        'id' => $purchase->id,
                        'product' => $purchase->product?->title(app()->getLocale()),
                        'customer' => $purchase->user?->name ?? __('admin.common.guest'),
                        'purchased_at' => $purchase->purchased_at?->format('Y-m-d H:i'),
                        'href' => $purchase->product ? route('admin.products.show', $purchase->product) : null,
                    ])->values(),
                    'showLinks' => true,
                ],
                [
                    'title' => __('admin.transfers.title'),
                    'columns' => [
                        ['key' => 'reference', 'label' => __('admin.common.reference')],
                        ['key' => 'status', 'label' => __('admin.common.status')],
                        ['key' => 'reviewer', 'label' => __('admin.layout.admin_role')],
                    ],
                    'rows' => $order->bankTransfer ? [[
                        'id' => $order->bankTransfer->id,
                        'reference' => $order->bankTransfer->reference_number ?: __('admin.common.no_reference'),
                        'status' => __('admin.common.statuses.'.$order->bankTransfer->status),
                        'reviewer' => $order->bankTransfer->reviewer?->name,
                        'href' => route('admin.bank-transfers.show', $order->bankTransfer),
                    ]] : [],
                    'showLinks' => true,
                ],
                [
                    'title' => 'Payments',
                    'columns' => [
                        ['key' => 'provider', 'label' => __('admin.common.method')],
                        ['key' => 'reference', 'label' => __('admin.common.reference')],
                        ['key' => 'status', 'label' => __('admin.common.status')],
                        ['key' => 'amount', 'label' => __('admin.common.amount')],
                    ],
                    'rows' => $payments->map(fn (Payment $payment) => [
                        'id' => $payment->id,
                        'provider' => $payment->provider,
                        'reference' => $payment->provider_reference,
                        'status' => $payment->status,
                        'amount' => number_format($payment->amount_cents / 100, 2).' '.$payment->currency,
                    ])->values(),
                ],
            ],
        ]);
    }
}
