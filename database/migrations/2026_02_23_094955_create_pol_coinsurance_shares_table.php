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
        Schema::create('pol_coinsurance_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_version_id')->constrained('pol_policy_versions')->cascadeOnDelete();
            $table->foreignId('insurer_id')->constrained('ent_entities')->restrictOnDelete();
            $table->boolean('is_leader')->default(false);
            $table->decimal('share_percent', 5, 2);
            $table->decimal('share_premium', 18, 2);
            $table->decimal('share_premium_usd', 18, 2)->nullable();
            $table->string('external_reference', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('policy_version_id');
            $table->index('insurer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pol_coinsurance_shares');
    }
};
