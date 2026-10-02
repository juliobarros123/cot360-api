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
        Schema::create('obj_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('obj_objects')->cascadeOnDelete();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('property_type', 50)->nullable()->comment('Residential, Commercial, Industrial, BusinessExploration');
            $table->double('total_area')->nullable();
            $table->integer('number_of_floors')->nullable();
            $table->integer('construction_year')->nullable();
            $table->boolean('fire_protection')->default(false);
            $table->boolean('security_system')->default(false);
            $table->decimal('building_capital', 18, 2)->nullable();
            $table->decimal('content_capital', 18, 2)->nullable();
            $table->decimal('exploration_capital', 18, 2)->nullable();
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('object_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obj_properties');
    }
};
