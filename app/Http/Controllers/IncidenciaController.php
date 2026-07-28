<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\IncidenciaEstadoEnum;
use App\Enums\IncidenciaTipoResponsableEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Http\Requests\IncidenciaController\StoreIncidenciaRequest;
use App\Http\Requests\IncidenciaController\UpdateIncidenciaRequest;
use App\Models\Incidencia;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncidenciaController extends Controller
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
                    'message' => 'No tienes permiso para ver las incidencias',
                ], 403);
            }

            $incidencias = Incidencia::with([
                'vehiculo:id,placa,color,estado',
                'contrato:id,numero_contrato,monto_total_renta,cliente_id',
                'contrato.cliente:id,nombre',
                'usuario:id,nombre,apellido',
            ])
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('tipo_incidencia', 'like', '%' . $search . '%')
                            ->orWhere('descripcion', 'like', '%' . $search . '%')
                            ->orWhereHas('vehiculo', function ($q) use ($search) {
                                $q->where('placa', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('contrato.cliente', function ($q) use ($search) {
                                $q->where('nombre', 'like', '%' . $search . '%')
                                    ->orWhere('dui', 'like', '%' . $search . '%');
                            });
                    });
                })
                ->when($request->tipo_incidencia, function ($query, $tipo) {
                    $query->where('tipo_incidencia', $tipo);
                })
                ->when($request->responsable_tipo, function ($query, $responsable) {
                    $query->where('responsable_tipo', $responsable);
                })
                ->when($request->estado_incidencia, function ($query, $estado) {
                    $query->where('estado_incidencia', $estado);
                })
                ->when($request->vehiculo_id, function ($query, $vehiculoId) {
                    $query->where('vehiculo_id', $vehiculoId);
                })
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $incidencias,
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
    public function store(StoreIncidenciaRequest $request)
    {
        try {
            // authorize(), rules() y withValidator() ya se resolvieron automáticamente
            $incidencia = DB::transaction(function () use ($request) {
                $incidencia = Incidencia::create([
                    'vehiculo_id'       => $request->vehiculo_id,
                    'contrato_id'       => $request->contrato_id,
                    'usuario_id'        => auth('api')->id(),
                    'tipo_incidencia'   => $request->tipo_incidencia,
                    'responsable_tipo'  => $request->responsable_tipo,
                    'estado_incidencia' => IncidenciaEstadoEnum::REPORTADA->value,
                    'descripcion'       => $request->descripcion,
                    'fecha'             => $request->fecha,
                    'costo'             => $request->costo,
                ]);

                // Caso: responsabilidad del CLIENTE → el costo se suma al contrato
                if (
                    $request->responsable_tipo === IncidenciaTipoResponsableEnum::CLIENTE->value
                    && $request->filled('costo')
                    && $incidencia->contrato
                ) {
                    $contrato = $incidencia->contrato;
                    $contrato->update([
                        'monto_total_renta' => $contrato->monto_total_renta + $request->costo,
                    ]);
                }

                // Caso: responsabilidad del NEGOCIO → el vehículo pasa a mantenimiento
                if ($request->responsable_tipo === IncidenciaTipoResponsableEnum::NEGOCIO->value) {
                    $incidencia->vehiculo->update([
                        'estado' => VehiculoEstadoEnum::MANTENIMIENTO->value,
                    ]);
                }

                return $incidencia;
            });

            $incidencia->load([
                'vehiculo:id,placa,color,estado',
                'contrato:id,numero_contrato,monto_total_renta,cliente_id',
                'contrato.cliente:id,nombre',
                'usuario:id,nombre,apellido',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Incidencia registrada con éxito',
                'data'    => $incidencia,
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Vehículo o contrato no encontrado',
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
                    'message' => 'No tienes permiso para ver esta incidencia',
                ], 403);
            }

            $incidencia = Incidencia::with([
                'vehiculo:id,placa,color,estado',
                'contrato:id,numero_contrato,monto_total_renta,cliente_id',
                'contrato.cliente:id,nombre',
                'usuario:id,nombre,apellido',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $incidencia,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Incidencia no encontrada',
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
    public function update(UpdateIncidenciaRequest $request, string $id)
    {
        try {
            // authorize(), rules() y withValidator() ya se resolvieron automáticamente
            $incidencia = Incidencia::with(['contrato', 'vehiculo'])->findOrFail($id);

            DB::transaction(function () use ($request, $incidencia) {
                $costoAnterior       = $incidencia->costo;
                $responsableAnterior = $incidencia->responsable_tipo;

                $incidencia->update($request->only([
                    'tipo_incidencia',
                    'responsable_tipo',
                    'estado_incidencia',
                    'descripcion',
                    'fecha',
                    'costo',
                ]));

                $incidencia->refresh();

                $responsableNuevo = $incidencia->responsable_tipo;
                $costoNuevo       = $incidencia->costo;

                if (
                    $responsableNuevo === IncidenciaTipoResponsableEnum::CLIENTE->value
                    && $incidencia->contrato
                    && $costoNuevo != $costoAnterior
                ) {
                    $contrato = $incidencia->contrato;

                    $montoAnteriorAplicado = $responsableAnterior === IncidenciaTipoResponsableEnum::CLIENTE->value
                        ? $costoAnterior
                        : 0;

                    $diferencia = ($costoNuevo ?? 0) - $montoAnteriorAplicado;

                    $contrato->update([
                        'monto_total_renta' => $contrato->monto_total_renta + $diferencia,
                    ]);
                }

                if (
                    $responsableAnterior === IncidenciaTipoResponsableEnum::CLIENTE->value
                    && $responsableNuevo !== IncidenciaTipoResponsableEnum::CLIENTE->value
                    && $incidencia->contrato
                ) {
                    $contrato = $incidencia->contrato;
                    $contrato->update([
                        'monto_total_renta' => $contrato->monto_total_renta - ($costoAnterior ?? 0),
                    ]);
                }

                if (
                    $responsableNuevo === IncidenciaTipoResponsableEnum::NEGOCIO->value
                    && $responsableAnterior !== IncidenciaTipoResponsableEnum::NEGOCIO->value
                ) {
                    $incidencia->vehiculo->update([
                        'estado' => VehiculoEstadoEnum::MANTENIMIENTO->value,
                    ]);
                }
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Incidencia actualizada con éxito',
                'data'    => $incidencia->load([
                    'vehiculo:id,placa,color,estado',
                    'contrato:id,numero_contrato,monto_total_renta,cliente_id',
                    'contrato.cliente:id,nombre',
                    'usuario:id,nombre,apellido',
                ]),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Incidencia no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage 
     */
    public function destroy(string $id)
    {
        try {
            $userAuth = auth('api')->user();

            if (!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para anular una incidencia',
                ], 403);
            }

            $incidencia = Incidencia::with(['contrato', 'vehiculo'])->findOrFail($id);

            if ($incidencia->estado_incidencia === IncidenciaEstadoEnum::ANULADA->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'La incidencia ya fue anulada anteriormente',
                ], 409);
            }

            DB::transaction(function () use ($incidencia) {
                // Revertir el cobro al contrato si el responsable era CLIENTE
                if (
                    $incidencia->responsable_tipo === IncidenciaTipoResponsableEnum::CLIENTE->value
                    && $incidencia->contrato
                    && $incidencia->costo
                ) {
                    $incidencia->contrato->update([
                        'monto_total_renta' => $incidencia->contrato->monto_total_renta - $incidencia->costo,
                    ]);
                }

                $incidencia->update([
                    'estado_incidencia' => IncidenciaEstadoEnum::ANULADA->value,
                ]);
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Incidencia anulada correctamente',
                'data'    => $incidencia->fresh([
                    'vehiculo:id,placa,color,estado',
                    'contrato:id,numero_contrato,monto_total_renta,cliente_id',
                ]),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Incidencia no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
