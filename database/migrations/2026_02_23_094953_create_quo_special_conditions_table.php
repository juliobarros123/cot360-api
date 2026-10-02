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
        Schema::create('quo_special_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_version_id')->constrained('quo_quotation_versions')->cascadeOnDelete();
            $table->foreignId('special_condition_id')->constrained('cfg_special_conditions')->restrictOnDelete();
            $table->text('custom_text')->nullable();
            $table->integer('display_order')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index('quotation_version_id');
            $table->index('special_condition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quo_special_conditions');
    }
};
