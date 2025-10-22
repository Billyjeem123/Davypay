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
        Schema::create('usdt_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usdt_wallet_id')->constrained()->cascadeOnDelete();
            $table->string('hash')->nullable();
            $table->string('reference')->nullable();
            $table->string('type');
            $table->string('action');
            $table->string('chain')->nullable();
            $table->decimal('amount', 24, 8);
            $table->decimal('fees', 20, 8)->default(0);
            $table->string('channel')->nullable();
            $table->string('description')->nullable();
            $table->integer('confirmations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usdt_transactions');
    }
};
