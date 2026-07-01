<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;

class CartItemResource extends BaseStorefrontResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price_cents,
            'unit_price' => $this->money($this->unit_price_cents, $this->product?->currency),
            'total_cents' => $this->unit_price_cents * $this->quantity,
            'total' => $this->money($this->unit_price_cents * $this->quantity, $this->product?->currency),
            'product' => $this->product ? ProductResource::make($this->product)->resolve($request) : null,
        ];
    }
}
