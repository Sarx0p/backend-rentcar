<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\EstadoTransaccionEnum;
use App\Models\Pago;
use App\Models\Vehiculo;
use App\Models\Cliente;
use App\Models\Cancelacion;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    //reporte de ingresos

    public function ingresos(Request $request)
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value) &&
                !$userAuth->hasRole(Rolenum::CONTADOR->value)
            ) {
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
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    //licencias por vencer
    public function estadoFlota(Request $request)
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
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
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    //licencias por vencer
    public function licenciasPorVencer(Request $request)
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
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
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    //reservas canceldas
    public function reservasCanceladas(Request $request)
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
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
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    
}
