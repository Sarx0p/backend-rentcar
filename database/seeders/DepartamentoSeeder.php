<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departamento = [
            ['id' => 1, 'nombre' => 'Ahuachapán'],
            ['id' => 2, 'nombre' => 'Santa Ana'],
            ['id' => 3, 'nombre' => 'Sonsonate'],
            ['id' => 4, 'nombre' => 'Chalatenango'],
            ['id' => 5, 'nombre' => 'La Libertad'],
            ['id' => 6, 'nombre' => 'San Salvador'],
            ['id' => 7, 'nombre' => 'Cuscatlán'],
            ['id' => 8, 'nombre' => 'La Paz'],
            ['id' => 9, 'nombre' => 'Cabañas'],
            ['id' => 10, 'nombre' => 'San Vicente'],
            ['id' => 11, 'nombre' => 'Usulután'],
            ['id' => 12, 'nombre' => 'San Miguel'],
            ['id' => 13, 'nombre' => 'Morazán'],
            ['id' => 14, 'nombre' => 'La Unión'],
        ];
        DB::table('departamentos')->insert($departamento);
    }
}
