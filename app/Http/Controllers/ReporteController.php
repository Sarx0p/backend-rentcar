<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\EstadoTransaccionEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Enums\IncidenciaTipoResponsableEnum;
use App\Models\Pago;
use App\Models\Vehiculo;
use App\Models\Cliente;
use App\Models\Cancelacion;
use App\Models\Contrato;
use App\Models\Mantenimiento;
use App\Models\Incidencia;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    /**
     * Chequeo de permiso reutilizable para reportes.
     */
    private function tienePermiso($incluirContador = false): bool
    {
        $userAuth = auth('api')->user();

        $permitido = $userAuth->hasRole(RolEnum::ADMINISTRADOR->value)
            || $userAuth->hasRole(RolEnum::EMPLEADO->value);

        if ($incluirContador) {
            $permitido = $permitido || $userAuth->hasRole(RolEnum::CONTADOR->value);
        }

        return $permitido;
    }

  

    public function ingresos(Request $request)
    {
        try {
            if (!$this->tienePermiso(true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
            $fechaFin    = $request->fecha_fin ?? now()->endOfMonth()->format('Y-m-d');

            $pagos = Pago::with('contrato:id,numero_contrato')
                ->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                ->whereDate('fecha_pago', '>=', $fechaInicio)
                ->whereDate('fecha_pago', '<=', $fechaFin)
                ->orderBy('fecha_pago')
                ->get();

            $totalIngresos = $pagos->sum('monto');

            $pdf = Pdf::loadView('reportes.ingresos', [
                'pagos'         => $pagos,
                'totalIngresos' => $totalIngresos,
                'fechaInicio'   => $fechaInicio,
                'fechaFin'      => $fechaFin,
            ]);

            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream('reporte-ingresos.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    public function estadoFlota(Request $request)
    {
        try {
            if (!$this->tienePermiso()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $estados = Vehiculo::selectRaw('estado, count(*) as total')
                ->groupBy('estado')
                ->get();

            $totalVehiculos = $estados->sum('total');

            $vehiculos = Vehiculo::with([
                'modelo:id,nombre,marca_id',
                'modelo.marca:id,nombre',
                'categoria:id,nombre',
            ])
                ->orderBy('estado')
                ->get();

            $pdf = Pdf::loadView('reportes.estado-flota', [
                'estados'        => $estados,
                'totalVehiculos' => $totalVehiculos,
                'vehiculos'      => $vehiculos,
            ]);

            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream('reporte-estado-flota.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    public function licenciasPorVencer(Request $request)
    {
        try {
            if (!$this->tienePermiso()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $dias = (int) ($request->dias ?? 30);

            $clientes = Cliente::whereBetween('vencimiento_licencia', [
                now(),
                now()->addDays($dias),
            ])
                ->orderBy('vencimiento_licencia')
                ->get();

            $pdf = Pdf::loadView('reportes.licencias-por-vencer', [
                'clientes' => $clientes,
                'dias'     => $dias,
            ]);

            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream('reporte-licencias-por-vencer.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    public function reservasCanceladas(Request $request)
    {
        try {
            if (!$this->tienePermiso()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $cancelaciones = Cancelacion::with([
                'reserva.cliente:id,nombre,dui',
                'reserva.vehiculo:id,placa,color',
                'user:id,nombre,apellido',
            ])
                ->when($request->fecha_inicio && $request->fecha_fin, function ($query) use ($request) {
                    $query->whereDate('fecha_cancelacion', '>=', $request->fecha_inicio)
                        ->whereDate('fecha_cancelacion', '<=', $request->fecha_fin);
                })
                ->latest('fecha_cancelacion')
                ->get();

            $pdf = Pdf::loadView('reportes.reservas-canceladas', [
                'cancelaciones' => $cancelaciones,
            ]);

            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream('reporte-reservas-canceladas.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    // ============ NUEVOS ============

    /**
     * Desempeño general: resumen ejecutivo del negocio.
     */
    public function desempenoGeneral(Request $request)
    {
        try {
            if (!$this->tienePermiso(true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
            $fechaFin    = $request->fecha_fin ?? now()->endOfMonth()->format('Y-m-d');

            $totalIngresos = Pago::where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                ->whereDate('fecha_pago', '>=', $fechaInicio)
                ->whereDate('fecha_pago', '<=', $fechaFin)
                ->sum('monto');

            $totalContratos = Contrato::whereDate('fecha_hora_entrega', '>=', $fechaInicio)
                ->whereDate('fecha_hora_entrega', '<=', $fechaFin)
                ->count();

            $totalVehiculos = Vehiculo::count();

            $vehiculosRentados = Vehiculo::where('estado', VehiculoEstadoEnum::RENTADO->value)->count();

            $tasaOcupacion = $totalVehiculos > 0
                ? round(($vehiculosRentados / $totalVehiculos) * 100, 2)
                : 0;

            $totalClientesNuevos = Cliente::whereDate('created_at', '>=', $fechaInicio)
                ->whereDate('created_at', '<=', $fechaFin)
                ->count();

            $totalGastosMantenimiento = Mantenimiento::whereDate('fecha', '>=', $fechaInicio)
                ->whereDate('fecha', '<=', $fechaFin)
                ->sum('costo');

            $pdf = Pdf::loadView('reportes.desempeno-general', [
                'fechaInicio'              => $fechaInicio,
                'fechaFin'                 => $fechaFin,
                'totalIngresos'            => $totalIngresos,
                'totalContratos'           => $totalContratos,
                'totalVehiculos'           => $totalVehiculos,
                'vehiculosRentados'        => $vehiculosRentados,
                'tasaOcupacion'            => $tasaOcupacion,
                'totalClientesNuevos'      => $totalClientesNuevos,
                'totalGastosMantenimiento' => $totalGastosMantenimiento,
            ]);

            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream('reporte-desempeno-general.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Ingresos por vehículo: suma de pagos confirmados agrupados por vehículo.
     */
    public function ingresosPorVehiculo(Request $request)
    {
        try {
            if (!$this->tienePermiso(true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
            $fechaFin    = $request->fecha_fin ?? now()->endOfMonth()->format('Y-m-d');

            $vehiculos = Vehiculo::with(['modelo.marca'])
                ->get()
                ->map(function ($vehiculo) use ($fechaInicio, $fechaFin) {
                    $ingresos = Pago::whereHas('contrato', function ($q) use ($vehiculo) {
                        $q->where('vehiculo_id', $vehiculo->id);
                    })
                        ->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                        ->whereDate('fecha_pago', '>=', $fechaInicio)
                        ->whereDate('fecha_pago', '<=', $fechaFin)
                        ->sum('monto');

                    $rentas = Contrato::where('vehiculo_id', $vehiculo->id)
                        ->whereDate('fecha_hora_entrega', '>=', $fechaInicio)
                        ->whereDate('fecha_hora_entrega', '<=', $fechaFin)
                        ->count();

                    return [
                        'vehiculo'    => $vehiculo,
                        'ingresos'    => $ingresos,
                        'num_rentas'  => $rentas,
                    ];
                })
                ->sortByDesc('ingresos')
                ->values();

            $totalGeneral = $vehiculos->sum('ingresos');

            $pdf = Pdf::loadView('reportes.ingresos-por-vehiculo', [
                'vehiculos'    => $vehiculos,
                'totalGeneral' => $totalGeneral,
                'fechaInicio'  => $fechaInicio,
                'fechaFin'     => $fechaFin,
            ]);

            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream('reporte-ingresos-por-vehiculo.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Gastos por vehículo: mantenimientos + incidencias asumidas por el negocio.
     */
    public function gastosPorVehiculo(Request $request)
    {
        try {
            if (!$this->tienePermiso(true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
            $fechaFin    = $request->fecha_fin ?? now()->endOfMonth()->format('Y-m-d');

            $vehiculos = Vehiculo::with(['modelo.marca'])
                ->get()
                ->map(function ($vehiculo) use ($fechaInicio, $fechaFin) {
                    $gastoMantenimiento = Mantenimiento::where('vehiculo_id', $vehiculo->id)
                        ->whereDate('fecha', '>=', $fechaInicio)
                        ->whereDate('fecha', '<=', $fechaFin)
                        ->sum('costo');

                    $gastoIncidenciasNegocio = Incidencia::where('vehiculo_id', $vehiculo->id)
                        ->where('responsable_tipo', IncidenciaTipoResponsableEnum::NEGOCIO->value)
                        ->whereDate('fecha', '>=', $fechaInicio)
                        ->whereDate('fecha', '<=', $fechaFin)
                        ->sum('costo');

                    return [
                        'vehiculo'                 => $vehiculo,
                        'gasto_mantenimiento'      => $gastoMantenimiento,
                        'gasto_incidencias_negocio' => $gastoIncidenciasNegocio,
                        'gasto_total'              => $gastoMantenimiento + $gastoIncidenciasNegocio,
                    ];
                })
                ->sortByDesc('gasto_total')
                ->values();

            $totalGeneral = $vehiculos->sum('gasto_total');

            $pdf = Pdf::loadView('reportes.gastos-por-vehiculo', [
                'vehiculos'    => $vehiculos,
                'totalGeneral' => $totalGeneral,
                'fechaInicio'  => $fechaInicio,
                'fechaFin'     => $fechaFin,
            ]);

            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream('reporte-gastos-por-vehiculo.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Resultado neto por vehículo: ingresos - gastos.
     */
    public function resultadoNetoPorVehiculo(Request $request)
    {
        try {
            if (!$this->tienePermiso(true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
            $fechaFin    = $request->fecha_fin ?? now()->endOfMonth()->format('Y-m-d');

            $vehiculos = Vehiculo::with(['modelo.marca', 'propietario'])
                ->get()
                ->map(function ($vehiculo) use ($fechaInicio, $fechaFin) {
                    $ingresos = Pago::whereHas('contrato', function ($q) use ($vehiculo) {
                        $q->where('vehiculo_id', $vehiculo->id);
                    })
                        ->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                        ->whereDate('fecha_pago', '>=', $fechaInicio)
                        ->whereDate('fecha_pago', '<=', $fechaFin)
                        ->sum('monto');

                    $gastoMantenimiento = Mantenimiento::where('vehiculo_id', $vehiculo->id)
                        ->whereDate('fecha', '>=', $fechaInicio)
                        ->whereDate('fecha', '<=', $fechaFin)
                        ->sum('costo');

                    $gastoIncidencias = Incidencia::where('vehiculo_id', $vehiculo->id)
                        ->where('responsable_tipo', IncidenciaTipoResponsableEnum::NEGOCIO->value)
                        ->whereDate('fecha', '>=', $fechaInicio)
                        ->whereDate('fecha', '<=', $fechaFin)
                        ->sum('costo');

                    $gastoTotal = $gastoMantenimiento + $gastoIncidencias;

                    return [
                        'vehiculo'      => $vehiculo,
                        'ingresos'      => $ingresos,
                        'gastos'        => $gastoTotal,
                        'resultado_neto' => $ingresos - $gastoTotal,
                    ];
                })
                ->sortByDesc('resultado_neto')
                ->values();

            $totalIngresos = $vehiculos->sum('ingresos');
            $totalGastos   = $vehiculos->sum('gastos');
            $totalNeto     = $vehiculos->sum('resultado_neto');

            $pdf = Pdf::loadView('reportes.resultado-neto-por-vehiculo', [
                'vehiculos'     => $vehiculos,
                'totalIngresos' => $totalIngresos,
                'totalGastos'   => $totalGastos,
                'totalNeto'     => $totalNeto,
                'fechaInicio'   => $fechaInicio,
                'fechaFin'      => $fechaFin,
            ]);

            $pdf->setPaper('letter', 'landscape');

            return $pdf->stream('reporte-resultado-neto-por-vehiculo.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Saldos pendientes: contratos con pagos incompletos.
     */
    public function saldosPendientes(Request $request)
    {
        try {
            if (!$this->tienePermiso(true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $contratos = Contrato::with([
                'cliente:id,nombre,dui,telefono',
                'vehiculo:id,placa,color',
            ])
                ->where('estado_pago', '!=', EstadoPagoEnum::PAGADO->value)
                ->get()
                ->map(function ($contrato) {
                    $totalPagado = $contrato->pagos()
                        ->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                        ->sum('monto');

                    return [
                        'contrato'      => $contrato,
                        'total_pagado'  => $totalPagado,
                        'saldo'         => $contrato->monto_total_renta - $totalPagado,
                    ];
                })
                ->sortByDesc('saldo')
                ->values();

            $totalSaldos = $contratos->sum('saldo');

            $pdf = Pdf::loadView('reportes.saldos-pendientes', [
                'contratos'   => $contratos,
                'totalSaldos' => $totalSaldos,
            ]);

            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream('reporte-saldos-pendientes.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
