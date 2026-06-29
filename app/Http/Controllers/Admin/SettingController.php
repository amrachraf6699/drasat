<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'group', 'input_type', 'translatable', 'sort']);
        $query = Setting::query()->with('translations');

        $query
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('group', 'like', "%{$search}%")
                        ->orWhere('key', 'like', "%{$search}%")
                        ->orWhere('value', 'like', "%{$search}%")
                        ->orWhereHas('translations', fn ($query) => $query->where('value', 'like', "%{$search}%"));
                });
            })
            ->when($filters['group'] ?? null, fn ($query, string $group) => $query->where('group', $group))
            ->when($filters['input_type'] ?? null, fn ($query, string $type) => $query->where('input_type', $type))
            ->when(($filters['translatable'] ?? null) === 'yes', fn ($query) => $query->where('is_translatable', true))
            ->when(($filters['translatable'] ?? null) === 'no', fn ($query) => $query->where('is_translatable', false));

        match ($filters['sort'] ?? 'key') {
            'newest' => $query->latest(),
            'oldest' => $query->oldest(),
            default => $query->orderBy('group')->orderBy('key'),
        };

        return Inertia::render('Admin/Settings', [
            'filters' => $filters,
            'filterOptions' => [
                'groups' => Setting::query()->select('group')->distinct()->orderBy('group')->pluck('group')->values(),
            ],
            'settings' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Setting $setting) => $this->serializeSetting($setting)),
        ]);
    }

    public function show(Setting $setting): Response
    {
        $setting->load('translations');
        $data = $this->serializeSetting($setting);

        return Inertia::render('Admin/Detail', [
            'title' => "{$setting->group}.{$setting->key}",
            'subtitle' => __('admin.common.input_types.'.$setting->input_type),
            'backHref' => route('admin.settings.index'),
            'stats' => [
                ['label' => __('admin.common.group'), 'value' => $setting->group],
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
                    'rows' => $setting->translations->map(fn ($translation) => [
                        'id' => $translation->id,
                        'locale' => $translation->locale,
                        'value' => $translation->value,
                    ])->values(),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $setting = Setting::create([
            'group' => $data['group'],
            'input_type' => $data['input_type'],
            'key' => $data['key'],
            'value' => $data['value'] ?? null,
            'is_translatable' => (bool) ($data['is_translatable'] ?? false),
        ]);

        $this->syncTranslations($setting, $data);

        return back()->with('status', __('admin.flash.setting_created'));
    }

    public function update(Request $request, Setting $setting): RedirectResponse
    {
        $data = $this->validated($request, $setting);

        $setting->update([
            'group' => $data['group'],
            'input_type' => $data['input_type'],
            'key' => $data['key'],
            'value' => $data['value'] ?? null,
            'is_translatable' => (bool) ($data['is_translatable'] ?? false),
        ]);

        $this->syncTranslations($setting, $data);

        return back()->with('status', __('admin.flash.setting_updated'));
    }

    public function destroy(Setting $setting): RedirectResponse
    {
        $setting->delete();

        return back()->with('status', __('admin.flash.setting_deleted'));
    }

    private function validated(Request $request, ?Setting $setting = null): array
    {
        return $request->validate([
            'group' => ['required', 'string', 'max:80'],
            'input_type' => ['required', 'in:text,textarea,image,url,email,number,boolean'],
            'key' => ['required', 'string', 'max:120'],
            'value' => ['nullable', 'string'],
            'value_en' => ['nullable', 'string'],
            'value_ar' => ['nullable', 'string'],
            'is_translatable' => ['nullable', 'boolean'],
        ]);
    }

    private function syncTranslations(Setting $setting, array $data): void
    {
        if (! $setting->is_translatable) {
            $setting->translations()->delete();

            return;
        }

        foreach (['en', 'ar'] as $locale) {
            $setting->translations()->updateOrCreate(
                ['locale' => $locale],
                ['value' => $data["value_{$locale}"] ?? null],
            );
        }
    }

    private function serializeSetting(Setting $setting): array
    {
        $translations = $setting->translations->keyBy('locale');

        return [
            'id' => $setting->id,
            'group' => $setting->group,
            'input_type' => $setting->input_type,
            'key' => $setting->key,
            'value' => $setting->value,
            'value_en' => $translations->get('en')?->value,
            'value_ar' => $translations->get('ar')?->value,
            'is_translatable' => $setting->is_translatable,
        ];
    }
}
