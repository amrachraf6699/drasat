<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'status', 'sort']);
        $query = Faq::query();

        $query
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    foreach ($this->translatedSearchColumns(['question', 'answer']) as $column) {
                        $query->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status));

        match ($filters['sort'] ?? 'sort_order') {
            'newest' => $query->latest(),
            'oldest' => $query->oldest(),
            default => $query->orderBy('sort_order')->latest(),
        };

        return Inertia::render('Admin/Faqs', [
            'filters' => $filters,
            'faqs' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Faq $faq) => $this->serializeFaq($faq)),
        ]);
    }

    public function show(Faq $faq): Response
    {
        $data = $this->serializeFaq($faq);

        return Inertia::render('Admin/Detail', [
            'title' => $data['question_en'] ?: $data['question_ar'] ?: __('admin.faqs.title'),
            'subtitle' => __('admin.common.statuses.'.$faq->status),
            'backHref' => route('admin.faqs.index'),
            'stats' => [
                ['label' => __('admin.common.status'), 'value' => __('admin.common.statuses.'.$faq->status)],
                ['label' => __('admin.common.sort'), 'value' => $faq->sort_order],
                ['label' => __('admin.common.created'), 'value' => $faq->created_at?->format('Y-m-d H:i')],
                ['label' => __('admin.common.date'), 'value' => $faq->updated_at?->format('Y-m-d H:i')],
            ],
            'fields' => [
                ['label' => __('admin.common.question_en'), 'value' => $data['question_en']],
                ['label' => __('admin.common.answer_en'), 'value' => $data['answer_en']],
                ['label' => __('admin.common.question_ar'), 'value' => $data['question_ar']],
                ['label' => __('admin.common.answer_ar'), 'value' => $data['answer_ar']],
            ],
            'sections' => [
                [
                    'title' => __('admin.common.translated'),
                    'columns' => [
                        ['key' => 'locale', 'label' => __('admin.layout.switch_language')],
                        ['key' => 'question', 'label' => __('admin.common.question_en')],
                        ['key' => 'answer', 'label' => __('admin.common.answer_en')],
                    ],
                    'rows' => collect($this->locales())->map(fn (string $locale) => [
                        'id' => "{$faq->id}-{$locale}",
                        'locale' => $locale,
                        'question' => $this->translation($faq, 'question', $locale),
                        'answer' => $this->translation($faq, 'answer', $locale),
                    ])->values(),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $faq = Faq::create([
            'question' => $this->translationsFromData($data, 'question'),
            'answer' => $this->translationsFromData($data, 'answer'),
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', __('admin.flash.faq_created'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $data = $this->validated($request);
        $faq->update([
            'question' => $this->translationsFromData($data, 'question'),
            'answer' => $this->translationsFromData($data, 'answer'),
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', __('admin.flash.faq_updated'));
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return back()->with('status', __('admin.flash.faq_deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question_en' => ['required', 'string', 'max:255'],
            'question_ar' => ['required', 'string', 'max:255'],
            'answer_en' => ['required', 'string'],
            'answer_ar' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function serializeFaq(Faq $faq): array
    {
        return [
            'id' => $faq->id,
            'question_en' => $this->translation($faq, 'question', 'en'),
            'question_ar' => $this->translation($faq, 'question', 'ar'),
            'answer_en' => $this->translation($faq, 'answer', 'en'),
            'answer_ar' => $this->translation($faq, 'answer', 'ar'),
            'status' => $faq->status,
            'sort_order' => $faq->sort_order,
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

    private function translation(Faq $faq, string $attribute, string $locale): mixed
    {
        return $faq->getTranslation($attribute, $locale, false);
    }

    private function translatedSearchColumns(array $attributes): array
    {
        $columns = [];

        foreach ($attributes as $attribute) {
            foreach ($this->locales() as $locale) {
                $columns[] = "{$attribute}->{$locale}";
            }
        }

        return $columns;
    }

    private function locales(): array
    {
        return config('app.supported_locales', ['en', 'ar']);
    }
}
