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
        Schema::create('cfg_special_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_branch_id')->constrained('cfg_insurance_branches')->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->enum('condition_type', ['Inclusion', 'Exclusion', 'Clause', 'Warranty'])->default('Clause');
            $table->string('short_text', 500);
            $table->text('long_text')->nullable();
            $table->boolean('active')->default(true);
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index('insurance_branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_special_conditions');
    }
};
