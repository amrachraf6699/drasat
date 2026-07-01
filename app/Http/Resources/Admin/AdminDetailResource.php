<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class AdminDetailResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->name,
            'subtitle' => $this->email,
            'backHref' => route('admin.admins.index'),
            'stats' => [
                ['label' => __('admin.common.status'), 'value' => $this->is_active ? __('admin.common.statuses.active') : __('admin.common.statuses.inactive')],
                ['label' => __('admin.common.roles'), 'value' => $this->roles->pluck('name')->join(', ') ?: '-'],
                ['label' => __('admin.common.permissions'), 'value' => $this->resource->getAllPermissions()->count()],
                ['label' => __('admin.common.created'), 'value' => $this->dateTime($this->created_at)],
            ],
            'fields' => [
                ['label' => __('admin.common.name'), 'value' => $this->name],
                ['label' => __('admin.common.email'), 'value' => $this->email],
                ['label' => __('admin.common.status'), 'value' => $this->is_active ? __('admin.common.statuses.active') : __('admin.common.statuses.inactive')],
                ['label' => __('admin.common.date'), 'value' => $this->dateTime($this->email_verified_at)],
            ],
            'sections' => [
                [
                    'title' => __('admin.common.roles'),
                    'columns' => [
                        ['key' => 'name', 'label' => __('admin.common.name')],
                        ['key' => 'permissions', 'label' => __('admin.common.permissions')],
                    ],
                    'rows' => $this->roles->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'permissions' => $role->permissions->pluck('name')->join(', '),
                    ])->values(),
                ],
                [
                    'title' => __('admin.common.permissions'),
                    'columns' => [
                        ['key' => 'name', 'label' => __('admin.common.name')],
                    ],
                    'rows' => $this->resource->getAllPermissions()->map(fn ($permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ])->values(),
                ],
            ],
        ];
    }
}
