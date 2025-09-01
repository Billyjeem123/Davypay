<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard
            'view dashboard',

            // User Management
            'view all users',
            'view active users',
            'view suspended users',
            'manage kyc',
            'manage user roles',
            'manage user permissions',
            'view user activity logs',

            // Transaction Management
            'view user transactions',
            'view all transactions',
            'view pending transactions',
            'view failed transactions',
            'view successful transactions',
            'view transaction reports',

            // Wallet Management
            'view wallet overview',
            'view wallet funding',

            // Security & Monitoring
            'view fraudulent transaction reports',

            // Reports & Analytics
            'view transaction analytics',

            // Support & Communication
            'send announcements',
            'view all announcements',

            // Settings & Notifications
            'manage settings',
            'view notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('Permissions seeded successfully.');
    }

}
