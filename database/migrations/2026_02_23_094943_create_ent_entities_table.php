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
        Schema::create('ent_entities', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_type', ['Personal', 'Corporate']);
            $table->string('name', 255);
            $table->string('nif', 50)->unique()->nullable();
            $table->string('id_card', 50)->unique()->nullable();
            $table->string('passport', 50)->unique()->nullable();
            $table->string('temp_identifier', 100)->unique()->nullable();
            $table->enum('identifier_status', ['Temporary', 'Partial', 'Verified'])->default('Temporary');
            $table->enum('kyc_status', ['Not_Started', 'Pending', 'Approved', 'Rejected', 'Expired'])->default('Not_Started');
            $table->timestamp('kyc_approved_at')->nullable();
            $table->timestamp('kyc_expires_at')->nullable();
            $table->enum('pep_status', ['Not_Checked', 'Not_PEP', 'PEP', 'Related_PEP'])->default('Not_Checked');
            $table->boolean('us_person')->default(false);
            $table->enum('status', ['Active', 'Inactive', 'Blocked', 'Pending_Compliance'])->default('Active');
            $table->boolean('active')->default(true);
            $table->string('slug', 100)->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('entity_type');
            $table->index('nif');
            $table->index('id_card');
            $table->index('passport');
            $table->index('temp_identifier');
            $table->index('identifier_status');
            $table->index('status');
            $table->index('kyc_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ent_entities');
    }
};
