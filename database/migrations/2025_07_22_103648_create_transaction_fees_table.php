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
        Schema::create('transaction_fees', function (Blueprint $table) {
            $table->id();
            $table->enum('provider', ['paystack', 'nomba']);
            $table->enum('type', ['transfer', 'deposit']);
            $table->decimal('min', 15, 2)->default(0); // min amount
            $table->decimal('max', 15, 2)->default(0); // max amount
            $table->decimal('fee', 5, 2)->default(0);  // percent fee (e.g., 1.5)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_fees');
    }
};
