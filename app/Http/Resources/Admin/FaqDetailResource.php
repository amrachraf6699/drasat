<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class FaqDetailResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        $data = FaqResource::make($this->resource)->resolve($request);

        return [
            'title' => $data['question_en'] ?: $data['question_ar'] ?: __('admin.faqs.title'),
            'subtitle' => __('admin.common.statuses.'.$this->status),
            'backHref' => route('admin.faqs.index'),
            'stats' => [
                ['label' => __('admin.common.status'), 'value' => __('admin.common.statuses.'.$this->status)],
                ['label' => __('admin.common.sort'), 'value' => $this->sort_order],
                ['label' => __('admin.common.created'), 'value' => $this->dateTime($this->created_at)],
                ['label' => __('admin.common.date'), 'value' => $this->dateTime($this->updated_at)],
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
                        'id' => "{$this->id}-{$locale}",
                        'locale' => $locale,
                        'question' => $this->translation($this->resource, 'question', $locale),
                        'answer' => $this->translation($this->resource, 'answer', $locale),
                    ])->values(),
                ],
            ],
        ];
    }
}
