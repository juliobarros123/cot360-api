<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceBranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'id' => 1,
                'name' => 'AT CONTA OUTREM',
                'code' => '10',
                'description' => 'Acidentes de Trabalho',
                'active' => 1,
                'slug' => 'X6d6Yxa1542242025061668503b608e753yRcK9sY',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-07-01 12:13:14',
            ],
            [
                'id' => 2,
                'name' => 'ACIDENTES PESSOAIS GRUPO',
                'code' => '14',
                'description' => null,
                'active' => 1,
                'slug' => 'WA0bPDq1542242025061668503b608f2bah8OJV5p',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
            [
                'id' => 3,
                'name' => 'ACIDENTES PESSOAIS',
                'code' => '15',
                'description' => null,
                'active' => 1,
                'slug' => 'Jz5vEQj1542242025061668503b608f7fdIvqNvRS',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
            [
                'id' => 4,
                'name' => 'ASSISTENCIA EM VIAGEM',
                'code' => '16',
                'description' => null,
                'active' => 1,
                'slug' => 'RoGDbFQ1542242025061668503b608fd05C7bRAzz',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
            [
                'id' => 5,
                'name' => 'MULTIRISCOS HABITAÇÃO',
                'code' => '32',
                'description' => null,
                'active' => 1,
                'slug' => '9Qx5XQM1542242025061668503b60901f4V31RKGT',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
            [
                'id' => 6,
                'name' => 'MULTIRRISCOS EMPRESAS',
                'code' => '34',
                'description' => 'MULTIRRISCOS EMPRESA',
                'active' => 1,
                'slug' => 'PzAWEZQ1542242025061668503b60906bd55DLKtm',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-12-30 08:28:06',
            ],
            [
                'id' => 7,
                'name' => 'MÁQUINAS-CASCOS',
                'code' => '40',
                'description' => null,
                'active' => 1,
                'slug' => 'Utzb28J1542242025061668503b6090beff9wJJLB',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
            [
                'id' => 8,
                'name' => 'RESPONSABILIDADE CIVIL',
                'code' => '50',
                'description' => null,
                'active' => 1,
                'slug' => '8UuI4Px1542242025061668503b609125e0secbWQ',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
            [
                'id' => 9,
                'name' => 'CONSTRUÇÃO E MONTAGEM',
                'code' => '60',
                'description' => null,
                'active' => 1,
                'slug' => 'OVY1qML1542242025061668503b60917181gwIx1W',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
            [
                'id' => 10,
                'name' => 'MERC TRANSPORTADAS',
                'code' => '80',
                'description' => null,
                'active' => 1,
                'slug' => 'TRhvhx41542242025061668503b6091bbcz5RZ1Di',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
            [
                'id' => 11,
                'name' => 'MARÍTIMO/RECREIO',
                'code' => '86',
                'description' => null,
                'active' => 1,
                'slug' => 'AJ7D0B01542242025061668503b6092057UU8qrSS',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
            [
                'id' => 12,
                'name' => 'AUTOMÓVEL',
                'code' => '90',
                'description' => null,
                'active' => 1,
                'slug' => 'H1I3Mkx1542242025061668503b60924d2wdWKv9v',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
            [
                'id' => 13,
                'name' => 'Automóvel Frota',
                'code' => '91',
                'description' => 'Automóvel Frota',
                'active' => 1,
                'slug' => 'OmR2DYz1542242025061668503b6092948fWas4Dp',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-08-12 14:06:28',
            ],
            [
                'id' => 14,
                'name' => 'SAÚDE FAMÍLIA',
                'code' => '18',
                'description' => null,
                'active' => 1,
                'slug' => 'YT4ZMU21542242025061668503b6092d84DrqOIjh',
                'deleted_at' => null,
                'created_at' => '2025-06-16 14:42:24',
                'updated_at' => '2025-06-16 14:42:24',
            ],
        ];

        foreach ($branches as $branch) {
            $exists = DB::table('cfg_insurance_branches')
                ->where('code', $branch['code'])
                ->exists();

            if (! $exists) {
                DB::table('cfg_insurance_branches')->insert($branch);
            }
        }
    }
}
