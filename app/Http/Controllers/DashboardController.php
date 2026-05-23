<?php

namespace App\Http\Controllers;

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

        $reservasDelDia = Reserva::whereDate('fecha_inicio', $hoy)->count();

        $reservasDelMes = Reserva::whereMonth('fecha_inicio', Carbon::now()->month)
            ->whereYear('fecha_inicio', Carbon::now()->year)
            ->count();

        $clientesRegistrados = Cliente::count();

        $vehiculosDisponibles = Vehiculo::where(
            'estado',
            VehiculoEstadoEnum::DISPONIBLE->value
        )->count();

        $vehiculosOcupados = Vehiculo::whereIn('estado', [
            VehiculoEstadoEnum::RESERVADO->value,
            VehiculoEstadoEnum::RENTADO->value,
        ])->count();

        $reservasPorMes = Reserva::selectRaw('MONTH(fecha_inicio) as mes, COUNT(*) as total')
            ->whereYear('fecha_inicio', Carbon::now()->year)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $vehiculosPorEstado = Vehiculo::selectRaw('estado, COUNT(*) as total')
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
                'reservas_dia' => $reservasDelDia,
                'reservas_mes' => $reservasDelMes,
                'clientes_registrados' => $clientesRegistrados,
                'vehiculos_disponibles' => $vehiculosDisponibles,
                'vehiculos_ocupados' => $vehiculosOcupados,
            ],
            'graficas' => [
                'reservas_por_mes' => $reservasPorMes,
                'vehiculos_por_estado' => $vehiculosPorEstado,
            ],
            'ultimas_reservas' => $ultimasReservas,
        ]);
    }
}
