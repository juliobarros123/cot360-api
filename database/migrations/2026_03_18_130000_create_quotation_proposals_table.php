<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_version_id')
                ->nullable()
                ->constrained('quo_quotation_versions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->integer('quotation_version');
            $table->string('client_email')->nullable();
            $table->string('client_name')->nullable();
            $table->text('message')->nullable();
            $table->string('proposal_pdf_url');
            $table->string('proposal_form_url');
            $table->string('status')->default('sent');
            $table->string('tracking_token')->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('client_data')->nullable();
            $table->timestamps();
            $table->index(['quotation_version_id', 'quotation_version']);
            $table->index('tracking_token');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_proposals');
    }
};
