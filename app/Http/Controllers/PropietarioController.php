<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPropietarioEnum;
use App\Enums\RolEnum;
use App\Http\Requests\PropietarioController\StorePropietarioRequest;
use App\Http\Requests\PropietarioController\UpdatePropietarioRequest;
use App\Models\Propietario;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function store(StorePropietarioRequest $request)
    {
        try {
           
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
    public function update(UpdatePropietarioRequest $request, string $id)
    {
        try {
           
            $propietario = Propietario::findOrFail($id);

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

            if ($propietario->estado === EstadoPropietarioEnum::INACTIVO->value) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Este propietario ya está desactivado',
                ], 422);
            }

            $propietario->update(['estado' => EstadoPropietarioEnum::INACTIVO->value]);

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