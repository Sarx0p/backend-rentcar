<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        ]);

        User::factory()->create([
           'nombre'=> 'Test',
           'apellido'=> 'User',
           'correo'=> 'test@gmail.com',
           'password'=> bcrypt('12345678'),
           'estado'=> 'ACTIVO',
        ]);
    }
}
