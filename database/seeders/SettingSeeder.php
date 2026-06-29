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
                    'value' => $item['value'],
                    'is_translatable' => $item['is_translatable'],
                ],
            );

            if (! $setting->is_translatable) {
                $setting->translations()->delete();

                continue;
            }

            foreach (['en', 'ar'] as $locale) {
                $setting->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['value' => $item[$locale] ?? $item['value']],
                );
            }
        }
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
            ['group' => 'payments', 'input_type' => 'text', 'key' => 'default_currency', 'value' => 'EGP', 'is_translatable' => false],
        ];
    }
}
