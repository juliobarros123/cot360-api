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
        Schema::create('pol_special_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_version_id')->constrained('pol_policy_versions')->cascadeOnDelete();
            $table->unsignedBigInteger('special_condition_id')->nullable();
            $table->enum('condition_type', ['Inclusion', 'Exclusion', 'Clause', 'Warranty']);
            $table->string('short_text', 500);
            $table->text('long_text')->nullable();
            $table->integer('display_order')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->foreign('special_condition_id')->references('id')->on('cfg_special_conditions')->nullOnDelete();
            $table->index('policy_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pol_special_conditions');
    }
};
