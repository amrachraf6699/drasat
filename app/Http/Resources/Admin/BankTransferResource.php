<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class BankTransferResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'customer' => $this->user?->name ?? $this->order?->user?->name ?? __('admin.common.guest'),
            'email' => $this->user?->email ?? $this->order?->user?->email,
            'order_number' => $this->order?->order_number,
            'items' => $this->order?->items?->pluck('title')->join(', ') ?: __('admin.common.no_items'),
            'status' => $this->status,
            'amount' => $this->money($this->amount_cents, $this->currency),
            'reviewer' => $this->reviewer?->name,
            'created_at' => $this->dateTime($this->created_at),
            'reviewed_at' => $this->dateTime($this->reviewed_at),
        ];
    }
}
