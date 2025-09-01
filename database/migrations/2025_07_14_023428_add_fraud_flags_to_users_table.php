<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_account_restricted')->default(0)->after('account_level');
            $table->boolean('is_ban')->default(0)->after('is_account_restricted');
            $table->boolean('view')->default(1)->after('is_ban');
            $table->string('reason_restriction')->nullable()->after('view');// Assuming 1 means "can view"
            $table->string('restriction_date')->nullable()->after('reason_restriction');// Assuming 1 means "can view"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_account_restricted', 'is_ban', 'view']);
        });
    }
};
