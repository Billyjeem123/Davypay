<?php

namespace App\Console\Commands;

use App\Models\Airport;
use Illuminate\Console\Command;

class ImportAirports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-airports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = base_path('public/airports_raw.txt');
        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return 1;
        }

        $raw = trim(file_get_contents($file));
        $raw = rtrim($raw, ',');

        $json = '[' . $raw . ']';
        $data = json_decode($json, true);

        if ($data === null) {
            $this->error("Failed to decode JSON. Check the file format.");
            return 1;
        }

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        foreach ($data as $row) {
            Airport::updateOrCreate(
                ['airport_code' => $row['AirportCode']],
                [
                    'name'         => $row['AirportName'] ?? '',
                    'city'         => $row['City'] ?? '',
                    'country'      => $row['Country'] ?? '',
                    'city_country' => $row['CityCountry'] ?? '',
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n✅ Airports imported successfully!");
        return 0;
    }
}
