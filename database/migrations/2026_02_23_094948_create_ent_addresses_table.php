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
        Schema::create('ent_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('ent_entities')->cascadeOnDelete();
            $table->string('address_type', 50)->comment('Registered, Mailing, Branch, Fiscal, Other');
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 50)->default('Angola');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index('entity_id');
            $table->index('address_type');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ent_addresses');
    }
};
