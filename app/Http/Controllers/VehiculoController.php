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
                ->when($request->filled('estado'), function ($query) use ($request) {
                    $query->where('estado', $request->estado);
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = $request->search;
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('placa', 'like', '%' . $search . '%')
                            ->orWhereHas('modelo', function ($q) use ($search) {
                                $q->where('nombre', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('modelo.marca', function ($q) use ($search) {
                                $q->where('nombre', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('modelo', function ($q) use ($search) {
                                $q->whereHas('marca', function ($qMarca) use ($search) {
                                    $qMarca->whereRaw("CONCAT(marcas.nombre, ' ', modelos.nombre) LIKE ?", ['%' . $search . '%']);
                                });
                            });
                    });
                })
                ->when(
                    $request->filled('fecha_inicio') && $request->filled('fecha_fin'),
                    function ($query) use ($request) {
                        $query->whereDoesntHave('reservas', function ($q) use ($request) {
                            $q->whereNotIn('estado', [EstadoReservaEnum::CANCELADA->value])
                                ->whereDate('fecha_inicio', '<', $request->fecha_fin)
                                ->whereDate('fecha_fin', '>', $request->fecha_inicio);
                        });
                    }
                )
                ->orderBy('id')
                ->paginate(15);

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
            if ($request->has('estado')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El estado del vehículo no se puede establecer manualmente al crearlo.',
                ], 422);
            }

            DB::beginTransaction();

            $vehiculo = Vehiculo::create([
                'anio'                => $request->anio,
                'color'               => $request->color,
                'placa'               => $request->placa,
                'capacidad_pasajeros' => $request->capacidad_pasajeros,
                'estado'              => VehiculoEstadoEnum::DISPONIBLE->value,
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

            if ($request->has('estado')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El estado del vehículo no se puede modificar directamente. Cambia por contratos, reservas, mantenimiento o cierres.',
                ], 422);
            }

            DB::beginTransaction();

            $vehiculo->update($request->only([
                'anio',
                'color',
                'placa',
                'capacidad_pasajeros',
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

            if ($vehiculo->estado === VehiculoEstadoEnum::RENTADO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vehículo tiene que estar disponible para poder desactivarse.',
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
