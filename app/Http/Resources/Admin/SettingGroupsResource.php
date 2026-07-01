<?php

namespace App\Http\Resources\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingGroupsResource extends BaseAdminResource
{
    private const GROUP_ORDER = ['general', 'social', 'analytics', 'payments'];

    private const KEY_ORDER = [
        'general' => ['site_name', 'site_logo', 'support_email'],
        'social' => ['facebook', 'x', 'whatsapp', 'linkedin', 'twitter'],
        'analytics' => ['google_analytics_id', 'google_tag_manager_id', 'meta_pixel_id'],
    ];

    public function toArray(Request $request): array
    {
        return $this->resource
            ->sortBy(fn (Setting $setting) => $this->sortKey($setting))
            ->values()
            ->groupBy('group')
            ->sortBy(fn ($settings, string $group) => $this->groupIndex($group))
            ->map(fn ($settings, string $group) => (new SettingGroupResource($group, $settings))->resolve($request))
            ->values()
            ->all();
    }

    private function sortKey(Setting $setting): string
    {
        return str_pad((string) $this->groupIndex($setting->group), 3, '0', STR_PAD_LEFT)
            .'-'
            .str_pad((string) $this->keyIndex($setting), 3, '0', STR_PAD_LEFT)
            .'-'
            .$setting->key;
    }

    private function groupIndex(string $group): int
    {
        $index = array_search($group, self::GROUP_ORDER, true);

        return $index === false ? 999 : $index;
    }

    private function keyIndex(Setting $setting): int
    {
        $index = array_search($setting->key, self::KEY_ORDER[$setting->group] ?? [], true);

        return $index === false ? 999 : $index;
    }
}
