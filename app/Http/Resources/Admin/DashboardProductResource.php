<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class DashboardProductResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->resource->title(app()->getLocale()),
            'price' => $this->money($this->price_cents, $this->currency),
            'status' => ucfirst($this->status),
            'updated_at' => $this->dateTime($this->updated_at),
        ];
    }
}
