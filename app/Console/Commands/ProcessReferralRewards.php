<?php

namespace App\Console\Commands;

use App\Helpers\Utility;
use App\Models\Referral;
use App\Models\Settings;
use App\Models\TransactionLog;
use App\Notifications\ReferralRewardNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessReferralRewards extends Command
{
    protected $signature = 'referrals:process-rewards';
    protected $description = 'Process referral rewards for users who made their first transaction';

    public function handle()
    {

        if (Settings::get('referral_status') !== 'active') {
            $this->info('Referral reward system is currently inactive. Exiting...');
            return;
        }
        $pendingReferrals = Referral::where('status', 'pending')
            ->whereNotNull('referred_id')
            ->get();


        $rewardedCount = 0;

        foreach ($pendingReferrals as $referral) {
            $hasFirstTransaction = TransactionLog::where('user_id', $referral->referred_id)
                ->where('category', 'external_bank_deposit')
                ->where('type', 'credit')
                ->where('status', 'successful')
                ->exists();

            if (! $hasFirstTransaction) {
                continue;
            }

            try {
                DB::transaction(function () use ($referral, &$rewardedCount) {
                    $referrer = $referral->referrer;

                    if (! $referrer || ! $referrer->wallet) {
                        throw new \Exception('Referrer or wallet not found.');
                    }

                    $rewardAmount = Settings::get('referral_fee') ?? 50;
                    $wallet = $referrer->wallet;
                    $amountBefore = $wallet->amount;
                    $wallet->increment('amount', $rewardAmount);

                    $amountAfter = $wallet->fresh()->amount;

                    TransactionLog::create([
                        'user_id' => $referrer->id,
                        'wallet_id' => $wallet->id,
                        'type' => 'credit',
                        'category' => 'referral_reward',
                        'amount' => $rewardAmount,
                        'transaction_reference' => Utility::txRef('reward', 'system'),
                        'service_type' => 'referral_bonus',
                        'amount_before' => $amountBefore,
                        'amount_after' => $amountAfter,
                        'status' => 'successful',
                        'provider' => 'system',
                        'channel' => 'internal',
                        'currency' => 'NGN',
                        'description' => 'Referral bonus reward',
                        'payload' => json_encode([
                            'source' => 'referral_program',
                            'referrer_email' => $referrer->email,
                            'referred_user_id' => $referral->referred_id,
                        ]),
                    ]);

                    $referral->update([
                        'status' => 'completed',
                        'reward_amount' => $rewardAmount,
                        'rewarded_at' => now(),
                    ]);

                    $referrer->notify(new ReferralRewardNotification($rewardAmount, $referral));

                    $this->info("✓ Rewarded ₦{$rewardAmount} to referrer ID {$referrer->id}");
                    $rewardedCount++;
                });

            } catch (\Throwable $e) {
                Log::error('Referral reward processing failed', [
                    'referral_id' => $referral->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("✗ Failed to reward referrer ID {$referral->referrer_id}: {$e->getMessage()}");
            }
        }

        $this->info("Referral processing complete. Rewarded {$rewardedCount} referrer(s).");
    }

}

