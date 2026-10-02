<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Coverage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReplicateCoverages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coverages:replicate 
                            {ids : IDs dos registros a replicar (separados por vírgula)}
                            {--fields= : Campos a alterar no formato campo1:valor1,campo2:valor2}
                            {--dry-run : Mostrar preview sem salvar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replica registros da tabela coverages permitindo alterar campos específicos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ids = explode(',', $this->argument('ids'));
        $fieldsToChange = $this->parseFields($this->option('fields'));
        $dryRun = $this->option('dry-run');

        $this->info("Buscando registros com IDs: " . implode(', ', $ids));

        $coverages = Coverage::whereIn('id', $ids)->get();

        if ($coverages->isEmpty()) {
            $this->error('Nenhum registro encontrado com os IDs informados.');
            return 1;
        }

        $this->info("Encontrados {$coverages->count()} registros para replicar.");

        $replicatedCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($coverages as $original) {
                $replica = $original->replicate();

                // Aplicar alterações nos campos especificados
                foreach ($fieldsToChange as $field => $value) {
                    if ($field === 'slug') {
                        // Garantir que o slug seja único
                        $replica->$field = $this->generateUniqueSlug($value ?: $original->slug);
                    } else {
                        $replica->$field = $value;
                    }
                }

                // Se não foi especificado um slug, gerar um único
                if (!isset($fieldsToChange['slug'])) {
                    $replica->slug = $this->generateUniqueSlug($original->slug);
                }

                if ($dryRun) {
                    $this->info("[PREVIEW] Replicando registro ID: {$original->id}");
                    $this->table(
                        ['Campo', 'Valor Original', 'Valor Replicado'],
                        $this->getComparisonData($original, $replica)
                    );
                } else {
                    if ($replica->save()) {
                        $this->info("✅ Registro ID: {$original->id} replicado com sucesso. Novo ID: {$replica->id}");
                        $replicatedCount++;
                    } else {
                        $errors[] = "Falha ao replicar registro ID: {$original->id}";
                        $this->error("❌ Falha ao replicar registro ID: {$original->id}");
                    }
                }
            }

            if ($dryRun) {
                $this->info("✅ Preview concluído. Use sem --dry-run para salvar as alterações.");
                DB::rollBack();
            } else {
                DB::commit();
                $this->info("✅ Replicação concluída! {$replicatedCount} registros replicados com sucesso.");
                
                if (!empty($errors)) {
                    $this->error("❌ Erros encontrados:");
                    foreach ($errors as $error) {
                        $this->error($error);
                    }
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erro durante a replicação: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Parse dos campos fornecidos via opção
     */
    private function parseFields(?string $fieldsString): array
    {
        if (empty($fieldsString)) {
            return [];
        }

        $fields = [];
        $pairs = explode(',', $fieldsString);

        foreach ($pairs as $pair) {
            $parts = explode(':', $pair, 2);
            if (count($parts) === 2) {
                $field = trim($parts[0]);
                $value = trim($parts[1]);
                
                // Converter valores booleanos
                if ($value === 'true') $value = true;
                if ($value === 'false') $value = false;
                if (is_numeric($value)) $value = (float) $value;
                
                $fields[$field] = $value;
            }
        }

        return $fields;
    }

    /**
     * Gerar slug único
     */
    private function generateUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug . '-copy';
        $counter = 1;

        while (Coverage::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-copy-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Preparar dados para comparação no preview
     */
    private function getComparisonData(Coverage $original, Coverage $replica): array
    {
        $data = [];
        $allFields = array_merge(
            $original->getFillable(),
            ['id', 'created_at', 'updated_at', 'deleted_at']
        );

        foreach ($allFields as $field) {
            if ($field !== 'id') {
                $data[] = [
                    'field' => $field,
                    'original' => $original->$field ?? 'null',
                    'replica' => $replica->$field ?? 'null'
                ];
            }
        }

        return $data;
    }
}