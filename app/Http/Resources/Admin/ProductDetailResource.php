<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class ProductDetailResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        $data = ProductResource::make($this->resource)->resolve($request);

        return [
            'title' => $data['title_en'] ?: $data['title_ar'] ?: __('admin.products.title'),
            'subtitle' => $data['sku'] ?: __('admin.common.no_sku'),
            'backHref' => route('admin.products.index'),
            'stats' => [
                ['label' => __('admin.common.price'), 'value' => $this->money($this->price_cents, $this->currency)],
                ['label' => __('admin.common.status'), 'value' => __('admin.common.statuses.'.$this->status)],
                ['label' => __('admin.common.documents'), 'value' => $this->documents->count()],
                ['label' => __('admin.users.purchases'), 'value' => $this->purchases->count()],
            ],
            'fields' => [
                ['label' => __('admin.common.key'), 'value' => $this->slug],
                ['label' => __('admin.common.title_en'), 'value' => $data['title_en']],
                ['label' => __('admin.common.title_ar'), 'value' => $data['title_ar']],
                ['label' => __('admin.common.short_description_en'), 'value' => $data['short_description_en']],
                ['label' => __('admin.common.short_description_ar'), 'value' => $data['short_description_ar']],
                ['label' => __('admin.common.description_en'), 'value' => $data['description_en']],
                ['label' => __('admin.common.description_ar'), 'value' => $data['description_ar']],
                ['label' => __('admin.common.created'), 'value' => $this->dateTime($this->created_at)],
            ],
            'sections' => [
                [
                    'title' => __('admin.users.purchases'),
                    'columns' => [
                        ['key' => 'customer', 'label' => __('admin.common.customer')],
                        ['key' => 'order_number', 'label' => __('admin.common.order')],
                        ['key' => 'purchased_at', 'label' => __('admin.common.date')],
                    ],
                    'rows' => $this->purchases->map(fn ($purchase) => [
                        'id' => $purchase->id,
                        'customer' => $purchase->user?->name ?? __('admin.common.guest'),
                        'order_number' => $purchase->order?->order_number,
                        'purchased_at' => $this->dateTime($purchase->purchased_at),
                        'href' => $purchase->order ? route('admin.orders.show', $purchase->order) : null,
                    ])->values(),
                    'showLinks' => true,
                ],
                [
                    'title' => __('admin.common.documents'),
                    'columns' => [
                        ['key' => 'name', 'label' => __('admin.common.name')],
                        ['key' => 'type', 'label' => __('admin.common.input_type')],
                        ['key' => 'size', 'label' => __('admin.common.total')],
                    ],
                    'rows' => $this->documents->map(fn ($media) => [
                        'id' => $media->id,
                        'name' => $media->original_name,
                        'type' => $media->mime_type,
                        'size' => $this->kilobytes($media->size),
                    ])->values(),
                ],
            ],
        ];
    }
}
