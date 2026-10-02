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
        Schema::create('cfg_deductibles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coverage_id')->nullable()->constrained('cfg_coverages')->nullOnDelete();
            $table->string('deduct_type', 50)->comment('Percentage, Fixed, Mixed');
            $table->enum('deduct_object', ['CLAIM', 'CAP_BUILDING', 'CAP_INSURANCE', 'INDEMNITY', 'NA']);
            $table->float('deduct_percent')->nullable();
            $table->float('deduct_value')->nullable();
            $table->float('deduct_min')->nullable();
            $table->float('deduct_max')->nullable();
            $table->float('discount')->nullable();
            $table->text('details')->nullable();
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index('coverage_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_deductibles');
    }
};
