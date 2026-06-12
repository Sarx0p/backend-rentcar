<?php

namespace App\Http\Controllers;

use App\Enums\CargoAdicionalEstadoEnum;
use App\Enums\CargoAdicionalTipoEnum;
use App\Enums\EstadoContratoEnum;
use App\Enums\RolEnum;
use App\Models\CargoAdicional;
use App\Models\Contrato;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nette\Schema\ValidationException;

class CargoAdicionalController extends Controller
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
                    'message' => 'No tienes permiso para ver los cargos adicionales',
                ], 403);
            }

            $cargos = CargoAdicional::with([
                'contrato:id,numero_contrato,monto_total_renta,estado_pago',
            ])

                ->when($request->search, function ($query, $search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('tipo_cargo', 'like', '%' . $search . '%')
                            ->orWhere('descripcion', 'like', '%' . $search . '%')
                            ->orWhereHas('contrato', function ($q) use ($search) {
                                $q->where('numero_contrato', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('contrato.reserva.cliente', function ($q) use ($search) {
                                $q->where('nombre', 'like', '%' . $search . '%')
                                    ->orWhere('dui', 'like', '%' . $search . '%');
                            });
                    });
                })

                ->when($request->tipo_cargo, function ($query, $tipo) {
                    $query->where('tipo_cargo', $tipo);
                })
                ->when($request->estado_cargo, function ($query, $estado) {
                    $query->where('estado_cargo', $estado);
                })
                ->when($request->contrato_id, function ($query, $contratoId) {
                    $query->where('contrato_id', $contratoId);
                })
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $cargos,
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
                    'message' => 'No tienes permiso para registrar cargos adicionales',
                ], 403);
            }

            $request->validate([
                'contrato_id'    => 'required|exists:contratos,id',
                'tipo_cargo'     => 'required|in:' . implode(',', array_column(CargoAdicionalTipoEnum::cases(), 'value')),
                'descripcion'    => 'sometimes|string|nullable',
                'monto'          => 'required|numeric|min:0.01',
                'fecha_registro' => 'required|date',
            ]);

            $contrato = Contrato::findOrFail($request->contrato_id);

            if ($contrato->estado_contrato !== EstadoContratoEnum::ACTIVO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Solo se pueden agregar cargos a contratos ACTIVOS',
                ], 422);
            }

            $cargo = DB::transaction(function () use ($request, $contrato) {


                $cargo = CargoAdicional::create([
                    'contrato_id'    => $contrato->id,
                    'tipo_cargo'     => $request->tipo_cargo,
                    'descripcion'    => $request->descripcion ?? null,
                    'monto'          => $request->monto,
                    'fecha_registro' => $request->fecha_registro,
                    'estado_cargo'   => CargoAdicionalEstadoEnum::PENDIENTE->value,
                ]);

                $montoBase    = ($contrato->dias_acordados * $contrato->precio_por_dia) - $contrato->monto_descuento;
                $totalCargos  = $contrato->cargosAdicionales()->sum('monto');
                $nuevoTotal   = $montoBase + $totalCargos;

                $contrato->update([
                    'monto_total_renta' => $nuevoTotal,
                ]);

                return $cargo;
            });

            $cargo->load('contrato:id,numero_contrato,monto_total_renta,estado_pago');

            return response()->json([
                'status'  => 'success',
                'message' => 'Cargo adicional registrado con éxito',
                'data'    => $cargo,
            ], 201);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Contrato no encontrado',
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
                    'message' => 'No tienes permiso para ver este cargo adicional',
                ], 403);
            }

            $cargo = CargoAdicional::with([
                'contrato:id,numero_contrato,monto_total_renta,estado_pago',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $cargo,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cargo adicional no encontrado',
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
}
