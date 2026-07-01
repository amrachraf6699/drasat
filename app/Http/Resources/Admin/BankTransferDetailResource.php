<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class BankTransferDetailResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->reference_number ?: __('admin.common.no_reference'),
            'subtitle' => $this->user?->name ?? $this->order?->user?->name ?? __('admin.common.guest'),
            'backHref' => route('admin.bank-transfers.index'),
            'stats' => [
                ['label' => __('admin.common.amount'), 'value' => $this->money($this->amount_cents, $this->currency)],
                ['label' => __('admin.common.status'), 'value' => __('admin.common.statuses.'.$this->status)],
                ['label' => __('admin.common.order'), 'value' => $this->order?->order_number ?: '-'],
                ['label' => __('admin.layout.admin_role'), 'value' => $this->reviewer?->name ?: '-'],
            ],
            'fields' => [
                ['label' => __('admin.common.reference'), 'value' => $this->reference_number],
                ['label' => __('admin.common.customer'), 'value' => $this->user?->name ?? $this->order?->user?->name ?? __('admin.common.guest')],
                ['label' => __('admin.common.email'), 'value' => $this->user?->email ?? $this->order?->user?->email],
                ['label' => __('admin.common.created'), 'value' => $this->dateTime($this->created_at)],
                ['label' => __('admin.common.date'), 'value' => $this->dateTime($this->reviewed_at)],
                ['label' => __('admin.common.value'), 'value' => $this->admin_note],
            ],
            'sections' => [
                [
                    'title' => __('admin.common.order'),
                    'columns' => [
                        ['key' => 'order_number', 'label' => __('admin.common.order')],
                        ['key' => 'status', 'label' => __('admin.common.status')],
                        ['key' => 'total', 'label' => __('admin.common.total')],
                    ],
                    'rows' => $this->order ? [[
                        'id' => $this->order->id,
                        'order_number' => $this->order->order_number,
                        'status' => __('admin.common.statuses.'.$this->order->status),
                        'total' => $this->money($this->order->total_cents, $this->order->currency),
                        'href' => route('admin.orders.show', $this->order),
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
                    'rows' => $this->order?->items?->map(fn ($item) => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'quantity' => $item->quantity,
                        'total' => $this->money($item->total_cents, $this->order->currency),
                        'href' => $item->product ? route('admin.products.show', $item->product) : null,
                    ])->values() ?? [],
                    'showLinks' => true,
                ],
            ],
        ];
    }
}
