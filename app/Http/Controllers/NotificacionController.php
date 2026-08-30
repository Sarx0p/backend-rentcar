<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\IncidenciaEstadoEnum;
use App\Enums\SeguroEstadoEnum;
use App\Models\Cliente;
use App\Models\Seguro;
use App\Models\Contrato;
use App\Models\Incidencia;
use App\Models\Reserva;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
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
                    'message' => 'No tienes permiso para ver las notificaciones',
                ], 403);
            }

            $diasLicencia = (int) ($request->dias_licencia ?? 30);
            $diasSeguro   = (int) ($request->dias_seguro ?? 30);
            $horasReserva = (int) ($request->horas_reserva ?? 24);

            $licenciasPorVencer = Cliente::whereBetween('vencimiento_licencia', [
                now(), now()->addDays($diasLicencia),
            ])
                ->orderBy('vencimiento_licencia')
                ->get(['id', 'nombre', 'dui', 'vencimiento_licencia'])
                ->map(fn ($cliente) => [
                    'tipo'      => 'LICENCIA_POR_VENCER',
                    'prioridad' => now()->diffInDays($cliente->vencimiento_licencia) <= 7 ? 'ALTA' : 'MEDIA',
                    'mensaje'   => "La licencia de {$cliente->nombre} vence el {$cliente->vencimiento_licencia->format('d/m/Y')}",
                    'cliente_id' => $cliente->id,
                ]);

            $segurosPorVencer = Seguro::with('vehiculo:id,placa')
                ->where('estado', SeguroEstadoEnum::VIGENTE->value)
                ->whereBetween('fecha_vencimiento', [
                    now(), now()->addDays($diasSeguro),
                ])
                ->orderBy('fecha_vencimiento')
                ->get()
                ->map(fn ($seguro) => [
                    'tipo'      => 'SEGURO_POR_VENCER',
                    'prioridad' => now()->diffInDays($seguro->fecha_vencimiento) <= 7 ? 'ALTA' : 'MEDIA',
                    'mensaje'   => "El seguro del vehículo {$seguro->vehiculo->placa} vence el {$seguro->fecha_vencimiento->format('d/m/Y')}",
                    'seguro_id' => $seguro->id,
                    'vehiculo_id' => $seguro->vehiculo_id,
                ]);

            $pagosPendientes = Contrato::with('cliente:id,nombre')
                ->where('estado_contrato', EstadoContratoEnum::ACTIVO->value)
                ->whereIn('estado_pago', [EstadoPagoEnum::PENDIENTE->value, EstadoPagoEnum::PARCIAL->value])
                ->get()
                ->map(fn ($contrato) => [
                    'tipo'      => 'PAGO_' . $contrato->estado_pago,
                    'prioridad' => $contrato->estado_pago === EstadoPagoEnum::PENDIENTE->value ? 'ALTA' : 'MEDIA',
                    'mensaje'   => "El contrato {$contrato->numero_contrato} de {$contrato->cliente->nombre} tiene pago {$contrato->estado_pago}",
                    'contrato_id' => $contrato->id,
                ]);

            $incidenciasPendientes = Incidencia::with('vehiculo:id,placa')
                ->where('estado_incidencia', IncidenciaEstadoEnum::REPORTADA->value)
                ->get()
                ->map(fn ($incidencia) => [
                    'tipo'      => 'INCIDENCIA_PENDIENTE',
                    'prioridad' => 'MEDIA',
                    'mensaje'   => "Incidencia sin resolver en el vehículo {$incidencia->vehiculo->placa}: {$incidencia->tipo_incidencia}",
                    'incidencia_id' => $incidencia->id,
                    'vehiculo_id'   => $incidencia->vehiculo_id,
                ]);

            $reservasRecientes = Reserva::with('cliente:id,nombre')
                ->where('created_at', '>=', now()->subHours($horasReserva))
                ->get()
                ->map(fn ($reserva) => [
                    'tipo'      => 'RESERVA_CREADA',
                    'prioridad' => 'BAJA',
                    'mensaje'   => "Nueva reserva de {$reserva->cliente->nombre} ({$reserva->fecha_inicio->format('d/m/Y')} - {$reserva->fecha_fin->format('d/m/Y')})",
                    'reserva_id' => $reserva->id,
                ]);

            $notificaciones = collect()
                ->merge($licenciasPorVencer)
                ->merge($segurosPorVencer)
                ->merge($pagosPendientes)
                ->merge($incidenciasPendientes)
                ->merge($reservasRecientes)
                ->sortBy(fn ($n) => match ($n['prioridad']) {
                    'ALTA'  => 1,
                    'MEDIA' => 2,
                    'BAJA'  => 3,
                })
                ->values();

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'total'           => $notificaciones->count(),
                    'notificaciones'  => $notificaciones,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
