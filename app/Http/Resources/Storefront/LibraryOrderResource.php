<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;

class LibraryOrderResource extends BaseStorefrontResource
{
    public function toArray(Request $request): array
    {
        $transfer = $this->bankTransfer;
        $product = $this->items->first()?->product;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'order_number' => $this->order_number,
            'created_at' => $this->date($this->created_at),
            'payment_method' => $this->payment_method,
            'total' => $this->money($this->total_cents, $this->currency),
            'product' => $product ? ProductResource::make($product)->resolve($request) : null,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'quantity' => $item->quantity,
                'total' => $this->money($item->total_cents, $this->currency),
            ])->values()->all(),
            'transfer' => $transfer ? [
                'status' => $transfer->status,
                'status_label' => $this->transferStatusLabel($transfer->status),
                'reference_number' => $transfer->reference_number,
                'proof_name' => $transfer->proofMedia?->original_name,
                'submitted_at' => $this->dateTime($transfer->created_at),
                'admin_note' => $transfer->admin_note,
            ] : null,
        ];
    }

    protected function statusLabel(): string
    {
        return match ($this->status) {
            'paid' => __('storefront.library.purchased'),
            'cancelled' => __('storefront.library.rejected'),
            default => __('storefront.library.pending'),
        };
    }

    protected function transferStatusLabel(string $status): string
    {
        return match ($status) {
            'approved' => __('storefront.library.transfer_approved'),
            'rejected' => __('storefront.library.transfer_rejected'),
            default => __('storefront.library.transfer_pending'),
        };
    }
}
