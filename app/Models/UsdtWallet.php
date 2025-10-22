<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsdtWallet extends Model
{
    protected $fillable = [
        'user_id',
        'address',
        'balance',
        'network',
        'mode',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(UsdtTransaction::class);
    }

}
