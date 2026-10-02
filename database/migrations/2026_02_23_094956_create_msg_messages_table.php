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
        Schema::create('msg_messages', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->enum('message_type', ['Collection_Notice', 'Renewal_Reminder', 'Policy_Document', 'Notification', 'Alert', 'Reminder', 'Confirmation', 'Marketing']);
            $table->string('subtype', 50)->nullable();
            $table->enum('sender_module', ['FIN', 'POL', 'CLA', 'CRM', 'ENT', 'SYS']);
            $table->enum('channel', ['Email', 'SMS', 'Letter', 'WhatsApp', 'Push', 'InApp']);
            $table->unsignedBigInteger('recipient_entity_id')->nullable();
            $table->string('recipient_name', 255)->nullable();
            $table->string('recipient_contact', 255)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('content')->nullable();
            $table->string('template_code', 50)->nullable();
            $table->json('attachments_json')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->enum('status', ['Draft', 'Scheduled', 'Sending', 'Sent', 'Delivered', 'Failed', 'Bounced', 'Opened', 'Clicked'])->default('Draft');
            $table->string('delivery_status', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->string('external_id', 100)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('recipient_entity_id')->references('id')->on('ent_entities')->nullOnDelete();
            $table->index('entity_type');
            $table->index('entity_id');
            $table->index('message_type');
            $table->index('channel');
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('msg_messages');
    }
};
