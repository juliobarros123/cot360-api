<?php

namespace App\Console\Commands;

use App\Models\CFG\CFG_coverages;
use App\Models\CFG\CFG_insurance_branches;
use App\Models\CFG\CFG_template_coverage;
use App\Models\CFG\CFG_templates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SetupMultirriscosHabitacao extends Command
{
    protected $signature = 'app:setup-multirriscos-habitacao
                            {--branch-code=32 : Código do ramo MR Habitação}
                            {--template-codes=1,2 : Códigos dos templates a preparar}';

    protected $description = 'Configura MR Habitação de ponta a ponta (2 planos, coberturas e vínculos) de forma idempotente';

    public function handle(): int
    {
        $branchCode = trim((string) $this->option('branch-code'));
        $templateCodes = $this->parseTemplateCodes((string) $this->option('template-codes'));

        if ($branchCode === '') {
            $this->error('branch-code é obrigatório.');
            return self::FAILURE;
        }

        if (empty($templateCodes)) {
            $this->error('Nenhum template code válido foi informado.');
            return self::FAILURE;
        }

        try {
            $summary = DB::transaction(function () use ($branchCode, $templateCodes) {
                $branch = $this->upsertBranch($branchCode);
                $this->resetBranchConfiguration((int) $branch->id);

                $templates = $this->upsertTemplates((int) $branch->id, $templateCodes);
                $coverages = $this->upsertCoverages((int) $branch->id);
                $linked = $this->syncTemplateCoverage($templates, $coverages);
                $cleanedProperties = $this->sanitizeMrhPropertyFields((int) $branch->id);

                return [
                    'branch' => $branch,
                    'templates' => $templates,
                    'coverages' => $coverages,
                    'linked' => $linked,
                    'cleaned_properties' => $cleanedProperties,
                ];
            });
        } catch (Throwable $e) {
            $this->error('Falha ao configurar MR Habitação: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('MR Habitação configurado com sucesso.');
        $this->line("Ramo: {$summary['branch']->name} ({$summary['branch']->code})");
        $this->line("Templates preparados: " . count($summary['templates']));
        $this->line("Coberturas preparadas: " . count($summary['coverages']));
        $this->line("Vínculos cfg_template_coverage sincronizados: {$summary['linked']}");
        $this->line("Objetos MR Habitação saneados: {$summary['cleaned_properties']}");

        return self::SUCCESS;
    }

    private function upsertBranch(string $branchCode): CFG_insurance_branches
    {
        $branch = CFG_insurance_branches::withTrashed()
            ->where('code', $branchCode)
            ->first();

        if (!$branch) {
            throw new \RuntimeException("Ramo com code {$branchCode} não encontrado.");
        }

        if ($branch->trashed()) {
            $branch->restore();
        }

        $branch->name = 'MULTIRISCOS HABITAÇÃO';
        $branch->description = 'Ramo preparado automaticamente para cotação de Multirriscos Habitação';
        $branch->requires_coordinates = false;
        $branch->default_validity_days = 30;
        $branch->policy_number_prefix = 'MH';
        $branch->active = true;
        $branch->save();

        return $branch->fresh();
    }

    /**
     * @return int[]
     */
    private function parseTemplateCodes(string $codes): array
    {
        $items = array_filter(array_map('trim', explode(',', $codes)), fn ($v) => $v !== '');
        $parsed = [];
        foreach ($items as $item) {
            if (is_numeric($item)) {
                $parsed[] = (int) $item;
            }
        }
        return array_values(array_unique($parsed));
    }

    /**
     * @param int[] $templateCodes
     * @return CFG_templates[]
     */
    private function upsertTemplates(int $branchId, array $templateCodes): array
    {
        $defaults = [
            1 => ['name' => 'PLANO BASE', 'description' => 'MULTIRISCOS HABITAÇÃO - PLANO BASE', 'package' => 'BASE'],
            2 => ['name' => 'PLANO COMPLETO', 'description' => 'MULTIRISCOS HABITAÇÃO - PLANO COMPLETO', 'package' => 'COMPLETO'],
        ];

        $result = [];
        foreach ($templateCodes as $code) {
            $seed = $defaults[$code] ?? [
                'name' => "MR HAB {$code}",
                'description' => "MULTIRISCOS HABITAÇÃO - TEMPLATE {$code}",
                'package' => null,
            ];

            $template = CFG_templates::withTrashed()
                ->where('insurance_branch_id', $branchId)
                ->where('code', $code)
                ->first();

            if (!$template) {
                $template = new CFG_templates();
                $template->insurance_branch_id = $branchId;
                $template->code = $code;
                $template->slug = 'tpl-mrh-' . $branchId . '-' . $code . '-' . Str::slug($seed['name']);
            }

            $template->name = $seed['name'];
            $template->description = $seed['description'];
            $template->package = $seed['package'];
            $template->object_type = 'PROPERTY';
            $template->qty_objects = null;
            $template->active = true;
            $template->save();

            if ($template->trashed()) {
                $template->restore();
            }

            $result[] = $template->fresh();
        }

        return $result;
    }

    /**
     * @return array<string, CFG_coverages>
     */
    private function upsertCoverages(int $branchId): array
    {
        $definitions = [
            ['code' => 'base-ctd', 'seq' => 1, 'gis_cod' => 6200, 'short_name' => 'BASE CONTEÚDO', 'name' => 'COBERTURA BASE CONTEÚDO', 'obj_type' => 'BUILDING_CONTENT', 'group_code' => null, 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'base-edf', 'seq' => 2, 'gis_cod' => 6250, 'short_name' => 'BASE EDIFÍCIO', 'name' => 'COBERTURA BASE EDIFÍCIO', 'obj_type' => 'BUILDING', 'group_code' => null, 'cov_perc' => 1.00, 'type_covt' => 'O'],

            ['code' => 'edf-incendio', 'seq' => 10, 'gis_cod' => 6210, 'short_name' => 'Edf-Incêndio', 'name' => 'Edf-Incêndio Queda de Raio e Explosão', 'obj_type' => 'BUILDING', 'group_code' => 'base-edf', 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'edf-tempestades', 'seq' => 20, 'gis_cod' => 6211, 'short_name' => 'Edf-Tempestades', 'name' => 'Edf-Tempestades', 'obj_type' => 'BUILDING', 'group_code' => 'base-edf', 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'edf-inundacoes', 'seq' => 30, 'gis_cod' => 6212, 'short_name' => 'Edf-Inundações', 'name' => 'Edf-Inundações', 'obj_type' => 'BUILDING', 'group_code' => 'base-edf', 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'edf-demolicao', 'seq' => 40, 'gis_cod' => 6213, 'short_name' => 'Edf-Demolição', 'name' => 'Edf-Demolição e Remoção de Escombros', 'obj_type' => 'BUILDING', 'group_code' => 'base-edf', 'cov_perc' => 0.05, 'type_covt' => 'O'],
            ['code' => 'edf-aeronaves', 'seq' => 50, 'gis_cod' => 6214, 'short_name' => 'Edf-Queda Aeronaves', 'name' => 'Edf-Queda de Aeronaves', 'obj_type' => 'BUILDING', 'group_code' => 'base-edf', 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'edf-choque', 'seq' => 60, 'gis_cod' => 6215, 'short_name' => 'Edf-Choque', 'name' => 'Edf-Choque Impacto Veículos Terrestres', 'obj_type' => 'BUILDING', 'group_code' => 'base-edf', 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'edf-vidros', 'seq' => 70, 'gis_cod' => 6216, 'short_name' => 'Edf-Quebra Vidros', 'name' => 'Edf-Quebra Vidros Espelhos e Pedras Mármores', 'obj_type' => 'BUILDING', 'group_code' => 'base-edf', 'cov_perc' => 0.015, 'type_covt' => 'O'],
            ['code' => 'edf-rc', 'seq' => 80, 'gis_cod' => 6217, 'short_name' => 'Edf-Resp Civil', 'name' => 'Edf-Responsabilidade Civil', 'obj_type' => 'BUILDING', 'group_code' => 'base-edf', 'cov_perc' => 0.05, 'type_covt' => 'O'],
            ['code' => 'edf-sismicos', 'seq' => 90, 'gis_cod' => 6218, 'short_name' => 'Edf-Fenómenos Sísmicos', 'name' => 'Edf-Fenómenos Sísmicos', 'obj_type' => 'BUILDING', 'group_code' => 'base-edf', 'cov_perc' => 1.00, 'type_covt' => 'O'],

            ['code' => 'ctd-incendio', 'seq' => 100, 'gis_cod' => 6220, 'short_name' => 'Ctd-Incêndio', 'name' => 'Ctd-Incêndio, Queda de Raio e Explosão', 'obj_type' => 'BUILDING_CONTENT', 'group_code' => 'base-ctd', 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'ctd-tempestades', 'seq' => 110, 'gis_cod' => 6221, 'short_name' => 'Ctd-Tempestades', 'name' => 'Ctd-Tempestades', 'obj_type' => 'BUILDING_CONTENT', 'group_code' => 'base-ctd', 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'ctd-inundacoes', 'seq' => 120, 'gis_cod' => 6222, 'short_name' => 'Ctd-Inundações', 'name' => 'Ctd-Inundações', 'obj_type' => 'BUILDING_CONTENT', 'group_code' => 'base-ctd', 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'ctd-aeronaves', 'seq' => 130, 'gis_cod' => 6223, 'short_name' => 'Ctd-Queda Aeronaves', 'name' => 'Ctd-Queda de Aeronaves', 'obj_type' => 'BUILDING_CONTENT', 'group_code' => 'base-ctd', 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'ctd-vidros', 'seq' => 140, 'gis_cod' => 6224, 'short_name' => 'Ctd-Quebra Vidros', 'name' => 'Ctd-Quebra de Vidros, Espelhos, Pedras Mármores', 'obj_type' => 'BUILDING_CONTENT', 'group_code' => 'base-ctd', 'cov_perc' => 1.00, 'type_covt' => 'O'],
            ['code' => 'ctd-rc', 'seq' => 150, 'gis_cod' => 6225, 'short_name' => 'Ctd-Resp Civil', 'name' => 'Ctd-Resp. Civil Proprietário/Inquilino/Ocupante', 'obj_type' => 'BUILDING_CONTENT', 'group_code' => 'base-ctd', 'cov_perc' => 0.015, 'type_covt' => 'O'],
            ['code' => 'ctd-sismicos', 'seq' => 160, 'gis_cod' => 6226, 'short_name' => 'Ctd-Fenómenos Sísmicos', 'name' => 'Ctd-Fenómenos Sísmicos', 'obj_type' => 'BUILDING_CONTENT', 'group_code' => 'base-ctd', 'cov_perc' => 0.05, 'type_covt' => 'O'],
            ['code' => 'danos-agua', 'seq' => 170, 'gis_cod' => 6227, 'short_name' => 'Danos por Água', 'name' => 'Danos por Água', 'obj_type' => 'BUILDING_CONTENT', 'group_code' => 'base-ctd', 'cov_perc' => 0.10, 'type_covt' => 'O'],
            ['code' => 'aluimento-terras', 'seq' => 180, 'gis_cod' => 6228, 'short_name' => 'Aluimento de Terras', 'name' => 'Aluimento de Terras', 'obj_type' => 'BUILDING', 'group_code' => 'base-edf', 'cov_perc' => 2.00, 'type_covt' => 'O'],
        ];

        $coverages = [];
        $parentCodes = ['base-ctd', 'base-edf'];

        foreach ($definitions as $def) {
            $coverage = CFG_coverages::withTrashed()
                ->where('insurance_branch_id', $branchId)
                ->where('gis_cod', $def['gis_cod'])
                ->first();

            if (!$coverage) {
                $coverage = new CFG_coverages();
                $coverage->insurance_branch_id = $branchId;
                $coverage->gis_cod = $def['gis_cod'];
                $coverage->slug = 'cov-mrh-' . $branchId . '-' . $def['gis_cod'] . '-' . Str::slug($def['short_name']);
            }

            $coverage->group_id = null;
            $coverage->seq = $def['seq'];
            $coverage->type_cov = '1';
            $coverage->short_name = $def['short_name'];
            $coverage->name = $def['name'];
            $coverage->description = $def['name'];
            $coverage->obj_type = $def['obj_type'];
            $coverage->capital_value = null;
            $coverage->capital_origin = null;
            $coverage->rate_type = 'PERCENTAGE';
            $coverage->risk_rate = null;
            $coverage->risk_rate_origin = null;
            $coverage->cov_opt = in_array($def['code'], $parentCodes, true) ? 'O' : null;
            $coverage->type_covt = $def['type_covt'];
            $coverage->cov_perc = $def['cov_perc'];
            $coverage->active = true;
            $coverage->save();

            if ($coverage->trashed()) {
                $coverage->restore();
            }

            $coverages[$def['code']] = $coverage->fresh();
        }

        foreach ($definitions as $def) {
            if (empty($def['group_code'])) {
                continue;
            }

            $child = $coverages[$def['code']] ?? null;
            $parent = $coverages[$def['group_code']] ?? null;
            if (!$child || !$parent) {
                continue;
            }

            if ((int) $child->group_id !== (int) $parent->id) {
                $child->group_id = $parent->id;
                $child->save();
                $coverages[$def['code']] = $child->fresh();
            }
        }

        return $coverages;
    }

    /**
     * @param CFG_templates[] $templates
     * @param array<string, CFG_coverages> $coverages
     */
    private function syncTemplateCoverage(array $templates, array $coverages): int
    {
        $codesBase = [
            'base-edf',
            'edf-incendio',
            'edf-tempestades',
            'edf-inundacoes',
            'edf-demolicao',
            'edf-aeronaves',
            'edf-choque',
            'edf-vidros',
            'edf-rc',
            'edf-sismicos',
            'base-ctd',
            'aluimento-terras',
        ];

        $codesCompleto = [
            ...$codesBase,
            'ctd-incendio',
            'ctd-tempestades',
            'ctd-inundacoes',
            'ctd-aeronaves',
            'ctd-vidros',
            'ctd-rc',
            'ctd-sismicos',
            'danos-agua',
        ];

        $updated = 0;
        foreach ($templates as $template) {
            CFG_template_coverage::where('template_id', $template->id)->delete();

            $codesToUse = ((int) $template->code === 1) ? $codesBase : $codesCompleto;
            $displayOrder = 1;
            foreach ($codesToUse as $code) {
                if (!isset($coverages[$code])) {
                    continue;
                }

                CFG_template_coverage::create([
                    'template_id' => $template->id,
                    'coverage_id' => $coverages[$code]->id,
                    'is_mandatory' => true,
                    'display_order' => $displayOrder++,
                ]);
                $updated++;
            }
        }

        return $updated;
    }

    private function resetBranchConfiguration(int $branchId): void
    {
        $templateIds = CFG_templates::withTrashed()
            ->where('insurance_branch_id', $branchId)
            ->pluck('id');
        if ($templateIds->isNotEmpty()) {
            CFG_template_coverage::whereIn('template_id', $templateIds)->delete();
        }

        CFG_templates::withTrashed()->where('insurance_branch_id', $branchId)->forceDelete();
        CFG_coverages::withTrashed()->where('insurance_branch_id', $branchId)->forceDelete();
    }

    private function sanitizeMrhPropertyFields(int $branchId): int
    {
        $objectIds = DB::table('quo_object_risk as qor')
            ->join('quo_quotation_versions as qv', 'qv.id', '=', 'qor.quotation_version_id')
            ->join('quo_quotations as qq', 'qq.id', '=', 'qv.quotation_id')
            ->where('qq.insurance_branch_id', $branchId)
            ->whereNotNull('qor.object_id')
            ->distinct()
            ->pluck('qor.object_id');

        if ($objectIds->isEmpty()) {
            return 0;
        }

        return DB::table('obj_properties')
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
}
