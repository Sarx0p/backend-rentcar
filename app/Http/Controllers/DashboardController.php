<?php

namespace App\Http\Controllers;

use App\Enums\EstadoReservaEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Models\Cliente;
use App\Models\Reserva;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function resumen(): JsonResponse
    {
        $hoy = Carbon::today();

        // 1. Reservas del día (omite canceladas y concluidas)
        $reservasDelDia = Reserva::whereDate('fecha_inicio', $hoy)
            ->whereNotIn('estado', [
                EstadoReservaEnum::CANCELADA->value,
                EstadoReservaEnum::CONCLUIDA->value,
            ])
            ->count();

        // 2. Reservas del mes (omite canceladas y concluidas)
        $reservasDelMes = Reserva::whereMonth('fecha_inicio', Carbon::now()->month)
            ->whereYear('fecha_inicio', Carbon::now()->year)
            ->whereNotIn('estado', [
                EstadoReservaEnum::CANCELADA->value,
                EstadoReservaEnum::CONCLUIDA->value,
            ])
            ->count();

        $clientesRegistrados = Cliente::count();

        $vehiculosDisponibles = Vehiculo::where(
            'estado',
            VehiculoEstadoEnum::DISPONIBLE->value
        )->count();

        $vehiculosOcupados = Vehiculo::where(
            'estado',
            VehiculoEstadoEnum::RENTADO->value
        )->count();

        $reservasPorMes = Reserva::whereYear('fecha_inicio', Carbon::now()->year)
            ->whereNotIn('estado', [EstadoReservaEnum::CANCELADA->value])
            ->get(['fecha_inicio'])
            ->groupBy(function ($reserva) {
                return (int) Carbon::parse($reserva->fecha_inicio)->format('m');
            })
            ->map(function ($items, $mes) {
                return [
                    'mes'   => $mes,
                    'total' => $items->count(),
                ];
            })
            ->values();

        $vehiculosPorEstado = Vehiculo::select('estado')
            ->selectRaw('count(*) as total')
            ->groupBy('estado')
            ->orderBy('estado')
            ->get();

        $ultimasReservas = Reserva::with([
                'cliente:id,nombre,dui,telefono',
                'vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'vehiculo.modelo:id,nombre,marca_id',
                'vehiculo.modelo.marca:id,nombre',
                'vehiculo.categoria:id,nombre,precio_dia',
            ])
            ->orderByDesc('id')
            ->take(5)
            ->get();

        return response()->json([
            'resumen' => [
                'reservas_dia'         => $reservasDelDia,
                'reservas_mes'         => $reservasDelMes,
                'clientes_registrados' => $clientesRegistrados,
                'vehiculos_disponibles' => $vehiculosDisponibles,
                'vehiculos_ocupados'    => $vehiculosOcupados,
            ],
            'graficas' => [
                'reservas_por_mes'     => $reservasPorMes,
                'vehiculos_por_estado' => $vehiculosPorEstado,
            ],
            'ultimas_reservas' => $ultimasReservas,
        ]);
    }
}
