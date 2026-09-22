<?php

namespace App\Http\Controllers;

use App\Enums\EstadoMantenimientoEnum;
use App\Enums\IncidenciaEstadoEnum;
use App\Enums\RolEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Http\Requests\MantenimientoController\StoreMantenimientoRequest;
use App\Http\Requests\MantenimientoController\UpdateMantenimientoRequest;
use App\Models\Incidencia;
use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MantenimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (
                !$user->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$user->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver los mantenimientos',
                ], 403);
            }

            if ($request->filled('estado') && !EstadoMantenimientoEnum::tryFrom($request->estado)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El estado proporcionado no es válido',
                ], 422);
            }

            $mantenimientos = Mantenimiento::with([
                'vehiculo:id,anio,color,placa,estado,propietario_id',
                'incidencia',
            ])
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('tipo_mantenimiento', 'like', '%' . $search . '%')
                            ->orWhere('descripcion', 'like', '%' . $search . '%')
                            ->orWhere('lugar', 'like', '%' . $search . '%')
                            ->orWhereHas('vehiculo', function ($q) use ($search) {
                                $q->where('placa', 'like', '%' . $search . '%');
                            });
                    });
                })
                ->when($request->tipo_mantenimiento, function ($query, $tipo) {
                    $query->where('tipo_mantenimiento', $tipo);
                })
                ->when($request->estado, function ($query, $estado) {
                    $query->where('estado', $estado);
                })
                ->when($request->vehiculo_id, function ($query, $vehiculoId) {
                    $query->where('vehiculo_id', $vehiculoId);
                })
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $mantenimientos,
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
    public function store(StoreMantenimientoRequest $request)
    {
        try {
            $vehiculo = Vehiculo::findOrFail($request->vehiculo_id);

            if ($vehiculo->estado === VehiculoEstadoEnum::MANTENIMIENTO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Este vehículo ya se encuentra en mantenimiento actualmente',
                ], 422);
            }

            if ($vehiculo->estado === VehiculoEstadoEnum::RENTADO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "No se puede enviar a mantenimiento un vehículo en estado {$vehiculo->estado}",
                ], 422);
            }

            if ($request->filled('incidencia_id')) {
                $incidencia = Incidencia::find($request->incidencia_id);

                if (!$incidencia) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'La incidencia seleccionada no existe',
                    ], 422);
                }

                if ((int) $incidencia->vehiculo_id !== (int) $request->vehiculo_id) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'La incidencia seleccionada no pertenece al vehículo especificado',
                    ], 422);
                }

                if ($incidencia->estado_incidencia === IncidenciaEstadoEnum::RESUELTA->value) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'La incidencia seleccionada ya se encuentra resuelta',
                    ], 422);
                }
            }

            $mantenimientoReciente = Mantenimiento::where('vehiculo_id', $request->vehiculo_id)
                ->where('created_at', '>=', now()->subMinute())
                ->exists();

            if ($mantenimientoReciente) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Se registró un mantenimiento para este vehículo recientemente. Por favor espera un momento.',
                ], 422);
            }

            $mantenimiento = DB::transaction(function () use ($request, $vehiculo) {
                $mantenimiento = Mantenimiento::create([
                    'vehiculo_id'        => $request->vehiculo_id,
                    'incidencia_id'      => $request->incidencia_id ?? null,
                    'tipo_mantenimiento' => $request->tipo_mantenimiento,
                    'descripcion'        => $request->descripcion,
                    'costo'              => $request->costo,
                    'fecha'              => now(),
                    'lugar'              => $request->lugar,
                    'estado'             => EstadoMantenimientoEnum::ACTIVO->value,
                ]);

                $vehiculo->update([
                    'estado' => VehiculoEstadoEnum::MANTENIMIENTO->value,
                ]);

                return $mantenimiento;
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Mantenimiento registrado con éxito',
                'data'    => $mantenimiento->load([
                    'vehiculo:id,anio,color,placa,estado,propietario_id',
                    'incidencia',
                ]),
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vehículo no encontrado',
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
            $user = auth('api')->user();

            if (
                !$user->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$user->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este mantenimiento',
                ], 403);
            }

            $mantenimiento = Mantenimiento::with([
                'vehiculo:id,anio,color,placa,estado,propietario_id',
                'incidencia',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $mantenimiento,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mantenimiento no encontrado',
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
    public function update(UpdateMantenimientoRequest $request, string $id)
    {
        try {
            $mantenimiento = Mantenimiento::with(['vehiculo', 'incidencia'])->findOrFail($id);

            if (in_array($mantenimiento->estado, [
                EstadoMantenimientoEnum::FINALIZADO->value,
                EstadoMantenimientoEnum::CANCELADO->value,
            ])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "No se puede modificar un mantenimiento con estado {$mantenimiento->estado}",
                ], 422);
            }

            DB::transaction(function () use ($request, $mantenimiento) {
                $estadoAnterior = $mantenimiento->estado;

                $mantenimiento->update($request->only([
                    'tipo_mantenimiento',
                    'descripcion',
                    'costo',
                    'lugar',
                    'estado',
                ]));

                $mantenimiento->refresh();
                $estadoNuevo = $mantenimiento->estado;

                if (
                    $estadoAnterior === EstadoMantenimientoEnum::ACTIVO->value
                    && $estadoNuevo === EstadoMantenimientoEnum::FINALIZADO->value
                ) {
                    $mantenimiento->vehiculo->update([
                        'estado' => VehiculoEstadoEnum::DISPONIBLE->value,
                    ]);

                    if ($mantenimiento->incidencia_id) {
                        $mantenimiento->incidencia?->update([
                            'estado_incidencia' => IncidenciaEstadoEnum::RESUELTA->value,
                        ]);
                    }
                }

                if (
                    $estadoAnterior === EstadoMantenimientoEnum::ACTIVO->value
                    && $estadoNuevo === EstadoMantenimientoEnum::CANCELADO->value
                ) {
                    $mantenimiento->vehiculo->update([
                        'estado' => VehiculoEstadoEnum::DISPONIBLE->value,
                    ]);
                }
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Mantenimiento actualizado con éxito',
                'data'    => $mantenimiento->fresh([
                    'vehiculo:id,anio,color,placa,estado,propietario_id',
                    'incidencia',
                ]),
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mantenimiento no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Anulación lógica).
     */
   public function destroy(string $id)
    {
        try {
            $mantenimiento = Mantenimiento::with('vehiculo')->findOrFail($id);

            if ($mantenimiento->estado === EstadoMantenimientoEnum::CANCELADO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Este mantenimiento ya se encuentra anulado',
                ], 422);
            }

            if ($mantenimiento->estado === EstadoMantenimientoEnum::FINALIZADO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se puede anular un mantenimiento que ya ha sido finalizado',
                ], 422);
            }

            DB::transaction(function () use ($mantenimiento) {
                $estadoAnterior = $mantenimiento->estado;

                $mantenimiento->update([
                    'estado' => EstadoMantenimientoEnum::CANCELADO->value,
                ]);

                if ($estadoAnterior === EstadoMantenimientoEnum::ACTIVO->value) {
                    $mantenimiento->vehiculo->update([
                        'estado' => VehiculoEstadoEnum::DISPONIBLE->value,
                    ]);
                }
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Mantenimiento anulado con éxito',
                'data'    => $mantenimiento->fresh([
                    'vehiculo:id,anio,color,placa,estado,propietario_id',
                    'incidencia',
                ]),
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mantenimiento no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
