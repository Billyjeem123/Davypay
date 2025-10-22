<?php

namespace App\Http\Controllers\v1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Models\EpinPermit;
use App\Models\PaymentServiceCharges;
use App\Models\TransactionFee;
use App\Models\TransactionLog;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentChargesController extends Controller
{

    /**
     * Get transaction fees for different providers and amount ranges
     */
    public function getTransactionFees(Request $request): JsonResponse
    {
        try {
            $amount = $request->input('amount');
            $provider = $request->input('provider'); // optional filter
            $type = $request->input('type'); // optional filter (deposit, transfer)

            $query = TransactionFee::query();

            // Filter by provider if specified
            if ($provider) {
                $query->where('provider', $provider);
            }

            // Filter by transaction type if specified
            if ($type) {
                $query->where('type', $type);
            }

            // If amount is provided, get applicable fees for that amount
            if ($amount) {
                $query->where('min', '<=', $amount)
                    ->where('max', '>=', $amount);
            }

            $fees = $query->get()->map(function ($fee) use ($amount) {
                $calculatedFee = 0;
                if ($amount) {
                    $calculatedFee = ($amount * $fee->fee) / 100;
                }

                return [
                    'id' => $fee->id,
                    'provider' => $fee->provider,
                    'type' => $fee->type,
                    'min_amount' => $fee->min,
                    'max_amount' => $fee->max,
                    'fee_percentage' => $fee->fee,
                    'calculated_fee' => round($calculatedFee, 2),
                    'amount_range' => "₦" . number_format($fee->min) . " - ₦" . number_format($fee->max),
                    'fee_display' => $fee->fee . "%",
                    'created_at' => $fee->created_at,
                    'updated_at' => $fee->updated_at,
                ];
            });

            // Group by provider for better frontend consumption
            $groupedFees = $fees->groupBy('provider');

            return response()->json([
                'success' => true,
                'message' => 'Transaction fees retrieved successfully',
                'data' => [
                    'fees' => $fees,
                    'grouped_fees' => $groupedFees,
                    'request_params' => [
                        'amount' => $amount,
                        'provider' => $provider,
                        'type' => $type,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transaction fees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fee calculation for specific amount and provider
     */
    public function calculateFee(GlobalRequest $request): JsonResponse
    {
        try {
            $validated =  $request->validated();

            $amount = $validated['amount'];
            $provider = $validated['provider'];
            $type = $validated['type'];

            $feeRule = TransactionFee::where('provider', $provider)
                ->where('type', $type)
                ->where('min', '<=', $amount)
                ->where('max', '>=', $amount)
                ->first();

            if (!$feeRule) {
                return response()->json([
                    'success' => true,
                    'message' => "No fee structure found for {$provider} {$type}",
                    'data' => [
                        'fee_amount' => 0,
                        'fee_percentage' => 0,
                        'total_amount' => $amount,
                        'original_amount' => $amount,
                        'has_fee' => false
                    ]
                ]);
            }

            $feeAmount = ($amount * $feeRule->fee) / 100;
            $totalAmount = $amount + $feeAmount;

            return response()->json([
                'success' => true,
                'message' => 'Fee calculated successfully',
                'data' => [
                    'fee_amount' => round($feeAmount, 2),
                    'fee_percentage' => $feeRule->fee,
                    'total_amount' => round($totalAmount, 2),
                    'original_amount' => $amount,
                    'has_fee' => true,
                    'fee_rule' => [
                        'id' => $feeRule->id,
                        'min_amount' => $feeRule->min,
                        'max_amount' => $feeRule->max,
                        'amount_range' => "₦" . number_format($feeRule->min) . " - ₦" . number_format($feeRule->max)
                    ],
                    'breakdown' => [
                        'amount' => "₦" . number_format($amount),
                        'fee' => "₦" . number_format($feeAmount, 2) . " ({$feeRule->fee}%)",
                        'total' => "₦" . number_format($totalAmount, 2)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getServiceCharge(GlobalRequest $request): JsonResponse
    {
        $validated = $request->validated();
        try {
            $serviceType = $validated['service'];
            $charge = PaymentServiceCharges::where('services', $serviceType)->first();
            if (!$charge) {
                return response()->json([
                    'success' => false,
                    'message' => "No charge found for service: {$serviceType}"
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => "Charge retrieved successfully",
                'data' => [
                    'service' => $charge->services,
                    'rate' => (float) $charge->amount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching service charge: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Error fetching service charge",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * User pays for ePIN permit (wallet debit + log + permit creation)
     */
    public function payPermit(Request $request)
    {
        $permitFee = TransactionFee::where('type', 'epin_permit')->value('min');

        if (!$permitFee) {
            return response()->json([
                'success' => false,
                'message' => 'Permit fee not configured',
            ], 400);
        }

        $user = auth('sanctum')->user();

        $existing = EpinPermit::where('user_id', $user->id)
            ->where('status', true)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active permit',
            ], 400);
        }

        try {
            $result = DB::transaction(function () use ($user, $permitFee) {
                $wallet = Wallet::where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet || $wallet->amount < $permitFee) {
                    return [
                        'success' => false,
                        'message' => 'Insufficient wallet balance',
                    ];
                }

                $walletBalanceBefore = $wallet->amount;
                $wallet->decrement('amount', $permitFee);
                $wallet->refresh();

                $transaction = TransactionLog::create_transaction([
                    'service_type'  => 'Epin_permit',
                    'amount'        => $permitFee,
                    'amount_before' => $walletBalanceBefore,
                    'amount_after'  => $wallet->amount,
                    'status'        => 'success',
                    'wallet_id'     => $wallet->id,
                    'provider'      => 'wallet',
                    'type'          => 'debit',
                    'description'   => "Epin Buying permit",
                ]);

                $permit = EpinPermit::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'status'     => true,
                    ]
                );

                Log::info("Epin permit purchased", [
                    'user_id'     => $user->id,
                    'amount'      => $permitFee,
                    'transaction' => (bool)$transaction,
                    'permit_id'   => $permit->id
                ]);

                return [
                    'success' => true,
                    'message' => 'Permit activated successfully',
                    'data'    => [
                        'permit' => $permit,
                        'wallet' => [
                            'new_balance' => $wallet->amount,
                            'amount'      => $permitFee,
                        ]
                    ]
                ];
            });

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Throwable $e) {
            Log::error("Permit payment failed", [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Permit payment failed, please try again',
            ], 500);
        }
    }


}
