<?php

namespace App\Http\Controllers;

use App\Enums\CargoAdicionalEstadoEnum;
use App\Enums\CargoAdicionalTipoEnum;
use App\Enums\RolEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\EstadoReservaEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Enums\CierreRentaEstadoEnum;
use App\Http\Requests\CierreRentaController\StoreCierreRentaRequest;
use App\Models\CargoAdicional;
use App\Models\Contrato;
use App\Models\CierreRenta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CierreRentaController extends Controller
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
                    'message' => 'No tienes permiso para ver los cierres de renta',
                ], 403);
            }

            $cierres = CierreRenta::with([
                'contrato:id,numero_contrato,monto_total_renta,estado_pago',
                'user:id,nombre,apellido',
            ])
                ->when($request->search, function ($query, $search) {
                    $query->where('estado_vehiculo_recepcion', 'like', '%' . $search . '%')
                        ->orWhereHas('contrato', function ($q) use ($search) {
                            $q->where('numero_contrato', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('contrato.cliente', function ($q) use ($search) {
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
                'data'   => $cierres,
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
    public function store(StoreCierreRentaRequest $request)
    {
        try {
            // authorize() y rules() ya se resolvieron automáticamente
            $contrato = Contrato::with(['vehiculo', 'reserva'])->findOrFail($request->contrato_id);

            if ($contrato->estado_contrato !== EstadoContratoEnum::ACTIVO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Solo se puede cerrar un contrato en estado ACTIVO',
                ], 422);
            }

            if ($contrato->estado_pago !== EstadoPagoEnum::PAGADO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se puede cerrar la renta, el contrato tiene pagos pendientes',
                ], 422);
            }

            if ($contrato->cierreRenta) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Este contrato ya tiene un cierre registrado',
                ], 422);
            }

            $cierre = DB::transaction(function () use ($request, $contrato) {

                // Calcular horas de retraso con margen de 2 horas
                $fechaDevolucionAcordada = $contrato->fecha_hora_devolucion;
                $fechaRecepcionReal      = \Carbon\Carbon::parse($request->fecha_hora_recepcion);
                $fechaLimite             = $fechaDevolucionAcordada->copy()->addHours(2);

                $horasRetraso = $fechaRecepcionReal->gt($fechaLimite)
                    ? max(0, $fechaDevolucionAcordada->diffInHours($fechaRecepcionReal, false))
                    : 0;

                if ($horasRetraso > 0 && $request->aplicar_cargo_retraso) {
                    CargoAdicional::create([
                        'contrato_id'    => $contrato->id,
                        'tipo_cargo'     => CargoAdicionalTipoEnum::RETRASO->value,
                        'descripcion'    => 'Cargo por retraso de ' . $horasRetraso . ' horas',
                        'monto'          => $request->monto_retraso,
                        'fecha_registro' => now(),
                        'estado_cargo'   => CargoAdicionalEstadoEnum::APLICADO->value,
                    ]);

                    $montoBase   = ($contrato->dias_acordados * $contrato->precio_por_dia) - $contrato->monto_descuento;
                    $totalCargos = $contrato->cargosAdicionales()->sum('monto');

                    $contrato->update([
                        'monto_total_renta' => $montoBase + $totalCargos,
                        'estado_pago'       => EstadoPagoEnum::PENDIENTE->value,
                    ]);
                }

                $cierre = CierreRenta::create([
                    'contrato_id'                 => $contrato->id,
                    'usuario_id'                  => auth('api')->id(),
                    'fecha_hora_recepcion'        => $request->fecha_hora_recepcion,
                    'nivel_combustible_recepcion' => $request->nivel_combustible_recepcion,
                    'estado_vehiculo_recepcion'   => $request->estado_vehiculo_recepcion,
                    'observaciones'               => $request->observaciones,
                    'horas_retraso'               => $horasRetraso,
                    'monto_extras'                => $contrato->cargosAdicionales()->sum('monto'),
                    'estado'                      => CierreRentaEstadoEnum::FINALIZADO->value,
                ]);

                $contrato->vehiculo->update([
                    'estado' => VehiculoEstadoEnum::DISPONIBLE->value,
                ]);

                if ($contrato->reserva) {
                    $contrato->reserva->update([
                        'estado' => EstadoReservaEnum::CONCLUIDA->value,
                    ]);
                }

                $contrato->update([
                    'estado_contrato' => EstadoContratoEnum::FINALIZADO->value,
                ]);

                return $cierre;
            });

            $cierre->load([
                'contrato:id,numero_contrato,monto_total_renta,estado_pago',
                'user:id,nombre,apellido',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Renta cerrada con éxito',
                'data'    => $cierre,
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Contrato no encontrado',
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
                    'message' => 'No tienes permiso para ver este cierre de renta',
                ], 403);
            }

            $cierre = CierreRenta::with([
                'contrato:id,numero_contrato,monto_total_renta,estado_pago',
                'contrato.cliente:id,nombre,dui,telefono',
                'contrato.vehiculo:id,placa,color,anio,modelo_id',
                'contrato.vehiculo.modelo:id,nombre,marca_id',
                'contrato.vehiculo.modelo.marca:id,nombre',
                'contrato.cargosAdicionales',
                'contrato.incidencias',
                'user:id,nombre,apellido',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $cierre,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cierre de renta no encontrado',
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
