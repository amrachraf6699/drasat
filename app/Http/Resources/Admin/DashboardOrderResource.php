<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class DashboardOrderResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->dateTime($this->created_at),
            'status' => ucfirst($this->status),
            'amount' => $this->money($this->total_cents, $this->currency),
            'product' => $this->items->first()?->title ?? __('admin.common.no_items'),
            'customer' => $this->user?->name ?? __('admin.common.guest'),
            'number' => '#'.$this->order_number,
        ];
    }
}
