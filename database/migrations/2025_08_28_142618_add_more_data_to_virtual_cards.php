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
        Schema::table('virtual_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_cards', 'reference')) {
                $table->string('reference')->nullable();
            }

            if (!Schema::hasColumn('virtual_cards', 'customer_id')) {
                $table->string('customer_id')->nullable();
            }
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_cards', function (Blueprint $table) {
            //
        });
    }
};
