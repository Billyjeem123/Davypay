<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $airports = [
            [
                'airport_code' => 'LOS',
                'name' => 'Murtala Muhammed International Airport (LOS)',
                'city' => 'Lagos',
                'country' => 'Nigeria',
                'city_country' => 'Lagos, Nigeria',
            ],
            [
                'airport_code' => 'ABV',
                'name' => 'Nnamdi Azikwe International Airport (ABV)',
                'city' => 'Abuja',
                'country' => 'Nigeria',
                'city_country' => 'Abuja, Nigeria',
            ],
            [
                'airport_code' => 'DXB',
                'name' => 'Dubai International Airport (DXB)',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'city_country' => 'Dubai, United Arab Emirates',
            ],
            [
                'airport_code' => 'LHR',
                'name' => 'Heathrow (LHR)',
                'city' => 'London',
                'country' => 'United Kingdom',
                'city_country' => 'London, United Kingdom',
            ],
            [
                'airport_code' => 'QOW',
                'name' => 'Sam Mbakwe Airport (QOW)',
                'city' => 'Owerri',
                'country' => 'Nigeria',
                'city_country' => 'Owerri, Nigeria',
            ],
            [
                'airport_code' => 'PHC',
                'name' => 'Port Harcourt International Airport (PHC)',
                'city' => 'Port Harcourt',
                'country' => 'Nigeria',
                'city_country' => 'Port Harcourt, Nigeria',
            ],
            [
                'airport_code' => 'JNB',
                'name' => 'Johannesburg (JNB)',
                'city' => 'Johannesburg',
                'country' => 'South Africa',
                'city_country' => 'Johannesburg, South Africa',
            ],
            [
                'airport_code' => 'ABB',
                'name' => 'Asaba Airport (ABB)',
                'city' => 'Asaba',
                'country' => 'Nigeria',
                'city_country' => 'Asaba, Nigeria',
            ],
            [
                'airport_code' => 'BNI',
                'name' => 'Benin Airport  (BNI)',
                'city' => 'Benin',
                'country' => 'Nigeria',
                'city_country' => 'Benin, Nigeria',
            ],
            [
                'airport_code' => 'IST',
                'name' => 'Istanbul (IST)',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'city_country' => 'Istanbul, Turkey',
            ],

            [
                'airport_code' => 'ACC',
                'name' => 'Accra airport (ACC)',
                'city' => 'Accra',
                'country' => 'Ghana',
                'city_country' => 'Accra, Ghana',
            ],
            [
                'airport_code' => 'JFK',
                'name' => 'New York (JFK)',
                'city' => 'New York',
                'country' => 'United States',
                'city_country' => 'New York, United States',
            ],
            [
                'airport_code' => 'YYZ',
                'name' => 'Toronto (YYZ)',
                'city' => 'Toronto',
                'country' => 'Canada',
                'city_country' => 'Toronto, Canada',
            ],
            [
                'airport_code' => 'NBO',
                'name' => 'Nairobi Jomo Kenyatta airport (NBO)',
                'city' => 'Nairobi Jomo Kenyatta',
                'country' => 'Kenya',
                'city_country' => 'Nairobi Jomo Kenyatta, Kenya',
            ],
            [
                'airport_code' => 'IAH',
                'name' => 'Houston (IAH)',
                'city' => 'Houston',
                'country' => 'United States',
                'city_country' => 'Houston, United States',
            ],
            [
                'airport_code' => 'CDG',
                'name' => 'Charles De Gaulle (CDG)',
                'city' => 'Paris',
                'country' => 'France',
                'city_country' => 'Paris, France',
            ],
        ];

        foreach ($airports as $airport) {
            Airport::updateOrCreate(
                ['airport_code' => $airport['airport_code']],
                $airport
            );
        }
    }
}
