<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const GROUP_ORDER = ['general', 'social', 'analytics', 'payments'];

    private const KEY_ORDER = [
        'general' => ['site_name', 'site_logo', 'support_email'],
        'social' => ['facebook', 'x', 'whatsapp', 'linkedin', 'twitter'],
        'analytics' => ['google_analytics_id', 'google_tag_manager_id', 'meta_pixel_id'],
    ];

    public function index(): Response
    {
        $settings = Setting::query()
            ->get()
            ->sortBy(fn (Setting $setting) => $this->sortKey($setting))
            ->values();

        return Inertia::render('Admin/Settings', [
            'groups' => $settings
                ->groupBy('group')
                ->sortBy(fn ($settings, string $group) => $this->groupIndex($group))
                ->map(fn ($settings, string $group) => [
                    'key' => $group,
                    'label' => __("admin.settings.groups.{$group}"),
                    'settings' => $settings->map(fn (Setting $setting) => $this->serializeSetting($setting))->values(),
                ])
                ->values(),
        ]);
    }

    public function show(Setting $setting): Response
    {
        $data = $this->serializeSetting($setting);

        return Inertia::render('Admin/Detail', [
            'title' => $data['label'],
            'subtitle' => __("admin.settings.groups.{$setting->group}"),
            'backHref' => route('admin.settings.index'),
            'stats' => [
                ['label' => __('admin.common.group'), 'value' => __("admin.settings.groups.{$setting->group}")],
                ['label' => __('admin.common.input_type'), 'value' => __('admin.common.input_types.'.$setting->input_type)],
                ['label' => __('admin.common.translated'), 'value' => $setting->is_translatable ? __('admin.common.translated') : __('admin.common.single_value')],
                ['label' => __('admin.common.date'), 'value' => $setting->updated_at?->format('Y-m-d H:i')],
            ],
            'fields' => [
                ['label' => __('admin.common.key'), 'value' => $setting->key],
                ['label' => __('admin.common.value'), 'value' => $data['value']],
                ['label' => __('admin.common.value_en'), 'value' => $data['value_en']],
                ['label' => __('admin.common.value_ar'), 'value' => $data['value_ar']],
                ['label' => __('admin.common.created'), 'value' => $setting->created_at?->format('Y-m-d H:i')],
                ['label' => __('admin.common.date'), 'value' => $setting->updated_at?->format('Y-m-d H:i')],
            ],
            'sections' => [
                [
                    'title' => __('admin.common.translated'),
                    'columns' => [
                        ['key' => 'locale', 'label' => __('admin.layout.switch_language')],
                        ['key' => 'value', 'label' => __('admin.common.value')],
                    ],
                    'rows' => collect($this->locales())->map(fn (string $locale) => [
                        'id' => "{$setting->id}-{$locale}",
                        'locale' => $locale,
                        'value' => $this->translation($setting, 'value', $locale),
                    ])->values(),
                ],
            ],
        ]);
    }

    public function update(Request $request, Setting $setting): RedirectResponse
    {
        $data = $this->validated($request, $setting);

        if ($setting->input_type === 'image') {
            $this->updateImageSetting($request, $setting);

            return back()->with('status', __('admin.flash.setting_updated'));
        }

        if ($setting->is_translatable) {
            $setting
                ->setTranslations('value', $this->translationsFromData($data, 'value'))
                ->save();
        } else {
            $setting
                ->setSharedValue($data['value'] ?? null)
                ->save();
        }

        return back()->with('status', __('admin.flash.setting_updated'));
    }

    private function validated(Request $request, Setting $setting): array
    {
        $valueRules = match ($setting->input_type) {
            'email' => ['nullable', 'email', 'max:255'],
            'url' => ['nullable', 'url', 'max:1000'],
            'number' => ['nullable', 'numeric'],
            'boolean' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            default => ['nullable', 'string'],
        };

        if ($setting->input_type === 'image') {
            return $request->validate([
                'value' => $valueRules,
            ]);
        }

        return $request->validate([
            'value' => $valueRules,
            'value_en' => ['nullable', 'string'],
            'value_ar' => ['nullable', 'string'],
        ]);
    }

    private function updateImageSetting(Request $request, Setting $setting): void
    {
        if (! $request->hasFile('value')) {
            return;
        }

        $previousPath = $setting->value;
        $path = $request->file('value')->store('settings', 'public');

        $setting
            ->setSharedValue($path)
            ->save();

        if ($previousPath && str_starts_with($previousPath, 'settings/')) {
            Storage::disk('public')->delete($previousPath);
        }
    }

    private function serializeSetting(Setting $setting): array
    {
        return [
            'id' => $setting->id,
            'group' => $setting->group,
            'input_type' => $setting->input_type,
            'key' => $setting->key,
            'label' => __("admin.settings.labels.{$setting->key}"),
            'help' => __("admin.settings.help.{$setting->key}"),
            'value' => $setting->value,
            'value_url' => $this->settingUrl($setting),
            'value_en' => $this->translation($setting, 'value', 'en'),
            'value_ar' => $this->translation($setting, 'value', 'ar'),
            'is_translatable' => $setting->is_translatable,
        ];
    }

    private function translationsFromData(array $data, string $attribute): array
    {
        $translations = [];

        foreach ($this->locales() as $locale) {
            $translations[$locale] = $data["{$attribute}_{$locale}"] ?? null;
        }

        return $translations;
    }

    private function translation(Setting $setting, string $attribute, string $locale): mixed
    {
        return $setting->getTranslation($attribute, $locale, false);
    }

    private function locales(): array
    {
        return config('app.supported_locales', ['en', 'ar']);
    }

    private function settingUrl(Setting $setting): ?string
    {
        if (! $setting->value) {
            return null;
        }

        if (filter_var($setting->value, FILTER_VALIDATE_URL)) {
            return $setting->value;
        }

        return $setting->input_type === 'image'
            ? Storage::disk('public')->url($setting->value)
            : null;
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
