<?php

namespace App\Models;

use App\Helpers\PaymentLogger;
use App\Helpers\Utility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_reference',
        'service_type',
        'amount',
        'amount_after',
        'payload',
        'provider_response',
        'status',
        'vtpass_transaction_id',
        'vtpass_webhook_data',
        'wallet_id',
        'provider',
        'channel',
        'type',
        'amount_before',
        'description',
        'category',
        'idempotency_key',
        'image'
    ];

    protected $casts = [
        'vtpass_webhook_data' => 'array',
         'payload' => 'array',
    ];


    public function user(){

        return $this->belongsTo(User::class, 'user_id');
    }

    public static function create_transaction($data):array{

        $ref = Utility::txRef("bills", "system", true);
        $transaction = TransactionLog::create([
                    'user_id'        => auth()->id(),
                    'transaction_reference' => $ref,
                    'service_type'   => $data['service_type'] ?? null,
                    'amount'         => self::amount($data, $data['amount']),
                    'amount_before' => $data['amount_before'] ?? null,
                    'amount_after'   =>  $data['amount_after'] ?? 0,
                    'payload'        => json_encode($data),
                    'status'         => $data['status'] ?? 'pending',
                    'wallet_id' => $data['wallet_id'] ?? null,
                    'provider'  =>  $data['provider'],
                    'category'  =>  $data['service_type'],
                     'image' => request()->image,
                     'idempotency_key' => request()->attributes->get('idempotency_key'),
                     'channel' => 'Internal',
                    'type' => $data['type'],
                    'description' => $data['description'] ?? null,
        ]);
        return [
            'transaction_id' => $transaction->id
        ];
    }

    private static function amount(array $data, float $amount): float
    {
        if (($data['vending_type'] ?? null) === 'giftcard') {
            $dollarRate = Settings::get('dollar_conversion_rate', 1600);
            $amount *= $dollarRate;
            $data['amount'] = $amount; // Optional: remove if not needed here
        }

        return $amount;
    }



    public static function update_info(string $transactionId, array $data): void
    {

        # $providerData = json_decode($data['provider_response'] ?? '{}', true);
        $providerResponse = $data['provider_response'] ?? [];

        $providerData = is_array($providerResponse)
            ? $providerResponse
            : json_decode($providerResponse, true);

        if (isset($providerData['content']['transactions']['transactionId'])) {
            $data['vtpass_transaction_id'] = $providerData['content']['transactions']['transactionId'];
        }
        TransactionLog::where('id', $transactionId)
            ->orWhere('transaction_reference', $transactionId)
            ->update([
                'status' => $data['status'],
                'vtpass_transaction_id' => $data['vtpass_transaction_id'] ?? null,
                "provider_response" => ($providerResponse),
            ]);
    }



    public function wallet(){

        return $this->belongsTo(Wallet::class);
    }


    /**
     * Check user limits (wallet and daily).
     *
     * @param User $user
     * @param float $amount
     * @return array [bool status, string|null message]
     */
    public static function checkLimits(User $user, float $amount): array
    {
        $tier = $user->tier;

        if (!$tier) {
            PaymentLogger::log("Tier not found for user ID {$user->id}");
            return [false, 'No tier limits found for your account.'];
        }

        # proceed to check if account is restricted
        if ($user->is_account_restricted || $user->is_ban) {
            $reason = $user->is_ban ? 'Your account is currently banned.' : 'Your account is currently restricted.';
            PaymentLogger::log("User ID {$user->id} login blocked: {$reason}");
            return [false, $reason];
        }

        if ($user->wallet->has_exceeded_limit AND  $user->wallet->status === "locked" ) {
            $reason = 'Your account is locked. Please contact support or upgrade your tier to regain full access.';
            PaymentLogger::log("User ID {$user->id} account locked: {$reason}");
            return [false, $reason];
        }


        if ($user->wallet->status === "locked" ) {
            $reason = 'Your account is locked. Please contact support to resolve this issue';
            PaymentLogger::log("User ID {$user->id} account locked: {$reason}");
            return [false, $reason];
        }

        #  Wallet balance limit check (optional feature)
        if (!is_null($tier->wallet_balance)) {
            $walletAmount = optional($user->wallet)->amount ?? 0;
            $total = $walletAmount + $amount;

            if (($walletAmount + $amount) > $tier->wallet_balance) {
                PaymentLogger::log("User ID {$user->id} exceeded wallet balance limit. Attempted: ₦{$total}, Max: ₦{$tier->wallet_balance}");
                return [
                    false,
                    "Wallet limit exceeded. Max allowed: ₦" . number_format($tier->wallet_balance)
                ];
            }
        }

        #  Daily transaction total
        $todayTotal = $user->transactions()
            ->whereDate('created_at', now())
            ->sum('amount');
        $totalAmountToday = $todayTotal + $amount;

        if (($todayTotal + $amount) > $tier->daily_limit) {
            PaymentLogger::log("User ID {$user->id} exceeded daily limit. Attempted: ₦{$totalAmountToday}, Max: ₦{$tier->daily_limit}");
            return [
                false,
                "Daily transaction limit exceeded. Max allowed: ₦" . number_format($tier->daily_limit),
                [
                    'amount_used_today' => $todayTotal,
                    'attempted_transaction' => $amount,
                    'max_allowed' => $tier->daily_limit,
                    'remaining_quota' => $tier->daily_limit - $todayTotal
                ],
            ];
        }

        #  Passed all checks
        return [true, null];
    }


    #  In TransactionLog.php (Model)
    public static function isDuplicateTransfer($userId, $amount, $identifier): ?self
    {
        return self::where('user_id', $userId)
            ->where('amount', $amount)
            ->where('service_type', 'in-app-transfer')
            ->where('created_at', '>', now()->subSeconds(5))
            ->where('payload', 'LIKE', '%' . $identifier . '%')
            ->first();
    }


    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCashInflow($query)
    {
        return $query->where('type', 'credit')
            ->where('status', 'successful')
            ->whereIn('category', ['deposit', 'external_bank_deposit']);
    }


    public static function totalCashInflow(): float
    {
        return static::cashInflow()->sum('amount');
    }


    public function scopeCashOutflow($query)
    {
        return $query->where('type', 'debit')
            ->where('status', 'successful');
    }


    public static function totalCashOutflow(): float
    {
        return static::cashOutflow()->sum('amount');
    }


    public function scopeInternalPlatformCredits($query)
    {
        return $query->where('type', 'credit')
            ->where('status', 'successful')
            ->whereIn('category', ['wallet_transfer_in', 'referral', 'refund']);
    }

    public static function totalInternalCredits(): float
    {
        return static::internalPlatformCredits()->sum('amount');
    }

    public function scopeReferralCredits($query)
    {
        return $query->where('type', 'credit')
            ->where('status', 'successful')
            ->where('category', 'referral');
    }


    public static function totalReferralCredits(): float
    {
        return static::referralCredits()->sum('amount');
    }

    public static function countSuccessfulBankTransfersToday(): int
    {
        return static::where('category', 'external_bank_transfer')
            ->where('status', 'successful')
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }





    public static function countFailedTransferRefundsToday(): int
    {
//        Transfer Refunds (Failed Transfers Today):
        return static::where('type', 'credit')
            ->where('category', 'refund')
            ->whereIn('service_type', ['bank_transfer_failed', 'bank_transfer_reversed'])
            ->where('status', 'successful')
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }


    public static function countFailedBankTransfersToday(): int
    {
        return static::whereIn('category', ['bank_transfer_reversed', 'bank_transfer_failed'])
            ->where('status', 'failed')
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }

    public static function countSuccessfulBillTransactionsToday(): int
    {
        return static::where('provider', "vtpass")
            ->where('status', 'successful')
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }



    public function paystackTransaction()
    {
        return $this->hasOne(PaystackTransaction::class, 'reference', 'transaction_reference');
    }







}
