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
        Schema::create('cfg_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_branch_id')->constrained('cfg_insurance_branches')->cascadeOnDelete();
            $table->integer('code');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('package', 50)->nullable()->comment('Basic, Standard, Premium, Custom');
            $table->string('object_type', 50)->nullable()->comment('Vehicle, Property, Person, Salary');
            $table->integer('qty_objects')->nullable();
            $table->boolean('active')->default(true);
            $table->string('slug', 100)->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('insurance_branch_id');
            $table->unique(['insurance_branch_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_templates');
    }
};
