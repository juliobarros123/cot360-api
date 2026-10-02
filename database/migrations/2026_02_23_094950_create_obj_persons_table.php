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
        Schema::create('obj_persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('obj_objects')->cascadeOnDelete();
            $table->unsignedBigInteger('entity_id')->nullable()->comment('Nullable — preenchido quando promovido a entidade completa');
            $table->string('name', 255)->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->date('birthdate')->nullable();
            $table->integer('age')->nullable();
            $table->boolean('dependent')->default(false);
            $table->string('relationship', 50)->nullable()->comment('Titular, Cônjuge, Filho, Dependente');
            $table->enum('job_category', ['I', 'II', 'III', 'IV', 'Not specified'])->default('Not specified');
            $table->decimal('salary', 18, 2)->nullable();
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('entity_id')->references('id')->on('ent_entities')->nullOnDelete();
            $table->unique('object_id');
            $table->index('entity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obj_persons');
    }
};
