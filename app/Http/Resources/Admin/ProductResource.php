<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class ProductResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'title_en' => $this->translation($this->resource, 'title', 'en'),
            'title_ar' => $this->translation($this->resource, 'title', 'ar'),
            'short_description_en' => $this->translation($this->resource, 'short_description', 'en'),
            'short_description_ar' => $this->translation($this->resource, 'short_description', 'ar'),
            'description_en' => $this->translation($this->resource, 'description', 'en'),
            'description_ar' => $this->translation($this->resource, 'description', 'ar'),
            'price' => $this->price,
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'status' => $this->status,
            'documents_count' => $this->resource->relationLoaded('documents')
                ? $this->documents->count()
                : $this->documents()->count(),
            'cover_url' => $this->cover?->url,
            'updated_at' => $this->dateTime($this->updated_at),
        ];
    }
}
