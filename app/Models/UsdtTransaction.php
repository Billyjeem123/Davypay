<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsdtTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'usdt_wallet_id',
        'hash',
        'reference',
        'type',
        'action',
        'chain',
        'amount',
        'fees',
        'channel',
        'description',
        'confirmations',
    ];

    public function usdtWallet(): BelongsTo
    {
        return $this->belongsTo(UsdtWallet::class);
    }

    /**
     * Log a new transaction safely with idempotency checks.
     *
     * @param UsdtWallet $wallet
     * @param array $payload
     * @return static|null
     */
    public static function log(UsdtWallet $wallet, array $payload): ?self
    {
        $hash      = $payload['hash'] ?? null;
        $reference = $payload['reference'] ?? null;

        $existing = self::query()
            ->when($hash, fn($q) => $q->where('hash', $hash))
            ->when($reference, fn($q) => $q->orWhere('reference', $reference))
            ->first();

        if ($existing) {
            return $existing;
        }

        return self::create([
            'usdt_wallet_id' => $wallet->id,
            'hash'           => $hash,
            'reference'      => $reference,
            'type'           => $payload['type'] ?? null,
            'action'         => $payload['action'] ?? null,
            'chain'          => $payload['chain'] ?? null,
            'amount'         => $payload['amount'] ?? 0,
            'fees'           => $payload['fees'] ?? 0,
            'channel'        => $payload['channel'] ?? null,
            'description'    => $payload['description'] ?? null,
            'confirmations'  => $payload['confirmations'] ?? null,
        ]);
    }
}
