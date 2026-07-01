<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;

class ProductDetailResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'documents' => DocumentResource::collection($this->whenLoaded('documents'))->resolve($request),
        ];
    }
}
