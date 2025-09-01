<?php

namespace App\Http\Controllers\v1\Admin;

use App\Helpers\FraudLogger;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminWalletRequest;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\ActivityTracker;
use App\Services\FraudDetectionService;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminWalletTransactionController extends Controller
{


    public  $fraudDetection;

    public  $tracker;

    public function __construct(FraudDetectionService $fraudDetection, ActivityTracker $activityTracker)
    {
        $this->fraudDetection = $fraudDetection;
        $this->tracker = $activityTracker;
    }



    public function fund(AdminWalletRequest $request)
    {
        $validated = $request->validated();

        try {
            # Step 1: Get user and wallet
            $user = $this->getUserWithWallet($validated['user_id']);
            if (!$user->wallet) {
                return back()->with('error', 'User wallet not found.');
            }

            $reference = Utility::txRef("in-app", "paystack");

            # Step 2: Perform fraud checks
            if($validated['transaction_type'] === "debit"){
                $this->performFraudChecks($user, $validated['amount']);
            }


            $this->checkTransactionLimits($user, $validated['amount']);


            # Step 3: Begin transaction
            DB::beginTransaction();

            # Step 4: Update wallet balance
            $amount = $validated['amount'];
            $amount_before = $user->wallet->amount;
            $transaction_type = $validated['transaction_type'];

            $type = $transaction_type === 'credit' ? 'admin_credit' : 'admin_debit';
            $defaultDescription = $transaction_type === 'credit'
                ? 'Manual credit by admin to user wallet'
                : 'Manual debit by admin from user wallet';

            $this->applyWalletTransaction($user->wallet, $amount, $transaction_type);
            $amount_after = $user->wallet->fresh()->amount;

            # Step 5: Create transaction log
            $log = $this->logTransaction($user, $validated, $amount, $amount_before, $amount_after, $transaction_type, $reference);

            # Step 6: Track activity
            $baseTrackingData = [
                'user_id' => $user->id,
                'transaction_id' => $log->id,
                'amount' => $user->wallet->amount,
                'provider' => 'admin_in_app_wallet_credit',
                'reference' => $reference ,
                'webhook_event' => "sucessful",
                'ip' => request()->ip(),
                'processed_at' => now()->toISOString(),
            ];


            $this->tracker->track($type, $defaultDescription, $baseTrackingData);

            DB::commit();

            return back()->with('success', 'Wallet successfully ' . $transaction_type . 'ed.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin wallet funding failed: ' . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Check transaction limits
     */
    public function checkTransactionLimits(User $sender, float $amount): void
    {
        [$limitOk, $limitMessage] = TransactionLog::checkLimits($sender, $amount);
        if (!$limitOk) {
            throw new TransferException($limitMessage, 403);
        }
    }



    /**
     * Perform fraud detection checks
     */
    private function performFraudChecks($user, $amount)
    {
        $fraudCheck = $this->fraudDetection->checkTransaction(
            $user,
            $amount,
            'debit',
            [
                'transaction_type' => 'wallet_transfer_out',
                'recipient_identifier' => "Top up from admin to user " . $user->first_name,
                'reference' => 0000
            ]
        );

        if (!$fraudCheck['passed']) {
            throw new \Exception($fraudCheck['message']);
        }
    }



    /**
     * Log fraud alert
     */
    private function logFraudAlert(array $transferData, array $fraudCheck): void
    {
        FraudLogger::logFraudAlert('Transaction blocked by fraud detection, Transaction doe by admin', [
            'user_id' => $transferData['sender']->id,
            'amount' => $transferData['amount'],
            'fraud_check_id' => $fraudCheck['fraud_check_id'],
            'reason' => $fraudCheck['message']
        ]);
    }


    # Get user and their wallet
    private function getUserWithWallet($userId)
    {
        return User::with('wallet')->findOrFail($userId);
    }

# Apply the wallet operation (credit or debit)
    private function applyWalletTransaction($wallet, $amount, $type)
    {
        if ($type === 'credit') {
            $wallet->amount += $amount;
        } elseif ($type === 'debit') {
            if ($wallet->amount < $amount) {
                throw new \Exception('Insufficient wallet balance.');
            }
            $wallet->amount -= $amount;
        }

        $wallet->save();
    }

# Log the transaction details
    private function logTransaction($user, $validated, $amount, $before, $after, $type, $reference)
    {
        $description = $validated['description'] ?? (
        $type === 'credit'
            ? 'Manual credit by admin to user wallet'
            : 'Manual debit by admin from user wallet'
        );

        return TransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'amount' => $amount,
            'amount_before' => $before,
            'amount_after' => $after,
            'currency' => 'NGN',
            'description' => $description,
            'status' => 'successful',
            'type' => $type,
            'category' => $validated['funding_type'],
            'service_type' => $validated['funding_type'],
            'purpose' => 'admin_wallet_' . $type,
            'payable_type' => 'App\\Models\\Wallet',
            'payable_id' => $user->wallet->id,
            'provider' => 'admin_manual',
            'transaction_reference' => $reference,
            'channel' => 'manual',
            'paid_at' => now(),
            'provider_response' => json_encode([
                'source' => 'admin_panel',
                'admin_id' => auth()->id(),
                'funding_type' => $validated['funding_type'],
            ]),
        ]);
    }


}
