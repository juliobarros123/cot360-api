<?php

namespace App\Console\Commands;

use App\Models\CFG\CFG_insurance_branches as InsuranceBranch;
use App\Models\CFG\CFG_rates_health as RatesHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Migra as tarifas de SAÚDE FAMÍLIA do legado (mysql_old.rates_healths)
 * para o novo schema (cfg_rates_health).
 *
 * - Lê do legado em vez de hard-codear (não temos Excel oficial p/ Família)
 * - Auto-detecta se a coluna `currency` existe no legado
 * - Mapeia `coverage_id` legado -> novo (cfg_coverages)
 * - Idempotente: pode correr múltiplas vezes sem duplicar
 *
 * Sistema aplica margem e fator de família dinamicamente em runtime.
 */
class InsertRateSaudeFamilia extends Command
{
    protected $signature = 'app:insert-rate-saude-familia
                            {--branch-code=18 : Código do ramo alvo (Saúde Família)}
                            {--branch-name=SAÚDE FAMÍLIA : Nome do ramo alvo}
                            {--old-branch-id= : Override do ID legado (opcional)}
                            {--currency= : Filtrar moeda específica (AOA ou USD)}
                            {--default-currency=AOA : Moeda usada quando o legado não tiver coluna currency}
                            {--dry-run : Simula sem gravar}
                            {--reset : Remove tarifas existentes do ramo antes de inserir}';

    protected $description = 'Migra tarifas Prémio Risco de SAÚDE FAMÍLIA do legado para cfg_rates_health.';

    private string $OLD = 'mysql_old';
    private string $NEW = 'mysql';

    /** @var array<int,int> map old coverages.id => new cfg_coverages.id */
    private array $coverageMap = [];
    private bool $legacyHasCurrency = false;

    public function handle(): int
    {
        $branchCode = trim((string) $this->option('branch-code'));
        $branchName = trim((string) $this->option('branch-name'));
        $currencyFilter = strtoupper(trim((string) $this->option('currency')));
        $defaultCurrency = strtoupper(trim((string) $this->option('default-currency'))) ?: 'AOA';
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN ativo (sem gravação).');
        }

        // ── 1. Conexão ao legado ──────────────────────────────────────────
        try {
            DB::connection($this->OLD)->getPdo();
        } catch (Throwable $e) {
            $this->error("Falha na conexão {$this->OLD}: " . $e->getMessage());
            return self::FAILURE;
        }

        // ── 2. Auto-detectar coluna currency no legado ────────────────────
        $this->legacyHasCurrency = Schema::connection($this->OLD)
            ->hasColumn('rates_healths', 'currency');

        if (!$this->legacyHasCurrency) {
            $this->warn("Legado NÃO tem coluna 'currency' em rates_healths.");
            $this->warn("Todos os registos vão usar default-currency={$defaultCurrency}.");
            $this->warn("Se o BIC tem tarifas USD em produção, vais querer importá-las separadamente.");
        }

        // ── 3. Resolver ramo alvo (novo) ──────────────────────────────────
        $branch = InsuranceBranch::query()
            ->when($branchCode !== '', fn($q) => $q->where('code', $branchCode))
            ->first();

        if (!$branch && $branchName !== '') {
            $branch = InsuranceBranch::firstWhere('name', $branchName);
        }

        if (!$branch) {
            $this->error("Ramo SAÚDE FAMÍLIA não encontrado em cfg_insurance_branches (code={$branchCode}).");
            return self::FAILURE;
        }

        // ── 4. Resolver ramo legado ───────────────────────────────────────
        $oldBranchIdOpt = $this->option('old-branch-id');
        $oldBranchId = !empty($oldBranchIdOpt)
            ? (int) $oldBranchIdOpt
            : DB::connection($this->OLD)
                ->table('insurance_branches')
                ->where('code', $branchCode)
                ->orderBy('id')
                ->value('id');

        if (!$oldBranchId) {
            $this->error("Ramo legado não encontrado para code={$branchCode}.");
            return self::FAILURE;
        }

        $this->line(sprintf(
            'Ramo alvo: legado old_id=%d  =>  novo id=%d (code=%s, %s)',
            $oldBranchId,
            $branch->id,
            $branch->code,
            $branch->name
        ));

        // ── 5. Carregar coverageMap (legado -> novo) ──────────────────────
        $this->loadCoverageMap($oldBranchId, (int) $branch->id);
        $this->line('Coverages mapeadas: ' . count($this->coverageMap));

        // ── 6. Reset (opcional) ───────────────────────────────────────────
        if ($this->option('reset')) {
            $query = RatesHealth::where('insurance_branch_id', $branch->id);
            if ($currencyFilter !== '') {
                $query->where('currency', $currencyFilter);
            }
            $deleted = $dryRun ? $query->count() : $query->withTrashed()->forceDelete();
            $this->info(($dryRun ? '[dry] ' : '') . "{$deleted} tarifas antigas removidas.");
        }

        // ── 7. Carregar tarifas do legado ─────────────────────────────────
        $this->info('Lendo tarifas do legado...');

        $columns = [
            'id', 'coverage_id', 'age_min', 'age_max',
            'gender', 'type', 'package', 'premium_value',
            'active', 'created_at', 'updated_at',
        ];
        if ($this->legacyHasCurrency) {
            $columns[] = 'currency';
        }

        $legacyQuery = DB::connection($this->OLD)
            ->table('rates_healths')
            ->where('insurance_branch_id', $oldBranchId)
            ->whereNull('deleted_at')
            ->select($columns);

        if ($currencyFilter !== '' && $this->legacyHasCurrency) {
            $legacyQuery->where('currency', $currencyFilter);
        }

        $legacyRates = $legacyQuery->get();

        if ($legacyRates->isEmpty()) {
            $this->warn("Nenhuma tarifa encontrada no legado (old_branch_id={$oldBranchId}).");
            return self::SUCCESS;
        }

        $this->info("Encontradas {$legacyRates->count()} tarifas no legado.");

        // ── 8. Resumo prévio (contagem por moeda + pacote) ────────────────
        $summary = $legacyRates->groupBy(function ($r) use ($defaultCurrency) {
            return $this->resolveCurrency($r, $defaultCurrency);
        })->map(function ($g) {
            return [
                'total' => $g->count(),
                'packages' => $g->groupBy('package')->map->count()->toArray(),
            ];
        });

        foreach ($summary as $currency => $info) {
            $this->line("  {$currency}: {$info['total']} tarifas");
            foreach ($info['packages'] as $pkg => $count) {
                $this->line("    • {$pkg}: {$count}");
            }
        }

        // ── 9. Inserir no novo schema ─────────────────────────────────────
        $this->info('Inserindo no schema novo...');

        $inserted = 0;
        $skipped = 0;
        $coverageMissing = 0;

        foreach ($legacyRates as $rate) {
            $currency = $this->resolveCurrency($rate, $defaultCurrency);

            // Mapear coverage_id legado -> novo (mantém null se legado for null)
            $newCoverageId = null;
            if (!empty($rate->coverage_id)) {
                $newCoverageId = $this->coverageMap[(int) $rate->coverage_id] ?? null;
                if (!$newCoverageId) {
                    $coverageMissing++;
                    // Não saltamos — inserimos com coverage_id null, mas avisamos
                }
            }

            // Idempotência: combinação que torna a tarifa única
            $exists = RatesHealth::where('insurance_branch_id', $branch->id)
                ->where('age_min', $rate->age_min)
                ->where('age_max', $rate->age_max)
                ->where('gender', $rate->gender)
                ->where('type', $rate->type)
                ->where('package', $rate->package)
                ->where('currency', $currency)
                ->when($newCoverageId, fn($q) => $q->where('coverage_id', $newCoverageId))
                ->when(!$newCoverageId, fn($q) => $q->whereNull('coverage_id'))
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $payload = [
                'insurance_branch_id' => $branch->id,
                'coverage_id' => $newCoverageId,
                'age_min' => (int) $rate->age_min,
                'age_max' => (int) $rate->age_max,
                'gender' => $rate->gender,
                'type' => $rate->type,
                'package' => $rate->package,
                'premium_value' => $rate->premium_value,
                'currency' => $currency,
                'active' => (bool) ($rate->active ?? true),
                'slug' => $this->buildRateSlug($branch->id, [
                    'currency' => $currency,
                    'package' => $rate->package,
                    'type' => $rate->type,
                    'gender' => $rate->gender,
                    'age_min' => $rate->age_min,
                    'age_max' => $rate->age_max,
                    'coverage_id' => $newCoverageId,
                ]),
            ];

            if (!$dryRun) {
                RatesHealth::create($payload);
            }

            $inserted++;
        }

        $this->info(sprintf(
            '✅ Concluído. Inseridos=%d | Já existiam=%d | Coverage não-mapeada=%d',
            $inserted,
            $skipped,
            $coverageMissing
        ));

        if ($coverageMissing > 0) {
            $this->warn("⚠️  {$coverageMissing} tarifas tinham coverage_id no legado que não foi mapeada para o novo schema.");
            $this->warn('   Foram inseridas com coverage_id=NULL. Corre `repair:saude-familia-branch-migration` antes para garantir que as cfg_coverages estão completas.');
        }

        $this->line('Lembrete: o sistema aplica em runtime: Tarifa × Margem × Fator (regras Saúde Família).');

        return self::SUCCESS;
    }

    /**
     * Carrega o mapa de coverage_id legado -> novo, igual ao RepairSaudeFamiliaBranchMigration.
     */
    private function loadCoverageMap(int $oldBranchId, int $newBranchId): void
    {
        $this->coverageMap = [];

        DB::connection($this->OLD)
            ->table('coverages')
            ->where('insurance_branch_id', $oldBranchId)
            ->select('id', 'seq', 'short_name', 'name', 'gis_cod', 'type_cov')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($newBranchId) {
                foreach ($rows as $oldCov) {
                    $newId = $this->resolveNewCoverageId((array) $oldCov, $newBranchId);
                    if ($newId) {
                        $this->coverageMap[(int) $oldCov->id] = $newId;
                    }
                }
            });
    }

    private function resolveNewCoverageId(array $oldCoverage, int $newBranchId): ?int
    {
        $seq = $oldCoverage['seq'] ?? null;
        $shortName = $oldCoverage['short_name'] ?? null;
        $name = $oldCoverage['name'] ?? null;
        $gisCod = $oldCoverage['gis_cod'] ?? null;
        $typeCov = $oldCoverage['type_cov'] ?? null;

        $q = DB::connection($this->NEW)
            ->table('cfg_coverages')
            ->where('insurance_branch_id', $newBranchId)
            ->where('seq', $seq);
        if (!empty($shortName)) $q->where('short_name', $shortName);
        if (!empty($typeCov))   $q->where('type_cov', $typeCov);
        if (!empty($gisCod))    $q->where('gis_cod', $gisCod);

        $id = $q->value('id');
        if ($id) return (int) $id;

        // Fallback por short_name
        if (!empty($shortName)) {
            $id = DB::connection($this->NEW)
                ->table('cfg_coverages')
                ->where('insurance_branch_id', $newBranchId)
                ->where('short_name', $shortName)
                ->orderBy('id')
                ->value('id');
            if ($id) return (int) $id;
        }

        // Fallback por name
        if (!empty($name)) {
            $id = DB::connection($this->NEW)
                ->table('cfg_coverages')
                ->where('insurance_branch_id', $newBranchId)
                ->where('name', $name)
                ->orderBy('id')
                ->value('id');
            if ($id) return (int) $id;
        }

        return null;
    }

    private function resolveCurrency(object $rate, string $default): string
    {
        if ($this->legacyHasCurrency && !empty($rate->currency)) {
            return strtoupper((string) $rate->currency);
        }
        return $default;
    }

    private function buildRateSlug(int $branchId, array $rate): string
    {
        $parts = [
            'rate-health-sf',
            $branchId,
            $rate['currency'] ?? 'AOA',
            $rate['package'] ?? '',
            $rate['type'] ?? '',
            $rate['gender'] ?? '',
            $rate['age_min'] ?? '',
            $rate['age_max'] ?? '',
        ];
        if (!empty($rate['coverage_id'])) {
            $parts[] = 'cov' . $rate['coverage_id'];
        }

        return collect($parts)
            ->filter(fn($v) => $v !== null && $v !== '')
            ->map(fn($v) => str((string) $v)->slug('-')->value())
            ->implode('-');
    }
}