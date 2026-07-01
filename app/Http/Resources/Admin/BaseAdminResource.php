<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseAdminResource extends JsonResource
{
    public static $wrap = null;

    protected function dateTime(mixed $value): ?string
    {
        return $value?->format('Y-m-d H:i');
    }

    protected function humanDate(mixed $value): ?string
    {
        return $value?->diffForHumans();
    }

    protected function money(mixed $cents, ?string $currency): string
    {
        return number_format(((int) ($cents ?? 0)) / 100, 2).' '.($currency ?? '');
    }

    protected function kilobytes(mixed $bytes): string
    {
        return number_format(((int) ($bytes ?? 0)) / 1024, 1).' KB';
    }

    protected function locales(): array
    {
        return config('app.supported_locales', ['en', 'ar']);
    }

    protected function translation(mixed $model, string $attribute, string $locale): mixed
    {
        return $model->getTranslation($attribute, $locale, false);
    }

    protected function resourceCollection(string $resourceClass, mixed $resources, Request $request): array
    {
        return $resourceClass::collection($resources)->resolve($request);
    }
}
