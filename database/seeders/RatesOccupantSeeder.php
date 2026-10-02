<?php

namespace Database\Seeders;

use App\Models\RatesOccupant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RatesOccupantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ratesOccupants = [
            [
                'seat_min' => '1',
                'seat_max' => '6',
                'package' => 'Min',
                'mip' => '500000',    // Primeiro valor numérico após o package
                'dt' => '100000',
                'df' => '100000',
                'premium' => 4254
            ],
            [
                'seat_min' => '7',
                'seat_max' => '9',
                'package' => 'Min',
                'mip' => '500000',
                'dt' => '100000',
                'df' => '100000',
                'premium' => 6381
            ],
            [
                'seat_min' => '1',
                'seat_max' => '6',
                'package' => 'Med',
                'mip' => '1000000',
                'dt' => '200000',
                'df' => '200000',
                'premium' => 8508
            ],
            [
                'seat_min' => '7',
                'seat_max' => '9',
                'package' => 'Med',
                'mip' => '1000000',
                'dt' => '200000',
                'df' => '200000',
                'premium' => 12762
            ],
            [
                'seat_min' => '1',
                'seat_max' => '6',
                'package' => 'Max',
                'mip' => '5500000',
                'dt' => '500000',
                'df' => '500000',
                'premium' => 17725
            ],
            [
                'seat_min' => '7',
                'seat_max' => '9',
                'package' => 'Max',
                'mip' => '5500000',
                'dt' => '500000',
                'df' => '500000',
                'premium' => 24815
            ]
        ];
        
        foreach ($ratesOccupants as $ratesOccupant) {
            RatesOccupant::create($ratesOccupant);
        }
    }
}
