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
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignId('type_id')->constrained('gift_card_lists')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('country');
            $table->decimal('initial_value', 10, 2);
            $table->decimal('current_value', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('image_path')->nullable();
            $table->string('code')->nullable();
            $table->string('package')->nullable();
            $table->decimal('evaluated_value', 10, 2)->nullable();
            $table->enum('status', ['pending', 'evaluated', 'confirmed', 'paid', 'rejected'])->default('pending');
            $table->string('issue_by')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};
