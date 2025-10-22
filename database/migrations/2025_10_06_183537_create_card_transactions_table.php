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
        Schema::create('card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('card_id');
            $table->string('transaction_id')->nullable()->unique();
            $table->string('authorization_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('merchant_name')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('channel')->nullable();
            $table->string('type')->default('purchase');
            $table->string('status')->default('pending');
            $table->decimal('amount_before', 15, 2)->nullable();
            $table->decimal('amount_after', 15, 2)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_transactions');
    }
};
