<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group' => $this->group,
            'input_type' => $this->input_type,
            'key' => $this->key,
            'label' => __("admin.settings.labels.{$this->key}"),
            'help' => __("admin.settings.help.{$this->key}"),
            'value' => $this->value,
            'value_url' => $this->settingUrl(),
            'value_en' => $this->translation($this->resource, 'value', 'en'),
            'value_ar' => $this->translation($this->resource, 'value', 'ar'),
            'is_translatable' => $this->is_translatable,
        ];
    }

    private function settingUrl(): ?string
    {
        if (! $this->value) {
            return null;
        }

        if (filter_var($this->value, FILTER_VALIDATE_URL)) {
            return $this->value;
        }

        return $this->input_type === 'image'
            ? Storage::disk('public')->url($this->value)
            : null;
    }
}
