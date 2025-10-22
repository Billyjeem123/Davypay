<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-old-notifications';

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
        $oldNotifications = DB::connection('mysql_old')->table('notifications')->get();
        $bar = $this->output->createProgressBar(count($oldNotifications));
        $bar->start();

        foreach ($oldNotifications as $old) {
            // ✅ Skip if the notifiable user doesn't exist
            $userExists = DB::connection('mysql')
                ->table('users')
                ->where('id', $old->notifiable_id)
                ->exists();

            if (!$userExists) {
                $bar->advance();
                continue;
            }

            // ✅ Validate JSON structure in data column
            $data = $old->data;
            if (is_null($data) || trim($data) === '') {
                $data = json_encode(['message' => 'Empty notification data']);
            } elseif (!json_decode($data)) {
                $data = json_encode(['raw_data' => $old->data]);
            }

            try {
                DB::connection('mysql')->table('notifications')->insert([
                    'id'              => $old->id,
                    'type'            => $old->type,
                    'notifiable_type' => $old->notifiable_type,
                    'notifiable_id'   => $old->notifiable_id,
                    'data'            => $data,
                    'read_at'         => $old->read_at,
                    'created_at'      => $old->created_at,
                    'updated_at'      => $old->updated_at,
                ]);
            } catch (\Throwable $e) {
                $this->warn("\n⚠️ Skipped notification {$old->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Notifications migration completed successfully — missing users skipped!');
    }

}
