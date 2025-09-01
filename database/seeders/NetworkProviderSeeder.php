<?php

namespace Database\Seeders;

use App\Models\NetworkProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NetworkProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $providers = [
            [
                'network_name' => 'MTN',
                'admin_rate' => 5.00,
                'transfer_number' => '+2348123456789',
                'is_active' => true,
                'description' => 'MTN Nigeria - Leading telecommunications provider'
            ],
            [
                'network_name' => 'GLO',
                'admin_rate' => 4.50,
                'transfer_number' => '+2347098765432',
                'is_active' => true,
                'description' => 'Globacom Limited - Nigerian telecommunications company'
            ],
            [
                'network_name' => 'AIRTEL',
                'admin_rate' => 5.50,
                'transfer_number' => '+2348087654321',
                'is_active' => true,
                'description' => 'Airtel Nigeria - Telecommunications services provider'
            ],
            [
                'network_name' => '9MOBILE',
                'admin_rate' => 6.00,
                'transfer_number' => '+2349012345678',
                'is_active' => false,
                'description' => '9mobile Nigeria - Mobile telecommunications operator'
            ]
        ];

        foreach ($providers as $provider) {
            NetworkProvider::updateOrCreate(
                ['network_name' => $provider['network_name']],
                $provider
            );
        }
    }
}
