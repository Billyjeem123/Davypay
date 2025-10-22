<?php

namespace Database\Seeders;

use App\Models\EpinRate;
use Illuminate\Database\Seeder;

class EpinRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rates = [
            [
                'card_network' => 'MTN',
                'value' => 100,
                'min_quantity' => 1,
                'max_quantity' => null,
                'rate' => 98,
            ],
            [
                'card_network' => 'GLO',
                'value' => 100,
                'min_quantity' => 1,
                'max_quantity' => null,
                'rate' => 97,
            ],
            [
                'card_network' => 'AIRTEL',
                'value' => 100,
                'min_quantity' => 1,
                'max_quantity' => null,
                'rate' => 97,
            ],
            [
                'card_network' => '9MOBILE',
                'value' => 100,
                'min_quantity' => 1,
                'max_quantity' => null,
                'rate' => 95,
            ],
        ];

        foreach ($rates as $rate) {
            EpinRate::firstOrCreate(
                [
                    'card_network' => $rate['card_network'],
                    'value' => $rate['value']
                ],
                $rate
            );
        }
    }
}
