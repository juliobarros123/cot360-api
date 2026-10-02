<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesChannels = [
            [
                'id' => 1,
                'channel_type' => 'Corretores',
                'name' => 'Corretor',
                'description' => 'CORRETORES',
                'responsible' => 'Marisa Goncalves',
                'active' => true,
                'slug' => 'LQEgF601542252025061668503b61ec3564Tmoe0z',
                'deleted_at' => null,
                'created_at' => '2025-06-16 15:42:25',
                'updated_at' => '2025-07-03 11:07:34',
            ],
            [
                'id' => 2,
                'channel_type' => 'Bank_Assurance',
                'name' => 'Bank_Assurance',
                'description' => 'BANK ASSURANCE',
                'responsible' => 'Macano Kiala',
                'active' => true,
                'slug' => '78PaMrH1542252025061668503b61ecbe4kgWAp4O',
                'deleted_at' => null,
                'created_at' => '2025-06-16 15:42:25',
                'updated_at' => '2025-06-16 15:42:25',
            ],
            [
                'id' => 3,
                'channel_type' => 'Directo',
                'name' => 'Directo',
                'description' => 'DIRECTO',
                'responsible' => 'Macano Kiala',
                'active' => true,
                'slug' => 'jxgZOAI1542252025061668503b61ed1bf77ueOjz',
                'deleted_at' => null,
                'created_at' => '2025-06-16 15:42:25',
                'updated_at' => '2025-06-16 15:42:25',
            ],
            [
                'id' => 4,
                'channel_type' => 'Online',
                'name' => 'Online',
                'description' => 'ONLINE',
                'responsible' => 'Macano Kiala',
                'active' => true,
                'slug' => 'yTzdJLp1542252025061668503b61eda669opd4MQ',
                'deleted_at' => null,
                'created_at' => '2025-06-16 15:42:25',
                'updated_at' => '2025-06-16 15:42:25',
            ],
        ];

        DB::table('ent_sales_channels')->upsert(
            $salesChannels,
            ['id'],
            ['channel_type', 'name', 'description', 'responsible', 'active', 'slug', 'deleted_at', 'created_at', 'updated_at']
        );
    }
}
