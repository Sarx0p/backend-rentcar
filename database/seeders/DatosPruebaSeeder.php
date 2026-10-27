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
                'nombre' => 'Karen',
                'apellido' => 'Hernández',
                'correo' => 'empleado@test.com',
                'rol' => 'EMPLEADO',
            ],
            [
                'nombre' => 'Julio',
                'apellido' => 'Mejía',
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
            ['nombre' => 'Compacto', 'precio_dia' => 28.00, 'capacidad_maxima' => 5],
            ['nombre' => 'Sedan', 'precio_dia' => 35.00, 'capacidad_maxima' => 5],
            ['nombre' => 'SUV', 'precio_dia' => 55.00, 'capacidad_maxima' => 8],
            ['nombre' => 'Pickup', 'precio_dia' => 65.00, 'capacidad_maxima' => 5],
            ['nombre' => 'Microbus', 'precio_dia' => 85.00, 'capacidad_maxima' => 15],
        ];

        return collect($categorias)->mapWithKeys(fn($categoria) => [
            $categoria['nombre'] => Categoria::updateOrCreate(
                ['nombre' => $categoria['nombre']],
                [
                    'precio_dia'       => $categoria['precio_dia'],
                    'capacidad_maxima' => $categoria['capacidad_maxima'],
                ]
            ),
        ]);
    }

    private function crearMarcasYModelos()
    {
        $marcas = [
            'Toyota' => ['Yaris', 'Corolla', 'RAV4', 'Hilux', 'Hiace'],
            'Kia' => ['Picanto', 'Soluto', 'Sportage'],
            'Hyundai' => ['Accent', 'Creta', 'Tucson'],
            'Nissan' => ['Versa', 'Kicks', 'Frontier'],
            'Suzuki' => ['Swift'],
            'Mitsubishi' => ['L200', 'Montero Sport'],
            'Mazda' => ['CX-5'],
        ];

        $modelos = collect();

        foreach ($marcas as $nombreMarca => $nombresModelos) {
            $marca = Marca::firstOrCreate(['nombre' => $nombreMarca]);

            foreach ($nombresModelos as $nombreModelo) {
                $modelos->put($nombreModelo, Modelo::updateOrCreate(
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
                'telefono' => '+503 2408-3150',
                'estado' => EstadoPropietarioEnum::ACTIVO->value,
            ]);
        } else {
            $dueno = Propietario::create([
                'nombre' => 'RentaCar El Guayabo',
                'telefono' => '+503 2408-3150',
                'tipo_propietario' => TipoPropietarioEnum::PROPIO->value,
                'estado' => EstadoPropietarioEnum::ACTIVO->value,
            ]);
        }

        $otrosPropietarios = [
            ['nombre' => 'Mario Martínez', 'telefono' => '+503 7845-2190', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Carlos Hernández', 'telefono' => '+503 7612-4478', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Ana López', 'telefono' => '+503 6143-8820', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'José Ramírez', 'telefono' => '+503 7930-1157', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Miguel Guardado', 'telefono' => '+503 7258-6634', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Roberto Cruz', 'telefono' => '+503 6021-7745', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Sofía Menjívar', 'telefono' => '+503 7389-5012', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Daniel Morales', 'telefono' => '+503 7704-9263', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Patricia Flores', 'telefono' => '+503 6287-3409', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Fernando Castro', 'telefono' => '+503 7516-0872', 'tipo_propietario' => TipoPropietarioEnum::TERCERO->value],
            ['nombre' => 'Luis Arteaga', 'telefono' => '+503 7461-2385', 'tipo_propietario' => TipoPropietarioEnum::FAMILIAR->value],
            ['nombre' => 'Marta Portillo', 'telefono' => '+503 6198-4527', 'tipo_propietario' => TipoPropietarioEnum::FAMILIAR->value],
            ['nombre' => 'Raúl Peña', 'telefono' => '+503 7823-6091', 'tipo_propietario' => TipoPropietarioEnum::FAMILIAR->value],
            ['nombre' => 'Elena Gómez', 'telefono' => '+503 7057-3348', 'tipo_propietario' => TipoPropietarioEnum::FAMILIAR->value],
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
            ['nombre' => 'Juan Carlos Rivera Morales', 'dui' => '01082305-2', 'telefono' => '+503 7124-5836', 'dui_meses' => 70, 'licencia_dias' => 1460],
            ['nombre' => 'Andrea Sofía Martínez López', 'dui' => '05560774-2', 'telefono' => '+503 7681-2047', 'dui_meses' => 52, 'licencia_dias' => 12],
            ['nombre' => 'Carlos Ernesto Hernández Ruiz', 'dui' => '04871898-0', 'telefono' => '+503 6035-9172', 'dui_meses' => 88, 'licencia_dias' => 1095],
            ['nombre' => 'María Fernanda García Pérez', 'dui' => '01273336-8', 'telefono' => '+503 7452-8613', 'dui_meses' => 34, 'licencia_dias' => 730],
            ['nombre' => 'Luis Alberto Ramírez Cruz', 'dui' => '01617421-5', 'telefono' => '+503 7903-4458', 'dui_meses' => 61, 'licencia_dias' => 25],
            ['nombre' => 'Rosa Emilia Flores Mejía', 'dui' => '02491917-4', 'telefono' => '+503 6214-7390', 'dui_meses' => 45, 'licencia_dias' => 1825],
            ['nombre' => 'Miguel Ángel Guardado Soto', 'dui' => '04818397-3', 'telefono' => '+503 7336-0285', 'dui_meses' => 79, 'licencia_dias' => 540],
            ['nombre' => 'Gabriela Beatriz Morales Díaz', 'dui' => '01691060-3', 'telefono' => '+503 7568-1904', 'dui_meses' => 23, 'licencia_dias' => 1280],
            ['nombre' => 'Óscar Eduardo Castro Núñez', 'dui' => '01534300-7', 'telefono' => '+503 7819-6527', 'dui_meses' => 95, 'licencia_dias' => 45],
            ['nombre' => 'Claudia Patricia Menjívar Rivas', 'dui' => '01665779-2', 'telefono' => '+503 6472-3159', 'dui_meses' => 57, 'licencia_dias' => 915],
            ['nombre' => 'Daniel Alejandro Peña Gómez', 'dui' => '06497087-7', 'telefono' => '+503 7045-8821', 'dui_meses' => 40, 'licencia_dias' => 1640],
            ['nombre' => 'Karla Vanessa Ortiz Campos', 'dui' => '01856133-7', 'telefono' => '+503 7287-5643', 'dui_meses' => 83, 'licencia_dias' => 8],
            ['nombre' => 'Roberto Antonio López Aguilar', 'dui' => '06009484-9', 'telefono' => '+503 7740-1368', 'dui_meses' => 66, 'licencia_dias' => 1190],
            ['nombre' => 'Ana Carolina Reyes Molina', 'dui' => '04745564-4', 'telefono' => '+503 6359-2786', 'dui_meses' => 29, 'licencia_dias' => 610],
            ['nombre' => 'Jorge Mauricio Escobar Funes', 'dui' => '04786882-1', 'telefono' => '+503 7192-6034', 'dui_meses' => 74, 'licencia_dias' => 1370],
            ['nombre' => 'Verónica Elizabeth Chicas Romero', 'dui' => '06170998-4', 'telefono' => '+503 7625-4917', 'dui_meses' => 48, 'licencia_dias' => 820],
            ['nombre' => 'Francisco Javier Alvarado Lemus', 'dui' => '04665997-8', 'telefono' => '+503 7958-3201', 'dui_meses' => 91, 'licencia_dias' => 1550],
            ['nombre' => 'Natalia Isabel Ventura Arias', 'dui' => '02680923-6', 'telefono' => '+503 6106-8473', 'dui_meses' => 37, 'licencia_dias' => 980],
            ['nombre' => 'Ricardo Ernesto Pineda Solís', 'dui' => '04944928-3', 'telefono' => '+503 7413-9652', 'dui_meses' => 55, 'licencia_dias' => 1700],
            ['nombre' => 'Carmen Elena Meléndez Torres', 'dui' => '01464249-0', 'telefono' => '+503 7586-0749', 'dui_meses' => 68, 'licencia_dias' => 400],
        ];

        foreach ($clientes as $index => $cliente) {
            Cliente::updateOrCreate(
                ['dui' => $cliente['dui']],
                [
                    'nombre' => $cliente['nombre'],
                    'vencimiento_dui' => now()->addMonths($cliente['dui_meses'])->toDateString(),
                    'numero_licencia' => str_replace('-', '', $cliente['dui']),
                    'vencimiento_licencia' => now()->addDays($cliente['licencia_dias'])->toDateString(),
                    'telefono' => $cliente['telefono'],
                    'municipio_id' => $municipios[$index % $municipios->count()],
                ]
            );
        }
    }

    private function crearVehiculos($categorias, $modelos, $propietarios): void
    {
        $vehiculos = [
            ['placa' => 'P512873', 'modelo' => 'Picanto', 'categoria' => 'Compacto', 'anio' => 2024, 'color' => 'Blanco', 'capacidad' => 5],
            ['placa' => 'P608142', 'modelo' => 'Swift', 'categoria' => 'Compacto', 'anio' => 2023, 'color' => 'Rojo', 'capacidad' => 5],
            ['placa' => 'P731905', 'modelo' => 'Yaris', 'categoria' => 'Compacto', 'anio' => 2025, 'color' => 'Plata', 'capacidad' => 5],
            ['placa' => 'P457216', 'modelo' => 'Picanto', 'categoria' => 'Compacto', 'anio' => 2022, 'color' => 'Gris', 'capacidad' => 4],
            ['placa' => 'P820364', 'modelo' => 'Corolla', 'categoria' => 'Sedan', 'anio' => 2024, 'color' => 'Negro', 'capacidad' => 5],
            ['placa' => 'P395027', 'modelo' => 'Accent', 'categoria' => 'Sedan', 'anio' => 2023, 'color' => 'Azul', 'capacidad' => 5],
            ['placa' => 'P674518', 'modelo' => 'Versa', 'categoria' => 'Sedan', 'anio' => 2025, 'color' => 'Blanco', 'capacidad' => 5],
            ['placa' => 'P243790', 'modelo' => 'Soluto', 'categoria' => 'Sedan', 'anio' => 2022, 'color' => 'Gris', 'capacidad' => 5],
            ['placa' => 'P918453', 'modelo' => 'Accent', 'categoria' => 'Sedan', 'anio' => 2026, 'color' => 'Plata', 'capacidad' => 5],
            ['placa' => 'P560281', 'modelo' => 'RAV4', 'categoria' => 'SUV', 'anio' => 2024, 'color' => 'Blanco', 'capacidad' => 5],
            ['placa' => 'P387645', 'modelo' => 'Sportage', 'categoria' => 'SUV', 'anio' => 2025, 'color' => 'Negro', 'capacidad' => 5],
            ['placa' => 'P702938', 'modelo' => 'Tucson', 'categoria' => 'SUV', 'anio' => 2023, 'color' => 'Gris', 'capacidad' => 5],
            ['placa' => 'P149572', 'modelo' => 'Creta', 'categoria' => 'SUV', 'anio' => 2024, 'color' => 'Rojo', 'capacidad' => 5],
            ['placa' => 'P836014', 'modelo' => 'Kicks', 'categoria' => 'SUV', 'anio' => 2025, 'color' => 'Azul', 'capacidad' => 5],
            ['placa' => 'P471386', 'modelo' => 'Montero Sport', 'categoria' => 'SUV', 'anio' => 2022, 'color' => 'Plata', 'capacidad' => 7],
            ['placa' => 'P625809', 'modelo' => 'CX-5', 'categoria' => 'SUV', 'anio' => 2023, 'color' => 'Rojo', 'capacidad' => 5],
            ['placa' => 'P958140', 'modelo' => 'Hilux', 'categoria' => 'Pickup', 'anio' => 2024, 'color' => 'Blanco', 'capacidad' => 5],
            ['placa' => 'P213657', 'modelo' => 'Frontier', 'categoria' => 'Pickup', 'anio' => 2023, 'color' => 'Gris', 'capacidad' => 5],
            ['placa' => 'P789423', 'modelo' => 'L200', 'categoria' => 'Pickup', 'anio' => 2025, 'color' => 'Negro', 'capacidad' => 5],
            ['placa' => 'P346091', 'modelo' => 'Hilux', 'categoria' => 'Pickup', 'anio' => 2021, 'color' => 'Plata', 'capacidad' => 5],
            ['placa' => 'P507264', 'modelo' => 'Hiace', 'categoria' => 'Microbus', 'anio' => 2024, 'color' => 'Blanco', 'capacidad' => 15],
            ['placa' => 'P864735', 'modelo' => 'Hiace', 'categoria' => 'Microbus', 'anio' => 2022, 'color' => 'Plata', 'capacidad' => 12],
        ];

        foreach ($vehiculos as $index => $vehiculo) {
            $categoria = $categorias[$vehiculo['categoria']];
            $propietario = $propietarios[$index % $propietarios->count()];

            Vehiculo::updateOrCreate(
                ['placa' => $vehiculo['placa']],
                [
                    'color' => $vehiculo['color'],
                    'anio' => $vehiculo['anio'],
                    'capacidad_pasajeros' => min($vehiculo['capacidad'], $categoria->capacidad_maxima),
                    'estado' => VehiculoEstadoEnum::DISPONIBLE->value,
                    'observaciones' => 'Unidad en buen estado, lista para renta.',
                    'modelo_id' => $modelos[$vehiculo['modelo']]->id,
                    'categoria_id' => $categoria->id,
                    'propietario_id' => $propietario->id,
                ]
            );
        }
    }
}
