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
        Schema::table('obj_properties', function (Blueprint $table) {
            $table->string('plan_name', 100)->nullable()->after('property_type');
            $table->string('risk_class', 100)->nullable()->after('plan_name');
            $table->string('insured_role', 100)->nullable()->after('risk_class');
            $table->string('residence_type', 100)->nullable()->after('insured_role');
            $table->boolean('theft_protection')->nullable()->after('security_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obj_properties', function (Blueprint $table) {
            $table->dropColumn([
                'plan_name',
                'risk_class',
                'insured_role',
                'residence_type',
                'theft_protection',
            ]);
        });
    }
};

