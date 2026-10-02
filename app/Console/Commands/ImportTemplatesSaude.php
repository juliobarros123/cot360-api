<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTemplatesSaude extends Command
{
    protected $signature = 'import:templates-saude {--remove : Remove os templates de saúde}';
    protected $description = 'Importa ou remove templates de saúde';

    public function handle()
    {
        if ($this->option('remove')) {
            return $this->removeTemplates();
        }

        return $this->importTemplates();
    }

    private function importTemplates()
    {
        $templates = [
            [
                'description' => 'BIC Top',
                'name' => 'BIC Top',
                'package' => 'BIC Top',
                'object_type' => 'PERSON',
                'qty_objects' => null,
                'code' => 1,
                'active' => 1,
                'slug' => 'saude-plus',
                'insurance_branch_id' => 14,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'description' => 'BIC Plus',
                'name' => 'BIC Plus',
                'package' => 'BIC Plus',
                'object_type' => 'PERSON',
                'qty_objects' => null,
                'code' => 2,
                'active' => 1,
                'slug' => 'saude-top',
                'insurance_branch_id' => 14,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'description' => 'Bic Mega',
                'name' => 'Bic Mega',
                'package' => 'Bic Mega',
                'object_type' => 'PERSON',
                'qty_objects' => null,
                'code' => 3,
                'active' => 1,
                'slug' => 'saude-mega',
                'insurance_branch_id' => 14,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $total = 0;
        $exists = 0;

        foreach ($templates as $template) {
            // Verificar se já existe
            $existsRecord = DB::table('cfg_templates')
                ->where('slug', $template['slug'])
                ->exists();

            if ($existsRecord) {
                $this->info("Template {$template['name']} já existe (slug: {$template['slug']})");
                $exists++;
                continue;
            }

            try {
                DB::table('cfg_templates')->insert($template);
                $this->info("Template {$template['name']} inserido com sucesso");
                $total++;
            } catch (\Exception $e) {
                $this->error("Erro ao inserir template {$template['name']}: " . $e->getMessage());
            }
        }

        $this->info("Importação concluída: {$total} novos templates inseridos, {$exists} já existiam");
    }

    private function removeTemplates()
    {
        $slugs = ['saude-plus', 'saude-top', 'saude-mega','RzswOmr1542252025061668503b6165d4fl1ZW7zL','SGEhqDc1542252025061668503b616570fbEwDFWU'];

        $deleted = DB::table('cfg_templates')
            ->whereIn('slug', $slugs)
            ->delete();

        $this->info("{$deleted} templates de saúde removidos com sucesso");
    }
}
