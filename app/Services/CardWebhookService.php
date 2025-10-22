<?php

namespace App\Services;

use App\Models\CardTransaction;
use App\Models\NairaCard;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;


class CardWebhookService
{
    public function handleAuthorization(array $payload): array
    {
        $cardId = $payload['card_Id'] ?? null;
        $authorizationId = $payload['authorization.request'] ?? null;
        $merchantAmount = $payload['merchantAmount'] ?? 0;

        if (!$cardId || !$authorizationId) {
            return ['APPROVE' => 'NO', 'reason' => 'Invalid request data'];
        }

        $nairaCard = NairaCard::where('card_id', $cardId)->first();
        if (!$nairaCard) {
            return ['APPROVE' => 'NO', 'reason' => 'Card not found'];
        }

        if ($nairaCard->status !== 'active') {
            return ['APPROVE' => 'NO', 'reason' => 'Card is not active'];
        }

        $wallet = Wallet::where('user_id', $nairaCard->user_id)->first();
        if (!$wallet) {
            return ['APPROVE' => 'NO', 'reason' => 'Wallet not found'];
        }

        if ($wallet->amount < $merchantAmount) {
            return ['APPROVE' => 'NO', 'reason' => 'Insufficient balance'];
        }

        CardTransaction::create([
            'user_id' => $nairaCard->user_id,
            'card_id' => $cardId,
            'authorization_id' => $authorizationId,
            'amount' => $merchantAmount,
            'currency' => $payload['currency'] ?? 'NGN',
            'merchant_name' => $payload['merchant']['name'] ?? null,
            'merchant_id' => $payload['merchant']['merchantId'] ?? null,
            'channel' => $payload['channel'] ?? null,
            'type' => $payload['type'] ?? 'purchase',
            'status' => 'pending',
            'payload' => $payload,
        ]);

        return ['APPROVE' => 'YES'];
    }

    public function handleTransactionCreated(array $payload): array
    {
        $transactionId = $payload['transaction.created'] ?? null;
        $cardId = $payload['card_Id'] ?? null;
        $merchantAmount = $payload['merchantAmount'] ?? 0;
        $fee = $payload['fee'] ?? 0;
        $totalAmount = $merchantAmount + $fee;

        if (!$transactionId || !$cardId) {
            return ['success' => false, 'message' => 'Invalid data'];
        }

        $nairaCard = NairaCard::where('card_id', $cardId)->first();
        if (!$nairaCard) {
            return ['success' => false, 'message' => 'Card not found'];
        }

        DB::transaction(function () use ($nairaCard, $transactionId, $totalAmount, $merchantAmount, $fee, $payload, $cardId) {
            $wallet = Wallet::where('user_id', $nairaCard->user_id)->lockForUpdate()->first();

            if ($wallet && $wallet->amount >= $totalAmount) {
                $before = $wallet->amount;
                $wallet->decrement('amount', $totalAmount);
                $after = $wallet->amount;

                CardTransaction::updateOrCreate(
                    ['transaction_id' => $transactionId],
                    [
                        'user_id' => $nairaCard->user_id,
                        'card_id' => $cardId,
                        'transaction_id' => $transactionId,
                        'amount' => $merchantAmount,
                        'fee' => $fee,
                        'total_amount' => $totalAmount,
                        'currency' => $payload['currency'] ?? 'NGN',
                        'merchant_name' => $payload['merchant']['name'] ?? null,
                        'merchant_id' => $payload['merchant']['merchantId'] ?? null,
                        'channel' => $payload['channel'] ?? null,
                        'type' => $payload['type'] ?? 'purchase',
                        'status' => 'completed',
                        'amount_before' => $before,
                        'amount_after' => $after,
                        'payload' => $payload,
                    ]
                );
            }
        });

        return ['success' => true];
    }

    public function handleTransactionRefund(array $payload): array
    {
        $refundTransactionId = $payload['transaction.refund'] ?? null;
        $cardId = $payload['card_Id'] ?? null;
        $merchantAmount = $payload['merchantAmount'] ?? 0;
        $fee = $payload['fee'] ?? 0;
        $totalAmount = $merchantAmount + $fee;

        if (!$refundTransactionId || !$cardId) {
            return ['success' => false, 'message' => 'Invalid data'];
        }

        $nairaCard = NairaCard::where('card_id', $cardId)->first();
        if (!$nairaCard) {
            return ['success' => false, 'message' => 'Card not found'];
        }

        DB::transaction(function () use ($nairaCard, $refundTransactionId, $totalAmount, $merchantAmount, $fee, $payload, $cardId) {
            $wallet = Wallet::where('user_id', $nairaCard->user_id)->lockForUpdate()->first();

            if ($wallet) {
                $before = $wallet->amount;
                $wallet->increment('amount', $totalAmount);
                $after = $wallet->amount;

                CardTransaction::create([
                    'user_id' => $nairaCard->user_id,
                    'card_id' => $cardId,
                    'transaction_id' => $refundTransactionId,
                    'amount' => $merchantAmount,
                    'fee' => $fee,
                    'total_amount' => $totalAmount,
                    'currency' => $payload['currency'] ?? 'NGN',
                    'merchant_name' => $payload['merchant']['name'] ?? null,
                    'merchant_id' => $payload['merchant']['merchantId'] ?? null,
                    'channel' => $payload['channel'] ?? null,
                    'type' => 'refund',
                    'status' => 'completed',
                    'amount_before' => $before,
                    'amount_after' => $after,
                    'payload' => $payload,
                ]);
            }
        });

        return ['success' => true];
    }
}
