<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Enums\EstadoReservaEnum;
use App\Http\Requests\ReservaController\StoreReservaRequest;
use App\Http\Requests\ReservaController\UpdateReservaRequest;
use App\Models\Reserva;
use App\Models\Vehiculo;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
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
                    'message' => 'No tienes permiso para ver las reservas',
                ], 403);
            }

            $reservas = Reserva::with([
                'cliente:id,nombre,dui,telefono,numero_licencia,vencimiento_licencia',
                'vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'vehiculo.modelo:id,nombre,marca_id',
                'vehiculo.modelo.marca:id,nombre',
                'vehiculo.categoria:id,nombre',
                'user:id,nombre,apellido',
            ])
                ->when($request->search, function ($query, $search) {
                    $query->where('estado', 'like', '%' . $search . '%')
                        ->orWhereHas('cliente', function ($q) use ($search) {
                            $q->where('nombre', 'like', '%' . $search . '%')
                                ->orWhere('dui', 'like', '%' . $search . '%');
                        });
                })
                ->when($request->estado, function ($query, $estado) {
                    $query->where('estado', $estado);
                })
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $reservas,
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
    public function store(StoreReservaRequest $request)
    {
        try {
            // authorize() y rules() ya se resolvieron automáticamente
            $userAuth = auth('api')->user();

            $cliente = Cliente::findOrFail($request->cliente_id);

            if ($cliente->vencimiento_licencia->isPast()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El cliente tiene la licencia vencida, no puede hacer una reserva',
                ], 422);
            }

            $vehiculo = Vehiculo::findOrFail($request->vehiculo_id);

            if (in_array($vehiculo->estado, [
                VehiculoEstadoEnum::MANTENIMIENTO->value,
                VehiculoEstadoEnum::FUERA_SERVICIO->value,
            ])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vehículo no está disponible, estado actual: ' . $vehiculo->estado,
                ], 422);
            }

            $traslapada = Reserva::where('vehiculo_id', $request->vehiculo_id)
                ->whereNotIn('estado', [EstadoReservaEnum::CANCELADA->value])
                ->where(function ($query) use ($request) {
                    $query->where('fecha_inicio', '<', $request->fecha_fin)
                        ->where('fecha_fin', '>', $request->fecha_inicio);
                })->exists();

            if ($traslapada) {
                $fechasOcupadas = Reserva::where('vehiculo_id', $request->vehiculo_id)
                    ->whereNotIn('estado', [EstadoReservaEnum::CANCELADA->value])
                    ->select('fecha_inicio', 'fecha_fin')
                    ->get();

                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vehículo ya tiene una reserva en esas fechas',
                    'fechas_ocupadas' => $fechasOcupadas,
                ], 422);
            }

            // El vehículo NO cambia de estado aquí: sigue en DISPONIBLE.
            // Las fechas reservadas quedan registradas en la tabla `reservas`;
            // la disponibilidad real para un rango de fechas se calcula
            // consultando traslapes contra esa tabla (ver Vehiculo::disponibles()),
            // no leyendo el campo `estado` del vehículo.
            $reserva = DB::transaction(function () use ($request, $userAuth) {
                return Reserva::create([
                    'fecha_solicitud' => now(),
                    'fecha_inicio'    => $request->fecha_inicio,
                    'fecha_fin'       => $request->fecha_fin,
                    'estado'          => EstadoReservaEnum::PENDIENTE->value,
                    'cliente_id'      => $request->cliente_id,
                    'vehiculo_id'     => $request->vehiculo_id,
                    'usuario_id'      => $userAuth->id,
                ]);
            });

            $reserva->load([
                'cliente:id,nombre,dui,telefono',
                'vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'vehiculo.modelo:id,nombre,marca_id',
                'vehiculo.modelo.marca:id,nombre',
                'vehiculo.categoria:id,nombre',
                'user:id,nombre,apellido',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Reserva creada con éxito',
                'data'    => $reserva,
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cliente o vehículo no encontrado',
            ], 404);
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
                    'message' => 'No tienes permiso para ver esta reserva',
                ], 403);
            }

            $reserva = Reserva::with([
                'cliente:id,nombre,dui,telefono,numero_licencia,vencimiento_licencia',
                'vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'vehiculo.modelo:id,nombre,marca_id',
                'vehiculo.modelo.marca:id,nombre',
                'vehiculo.categoria:id,nombre',
                'user:id,nombre,apellido',
                'cancelacion:id,fecha_cancelacion,motivo,usuario_id',
                'cancelacion.user:id,nombre,apellido',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $reserva,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Reserva no encontrada',
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
    public function update(UpdateReservaRequest $request, string $id)
    {
        try {
            $reserva = Reserva::findOrFail($id);

            if ($reserva->estado !== EstadoReservaEnum::PENDIENTE->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Solo se pueden modificar reservas en estado PENDIENTE',
                ], 422);
            }

            if ($request->has('fecha_inicio') || $request->has('fecha_fin')) {
                $inicioEvaluar = $request->fecha_inicio ?? $reserva->fecha_inicio;
                $finEvaluar = $request->fecha_fin ?? $reserva->fecha_fin;

                $traslapada = Reserva::where('vehiculo_id', $reserva->vehiculo_id)
                    ->where('id', '!=', $id)
                    ->whereNotIn('estado', [EstadoReservaEnum::CANCELADA->value])
                    ->where(function ($query) use ($inicioEvaluar, $finEvaluar) {
                        $query->where('fecha_inicio', '<', $finEvaluar)
                            ->where('fecha_fin', '>', $inicioEvaluar);
                    })->exists();

                if ($traslapada) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'No se puede actualizar: El vehículo ya tiene otra reserva en esas fechas',
                    ], 422);
                }
            }

            $reserva->update($request->only(['fecha_inicio', 'fecha_fin']));

            return response()->json([
                'status'  => 'success',
                'message' => 'Reserva actualizada con éxito',
                'data'    => $reserva->load([
                    'cliente:id,nombre',
                    'vehiculo:id,placa,color',
                ]),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Reserva no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
