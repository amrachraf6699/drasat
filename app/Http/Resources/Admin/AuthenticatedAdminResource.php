<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class AuthenticatedAdminResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->resource->getRoleNames()->values(),
            'permissions' => $this->resource->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
