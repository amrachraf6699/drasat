<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class SettingDetailResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        $data = SettingResource::make($this->resource)->resolve($request);

        return [
            'title' => $data['label'],
            'subtitle' => __("admin.settings.groups.{$this->group}"),
            'backHref' => route('admin.settings.index'),
            'stats' => [
                ['label' => __('admin.common.group'), 'value' => __("admin.settings.groups.{$this->group}")],
                ['label' => __('admin.common.input_type'), 'value' => __('admin.common.input_types.'.$this->input_type)],
                ['label' => __('admin.common.translated'), 'value' => $this->is_translatable ? __('admin.common.translated') : __('admin.common.single_value')],
                ['label' => __('admin.common.date'), 'value' => $this->dateTime($this->updated_at)],
            ],
            'fields' => [
                ['label' => __('admin.common.key'), 'value' => $this->key],
                ['label' => __('admin.common.value'), 'value' => $data['value']],
                ['label' => __('admin.common.value_en'), 'value' => $data['value_en']],
                ['label' => __('admin.common.value_ar'), 'value' => $data['value_ar']],
                ['label' => __('admin.common.created'), 'value' => $this->dateTime($this->created_at)],
                ['label' => __('admin.common.date'), 'value' => $this->dateTime($this->updated_at)],
            ],
            'sections' => [
                [
                    'title' => __('admin.common.translated'),
                    'columns' => [
                        ['key' => 'locale', 'label' => __('admin.layout.switch_language')],
                        ['key' => 'value', 'label' => __('admin.common.value')],
                    ],
                    'rows' => collect($this->locales())->map(fn (string $locale) => [
                        'id' => "{$this->id}-{$locale}",
                        'locale' => $locale,
                        'value' => $this->translation($this->resource, 'value', $locale),
                    ])->values(),
                ],
            ],
        ];
    }
}
