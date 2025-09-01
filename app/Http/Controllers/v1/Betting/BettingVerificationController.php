<?php

namespace App\Http\Controllers\v1\Betting;

use App\Helpers\RedbillerLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Models\TransactionLog;
use App\Notifications\BettingPaymenSucessful;
use App\Notifications\BettingPaymentFailed;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;


class BettingVerificationController extends Controller
{
    /**
     * Verify transaction status - called by frontend immediately after getting reference
     * POST /api/betting/verify-transaction
     */
    public function verifyTransaction($reference): \Illuminate\Http\JsonResponse
    {
        $transaction = TransactionLog::where('transaction_reference', $reference)
            ->where('category', 'betting_fund')
            ->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction not found'
            ], 404);
        }

        # Perform the verification
        $verificationResult = $this->performVerification($transaction);

        return response()->json([
            'status' => 'success',
            'data' => $verificationResult
        ]);
    }

    /**
     * Perform actual verification with Redbiller
     */
    private function performVerification($transaction): array
    {
        try {
            $transaction->update(['updated_at' => now()]);
            $response = Http::timeout(30)
                ->withHeaders([
                    'Private-Key' => config('services.redbiller.private_key'),
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.live.redbiller.com/1.4/bills/betting/account/payment/status', [
                    'reference' => $transaction->transaction_reference
                ]);

            if (!$response->successful()) {
                return [
                    'reference' => $transaction->transaction_reference,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'message' => 'Verification service temporarily unavailable',
                    'should_retry' => true,
                    'retry_after' => 60, // seconds
                    'created_at' => $transaction->created_at->toISOString(),
                    'updated_at' => $transaction->updated_at->toISOString()
                ];
            }
            $responseData = $response->json();
            if (!isset($responseData['response']) || $responseData['response'] != 200 ||
                !isset($responseData['status']) || $responseData['status'] !== 'true') {
                $this->handleFailedVerification($transaction, $responseData);

                $transaction->user->notify(new BettingPaymentFailed($transaction));

                return [
                    'reference' => $transaction->transaction_reference,
                    'status' => $transaction->fresh()->status,
                    'amount' => $transaction->amount,
                    'message' => $responseData['message'] ?? 'Transaction verification failed',
                    'should_retry' => $transaction->fresh()->status !== 'failed',
                    'retry_after' => 60,
                    'created_at' => $transaction->created_at->toISOString(),
                    'updated_at' => $transaction->updated_at->toISOString()
                ];
            }
            $metaStatus = $responseData['meta']['status'] ?? 'Unknown';
            $newTransactionStatus = $this->mapMetaStatusToTransactionStatus($metaStatus);
            $this->updateTransactionStatus($transaction, $newTransactionStatus, $responseData);
            $transaction->refresh();

            $transaction->user->notify(new BettingPaymenSucessful($transaction));

            RedbillerLogger::log('Betting verification successful', [
                'transaction_id' => $transaction->id,
                'reference' => $transaction->transaction_reference,
                'meta_status' => $metaStatus,
                'new_status' => $newTransactionStatus,
                'user_id' => $transaction->user_id
            ]);

            return [
                'reference' => $transaction->transaction_reference,
            ];

        } catch (Exception $e) {
            RedbillerLogger::log('Frontend verification exception', [
                'transaction_id' => $transaction->id,
                'reference' => $transaction->transaction_reference,
                'error' => $e->getMessage(),
                'user_id' => $transaction->user_id
            ]);

            return [
                'reference' => $transaction->transaction_reference,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'message' => 'Verification error occurred',
                'should_retry' => true,
                'retry_after' => 60,
                'created_at' => $transaction->created_at->toISOString(),
                'updated_at' => $transaction->updated_at->toISOString()
            ];
        }
    }



    /**
     * Normalize and record failed transaction
     */
    private function formatFailedTransaction(array $transaction): array
    {
        return [
            'status' => false,
            'data' => [
                'reference'      => $transaction['reference'] ?? null,
                'status'         => 'failed',
                'amount'         => $transaction['amount'] ?? 0,
                'meta_status'    => 'Failed',
                'message'        => $transaction['message'] ?? 'Transaction failed',
                'should_retry'   => $transaction['should_retry'] ?? false,
                'retry_after'    => $transaction['retry_after'] ?? null,
                'customer_profile' => $transaction['customer_profile'] ?? [],
                'charge'         => $transaction['charge'] ?? 0,
                'wallet_balance' => $transaction['wallet_balance'] ?? null,
                'created_at'     => now(),
                'updated_at'     => now(),
                'provider_response' => [
                    'response' => $transaction['provider_response']['response'] ?? null,
                    'status'   => false,
                    'message'  => 'Failed',
                    'details'  => $transaction['provider_response']['details'] ?? [],
                    'meta'     => [
                        'status' => 'Failed'
                    ]
                ]
            ]
        ];
    }


    /**
     * Update transaction status and handle refunds if needed
     */
    private function updateTransactionStatus($transaction, $newStatus, $responseData): void
    {
        DB::beginTransaction();

        try {
            $oldStatus = $transaction->status;
            if ($newStatus === 'failed' && $oldStatus !== 'failed') {
                $this->refundFailedTransaction($transaction);
            }
            $transaction->update([
                'status' => $newStatus,
                'provider_response' => json_encode($responseData),
                'verified_at' => now()
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Handle failed verification attempts
     */
    private function handleFailedVerification($transaction, $responseData): void
    {
            DB::beginTransaction();
            try {
                $this->refundFailedTransaction($transaction);

                $transaction->update([
                    'status' => 'failed',
                    'provider_response' => json_encode(array_merge($responseData, [
                        'verification_failed' => true,
                        'max_attempts_reached' => true,
                        'refunded' => true
                    ]))
                ]);

                DB::commit();
            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }

    }

    /**
     * Refund failed transaction
     */
    private function refundFailedTransaction($transaction): void
    {
        try {
            $user = $transaction->user;
            $wallet = $user->wallet;

            if ($wallet) {
                $wallet->increment('amount', $transaction->amount);
                $transaction->update([
                    'amount_after' => $wallet->fresh()->amount
                ]);
                RedbillerLogger::log('Transaction refunded due to failure', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                    'amount_refunded' => $transaction->amount,
                    'new_wallet_balance' => $wallet->fresh()->amount
                ]);
            }
        } catch (Exception $e) {
            RedbillerLogger::log('Failed to refund transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Map Redbiller meta status to our transaction status
     */
    private function mapMetaStatusToTransactionStatus($metaStatus): string
    {
        switch (strtolower($metaStatus)) {
            case 'approved':
            case 'successful':
            case 'completed':
                return 'successful';

            case 'failed':
            case 'declined':
            case 'rejected':
                return 'failed';

            case 'pending':
            case 'processing':
            default:
                return 'processing';
        }
    }

    /**
     * Get user-friendly status message
     */
    private function getStatusMessage($status): string
    {
        switch ($status) {
            case 'completed':
                return 'Transaction completed successfully!';
            case 'failed':
                return 'Transaction failed. Amount refunded to wallet.';
            case 'processing':
                return 'Transaction is being processed...';
            case 'pending':
                return 'Transaction is pending...';
            default:
                return 'Transaction status unknown';
        }
    }

    /**
     * Get recommended retry delay based on status
     */
    private function getRetryDelay($status): int
    {
        switch ($status) {
            case 'completed':
            case 'failed':
                return 0; // No need to retry
            case 'processing':
                return 30; // Check every 30 seconds
            case 'pending':
            default:
                return 15; // Check every 15 seconds for pending
        }
    }


}
