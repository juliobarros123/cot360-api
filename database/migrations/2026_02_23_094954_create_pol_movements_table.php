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
        Schema::create('pol_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('pol_policies')->cascadeOnDelete();
            $table->unsignedBigInteger('from_version_id')->nullable()->comment('NULL se emissão inicial');
            $table->foreignId('to_version_id')->constrained('pol_policy_versions')->restrictOnDelete();
            $table->enum('movement_type', ['New', 'Renewal', 'Amendment', 'Cancellation', 'Reinstatement', 'Correction']);
            $table->string('movement_subtype', 50)->nullable();
            $table->date('movement_date');
            $table->date('effective_date');
            $table->decimal('premium_difference', 18, 2)->default(0)->comment('Positivo=débito, Negativo=crédito');
            $table->decimal('premium_difference_usd', 18, 2)->nullable();
            $table->integer('prorata_days')->nullable();
            $table->decimal('prorata_factor', 10, 6)->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Processed'])->default('Pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->foreign('from_version_id')->references('id')->on('pol_policy_versions')->nullOnDelete();
            $table->index('policy_id');
            $table->index('movement_type');
            $table->index('status');
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pol_movements');
    }
};
