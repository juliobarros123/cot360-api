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
        Schema::create('quo_premiums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_version_id')->constrained('quo_quotation_versions')->cascadeOnDelete();
            $table->string('type', 50)->comment('Net, Tax_FGA, Tax_Selo, Gross, Commission');
            $table->decimal('value', 20, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->index('quotation_version_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quo_premiums');
    }
};
