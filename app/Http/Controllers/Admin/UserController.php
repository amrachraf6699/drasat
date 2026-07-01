<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'purchases', 'sort']);
        $query = User::query()->withCount('purchases');

        $query
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when(($filters['purchases'] ?? null) === 'with', fn ($query) => $query->has('purchases'))
            ->when(($filters['purchases'] ?? null) === 'without', fn ($query) => $query->doesntHave('purchases'));

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(),
            'name_az' => $query->orderBy('name'),
            'name_za' => $query->orderByDesc('name'),
            default => $query->latest(),
        };

        return Inertia::render('Admin/Users', [
            'filters' => $filters,
            'users' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'purchases_count' => $user->purchases_count,
                    'joined_at' => $user->created_at?->format('Y-m-d H:i'),
                ]),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load([
            'orders.items',
            'purchases.order',
            'purchases.product',
        ]);

        $transfers = BankTransfer::query()
            ->with(['order'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Admin/Detail', [
            'title' => $user->name,
            'subtitle' => $user->email,
            'backHref' => route('admin.users.index'),
            'stats' => [
                ['label' => __('admin.orders.title'), 'value' => $user->orders->count()],
                ['label' => __('admin.users.purchases'), 'value' => $user->purchases->count()],
                ['label' => __('admin.transfers.title'), 'value' => $transfers->count()],
                ['label' => 'OAuth', 'value' => $user->oauth_provider ? 1 : 0],
            ],
            'fields' => [
                ['label' => __('admin.common.name'), 'value' => $user->name],
                ['label' => __('admin.common.email'), 'value' => $user->email],
                ['label' => __('admin.users.joined'), 'value' => $user->created_at?->format('Y-m-d H:i')],
                ['label' => __('admin.common.date'), 'value' => $user->email_verified_at?->format('Y-m-d H:i')],
            ],
            'sections' => [
                [
                    'title' => __('admin.orders.title'),
                    'columns' => [
                        ['key' => 'order_number', 'label' => __('admin.common.order')],
                        ['key' => 'status', 'label' => __('admin.common.status')],
                        ['key' => 'total', 'label' => __('admin.common.total')],
                        ['key' => 'created_at', 'label' => __('admin.common.created')],
                    ],
                    'rows' => $user->orders->map(fn ($order) => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => __('admin.common.statuses.'.$order->status),
                        'total' => number_format($order->total_cents / 100, 2).' '.$order->currency,
                        'created_at' => $order->created_at?->format('Y-m-d H:i'),
                        'href' => route('admin.orders.show', $order),
                    ])->values(),
                    'showLinks' => true,
                ],
                [
                    'title' => __('admin.users.purchases'),
                    'columns' => [
                        ['key' => 'product', 'label' => __('admin.dashboard.product')],
                        ['key' => 'order_number', 'label' => __('admin.common.order')],
                        ['key' => 'purchased_at', 'label' => __('admin.common.date')],
                    ],
                    'rows' => $user->purchases->map(fn ($purchase) => [
                        'id' => $purchase->id,
                        'product' => $purchase->product?->title(app()->getLocale()),
                        'order_number' => $purchase->order?->order_number,
                        'purchased_at' => $purchase->purchased_at?->format('Y-m-d H:i'),
                        'href' => $purchase->product ? route('admin.products.show', $purchase->product) : null,
                    ])->values(),
                    'showLinks' => true,
                ],
                [
                    'title' => __('admin.transfers.title'),
                    'columns' => [
                        ['key' => 'reference', 'label' => __('admin.common.reference')],
                        ['key' => 'order_number', 'label' => __('admin.common.order')],
                        ['key' => 'status', 'label' => __('admin.common.status')],
                        ['key' => 'amount', 'label' => __('admin.common.amount')],
                    ],
                    'rows' => $transfers->map(fn (BankTransfer $transfer) => [
                        'id' => $transfer->id,
                        'reference' => $transfer->reference_number ?: __('admin.common.no_reference'),
                        'order_number' => $transfer->order?->order_number,
                        'status' => __('admin.common.statuses.'.$transfer->status),
                        'amount' => number_format($transfer->amount_cents / 100, 2).' '.$transfer->currency,
                        'href' => route('admin.bank-transfers.show', $transfer),
                    ])->values(),
                    'showLinks' => true,
                ],
                [
                    'title' => 'OAuth',
                    'columns' => [
                        ['key' => 'provider', 'label' => __('admin.common.method')],
                        ['key' => 'provider_id', 'label' => __('admin.common.reference')],
                    ],
                    'rows' => $user->oauth_provider ? [[
                        'id' => $user->id,
                        'provider' => $user->oauth_provider,
                        'provider_id' => $user->oauth_provider_id,
                    ]] : [],
                ],
            ],
        ]);
    }
}
