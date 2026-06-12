<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\EstadoReservaEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Models\Cancelacion;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
       try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver las cancelaciones',
                ], 403);
            }

            $cancelaciones = Cancelacion::with([
                'user:id,nombre,apellido',
                'reserva:id,fecha_inicio,fecha_fin,cliente_id,vehiculo_id',
                'reserva.cliente:id,nombre,dui',
                'reserva.vehiculo:id,placa,color',
            ])
                ->when($request->search, function ($query, $search) {
                    $query->where('motivo', 'like', '%' . $search . '%')
                          ->orWhereHas('reserva.cliente', function ($q) use ($search) {
                              $q->where('nombre', 'like', '%' . $search . '%')
                                ->orWhere('dui', 'like', '%' . $search . '%');
                          });
                })
                ->when($request->fecha_inicio && $request->fecha_fin, function ($query) use ($request) {
                    $query->whereDate('fecha_cancelacion', '>=', $request->fecha_inicio)
                          ->whereDate('fecha_cancelacion', '<=', $request->fecha_fin);
                })
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $cancelaciones,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',

            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para cancelar reservas',
                ], 403);
            }

            $request->validate([
                'reserva_id' => 'required|exists:reservas,id',
                'motivo'     => 'required|string',
            ]);

            $reserva = Reserva::with('vehiculo')->findOrFail($request->reserva_id);

            if ($reserva->estado === EstadoReservaEnum::CANCELADA->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Esta reserva ya fue cancelada',
                ], 422);
            }

            $cancelacion = DB::transaction(function () use ($request, $reserva, $userAuth) {

                $cancelacion = Cancelacion::create([
                    'fecha_cancelacion' => now(),
                    'motivo'            => $request->motivo,
                    'usuario_id'        => $userAuth->id,
                    'reserva_id'        => $reserva->id,
                ]);

                $reserva->update([
                    'estado' => EstadoReservaEnum::CANCELADA->value,
                ]);

                if ($reserva->vehiculo->estado === VehiculoEstadoEnum::RESERVADO->value) {
                    $reserva->vehiculo->update([
                        'estado' => VehiculoEstadoEnum::DISPONIBLE->value,
                    ]);
                }

                return $cancelacion;
            });

            $cancelacion->load([
                'user:id,nombre,apellido',
                'reserva:id,fecha_inicio,fecha_fin,estado,cliente_id,vehiculo_id',
                'reserva.cliente:id,nombre,dui',
                'reserva.vehiculo:id,placa,color,estado',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Reserva cancelada con éxito',
                'data'    => $cancelacion,
            ], 201);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Reserva no encontrada',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error de validación',

            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',

            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
       try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver esta cancelación',
                ], 403);
            }

            $cancelacion = Cancelacion::with([
                'user:id,nombre,apellido',
                'reserva:id,fecha_inicio,fecha_fin,tipo_reserva,estado,cliente_id,vehiculo_id',
                'reserva.cliente:id,nombre,dui,telefono',
                'reserva.vehiculo:id,placa,color,anio,modelo_id',
                'reserva.vehiculo.modelo:id,nombre,marca_id',
                'reserva.vehiculo.modelo.marca:id,nombre',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $cancelacion,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cancelación no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
                
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
