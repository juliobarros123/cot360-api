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
        Schema::create('quo_insurance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_risk_id')->nullable()->constrained('quo_object_risk')->cascadeOnDelete();
            $table->enum('type', ['Bonus', 'Surcharge', 'Discount', 'Deductible']);
            $table->enum('value_type', ['Percentage', 'Fixed']);
            $table->decimal('value', 18, 2);
            $table->string('rate', 20)->nullable();
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('rate_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('object_risk_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quo_insurance_adjustments');
    }
};
