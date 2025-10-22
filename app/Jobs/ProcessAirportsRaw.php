<?php

namespace App\Jobs;

use App\Helpers\FlightLogger;
use App\Models\Airport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessAirportsRaw implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $filename) {}

    public function handle(): void
    {
        FlightLogger::log("Processing airports file", ['filename' => $this->filename]);

        try {
            $raw = Storage::get("airports/{$this->filename}");
            $airports = $this->decodeAirportsData($raw);

            if (!$airports) {
                FlightLogger::error("Failed to decode airports data", ['filename' => $this->filename]);
                return;
            }

            $this->storeInDatabase($airports);

            FlightLogger::log("Airports processing completed", [
                'filename' => $this->filename,
                'count' => count($airports)
            ]);

        } catch (\Exception $e) {
            FlightLogger::error("Error processing airports", [
                'filename' => $this->filename,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function decodeAirportsData(string $raw): ?array
    {
        // Try JSON decode first (API returns JSON string)
        $jsonDecoded = json_decode($raw, true);
        if (is_string($jsonDecoded)) {
            $airports = $this->tryDecode($jsonDecoded);
            if ($airports) return $airports;
        }

        // Fallback: try direct decode (remove quotes)
        $cleaned = trim($raw, "\"' \n\r\t");
        return $this->tryDecode($cleaned);
    }

    private function tryDecode(string $data): ?array
    {
        try {
            // Step 1: Base64 decode
            $binary = base64_decode($data, true);
            if (!$binary) {
                FlightLogger::error("Airports base64 decode failed");
                return null;
            }

            // Step 2: Save binary as temp zip
            $tempFile = tempnam(sys_get_temp_dir(), 'airports_') . '.zip';
            file_put_contents($tempFile, $binary);

            $zip = new \ZipArchive();
            if ($zip->open($tempFile) !== TRUE) {
                unlink($tempFile);
                FlightLogger::error("Failed to open airports zip");
                return null;
            }

            // Step 3: Extract first file (should be JSON)
            $jsonContent = $zip->getFromIndex(0);
            $zip->close();
            unlink($tempFile);

            if (!$jsonContent) {
                FlightLogger::error("Airports zip is empty");
                return null;
            }

            // Step 4: Parse JSON
            $airports = json_decode($jsonContent, true);
            if (!is_array($airports)) {
                FlightLogger::error("Failed to decode airports JSON", [
                    'first_200_chars' => substr($jsonContent, 0, 200)
                ]);
                return null;
            }

            return $airports;

        } catch (\Exception $e) {
            FlightLogger::error("Airports decode error", ['error' => $e->getMessage()]);
            return null;
        }
    }


    private function storeInDatabase(array $airports): void
    {
        DB::transaction(function () use ($airports) {
            // Clear existing data
            Airport::truncate();

            // Insert in chunks
            $chunks = array_chunk($airports, 1000);
            foreach ($chunks as $chunk) {
                $insertData = [];
                foreach ($chunk as $airport) {
                    $insertData[] = [
                        'airport_code' => $airport['AirportCode'],
                        'name' => $airport['AirportName'],
                        'city' => $airport['City'] ?? null,
                        'country' => $airport['Country'] ?? null,
                        'city_country' => $airport['CityCountry'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Airport::insert($insertData);
            }
        });
    }

}
