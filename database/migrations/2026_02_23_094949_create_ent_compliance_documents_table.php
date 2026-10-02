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
        Schema::create('ent_compliance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('ent_entities')->cascadeOnDelete();
            $table->enum('document_type', [
                'KYC_Form',
                'PEP_Declaration',
                'US_Declaration',
                'ID_Copy',
                'Address_Proof',
                'Financial_Statement',
                'Other'
            ]);
            $table->string('file_path', 500)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Expired'])->default('Pending');
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index('entity_id');
            $table->index('document_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ent_compliance_documents');
    }
};
