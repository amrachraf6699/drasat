<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;

class ProductResource extends BaseStorefrontResource
{
    public function toArray(Request $request): array
    {
        $documentsCount = $this->documents_count
            ?? ($this->resource->relationLoaded('documents') ? $this->documents->count() : $this->documents()->count());

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->translated($this->resource, 'title') ?: 'Untitled study',
            'short_description' => $this->translated($this->resource, 'short_description'),
            'description' => $this->translated($this->resource, 'description'),
            'price' => $this->money($this->price_cents, $this->currency),
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'cover_url' => $this->cover?->url,
            'documents_count' => $documentsCount,
            'is_purchased' => $this->isPurchasedBy($request),
            'href' => route('studies.show', $this->resource),
        ];
    }

    protected function isPurchasedBy(Request $request): bool
    {
        if (! $request->user()) {
            return false;
        }

        if (array_key_exists('purchased_by_user', $this->resource->getAttributes())) {
            return (bool) $this->purchased_by_user;
        }

        return $request->user()
            ->purchases()
            ->where('product_id', $this->id)
            ->exists();
    }
}
