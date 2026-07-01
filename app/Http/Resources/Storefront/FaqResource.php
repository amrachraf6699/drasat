<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;

class FaqResource extends BaseStorefrontResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->translated($this->resource, 'question'),
            'answer' => $this->translated($this->resource, 'answer'),
        ];
    }
}
