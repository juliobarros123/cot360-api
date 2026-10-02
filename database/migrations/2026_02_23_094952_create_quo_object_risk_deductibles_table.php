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
        Schema::create('quo_object_risk_deductibles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_risk_id')->constrained('quo_object_risk')->cascadeOnDelete();
            $table->foreignId('deductible_id')->constrained('cfg_deductibles')->restrictOnDelete();
            $table->decimal('applied_value', 18, 2)->nullable();
            $table->decimal('applied_percent', 5, 2)->nullable();
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index('object_risk_id');
            $table->index('deductible_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quo_object_risk_deductibles');
    }
};
