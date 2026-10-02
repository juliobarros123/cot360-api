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
        Schema::create('cfg_rates_occupants', function (Blueprint $table) {
            $table->id();
            $table->integer('seat_min');
            $table->integer('seat_max');
            $table->enum('package', ['Min', 'Med', 'Max']);
            $table->double('mip');
            $table->double('dt');
            $table->double('df');
            $table->double('premium');
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['seat_min', 'seat_max', 'package']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_rates_occupants');
    }
};
