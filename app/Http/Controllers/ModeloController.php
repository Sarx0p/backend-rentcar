<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Models\Modelo;
use App\Models\Marca;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModeloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $modelos = Modelo::with('marca')->orderBy('nombre')->get();

            if ($modelos->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No hay modelos registrados.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $modelos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    public function porMarca(int $marcaId)
    {
        try {
            $marca = Marca::find($marcaId);

            if (!$marca) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se encontro ninguna marca para este modelo.',
                ], 404);
            }

            $modelos = Modelo::with('marca')
                ->where('marca_id', $marcaId)
                ->orderBy('nombre')
                ->get();

            if ($modelos->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No existen modelos para esta marca.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $modelos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
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
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }

            $request->validate([
                'nombre'   => 'required|string|max:100',
                'marca_id' => 'required|integer|exists:marcas,id',
            ]);

            DB::beginTransaction();

            $modelo = Modelo::create([
                'nombre'   => $request->nombre,
                'marca_id' => $request->marca_id,
            ]);

            DB::commit();

            $modelo->load('marca');

            return response()->json([
                'status'  => 'success',
                'message' => 'Modelo registrado correctamente.',
                'data'    => $modelo,
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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $modelo = Modelo::with('marca:id,nombre')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $modelo,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Modelo no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
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
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }

            $modelo = Modelo::findOrFail($id);

            DB::beginTransaction();

            $modelo->update($request->only(['nombre']));

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Modelo actualizado correctamente.',
                'data'    => $modelo,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Modelo no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
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
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }

            $modelo = Modelo::findOrFail($id);

            $tieneVehiculos = $modelo->vehiculos()->exists();

            if ($tieneVehiculos) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se puede eliminar el modelo porque tiene vehículos asociados.',
                ], 422);
            }

            $modelo->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Modelo eliminado correctamente.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Modelo no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }
}
