<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RoleDetailResource extends BaseAdminResource
{
    public function __construct($resource, private Collection $admins)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'title' => $this->name,
            'subtitle' => __('admin.roles.subtitle'),
            'backHref' => route('admin.roles.index'),
            'stats' => [
                ['label' => __('admin.common.permissions'), 'value' => $this->permissions->count()],
                ['label' => __('admin.roles.assigned_admins'), 'value' => $this->admins->count()],
                ['label' => __('admin.common.created'), 'value' => $this->dateTime($this->created_at)],
                ['label' => __('admin.common.updated'), 'value' => $this->dateTime($this->updated_at)],
            ],
            'fields' => [
                ['label' => __('admin.common.name'), 'value' => $this->name],
                ['label' => __('admin.common.guard'), 'value' => $this->guard_name],
                ['label' => __('admin.common.permissions'), 'value' => $this->permissions->pluck('name')->join(', ') ?: '-'],
            ],
            'sections' => [
                [
                    'title' => __('admin.common.permissions'),
                    'columns' => [
                        ['key' => 'name', 'label' => __('admin.common.name')],
                    ],
                    'rows' => $this->permissions->map(fn ($permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ])->values(),
                ],
                [
                    'title' => __('admin.roles.assigned_admins'),
                    'columns' => [
                        ['key' => 'name', 'label' => __('admin.common.name')],
                        ['key' => 'email', 'label' => __('admin.common.email')],
                    ],
                    'rows' => $this->admins->map(fn ($admin) => [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'href' => route('admin.admins.show', $admin),
                    ])->values(),
                    'showLinks' => true,
                ],
            ],
        ];
    }
}
