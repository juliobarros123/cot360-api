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
        Schema::create('pol_movement_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_id')->constrained('pol_movements')->cascadeOnDelete();
            $table->enum('change_type', [
                'Object_Added',
                'Object_Removed',
                'Object_Modified',
                'Coverage_Added',
                'Coverage_Removed',
                'Coverage_Modified',
                'Data_Changed',
                'Premium_Changed'
            ]);
            $table->unsignedBigInteger('object_risk_id')->nullable()->comment('Risco afectado');
            $table->unsignedBigInteger('coverage_id')->nullable();
            $table->string('field_name', 100)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->decimal('premium_impact', 18, 2)->default(0);
            $table->timestamp('created_at')->nullable();
            $table->foreign('object_risk_id')->references('id')->on('pol_object_risk')->nullOnDelete();
            $table->index('movement_id');
            $table->index('change_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pol_movement_details');
    }
};
