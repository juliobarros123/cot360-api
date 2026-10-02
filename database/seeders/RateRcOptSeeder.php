<?php

namespace Database\Seeders;

use App\Models\CFG\CFG_rates_rc_options;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RateRcOptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rates = [
            ['rc_base' => 13376000, 'rc_fac' => 13376000, 'perc_rate' => 0],
            // Single step increases
            ['rc_base' => 6688000, 'rc_fac' => 13376000, 'perc_rate' => 30],
            ['rc_base' => 13376000, 'rc_fac' => 26752000, 'perc_rate' => 25],
            ['rc_base' => 26752000, 'rc_fac' => 40128000, 'perc_rate' => 15],
            ['rc_base' => 40128000, 'rc_fac' => 80256000, 'perc_rate' => 15],

            // Two-step increases
            ['rc_base' => 6688000, 'rc_fac' => 26752000, 'perc_rate' => 55],
            ['rc_base' => 13376000, 'rc_fac' => 40128000, 'perc_rate' => 40],
            ['rc_base' => 26752000, 'rc_fac' => 80256000, 'perc_rate' => 30],

            // Three-step increases
            ['rc_base' => 6688000, 'rc_fac' => 40128000, 'perc_rate' => 70],
            ['rc_base' => 13376000, 'rc_fac' => 80256000, 'perc_rate' => 55],

            // Four-step increase
            ['rc_base' => 6688000, 'rc_fac' => 80256000, 'perc_rate' => 85],

            // To 125,000,000 Kz
            ['rc_base' => 6688000, 'rc_fac' => 125000000, 'perc_rate' => 90],
            ['rc_base' => 13376000, 'rc_fac' => 125000000, 'perc_rate' => 70],
            ['rc_base' => 26752000, 'rc_fac' => 125000000, 'perc_rate' => 45],
            ['rc_base' => 40128000, 'rc_fac' => 125000000, 'perc_rate' => 30],
            ['rc_base' => 80256000, 'rc_fac' => 125000000, 'perc_rate' => 15],

            // To 250,000,000 Kz
            ['rc_base' => 6688000, 'rc_fac' => 250000000, 'perc_rate' => 105],
            ['rc_base' => 13376000, 'rc_fac' => 250000000, 'perc_rate' => 85],
            ['rc_base' => 26752000, 'rc_fac' => 250000000, 'perc_rate' => 60],
            ['rc_base' => 40128000, 'rc_fac' => 250000000, 'perc_rate' => 45],
            ['rc_base' => 80256000, 'rc_fac' => 250000000, 'perc_rate' => 30],

            // To 500,000,000 Kz
            ['rc_base' => 6688000, 'rc_fac' => 500000000, 'perc_rate' => 115],
            ['rc_base' => 13376000, 'rc_fac' => 500000000, 'perc_rate' => 95],
            ['rc_base' => 26752000, 'rc_fac' => 500000000, 'perc_rate' => 70],
            ['rc_base' => 40128000, 'rc_fac' => 500000000, 'perc_rate' => 55],
            ['rc_base' => 80256000, 'rc_fac' => 500000000, 'perc_rate' => 40],

            // To 975,000,000 Kz
            ['rc_base' => 6688000, 'rc_fac' => 975000000, 'perc_rate' => 130],
            ['rc_base' => 13376000, 'rc_fac' => 975000000, 'perc_rate' => 110],
            ['rc_base' => 26752000, 'rc_fac' => 975000000, 'perc_rate' => 85],
            ['rc_base' => 40128000, 'rc_fac' => 975000000, 'perc_rate' => 70],
            ['rc_base' => 80256000, 'rc_fac' => 975000000, 'perc_rate' => 55],
        ];

        foreach ($rates as $rate) {
            CFG_rates_rc_options::updateOrCreate(
                [
                    'rc_base' => $rate['rc_base'],
                    'rc_fac' => $rate['rc_fac']
                ],
                [
                    'perc_rate' => $rate['perc_rate']
                ]
            );
        }
    }
}