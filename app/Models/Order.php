<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_method',
        'subtotal_cents',
        'total_cents',
        'currency',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'subtotal_cents' => 'integer',
        'total_cents' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bankTransfer(): HasOne
    {
        return $this->hasOne(BankTransfer::class);
    }
}
