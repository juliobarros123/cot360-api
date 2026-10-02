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
        Schema::create('pol_object_risk_capitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_risk_id')->constrained('pol_object_risk')->cascadeOnDelete();
            $table->string('capital_type', 50)->comment('DP, RC, BUILDING, CONTENT, EXPLORATION, SUM_INSURED');
            $table->decimal('capital', 18, 2);
            $table->timestamps();
            $table->index('object_risk_id');
            $table->index('capital_type');
            $table->unique(['object_risk_id', 'capital_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pol_object_risk_capitals');
    }
};
