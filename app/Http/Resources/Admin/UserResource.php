<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class UserResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'purchases_count' => $this->purchases_count,
            'joined_at' => $this->dateTime($this->created_at),
        ];
    }
}
