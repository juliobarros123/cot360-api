<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quo_quotation_discount_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('quotation_version_id');
            $table->string('version_code', 20);
            $table->string('table_code', 20)->nullable();
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->string('status', 30)->default('applied');
            $table->timestamp('applied_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('quotation_id')
                ->references('id')
                ->on('quo_quotations')
                ->onDelete('cascade');
            $table->foreign('quotation_version_id')
                ->references('id')
                ->on('quo_quotation_versions')
                ->onDelete('cascade');

            $table->unique(['quotation_id', 'version_code', 'table_code'], 'uq_quo_discount_control_by_annex');
            $table->index(['quotation_id', 'version_code'], 'idx_quo_discount_control_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quo_quotation_discount_controls');
    }
};

