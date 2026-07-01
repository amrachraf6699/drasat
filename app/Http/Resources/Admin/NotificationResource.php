<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class NotificationResource extends BaseAdminResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->data['title'] ?? __('admin.layout.notification'),
            'body' => $this->data['body'] ?? null,
            'href' => $this->data['href'] ?? null,
            'created_at' => $this->humanDate($this->created_at),
        ];
    }
}
