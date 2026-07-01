<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserDetailResource extends BaseAdminResource
{
    public function __construct($resource, private Collection $transfers)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'title' => $this->name,
            'subtitle' => $this->email,
            'backHref' => route('admin.users.index'),
            'stats' => [
                ['label' => __('admin.orders.title'), 'value' => $this->orders->count()],
                ['label' => __('admin.users.purchases'), 'value' => $this->purchases->count()],
                ['label' => __('admin.transfers.title'), 'value' => $this->transfers->count()],
                ['label' => 'OAuth', 'value' => $this->oauth_provider ? 1 : 0],
            ],
            'fields' => [
                ['label' => __('admin.common.name'), 'value' => $this->name],
                ['label' => __('admin.common.email'), 'value' => $this->email],
                ['label' => __('admin.users.joined'), 'value' => $this->dateTime($this->created_at)],
                ['label' => __('admin.common.date'), 'value' => $this->dateTime($this->email_verified_at)],
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
                    'rows' => $this->orders->map(fn ($order) => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => __('admin.common.statuses.'.$order->status),
                        'total' => $this->money($order->total_cents, $order->currency),
                        'created_at' => $this->dateTime($order->created_at),
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
                    'rows' => $this->purchases->map(fn ($purchase) => [
                        'id' => $purchase->id,
                        'product' => $purchase->product?->title(app()->getLocale()),
                        'order_number' => $purchase->order?->order_number,
                        'purchased_at' => $this->dateTime($purchase->purchased_at),
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
                    'rows' => $this->transfers->map(fn ($transfer) => [
                        'id' => $transfer->id,
                        'reference' => $transfer->reference_number ?: __('admin.common.no_reference'),
                        'order_number' => $transfer->order?->order_number,
                        'status' => __('admin.common.statuses.'.$transfer->status),
                        'amount' => $this->money($transfer->amount_cents, $transfer->currency),
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
                    'rows' => $this->oauth_provider ? [[
                        'id' => $this->id,
                        'provider' => $this->oauth_provider,
                        'provider_id' => $this->oauth_provider_id,
                    ]] : [],
                ],
            ],
        ];
    }
}
