<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obj_student_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('obj_objects')->onDelete('cascade');
            $table->integer('student_count')->default(0);
            $table->string('slug')->unique();
            $table->timestamps();
            
            // Índices
            $table->index('student_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obj_student_counts');
    }
};