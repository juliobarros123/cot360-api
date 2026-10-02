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
        Schema::create('ent_agent_branch_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_role_id')->constrained('ent_entity_roles')->cascadeOnDelete();
            $table->foreignId('insurance_branch_id')->constrained('cfg_insurance_branches')->cascadeOnDelete();
            $table->decimal('commission_rate', 5, 2);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();
            $table->index('entity_role_id');
            $table->index('insurance_branch_id');
            // ✅ CORRIGIDO — nome curto explícito
            $table->unique(['entity_role_id', 'insurance_branch_id', 'valid_from'], 'ent_abc_role_branch_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ent_agent_branch_commissions');
    }
};
