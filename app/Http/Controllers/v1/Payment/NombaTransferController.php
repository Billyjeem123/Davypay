<?php

namespace App\Http\Controllers\v1\Payment;

use App\Helpers\PaymentLogger;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\NombaTransferRequest;
use App\Models\NombaTransaction;
use App\Models\PlatformFee;
use App\Models\TransactionFee;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Services\NombaService;
use Exception;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NombaTransferController extends Controller
{

    protected $nombaService;

    public function __construct(NombaService $nombaService)
    {
        $this->nombaService = $nombaService;
    }

    /**
     * @throws Exception
     */
    public function transferToBank(NombaTransferRequest $request)
    {
        $validated = $request->validated();

        $result = $this->processTransfer($validated);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Transfer successful',
                'data' => $result['data']
            ]);
        }

        return  $result;
    }


    /**
     * @throws Exception
     */
    public function processTransfer(array $data): array
    {
        $user = Auth::user();
        $reference = $this->generateReference();

        $payload = [
            'amount' => $data['amount'],
            'accountNumber' => $data['account_number'],
            'accountName' => $data['account_name'],
            'bankCode' => $data['bank_code'],
            'merchantTxRef' => $reference,
            'senderName' => $user->first_name . " " . $user->last_name,
            'narration' => $data['narration'] ?? '',
            'transaction_pin' => $data['transaction_pin'],
        ];


        # 1. Verify PIN
        if (!$this->verifyTransactionPin($user, $payload['transaction_pin'])) {
            return [
                'success' => false,
                'message' => "Invalid transaction PIN",
                'data' => []
            ];
        }

        [$limitOk, $limitMessage] = TransactionLog::checkLimits($user, $data['amount']);
            if (!$limitOk) {
            return [
                'success' => false,
                'message' => $limitMessage,
                'data' => []
            ];
        }

        $feeCalculation = $this->calculateTransactionFee('nomba', 'transfer', $payload['amount']);
        $totalAmount = $data['amount'] + $feeCalculation['fee'];

        $wallet = $this->lockWalletForBankTransfer($user, $totalAmount, $reference);
        $transaction = $this->createTransactionLog($user, $payload, $reference, 'pending');
        $this->createNombaTransaction($transaction, $reference, $payload, 'pending');

        # 3. Call Nomba first before logging/debiting
        try {
           $response = $this->nombaService->makeAuthenticatedRequest('POST', '/transfers/bank', $payload);
            if ($response && $response->successful()) {
                $responseData = $response->json();

                $balanceBeforeTotal = $wallet->fresh()->amount;
                $this->debitAndUnlockWallet($wallet, $totalAmount, $reference);
                $this->updateTransactionSuccess($transaction, $responseData);
                $balanceAfterTotal = $wallet->fresh()->amount;
                $this->recordFeeTransactionOnly($transaction, $feeCalculation, 'nomba', $balanceBeforeTotal, $balanceAfterTotal);

                PaymentLogger::log('Nomba Transfer initialized successfully');

                return [
                    'success' => true,
                    'message' => 'Transfer initiated successfully',
                    'data' => $responseData['data'] ?? []
                ];
            }

            # Handle API failure
            $errorData = $response ? $response->json() : ['description' => 'No response received'];

            return [
                'success' => false,
                'message' => 'Transfer failed',
                'error' => $errorData,
                'status' => $response ? $response->status() : 500
            ];

        } catch (\Exception $e) {
            # Critical: Unlock funds on failure if they were locked
            if ($wallet && $totalAmount > 0 && $reference) {
                try {
                    $this->unlockFundsOnFailure($wallet, $totalAmount, $reference);
                } catch (Exception $unlockError) {
                    PaymentLogger::error('Failed to unlock funds after transfer failure', [
                        'user_id' => $user->id,
                        'reference' => $reference,
                        'locked_amount' => $totalAmount,
                        'unlock_error' => $unlockError->getMessage()
                    ]);
                }
            }
            PaymentLogger::error('Nomba transfer to bank failed', [
                'user_id' => $user->id,
                'reference' => $reference,
                'payload' => $payload,
                'exception' => $e->getMessage(),
            ]);
            # Update transaction as failed
            if (isset($transaction)) {
                $this->updateTransactionFailed($transaction, $paystackTransaction ?? null, $e->getMessage());
            }
            return [
                'success' => false,
                'message' => 'An unexpected error occurred during the transfer.',
                'error' => $e->getMessage(),
                'status' => 500
            ];

        }
    }

    /**
     * Unlock funds when transfer fails (emergency cleanup)
     */
    private function unlockFundsOnFailure(Wallet $wallet, float $amount, string $reference): void
    {
        $affected = Wallet::where('id', $wallet->id)
            ->where('locked_amount', '>=', $amount)
            ->decrement('locked_amount', $amount);

        if ($affected > 0) {
            PaymentLogger::log('Funds unlocked after transfer failure', [
                'wallet_id' => $wallet->id,
                'unlocked_amount' => $amount,
                'reference' => $reference
            ]);
        }
    }


    /**
     * Atomically debit wallet and unlock funds in a single operation
     */
    private function debitAndUnlockWallet(Wallet $wallet, float $amount, string $reference): void
    {
        $balanceBefore = $wallet->amount;
        $lockedBefore = $wallet->locked_amount;

        if ($wallet->locked_amount < $amount) {
            throw new TransferException('Locked funds are insufficient for this debit.', 400);
        }

        PaymentLogger::log('Debiting and unlocking wallet', [
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'locked_amount_before' => $lockedBefore,
            'reference' => $reference
        ]);

        // Single atomic operation: debit amount and reduce locked_amount
        $affected = Wallet::where('id', $wallet->id)
            ->where('amount', '>=', $amount)
            ->where('locked_amount', '>=', $amount)
            ->update([
                'amount' => DB::raw('amount - ' . $amount),
                'locked_amount' => DB::raw('locked_amount - ' . $amount)
            ]);

        if ($affected === 0) {
            throw new TransferException('Failed to debit wallet - insufficient funds or locked amount', 400);
        }

        PaymentLogger::log('Wallet debited and unlocked successfully', [
            'wallet_id' => $wallet->id,
            'amount_processed' => $amount,
            'balance_after' => $wallet->fresh()->amount,
            'locked_amount_after' => $wallet->fresh()->locked_amount,
            'reference' => $reference
        ]);
    }


    /**
     * Lock wallet for bank transfer and validate available balance
     */
    public function lockWalletForBankTransfer(User $sender, float $amount, string $reference): Wallet
    {
        $wallet = Wallet::where('user_id', $sender->id)
            ->lockForUpdate()
            ->first();

        if (!$wallet) {
            throw new TransferException('Wallet not found', 404);
        }

        $availableBalance = $wallet->amount - $wallet->locked_amount;
        if ($amount > $availableBalance) {
            throw ValidationException::withMessages([
                'product_id' => 'Insufficient available balance.',
            ]);
        }

        // Lock funds (increment locked_amount)
        $this->lockAmount($wallet, $amount, $reference);

        return $wallet;
    }


    /**
     * Lock amount in wallet during processing
     */
    private function lockAmount(Wallet $wallet, float $amount, string $reference): void
    {
        $wallet->increment('locked_amount', $amount);
        $wallet->refresh();
        PaymentLogger::log('Amount locked in wallet', [
            'wallet_id'      => $wallet->id,
            'amount_locked'  => $amount,
            'total_locked'   => $wallet->locked_amount,
            'reference'      => $reference,
        ]);
    }

    /**
     * Update transaction records on failure
     */
    private function updateTransactionFailed($transaction, string $errorMessage): void
    {
        $transaction->update([
            'status' => 'failed',
            'amount_before' => $transaction->wallet->amount,
            'amount_after' => $transaction->wallet->amount,
            'provider_response' => array_merge($transaction->payload, [
                'failed_at' => now(),
                'error_message' => $errorMessage
            ])
        ]);

    }

    /**
     * Generate unique transfer reference
     */
    private function generateReference()
    {
        return Utility::txRef("bank-transfer", "nomba", false);
    }

    /**
     * Verify user's transaction PIN
     */
    private function verifyTransactionPin($user, string $pin): bool
    {
        #  Implement your PIN verification logic here
        #  This could be hashed PIN comparison
        return password_verify($pin, $user->pin);
    }


    /**
     * Create transaction log
     */
    private function createTransactionLog(User $user, array $transferData, string $reference, string $status)
    {
        unset($transferData['transaction_pin']);
        return TransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'type' => 'debit',
            'category' => 'external_bank_transfer',
            'amount' => $transferData['amount'],
            'transaction_reference' => $reference,
            'service_type' => 'external_bank_transfer',
            'amount_before' => $user->wallet->amount,
            'amount_after' => $user->wallet->amount - $transferData['amount'],
            'status' => $status,
            'idempotency_key' => request()->attributes->get('idempotency_key'),
            'provider' => 'nomba',
            'channel' => 'nomba_transfer',
            'currency' => 'NGN',
            'image' => $this->getBankLogoByCode($transferData['bankCode']),
            'description' => 'Sent to ' . $transferData['accountName'],
            'payload' => $transferData,
        ]);
    }


    public  function getBankLogoByCode($code)
    {
        $banks = json_decode(file_get_contents(public_path('banks.json')), true);
        foreach ($banks as $bank) {
            if ($bank['code'] === $code) {
                return $bank['logo'];
            }
        }

        return request()->image;
    }

    /**
     * Create Nomba transaction record
     */
    private function createNombaTransaction($transaction, string $reference, array $transferData, string $status)
    {
        return NombaTransaction::create([
            'transaction_id' => $transaction->id,
            'reference' => $reference,
//            'merchant_tx_ref' => $responseData['data']['meta']['merchantTxRef'] ?? '',
//            'order_id' => $responseData['data']['id'],

            'merchant_tx_ref' => "null",
            'order_id' => "null",

            'amount' => $transferData['amount'],
            'status' => $status,
            'metadata' => json_encode([
                'type' => 'transfer',
                'account_number' => $transferData['accountNumber'],
                'account_name' => $transferData['accountName'],
                'bank_code' => $transferData['bankCode']
            ]),
        ]);
    }

    private function updateTransactionSuccess($transaction, $transferResponse): void
    {
        $transaction->update([
            'status' => 'success',
            'provider_response' => array_merge($transaction->payload, [
                'completed_at' => now(),
                'paystack_response' => $transferResponse
            ])
        ]);

    }
    /**
     * Get transaction type for platform fees
     */
    private function getTransactionType($transaction): string
    {
        // Map transaction categories to platform fee types
        $typeMapping = [
            'deposit' => 'deposit',
            'transfer' => 'transfer',
            'withdrawal' => 'withdrawal',
            'payment' => 'payment',
        ];

        return $typeMapping[$transaction->category] ?? 'other';
    }

    /**
     * Calculate transaction fee based on provider, type, and amount (reusable)
     */


    /**
     * Record fee transaction without debiting wallet (wallet already debited)
     * But still track balance changes for audit purposes
     */
    private function recordFeeTransactionOnly($transaction, $feeCalculation, $provider, $balanceBeforeTotal, $balanceAfterTotal): void
    {
        if (!$feeCalculation['success'] || ($feeCalculation['fee'] ?? 0) <= 0) {
            return;
        }

        // Calculate what the balance was before and after the fee portion
        $transferAmount = $transaction->amount;
        $feeAmount = $feeCalculation['fee'];

        // Balance tracking for fee portion:
        // - Before fee deduction = after transfer deduction
        // - After fee deduction = final balance
        $feeBalanceBefore = $balanceBeforeTotal - $transferAmount;  // Balance after transfer, before fee
        $feeBalanceAfter = $balanceAfterTotal;  // Final balance after both transfer and fee



        $feeTransaction = $this->createCompletedFeeTransaction(
            $transaction->user,
            $transaction,
            $feeAmount,
            $transaction->transaction_reference,
            $feeCalculation,
            $provider,
            $feeBalanceBefore,
            $feeBalanceAfter
        );

        PaymentLogger::log('Fee transaction recorded (wallet already debited)', [
            'fee_transaction_id' => $feeTransaction->id,
            'parent_transaction_id' => $transaction->id,
            'fee_amount' => $feeAmount,
            'fee_percentage' => $feeCalculation['fee_percentage'] ?? 0,
            'balance_before_fee' => $feeBalanceBefore,
            'balance_after_fee' => $feeBalanceAfter,
            'transfer_amount' => $transferAmount,
            'total_debited' => $transferAmount + $feeAmount
        ]);

    }

    /**
     * Create completed fee transaction record (reusable for all providers)
     */
    private function createCompletedFeeTransaction($user, $mainTransaction, $feeAmount, $reference, $feeCalculation, $provider, $balanceBefore, $balanceAfter)
    {
        $feeTransaction = TransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'type' => 'debit', # Fee is a debit from user's perspective
            'amount' => $feeAmount,
            'category' => 'charges',
            'transaction_reference' => $reference,
            'service_type' => 'transaction_fee',
            'amount_before' => $balanceBefore, // Balance before fee deduction
            'amount_after' => $balanceAfter,   // Balance after fee deduction
            'status' => 'successful', # Created as successful since payment confirmed
            'provider' => $provider,
            'channel' => $provider ,
            'currency' => "NGN",
            'description' => "Charges",
            'paid_at' => now(),
            'payload' => json_encode([
                'parent_transaction_id' => $mainTransaction->id,
                'fee_calculation' => $feeCalculation,
                'processed_at' => now(),
            ]),
        ]);

        # Also create record in PlatformFees table for better analytics
        PlatformFee::create([
            'transaction_id' => $mainTransaction->id,
            'user_id' => $user->id,
            'fee_amount' => $feeAmount,
            'fee_percentage' => $feeCalculation['fee_percentage'] ?? 0,
            'provider' => $provider,
            'transaction_type' => $this->getTransactionType($mainTransaction), // deposit, transfer, etc.
        ]);

        return $feeTransaction;
    }



    /**
     * Calculate transaction fee based on provider, type, and amount (reusable)
     */
    private function calculateTransactionFee($provider, $type, $amount): array
    {
        try {
            $feeRule = TransactionFee::where('provider', $provider)
                ->where('type', $type)
                ->where('min', '<=', $amount)
                ->where('max', '>=', $amount)
                ->first();

            if (!$feeRule) {
                // No fee structure found - continue with zero fee
                return [
                    'success' => true,
                    'message' => "No fee structure configured for {$provider} {$type} - proceeding with zero fee",
                    'fee' => 0,
                    'fee_percentage' => 0,
                    'fee_rule_id' => null,
                ];
            }

            $fee = ($amount * $feeRule->fee) / 100; // Assuming fee is percentage

            return [
                'success' => true,
                'message' => 'Fee calculated successfully',
//                'fee' => round($fee, 2),
                'fee' => 2,
                'fee_percentage' => $feeRule->fee,
                'fee_rule_id' => $feeRule->id,
                'amount_range' => [
                    'min' => $feeRule->min,
                    'max' => $feeRule->max,
                ],
            ];
        } catch (\Exception $e) {
            # Even on error, continue with zero fee instead of stopping transaction
            PaymentLogger::error('Fee calculation error - proceeding with zero fee', [
                'provider' => $provider,
                'type' => $type,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => true,
                'message' => 'Fee calculation failed - proceeding with zero fee',
                'fee' => 0,
                'fee_percentage' => 0,
                'fee_rule_id' => null,
            ];
        }
    }




}

