<?php

namespace App\Http\Resources\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;

class RoleResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->permissions->pluck('name')->values(),
            'permissions_count' => $this->permissions->count(),
            'admins_count' => Admin::query()->role($this->name, 'admin')->count(),
            'created_at' => $this->dateTime($this->created_at),
        ];
    }
}
