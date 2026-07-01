<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseStorefrontResource extends JsonResource
{
    public static $wrap = null;

    protected function money(mixed $cents, ?string $currency): string
    {
        $amount = ((int) ($cents ?? 0)) / 100;
        $decimals = ((int) ($cents ?? 0)) % 100 === 0 ? 0 : 2;

        return trim(($currency ?? '').' '.number_format($amount, $decimals));
    }

    protected function date(mixed $value): ?string
    {
        return $value?->format('M j, Y');
    }

    protected function dateTime(mixed $value): ?string
    {
        return $value?->format('M j, Y g:i A');
    }

    protected function fileSize(mixed $bytes): string
    {
        $bytes = (int) ($bytes ?? 0);

        if ($bytes <= 0) {
            return '0 KB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 1).' KB';
    }

    protected function translated(mixed $model, string $attribute): ?string
    {
        if (! method_exists($model, 'getTranslation')) {
            return $model->{$attribute};
        }

        return $model->getTranslation($attribute, app()->getLocale(), false)
            ?: $model->getTranslation($attribute, config('app.fallback_locale', 'en'), false);
    }
}
