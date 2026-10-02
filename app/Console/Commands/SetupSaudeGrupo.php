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

class SetupSaudeGrupo extends Command
{
    protected $signature = 'app:setup-saude-grupo
                            {--branch-code=20 : Código do ramo alvo}
                            {--branch-name=SAÚDE GRUPO : Nome do ramo alvo}';

    protected $description = 'Prepara de ponta a ponta o ramo Saúde Grupo (ramo, templates, coberturas, vínculos e tarifas) sem redundâncias';

    public function handle(): int
    {
        $branchCode = trim((string) $this->option('branch-code'));
        $branchName = trim((string) $this->option('branch-name'));

        if ($branchCode === '') {
            $this->error('O código do ramo é obrigatório.');
            return self::FAILURE;
        }

        try {
            $branch = DB::transaction(function () use ($branchCode, $branchName) {
                $branch = $this->upsertBranch($branchCode, $branchName);
                $this->resetBranchConfiguration($branch->id);

                $templates = $this->seedTemplates($branch->id);
                $coverages = $this->seedCoverages($branch->id);
                $this->seedTemplateCoverage($templates, $coverages);

                return $branch;
            });
        } catch (Throwable $e) {
            $this->error('Falha ao preparar configuração base de Saúde Grupo: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Ramo {$branch->name} ({$branch->code}) preparado. Carregando tarifas...");

        $exit = $this->call('app:insert-rate-saude-grupo', [
            '--branch-code' => $branch->code,
            '--branch-name' => $branch->name,
            '--reset' => true,
        ]);

        if ($exit !== self::SUCCESS) {
            $this->error('As tarifas não foram carregadas.');
            return $exit;
        }

        $this->newLine();
        $this->info('SAÚDE GRUPO pronto para uso.');
        $this->line('Resumo:');
        $this->line('- ramo code 20 ativo');
        $this->line('- 4 templates alinhados com os packages das tarifas');
        $this->line('- 4 coberturas base configuradas');
        $this->line('- vínculos template/cobertura recriados');
        $this->line('- tarifas de saúde grupo recarregadas sem duplicação');

        return self::SUCCESS;
    }

    private function upsertBranch(string $branchCode, string $branchName): CFG_insurance_branches
    {
        $branch = CFG_insurance_branches::withTrashed()
            ->where('code', $branchCode)
            ->first();

        if (!$branch) {
            $branch = new CFG_insurance_branches();
            $branch->code = $branchCode;
            $branch->slug = 'branch-saude-grupo-' . $branchCode;
        }

        $branch->name = $branchName;
        $branch->description = 'Ramo preparado automaticamente para cotação de Saúde Grupo';
        $branch->requires_coordinates = false;
        $branch->default_validity_days = 30;
        $branch->policy_number_prefix = 'SG';
        $branch->active = true;
        $branch->save();

        if ($branch->trashed()) {
            $branch->restore();
        }

        return $branch->fresh();
    }

    private function resetBranchConfiguration(int $branchId): void
    {
        $templateIds = CFG_templates::withTrashed()
            ->where('insurance_branch_id', $branchId)
            ->pluck('id');

        if ($templateIds->isNotEmpty()) {
            CFG_template_coverage::whereIn('template_id', $templateIds)->delete();
        }

        DB::table('cfg_rates_health')->where('insurance_branch_id', $branchId)->delete();
        CFG_templates::withTrashed()->where('insurance_branch_id', $branchId)->forceDelete();
        CFG_coverages::withTrashed()->where('insurance_branch_id', $branchId)->forceDelete();
    }

    /**
     * @return array<int, CFG_templates>
     */
    private function seedTemplates(int $branchId): array
    {
        $rows = [
            [
                'code' => 1,
                'name' => 'BIC Pleno',
                'description' => 'SAÚDE GRUPO - BIC Pleno',
                'package' => 'BIC Pleno',
            ],
            [
                'code' => 2,
                'name' => 'BIC Extra',
                'description' => 'SAÚDE GRUPO - BIC Extra',
                'package' => 'BIC Extra',
            ],
            [
                'code' => 3,
                'name' => 'BIC Total',
                'description' => 'SAÚDE GRUPO - BIC Total',
                'package' => 'BIC Total',
            ],
            [
                'code' => 4,
                'name' => 'Bic Integral',
                'description' => 'SAÚDE GRUPO - Bic Integral',
                'package' => 'Bic Integral',
            ],
        ];

        $templates = [];

        foreach ($rows as $row) {
            $template = CFG_templates::create([
                'insurance_branch_id' => $branchId,
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => $row['description'],
                'package' => $row['package'],
                'object_type' => 'Person',
                'qty_objects' => null,
                'active' => true,
                'slug' => 'tpl-saude-grupo-' . $branchId . '-' . $row['code'] . '-' . Str::slug($row['name']),
            ]);

            $templates[$row['code']] = $template;
        }

        return $templates;
    }

    /**
     * @return array<string, CFG_coverages>
     */
    private function seedCoverages(int $branchId): array
    {
        $rows = [
            'base' => [
                'seq' => 1,
                'gis_cod' => 1,
                'type_cov' => '1',
                'short_name' => 'BASE',
                'name' => 'COBERTURA BASE',
                'description' => 'GRUPOBASE',
            ],
            'spouse' => [
                'seq' => 2,
                'gis_cod' => 2,
                'type_cov' => '1',
                'short_name' => 'BASE CONJUGU',
                'name' => 'COBERTURA BASE CONJUGUE',
                'description' => 'GRUPOBASE',
            ],
            'dependents' => [
                'seq' => 3,
                'gis_cod' => 3,
                'type_cov' => '1',
                'short_name' => 'BASE DEPENDE',
                'name' => 'COBERTURA BASE DEPENDENTES',
                'description' => 'GRUPOBASE',
            ],
            'employees' => [
                'seq' => 4,
                'gis_cod' => 4,
                'type_cov' => '1',
                'short_name' => 'BASE EMPREGA',
                'name' => 'COBERTURA BASE EMPREGADOS',
                'description' => 'GRUPOBASE',
            ],
        ];

        $coverages = [];

        foreach ($rows as $key => $row) {
            $coverage = CFG_coverages::create([
                'insurance_branch_id' => $branchId,
                'group_id' => null,
                'seq' => $row['seq'],
                'gis_cod' => $row['gis_cod'],
                'type_cov' => $row['type_cov'],
                'short_name' => $row['short_name'],
                'name' => $row['name'],
                'description' => $row['description'],
                'obj_type' => 'PERSON',
                'capital_value' => null,
                'capital_origin' => null,
                'rate_type' => 'FIXED',
                'risk_rate' => null,
                'risk_rate_origin' => 'cfg_rates_health.premium_value',
                'cov_opt' => $key === 'base' ? 'O' : null,
                'type_covt' => $key === 'base' ? 'O' : null,
                'cov_perc' => 0,
                'active' => true,
                'slug' => 'cov-saude-grupo-' . $branchId . '-' . $row['gis_cod'] . '-' . Str::slug($row['short_name']),
            ]);

            $coverages[$key] = $coverage;
        }

        return $coverages;
    }

    /**
     * @param array<int, CFG_templates> $templates
     * @param array<string, CFG_coverages> $coverages
     */
    private function seedTemplateCoverage(array $templates, array $coverages): void
    {
        foreach ($templates as $template) {
            $displayOrder = 1;

            foreach ($coverages as $key => $coverage) {
                CFG_template_coverage::create([
                    'template_id' => $template->id,
                    'coverage_id' => $coverage->id,
                    'is_mandatory' => $key === 'base',
                    'display_order' => $displayOrder++,
                ]);
            }
        }
    }
}
