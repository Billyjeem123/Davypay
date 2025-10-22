<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MigrateOldAdmins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-old-admins';

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
        $oldAdmins = DB::connection('mysql_old')->table('admins')->get();
        $bar = $this->output->createProgressBar(count($oldAdmins));
        $bar->start();

        foreach ($oldAdmins as $old) {
            $firstName = $old->first_name ?? 'Unknown';
            $lastName = $old->surname ?? 'Admin';
            $email = $old->email ?? strtolower(Str::random(6)) . '@example.com';
            $password = $old->password
                ? $old->password
                : Hash::make('password123');
            $role = 'admin';

            // Skip duplicates
            if (Admin::where('email', $email)->exists()) {
                $email = Str::random(8) . '@example.com';
            }

            Admin::updateOrCreate(
                ['id' => $old->id],
                [
                    'first_name'        => $firstName,
                    'last_name'         => $lastName,
                    'email'             => $email,
                    'password'          => $password,
                    'role'              => $role,
                    'is_active'         => 1,
                    'email_verified_at' => $old->email_verified_at,
                    'permissions'       => null,
                    'remember_token'    => $old->remember_token ?? Str::random(10),
                    'created_at'        => $old->created_at,
                    'updated_at'        => $old->updated_at,
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Old admins migrated successfully!');
    }
}
