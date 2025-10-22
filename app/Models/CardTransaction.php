<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'card_id',
        'transaction_id',
        'authorization_id',
        'amount',
        'fee',
        'total_amount',
        'currency',
        'merchant_name',
        'merchant_id',
        'channel',
        'type',
        'status',
        'amount_before',
        'amount_after',
        'payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_before' => 'decimal:2',
        'amount_after' => 'decimal:2',
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(NairaCard::class, 'card_id', 'card_id');
    }
}
