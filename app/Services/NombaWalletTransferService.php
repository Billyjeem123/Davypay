<?php

namespace App\Services;

use App\Helpers\PaymentLogger;
use App\Helpers\Utility;
use App\Models\NombaTransaction;
use App\Models\TransactionLog;
use App\Models\VirtualAccount;
use Carbon\Carbon;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NombaWalletTransferService
{

    /**
        * Initialize wallet-to-wallet transfer
        */
    public function initializeTransfer($data): array
    {
        DB::beginTransaction();

        try {
            $reference = Utility::txRef('wallet-transfer', 'nomba', true);
            $user = Auth::user();

            # Validate sender has sufficient balance
            if (!$this->validateSufficientBalance($user, $data['amount'])) {
                return [
                    'success' => false,
                    'error' => 'Insufficient wallet balance',
                    'status' => 400
                ];
            }


            #  Verify transaction PIN
            if (!$this->verifyTransactionPin($user, $data['pin'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid transaction PIN',
                    'status' => 400
                ];
            }

            # Validate receiver account
            $receiverValidation = $this->validateReceiverAccount($data['user_id']);
            if (!$receiverValidation['valid']) {
                return [
                    'success' => false,
                    'error' => $receiverValidation['error'],
                    'status' => 400
                ];
            }
            $requestData = $this->buildNombaTransferPayload($data, $reference, $receiverValidation);

            $response = $this->connectToNomba('/transfers/wallet', 'POST', $requestData);

            if ($response && $response->successful()) {
                $responseData = $response->json();

                # Create transaction records
                $transaction = $this->createTransferTransactionLog($user, $data, $reference, $responseData, $receiverValidation);
                $nombaTransaction = $this->createNombaTransferRecord($user, $data, $reference, $responseData, $transaction->id);

                # Update wallet balances if transfer is successful
                if (isset($responseData['data']['status']) && $responseData['data']['status'] === 'successful') {
                    $this->updateWalletBalances($user, $data['amount'], $transaction);
                }

                # Log activities
                $this->logTransferActivity($user, $data, $reference, 'initialized');
                PaymentLogger::log('Transfer initialized', [
                    'reference' => $reference,
                    'receiver_account' => $receiverValidation['user'],
                    'amount' => $data['amount']
                ]);

                PaymentLogger::log('Nomba transfer initialized successfully', [
                    'reference' => $reference,
                    'status' => $responseData['data']['status'] ?? 'pending',
                    'transfer_id' => $responseData['data']['id'] ?? null
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'data' => [
                        'reference' => $reference,
                        'transfer_id' => $responseData['data']['id'] ?? null,
                        'status' => $responseData['data']['status'] ?? 'pending',
                        'amount' => $responseData['data']['amount'] ?? $data['amount'],
                        'fee' => $responseData['data']['fee'] ?? 0,
                        'time_created' => $responseData['data']['timeCreated'] ?? now()->toISOString(),
                        'transaction_type' => $responseData['data']['type'] ?? 'transfer'
                    ]
                ];

            } else {
                DB::rollBack();

                $errorData = $response ? $response->json() : ['message' => 'No response received'];
                $statusCode = $response ? $response->status() : 500;

                #  Log failed transfer attempt
                $this->logFailedTransfer($user, $data, $reference, $errorData, $statusCode);

                PaymentLogger::error('Failed to initialize nomba wallet transfer', [
                    'reference' => $reference,
                    'error' => $errorData,
                    'status_code' => $statusCode
                ]);

                return [
                    'success' => false,
                    'error' => $this->getErrorMessage($errorData, $statusCode),
                    'status' => $statusCode
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();


            PaymentLogger::error('Exception while initializing nomba transfer', [
                'reference' => $reference ?? 'N/A',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return [
                'success' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Transfer initialization failed. Please try again.',
                'status' => 500
            ];
        }
    }


    /**
     * Make an authenticated request to the Nomba API.
     *
     * @param string $endpoint
     * @param string $method
     * @param array $payload
     * @return \Illuminate\Http\Client\Response
     */
    private function connectToNomba(string $endpoint, string $method = 'POST', array $payload = [])
    {
        try {
            $nomba = new NombaService();

        return $nomba->makeAuthenticatedRequest($method, $endpoint, $payload);
        } catch (\Throwable $e) {
            \Log::error('Failed to connect to Nomba', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'method' => $method,
                'payload' => $payload,
            ]);

            return null; #  or return a custom response like ['success' => false, 'message' => ...]
        }
    }


    /**
     * Build transfer payload for Nomba API
     */
    private function buildNombaTransferPayload(array $data, string $reference, $receiverValidation): array
    {
        $user = Auth::user();

        return [
            'amount' => $data['amount'],
            'receiverAccountId' => $receiverValidation['receiver'],
            'merchantTxRef' => $reference,
            'senderName' => $user->first_name . ' ' . $user->last_name,
            'narration' => $data['narration'] ?? ""
        ];
    }

    /**
     * Validate sender has sufficient balance
     */
    private function validateSufficientBalance($user, float $amount): bool
    {
        $wallet = $user->wallet;
        if (!$wallet) {
            return false;
        }
        return $wallet->amount;
    }

    /**
     * Validate receiver account exists and is valid
     */
    private function validateReceiverAccount(string $user_id): array
    {
        try {
            # Check if receiver account exists in our system

            $receiverAccount = VirtualAccount::where('user_id', $user_id)
                ->where('provider', 'nomba')
                ->first();

            if (!$receiverAccount) {
                return [
                    'valid' => false,
                    'error' => 'Receiver account not found or inactive'
                ];
            }

            # Additional validation: Check if not sending to self
            if ($receiverAccount->user_id === Auth::id()) {
                return [
                    'valid' => false,
                    'error' => 'Cannot transfer to your own account'
                ];
            }

            return [
                'valid' => true,
                'receiver' => $receiverAccount['account_holder_id'],
                'user' => $receiverAccount->user->first_name . ' ' . $receiverAccount->user->last_name,
            ];

        } catch (\Exception $e) {
            PaymentLogger::error('Error validating receiver account', [
                'receiver_account_id' => $user_id,
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'error' => 'Unable to validate receiver account'
            ];
        }
    }

    /**
     * Create transaction log for transfer
     */
    private function createTransferTransactionLog($user, array $data, string $reference, array $responseData, $receiverValidation): TransactionLog
    {
        return TransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'type' => 'debit',
            'category' => 'wallet_transfer_out',
            'amount' => $data['amount'],
            'transaction_reference' => $reference,
            'service_type' => 'wallet_transfer',
            'amount_before' => $user->wallet->amount,
            'amount_after' => $user->wallet->amount, #  Will be updated later
            'status' => $responseData['data']['status'] ?? 'pending',
            'provider' => 'nomba',
            'channel' => 'nomba_transfer',
            'currency' => 'NGN',
            'description' => 'Wallet transfer to ' . ($receiverValidation['user']),
            'payload' => [
                'initialized_at' => now(),
                'ip' => request()->ip(),
                'receiver_account_id' => ($receiverValidation['user']),
                'narration' => $data['narration'] ?? null,
                'nomba_response' => $responseData
            ],
        ]);
    }

    /**
     * Create Nomba transaction record
     */
    private function createNombaTransferRecord($user, array $data, string $reference, array $responseData, int $transactionId): NombaTransaction
    {
        return NombaTransaction::create([
            'transaction_id' => $transactionId,
            'reference' => $reference,
            'amount' => $data['amount'],
            'status' => $responseData['data']['status'] ?? 'pending',
            'user_id' => $user->id,
            'nomba_transfer_id' => $responseData['data']['id'] ?? null,
            'wallet_id' => $user->wallet->id,
            'fee' => $responseData['data']['fee'] ?? 0,
        ]);
    }

    /**
     * Update wallet balances after successful transfer
     */
    private function updateWalletBalances($user, float $amount, TransactionLog $transaction): void
    {
        $wallet = $user->wallet;
        $totalDeduction = $amount + ($transaction->nombaTransaction->fee ?? 0);

        $newBalance = $wallet->amount - $totalDeduction;
        $wallet->update(['amount' => $newBalance]);

        $transaction->update(['amount_after' => $newBalance]);
    }

    /**
     * Log transfer activity
     */
    private function logTransferActivity($user, array $data, string $reference, string $action): void
    {
        $service = new ActivityTracker();
        $service->track(
            "wallet_transfer_{$action}",
            ucfirst($action) . " wallet transfer of ₦" . number_format($data['amount']) . " via Nomba for {$user->first_name}",
            [
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'reference' => $reference,
                'ip' => request()->ip(),
                'provider' => 'nomba',
                'status' => 'pending',
                'effective' => true,
            ]
        );
    }

    /**
     * Log failed transfer attempt
     */
    private function logFailedTransfer($user, array $data, string $reference, array $errorData, int $statusCode): void
    {
        TransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'type' => 'debit',
            'amount' => $data['amount'],
            'category' => 'wallet_transfer_out',
            'transaction_reference' => $reference,
            'service_type' => 'wallet_transfer',
            'amount_before' => $user->wallet->amount,
            'amount_after' => $user->wallet->amount,
            'status' => 'failed',
            'provider' => 'nomba',
            'channel' => 'nomba_transfer',
            'currency' => 'NGN',
            'description' => 'Failed wallet transfer to ' . ($data['receiverAccountId'] ?? 'account'),
            'payload' => [
                'failed_at' => now(),
                'ip' => request()->ip(),
                'error' => $errorData,
                'status_code' => $statusCode
            ],
            "provider_response" => json_encode($errorData)
        ]);

        $this->logTransferActivity($user, $data, $reference, 'failed');
    }

    /**
     * Get user-friendly error message
     */
    private function getErrorMessage(array $errorData, int $statusCode): string
    {
        $message = $errorData['description'] ?? $errorData['message'] ?? 'Unknown error occurred';

        #  Map common errors to user-friendly messages
        $errorMappings = [
            'insufficient funds' => 'Insufficient wallet balance for this transfer',
            'invalid account' => 'The receiver account is invalid or does not exist',
            'account not found' => 'Receiver account not found',
            'transfer limit exceeded' => 'Transfer amount exceeds your daily limit',
        ];

        $lowerMessage = strtolower($message);
        foreach ($errorMappings as $key => $friendlyMessage) {
            if (strpos($lowerMessage, $key) !== false) {
                return $friendlyMessage;
            }
        }

        return $statusCode >= 500 ? 'Service temporarily unavailable. Please try again.' : $message;
    }

    /**
     * Get Nomba account holder ID for current user
     */
    private function getNombaAccountHolderId(): ?string
    {
        $user = Auth::user();

        $nombaAccount = VirtualAccount::where('user_id', $user->id)
            ->where('provider', 'nomba')
            ->first();

        return $nombaAccount?->account_holder_id;
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


}
