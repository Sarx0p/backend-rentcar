<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\CargoAdicionalEstadoEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\EstadoReservaEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Enums\IncidenciaEstadoEnum;
use App\Enums\TipoIncidenciaEnum;
use App\Enums\CierreRentaEstadoEnum;
use App\Http\Requests\ContratoController\StoreContratoRequest;
use App\Http\Requests\ContratoController\StoreContratoDirectoRequest;
use App\Http\Requests\ContratoController\CambiarVehiculoRequest;
use App\Http\Requests\ContratoController\AnularContratoRequest;
use App\Models\Contrato;
use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\Incidencia;
use App\Models\CierreRenta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ContratoController extends Controller
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
                !$userAuth->hasRole(RolEnum::EMPLEADO->value) &&
                !$userAuth->hasRole(RolEnum::CONTADOR->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver los contratos',
                ], 403);
            }

            $contratos = Contrato::with([
                'reserva:id,fecha_inicio,fecha_fin',
                'user:id,nombre,apellido',
                'cliente:id,nombre,dui',
                'vehiculo.modelo.marca',
            ])
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('numero_contrato', 'like', '%' . $search . '%')
                            ->orWhere('estado_contrato', 'like', '%' . $search . '%')
                            ->orWhereRaw("JSON_EXTRACT(info_registro, '$.cliente.nombre') LIKE ?", ['%' . $search . '%'])
                            ->orWhereRaw("JSON_EXTRACT(info_registro, '$.cliente.dui') LIKE ?", ['%' . $search . '%']);
                    });
                })
                ->when($request->estado, function ($query, $estado) {
                    $query->where('estado_contrato', $estado);
                })
                ->when($request->estado_pago, function ($query, $estadoPago) {
                    $query->where('estado_pago', $estadoPago);
                })
                ->orderByRaw("
                CASE estado_contrato
                    WHEN 'ACTIVO' THEN 1
                    WHEN 'PENDIENTE' THEN 2
                    WHEN 'FINALIZADO' THEN 3
                    WHEN 'ANULADO' THEN 4
                    ELSE 99
                END
            ")
                ->orderByRaw("
                CASE estado_pago
                    WHEN 'PENDIENTE' THEN 1
                    WHEN 'PARCIAL' THEN 2
                    WHEN 'PAGADO' THEN 3
                    ELSE 99
                END
            ")
                ->orderBy('fecha_hora_entrega')
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $contratos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage — desde una reserva existente.
     */
    public function store(StoreContratoRequest $request)
    {
        try {
            $reserva = Reserva::with([
                'vehiculo.modelo.marca',
                'vehiculo.categoria',
                'cliente.municipio.departamento',
            ])->findOrFail($request->reserva_id);

            $entrega    = Carbon::parse($request->fecha_hora_entrega);
            $dias       = max(1, (int) $reserva->fecha_inicio->diffInDays($reserva->fecha_fin));
            $devolucion = $entrega->copy()->addDays($dias);
            $vehiculo   = $reserva->vehiculo;

            $incidenciasEsteticas = $vehiculo->incidencias()
                ->where('estado_incidencia', '!=', IncidenciaEstadoEnum::RESUELTA->value)
                ->where('tipo_incidencia', TipoIncidenciaEnum::DANIO_ESTETICO->value)
                ->get(['id', 'tipo_incidencia', 'descripcion', 'fecha']);

            $contrato = DB::transaction(function () use ($request, $reserva, $vehiculo, $incidenciasEsteticas, $entrega, $devolucion, $dias) {

                $precioDia  = (float) $vehiculo->categoria->precio_dia;
                $descuento  = $request->monto_descuento ?? 0;
                $montoTotal = ($dias * $precioDia) - $descuento;

                $textoEstetico = $incidenciasEsteticas->isNotEmpty()
                    ? 'Daños estéticos previos: ' . $incidenciasEsteticas->map(function ($inc) {
                        $fechaFormat = $inc->fecha ? Carbon::parse($inc->fecha)->format('d/m/Y') : 'N/A';
                        return "{$inc->descripcion} ({$fechaFormat})";
                    })->implode(' | ')
                    : null;

                $observacionesFinal = trim(
                    ($request->observaciones_entrega ?? '') . ' ' . ($textoEstetico ?? '')
                );

                $contrato = Contrato::create([
                    'numero_contrato' => $this->generarNumeroContrato(),
                    'cliente_id'      => $reserva->cliente_id,
                    'vehiculo_id'     => $vehiculo->id,
                    'info_registro'   => [
                        'vehiculo' => [
                            'placa'      => $vehiculo->placa,
                            'color'      => $vehiculo->color,
                            'anio'       => $vehiculo->anio,
                            'modelo'     => $vehiculo->modelo->nombre,
                            'marca'      => $vehiculo->modelo->marca->nombre,
                            'categoria'  => $vehiculo->categoria->nombre,
                            'precio_dia' => $vehiculo->categoria->precio_dia,
                        ],
                        'cliente' => [
                            'nombre'          => $reserva->cliente->nombre,
                            'dui'             => $reserva->cliente->dui,
                            'telefono'        => $reserva->cliente->telefono,
                            'numero_licencia' => $reserva->cliente->numero_licencia,
                            'municipio'       => $reserva->cliente->municipio->nombre ?? null,
                            'departamento'    => $reserva->cliente->municipio->departamento->nombre ?? null,
                        ],
                    ],
                    'reserva_id'                => $reserva->id,
                    'fecha_hora_entrega'        => $entrega,
                    'fecha_hora_devolucion'     => $devolucion,
                    'dias_acordados'            => $dias,
                    'precio_por_dia'            => $precioDia,
                    'monto_descuento'           => $descuento,
                    'monto_total_renta'         => $montoTotal,
                    'nivel_combustible_entrega' => $request->nivel_combustible_entrega,
                    'observaciones_entrega'     => $observacionesFinal ?: null,
                    'observaciones'             => $request->observaciones,
                    'estado_contrato'           => EstadoContratoEnum::ACTIVO->value,
                    'estado_pago'               => EstadoPagoEnum::PENDIENTE->value,
                    'usuario_id'                => auth('api')->id(),
                ]);

                $reserva->update(['estado' => EstadoReservaEnum::CONFIRMADA->value]);
                $vehiculo->update(['estado' => VehiculoEstadoEnum::RENTADO->value]);

                return $contrato;
            });

            $contrato->load([
                'reserva:id,fecha_inicio,fecha_fin',
                'user:id,nombre,apellido',
                'cliente',
                'vehiculo.modelo.marca',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Contrato creado con éxito desde la reserva',
                'advertencia_estetica' => $incidenciasEsteticas->isNotEmpty()
                    ? 'Atención: El vehículo cuenta con ' . $incidenciasEsteticas->count() . ' detalle(s) estético(s) previo(s).'
                    : null,
                'detalles_esteticos' => $incidenciasEsteticas,
                'data'    => $contrato,
            ], 201);
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

    public function storeDirecto(StoreContratoDirectoRequest $request)
    {
        try {
            $cliente = Cliente::with('municipio.departamento')->findOrFail($request->cliente_id);

            $vehiculo = Vehiculo::with(['modelo.marca', 'categoria'])->findOrFail($request->vehiculo_id);

            $fechaEntrega    = now();
            $fechaDevolucion = $fechaEntrega->copy()->addDays($request->dias_acordados);

            $incidenciasEsteticas = $vehiculo->incidencias()
                ->where('estado_incidencia', '!=', IncidenciaEstadoEnum::RESUELTA->value)
                ->where('tipo_incidencia', TipoIncidenciaEnum::DANIO_ESTETICO->value)
                ->get(['id', 'tipo_incidencia', 'descripcion', 'fecha']);

            $contrato = DB::transaction(function () use ($request, $cliente, $vehiculo, $fechaEntrega, $fechaDevolucion, $incidenciasEsteticas) {
                $precioDia  = (float) $vehiculo->categoria->precio_dia;
                $descuento  = $request->monto_descuento ?? 0;
                $montoTotal = ($request->dias_acordados * $precioDia) - $descuento;

                $textoEstetico = $incidenciasEsteticas->isNotEmpty()
                    ? 'Daños estéticos previos: ' . $incidenciasEsteticas->map(function ($inc) {
                        $fechaFormat = $inc->fecha ? Carbon::parse($inc->fecha)->format('d/m/Y') : 'N/A';
                        return "{$inc->descripcion} ({$fechaFormat})";
                    })->implode(' | ')
                    : null;

                $observacionesFinal = trim(
                    ($request->observaciones_entrega ?? '') . ' ' . ($textoEstetico ?? '')
                );

                $contrato = Contrato::create([
                    'numero_contrato' => $this->generarNumeroContrato(),
                    'cliente_id'      => $request->cliente_id,
                    'vehiculo_id'     => $vehiculo->id,
                    'info_registro'   => [
                        'vehiculo' => [
                            'placa'      => $vehiculo->placa,
                            'color'      => $vehiculo->color,
                            'anio'       => $vehiculo->anio,
                            'modelo'     => $vehiculo->modelo->nombre,
                            'marca'      => $vehiculo->modelo->marca->nombre,
                            'categoria'  => $vehiculo->categoria->nombre,
                            'precio_dia' => $vehiculo->categoria->precio_dia,
                        ],
                        'cliente' => [
                            'nombre'          => $cliente->nombre,
                            'dui'             => $cliente->dui,
                            'telefono'        => $cliente->telefono,
                            'numero_licencia' => $cliente->numero_licencia,
                            'municipio'       => $cliente->municipio->nombre ?? null,
                            'departamento'    => $cliente->municipio->departamento->nombre ?? null,
                        ],
                    ],
                    'reserva_id'                => null,
                    'fecha_hora_entrega'        => $fechaEntrega,
                    'fecha_hora_devolucion'     => $fechaDevolucion,
                    'dias_acordados'            => $request->dias_acordados,
                    'precio_por_dia'            => $precioDia,
                    'monto_descuento'           => $descuento,
                    'monto_total_renta'         => $montoTotal,
                    'nivel_combustible_entrega' => $request->nivel_combustible_entrega,
                    'observaciones_entrega'     => $observacionesFinal ?: null,
                    'observaciones'             => $request->observaciones,
                    'estado_contrato'           => EstadoContratoEnum::ACTIVO->value,
                    'estado_pago'               => EstadoPagoEnum::PENDIENTE->value,
                    'usuario_id'                => auth('api')->id(),
                ]);

                $vehiculo->update(['estado' => VehiculoEstadoEnum::RENTADO->value]);

                return $contrato;
            });

            $contrato->load([
                'user:id,nombre,apellido',
                'cliente',
                'vehiculo.modelo.marca',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Contrato directo creado con éxito',
                'advertencia_estetica' => $incidenciasEsteticas->isNotEmpty()
                    ? 'Atención: El vehículo cuenta con ' . $incidenciasEsteticas->count() . ' detalle(s) estético(s) previo(s).'
                    : null,
                'detalles_esteticos' => $incidenciasEsteticas,
                'data'    => $contrato,
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

    public function cambiarVehiculo(CambiarVehiculoRequest $request, string $id)
    {
        try {
            $contrato = Contrato::with(['vehiculo', 'reserva', 'cliente.municipio.departamento'])->findOrFail($id);

            $vehiculoAnterior = $contrato->vehiculo;
            $vehiculoNuevo    = Vehiculo::with(['modelo.marca', 'categoria'])->findOrFail($request->vehiculo_nuevo_id);
            $ahora            = now();

            $diasAcordados = (int) $contrato->dias_acordados;
            $minutos       = max(0, intdiv($ahora->getTimestamp() - $contrato->fecha_hora_entrega->getTimestamp(), 60));
            $diasCompletos = intdiv($minutos, 1440);
            $diasUsados    = $diasCompletos + (($minutos - $diasCompletos * 1440) > 120 ? 1 : 0);
            $diasUsados    = max(1, min($diasUsados, $diasAcordados));
            $diasRestantes = $diasAcordados - $diasUsados;

            $descuentoUsado    = round((float) $contrato->monto_descuento * $diasUsados / $diasAcordados, 2);
            $descuentoRestante = round((float) $contrato->monto_descuento - $descuentoUsado, 2);

            $continuacion = DB::transaction(function () use ($request, $contrato, $vehiculoAnterior, $vehiculoNuevo, $ahora, $diasUsados, $diasRestantes, $descuentoUsado, $descuentoRestante) {
                $incidencia = Incidencia::create([
                    'vehiculo_id'       => $vehiculoAnterior->id,
                    'contrato_id'       => $contrato->id,
                    'usuario_id'        => auth('api')->id(),
                    'tipo_incidencia'   => TipoIncidenciaEnum::DANIO_MECANICO->value,
                    'responsable_tipo'  => $request->responsable_tipo,
                    'estado_incidencia' => IncidenciaEstadoEnum::REPORTADA->value,
                    'descripcion'       => $request->motivo,
                    'fecha'             => $ahora->toDateString(),
                    'costo'             => $request->costo,
                ]);

                $contrato->update([
                    'dias_acordados'  => $diasUsados,
                    'monto_descuento' => $descuentoUsado,
                ]);

                $contrato->recalcularTotalYEstadoPago();

                $saldoTrasladado = round($contrato->montoPagado() - (float) $contrato->monto_total_renta, 2);

                $nivelRecepcion = $request->nivel_combustible_recepcion ?? $contrato->nivel_combustible_entrega;

                $observacionesCierre = "CAMBIO DE VEHÍCULO:\n- Motivo: " . $request->motivo
                    . "\n- Días usados con este vehículo: " . $diasUsados;

                if (!$request->filled('nivel_combustible_recepcion')) {
                    $observacionesCierre .= "\n- No se indicó el combustible de recepción, se tomó el de la entrega";
                }

                CierreRenta::create([
                    'contrato_id'                 => $contrato->id,
                    'usuario_id'                  => auth('api')->id(),
                    'fecha_hora_recepcion'        => $ahora,
                    'nivel_combustible_recepcion' => $nivelRecepcion,
                    'estado_vehiculo_recepcion'   => 'RECIBIDO CON DAÑOS',
                    'observaciones'               => $observacionesCierre,
                    'horas_retraso'               => 0,
                    'monto_extras'                => $contrato->cargosAdicionales()
                        ->where('estado_cargo', '!=', CargoAdicionalEstadoEnum::ANULADO->value)
                        ->sum('monto'),
                    'estado'                      => CierreRentaEstadoEnum::CAMBIO_VEHICULO->value,
                    'monto_devuelto'              => 0,
                ]);

                $incidenciasEsteticas = $vehiculoNuevo->incidencias()
                    ->where('estado_incidencia', '!=', IncidenciaEstadoEnum::RESUELTA->value)
                    ->where('tipo_incidencia', TipoIncidenciaEnum::DANIO_ESTETICO->value)
                    ->get(['descripcion', 'fecha']);

                $observacionesEntrega = 'Continuación del contrato ' . $contrato->numero_contrato . ' por cambio de vehículo.';

                if ($incidenciasEsteticas->isNotEmpty()) {
                    $observacionesEntrega .= ' Daños estéticos previos: ' . $incidenciasEsteticas->map(function ($inc) {
                        $fechaFormat = $inc->fecha ? Carbon::parse($inc->fecha)->format('d/m/Y') : 'N/A';
                        return "{$inc->descripcion} ({$fechaFormat})";
                    })->implode(' | ');
                }

                $cliente = $contrato->cliente;

                $nuevo = Contrato::create([
                    'numero_contrato'    => $this->generarNumeroContrato(),
                    'cliente_id'         => $contrato->cliente_id,
                    'vehiculo_id'        => $vehiculoNuevo->id,
                    'info_registro'      => [
                        'vehiculo' => [
                            'placa'      => $vehiculoNuevo->placa,
                            'color'      => $vehiculoNuevo->color,
                            'anio'       => $vehiculoNuevo->anio,
                            'modelo'     => $vehiculoNuevo->modelo->nombre,
                            'marca'      => $vehiculoNuevo->modelo->marca->nombre,
                            'categoria'  => $vehiculoNuevo->categoria->nombre,
                            'precio_dia' => $contrato->precio_por_dia,
                        ],
                        'cliente' => [
                            'nombre'          => $cliente->nombre,
                            'dui'             => $cliente->dui,
                            'telefono'        => $cliente->telefono,
                            'numero_licencia' => $cliente->numero_licencia,
                            'municipio'       => $cliente->municipio->nombre ?? null,
                            'departamento'    => $cliente->municipio->departamento->nombre ?? null,
                        ],
                        'continuacion_de' => [
                            'contrato_id'                 => $contrato->id,
                            'numero_contrato'             => $contrato->numero_contrato,
                            'fecha_cambio'                => $ahora->toDateTimeString(),
                            'motivo'                      => $request->motivo,
                            'responsable_tipo'            => $request->responsable_tipo,
                            'incidencia_id'               => $incidencia->id,
                            'dias_usados'                 => $diasUsados,
                            'saldo_trasladado'            => $saldoTrasladado,
                            'nivel_combustible_recepcion' => $nivelRecepcion,
                            'vehiculo_anterior'           => [
                                'id'    => $vehiculoAnterior->id,
                                'placa' => $vehiculoAnterior->placa,
                            ],
                            'usuario_id'                  => auth('api')->id(),
                        ],
                    ],
                    'reserva_id'                => null,
                    'contrato_origen_id'        => $contrato->id,
                    'saldo_trasladado'          => $saldoTrasladado,
                    'fecha_hora_entrega'        => $ahora,
                    'fecha_hora_devolucion'     => $contrato->fecha_hora_devolucion,
                    'dias_acordados'            => $diasRestantes,
                    'precio_por_dia'            => $contrato->precio_por_dia,
                    'monto_descuento'           => $descuentoRestante,
                    'monto_total_renta'         => max(0, ($diasRestantes * (float) $contrato->precio_por_dia) - $descuentoRestante),
                    'nivel_combustible_entrega' => $request->nivel_combustible_entrega,
                    'observaciones_entrega'     => mb_substr($observacionesEntrega, 0, 500),
                    'observaciones'             => $contrato->observaciones,
                    'estado_contrato'           => EstadoContratoEnum::ACTIVO->value,
                    'estado_pago'               => EstadoPagoEnum::PENDIENTE->value,
                    'usuario_id'                => auth('api')->id(),
                ]);

                $nuevo->recalcularTotalYEstadoPago();

                $info = $contrato->info_registro ?? [];
                $info['continuado_en'] = [
                    'contrato_id'     => $nuevo->id,
                    'numero_contrato' => $nuevo->numero_contrato,
                    'fecha_cambio'    => $ahora->toDateTimeString(),
                    'vehiculo_nuevo'  => [
                        'id'    => $vehiculoNuevo->id,
                        'placa' => $vehiculoNuevo->placa,
                    ],
                ];

                $contrato->update([
                    'estado_contrato' => EstadoContratoEnum::FINALIZADO->value,
                    'info_registro'   => $info,
                ]);

                $contrato->recalcularTotalYEstadoPago();

                if ($contrato->reserva) {
                    $contrato->reserva->update([
                        'estado' => EstadoReservaEnum::CONCLUIDA->value,
                    ]);
                }

                $vehiculoAnterior->update(['estado' => VehiculoEstadoEnum::ENPROCESO->value]);
                $vehiculoNuevo->update(['estado' => VehiculoEstadoEnum::RENTADO->value]);

                return $nuevo;
            });

            $continuacion->load([
                'cliente:id,nombre,dui',
                'vehiculo.modelo.marca',
                'contratoOrigen:id,numero_contrato,dias_acordados,monto_total_renta,estado_contrato,estado_pago',
            ]);

            $saldo = (float) $continuacion->saldo_trasladado;

            return response()->json([
                'status'  => 'success',
                'message' => 'Vehículo cambiado. El contrato ' . $continuacion->contratoOrigen->numero_contrato
                    . ' se cerró con ' . $diasUsados . ' día(s) y continúa en el contrato ' . $continuacion->numero_contrato
                    . ' con ' . $diasRestantes . ' día(s) al mismo precio.'
                    . ($saldo > 0 ? ' Se trasladaron $' . number_format($saldo, 2) . ' ya pagados.' : '')
                    . ($saldo < 0 ? ' Se trasladó un saldo pendiente de $' . number_format(abs($saldo), 2) . '.' : ''),
                'data'    => $continuacion,
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Contrato o vehículo no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    public function anular(AnularContratoRequest $request, string $id)
    {
        try {
            $userAuth = auth('api')->user();

            $contrato = Contrato::with(['vehiculo', 'reserva'])->findOrFail($id);

            DB::transaction(function () use ($request, $contrato, $userAuth) {
                $info = $contrato->info_registro ?? [];
                $info['anulacion'] = [
                    'fecha'      => now()->toDateTimeString(),
                    'motivo'     => $request->motivo,
                    'usuario_id' => $userAuth->id,
                    'reserva_id' => $contrato->reserva_id,
                ];
                if ($contrato->reserva) {
                    $contrato->reserva->update([
                        'estado' => EstadoReservaEnum::PENDIENTE->value,
                    ]);
                }
                if ($contrato->vehiculo->estado === VehiculoEstadoEnum::RENTADO->value) {
                    $contrato->vehiculo->update([
                        'estado' => VehiculoEstadoEnum::DISPONIBLE->value,
                    ]);
                }
                $contrato->update([
                    'estado_contrato' => EstadoContratoEnum::ANULADO->value,
                    'reserva_id'      => null,
                    'info_registro'   => $info,
                ]);
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Contrato anulado correctamente',
                'data'    => $contrato->fresh(['cliente:id,nombre,dui', 'vehiculo:id,placa,color,estado']),
            ], 200);
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
                !$userAuth->hasRole(RolEnum::EMPLEADO->value) &&
                !$userAuth->hasRole(RolEnum::CONTADOR->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este contrato',
                ], 403);
            }

            $contrato = Contrato::with([
                'reserva:id,fecha_inicio,fecha_fin',
                'user:id,nombre,apellido',
                'pagos:id,contrato_id,monto,estado_transaccion',
                'cliente',
                'vehiculo.modelo.marca',
                'vehiculo.incidencias' => function ($q) {
                    $q->where('estado_incidencia', '!=', IncidenciaEstadoEnum::RESUELTA->value)
                        ->where('tipo_incidencia', TipoIncidenciaEnum::DANIO_ESTETICO->value);
                },
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $contrato,
            ], 200);
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

    public function generarPdf(string $id)
    {
        try {
            $contrato = Contrato::with(['user'])->findOrFail($id);

            $pdf = Pdf::loadView('contratos.pdf', compact('contrato'));
            $pdf->setPaper('letter', 'portrait');
            $pdf->setOption('margin-top', 15);
            $pdf->setOption('margin-bottom', 15);
            $pdf->setOption('margin-left', 20);
            $pdf->setOption('margin-right', 20);

            return $pdf->stream('contrato-' . $contrato->numero_contrato . '.pdf');
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

    private function generarNumeroContrato(): string
    {
        $prefijo = 'CONT-' . now()->format('Y') . '-';

        $ultimoContrato = Contrato::where('numero_contrato', 'like', $prefijo . '%')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        $correlativo = $ultimoContrato
            ? (int) substr($ultimoContrato->numero_contrato, strlen($prefijo)) + 1
            : 1;

        return $prefijo . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }
}
