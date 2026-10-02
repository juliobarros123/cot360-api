<?php

namespace Database\Seeders;

use App\Models\InsuranceBranch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            ['description' => 'ACID.TRABALHO-PRÉMIO FIXO', 'name' => 'AT FIXO', 'type_obj' => 'PERSON', 'code' => '1', 'branch' => '10', 'active' => true],
            ['description' => 'ACID.TRABALHO-PRÉMIO VARIÁVEL', 'name' => 'AT VARIA', 'type_obj' => 'SALARY', 'code' => '2', 'branch' => '10', 'active' => true],
            ['description' => 'AT SERVIÇOS DOMÉSTICOS', 'name' => 'DOMESTIC', 'code' => '3', 'branch' => '10', 'active' => true],
            ['description' => 'AC TRABALHO/TRAB. C/PROPRIA', 'name' => 'CPROPRIA', 'code' => '1', 'branch' => '11', 'active' => true],
            ['description' => 'AP GRP.PROF/EXTRA PROFISSIONAL', 'name' => 'IND P/EX', 'code' => '1', 'branch' => '14', 'active' => true],
            ['description' => 'ACIDENTES PESSOAIS ESCOLAR', 'name' => 'AP ESCOL', 'code' => '2', 'branch' => '14', 'active' => true],
            ['description' => 'AP IND.PROF/EXTRA PROFISSIONAL', 'name' => 'IND P/EX', 'code' => '1', 'branch' => '15', 'active' => true],
            ['description' => 'AP INDIVIDUAL', 'name' => 'AP INDV', 'code' => '2', 'branch' => '15', 'active' => true],
            ['description' => 'ASSISTENCIA EM VIAGEM', 'name' => 'ASS VIAG', 'code' => '1', 'branch' => '16', 'active' => true],
            ['description' => 'ASSIST. EM VIAGEM - ESTUDANTES', 'name' => 'AV ESTUD', 'code' => '2', 'branch' => '16', 'active' => true],
            ['description' => 'ASSIST. EM VIAGEM - ESTUDANTES', 'name' => 'AV ESTUD', 'code' => '3', 'branch' => '16', 'active' => true],
            ['description' => 'ASSISTENCIA EM VIAGEM PLUS', 'name' => 'AV PLUS', 'code' => '4', 'branch' => '16', 'active' => true],
            ['description' => 'ASSISTENCIA EM VIAGEM USD', 'name' => 'ASS VIAG', 'code' => '11', 'branch' => '16', 'active' => true],
            ['description' => 'SAÚDE GRUPO', 'name' => 'SAÚDE GR', 'code' => '1', 'branch' => '18', 'active' => true],
            ['description' => 'SAÚDE FAMILIA SEGURA', 'name' => 'SAÚDE FS', 'code' => '2', 'branch' => '18', 'active' => true],
            ['description' => 'SAÚDE INDIVIDUAL', 'name' => 'SAÚDE IN', 'code' => '1', 'branch' => '19', 'active' => true],
            ['description' => 'SAÚDE GRUPO - AKZ', 'name' => 'SGRP AKZ', 'code' => '1', 'branch' => '20', 'active' => true],
            ['description' => 'SAÚDE GRUPO - USD', 'name' => 'SGRP USD', 'code' => '11', 'branch' => '20', 'active' => true],
            ['description' => 'MULTIRISCO HABITAÇÃO VINCULADO', 'name' => 'MRH VINC', 'code' => '1', 'branch' => '32', 'active' => true],
            ['description' => 'MULTIRISCOS HABITAÇÃO', 'name' => 'MR HAB', 'code' => '2', 'branch' => '32', 'active' => true],
            ['description' => 'MULTIRISCO HABITAÇÃO VINCULADO', 'name' => 'MRH VINC', 'code' => '11', 'branch' => '32', 'active' => true],
            ['description' => 'MULTIRISCOS HABITAÇÃO', 'name' => 'MR HAB', 'code' => '12', 'branch' => '32', 'active' => true],
            ['description' => 'MULTIRISCOS HABITAÇÃO', 'name' => 'MR HAB', 'code' => '13', 'branch' => '32', 'active' => true],
            ['description' => 'MULTI-RISCOS EMPRESA - AKZ', 'name' => 'MRE AKZ', 'code' => '1', 'branch' => '34', 'active' => true],
            ['description' => 'MRISCOS COMÉRCIO E SERVIÇOS', 'name' => 'MRCS AKZ', 'code' => '2', 'branch' => '34', 'active' => true],
            ['description' => 'MULTI-RISCOS EMPRESA - AKZ', 'name' => 'MRE AKZ', 'code' => '3', 'branch' => '34', 'active' => true],
            ['description' => 'MULTI-RISCOS EMPRESA - USD', 'name' => 'MRE USD', 'code' => '11', 'branch' => '34', 'active' => true],
            ['description' => 'MRE - DESACTIVADO', 'name' => 'MRE USD', 'code' => '13', 'branch' => '34', 'active' => false],
            ['description' => 'PETROQUÍMICA AKZ', 'name' => 'PETRQAKZ', 'code' => '1', 'branch' => '37', 'active' => true],
            ['description' => 'PETROQUÍMICA USD', 'name' => 'PETRQUSD', 'code' => '11', 'branch' => '37', 'active' => true],
            ['description' => 'MINEIRO AKZ', 'name' => 'MINEIRO', 'code' => '1', 'branch' => '38', 'active' => true],
            ['description' => 'MINEIRO USD', 'name' => 'MINEIRO', 'code' => '11', 'branch' => '38', 'active' => true],
            ['description' => 'MÁQUINAS-CASCOS AKZ', 'name' => 'M-C AKZ', 'code' => '1', 'branch' => '40', 'active' => true],
            ['description' => 'MÁQUINAS-CASCOS USD', 'name' => 'M-C USD', 'code' => '11', 'branch' => '40', 'active' => true],
            ['description' => 'RESP.CIVIL-EXPLORAÇÃO+PRODUTOS', 'name' => 'RC EXP+P', 'code' => '1', 'branch' => '50', 'active' => true],
            ['description' => 'RESP.CIVIL-EXPLORAÇÃO', 'name' => 'RC EXP+P', 'code' => '2', 'branch' => '50', 'active' => true],
            ['description' => 'RESP.CIVIL-PRODUTOS', 'name' => 'RC PROD.', 'code' => '3', 'branch' => '50', 'active' => true],
            ['description' => 'RESP.CIVIL-GERAL', 'name' => 'RC GERAL', 'code' => '4', 'branch' => '50', 'active' => true],
            ['description' => 'RESP.CIVIL-EXPLORAÇÃO+PRODUTOS', 'name' => 'RC EXP+P', 'code' => '11', 'branch' => '50', 'active' => true],
            ['description' => 'RESP.CIVIL-EXPLORAÇÃO', 'name' => 'RC EXP', 'code' => '12', 'branch' => '50', 'active' => true],
            ['description' => 'RESP.CIVIL-PRODUTOS', 'name' => 'RC PROD.', 'code' => '13', 'branch' => '50', 'active' => true],
            ['description' => 'RESP.CIVIL-GERAL', 'name' => 'RC GERAL', 'code' => '14', 'branch' => '50', 'active' => true],
            ['description' => 'CONSTRUÇÃO E MONTAGEM AKZ', 'name' => 'C&M AKZ', 'code' => '1', 'branch' => '60', 'active' => true],
            ['description' => 'CONSTRUÇÃO E MONTAGEM USD', 'name' => 'C&M USD', 'code' => '11', 'branch' => '60', 'active' => true],
            ['description' => 'MERCADORIAS TRANSPORTADAS AKZ', 'name' => 'MERC.TR. AKZ', 'code' => '1', 'branch' => '80', 'active' => true],
            ['description' => 'MERCADORIAS TRANSPORTADAS USD', 'name' => 'MERC.TR. USD', 'code' => '11', 'branch' => '80', 'active' => true],
            ['description' => 'MARÍTIMO CASCO AKZ', 'name' => 'MARCASCO AKZ', 'code' => '1', 'branch' => '85', 'active' => true],
            ['description' => 'MARÍTIMO CASCO USD', 'name' => 'MARCASCO USD', 'code' => '11', 'branch' => '85', 'active' => true],
            ['description' => 'EMBARCAÇÕES DE RECREIO AKZ', 'name' => 'EMB.RECR AKZ', 'code' => '1', 'branch' => '86', 'active' => true],
            ['description' => 'EMBARCAÇÕES DE RECREIO USD', 'name' => 'EMB.RECR USD', 'code' => '11', 'branch' => '86', 'active' => true],
            ['description' => 'AVIAÇÂO AKZ', 'name' => 'AVIAÇÃO AKZ', 'code' => '1', 'branch' => '89', 'active' => true],
            ['description' => 'AVIAÇÃO USD', 'name' => 'AVIAÇÃO USD', 'code' => '11', 'branch' => '89', 'active' => true],
            ['description' => 'AUTO VINCULADO', 'name' => 'AU VINC', 'code' => '1', 'branch' => '90', 'active' => true],
            ['description' => 'AUTO NÃO VINCULADO', 'name' => 'AU NVINC', 'code' => '2', 'branch' => '90', 'active' => true],
            ['description' => 'AUTO', 'name' => 'AUTO', 'code' => '3', 'branch' => '90', 'active' => true],
            ['description' => 'AUTO COLABORADORES', 'name' => 'AU COLAB', 'code' => '4', 'branch' => '90', 'active' => true],
            ['description' => 'FROTA', 'name' => 'FROTA', 'code' => '1', 'branch' => '91', 'active' => true],
            ['description' => 'FROTA', 'name' => 'FROTA M', 'code' => '2', 'branch' => '91', 'active' => true],
         
        ];

        foreach ($templates as $template) {
            $ramo = InsuranceBranch::where('code', $template['branch'])->first();

            if ($ramo) {
                Template::create([
                    'description' => $template['description'],
                    'name' => $template['name'],
                    'code' => $template['code'],
                    'package' => isset($template['package']) ? $template['package'] : null,
                    'type_obj' => isset($template['type_obj']) ? $template['type_obj'] : null,
                    'insurance_branche_id' => $ramo->id,
                    'active' => $template['active'],
                ]);
            }

        }
    }
}