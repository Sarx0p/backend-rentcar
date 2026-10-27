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
use Illuminate\Validation\ValidationException;

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
                !$userAuth || (
                    !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                    !$userAuth->hasRole(RolEnum::EMPLEADO->value)
                )
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
                            ->orWhereHas('contrato.cliente', function ($q) use ($search) {
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
        } catch (\Exception $e) {
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
                !$userAuth || (
                    !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                    !$userAuth->hasRole(RolEnum::EMPLEADO->value)
                )
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para registrar cargos adicionales',
                ], 403);
            }

            $esDiaExtra = $request->tipo_cargo === CargoAdicionalTipoEnum::DIA_EXTRA->value;

            $request->validate([
                'contrato_id'    => 'required|exists:contratos,id',
                'tipo_cargo'     => 'required|in:' . implode(',', array_column(CargoAdicionalTipoEnum::cases(), 'value')),
                'descripcion'    => 'sometimes|string|nullable|max:500',
                'monto'          => $esDiaExtra ? 'nullable' : 'required|numeric|min:0.01|max:999999.99',
                'dias'           => 'sometimes|nullable|integer|min:1|max:365',
            ], [
                'monto.max'       => 'El monto no puede ser mayor a 999,999.99.',
                'descripcion.max' => 'La descripción no puede tener más de 500 caracteres.',
                'dias.integer'    => 'Los días extra deben ser un número entero.',
                'dias.min'        => 'Los días extra deben ser al menos 1.',
                'dias.max'        => 'Los días extra no pueden ser más de 365.',
            ]);

            $contrato = Contrato::findOrFail($request->contrato_id);

            $monto = $esDiaExtra
                ? round((int) ($request->dias ?? 1) * (float) $contrato->precio_por_dia, 2)
                : $request->monto;

            if ($contrato->estado_contrato !== EstadoContratoEnum::ACTIVO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Solo se pueden agregar cargos a contratos ACTIVOS',
                ], 422);
            }

            $cargo = DB::transaction(function () use ($request, $contrato, $monto) {

                $cargo = CargoAdicional::create([
                    'contrato_id'    => $contrato->id,
                    'tipo_cargo'     => $request->tipo_cargo,
                    'descripcion'    => $request->descripcion ?? null,
                    'monto'          => $monto,
                    'fecha_registro' => now(),
                    'estado_cargo'   => CargoAdicionalEstadoEnum::PENDIENTE->value,
                ]);

                $contrato->recalcularTotalYEstadoPago();

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
                'errors'  => $e->errors(),
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
                !$userAuth || (
                    !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                    !$userAuth->hasRole(RolEnum::EMPLEADO->value)
                )
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
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth || (
                    !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                    !$userAuth->hasRole(RolEnum::EMPLEADO->value)
                )
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para actualizar cargos adicionales',
                ], 403);
            }

            $cargo = CargoAdicional::findOrFail($id);
            $contrato = $cargo->contrato;

            if ($contrato->estado_contrato !== EstadoContratoEnum::ACTIVO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Solo se pueden modificar cargos en contratos ACTIVOS',
                ], 422);
            }

            $request->validate([
                'tipo_cargo'     => 'sometimes|in:' . implode(',', array_column(CargoAdicionalTipoEnum::cases(), 'value')),
                'descripcion'    => 'sometimes|string|nullable|max:500',
                'monto'          => 'sometimes|numeric|min:0.01|max:999999.99',
                'estado_cargo'   => 'sometimes|in:' . CargoAdicionalEstadoEnum::PENDIENTE->value . ',' . CargoAdicionalEstadoEnum::ANULADO->value,
            ], [
                'monto.max'       => 'El monto no puede ser mayor a 999,999.99.',
                'descripcion.max' => 'La descripción no puede tener más de 500 caracteres.',
                'estado_cargo.in' => 'El estado del cargo solo se puede cambiar a PENDIENTE o ANULADO.',
            ]);

            DB::transaction(function () use ($request, $cargo, $contrato) {
                $cargo->update($request->only([
                    'tipo_cargo',
                    'descripcion',
                    'monto',
                    'estado_cargo',
                ]));

                $contrato->recalcularTotalYEstadoPago();
            });

            $cargo->load('contrato:id,numero_contrato,monto_total_renta,estado_pago');

            $saldoAFavor = $contrato->fresh()->saldoAFavor();

            return response()->json([
                'status'        => 'success',
                'message'       => 'Cargo adicional actualizado correctamente',
                'advertencia'   => $saldoAFavor > 0
                    ? 'Hay un monto a devolver al cliente de $' . number_format($saldoAFavor, 2) . '.'
                    : null,
                'saldo_a_favor' => $saldoAFavor,
                'data'          => $cargo,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cargo adicional no encontrado',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth || (
                    !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                    !$userAuth->hasRole(RolEnum::EMPLEADO->value)
                )
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para eliminar cargos adicionales',
                ], 403);
            }

            $cargo = CargoAdicional::findOrFail($id);
            $contrato = $cargo->contrato;

            if ($contrato->estado_contrato !== EstadoContratoEnum::ACTIVO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Solo se pueden eliminar cargos en contratos ACTIVOS',
                ], 422);
            }

            DB::transaction(function () use ($cargo, $contrato) {
                $cargo->delete();

                $contrato->recalcularTotalYEstadoPago();
            });

            $saldoAFavor = $contrato->fresh()->saldoAFavor();

            return response()->json([
                'status'        => 'success',
                'message'       => 'Cargo adicional eliminado y saldos recalculados correctamente',
                'advertencia'   => $saldoAFavor > 0
                    ? 'Hay un monto a devolver al cliente de $' . number_format($saldoAFavor, 2) . '.'
                    : null,
                'saldo_a_favor' => $saldoAFavor,
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
}
