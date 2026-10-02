<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $branchId = DB::table('cfg_insurance_branches')->where('code', '32')->value('id');
        if (!$branchId) {
            return;
        }

        $objectIds = DB::table('quo_object_risk as qor')
            ->join('quo_quotation_versions as qv', 'qv.id', '=', 'qor.quotation_version_id')
            ->join('quo_quotations as qq', 'qq.id', '=', 'qv.quotation_id')
            ->where('qq.insurance_branch_id', $branchId)
            ->whereNotNull('qor.object_id')
            ->distinct()
            ->pluck('qor.object_id');

        if ($objectIds->isEmpty()) {
            return;
        }

        DB::table('obj_properties')
            ->whereIn('object_id', $objectIds->all())
            ->update([
                'exploration_capital' => null,
                'fire_protection' => false,
                'number_of_floors' => null,
                'total_area' => null,
                'latitude' => null,
                'longitude' => null,
                'city' => null,
                'province' => null,
                'postal_code' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Sem rollback de dados para evitar repor conteúdo removido.
    }
};

