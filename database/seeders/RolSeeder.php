<?php

namespace Database\Seeders;

use App\Enums\RolEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         foreach (RolEnum::cases() as $rol) {
          Role::firstOrCreate([
              'name' => $rol -> value ,
              'guard_name' => 'api'
          ]);
       }
    }
}
