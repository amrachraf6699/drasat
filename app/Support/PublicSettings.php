<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PublicSettings
{
    public function all(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $settings = $this->settings();

        return [
            'general' => [
                'site_name' => $this->value($settings, 'general', 'site_name', $locale) ?: config('app.name', 'Drasa'),
                'site_logo_url' => $this->assetUrl($this->value($settings, 'general', 'site_logo', $locale)),
                'support_email' => $this->value($settings, 'general', 'support_email', $locale),
            ],
            'social' => [
                'facebook' => $this->value($settings, 'social', 'facebook', $locale),
                'x' => $this->value($settings, 'social', 'x', $locale),
                'whatsapp' => $this->value($settings, 'social', 'whatsapp', $locale),
                'linkedin' => $this->value($settings, 'social', 'linkedin', $locale),
                'twitter' => $this->value($settings, 'social', 'twitter', $locale),
            ],
            'analytics' => [
                'google_analytics_id' => $this->value($settings, 'analytics', 'google_analytics_id', $locale),
                'google_tag_manager_id' => $this->value($settings, 'analytics', 'google_tag_manager_id', $locale),
                'meta_pixel_id' => $this->value($settings, 'analytics', 'meta_pixel_id', $locale),
            ],
        ];
    }

    private function settings(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Setting::query()
            ->whereIn('group', ['general', 'social', 'analytics'])
            ->get()
            ->keyBy(fn (Setting $setting) => "{$setting->group}.{$setting->key}")
            ->all();
    }

    private function value(array $settings, string $group, string $key, string $locale): ?string
    {
        $setting = $settings["{$group}.{$key}"] ?? null;

        if (! $setting) {
            return null;
        }

        $value = $setting->getTranslation('value', $locale, false)
            ?: $setting->getTranslation('value', config('app.fallback_locale', 'en'), false)
            ?: $setting->value;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
