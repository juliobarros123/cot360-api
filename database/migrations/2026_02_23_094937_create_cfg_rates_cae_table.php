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
        Schema::create('cfg_rates_cae', function (Blueprint $table) {
            $table->id();
            $table->string('cae', 20)->unique();
            $table->text('description')->nullable();
            $table->decimal('rate_mre_building', 10, 6)->nullable();
            $table->decimal('rate_mre_content', 10, 6)->nullable();
            $table->decimal('work_accident', 10, 6)->nullable();
            $table->boolean('active')->default(true);
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index('cae');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_rates_cae');
    }
};
