<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class AdminAccountResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'roles' => $this->roles->pluck('name')->values(),
            'created_at' => $this->dateTime($this->created_at),
        ];
    }
}
