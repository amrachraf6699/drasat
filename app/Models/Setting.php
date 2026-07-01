<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Setting extends Model
{
    use HasTranslations;

    public array $translatable = [
        'value',
    ];

    protected $fillable = [
        'group',
        'input_type',
        'key',
        'value',
        'is_translatable',
    ];

    protected $casts = [
        'is_translatable' => 'boolean',
    ];

    public function setSharedValue(mixed $value): self
    {
        if ($value === null) {
            return $this->forgetTranslations('value', true);
        }

        return $this->setTranslations('value', array_fill_keys($this->supportedLocales(), $value));
    }

    private function supportedLocales(): array
    {
        return config('app.supported_locales', ['en', 'ar']);
    }
}
