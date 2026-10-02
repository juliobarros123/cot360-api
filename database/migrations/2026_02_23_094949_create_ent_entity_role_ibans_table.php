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
        Schema::create('ent_entity_role_ibans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_role_id')->constrained('ent_entity_roles')->cascadeOnDelete();
            $table->string('iban', 50);
            $table->string('bank_name', 100)->nullable();
            $table->string('swift', 20)->nullable();
            $table->string('currency', 3)->default('AOA');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index('entity_role_id');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ent_entity_role_ibans');
    }
};
