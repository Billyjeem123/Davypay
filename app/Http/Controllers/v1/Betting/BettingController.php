<?php

namespace App\Http\Controllers\v1\Betting;

use App\Helpers\RedbillerLogger;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


class BettingController extends Controller
{
    public function fundBettingAccount(GlobalRequest $request): JsonResponse
    {
        $validated = $request->validated();
        DB::beginTransaction();

        $transactionLog = null;
        $walletDebited = false;
        $reference = null;

        try {
            $user = auth()->user();
            $amount = (float)$validated['amount'];

            # Get user's wallet
            $wallet = $user->wallet;
            if (!$wallet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User wallet not found'
                ], 400);
            }

            # Validate sender has sufficient balance
            if (!$this->validateSufficientBalance($user, $amount)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient wallet balance',
                    'error' => 'Insufficient wallet balance'
                ], 400);
            }

            # Check transaction limits
            [$limitOk, $limitMessage] = TransactionLog::checkLimits($user, $amount);
            if (!$limitOk) {
                return response()->json([
                    'status' => 'error',
                    'message' => $limitMessage,
                    'error' => $limitMessage
                ], 403);
            }

            # Generate unique transaction reference
            $reference = 'BET' . strtoupper(Str::random(12));

            # Get balance before transaction
            $balanceBefore = $wallet->amount;

            # Create transaction log in pending state (don't debit yet)
            $transactionLog = TransactionLog::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'category' => 'betting_fund',
                'amount' => $amount,
                'transaction_reference' => $reference,
                'service_type' => 'betting-payment',
                'amount_before' => $balanceBefore,
                'amount_after' => $balanceBefore, # Will update after successful debit
                'status' => 'pending',
                'provider' => 'redbiller',
                'channel' => 'api',
                'currency' => 'NGN',
                'idempotency_key' => request()->attributes->get('idempotency_key'),
                'image' => request()->image,
                'description' => "Betting account funding for {$validated['product']} - Customer ID: {$validated['customer_id']}",
                'provider_response' => json_encode([]),
                'payload' => json_encode([
                    'product' => $validated['product'],
                    'customer_id' => $validated['customer_id'],
                    'phone_no' => $validated['phone_no'],
                    'amount' => $amount,
                    'reference' => $reference
                ]),
            ]);

            # FIRST: Debit the wallet before making external API call
            $debitResult = $this->debitWallet($wallet, $amount, $reference);
            if (!$debitResult['success']) {
                $transactionLog->update([
                    'status' => 'failed',
                    'provider_response' => json_encode([
                        'error' => 'Failed to debit wallet: ' . $debitResult['message']
                    ])
                ]);

                DB::rollback();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to process payment',
                    'error' => $debitResult['message']
                ], 400);
            }

            $walletDebited = true;
            $newBalance = $wallet->fresh()->amount;

            # Update transaction log with new balance
            $transactionLog->update([
                'amount_after' => $newBalance,
                'status' => 'processing'
            ]);

            # Now make the API call to Redbiller
            $redbillerResponse = Http::timeout(30)
                ->withHeaders([
                    'Private-Key' => config('services.redbiller.private_key'),
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.live.redbiller.com/1.5/bills/betting/account/payment/create', [
                    'product' => $validated['product'],
                    'customer_id' => $validated['customer_id'],
                    'amount' => $amount,
                    'phone_no' => $validated['phone_no'],
                    'callback_url' => route('betting.callback', ['reference' => $reference]),
                    'reference' => $reference
                ]);

            # Handle HTTP request failure
            if (!$redbillerResponse->successful()) {
                # Refund the wallet since API call failed
                $this->creditWallet($wallet, $amount, $reference . '_REFUND');

                $transactionLog->update([
                    'status' => 'failed',
                    'provider_response' => json_encode([
                        'error' => 'HTTP request failed',
                        'status_code' => $redbillerResponse->status(),
                        'response' => $redbillerResponse->body(),
                        'refunded' => true
                    ]),
                    'amount_after' => $wallet->fresh()->amount # Update with refunded balance
                ]);

                DB::commit(); # Commit the refund

                RedbillerLogger::log('Betting payment HTTP request failed - wallet refunded', [
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'status_code' => $redbillerResponse->status(),
                    'error' => $redbillerResponse->body(),
                    'amount_refunded' => $amount
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment failed - amount refunded to wallet',
                    'error' => 'Provider service unavailable'
                ], 500);
            }

            $responseData = $redbillerResponse->json();

            # Check API business logic success based on actual Redbiller response format
            $isApiSuccess = isset($responseData['response']) &&
                $responseData['response'] == 200 &&
                isset($responseData['status']) &&
                ($responseData['status'] === 'true' || $responseData['status'] === true);

            if ($isApiSuccess) {
                # Success - create 3D authentication pointer file
                $this->create3DAuthPointer($reference, $user->id, $amount, $transactionLog->id);

                # Extract charge/fee from response if available
                $charge = $responseData['details']['charge'] ?? 0;

                # Update transaction log with success
                $transactionLog->update([
                    'status' => 'processing', # Will be updated to 'completed' via callback
                    'provider_response' => json_encode($responseData),
                    'fee' => $charge
                ]);

                DB::commit();

                RedbillerLogger::log('Betting payment initiated successfully', [
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'amount' => $amount,
                    'charge' => $charge,
                    'wallet_balance_after' => $newBalance,
                    'provider_response' => $responseData,
                    'customer_profile' => $responseData['details']['profile'] ?? null
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Betting account funding initiated successfully',
                    'data' => [
                        'reference' => $reference,
                        'amount' => $amount,
                        'charge' => $charge,
                        'transaction_id' => $transactionLog->id,
                        'wallet_balance' => $newBalance,
                        'customer_profile' => $responseData['details']['profile'] ?? null,
                        'transaction_status' => $responseData['meta']['status'] ?? 'Pending',
                        'redbiller_response' => $responseData
                    ]
                ]);

            } else {
                # API returned 200 but business logic failed - refund wallet
                $this->creditWallet($wallet, $amount, $reference . '_REFUND');

                $errorMessage = $responseData['message'] ?? 'Unknown error from provider';

                $transactionLog->update([
                    'status' => 'failed',
                    'provider_response' => json_encode(array_merge($responseData, ['refunded' => true])),
                    'amount_after' => $wallet->fresh()->amount # Update with refunded balance
                ]);

                DB::commit(); # Commit the refund

                RedbillerLogger::log('Betting payment business logic failed - wallet refunded', [
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'api_message' => $errorMessage,
                    'provider_response' => $responseData,
                    'amount_refunded' => $amount
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment failed - amount refunded to wallet',
                    'error' => $errorMessage
                ], 400);
            }

        } catch (ValidationException $e) {
            # Handle validation errors - refund if wallet was debited
            if ($walletDebited && $wallet) {
                $this->creditWallet($wallet, $amount, $reference . '_REFUND');

                if ($transactionLog) {
                    $transactionLog->update([
                        'status' => 'failed',
                        'provider_response' => json_encode([
                            'error' => 'Validation failed',
                            'details' => $e->errors(),
                            'refunded' => true
                        ]),
                        'amount_after' => $wallet->fresh()->amount
                    ]);
                }
                DB::commit(); # Commit the refund
            } else {
                DB::rollback();
            }

            return response()->json([
                'status' => 'error',
                'message' => $walletDebited ? 'Validation failed - amount refunded to wallet' : 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (TransferException $e) {
            # Handle transfer exceptions - refund if wallet was debited
            if ($walletDebited && $wallet) {
                $this->creditWallet($wallet, $amount, $reference . '_REFUND');

                if ($transactionLog) {
                    $transactionLog->update([
                        'status' => 'failed',
                        'provider_response' => json_encode([
                            'error' => $e->getMessage(),
                            'refunded' => true
                        ]),
                        'amount_after' => $wallet->fresh()->amount
                    ]);
                }
                DB::commit(); # Commit the refund
            } else {
                if ($transactionLog) {
                    $transactionLog->update(['status' => 'failed']);
                }
                DB::rollback();
            }

            return response()->json([
                'status' => 'error',
                'message' => $walletDebited ? 'Transfer failed - amount refunded to wallet' : $e->getMessage()
            ], $e->getCode());

        } catch (Exception $e) {
            # Handle general exceptions - refund if wallet was debited
            if ($walletDebited && $wallet) {
                $this->creditWallet($wallet, $amount, $reference . '_REFUND');

                if ($transactionLog) {
                    $transactionLog->update([
                        'status' => 'failed',
                        'provider_response' => json_encode([
                            'error' => $e->getMessage(),
                            'refunded' => true
                        ]),
                        'amount_after' => $wallet->fresh()->amount
                    ]);
                }
                DB::commit(); # Commit the refund
            } else {
                if ($transactionLog) {
                    $transactionLog->update([
                        'status' => 'failed',
                        'provider_response' => json_encode(['error' => $e->getMessage()])
                    ]);
                }
                DB::rollback();
            }

            RedbillerLogger::log('Betting payment exception', [
                'user_id' => $user->id ?? null,
                'reference' => $reference ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'wallet_debited' => $walletDebited,
                'refunded' => $walletDebited
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $walletDebited ?
                    'An error occurred - amount refunded to wallet' :
                    'An error occurred while processing your request'
            ], 500);
        }
    }

    /**
     * Helper method to create 3D authentication pointer file
     */
    private function create3DAuthPointer($reference, $userId, $amount, $transactionId): void
    {
        try {
            $base = public_path('redbiller');
            $hook = '3D-Authentication-Hook';
            $hookPath = $base . '/' . $hook;

            if (!File::exists($hookPath)) {
                File::makeDirectory($hookPath, 0755, true);
            }

            $pointerPath = $hookPath . '/' . $reference;
            File::put($pointerPath, json_encode([
                'reference' => $reference,
                'user_id' => $userId,
                'amount' => $amount,
                'transaction_id' => $transactionId,
                'created_at' => now()->toISOString()
            ]));
        } catch (Exception $e) {
            # Log error but don't fail the transaction
            RedbillerLogger::log('Failed to create 3D auth pointer', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Debit wallet with proper validation
     */
    private function debitWallet($wallet, $amount, $reference): array
    {
        try {
            if ($wallet->amount < $amount) {
                return [
                    'success' => false,
                    'message' => 'Insufficient wallet balance'
                ];
            }

            $wallet->decrement('amount', $amount);

            return [
                'success' => true,
                'new_balance' => $wallet->fresh()->amount
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to debit wallet: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Credit wallet (for refunds)
     */
    private function creditWallet($wallet, $amount, $reference)
    {
        try {
            $wallet->increment('amount', $amount);

            # Log the refund
            RedbillerLogger::log('Wallet refund processed', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'reference' => $reference,
                'new_balance' => $wallet->fresh()->amount
            ]);

            return true;
        } catch (Exception $e) {
            RedbillerLogger::log('Failed to refund wallet', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }


    public function bettingCallback(Request $request): JsonResponse
    {
        try {
            $reference = $request->input('reference');
            $status = $request->input('status');

            if (!$reference) {
                return response()->json(['status' => 'error', 'message' => 'Reference required'], 400);
            }

            # Find transaction
            $transaction = TransactionLog::where('transaction_reference', $reference)->first();

            if (!$transaction) {
                RedbillerLogger::log('Callback received for unknown transaction', ['reference' => $reference]);
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            # Update transaction status based on callback
            $finalStatus = ($status === 'successful' || $status === 'success') ? 'successful' : 'failed';

            $transaction->update([
                'status' => $finalStatus,
                'provider_response' => json_encode(array_merge(
                    json_decode($transaction->provider_response, true) ?? [],
                    $request->all()
                ))
            ]);

            # If transaction failed, credit back the wallet
            if ($finalStatus === 'failed') {
                $wallet = Wallet::find($transaction->wallet_id);
                if ($wallet) {
                    $wallet->increment('amount', $transaction->amount);

                    RedbillerLogger::log('Wallet credited back due to failed betting transaction', [
                        'wallet_id' => $wallet->id,
                        'amount' => $transaction->amount,
                        'reference' => $reference
                    ]);
                }
            }

            # Clean up pointer file
            $base = public_path('redbiller');
            $hook = '3D-Authentication-Hook';
            $pointerPath = $base . '/' . $hook . '/' . $reference;

            if (File::exists($pointerPath)) {
                File::delete($pointerPath);
            }

            RedbillerLogger::log('Betting callback processed', [
                'reference' => $reference,
                'status' => $finalStatus,
                'transaction_id' => $transaction->id
            ]);

            return response()->json(['status' => 'success']);

        } catch (Exception $e) {
            RedbillerLogger::log('Betting callback error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    public function getBetSites(Request $request): JsonResponse
    {
        try {
            $redbillerResponse = Http::timeout(30)->withHeaders([
                'Private-Key' => config('services.redbiller.private_key'),
                'Content-Type' => 'application/json',
            ])->get('https://api.live.redbiller.com/1.5/bills/betting/providers/list');

            if ($redbillerResponse->successful()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $redbillerResponse->json()
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch betting providers'
            ], 500);

        } catch (Exception $e) {
            RedbillerLogger::log('Get bet sites error', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Service temporarily unavailable'
            ], 500);
        }
    }

    public function verifyBettingAccount(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product' => 'required|string',
                'customer_id' => 'required|string',
            ]);

            $redbillerResponse = Http::timeout(30)->withHeaders([
                'Private-Key' => config('services.redbiller.private_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.live.redbiller.com/1.5/bills/betting/account/verify', [
                'product' => $request->product,
                'customer_id' => $request->customer_id,
            ]);

            if ($redbillerResponse->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Account verification completed',
                    'data' => $redbillerResponse->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Account verification failed',
                'error' => $redbillerResponse->body()
            ], 400);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            RedbillerLogger::log('Betting account verification error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Verification service temporarily unavailable'
            ], 500);
        }
    }

    public function getBettingTransactionHistory(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $perPage = $request->input('per_page', 15);

            $transactions = TransactionLog::where('user_id', $user->id)
                ->where('category', 'betting_fund')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $transactions
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch transaction history'
            ], 500);
        }
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
}
