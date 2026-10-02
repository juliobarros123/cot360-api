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
        Schema::create('msg_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('message_type', ['Collection_Notice', 'Renewal_Reminder', 'Policy_Document', 'Notification', 'Alert', 'Reminder', 'Confirmation', 'Marketing']);
            $table->enum('channel', ['Email', 'SMS', 'Letter', 'WhatsApp', 'Push', 'InApp']);
            $table->string('subject_template', 255)->nullable();
            $table->text('content_template');
            $table->json('available_variables_json')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('message_type');
            $table->index('channel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('msg_templates');
    }
};
