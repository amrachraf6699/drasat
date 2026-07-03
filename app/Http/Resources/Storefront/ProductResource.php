<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;

class ProductResource extends BaseStorefrontResource
{
    public function toArray(Request $request): array
    {
        $documentsCount = $this->documents_count
            ?? ($this->resource->relationLoaded('documents') ? $this->documents->count() : $this->documents()->count());
        $documentExtensions = $this->resource->relationLoaded('documents')
            ? $this->documents
                ->map(fn ($document) => pathinfo((string) ($document->original_name ?: $document->path), PATHINFO_EXTENSION))
                ->filter()
                ->map(fn ($extension) => strtoupper($extension))
                ->unique()
                ->values()
                ->all()
            : [];

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'slug' => $this->slug,
            'title' => $this->translated($this->resource, 'title') ?: __('storefront.product.untitled'),
            'short_description' => $this->translated($this->resource, 'short_description'),
            'description' => $this->translated($this->resource, 'description'),
            'price' => $this->money($this->price_cents, $this->currency),
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'cover_url' => $this->cover?->url,
            'documents_count' => $documentsCount,
            'document_extensions' => $documentExtensions,
            'published_at' => $this->published_at?->toDateString(),
            'is_purchased' => $this->isPurchasedBy($request),
            'href' => route('studies.show', $this->resource),
        ];
    }

    protected function isPurchasedBy(Request $request): bool
    {
        $user = $request->user('web');

        if (! $user) {
            return false;
        }

        if (array_key_exists('purchased_by_user', $this->resource->getAttributes())) {
            return (bool) $this->purchased_by_user;
        }

        return $user
            ->purchases()
            ->where('product_id', $this->id)
            ->exists();
    }
}
