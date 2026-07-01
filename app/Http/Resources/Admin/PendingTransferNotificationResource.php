<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class PendingTransferNotificationResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => __('admin.layout.pending_transfer_title'),
            'body' => __('admin.layout.pending_transfer_body', [
                'reference' => $this->reference_number ?: __('admin.common.no_reference'),
                'customer' => $this->user?->name ?? $this->order?->user?->name ?? __('admin.common.guest'),
            ]),
            'href' => route('admin.bank-transfers.index'),
            'created_at' => $this->humanDate($this->created_at),
        ];
    }
}
