<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\FaqDetailResource;
use App\Http\Resources\Admin\FaqResource;
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
            'faqs' => FaqResource::collection($query->paginate(10)->withQueryString()),
        ]);
    }

    public function show(Request $request, Faq $faq): Response
    {
        return Inertia::render('Admin/Detail', FaqDetailResource::make($faq)->resolve($request));
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

    private function translationsFromData(array $data, string $attribute): array
    {
        $translations = [];

        foreach ($this->locales() as $locale) {
            $translations[$locale] = $data["{$attribute}_{$locale}"] ?? null;
        }

        return $translations;
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
