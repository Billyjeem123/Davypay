<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MigrateOldUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-old-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate users from old DB to new schema';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $oldUsers = DB::connection('mysql_old')->table('users')->get();

        foreach ($oldUsers as $old) {
            // Map KYC type logic
            if (is_null($old->level_two_kyc_status)) {
                $kycType = 'none';
            } elseif ($old->level_two_kyc_status == 1) {
                $kycType = 'bvn';
            } elseif ($old->level_two_kyc_status == 2) {
                $kycType = 'nin';
            } else {
                $kycType = 'none';
            }

            $username = $old->username;
            if (empty($username) || DB::connection('mysql')->table('users')->where('username', $username)->exists()) {
                do {
                    $username = 'user_' . Str::random(8);
                } while (
                    DB::connection('mysql')->table('users')->where('username', $username)->exists()
                );
            }

            // Insert into new users table (preserve ID)
            DB::connection('mysql')->table('users')->insert([
                'id' => $old->id,
                'first_name' => $old->first_name ?? '',
                'last_name' => $old->surname ?? '',
                'maiden' => $old->other_name ?? null,
                'email' => $old->email,
                'image' => $old->profile ?? null,
                'email_verified_at' => $old->email_verified_at,
                'password' => $old->password ?? Hash::make('default123'),
                'phone' => $old->phone_number ?? '',
                'pin' => $old->transaction_pin ?? '',
                'referral_code' => $old->referral ?? null,
                'username' => $username,
                'role' => 'user',
                'kyc_status' => $old->level_two_kyc_status ? 'verified' : 'pending',
                'kyc_type' => $kycType,
                'account_level' => match ((int)$old->account_level) {
                    1 => 'tier_1',
                    2 => 'tier_2',
                    3 => 'tier_3',
                    default => 'tier_1',
                },
                'is_account_restricted' => $old->is_account_restricted ?? 0,
                'is_ban' => $old->is_ban ?? 0,
                'view' => $old->view ?? 1,
                'remember_token' => $old->remember_token,
                'created_at' => $old->created_at,
                'updated_at' => $old->updated_at,
            ]);

            $walletId = DB::connection('mysql')->table('wallets')->insertGetId([
                'user_id' => $old->id,
                'amount' => $old->balance ?? 0.00,
                'status' => 'active',
                'created_at' => $old->created_at,
                'updated_at' => $old->updated_at,
            ]);

            if (!empty($old->account_number)) {
                DB::connection('mysql')->table('virtual_accounts')->insert([
                    'user_id' => $old->id,
                    'wallet_id' => $walletId,
                    'account_number' => $old->account_number,
                    'account_name' => $old->account_name,
                    'bank_name' => $old->bank_name,
                    'provider' => ($old->is_nomba ?? 0) ? 'nomba' : 'paystack',
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]);
            }

            if (!empty($old->bvn)) {
                DB::connection('mysql')->table('kyc')->insert([
                    'user_id' => $old->id,
                    'bvn' => $old->bvn,
                    'address' => $old->address,
                    'tier' => $kycType === 'bvn' ? 'tier_2' : 'tier_1',
                    'status' => $old->level_two_kyc_status ? 'verified' : 'pending',
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]);
            }
        }

        $this->info('✅ User migration completed successfully, with IDs preserved!');
    }
}
