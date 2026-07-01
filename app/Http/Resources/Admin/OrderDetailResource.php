<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OrderDetailResource extends BaseAdminResource
{
    public function __construct($resource, private Collection $payments, private Collection $purchases)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'title' => $this->order_number,
            'subtitle' => $this->user?->name ?? __('admin.common.guest'),
            'backHref' => route('admin.orders.index'),
            'stats' => [
                ['label' => __('admin.common.total'), 'value' => $this->money($this->total_cents, $this->currency)],
                ['label' => __('admin.common.status'), 'value' => __('admin.common.statuses.'.$this->status)],
                ['label' => __('admin.common.method'), 'value' => str_replace('_', ' ', $this->payment_method ?: 'N/A')],
                ['label' => __('admin.common.items'), 'value' => $this->items->count()],
            ],
            'fields' => [
                ['label' => __('admin.common.customer'), 'value' => $this->user?->name ?? __('admin.common.guest')],
                ['label' => __('admin.common.email'), 'value' => $this->user?->email],
                ['label' => __('admin.common.created'), 'value' => $this->dateTime($this->created_at)],
                ['label' => __('admin.common.date'), 'value' => $this->dateTime($this->paid_at)],
                ['label' => __('admin.common.amount'), 'value' => $this->money($this->subtotal_cents, $this->currency)],
                ['label' => __('admin.common.total'), 'value' => $this->money($this->total_cents, $this->currency)],
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
                    'rows' => $this->items->map(fn ($item) => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'quantity' => $item->quantity,
                        'unit_price' => $this->money($item->unit_price_cents, $this->currency),
                        'total' => $this->money($item->total_cents, $this->currency),
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
                    'rows' => $this->purchases->map(fn ($purchase) => [
                        'id' => $purchase->id,
                        'product' => $purchase->product?->title(app()->getLocale()),
                        'customer' => $purchase->user?->name ?? __('admin.common.guest'),
                        'purchased_at' => $this->dateTime($purchase->purchased_at),
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
                    'rows' => $this->bankTransfer ? [[
                        'id' => $this->bankTransfer->id,
                        'reference' => $this->bankTransfer->reference_number ?: __('admin.common.no_reference'),
                        'status' => __('admin.common.statuses.'.$this->bankTransfer->status),
                        'reviewer' => $this->bankTransfer->reviewer?->name,
                        'href' => route('admin.bank-transfers.show', $this->bankTransfer),
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
                    'rows' => $this->payments->map(fn ($payment) => [
                        'id' => $payment->id,
                        'provider' => $payment->provider,
                        'reference' => $payment->provider_reference,
                        'status' => $payment->status,
                        'amount' => $this->money($payment->amount_cents, $payment->currency),
                    ])->values(),
                ],
            ],
        ];
    }
}
