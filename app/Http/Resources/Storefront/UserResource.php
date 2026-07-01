<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;

class UserResource extends BaseStorefrontResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->oauth_avatar,
        ];
    }
}
