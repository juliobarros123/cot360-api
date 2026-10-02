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
        Schema::create('cfg_rates_rc_options', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rc_base');
            $table->bigInteger('rc_fac');
            $table->decimal('perc_rate', 10, 6);
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['rc_base', 'rc_fac']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_rates_rc_options');
    }
};
