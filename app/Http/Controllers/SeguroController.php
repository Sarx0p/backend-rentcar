<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Enums\SeguroEstadoEnum;
use App\Models\Seguro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SeguroController extends Controller
{
    public function index()
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver las reservas',
                ], 403);
            }
            $seguros = Seguro::with(['vehiculo.propietario'])
                ->where('estado', SeguroEstadoEnum::VIGENTE->value)
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();

            $proximoAVencer = $seguros->first();

            return response()->json([
                'status'           => 'success',
                'data'             => $seguros,
                'proximo_a_vencer' => $proximoAVencer,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $userAuth = auth('api')->user();

            if (!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }

            $request->validate([
                'vehiculo_id'       => 'required|integer|exists:vehiculos,id',
                'aseguradora'       => 'required|string|max:150',
                'numero_poliza'     => 'required|string|max:50|unique:seguros,numero_poliza',
                'fecha_inicio'      => 'required|date',
                'fecha_vencimiento' => 'required|date|after:fecha_inicio',
                'cobertura'         => 'nullable|string',
            ]);

            DB::beginTransaction();

            $seguro = Seguro::create([
                'vehiculo_id'       => $request->vehiculo_id,
                'aseguradora'       => $request->aseguradora,
                'numero_poliza'     => $request->numero_poliza,
                'fecha_inicio'      => $request->fecha_inicio,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'cobertura'         => $request->cobertura,
                'estado'            => SeguroEstadoEnum::VIGENTE->value,
            ]);

            DB::commit();

            $seguro->load(['vehiculo.propietario']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Seguro registrado correctamente.',
                'data'    => $seguro,
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Faltan campos requeridos.',
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

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
                    'message' => 'No tienes permiso para ver las reservas',
                ], 403);
            }
            $seguro = Seguro::with(['vehiculo.propietario'])->find($id);

            if (!$seguro) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Seguro no encontrado.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $seguro,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $userAuth = auth('api')->user();

            if (!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }

            $seguro = Seguro::find($id);

            if (!$seguro) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Seguro no encontrado.',
                ], 404);
            }

            if ($seguro->estado === SeguroEstadoEnum::CANCELADO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se puede editar un seguro cancelado.',
                ], 409);
            }

            $request->validate([
                'vehiculo_id'       => 'sometimes|required|integer|exists:vehiculos,id',
                'aseguradora'       => 'sometimes|required|string|max:150',
                'numero_poliza'     => [
                    'sometimes', 'required', 'string', 'max:50',
                    Rule::unique('seguros', 'numero_poliza')->ignore($seguro->id),
                ],
                'fecha_inicio'      => 'sometimes|required|date',
                'fecha_vencimiento' => 'sometimes|required|date|after:fecha_inicio',
                'cobertura'         => 'nullable|string',
            ]);

            DB::beginTransaction();

            $seguro->update($request->only([
                'vehiculo_id',
                'aseguradora',
                'numero_poliza',
                'fecha_inicio',
                'fecha_vencimiento',
                'cobertura',
            ]));

            DB::commit();

            $seguro->load(['vehiculo.propietario']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Seguro actualizado correctamente.',
                'data'    => $seguro,
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Faltan campos requeridos.',
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $userAuth = auth('api')->user();

            if (!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }

            $seguro = Seguro::find($id);

            if (!$seguro) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Seguro no encontrado.',
                ], 404);
            }

            if ($seguro->estado === SeguroEstadoEnum::CANCELADO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El seguro ya se encuentra cancelado.',
                ], 409);
            }

            DB::beginTransaction();

            $seguro->update(['estado' => SeguroEstadoEnum::CANCELADO->value]);

            DB::commit();

            $seguro->load(['vehiculo.propietario']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Seguro anulado correctamente.',
                'data'    => $seguro,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }
}
