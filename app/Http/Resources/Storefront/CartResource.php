<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;

class CartResource extends BaseStorefrontResource
{
    public function toArray(Request $request): array
    {
        $items = $this->resource?->items ?? collect();
        $subtotalCents = $items->sum(fn ($item) => $item->unit_price_cents * $item->quantity);
        $currency = $items->first()?->product?->currency ?? 'EGP';

        return [
            'items' => CartItemResource::collection($items)->resolve($request),
            'subtotal_cents' => $subtotalCents,
            'subtotal' => $this->money($subtotalCents, $currency),
            'total_cents' => $subtotalCents,
            'total' => $this->money($subtotalCents, $currency),
            'currency' => $currency,
            'count' => $items->sum('quantity'),
            'is_empty' => $items->isEmpty(),
        ];
    }
}
