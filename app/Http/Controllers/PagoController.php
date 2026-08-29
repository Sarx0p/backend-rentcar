<?php

namespace App\Http\Controllers;

use App\Enums\EstadoContratoEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\EstadoTransaccionEnum;
use App\Enums\RolEnum;
use App\Http\Requests\PagoController\StorePagoRequest;
use App\Models\Contrato;
use App\Models\Pago;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
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
                    'message' => 'No tienes permiso para ver los pagos',
                ], 403);
            }

            $pagos = Pago::with([
                'contrato:id,numero_contrato,monto_total_renta,estado_pago',
            ])
                ->when($request->search, function ($query, $search) {
                    $query->where('metodo_pago', 'like', '%' . $search . '%')
                        ->orWhere('estado_transaccion', 'like', '%' . $search . '%')
                        ->orWhereHas('contrato', function ($q) use ($search) {
                            $q->where('numero_contrato', 'like', '%' . $search . '%');
                        });
                })
                ->when($request->estado_transaccion, function ($query, $estado) {
                    $query->where('estado_transaccion', $estado);
                })
                ->when($request->metodo_pago, function ($query, $metodo) {
                    $query->where('metodo_pago', $metodo);
                })
                ->when($request->fecha_inicio && $request->fecha_fin, function ($query) use ($request) {
                    $query->whereDate('fecha_pago', '>=', $request->fecha_inicio)
                        ->whereDate('fecha_pago', '<=', $request->fecha_fin);
                })
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $pagos,
            ], 200);
        } catch (\Exception) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePagoRequest $request)
    {
        try {

            $contrato = Contrato::findOrFail($request->contrato_id);

            if ($contrato->estado_contrato !== EstadoContratoEnum::ACTIVO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Solo se pueden registrar pagos en contratos ACTIVOS',
                ], 422);
            }

            if ($contrato->estado_pago === EstadoPagoEnum::PAGADO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Este contrato ya está completamente pagado',
                ], 422);
            }

            $totalPagadoActual = $contrato->pagos()
                ->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                ->sum('monto');

            $saldoPendiente = $contrato->monto_total_renta - $totalPagadoActual;

            if ($request->monto > $saldoPendiente) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El monto del pago excede el saldo pendiente del contrato. Saldo pendiente: $' . number_format($saldoPendiente, 2),
                ], 422);
            }

            $pago = DB::transaction(function () use ($request, $contrato) {

                $pago = Pago::create([
                    'contrato_id'        => $contrato->id,
                    'monto'              => $request->monto,
                    'metodo_pago'        => $request->metodo_pago,
                    'estado_transaccion' => EstadoTransaccionEnum::CONFIRMADO->value,
                    'fecha_pago'         => $request->fecha_pago,
                ]);

                $totalPagado = $contrato->pagos()
                    ->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                    ->sum('monto');

                $contrato->update([
                    'estado_pago' => $totalPagado >= $contrato->monto_total_renta
                        ? EstadoPagoEnum::PAGADO->value
                        : EstadoPagoEnum::PARCIAL->value,
                ]);

                return $pago;
            });

            $pago->load('contrato:id,numero_contrato,monto_total_renta,estado_pago');
            return response()->json([
                'status'  => 'success',
                'message' => 'Pago registrado con éxito',
                'data'    => $pago,
            ], 201);
        } catch (ModelNotFoundException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Contrato no encontrado',
            ], 404);
        } catch (\Exception) {
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
                    'message' => 'No tienes permiso para ver este pago',
                ], 403);
            }

            $pago = Pago::with([
                'contrato:id,numero_contrato,monto_total_renta,estado_pago',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $pago,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pago no encontrado',
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
    public function destroy(Request $request, string $id)
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para cancelar pagos',
                ], 403);
            }

            $request->validate([
                'motivo_cancelacion' => 'required|string|max:500',
            ]);

            $pago = Pago::with('contrato')->find($id);

            if (!$pago) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pago no encontrado',
                ], 404);
            }

            if ($pago->estado_transaccion === EstadoTransaccionEnum::CANCELADO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Este pago ya se encuentra cancelado',
                ], 422);
            }

            if ($pago->contrato && $pago->contrato->estado_contrato === EstadoContratoEnum::FINALIZADO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se puede cancelar un pago de un contrato ya finalizado',
                ], 422);
            }

            DB::transaction(function () use ($pago, $request) {
                $eraConfirmado = $pago->estado_transaccion === EstadoTransaccionEnum::CONFIRMADO->value;

                $pago->update([
                    'estado_transaccion' => EstadoTransaccionEnum::CANCELADO->value,
                    'motivo_cancelacion' => $request->motivo_cancelacion,
                ]);

                // Si el pago cancelado SÍ estaba confirmado, el estado_pago del contrato
                // quedó desactualizado (se calculó contando ese monto). Hay que recalcularlo.
                if ($eraConfirmado && $pago->contrato) {
                    $contrato = $pago->contrato;

                    $totalPagadoActual = $contrato->pagos()
                        ->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
                        ->sum('monto');

                    if ($totalPagadoActual <= 0) {
                        $nuevoEstadoPago = EstadoPagoEnum::PENDIENTE->value;
                    } elseif ($totalPagadoActual < $contrato->monto_total_renta) {
                        $nuevoEstadoPago = EstadoPagoEnum::PARCIAL->value;
                    } else {
                        $nuevoEstadoPago = EstadoPagoEnum::PAGADO->value;
                    }

                    $contrato->update([
                        'estado_pago' => $nuevoEstadoPago,
                    ]);
                }
            });

            $pago->load('contrato:id,numero_contrato,monto_total_renta,estado_pago');

            return response()->json([
                'status'  => 'success',
                'message' => 'Pago cancelado correctamente',
                'data'    => $pago,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
