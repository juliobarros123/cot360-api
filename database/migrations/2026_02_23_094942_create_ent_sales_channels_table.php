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
        Schema::create('ent_sales_channels', function (Blueprint $table) {
            $table->id();
            $table->string('channel_type', 50);
            $table->string('name', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('responsible', 100)->nullable();
            $table->boolean('active')->default(true);
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index('channel_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ent_sales_channels');
    }
};
