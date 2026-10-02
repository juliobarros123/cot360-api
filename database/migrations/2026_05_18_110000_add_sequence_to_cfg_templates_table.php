<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfg_templates', function (Blueprint $table) {
            $table->integer('sequence')->nullable()->after('code');
            $table->index('sequence');
        });
    }

    public function down(): void
    {
        Schema::table('cfg_templates', function (Blueprint $table) {
            $table->dropIndex(['sequence']);
            $table->dropColumn('sequence');
        });
    }
};

