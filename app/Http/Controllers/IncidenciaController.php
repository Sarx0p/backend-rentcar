<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\CargoAdicionalEstadoEnum;
use App\Enums\CargoAdicionalTipoEnum;
use App\Enums\IncidenciaEstadoEnum;
use App\Enums\TipoIncidenciaEnum;
use App\Enums\IncidenciaTipoResponsableEnum;
use App\Models\Contrato;
use App\Models\Incidencia;
use App\Models\CargoAdicional;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                'contrato:id,numero_contrato,monto_total_renta,estado_pago',
            ])

                ->when($request->search, function ($query, $search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('tipo_incidencia', 'like', '%' . $search . '%')
                            ->orWhere('descripcion', 'like', '%' . $search . '%')
                            ->orWhereHas('contrato', function ($q) use ($search) {
                                $q->where('numero_contrato', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('contrato.reserva.cliente', function ($q) use ($search) {
                                $q->where('nombre', 'like', '%' . $search . '%')
                                    ->orWhere('dui', 'like', '%' . $search . '%');
                            });
                    });
                })

                ->when($request->tipo_incidencia, function ($query, $tipo) {
                    $query->where('tipo_incidencia', $tipo);
                })
                ->when($request->estado_incidencia, function ($query, $estado) {
                    $query->where('estado_incidencia', $estado);
                })
                ->when($request->contrato_id, function ($query, $contratoId) {
                    $query->where('contrato_id', $contratoId);
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
                'error'   => $e->getMessage(),
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
                    'message' => 'No tienes permiso para registrar incidencias',
                ], 403);
            }

            $request->validate([
                'contrato_id'      => 'required|exists:contratos,id',
                'tipo_incidencia'  => 'required|in:' . implode(',', array_column(TipoIncidenciaEnum::cases(), 'value')),
                'descripcion'      => 'sometimes|string|nullable',
                'costo'            => 'required|numeric|min:0',
                'fecha'            => 'required|date',
                'responsable_tipo' => 'required|in:' . implode(',', array_column(IncidenciaTipoResponsableEnum::cases(), 'value')),
            ]);

            $contrato = Contrato::findOrFail($request->contrato_id);

            if ($contrato->estado_contrato !== EstadoContratoEnum::ACTIVO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Solo se pueden registrar incidencias en contratos ACTIVOS',
                ], 422);
            }

            $incidencia = DB::transaction(function () use ($request, $contrato) {

                $incidencia = Incidencia::create([
                    'contrato_id'       => $contrato->id,
                    'tipo_incidencia'   => $request->tipo_incidencia,
                    'descripcion'       => $request->descripcion ?? null,
                    'costo'             => $request->costo,
                    'fecha'             => $request->fecha,
                    'responsable_tipo'  => $request->responsable_tipo,
                    'estado_incidencia' => IncidenciaEstadoEnum::REPORTADA->value,
                ]);

                if ($request->costo > 0) {
                    CargoAdicional::create([
                        'contrato_id'    => $contrato->id,
                        'tipo_cargo'     => CargoAdicionalTipoEnum::DANIO->value,
                        'descripcion'    => 'Cargo por incidencia: ' . $request->tipo_incidencia,
                        'monto'          => $request->costo,
                        'fecha_registro' => now(),
                        'estado_cargo'   => CargoAdicionalEstadoEnum::PENDIENTE->value,
                    ]);

                    $montoBase   = ($contrato->dias_acordados * $contrato->precio_por_dia) - $contrato->monto_descuento;
                    $totalCargos = $contrato->cargosAdicionales()->sum('monto');

                    $contrato->update([
                        'monto_total_renta' => $montoBase + $totalCargos,
                    ]);
                }

                return $incidencia;
            });

            $incidencia->load('contrato:id,numero_contrato,monto_total_renta,estado_pago');

            return response()->json([
                'status'  => 'success',
                'message' => 'Incidencia registrada con éxito',
                'data'    => $incidencia,
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Contrato no encontrado',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage(),
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
                'contrato:id,numero_contrato,monto_total_renta,estado_pago',
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
                'error'   => $e->getMessage(),
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
