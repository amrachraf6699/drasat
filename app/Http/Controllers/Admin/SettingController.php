<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SettingDetailResource;
use App\Http\Resources\Admin\SettingGroupsResource;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(Request $request): Response
    {
        $settings = Setting::query()->get();

        return Inertia::render('Admin/Settings', [
            'groups' => SettingGroupsResource::make($settings)->resolve($request),
        ]);
    }

    public function show(Request $request, Setting $setting): Response
    {
        return Inertia::render('Admin/Detail', SettingDetailResource::make($setting)->resolve($request));
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

    private function translationsFromData(array $data, string $attribute): array
    {
        $translations = [];

        foreach ($this->locales() as $locale) {
            $translations[$locale] = $data["{$attribute}_{$locale}"] ?? null;
        }

        return $translations;
    }

    private function locales(): array
    {
        return config('app.supported_locales', ['en', 'ar']);
    }
}
