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
        Schema::create('pol_reinsurance_cessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_version_id')->constrained('pol_policy_versions')->cascadeOnDelete();
            $table->unsignedBigInteger('object_risk_id')->nullable()->comment('NULL = toda a apólice');
            $table->foreignId('reinsurer_id')->constrained('ent_entities')->restrictOnDelete();
            $table->string('treaty_reference', 100)->nullable();
            $table->enum('treaty_type', ['Quota_Share', 'Surplus', 'Excess_of_Loss', 'Facultative']);
            $table->decimal('ceded_percent', 5, 2)->nullable();
            $table->decimal('ceded_capital', 18, 2)->nullable();
            $table->decimal('ceded_premium', 18, 2)->nullable();
            $table->decimal('retention_percent', 5, 2)->nullable();
            $table->decimal('retention_capital', 18, 2)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->foreign('object_risk_id')->references('id')->on('pol_object_risk')->nullOnDelete();
            $table->index('policy_version_id');
            $table->index('reinsurer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pol_reinsurance_cessions');
    }
};
