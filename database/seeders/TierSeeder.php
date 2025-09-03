<?php

namespace Database\Seeders;

use App\Models\Tier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Tier::updateOrCreate(['name' => 'tier_1'], [
            'daily_limit' => 1000,
            'wallet_balance' => 5000,
        ]);

        Tier::updateOrCreate(['name' => 'tier_2'], [
            'daily_limit' => 100000,
            'wallet_balance' => 5000000,
        ]);

        Tier::updateOrCreate(['name' => 'tier_3'], [
            'daily_limit' => 1000000,
            'wallet_balance' => 20000000,
        ]);
    }
}
