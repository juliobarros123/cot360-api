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
        Schema::create('cfg_coverages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_branch_id')->constrained('cfg_insurance_branches')->cascadeOnDelete();
            $table->unsignedBigInteger('group_id')->nullable()->comment('Cobertura pai');
            $table->integer('seq');
            $table->integer('gis_cod')->nullable();
            $table->string('type_cov', 50);
            $table->string('short_name', 50)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('obj_type', 50)->nullable()->comment('Vehicle, Property, Person, Salary, All');
            $table->double('capital_value')->nullable();
            $table->string('capital_origin', 100)->nullable();
            $table->string('rate_type', 50)->nullable()->comment('Fixed, FromTable, Calculated, Manual');
            $table->double('risk_rate')->nullable();
            $table->string('risk_rate_origin', 50)->nullable();
            $table->string('cov_opt', 50)->nullable();
            $table->string('type_covt', 50)->nullable();
            $table->decimal('cov_perc', 10, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('group_id')->references('id')->on('cfg_coverages')->nullOnDelete();
            $table->index('insurance_branch_id');
            $table->index('group_id');
            $table->index('seq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_coverages');
    }
};
