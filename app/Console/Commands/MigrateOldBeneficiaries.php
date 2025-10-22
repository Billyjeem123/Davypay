<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateOldBeneficiaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-old-beneficiaries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $oldBeneficiaries = DB::connection('mysql_old')->table('beneficiaries')->get();
        $bar = $this->output->createProgressBar(count($oldBeneficiaries));
        $bar->start();

        foreach ($oldBeneficiaries as $old) {
            // ✅ Check if user exists in new DB
            $userExists = DB::connection('mysql')
                ->table('users')
                ->where('id', $old->user_id)
                ->exists();

            if (!$userExists) {
                // Skip missing users
                $bar->advance();
                continue;
            }

            // ✅ Ensure JSON data is valid
            $jsonData = $old->data;
            if (is_null($jsonData) || trim($jsonData) === '') {
                $jsonData = json_encode([
                    'type'     => $old->type ?? null,
                    'provider' => $old->provider ?? null,
                    'number'   => $old->number ?? null,
                ]);
            } elseif (!json_decode($jsonData)) {
                $jsonData = json_encode([
                    'raw_data' => $old->data,
                    'type'     => $old->type ?? null,
                    'provider' => $old->provider ?? null,
                ]);
            }

            try {
                DB::connection('mysql')->table('beneficiaries')->insert([
                    'id'           => $old->id,
                    'user_id'      => $old->user_id,
                    'data'         => $jsonData,
                    'name'         => $old->name,
                    'phone'        => $old->number,
                    'service_type' => $old->provider,
                    'created_at'   => $old->created_at,
                    'updated_at'   => $old->updated_at,
                ]);
            } catch (\Throwable $e) {
                $this->warn("\n⚠️ Skipped beneficiary ID {$old->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Beneficiaries migration completed successfully — invalid users skipped!');
    }
}
