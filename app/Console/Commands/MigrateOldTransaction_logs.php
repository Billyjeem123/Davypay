<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateOldTransaction_logs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-old-transaction_logs';

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
        $oldLogs = DB::connection('mysql_old')->table('transaction_logs')->get();
        $bar = $this->output->createProgressBar(count($oldLogs));
        $bar->start();

        foreach ($oldLogs as $old) {
            $userExists = DB::connection('mysql')
                ->table('users')
                ->where('id', $old->user_id)
                ->exists();

            if (!$userExists) {
                $bar->advance();
                continue;
            }

            try {
                DB::connection('mysql')->table('transaction_logs')->insert([
                    'id'                    => $old->id,
                    'transaction_reference' => $old->reference,
                    'vtpass_transaction_id' => $old->transaction_id,
                    'user_id'               => $old->user_id,
                    'wallet_id'             => null,
                    'type'                  => $old->type,
                    'amount'                => $old->amount ?? 0.00,
                    'amount_before'         => $old->balance_before ?? 0.00,
                    'amount_after'          => $old->balance_after ?? 0.00,
                    'payload'               => $old->data,
                    'description'           => $old->recipient ?? null,
                    'status'                => $old->status,
                    'provider'              => null,
                    'channel'               => null,
                    'category'              => null,
                    'idempotency_key'       => null,
                    'service_type'          => null,
                    'created_at'            => $old->created_at,
                    'updated_at'            => $old->updated_at,
                ]);
            } catch (\Throwable $e) {
                $this->warn("\n⚠️ Skipped ID {$old->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Transaction logs migration completed successfully (invalid users skipped).');
    }
}
