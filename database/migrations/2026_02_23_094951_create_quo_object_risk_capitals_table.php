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
        Schema::create('quo_object_risk_capitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obj_object_id')->constrained('obj_objects')->cascadeOnDelete();
            $table->string('capital_type', 50)->comment('DP, RC, BUILDING, CONTENT, EXPLORATION, SUM_INSURED');
            $table->decimal('capital', 18, 2);
            $table->timestamps();
            $table->index('obj_object_id');
            $table->index('capital_type');
            $table->unique(['obj_object_id', 'capital_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quo_object_risk_capitals');
    }
};
