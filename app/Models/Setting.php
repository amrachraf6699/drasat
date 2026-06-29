<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setting extends Model
{
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

    public function translations(): HasMany
    {
        return $this->hasMany(SettingTranslation::class);
    }
}
