<?php

namespace App\Services;

use App\Helpers\PaymentLogger;
use App\Helpers\Utility;
use App\Models\PaystackTransaction;
use App\Models\PlatformFee;
use App\Models\TransactionFee;
use App\Models\TransactionLog;
use App\Models\TransferRecipient;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaystackTransferService
{
    private $secretKey;
    private $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.sk');
    }

    /**
     * Transfer funds from user wallet to bank account
     */
    public function transferToBank(User $user, array $transferData): array
    {
        return DB::transaction(function () use ($user, $transferData) {
            $wallet = null;
            $transaction = null;
            $paystackTransaction = null;
            $reference = null;
            $totalAmount = 0;

            $existing = $this->getExistingIdempotentTransfer($transferData['idempotency_key']);
            if ($existing) {
                return $existing;
            }

            try {
                $this->validateTransferData($transferData);
                $reference = $this->generateReference();

                $feeCalculation = $this->calculateTransactionFee('paystack', 'transfer', $transferData['amount']);
                $totalAmount = $transferData['amount'] + $feeCalculation['fee'];

                $wallet = $this->lockWalletForBankTransfer($user, $totalAmount, $reference);

                $recipient = $this->createOrGetRecipient($transferData);
                $transaction = $this->createTransactionLog($user, $transferData, $reference, 'pending');

                #Create pending Paystack transaction
                $paystackTransaction = $this->createPaystackTransaction($transaction, $reference, $transferData, 'pending');
                $transferResponse = $this->initiatePaystackTransfer($recipient, $transferData, $reference);

                if ($transferResponse['status']) {
                    # Capture balance before deductions
                    $balanceBeforeTotal = $wallet->fresh()->amount;

                    $this->debitAndUnlockWallet($wallet, $totalAmount, $reference);
                    $this->updateTransactionSuccess($transaction, $paystackTransaction, $transferResponse);

                    $balanceAfterTotal = $wallet->fresh()->amount;
                    $this->recordFeeTransactionOnly($transaction, $feeCalculation, 'paystack', $balanceBeforeTotal, $balanceAfterTotal);

                    PaymentLogger::log('Paystack Transfer initialized successfully');

                    return [
                        'success' => true,
                        'message' => 'Transfer initiated successfully',
                        'data' => [
                            'reference' => $reference,
                            'transfer_code' => $transferResponse['data']['transfer_code'],
                            'amount' => $transferData['amount'],
                            'recipient_name' => $transferData['account_name'],
                            'bank_name' => $transferData['bank_name']
                        ]
                    ];
                } else {
                    throw new Exception($transferResponse['message'] ?? 'Transfer failed');
                }
            } catch (Exception $e) {
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

                // Update transaction as failed
                if (isset($transaction)) {
                    $this->updateTransactionFailed($transaction, $paystackTransaction ?? null, $e->getMessage());
                }

                PaymentLogger::error('Transfer failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'transfer_data' => $transferData
                ]);

                throw $e; // important: rethrow to trigger rollback
            }
        });
    }



    /**
     * Check if a transfer with this idempotency key was already completed successfully.
     *
     * @param string $idempotencyKey
     * @return array|null
     */
    private function getExistingIdempotentTransfer( $idempotencyKey): ?array
    {
        $transaction = TransactionLog::where('idempotency_key', $idempotencyKey)
            ->first();


        if (!$transaction) {
            return null; // No prior successful transfer
        }

        PaymentLogger::log(
            'Idempotency check: Existing successful transfer found',
            ['idempotency_key' => $idempotencyKey]
        );

        return [
            'success' => true,
            'message' => 'Transfer was already completed successfully.',
            'data' => [
                'reference' => $transaction->reference, // From DB
                'transfer_code' => [], // From DB
            ]
        ];
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
     * Validate transfer data
     */
    private function validateTransferData(array $data)
    {
        $required = ['amount', 'account_number', 'bank_code', 'account_name', 'narration'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }

        if ($data['amount'] <= 0) {
            throw new Exception('Amount must be greater than zero');
        }

        if ($data['amount'] < 100) { #  Minimum transfer amount
            throw new Exception('Minimum transfer amount is ₦100');
        }
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
     * Create or get transfer recipient
     */
    private function createOrGetRecipient(array $transferData)
    {
        #  Check if recipient already exists
        $existingRecipient = TransferRecipient::where([
            'account_number' => $transferData['account_number'],
            'bank_code' => $transferData['bank_code']
        ])->first();

        if ($existingRecipient && $existingRecipient->recipient_code) {
            return $existingRecipient;
        }

        #  Create new recipient with Paystack
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/transferrecipient', [
            'type' => 'nuban',
            'name' => $transferData['account_name'],
            'account_number' => $transferData['account_number'],
            'bank_code' => $transferData['bank_code'],
            'currency' => 'NGN'
        ]);

        $responseData = $response->json();

        if (!$response->successful() || !$responseData['status']) {
            throw new Exception($responseData['message'] ?? 'Failed to create transfer recipient');
        }

        #  Save recipient to database
        return TransferRecipient::updateOrCreate([
            'account_number' => $transferData['account_number'],
            'bank_code' => $transferData['bank_code'],

        ], [
            'account_name' => $transferData['account_name'],
            'bank_name' => $transferData['bank_name'] ?? '',
            'recipient_code' => $responseData['data']['recipient_code'],
            'is_active' => true,
            'user_id' => Auth::id()
        ]);
    }

    /**
     * Generate unique transfer reference
     */
    private function generateReference()
    {
        return Utility::txRef("bank-transfer", "paystack", false);
    }

    /**
     * Create transaction log
     */
    private function createTransactionLog(User $user, array $transferData, string $reference, string $status)
    {
        return TransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'type' => 'debit',
            'category' => 'external_bank_transfer',
            'amount' => $transferData['amount'],
            'transaction_reference' => $reference,
            'service_type' => 'external_bank_transfer',
            'amount_before' => $user->wallet->amount,
            'amount_after' =>  $user->wallet->amount - $transferData['amount'],
            'status' => $status,
            'provider' => 'paystack',
            'channel' => 'paystack_transfer',
            'image' => $this->getBankLogoByCode($transferData['bankCode']),
            'currency' => 'NGN',
            'idempotency_key' => $transferData['idempotency_key'],
            'description' => 'Sent to '. $transferData['account_name'] ,
            'payload' => $transferData
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
     * Create Paystack transaction record
     */
    private function createPaystackTransaction($transaction, string $reference, array $transferData, string $status)
    {
        return PaystackTransaction::create([
            'transaction_id' => $transaction->id,
            'reference' => $reference,
            'amount' => $transferData['amount'],
            'status' => $status,
            'gateway_response' => 'Transfer initiated',
            'metadata' => [
                'type' => 'transfer',
                'account_number' => $transferData['account_number'],
                'account_name' => $transferData['account_name'],
                'bank_code' => $transferData['bank_code']
            ],
        ]);
    }








    /**
     * Initiate transfer with Paystack
     */

    private function initiatePaystackTransfer($recipient, array $transferData, string $reference)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/transfer', [
            'source' => 'balance',
            'amount' => $transferData['amount'] * 100, // convert to kobo
            'recipient' => $recipient->recipient_code,
            'reason' => $transferData['narration'] ?? 'Wallet transfer',
            'reference' => $reference
        ]);

        $responseData = $response->json();

        if (!$response->successful() || !($responseData['status'] ?? false)) {
            $message = $responseData['message'] ?? 'Unknown error';
            $errors = $responseData['data']['errors'] ?? null;

            // Combine message and specific field errors if any
            $detailedError = is_array($errors)
                ? $message . ' - ' . json_encode($errors)
                : $message;

            throw new \Exception('Paystack transfer failed: ' . $detailedError);
        }

        return $responseData;
    }


    /**
     * Update transaction records on success
     */



    private function updateTransactionSuccess($transaction, $paystackTransaction, $transferResponse)
    {
        $transaction->update([
            'status' => 'success',
            'provider_response' => array_merge($transaction->payload, [
                'completed_at' => now(),
                'paystack_response' => $transferResponse
            ])
        ]);

        $paystackTransaction->update([
            'status' => 'success',
            'gateway_response' => $transferResponse['message'],
            'metadata' => array_merge($paystackTransaction->metadata, [
                'transfer_code' => $transferResponse['data']['transfer_code'] ?? null,
                'paystack_data' => $transferResponse['data']
            ])
        ]);
    }

    /**
     * Update transaction records on failure
     */
    private function updateTransactionFailed($transaction, $paystackTransaction, string $errorMessage): void
    {
        $transaction->update([
            'status' => 'failed',
            'amount_before' => $transaction->wallet->amount,
            'amount_after' =>  $transaction->wallet->amount,
            'provider_response' => array_merge($transaction->payload, [
                'failed_at' => now(),
                'error_message' => $errorMessage
            ])
        ]);

        if ($paystackTransaction) {
            $paystackTransaction->update([
                'status' => 'failed',
                'gateway_response' => $errorMessage
            ]);
        }
    }

    /**
     * Verify transfer status (for webhook or manual verification)
     */
    public function verifyTransfer(string $transferCode)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey
        ])->get($this->baseUrl . '/transfer/' . $transferCode);

        return $response->json();
    }

    /**
     * Get list of supported banks
     */
    public function getBanks()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey
        ])->get($this->baseUrl . '/bank');

        return $response->json();
    }

    /**
     * Resolve account number
     */
    public function resolveAccountNumber(string $accountNumber, string $bankCode): \Illuminate\Http\JsonResponse
    {
        try {
            // Step 1: Resolve the account number
            $resolveResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey
            ])->get($this->baseUrl . '/bank/resolve', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode
            ]);

            $resolveData = $resolveResponse->json();

            if (!($resolveData['status'] ?? false)) {
                return Utility::outputData(false, $resolveData['message'] ?? 'Failed to resolve account.', null, 400);
            }

            // Step 2: Get all banks and match by bank_id
            $banksResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey
            ])->get($this->baseUrl . '/bank', [
                'country' => 'nigeria',
                'currency' => 'NGN'
            ]);

            $banks = $banksResponse->json()['data'] ?? [];

            $bankId = $resolveData['data']['bank_id'] ?? null;
            $bankName = collect($banks)->firstWhere('id', $bankId)['name'] ?? null;

            $resolvedData = [
                'account_number' => $resolveData['data']['account_number'],
                'account_name' => $resolveData['data']['account_name'],
                'bank_code' => $bankCode,
                'bank_name' => $bankName
            ];

            return Utility::outputData(true, 'Account resolved successfully', $resolvedData, 200);

        } catch (\Exception $e) {
            return Utility::outputData(false, 'An error occurred while resolving account.', [
                'error' => $e->getMessage()
            ], 500);
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
     * Record fee transaction without debiting wallet (wallet already debited)
     * But still track balance changes for audit purposes
     */
    private function recordFeeTransactionOnly($transaction, $feeCalculation, $provider, $balanceBeforeTotal, $balanceAfterTotal)
    {
        if (!$feeCalculation['success'] || ($feeCalculation['fee'] ?? 0) <= 0) {
            return null;
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

        return $feeTransaction;
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
