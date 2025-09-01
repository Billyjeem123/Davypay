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
//        Schema::dropIfExists('user_devices');
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->unique(); // UUID or unique identifier
            $table->string('device_fingerprint')->nullable(); // Additional security layer
            $table->string('device_name')->nullable(); // iPhone 14, Samsung Galaxy, etc.
            $table->string('device_type')->nullable(); // iOS, Android, Web (from your existing field)
            $table->string('platform')->nullable(); // iOS, Android, Web
            $table->string('app_version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
