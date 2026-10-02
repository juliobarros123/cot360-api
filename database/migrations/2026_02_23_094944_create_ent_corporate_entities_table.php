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
        Schema::create('ent_corporate_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('ent_entities')->cascadeOnDelete();
            $table->string('legal_name', 255)->nullable();
            $table->string('trade_name', 255)->nullable();
            $table->string('registration_number', 50)->nullable();
            $table->date('registration_date')->nullable();
            $table->string('cae_code', 20)->nullable();
            $table->string('industry_type', 100)->nullable();
            $table->string('tax_classification', 50)->nullable();
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('entity_id');
            $table->index('cae_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ent_corporate_entities');
    }
};
