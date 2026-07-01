<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;

class PurchaseResource extends BaseStorefrontResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => 'purchased',
            'status_label' => __('storefront.library.purchased'),
            'order_number' => $this->order?->order_number,
            'purchased_at' => $this->date($this->purchased_at),
            'product' => $this->product ? ProductResource::make($this->product)->resolve($request) : null,
            'documents' => $this->product
                ? DocumentResource::collection($this->product->documents)->resolve($request)
                : [],
        ];
    }
}
