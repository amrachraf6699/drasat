<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class OrderResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer' => $this->user?->name ?? __('admin.common.guest'),
            'email' => $this->user?->email,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'total' => $this->money($this->total_cents, $this->currency),
            'items' => $this->items->pluck('title')->join(', ') ?: __('admin.common.no_items'),
            'items_count' => $this->items_count,
            'created_at' => $this->dateTime($this->created_at),
        ];
    }
}
