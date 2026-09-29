<?php

namespace App\Http\Controllers;

use App\Enums\CargoAdicionalEstadoEnum;
use App\Enums\CargoAdicionalTipoEnum;
use App\Enums\CierreRentaEstadoEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\EstadoReservaEnum;
use App\Enums\EstadoTransaccionEnum;
use App\Enums\IncidenciaEstadoEnum;
use App\Enums\RolEnum;
use App\Enums\TipoIncidenciaEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Http\Requests\CierreRentaController\StoreCierreRentaRequest;
use App\Models\CargoAdicional;
use App\Models\CierreRenta;
use App\Models\Contrato;
use Carbon\Carbon;
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
                    $query->where(function ($q) use ($search) {
                        $q->where('estado_vehiculo_recepcion', 'like', '%' . $search . '%')
                            ->orWhereHas('contrato', function ($q2) use ($search) {
                                $q2->where('numero_contrato', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('contrato.cliente', function ($q2) use ($search) {
                                $q2->where('nombre', 'like', '%' . $search . '%')
                                    ->orWhere('dui', 'like', '%' . $search . '%');
                            });
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
            $contrato = Contrato::with(['vehiculo', 'reserva'])->findOrFail($request->contrato_id);

            if ($contrato->estado_contrato !== EstadoContratoEnum::ACTIVO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Solo se puede cerrar un contrato en estado ACTIVO',
                ], 422);
            }

            $forzarConDeuda      = (bool) $request->forzar_cierre_con_deuda;
            $aplicarCargoRetraso = $request->boolean('aplicar_cargo_retraso', true);

            if ($contrato->estado_pago !== EstadoPagoEnum::PAGADO->value && !$forzarConDeuda) {
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

            $resultado = DB::transaction(function () use ($request, $contrato, $forzarConDeuda, $aplicarCargoRetraso) {

                // 1. Horas de retraso: margen de 2 horas; pasado el margen se cobran todas las horas, redondeando hacia arriba
                $fechaDevolucionAcordada = $contrato->fecha_hora_devolucion;
                $fechaRecepcionReal      = Carbon::parse($request->fecha_hora_recepcion);
                $fechaLimite             = $fechaDevolucionAcordada->copy()->addHours(2);

                $horasRetraso = $fechaRecepcionReal->gt($fechaLimite)
                    ? (int) ceil($fechaDevolucionAcordada->diffInMinutes($fechaRecepcionReal, false) / 60)
                    : 0;

                $totalPagado = (float) $contrato->pagos()
                    ->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                    ->sum('monto');

                // 2. Cargo por retraso: automático, una sola vez por contrato
                $tarifaHoraRetraso = 5.00;
                $montoRetraso      = 0;

                if ($horasRetraso > 0 && $aplicarCargoRetraso) {
                    $yaTieneCargoRetraso = $contrato->cargosAdicionales()
                        ->where('tipo_cargo', CargoAdicionalTipoEnum::RETRASO->value)
                        ->where('estado_cargo', '!=', CargoAdicionalEstadoEnum::ANULADO->value)
                        ->exists();

                    if (!$yaTieneCargoRetraso) {
                        $montoRetraso = $horasRetraso * $tarifaHoraRetraso;

                        CargoAdicional::create([
                            'contrato_id'    => $contrato->id,
                            'tipo_cargo'     => CargoAdicionalTipoEnum::RETRASO->value,
                            'descripcion'    => 'Cargo por retraso de ' . $horasRetraso . ' hora(s)',
                            'monto'          => $montoRetraso,
                            'fecha_registro' => now(),
                            'estado_cargo'   => CargoAdicionalEstadoEnum::APLICADO->value,
                        ]);

                        $nuevoTotal = (float) $contrato->monto_total_renta + $montoRetraso;

                        $contrato->update([
                            'monto_total_renta' => $nuevoTotal,
                            'estado_pago'       => $totalPagado >= $nuevoTotal
                                ? EstadoPagoEnum::PAGADO->value
                                : ($totalPagado > 0
                                    ? EstadoPagoEnum::PARCIAL->value
                                    : EstadoPagoEnum::PENDIENTE->value),
                        ]);
                    }
                }

                // 3. Si quedó saldo y no se fuerza, el cierre queda en espera (el cargo ya quedó guardado)
                if ($contrato->estado_pago !== EstadoPagoEnum::PAGADO->value && !$forzarConDeuda) {
                    $saldoPendiente = max(0, (float) $contrato->monto_total_renta - $totalPagado);

                    return [
                        'cierre_completado' => false,
                        'mensaje'           => 'Se registró un cargo por retraso de ' . $horasRetraso . ' hora(s) ($' . number_format($montoRetraso, 2) . '). El contrato tiene un saldo pendiente de $' . number_format($saldoPendiente, 2) . ': registra el pago para poder cerrar, o cierra con deuda.',
                        'horas_retraso'     => $horasRetraso,
                        'monto_retraso'     => $montoRetraso,
                        'saldo_pendiente'   => $saldoPendiente,
                    ];
                }

                $cargosVigentes = $contrato->cargosAdicionales()
                    ->where('estado_cargo', '!=', CargoAdicionalEstadoEnum::ANULADO->value)
                    ->get();

                $partesObservaciones = [];

                if (!empty($request->observaciones)) {
                    $partesObservaciones[] = "OBSERVACIONES GENERALES:\n" . trim($request->observaciones);
                }

                if ($cargosVigentes->isNotEmpty()) {
                    $textoCargos = $cargosVigentes->map(function ($cargo) {
                        return "- {$cargo->descripcion} ($" . number_format($cargo->monto, 2) . ")";
                    })->implode("\n");

                    $partesObservaciones[] = "CARGOS ADICIONALES:\n" . $textoCargos;
                }

                $incidenciasNoResueltas = $contrato->incidencias()
                    ->whereNotIn('estado_incidencia', [
                        IncidenciaEstadoEnum::RESUELTA->value,
                        IncidenciaEstadoEnum::ANULADA->value,
                    ])
                    ->pluck('descripcion');

                if ($incidenciasNoResueltas->isNotEmpty()) {
                    $textoIncidencias = $incidenciasNoResueltas->map(function ($desc) {
                        return "- " . $desc;
                    })->implode("\n");

                    $partesObservaciones[] = "INCIDENCIAS NO RESUELTAS:\n" . $textoIncidencias;
                }

                $montoDeuda = $forzarConDeuda
                    ? max(0, (float) $contrato->monto_total_renta - $totalPagado)
                    : null;

                $cierre = CierreRenta::create([
                    'contrato_id'                 => $contrato->id,
                    'usuario_id'                  => auth('api')->id(),
                    'fecha_hora_recepcion'        => $request->fecha_hora_recepcion,
                    'nivel_combustible_recepcion' => $request->nivel_combustible_recepcion,
                    'estado_vehiculo_recepcion'   => $request->estado_vehiculo_recepcion,
                    'observaciones'               => implode("\n\n", $partesObservaciones),
                    'horas_retraso'               => $horasRetraso,
                    'monto_extras'                => $cargosVigentes->sum('monto'),
                    'estado'                      => $forzarConDeuda
                        ? CierreRentaEstadoEnum::FINALIZADO_CON_DEUDA->value
                        : CierreRentaEstadoEnum::FINALIZADO->value,
                    'motivo_cierre_deuda'         => $forzarConDeuda ? $request->motivo_cierre_deuda : null,
                    'monto_deuda'                 => $montoDeuda,
                ]);

                // Solo se libera si sigue RENTADO (no pisar EN_PROCESO ni MANTENIMIENTO)
                // Si tiene un daño mecánico pendiente pasa a EN PROCESO en lugar de DISPONIBLE
                if ($contrato->vehiculo->estado === VehiculoEstadoEnum::RENTADO->value) {
                    $danioMecanicoPendiente = $contrato->vehiculo->incidencias()
                        ->where('tipo_incidencia', TipoIncidenciaEnum::DANIO_MECANICO->value)
                        ->whereNotIn('estado_incidencia', [
                            IncidenciaEstadoEnum::RESUELTA->value,
                            IncidenciaEstadoEnum::ANULADA->value,
                        ])
                        ->exists();

                    $contrato->vehiculo->update([
                        'estado' => $danioMecanicoPendiente
                            ? VehiculoEstadoEnum::ENPROCESO->value
                            : VehiculoEstadoEnum::DISPONIBLE->value,
                    ]);
                }

                if ($contrato->reserva) {
                    $contrato->reserva->update([
                        'estado' => EstadoReservaEnum::CONCLUIDA->value,
                    ]);
                }

                $contrato->update([
                    'estado_contrato' => EstadoContratoEnum::FINALIZADO->value,
                ]);

                return [
                    'cierre_completado' => true,
                    'cierre'            => $cierre,
                ];
            });

            if (!$resultado['cierre_completado']) {
                return response()->json([
                    'status'            => 'success',
                    'cierre_completado' => false,
                    'message'           => $resultado['mensaje'],
                    'data'              => [
                        'horas_retraso'   => $resultado['horas_retraso'],
                        'monto_retraso'   => $resultado['monto_retraso'],
                        'saldo_pendiente' => $resultado['saldo_pendiente'],
                    ],
                ], 200);
            }

            $cierre = $resultado['cierre'];
            $cierre->load([
                'contrato:id,numero_contrato,monto_total_renta,estado_pago',
                'user:id,nombre,apellido',
            ]);

            return response()->json([
                'status'            => 'success',
                'cierre_completado' => true,
                'message'           => $cierre->estado === CierreRentaEstadoEnum::FINALIZADO_CON_DEUDA->value
                    ? 'Renta cerrada con deuda pendiente registrada'
                    : 'Renta cerrada con éxito',
                'data'              => $cierre,
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
