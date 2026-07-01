<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class FaqResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_en' => $this->translation($this->resource, 'question', 'en'),
            'question_ar' => $this->translation($this->resource, 'question', 'ar'),
            'answer_en' => $this->translation($this->resource, 'answer', 'en'),
            'answer_ar' => $this->translation($this->resource, 'answer', 'ar'),
            'status' => $this->status,
            'sort_order' => $this->sort_order,
        ];
    }
}
