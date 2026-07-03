<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;

class DocumentResource extends BaseStorefrontResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->original_name ?: basename((string) $this->path),
            'mime_type' => $this->mime_type,
            'size' => $this->fileSize($this->size),
            'file_type' => $this->file_type,
            'extension' => $this->extension(),
            'download_url' => $this->canDownload($request)
                ? route('library.documents.download', $this->resource)
                : null,
        ];
    }

    protected function extension(): ?string
    {
        $name = $this->original_name ?: $this->path;

        if (! $name) {
            return null;
        }

        $extension = pathinfo((string) $name, PATHINFO_EXTENSION);

        return $extension ? strtoupper($extension) : null;
    }

    protected function canDownload(Request $request): bool
    {
        $user = $request->user('web');

        if (! $user || $this->file_type !== 'document' || ! $this->mediable_id) {
            return false;
        }

        return $user
            ->purchases()
            ->where('product_id', $this->mediable_id)
            ->exists();
    }
}
