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
        Schema::create('cfg_template_coverage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('cfg_templates')->cascadeOnDelete();
            $table->foreignId('coverage_id')->constrained('cfg_coverages')->cascadeOnDelete();
            $table->boolean('is_mandatory')->default(false);
            $table->integer('display_order')->default(1);
            $table->timestamps();
            $table->unique(['template_id', 'coverage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_template_coverage');
    }
};
