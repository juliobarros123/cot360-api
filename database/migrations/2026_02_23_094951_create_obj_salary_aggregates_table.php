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
        Schema::create('obj_salary_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('obj_objects')->cascadeOnDelete();
            $table->decimal('total_base_salary', 18, 2);
            $table->decimal('total_addons_salary', 18, 2);
            $table->decimal('total_avg_variable_salary', 18, 2);
            $table->integer('number_of_months');
            $table->integer('employee_count');
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('object_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obj_salary_aggregates');
    }
};
