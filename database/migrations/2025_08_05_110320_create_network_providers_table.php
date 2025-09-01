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
        Schema::create('network_providers', function (Blueprint $table) {
            $table->id();
            $table->string('network_name'); // MTN, GLO, AIRTEL, 9MOBILE
            $table->decimal('admin_rate', 5, 2)->default(0.00); // Admin percentage rate (e.g., 5.00 for 5%)
            $table->string('transfer_number'); // Number users will transfer airtime to
            $table->boolean('is_active')->default(true); // Whether this provider is active
            $table->text('description')->nullable(); // Optional description
            $table->timestamps();

            // Ensure unique network names
            $table->unique('network_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_providers');
    }
};
