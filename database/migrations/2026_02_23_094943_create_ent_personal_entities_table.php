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
        Schema::create('ent_personal_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('ent_entities')->cascadeOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->enum('id_type', ['BI', 'Passport', 'Residence_Card', 'Other'])->nullable();
            $table->date('id_expiry')->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('marital_status', 50)->nullable();
            $table->string('profession', 100)->nullable();
            $table->date('driver_license_date')->nullable();
            $table->text('personal_notes')->nullable();
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('entity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ent_personal_entities');
    }
};
