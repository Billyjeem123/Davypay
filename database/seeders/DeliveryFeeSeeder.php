<?php

namespace Database\Seeders;

use App\Models\DeliveryFee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeliveryFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fees = [
            ['state' => 'Lagos', 'amount' => 2000],
            ['state' => 'Rivers', 'amount' => 3000],
            ['state' => 'Abuja', 'amount' => 3000],
            ['state' => 'Ogun', 'amount' => 2000],
            ['state' => 'Oyo', 'amount' => 2000],
        ];

        foreach ($fees as $fee) {
            DeliveryFee::updateOrCreate(['state' => $fee['state']], ['amount' => $fee['amount']]);
        }
    }
}
