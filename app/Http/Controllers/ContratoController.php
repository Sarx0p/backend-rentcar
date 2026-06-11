<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\EstadoReservaEnum;
use App\Enums\VehiculoEstadoEnum;
use App\Models\Contrato;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
                'reserva.cliente:id,nombre,dui,telefono,departamento,municipio,numero_licencia',
                'reserva.vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'reserva.vehiculo.modelo:id,nombre,marca_id',
                'reserva.vehiculo.modelo.marca:id,nombre',
                'reserva.vehiculo.categoria:id,nombre,precio_dia',
                'user:id,nombre,apellido',
            ])
                ->when($request->search, function ($query, $search) {
                    $query->where('numero_contrato', 'like', '%' . $search . '%')
                        ->orWhere('estado_contrato', 'like', '%' . $search . '%')
                        ->orWhereHas('reserva.cliente', function ($q) use ($search) {
                            $q->where('nombre', 'like', '%' . $search . '%')
                                ->orWhere('dui', 'like', '%' . $search . '%');
                        });
                })
                ->when($request->estado, function ($query, $estado) {
                    $query->where('estado_contrato', $estado);
                })
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $contratos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para crear contratos',
                ], 403);
            }

            $request->validate([
                'reserva_id'                => 'required|exists:reservas,id',
                'fecha_hora_entrega'        => 'required|date',
                'fecha_hora_devolucion'     => 'required|date|after:fecha_hora_entrega',
                'precio_por_dia'            => 'required|numeric|min:0',
                'nivel_combustible_entrega' => 'required|string|max:50',
                'monto_descuento'           => 'sometimes|numeric|min:0',
                'observaciones_entrega'     => 'sometimes|string|nullable',
                'observaciones'             => 'sometimes|string|nullable',
            ]);

            $reserva = Reserva::with([
                'vehiculo',
                'cliente',
            ])->findOrFail($request->reserva_id);

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

            $contrato = DB::transaction(function () use ($request, $reserva, $userAuth) {


                $inicio    = \Carbon\Carbon::parse($request->fecha_hora_entrega);
                $fin       = \Carbon\Carbon::parse($request->fecha_hora_devolucion);
                $dias      = max(1, $inicio->diffInDays($fin));

                $descuento   = $request->monto_descuento ?? 0;
                $montoTotal  = ($dias * $request->precio_por_dia) - $descuento;

                $ultimoContrato = Contrato::latest()->first();
                $numero         = $ultimoContrato
                    ? str_pad((intval(substr($ultimoContrato->numero_contrato, -4)) + 1), 4, '0', STR_PAD_LEFT)
                    : '0001';
                $numeroContrato = 'CONT-' . date('Y') . '-' . $numero;

                $contrato = Contrato::create([
                    'numero_contrato'           => $numeroContrato,
                    'fecha_hora_entrega'        => $request->fecha_hora_entrega,
                    'fecha_hora_devolucion'     => $request->fecha_hora_devolucion,
                    'dias_acordados'            => $dias,
                    'precio_por_dia'            => $request->precio_por_dia,
                    'monto_descuento'           => $descuento,
                    'monto_total_renta'         => $montoTotal,
                    'nivel_combustible_entrega' => $request->nivel_combustible_entrega,
                    'observaciones_entrega'     => $request->observaciones_entrega ?? null,
                    'observaciones'             => $request->observaciones ?? null,
                    'estado_contrato'           => EstadoContratoEnum::ACTIVO->value,
                    'estado_pago'               => EstadoPagoEnum::PENDIENTE->value,
                    'reserva_id'                => $reserva->id,
                    'usuario_id'                => $userAuth->id,
                ]);


                $reserva->update([
                    'estado' => EstadoReservaEnum::CONFIRMADA->value,
                ]);

                $reserva->vehiculo->update([
                    'estado' => VehiculoEstadoEnum::RENTADO->value,
                ]);

                return $contrato;
            });

            $contrato->load([
                'reserva.cliente:id,nombre,dui,telefono,departamento,municipio,numero_licencia',
                'reserva.vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'reserva.vehiculo.modelo:id,nombre,marca_id',
                'reserva.vehiculo.modelo.marca:id,nombre',
                'reserva.vehiculo.categoria:id,nombre,precio_dia',
                'user:id,nombre,apellido',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Contrato creado con éxito',
                'data'    => $contrato,
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Reserva no encontrada',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error de validación',

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
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este contrato',
                ], 403);
            }

            $contrato = Contrato::with([
                'reserva.cliente:id,nombre,dui,telefono,departamento,municipio,numero_licencia',
                'reserva.vehiculo:id,placa,color,anio,estado,modelo_id,categoria_id',
                'reserva.vehiculo.modelo:id,nombre,marca_id',
                'reserva.vehiculo.modelo.marca:id,nombre',
                'reserva.vehiculo.categoria:id,nombre,precio_dia',
                'user:id,nombre,apellido',
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
                'reserva.cliente',
                'reserva.vehiculo.modelo.marca',
                'reserva.vehiculo.categoria',
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
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}
