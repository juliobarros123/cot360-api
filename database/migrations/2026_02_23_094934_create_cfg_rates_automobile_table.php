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
       Schema::create('cfg_rates_automobile', function (Blueprint $table) {
    $table->id();
    $table->foreignId('insurance_branch_id')->nullable()->constrained('cfg_insurance_branches')->nullOnDelete();
    $table->string('code', 50)->nullable();
    $table->text('description')->nullable();
    $table->string('vehicle_type', 50)->nullable();
    $table->string('category', 50)->nullable();
    $table->integer('engine_min')->nullable();
    $table->integer('engine_max')->nullable();
    $table->integer('seat_min')->nullable();
    $table->integer('seat_max')->nullable();
    $table->decimal('weight_min', 10, 2)->nullable();
    $table->decimal('weight_max', 10, 2)->nullable();
    $table->decimal('min_rc_legal_ucf', 18, 2)->nullable();
    $table->decimal('min_rc_limit_kz', 18, 2)->nullable();
    $table->decimal('rc_premium_kz', 18, 2)->nullable();
    $table->decimal('ccc_percent', 10, 6)->nullable();
    $table->decimal('ire_percent', 10, 6)->nullable();
    $table->decimal('fr_percent', 10, 6)->nullable();
    $table->decimal('av_percent', 10, 6)->nullable();
    $table->decimal('fn_percent', 10, 6)->nullable();
    $table->decimal('gtaop_percent', 10, 6)->nullable();
    $table->boolean('active')->default(true);
    $table->string('slug', 100)->unique();
    $table->timestamps();
    $table->softDeletes();
    $table->index('insurance_branch_id');
    $table->index('vehicle_type');
    $table->index('category');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_rates_automobile');
    }
};
