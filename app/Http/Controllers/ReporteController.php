<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\EstadoTransaccionEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Enums\IncidenciaTipoResponsableEnum;
use App\Enums\EstadoPropietarioEnum;
use App\Models\Pago;
use App\Models\Vehiculo;
use App\Enums\EstadoMantenimientoEnum;
use App\Enums\IncidenciaEstadoEnum;
use App\Enums\TipoPropietarioEnum;
use App\Models\Mantenimiento;
use App\Models\Cliente;
use App\Models\Cancelacion;
use App\Models\CierreRenta;
use App\Models\Contrato;
use App\Models\Incidencia;
use App\Models\Reserva;
use App\Models\Propietario;
use Illuminate\Http\Request;
use App\Http\Requests\ReporteController\ReporteRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReporteController extends Controller
{
    /**
     * Chequeo de permiso reutilizable para reportes.
     */
    // gastos del negocio: mantenimientos no cancelados + incidencias del negocio que no tengan ya un mantenimiento
    private function gastosDeVehiculos($vehiculoIds, $fechaInicio, $fechaFin): array
    {
        $mantenimientos = Mantenimiento::query()
            ->when($vehiculoIds !== null, fn ($q) => $q->whereIn('vehiculo_id', $vehiculoIds))
            ->where('estado', '!=', EstadoMantenimientoEnum::CANCELADO->value)
            ->whereDate('fecha', '>=', $fechaInicio)
            ->whereDate('fecha', '<=', $fechaFin)
            ->sum('costo');

        $incidenciasReparadas = Mantenimiento::query()
            ->when($vehiculoIds !== null, fn ($q) => $q->whereIn('vehiculo_id', $vehiculoIds))
            ->whereNotNull('incidencia_id')
            ->where('estado', '!=', EstadoMantenimientoEnum::CANCELADO->value)
            ->pluck('incidencia_id');

        $incidencias = Incidencia::query()
            ->when($vehiculoIds !== null, fn ($q) => $q->whereIn('vehiculo_id', $vehiculoIds))
            ->where('responsable_tipo', IncidenciaTipoResponsableEnum::NEGOCIO->value)
            ->where('estado_incidencia', '!=', IncidenciaEstadoEnum::ANULADA->value)
            ->whereNotIn('id', $incidenciasReparadas)
            ->whereDate('fecha', '>=', $fechaInicio)
            ->whereDate('fecha', '<=', $fechaFin)
            ->sum('costo');

        return [
            'incidencias'    => (float) $incidencias,
            'mantenimientos' => (float) $mantenimientos,
            'total'          => (float) $incidencias + (float) $mantenimientos,
        ];
    }

    private function devolucionesDeVehiculos($vehiculoIds, $fechaInicio, $fechaFin): float
    {
        return (float) CierreRenta::query()
            ->when($vehiculoIds !== null, fn ($q) => $q->whereHas('contrato', fn ($c) => $c->whereIn('vehiculo_id', $vehiculoIds)))
            ->where('monto_devuelto', '>', 0)
            ->whereDate('fecha_hora_recepcion', '>=', $fechaInicio)
            ->whereDate('fecha_hora_recepcion', '<=', $fechaFin)
            ->sum('monto_devuelto');
    }

    private function trasladosDeVehiculos($vehiculoIds, $fechaInicio, $fechaFin): float
    {
        $entrantes = Contrato::whereIn('vehiculo_id', $vehiculoIds)
            ->whereNotNull('contrato_origen_id')
            ->whereDate('fecha_hora_entrega', '>=', $fechaInicio)
            ->whereDate('fecha_hora_entrega', '<=', $fechaFin)
            ->sum('saldo_trasladado');

        $salientes = Contrato::whereNotNull('contrato_origen_id')
            ->whereHas('contratoOrigen', fn ($q) => $q->whereIn('vehiculo_id', $vehiculoIds))
            ->whereDate('fecha_hora_entrega', '>=', $fechaInicio)
            ->whereDate('fecha_hora_entrega', '<=', $fechaFin)
            ->sum('saldo_trasladado');

        return (float) $entrantes - (float) $salientes;
    }

    private function daniosClienteDeVehiculos($vehiculoIds, $fechaInicio, $fechaFin): array
    {
        $reparados = Mantenimiento::with(['incidencia', 'vehiculo:id,placa'])
            ->whereIn('vehiculo_id', $vehiculoIds)
            ->where('estado', '!=', EstadoMantenimientoEnum::CANCELADO->value)
            ->whereDate('fecha', '>=', $fechaInicio)
            ->whereDate('fecha', '<=', $fechaFin)
            ->whereHas('incidencia', function ($q) {
                $q->where('responsable_tipo', IncidenciaTipoResponsableEnum::CLIENTE->value)
                    ->where('estado_incidencia', '!=', IncidenciaEstadoEnum::ANULADA->value);
            })
            ->orderBy('fecha')
            ->get()
            ->map(fn ($mantenimiento) => [
                'placa'       => optional($mantenimiento->vehiculo)->placa,
                'fecha'       => $mantenimiento->fecha,
                'descripcion' => $mantenimiento->incidencia->descripcion,
                'cobrado'     => (float) $mantenimiento->incidencia->costo,
                'costo'       => (float) $mantenimiento->costo,
                'diferencia'  => round((float) $mantenimiento->incidencia->costo - (float) $mantenimiento->costo, 2),
                'pendiente'   => false,
            ]);

        $incidenciasConMantenimiento = Mantenimiento::whereNotNull('incidencia_id')
            ->where('estado', '!=', EstadoMantenimientoEnum::CANCELADO->value)
            ->pluck('incidencia_id');

        $pendientes = Incidencia::with('vehiculo:id,placa')
            ->whereIn('vehiculo_id', $vehiculoIds)
            ->where('responsable_tipo', IncidenciaTipoResponsableEnum::CLIENTE->value)
            ->whereNotIn('estado_incidencia', [
                IncidenciaEstadoEnum::ANULADA->value,
                IncidenciaEstadoEnum::RESUELTA->value,
            ])
            ->whereNotIn('id', $incidenciasConMantenimiento)
            ->whereDate('fecha', '>=', $fechaInicio)
            ->whereDate('fecha', '<=', $fechaFin)
            ->orderBy('fecha')
            ->get()
            ->map(fn ($incidencia) => [
                'placa'       => optional($incidencia->vehiculo)->placa,
                'fecha'       => $incidencia->fecha,
                'descripcion' => $incidencia->descripcion,
                'cobrado'     => (float) $incidencia->costo,
                'costo'       => null,
                'diferencia'  => null,
                'pendiente'   => true,
            ]);

        return [
            'diferencia' => round($reparados->sum('diferencia'), 2),
            'detalle'    => $reparados->concat($pendientes)->values(),
        ];
    }

    private function ocupacionDelPeriodo($fechaInicio, $fechaFin, $totalVehiculos): array
    {
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin    = Carbon::parse(min($fechaFin, now()->toDateString()))->endOfDay();

        $contratos = Contrato::with('cierreRenta:id,contrato_id,fecha_hora_recepcion')
            ->whereIn('estado_contrato', [
                EstadoContratoEnum::ACTIVO->value,
                EstadoContratoEnum::FINALIZADO->value,
            ])
            ->where('fecha_hora_entrega', '<=', $fin)
            ->get(['id', 'vehiculo_id', 'estado_contrato', 'fecha_hora_entrega', 'fecha_hora_devolucion']);

        $diasRentados = 0;
        $vehiculos    = collect();

        foreach ($contratos as $contrato) {
            if ($contrato->cierreRenta) {
                $salida = $contrato->cierreRenta->fecha_hora_recepcion;
            } elseif ($contrato->estado_contrato === EstadoContratoEnum::ACTIVO->value) {
                $salida = now()->max($contrato->fecha_hora_devolucion);
            } else {
                $salida = $contrato->fecha_hora_devolucion;
            }

            $desde = $contrato->fecha_hora_entrega->max($inicio);
            $hasta = $salida->min($fin);

            if ($hasta->lessThanOrEqualTo($desde)) {
                continue;
            }

            $diasRentados += max(1, (int) ceil(abs($desde->diffInHours($hasta)) / 24));
            $vehiculos->push($contrato->vehiculo_id);
        }

        $diasPeriodo = (int) abs($inicio->diffInDays($fin->copy()->startOfDay())) + 1;
        $capacidad   = $totalVehiculos * $diasPeriodo;

        return [
            'vehiculos' => $vehiculos->unique()->count(),
            'tasa'      => $capacidad > 0
                ? min(100, round($diasRentados / $capacidad * 100, 2))
                : 0,
        ];
    }

    // El empleado solo entra a reportes sin dinero (estado de flota, licencias, reservas canceladas)
    private function tienePermiso($incluirContador = false, $incluirEmpleado = true): bool
    {
        $userAuth = auth('api')->user();

        if (!$userAuth) {
            return false;
        }

        $permitido = $userAuth->hasRole(RolEnum::ADMINISTRADOR->value)
            || ($incluirEmpleado && $userAuth->hasRole(RolEnum::EMPLEADO->value));

        if ($incluirContador) {
            $permitido = $permitido || $userAuth->hasRole(RolEnum::CONTADOR->value);
        }

        return $permitido;
    }

    public function ingresos(ReporteRequest $request)
    {
        try {
            if (!$this->tienePermiso(true, false)) {
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

            $devoluciones = CierreRenta::with('contrato:id,numero_contrato')
                ->where('monto_devuelto', '>', 0)
                ->whereDate('fecha_hora_recepcion', '>=', $fechaInicio)
                ->whereDate('fecha_hora_recepcion', '<=', $fechaFin)
                ->orderBy('fecha_hora_recepcion')
                ->get();

            $totalCobrado  = $pagos->sum('monto');
            $totalDevuelto = $devoluciones->sum('monto_devuelto');
            $totalIngresos = $totalCobrado - $totalDevuelto;

            $pdf = Pdf::loadView('reportes.ingresos', [
                'pagos'         => $pagos,
                'devoluciones'  => $devoluciones,
                'totalCobrado'  => $totalCobrado,
                'totalDevuelto' => $totalDevuelto,
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

    public function estadoFlota(ReporteRequest $request)
    {
        try {
            if (!$this->tienePermiso(true)) {
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

    public function licenciasPorVencer(ReporteRequest $request)
    {
        try {
            if (!$this->tienePermiso(true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $dias = (int) ($request->dias ?? 30);

            $clientes = Cliente::whereDate('vencimiento_licencia', '>=', now()->toDateString())
                ->whereDate('vencimiento_licencia', '<=', now()->addDays($dias)->toDateString())
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

    /**
     * Resultado neto por propietario: ingresos - gastos.
     */
    public function resultadoNetoPorPropietario(ReporteRequest $request)
    {
        try {
            if (!$this->tienePermiso(true, false)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
            $fechaFin    = $request->fecha_fin ?? now()->endOfMonth()->format('Y-m-d');

            $propietarioBusqueda = trim((string) ($request->input('propietario') ?? ''));
            $propietarioId = $request->integer('propietario_id');

            if ($propietarioId && $propietarioBusqueda === '') {
                $propietarioBusqueda = (string) (Propietario::whereKey($propietarioId)->value('nombre') ?? '');
            }

            $porcentajeAdmin = (float) ($request->input('porcentaje_administracion') ?? 0);

            if ($porcentajeAdmin < 0 || $porcentajeAdmin > 100) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El porcentaje de administración debe estar entre 0 y 100',
                ], 422);
            }

            $propietarios = Propietario::with('vehiculos.modelo.marca')
                ->when($propietarioId, function ($query) use ($propietarioId) {
                    $query->whereKey($propietarioId);
                }, function ($query) use ($propietarioBusqueda) {
                    $query->when($propietarioBusqueda !== '', function ($q) use ($propietarioBusqueda) {
                        $q->where('nombre', 'like', "%{$propietarioBusqueda}%");
                    });
                })
                ->get()
                ->map(function ($propietario) use ($fechaInicio, $fechaFin, $porcentajeAdmin) {
                    $vehiculoIds = $propietario->vehiculos->pluck('id');

                    $ingresos = Pago::whereHas('contrato', function ($q) use ($vehiculoIds) {
                        $q->whereIn('vehiculo_id', $vehiculoIds);
                    })
                        ->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                        ->whereDate('fecha_pago', '>=', $fechaInicio)
                        ->whereDate('fecha_pago', '<=', $fechaFin)
                        ->sum('monto');

                    $ingresos += $this->trasladosDeVehiculos($vehiculoIds, $fechaInicio, $fechaFin);

                    $devoluciones = $this->devolucionesDeVehiculos($vehiculoIds, $fechaInicio, $fechaFin);
                    $danios       = $this->daniosClienteDeVehiculos($vehiculoIds, $fechaInicio, $fechaFin);
                    $ingresoNeto  = $ingresos - $devoluciones;

                    $mantenimientos = Mantenimiento::with('vehiculo:id,placa')
                        ->whereIn('vehiculo_id', $vehiculoIds)
                        ->where('estado', '!=', EstadoMantenimientoEnum::CANCELADO->value)
                        ->whereDate('fecha', '>=', $fechaInicio)
                        ->whereDate('fecha', '<=', $fechaFin)
                        ->get(['id', 'vehiculo_id', 'tipo_mantenimiento', 'descripcion', 'costo', 'fecha', 'lugar']);

                    $incidenciasReparadas = Mantenimiento::whereIn('vehiculo_id', $vehiculoIds)
                        ->whereNotNull('incidencia_id')
                        ->where('estado', '!=', EstadoMantenimientoEnum::CANCELADO->value)
                        ->pluck('incidencia_id');

                    $incidenciasNegocio = Incidencia::with('vehiculo:id,placa')
                        ->whereIn('vehiculo_id', $vehiculoIds)
                        ->where('responsable_tipo', IncidenciaTipoResponsableEnum::NEGOCIO->value)
                        ->where('estado_incidencia', '!=', IncidenciaEstadoEnum::ANULADA->value)
                        ->whereNotIn('id', $incidenciasReparadas)
                        ->whereDate('fecha', '>=', $fechaInicio)
                        ->whereDate('fecha', '<=', $fechaFin)
                        ->get(['id', 'vehiculo_id', 'tipo_incidencia', 'descripcion', 'costo', 'fecha']);

                    $gastoIncidencias    = $incidenciasNegocio->sum('costo');
                    $gastoMantenimientos = $mantenimientos->sum('costo');
                    $gastoTotal          = $gastoIncidencias + $gastoMantenimientos;

                    $aplicaAdministracion = $propietario->tipo_propietario !== TipoPropietarioEnum::PROPIO->value;

                    $montoAdministracion = $aplicaAdministracion
                        ? round($ingresoNeto * $porcentajeAdmin / 100, 2)
                        : 0;

                    return [
                        'propietario'            => $propietario,
                        'num_vehiculos'          => $vehiculoIds->count(),
                        'ingresos'               => $ingresos,
                        'devoluciones'           => $devoluciones,
                        'gasto_incidencias'      => $gastoIncidencias,
                        'incidencias_detalle'    => $incidenciasNegocio,
                        'gasto_mantenimientos'   => $gastoMantenimientos,
                        'mantenimientos_detalle' => $mantenimientos,
                        'gasto_total'            => $gastoTotal,
                        'diferencia_danios'      => $danios['diferencia'],
                        'danios_detalle'         => $danios['detalle'],
                        'aplica_administracion'  => $aplicaAdministracion,
                        'monto_administracion'   => $montoAdministracion,
                        'resultado_neto'         => $ingresoNeto - $gastoTotal - $montoAdministracion,
                    ];
                })
                ->filter(fn ($item) => $propietarioId
                    || $item['propietario']->estado === EstadoPropietarioEnum::ACTIVO->value
                    || $item['ingresos'] != 0
                    || $item['devoluciones'] != 0
                    || $item['gasto_total'] != 0
                    || $item['danios_detalle']->isNotEmpty())
                ->sortByDesc('resultado_neto')
                ->values();

            $totalIngresos       = $propietarios->sum('ingresos');
            $totalDevoluciones   = $propietarios->sum('devoluciones');
            $totalIncidencias    = $propietarios->sum('gasto_incidencias');
            $totalMantenimientos = $propietarios->sum('gasto_mantenimientos');
            $totalGastos         = $propietarios->sum('gasto_total');
            $totalDiferenciaDanios = $propietarios->sum('diferencia_danios');
            $totalAdministracion = $propietarios->sum('monto_administracion');
            $totalNeto           = $propietarios->sum('resultado_neto');

            $pdf = Pdf::loadView('reportes.resultado-neto-por-propietario', [
                'propietarios'        => $propietarios,
                'totalIngresos'       => $totalIngresos,
                'totalDevoluciones'   => $totalDevoluciones,
                'totalIncidencias'    => $totalIncidencias,
                'totalMantenimientos' => $totalMantenimientos,
                'totalGastos'         => $totalGastos,
                'totalDiferenciaDanios' => $totalDiferenciaDanios,
                'totalAdministracion' => $totalAdministracion,
                'totalNeto'           => $totalNeto,
                'porcentajeAdmin'     => $porcentajeAdmin,
                'fechaInicio'         => $fechaInicio,
                'fechaFin'            => $fechaFin,
                'propietarioBusqueda' => $propietarioBusqueda,
            ]);

            $pdf->setPaper('letter', 'landscape');

            return $pdf->stream('reporte-resultado-neto-por-propietario.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    public function reservasCanceladas(ReporteRequest $request)
    {
        try {
            if (!$this->tienePermiso(true)) {
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

    /**
     * Desempeño general: resumen ejecutivo del negocio.
     */
    public function desempenoGeneral(ReporteRequest $request)
    {
        try {
            if (!$this->tienePermiso(true, false)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
            $fechaFin    = $request->fecha_fin ?? now()->endOfMonth()->format('Y-m-d');

            $totalCobrado = Pago::where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                ->whereDate('fecha_pago', '>=', $fechaInicio)
                ->whereDate('fecha_pago', '<=', $fechaFin)
                ->sum('monto');

            $totalDevoluciones = $this->devolucionesDeVehiculos(null, $fechaInicio, $fechaFin);
            $totalIngresos     = $totalCobrado - $totalDevoluciones;

            $totalContratos = Contrato::where('estado_contrato', '!=', EstadoContratoEnum::ANULADO->value)
                ->whereNull('contrato_origen_id')
                ->whereDate('fecha_hora_entrega', '>=', $fechaInicio)
                ->whereDate('fecha_hora_entrega', '<=', $fechaFin)
                ->count();

            $totalVehiculos = Vehiculo::count();

            $ocupacion = $this->ocupacionDelPeriodo($fechaInicio, $fechaFin, $totalVehiculos);

            $vehiculosRentados = $ocupacion['vehiculos'];
            $tasaOcupacion     = $ocupacion['tasa'];

            $totalClientesNuevos = Cliente::whereDate('created_at', '>=', $fechaInicio)
                ->whereDate('created_at', '<=', $fechaFin)
                ->count();

            $gastos = $this->gastosDeVehiculos(null, $fechaInicio, $fechaFin);
            $totalGastosIncidencias    = $gastos['incidencias'];
            $totalGastosMantenimientos = $gastos['mantenimientos'];
            $totalGastos               = $gastos['total'];

            $pdf = Pdf::loadView('reportes.desempeno-general', [
                'fechaInicio'            => $fechaInicio,
                'fechaFin'               => $fechaFin,
                'totalCobrado'           => $totalCobrado,
                'totalDevoluciones'      => $totalDevoluciones,
                'totalIngresos'          => $totalIngresos,
                'totalContratos'         => $totalContratos,
                'totalVehiculos'         => $totalVehiculos,
                'vehiculosRentados'      => $vehiculosRentados,
                'tasaOcupacion'          => $tasaOcupacion,
                'totalClientesNuevos'    => $totalClientesNuevos,
                'totalGastosIncidencias'    => $totalGastosIncidencias,
                'totalGastosMantenimientos' => $totalGastosMantenimientos,
                'totalGastos'               => $totalGastos,
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

    public function ingresosPorVehiculo(ReporteRequest $request)
    {
        try {
            if (!$this->tienePermiso(true, false)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
            $fechaFin    = $request->fecha_fin ?? now()->endOfMonth()->format('Y-m-d');

            $propietarioBusqueda = trim((string) (
                $request->input('propietario')
                ?? $request->input('propietarios')
                ?? ''
            ));
            $propietarioId = $request->integer('propietario_id');

            if ($propietarioId && $propietarioBusqueda === '') {
                $propietarioBusqueda = (string) (Propietario::whereKey($propietarioId)->value('nombre') ?? '');
            }

            $vehiculos = Vehiculo::with(['modelo.marca', 'propietario'])
                ->when($propietarioId, function ($query) use ($propietarioId) {
                    $query->where('propietario_id', $propietarioId);
                }, function ($query) use ($propietarioBusqueda) {
                    $query->when($propietarioBusqueda !== '', function ($q) use ($propietarioBusqueda) {
                        $q->whereHas('propietario', function ($p) use ($propietarioBusqueda) {
                            $p->where('nombre', 'like', "%{$propietarioBusqueda}%");
                        });
                    });
                })
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
                        ->where('estado_contrato', '!=', EstadoContratoEnum::ANULADO->value)
                        ->whereDate('fecha_hora_entrega', '>=', $fechaInicio)
                        ->whereDate('fecha_hora_entrega', '<=', $fechaFin)
                        ->count();

                    $ingresos += $this->trasladosDeVehiculos([$vehiculo->id], $fechaInicio, $fechaFin);

                    $devoluciones = $this->devolucionesDeVehiculos([$vehiculo->id], $fechaInicio, $fechaFin);

                    return [
                        'vehiculo'     => $vehiculo,
                        'ingresos'     => $ingresos,
                        'devoluciones' => $devoluciones,
                        'ingreso_neto' => $ingresos - $devoluciones,
                        'num_rentas'   => $rentas,
                    ];
                })
                ->sortByDesc('ingreso_neto')
                ->values();

            $totalCobrado      = $vehiculos->sum('ingresos');
            $totalDevoluciones = $vehiculos->sum('devoluciones');
            $totalGeneral      = $vehiculos->sum('ingreso_neto');

            $propietario = null;
            if ($propietarioId) {
                $propietario = Propietario::find($propietarioId);
            } elseif ($propietarioBusqueda !== '' && $vehiculos->isNotEmpty()) {
                $propietario = optional($vehiculos->first()['vehiculo'])->propietario;
            }

            $pdf = Pdf::loadView('reportes.ingresos-por-vehiculo', [
                'vehiculos'           => $vehiculos,
                'totalCobrado'        => $totalCobrado,
                'totalDevoluciones'   => $totalDevoluciones,
                'totalGeneral'        => $totalGeneral,
                'fechaInicio'         => $fechaInicio,
                'fechaFin'            => $fechaFin,
                'propietario'         => $propietario,
                'propietarioBusqueda' => $propietarioBusqueda,
            ]);

            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream('reporte-ingresos-por-vehiculo.pdf');
        } catch (\Throwable $e) {
            \Log::error('Error en reporte ingresos por vehiculo', [
                'exception' => $e,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Gastos por vehículo: incidencias del negocio y mantenimientos.
     */
    public function gastosPorVehiculo(ReporteRequest $request)
    {
        try {
            if (!$this->tienePermiso(true, false)) {
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
                    $gastos = $this->gastosDeVehiculos([$vehiculo->id], $fechaInicio, $fechaFin);

                    return [
                        'vehiculo'                  => $vehiculo,
                        'gasto_incidencias_negocio' => $gastos['incidencias'],
                        'gasto_mantenimientos'      => $gastos['mantenimientos'],
                        'gasto_total'               => $gastos['total'],
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
    public function resultadoNetoPorVehiculo(ReporteRequest $request)
    {
        try {
            if (!$this->tienePermiso(true, false)) {
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

                    $ingresos += $this->trasladosDeVehiculos([$vehiculo->id], $fechaInicio, $fechaFin);

                    $gastoTotal   = $this->gastosDeVehiculos([$vehiculo->id], $fechaInicio, $fechaFin)['total'];
                    $devoluciones = $this->devolucionesDeVehiculos([$vehiculo->id], $fechaInicio, $fechaFin);
                    $danios       = $this->daniosClienteDeVehiculos([$vehiculo->id], $fechaInicio, $fechaFin);

                    return [
                        'vehiculo'       => $vehiculo,
                        'ingresos'       => $ingresos,
                        'devoluciones'   => $devoluciones,
                        'gastos'         => $gastoTotal,
                        'diferencia_danios' => $danios['diferencia'],
                        'resultado_neto' => $ingresos - $devoluciones - $gastoTotal,
                    ];
                })
                ->sortByDesc('resultado_neto')
                ->values();

            $totalIngresos     = $vehiculos->sum('ingresos');
            $totalDevoluciones = $vehiculos->sum('devoluciones');
            $totalGastos       = $vehiculos->sum('gastos');
            $totalDiferenciaDanios = $vehiculos->sum('diferencia_danios');
            $totalNeto         = $vehiculos->sum('resultado_neto');

            $pdf = Pdf::loadView('reportes.resultado-neto-por-vehiculo', [
                'vehiculos'         => $vehiculos,
                'totalIngresos'     => $totalIngresos,
                'totalDevoluciones' => $totalDevoluciones,
                'totalGastos'   => $totalGastos,
                'totalDiferenciaDanios' => $totalDiferenciaDanios,
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
    public function saldosPendientes(ReporteRequest $request)
    {
        try {
            if (!$this->tienePermiso(true, false)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este reporte',
                ], 403);
            }

            $contratos = Contrato::with([
                'cliente:id,nombre,dui,telefono',
                'vehiculo:id,placa,color',
                'pagos' => function ($query) {
                    $query->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value);
                },
            ])
                ->where('estado_pago', '!=', EstadoPagoEnum::PAGADO->value)
                ->where('estado_contrato', '!=', EstadoContratoEnum::ANULADO->value)
                ->get()
                ->map(function ($contrato) {
                    $totalPagado = $contrato->montoPagado();

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
