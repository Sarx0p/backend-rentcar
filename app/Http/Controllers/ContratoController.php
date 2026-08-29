<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\EstadoReservaEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Enums\IncidenciaEstadoEnum;
use App\Http\Requests\ContratoController\StoreContratoRequest;
use App\Http\Requests\ContratoController\StoreContratoDirectoRequest;
use App\Models\Contrato;
use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

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
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver los contratos',
                ], 403);
            }

            $contratos = Contrato::with([
                'cliente:id,nombre,dui,telefono,municipio_id,numero_licencia',
                'cliente.municipio.departamento',
                'vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'vehiculo.modelo:id,nombre,marca_id',
                'vehiculo.modelo.marca:id,nombre',
                'vehiculo.categoria:id,nombre,precio_dia',
                'reserva:id,fecha_inicio,fecha_fin',
                'user:id,nombre,apellido',
            ])
                ->when($request->search, function ($query, $search) {
                    $query->where('numero_contrato', 'like', '%' . $search . '%')
                        ->orWhere('estado_contrato', 'like', '%' . $search . '%')
                        ->orWhereHas('cliente', function ($q) use ($search) {
                            $q->where('nombre', 'like', '%' . $search . '%')
                                ->orWhere('dui', 'like', '%' . $search . '%');
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
                    WHEN 'VENCIDO' THEN 2
                    WHEN 'PENDIENTE' THEN 3
                    WHEN 'FINALIZADO' THEN 4
                    WHEN 'ANULADO' THEN 5
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
            $reserva = Reserva::with(['vehiculo', 'cliente'])->findOrFail($request->reserva_id);

            if ($reserva->estado !== EstadoReservaEnum::PENDIENTE->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Solo se puede crear un contrato para una reserva en estado PENDIENTE',
                ], 422);
            }

            if ($reserva->contrato) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Esta reserva ya tiene un contrato generado',
                ], 422);
            }

            $vehiculo = $reserva->vehiculo;

            if ($vehiculo->estado !== VehiculoEstadoEnum::DISPONIBLE->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vehículo no está disponible, estado actual: ' . $vehiculo->estado,
                ], 422);
            }

            $contratoTraslapado = Contrato::where('vehiculo_id', $vehiculo->id)
                ->where('estado_contrato', EstadoContratoEnum::ACTIVO->value)
                ->where(function ($query) use ($request) {
                    $query->where('fecha_hora_entrega', '<', $request->fecha_hora_devolucion)
                        ->where('fecha_hora_devolucion', '>', $request->fecha_hora_entrega);
                })
                ->exists();

            if ($contratoTraslapado) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vehículo ya tiene un contrato activo que se traslapa con esas fechas',
                ], 422);
            }

            // Validar que el cliente no tenga otro contrato activo
            $tieneContratoActivo = Contrato::where('cliente_id', $reserva->cliente_id)
                ->where('estado_contrato', EstadoContratoEnum::ACTIVO->value)
                ->exists();

            if ($tieneContratoActivo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Este cliente ya tiene un contrato activo. No se puede generar otro hasta cerrarlo.',
                ], 422);
            }

            $contrato = DB::transaction(function () use ($request, $reserva, $vehiculo) {
                $inicio = \Carbon\Carbon::parse($request->fecha_hora_entrega);
                $fin    = \Carbon\Carbon::parse($request->fecha_hora_devolucion);
                $dias   = max(1, $inicio->diffInDays($fin));

                $descuento  = $request->monto_descuento ?? 0;
                $montoTotal = ($dias * $request->precio_por_dia) - $descuento;

                // Obtener incidencias pendientes (REPORTADA) del vehículo
                $incidenciasPendientes = $vehiculo->incidencias()
                    ->where('estado_incidencia', IncidenciaEstadoEnum::REPORTADA->value)
                    ->get(['tipo_incidencia', 'descripcion', 'fecha']);

                // Construir texto de incidencias
                $textoIncidencias = $incidenciasPendientes->isNotEmpty()
                    ? 'Incidencias previas registradas: ' .
                    $incidenciasPendientes->map(function ($inc) {
                        return "[{$inc->tipo_incidencia}] {$inc->descripcion} ({$inc->fecha->format('d/m/Y')})";
                    })->implode(' | ')
                    : null;

                // Concatenar observaciones del usuario con el texto de incidencias
                $observacionesFinal = trim(
                    ($request->observaciones_entrega ?? '') . ' ' . ($textoIncidencias ?? '')
                );

                $contrato = Contrato::create([
                    'numero_contrato'           => $this->generarNumeroContrato(),
                    'cliente_id'                => $reserva->cliente_id,
                    'vehiculo_id'               => $vehiculo->id,
                    'reserva_id'                => $reserva->id,
                    'fecha_hora_entrega'        => $request->fecha_hora_entrega,
                    'fecha_hora_devolucion'     => $request->fecha_hora_devolucion,
                    'dias_acordados'            => $dias,
                    'precio_por_dia'            => $request->precio_por_dia,
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
                'cliente:id,nombre,dui,telefono,municipio_id,numero_licencia',
                'cliente.municipio.departamento',
                'vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'vehiculo.modelo:id,nombre,marca_id',
                'vehiculo.modelo.marca:id,nombre',
                'vehiculo.categoria:id,nombre,precio_dia',
                'reserva:id,fecha_inicio,fecha_fin',
                'user:id,nombre,apellido',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Contrato creado con éxito desde la reserva',
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
            $cliente = Cliente::findOrFail($request->cliente_id);

            if ($cliente->vencimiento_licencia->isPast()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El cliente tiene la licencia vencida, no puede rentar un vehículo',
                ], 422);
            }

            $tieneContratoActivo = Contrato::where('cliente_id', $cliente->id)
                ->where('estado_contrato', EstadoContratoEnum::ACTIVO->value)
                ->exists();

            if ($tieneContratoActivo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Este cliente ya tiene un contrato activo. No se puede generar otro hasta cerrarlo.',
                ], 422);
            }

            $vehiculo = Vehiculo::findOrFail($request->vehiculo_id);

            if ($vehiculo->estado !== VehiculoEstadoEnum::DISPONIBLE->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vehículo no está disponible, estado actual: ' . $vehiculo->estado,
                ], 422);
            }

            // Calcular fechas del nuevo contrato
            $fechaEntrega    = now();
            $fechaDevolucion = $fechaEntrega->copy()->addDays($request->dias_acordados);

            // Validar traslape de fechas con contratos activos del mismo vehículo
            $contratoTraslapado = Contrato::where('vehiculo_id', $vehiculo->id)
                ->where('estado_contrato', EstadoContratoEnum::ACTIVO->value)
                ->where(function ($query) use ($fechaEntrega, $fechaDevolucion) {
                    $query->where('fecha_hora_entrega', '<', $fechaDevolucion)
                        ->where('fecha_hora_devolucion', '>', $fechaEntrega);
                })
                ->exists();

            if ($contratoTraslapado) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vehículo ya tiene un contrato activo que se traslapa con esas fechas',
                ], 422);
            }

            // NUEVO: validar que no choque con una reserva futura del mismo vehículo
            $reservaTraslapada = Reserva::where('vehiculo_id', $vehiculo->id)
                ->whereIn('estado', [EstadoReservaEnum::PENDIENTE->value, EstadoReservaEnum::CONFIRMADA->value])
                ->where(function ($query) use ($fechaEntrega, $fechaDevolucion) {
                    $query->where('fecha_inicio', '<', $fechaDevolucion)
                        ->where('fecha_fin', '>', $fechaEntrega);
                })
                ->exists();

            if ($reservaTraslapada) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vehículo tiene una reserva que se traslapa con estas fechas. No se puede generar un contrato directo.',
                ], 422);
            }

            //validar que el cliente no tenga otra reserva activa que se traslape con estas fechas (evita duplicidad)
            $reservaClienteTraslapada = Reserva::where('cliente_id', $request->cliente_id)
                ->whereIn('estado', [EstadoReservaEnum::PENDIENTE->value, EstadoReservaEnum::CONFIRMADA->value])
                ->where(function ($query) use ($fechaEntrega, $fechaDevolucion) {
                    $query->where('fecha_inicio', '<', $fechaDevolucion)
                        ->where('fecha_fin', '>', $fechaEntrega);
                })
                ->exists();

            if ($reservaClienteTraslapada) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Este cliente ya tiene una reserva activa que se traslapa con estas fechas. Genera el contrato desde esa reserva o cancélala primero.',
                ], 422);
            }

            // Obtener incidencias pendientes ANTES de la transacción
            $incidenciasPendientes = $vehiculo->incidencias()
                ->where('estado_incidencia', IncidenciaEstadoEnum::REPORTADA->value)
                ->get(['id', 'tipo_incidencia', 'descripcion', 'fecha']);

            $contrato = DB::transaction(function () use ($request, $vehiculo, $fechaEntrega, $fechaDevolucion, $incidenciasPendientes) {
                $descuento  = $request->monto_descuento ?? 0;
                $montoTotal = ($request->dias_acordados * $request->precio_por_dia) - $descuento;

                $textoIncidencias = $incidenciasPendientes->isNotEmpty()
                    ? 'Incidencias previas registradas: ' .
                    $incidenciasPendientes->map(function ($inc) {
                        return "[{$inc->tipo_incidencia}] {$inc->descripcion} ({$inc->fecha->format('d/m/Y')})";
                    })->implode(' | ')
                    : null;

                $observacionesFinal = trim(
                    ($request->observaciones_entrega ?? '') . ' ' . ($textoIncidencias ?? '')
                );

                $contrato = Contrato::create([
                    'numero_contrato'           => $this->generarNumeroContrato(),
                    'cliente_id'                => $request->cliente_id,
                    'vehiculo_id'               => $vehiculo->id,
                    'reserva_id'                => null,
                    'fecha_hora_entrega'        => $fechaEntrega,
                    'fecha_hora_devolucion'     => $fechaDevolucion,
                    'dias_acordados'            => $request->dias_acordados,
                    'precio_por_dia'            => $request->precio_por_dia,
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
                'cliente:id,nombre,dui,telefono,municipio_id,numero_licencia',
                'cliente.municipio.departamento',
                'vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'vehiculo.modelo:id,nombre,marca_id',
                'vehiculo.modelo.marca:id,nombre',
                'vehiculo.categoria:id,nombre,precio_dia',
                'user:id,nombre,apellido',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Contrato directo creado con éxito',
                'advertencia' => $incidenciasPendientes->isNotEmpty()
                    ? 'Este vehículo tiene incidencias pendientes sin resolver.'
                    : null,
                'incidencias_pendientes' => $incidenciasPendientes,
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
                    'message' => 'No tienes permiso para ver este contrato',
                ], 403);
            }

            $contrato = Contrato::with([
                'cliente:id,nombre,dui,telefono,municipio_id,numero_licencia',
                'cliente.municipio.departamento',
                'vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'vehiculo.modelo:id,nombre,marca_id',
                'vehiculo.modelo.marca:id,nombre',
                'vehiculo.categoria:id,nombre,precio_dia',
                'reserva:id,fecha_inicio,fecha_fin',
                'user:id,nombre,apellido',
                'pagos:id,contrato_id,monto,estado_transaccion',
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
            $contrato = Contrato::with([
                'cliente.municipio.departamento',
                'vehiculo.modelo.marca',
                'vehiculo.categoria',
                'user',
            ])->findOrFail($id);

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
        $ultimoContrato = Contrato::latest()->first();
        $numero = $ultimoContrato
            ? str_pad((intval(substr($ultimoContrato->numero_contrato, -4)) + 1), 4, '0', STR_PAD_LEFT)
            : '0001';

        return 'CONT-' . date('Y') . '-' . $numero;
    }
}
