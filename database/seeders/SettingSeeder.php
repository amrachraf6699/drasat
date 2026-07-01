<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = $this->settings();
        $expectedSettings = collect($settings)
            ->map(fn (array $item) => "{$item['group']}.{$item['key']}")
            ->all();

        Setting::query()
            ->get()
            ->reject(fn (Setting $setting) => in_array("{$setting->group}.{$setting->key}", $expectedSettings, true))
            ->each->delete();

        foreach ($settings as $item) {
            $setting = Setting::updateOrCreate(
                ['group' => $item['group'], 'key' => $item['key']],
                [
                    'input_type' => $item['input_type'],
                    'is_translatable' => $item['is_translatable'],
                ],
            );

            if ($setting->is_translatable) {
                $setting->setTranslations('value', $this->translations($item));
            } else {
                $setting->setSharedValue($item['value']);
            }

            $setting->save();
        }
    }

    private function translations(array $item): array
    {
        return collect(config('app.supported_locales', ['en', 'ar']))
            ->mapWithKeys(fn (string $locale) => [$locale => $item[$locale] ?? $item['value']])
            ->all();
    }

    private function settings(): array
    {
        return [
            ['group' => 'general', 'input_type' => 'text', 'key' => 'site_name', 'value' => 'Dirasat', 'is_translatable' => true, 'en' => 'Dirasat', 'ar' => 'دراسات'],
            ['group' => 'general', 'input_type' => 'image', 'key' => 'site_logo', 'value' => null, 'is_translatable' => false],
            ['group' => 'general', 'input_type' => 'email', 'key' => 'support_email', 'value' => 'support@drasa.test', 'is_translatable' => false],
            ['group' => 'social', 'input_type' => 'url', 'key' => 'facebook', 'value' => 'https://facebook.com/drasa', 'is_translatable' => false],
            ['group' => 'social', 'input_type' => 'url', 'key' => 'x', 'value' => 'https://x.com/drasa', 'is_translatable' => false],
            ['group' => 'social', 'input_type' => 'url', 'key' => 'whatsapp', 'value' => 'https://wa.me/201000000000', 'is_translatable' => false],
            ['group' => 'social', 'input_type' => 'url', 'key' => 'linkedin', 'value' => 'https://linkedin.com/company/drasa', 'is_translatable' => false],
            ['group' => 'social', 'input_type' => 'url', 'key' => 'twitter', 'value' => 'https://twitter.com/drasa', 'is_translatable' => false],
            ['group' => 'analytics', 'input_type' => 'text', 'key' => 'google_analytics_id', 'value' => null, 'is_translatable' => false],
            ['group' => 'analytics', 'input_type' => 'text', 'key' => 'google_tag_manager_id', 'value' => null, 'is_translatable' => false],
            ['group' => 'analytics', 'input_type' => 'text', 'key' => 'meta_pixel_id', 'value' => null, 'is_translatable' => false],
        ];
    }
}
