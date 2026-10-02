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
        Schema::create('cfg_insurance_branches', function (Blueprint $table) {
    $table->id();
    $table->string('code', 20)->unique();
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->boolean('requires_coordinates')->default(false);
    $table->integer('default_validity_days')->default(30);
    $table->string('policy_number_prefix', 10)->nullable();
    $table->boolean('active')->default(true);
    $table->string('slug', 100)->unique();
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index('code');
    $table->index('active');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_insurance_branches');
    }
};
