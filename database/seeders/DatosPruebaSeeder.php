<?php

namespace Database\Seeders;

use App\Enums\EstadoPropietarioEnum;
use App\Enums\TipoPropietarioEnum;
use App\Enums\UsuarioEstadoEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $this->crearUsuariosPrueba();
        $categorias = $this->crearCategorias();
        $modelos = $this->crearMarcasYModelos();
        $propietarios = $this->crearPropietarios();
        $this->crearClientes();
        $this->crearVehiculos($categorias, $modelos, $propietarios);
    }

    private function crearUsuariosPrueba(): void
    {
        $usuarios = [
            [
                'nombre' => 'Empleado',
                'apellido' => 'Prueba',
                'correo' => 'empleado@test.com',
                'rol' => 'EMPLEADO',
            ],
            [
                'nombre' => 'Contador',
                'apellido' => 'Prueba',
                'correo' => 'contador@test.com',
                'rol' => 'CONTADOR',
            ],
        ];

        foreach ($usuarios as $usuario) {
            $modelo = User::updateOrCreate(
                ['correo' => $usuario['correo']],
                [
                    'nombre' => $usuario['nombre'],
                    'apellido' => $usuario['apellido'],
                    'password' => Hash::make('12345678'),
                    'estado' => UsuarioEstadoEnum::ACTIVO->value,
                ]
            );

            $modelo->syncRoles([$usuario['rol']]);
        }
    }

    private function crearCategorias()
    {
        $categorias = [
            ['nombre' => 'Compacto', 'precio_dia' => 12.00],
            ['nombre' => 'Sedan', 'precio_dia' => 15.00],
            ['nombre' => 'SUV', 'precio_dia' => 25.00],
            ['nombre' => 'Pickup', 'precio_dia' => 30.00],
        ];

        return collect($categorias)->map(fn($categoria) => Categoria::updateOrCreate(
            ['nombre' => $categoria['nombre']],
            ['precio_dia' => $categoria['precio_dia']]
        ));
    }

    private function crearMarcasYModelos()
    {
        $marcas = [
            'Toyota' => ['Corolla', 'Hilux'],
            'Kia' => ['Picanto', 'Sportage'],
            'Hyundai' => ['Accent', 'Tucson'],
            'Nissan' => ['Versa'],
            'Suzuki' => ['Swift'],
        ];

        $modelos = collect();

        foreach ($marcas as $nombreMarca => $nombresModelos) {
            $marca = Marca::firstOrCreate(['nombre' => $nombreMarca]);

            foreach ($nombresModelos as $nombreModelo) {
                $modelos->push(Modelo::updateOrCreate(
                    ['nombre' => $nombreModelo, 'marca_id' => $marca->id],
                    ['nombre' => $nombreModelo, 'marca_id' => $marca->id]
                ));
            }
        }

        return $modelos;
    }

    private function crearPropietarios()
    {
        $dueno = Propietario::where('tipo_propietario', TipoPropietarioEnum::PROPIO->value)->first();

        if ($dueno) {
            $dueno->update([
                'nombre' => 'RentaCar El Guayabo',
                'telefono' => '+503 7000-0001',
                'estado' => EstadoPropietarioEnum::ACTIVO->value,
            ]);
        } else {
            $dueno = Propietario::create([
                'nombre' => 'RentaCar El Guayabo',
                'telefono' => '+503 7000-0001',
                'tipo_propietario' => TipoPropietarioEnum::PROPIO->value,
                'estado' => EstadoPropietarioEnum::ACTIVO->value,
            ]);
        }

        $otrosPropietarios = [
            ['nombre' => 'Mario Martinez', 'telefono' => '+503 7000-0002', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Carlos Hernandez', 'telefono' => '+503 7000-0003', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Ana Lopez', 'telefono' => '+503 7000-0004', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Jose Ramirez', 'telefono' => '+503 7000-0005', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Miguel Guardado', 'telefono' => '+503 7000-0006', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Roberto Cruz', 'telefono' => '+503 7000-0007', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Sofia Menjivar', 'telefono' => '+503 7000-0008', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Daniel Morales', 'telefono' => '+503 7000-0009', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Patricia Flores', 'telefono' => '+503 7000-0010', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Fernando Castro', 'telefono' => '+503 7000-0011', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Luis Arteaga', 'telefono' => '+503 7000-0012', 'tipo_propietario' => TipoPropietarioEnum::FAMILIAR->value],
            ['nombre' => 'Marta Portillo', 'telefono' => '+503 7000-0013', 'tipo_propietario' => TipoPropietarioEnum::FAMILIAR->value],
            ['nombre' => 'Raul Pena', 'telefono' => '+503 7000-0014', 'tipo_propietario' => TipoPropietarioEnum::FAMILIAR->value],
            ['nombre' => 'Elena Gomez', 'telefono' => '+503 7000-0015', 'tipo_propietario' => TipoPropietarioEnum::FAMILIAR->value],
        ];

        $propietarios = collect([$dueno]);

        foreach ($otrosPropietarios as $propietario) {
            $propietarios->push(Propietario::updateOrCreate(
                ['telefono' => $propietario['telefono']],
                [
                    'nombre' => $propietario['nombre'],
                    'tipo_propietario' => $propietario['tipo_propietario'],
                    'estado' => EstadoPropietarioEnum::ACTIVO->value,
                ]
            ));
        }

        return $propietarios;
    }
    private function crearClientes(): void
    {
        $municipios = Municipio::pluck('id')->values();

        if ($municipios->isEmpty()) {
            return;
        }

        $clientes = [
            'Juan Carlos Rivera Morales',
            'Andrea Sofia Martinez Lopez',
            'Carlos Ernesto Hernandez Ruiz',
            'Maria Fernanda Garcia Perez',
            'Luis Alberto Ramirez Cruz',
            'Rosa Emilia Flores Mejia',
            'Miguel Angel Guardado Soto',
            'Gabriela Beatriz Morales Diaz',
            'Oscar Eduardo Castro Nunez',
            'Claudia Patricia Menjivar Rivas',
            'Daniel Alejandro Pena Gomez',
            'Karla Vanessa Ortiz Campos',
            'Roberto Antonio Lopez Aguilar',
            'Ana Carolina Reyes Molina',
            'Jorge Mauricio Escobar Funes',
            'Veronica Elizabeth Chicas Romero',
            'Francisco Javier Alvarado Lemus',
            'Natalia Isabel Ventura Arias',
            'Ricardo Ernesto Pineda Solis',
            'Carmen Elena Melendez Torres',
        ];

        foreach ($clientes as $index => $nombre) {
            $numero = $index + 1;
            Cliente::updateOrCreate(
                ['dui' => sprintf('045000%02d-%d', $numero, $numero % 10)],
                [
                    'nombre' => $nombre,
                    'vencimiento_dui' => now()->addYears(1 + ($index % 5))->toDateString(),
                    'numero_licencia' => sprintf('%08d', 10000000 + $numero),
                    'vencimiento_licencia' => now()->addYears(2 + ($index % 4))->toDateString(),
                    'telefono' => sprintf('+503 72%02d-%04d', $index + 10, 1000 + $index),
                    'municipio_id' => $municipios[$index % $municipios->count()],
                ]
            );
        }
    }

    private function crearVehiculos($categorias, $modelos, $propietarios): void
    {
        $colores = ['Blanco', 'Negro', 'Gris', 'Azul', 'Rojo', 'Plata', 'Verde', 'Cafe'];
        $capacidades = [4, 5, 5, 7, 5, 4, 5, 5, 4, 5, 7, 5, 5, 4, 5, 5, 7, 5, 4, 5];

        for ($i = 0; $i < 20; $i++) {
            $modelo = $modelos[$i % $modelos->count()];
            $categoria = $categorias[$i % $categorias->count()];
            $propietario = $propietarios[$i % $propietarios->count()];

            Vehiculo::updateOrCreate(
                ['placa' => sprintf('P%06d', 240001 + $i)],
                [
                    'color' => $colores[$i % count($colores)],
                    'anio' => 2016 + ($i % 9),
                    'capacidad_pasajeros' => $capacidades[$i],
                    'estado' => VehiculoEstadoEnum::DISPONIBLE->value,
                    'observaciones' => 'Vehiculo de prueba disponible para flujos del sistema.',
                    'modelo_id' => $modelo->id,
                    'categoria_id' => $categoria->id,
                    'propietario_id' => $propietario->id,
                ]
            );
        }
    }
}

