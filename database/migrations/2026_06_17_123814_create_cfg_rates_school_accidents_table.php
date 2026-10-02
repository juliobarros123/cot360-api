<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfg_rates_school_accident', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_branch_id')->nullable()->constrained('cfg_insurance_branches')->nullOnDelete();
            $table->foreignId('coverage_id')->nullable()->constrained('cfg_coverages')->nullOnDelete();
            $table->string('type', 50)->nullable()->comment('Obrigatorio, Facultativa');
            $table->string('package', 50)->nullable()->comment('Option A, Option B, Option C');
            $table->decimal('premium_value', 18, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index('insurance_branch_id');
            $table->index('coverage_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfg_rates_school_accident');
    }
};