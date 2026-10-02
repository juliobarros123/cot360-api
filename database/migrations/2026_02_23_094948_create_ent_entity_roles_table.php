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
        Schema::create('ent_entity_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('ent_entities')->cascadeOnDelete();
            $table->foreignId('role_type_id')->constrained('ent_role_types')->restrictOnDelete();
            $table->foreignId('role_subtype_id')->nullable()->constrained('ent_role_subtypes')->nullOnDelete();
            $table->string('license_number', 50)->nullable();
            $table->date('license_expiry')->nullable();
            $table->decimal('commission_default', 5, 2)->nullable();
            $table->foreignId('sales_channel_id')->nullable()->constrained('ent_sales_channels')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('entity_id');
            $table->index('role_type_id');
            $table->index('role_subtype_id');
            $table->unique(['entity_id', 'role_type_id', 'role_subtype_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ent_entity_roles');
    }
};
