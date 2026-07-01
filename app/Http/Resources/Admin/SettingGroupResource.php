<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SettingGroupResource extends BaseAdminResource
{
    public function __construct(private string $group, Collection $settings)
    {
        parent::__construct($settings);
    }

    public function toArray(Request $request): array
    {
        return [
            'key' => $this->group,
            'label' => __("admin.settings.groups.{$this->group}"),
            'settings' => $this->resourceCollection(SettingResource::class, $this->resource, $request),
        ];
    }
}
