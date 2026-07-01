<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class DashboardTransferResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->dateTime($this->created_at),
            'customer' => $this->user?->name ?? $this->order?->user?->name ?? __('admin.common.guest'),
            'amount' => $this->money($this->amount_cents, $this->currency),
            'reference' => '#'.$this->reference_number,
        ];
    }
}
