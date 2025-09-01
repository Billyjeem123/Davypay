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
        // Migration
        Schema::create('platform_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained('transaction_logs');
            $table->foreignId('user_id')->constrained();
            $table->decimal('fee_amount', 15, 2);
            $table->decimal('fee_percentage', 5, 2);
            $table->string('provider'); // paystack, nomba
            $table->string('transaction_type'); // deposit, transfer
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_fees');
    }
};
