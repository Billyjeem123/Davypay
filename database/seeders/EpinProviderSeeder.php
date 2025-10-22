<?php

namespace Database\Seeders;

use App\Models\EpinProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EpinProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Mtn',
                'img' => 'https://billia.smartrobtech.co.uk/assets/images/vtu/mtn.jpg',
                'airtime_code' => 'MTN',
            ],
            [
                'name' => '9mobile',
                'img' => 'https://billia.smartrobtech.co.uk/assets/images/vtu/etisalat.jpg',
                'airtime_code' => '9mobile',
            ],
            [
                'name' => 'Airtel',
                'img' => 'https://billia.smartrobtech.co.uk/assets/images/vtu/airtel.jpg',
                'airtime_code' => 'Airtel',
            ],
            [
                'name' => 'Glo',
                'img' => 'https://billia.smartrobtech.co.uk/assets/images/vtu/glo.jpg',
                'airtime_code' => 'Glo',
            ],
        ];

        foreach ($providers as $provider) {
            EpinProvider::updateOrCreate(
                ['airtime_code' => $provider['airtime_code']],
                $provider
            );
        }
    }
}
