<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MunicipioSeeder extends Seeder
{
    public function run(): void
    {
        $municipio = [
            // Ahuachapán (id: 1)
            ['nombre' => 'Ahuachapán Norte', 'departamento_id' => 1],
            ['nombre' => 'Ahuachapán Centro', 'departamento_id' => 1],
            ['nombre' => 'Ahuachapán Sur', 'departamento_id' => 1],

            // Santa Ana (id: 2)
            ['nombre' => 'Santa Ana Norte', 'departamento_id' => 2],
            ['nombre' => 'Santa Ana Centro', 'departamento_id' => 2],
            ['nombre' => 'Santa Ana Este', 'departamento_id' => 2],
            ['nombre' => 'Santa Ana Oeste', 'departamento_id' => 2],

            // Sonsonate (id: 3)
            ['nombre' => 'Sonsonate Norte', 'departamento_id' => 3],
            ['nombre' => 'Sonsonate Centro', 'departamento_id' => 3],
            ['nombre' => 'Sonsonate Este', 'departamento_id' => 3],
            ['nombre' => 'Sonsonate Oeste', 'departamento_id' => 3],

            // Chalatenango (id: 4)
            ['nombre' => 'Chalatenango Norte', 'departamento_id' => 4],
            ['nombre' => 'Chalatenango Centro', 'departamento_id' => 4],
            ['nombre' => 'Chalatenango Sur', 'departamento_id' => 4],

            // La Libertad (id: 5)
            ['nombre' => 'La Libertad Norte', 'departamento_id' => 5],
            ['nombre' => 'La Libertad Centro', 'departamento_id' => 5],
            ['nombre' => 'La Libertad Oeste', 'departamento_id' => 5],
            ['nombre' => 'La Libertad Este', 'departamento_id' => 5],
            ['nombre' => 'La Libertad Sur', 'departamento_id' => 5],

            // San Salvador (id: 6)
            ['nombre' => 'San Salvador Norte', 'departamento_id' => 6],
            ['nombre' => 'San Salvador Oeste', 'departamento_id' => 6],
            ['nombre' => 'San Salvador Este', 'departamento_id' => 6],
            ['nombre' => 'San Salvador Centro', 'departamento_id' => 6],
            ['nombre' => 'San Salvador Sur', 'departamento_id' => 6],

            // Cuscatlán (id: 7)
            ['nombre' => 'Cuscatlán Norte', 'departamento_id' => 7],
            ['nombre' => 'Cuscatlán Sur', 'departamento_id' => 7],

            // La Paz (id: 8)
            ['nombre' => 'La Paz Oeste', 'departamento_id' => 8],
            ['nombre' => 'La Paz Centro', 'departamento_id' => 8],
            ['nombre' => 'La Paz Este', 'departamento_id' => 8],

            // Cabañas (id: 9)
            ['nombre' => 'Cabañas Este', 'departamento_id' => 9],
            ['nombre' => 'Cabañas Oeste', 'departamento_id' => 9],

            // San Vicente (id: 10)
            ['nombre' => 'San Vicente Norte', 'departamento_id' => 10],
            ['nombre' => 'San Vicente Sur', 'departamento_id' => 10],

            // Usulután (id: 11)
            ['nombre' => 'Usulután Norte', 'departamento_id' => 11],
            ['nombre' => 'Usulután Este', 'departamento_id' => 11],
            ['nombre' => 'Usulután Oeste', 'departamento_id' => 11],

            // San Miguel (id: 12)
            ['nombre' => 'San Miguel Norte', 'departamento_id' => 12],
            ['nombre' => 'San Miguel Centro', 'departamento_id' => 12],
            ['nombre' => 'San Miguel Oeste', 'departamento_id' => 12],

            // Morazán (id: 13)
            ['nombre' => 'Morazán Norte', 'departamento_id' => 13],
            ['nombre' => 'Morazán Sur', 'departamento_id' => 13],

            // La Unión (id: 14)
            ['nombre' => 'La Unión Norte', 'departamento_id' => 14],
            ['nombre' => 'La Unión Sur', 'departamento_id' => 14],
        ];

        DB::table('municipios')->insert($municipio);
    }
}
