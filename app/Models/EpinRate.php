<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpinRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_network',
        'value',
        'min_quantity',
        'max_quantity',
        'rate',
    ];

    /**
     * Get the applicable rate for a purchase.
     */
    public static function getRate(string $cardNetwork, float $value, int $quantity): ?float
    {
        return self::where('card_network', $cardNetwork)
            ->where('value', $value)
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->where('max_quantity', '>=', $quantity)
                    ->orWhereNull('max_quantity');
            })
            ->orderBy('min_quantity', 'desc')
            ->value('rate');
    }
}
