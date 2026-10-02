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
        Schema::create('obj_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('obj_objects')->cascadeOnDelete();
            $table->string('plate', 20)->nullable();
            $table->string('chassis_number', 50)->nullable();
            $table->string('brand', 50)->nullable();
            $table->string('model', 50)->nullable();
            $table->string('version', 50)->nullable();
            $table->string('color', 30)->nullable();
            $table->string('type', 50)->nullable()->comment('Ligeiro, Pesado, Motociclo, Atrelado, Autocarro, Camião, Táxi, Outro');
            $table->string('subcategory', 50)->nullable();
            $table->integer('cylinder_cc')->nullable();
            $table->integer('number_seats')->nullable();
            $table->integer('weight')->nullable();
            $table->date('first_plate_date')->nullable();
            $table->decimal('dp_capital', 18, 2)->nullable()->comment('Capital actual Danos Próprios');
            $table->decimal('dp_initial_capital', 18, 2)->nullable()->comment('Capital original DP — referência histórica');
            $table->decimal('rc_capital', 18, 2)->nullable()->comment('Capital actual Responsabilidade Civil');
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('object_id');
            $table->index('plate');
            $table->index('chassis_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obj_vehicles');
    }
};
