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
        Schema::dropIfExists('usdt_wallets');
        Schema::create('usdt_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();

            $table->string('address')->unique();
            $table->decimal('balance', 24, 8)->default(0);
            $table->string('network')->nullable();
            $table->enum('mode', ['sandbox', 'live'])->default('sandbox');
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usdt_wallets');
    }
};
