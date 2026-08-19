<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            DepartamentoSeeder::class,
            MunicipioSeeder::class,
        ]);

        User::updateOrCreate(
            ['correo' => 'test@gmail.com'],
            [
                'nombre' => 'Test',
                'apellido' => 'User',
                'password' => bcrypt('12345678'),
                'estado' => 'ACTIVO',
            ]
        )->syncRoles(['ADMINISTRADOR']);

        $this->call([
            DatosPruebaSeeder::class,
        ]);
    }
}
