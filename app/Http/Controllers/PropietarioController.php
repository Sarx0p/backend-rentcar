<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPropietarioEnum;
use App\Enums\RolEnum;
use App\Enums\TipoPropietarioEnum;
use App\Models\Propietario;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropietarioController extends Controller
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
                    'message' => 'No tienes permiso para ver los propietarios',
                ], 403);
            }

            $propietarios = Propietario::query()
                ->where('estado', EstadoPropietarioEnum::ACTIVO->value)
                ->when($request->search, function ($query, $search) {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%");
                })
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'status' => 'success',
                'data'   => $propietarios,
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

            if (!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para registrar propietarios',
                ], 403);
            }

            $request->validate([
                'nombre'           => 'required|string|max:100',
                'telefono'         => 'required|string|max:25',
                'tipo_propietario' => 'required|in:' . implode(',', array_column(TipoPropietarioEnum::cases(), 'value')),
            ]);

            $propietario = DB::transaction(function () use ($request) {
                return Propietario::create([
                    'nombre'           => $request->nombre,
                    'telefono'         => $request->telefono,
                    'tipo_propietario' => $request->tipo_propietario,
                    'estado'           => EstadoPropietarioEnum::ACTIVO->value,
                ]);
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Propietario registrado correctamente',
                'data'    => $propietario,
            ], 201);
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
                    'message' => 'No tienes permiso para ver este propietario',
                ], 403);
            }

            $propietario = Propietario::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $propietario,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Propietario no encontrado',
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

            if (!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para actualizar propietarios',
                ], 403);
            }

            $propietario = Propietario::findOrFail($id);

            $request->validate([
                'nombre'           => 'sometimes|string|max:100',
                'telefono'         => 'sometimes|string|max:25',
                'tipo_propietario' => 'sometimes|in:' . implode(',', array_column(TipoPropietarioEnum::cases(), 'value')),
            ]);

            $propietario->update($request->only(['nombre', 'telefono', 'tipo_propietario']));

            return response()->json([
                'status'  => 'success',
                'message' => 'Propietario actualizado correctamente',
                'data'    => $propietario,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Propietario no encontrado',
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $userAuth = auth('api')->user();

            if (!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para desactivar propietarios',
                ], 403);
            }

            $propietario = Propietario::findOrFail($id);

            if ($propietario->estado === EstadoPropietariEnum::INACTIVO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Este propietario ya está desactivado',
                ], 422);
            }

            $propietario->update(['estado' => EstadoPropietariEnum::INACTIVO->value]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Propietario desactivado correctamente',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Propietario no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
