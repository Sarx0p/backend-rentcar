<?php

namespace App\Http\Controllers;

use App\Enums\EstadoReservaEnum;
use App\Enums\RolEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehiculoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $vehiculos = Vehiculo::with(['modelo.marca', 'categoria'])
                ->whereNotIn('estado', [
                    VehiculoEstadoEnum::MANTENIMIENTO->value,
                    VehiculoEstadoEnum::FUERA_SERVICIO->value,
                    VehiculoEstadoEnum::INACTIVO->value,
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

    public function store(Request $request)
    {
        try {
            $userAuth = auth('api')->user();

            if (!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }

            $request->validate([
                'anio'           => 'required|integer|min:1990|max:' . (date('Y') + 1),
                'color'          => 'required|string|max:30',
                'placa'          => 'required|string|max:20|unique:vehiculos,placa',
                'estado'         => 'required|in:DISPONIBLE,RESERVADO,RENTADO,MANTENIMIENTO,FUERA DE SERVICIO,INACTIVO',
                'propietario_id' => 'required|integer|exists:propietarios,id',
                'categoria_id'   => 'required|integer|exists:categorias,id',
                'modelo_id'      => 'required|integer|exists:modelos,id',
                'seguro_id'      => 'required|integer|exists:seguros,id',
            ]);

            DB::beginTransaction();

            $vehiculo = Vehiculo::create([
                'anio'           => $request->anio,
                'color'          => $request->color,
                'placa'          => $request->placa,
                'estado'         => $request->estado,
                'propietario_id' => $request->propietario_id,
                'categoria_id'   => $request->categoria_id,
                'modelo_id'      => $request->modelo_id,
                'seguro_id'      => $request->seguro_id,
            ]);

            DB::commit();

            $vehiculo->load(['modelo.marca', 'categoria']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Vehiculo registrado correctamente.',
                'data'    => $vehiculo,
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Faltan campos requeridos.',
            ], 422);
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

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        
    }
}
