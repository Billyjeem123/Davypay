<?php

namespace Database\Seeders;

use App\Models\TransactionFee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactionFees = [

            [
                'provider' => 'system',
                'type' => 'epin_permit',
                'min' => 1000,
                'max' => 0,
                'fee' => 0,
            ],
            [
                'provider' => 'system',
                'type' => 'physical_card',
                'min' => 2500,
                'max' => 0,
                'fee' => 0,
            ],
            [
            'provider' => 'system',
            'type' => 'virtual_card',
            'min' => 2000,
            'max' => 0,
            'fee' => 0,
        ]
        ];

        foreach ($transactionFees as $transactionFee) {
            TransactionFee::updateOrCreate(
                [
                    'provider' => $transactionFee['provider'],
                    'type'     => $transactionFee['type'],
                ],
                $transactionFee
            );
        }

    }
}

