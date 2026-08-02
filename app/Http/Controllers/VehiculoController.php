<?php

namespace App\Http\Controllers;

use App\Enums\EstadoReservaEnum;
use App\Enums\RolEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Http\Requests\VehiculoController\StoreVehiculoRequest;
use App\Http\Requests\VehiculoController\UpdateVehiculoRequest;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiculoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $vehiculos = Vehiculo::with(['modelo.marca', 'categoria'])
                ->whereNotIn('estado', [
                    VehiculoEstadoEnum::MANTENIMIENTO->value,
                    VehiculoEstadoEnum::FUERA_SERVICIO->value,
                    VehiculoEstadoEnum::RENTADO->value,
                ])
                ->when(
                    $request->filled('fecha_inicio') && $request->filled('fecha_fin'),
                    function ($query) use ($request) {
                        $query->whereDoesntHave('reservas', function ($q) use ($request) {
                            $q->whereNotIn('estado', [EstadoReservaEnum::CANCELADA->value])
                                ->whereDate('fecha_inicio', '<', $request->fecha_fin)
                                ->whereDate('fecha_fin',    '>', $request->fecha_inicio);
                        });
                    }
                )
                ->orderBy('id')
                ->get();

            if ($vehiculos->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No hay vehículos disponibles.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $vehiculos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    public function store(StoreVehiculoRequest $request)
    {
        try {
            DB::beginTransaction();

            $vehiculo = Vehiculo::create([
                'anio'                => $request->anio,
                'color'               => $request->color,
                'placa'               => $request->placa,
                'capacidad_pasajeros' => $request->capacidad_pasajeros,
                'estado'              => $request->estado,
                'observaciones'       => $request->observaciones,
                'propietario_id'      => $request->propietario_id,
                'categoria_id'        => $request->categoria_id,
                'modelo_id'           => $request->modelo_id,
            ]);

            DB::commit();

            $vehiculo->load(['modelo.marca', 'categoria']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Vehiculo registrado correctamente.',
                'data'    => $vehiculo,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $vehiculo = Vehiculo::with(['modelo.marca', 'categoria'])
                ->find($id);

            if (!$vehiculo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Vehiculo no encontrado.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $vehiculo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    // decidi hacer el update por la situcacion qeu aya un trabajo de pintura en vehiuclo poder tener la livertad
    //de hacer la actualizacion en ves de tener que crear el carro
    public function update(UpdateVehiculoRequest $request, string $id)
    {
        try {
            $vehiculo = Vehiculo::find($id);

            if (!$vehiculo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Vehiculo no encontrado.',
                ], 404);
            }

            DB::beginTransaction();

            $vehiculo->update($request->only([
                'anio',
                'color',
                'placa',
                'capacidad_pasajeros',
                'estado',
                'observaciones',
                'propietario_id',
                'categoria_id',
                'modelo_id',
            ]));

            DB::commit();

            $vehiculo->load(['modelo.marca', 'categoria']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Vehiculo actualizado correctamente.',
                'data'    => $vehiculo,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }

            $vehiculo = Vehiculo::find($id);

            if (!$vehiculo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Vehiculo no encontrado.',
                ], 404);
            }

            if ($vehiculo->estado === VehiculoEstadoEnum::FUERA_SERVICIO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vehículo ya se encuentra fuera de servicio.',
                ], 422);
            }

            $tieneReservasActivas = $vehiculo->reservas()
                ->whereNotIn('estado', [EstadoReservaEnum::CANCELADA->value])
                ->exists();

            if ($tieneReservasActivas) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se puede desactivar el vehículo porque tiene reservas activas asociadas.',
                ], 422);
            }

            DB::beginTransaction();

            $vehiculo->update([
                'estado' => VehiculoEstadoEnum::FUERA_SERVICIO->value,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Vehiculo puesto fuera de servicio correctamente.',
                'data'    => $vehiculo,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }
}
