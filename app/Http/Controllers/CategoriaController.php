<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $categorias = Categoria::orderBy('nombre')->get();

            if($categorias->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No hay categorias registradas.',
                ],404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $categorias,
            ],200);

        } catch(\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $userAuth = auth('api')->user();

            if(!$userAuth->hasRole(RolEnum::ADMINISTRADOR->value)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para realizar esta acción.',
                ],403);
            }

            $request->validate([
                'nombre'     => ['required', 'string', 'min:2', 'max:80', 'regex:/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/', 'unique:categorias,nombre'],
                'precio_dia' => 'required|numeric|min:1',
            ]);

            DB::beginTransaction();

            $categoria = Categoria::create([
                'nombre'     => $request->nombre,
                'precio_dia' => $request->precio_dia,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Categoria registrada correctamente.',
                'data'    => $categoria,
            ],201);

        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Faltan campos requeridos.',
                'errors'  => $e->errors(),
            ],422);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ],500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $categoria = Categoria::find($id);

            if(!$categoria) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Categoria no encontrada.',
                ],404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $categoria,
            ],200);

        } catch(\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ],500);
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

            $request->validate([
                'nombre'     => ['required', 'string', 'min:2', 'max:80', 'regex:/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/', Rule::unique('categorias', 'nombre')->ignore($id)],
                'precio_dia' => 'required|numeric|min:1',
            ]);

            $categoria = Categoria::find($id);

            if (!$categoria) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Categoria no encontrada.',
                ], 404);
            }

            $categoria->update($request->only(['nombre', 'precio_dia']));

            return response()->json([
                'status'  => 'success',
                'message' => 'Categoria actualizada correctamente.',
                'data'    => $categoria,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Faltan campos requeridos.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
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

            $categoria = Categoria::find($id);

            if (!$categoria) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Categoría no encontrada.',
                ], 404);
            }

            if ($categoria->vehiculos()->exists()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se puede eliminar la categoría porque tiene vehículos asociados.',
                    'errors'  => [
                        'categoria' => ['No se puede eliminar la categoría porque tiene vehículos asociados.'],
                    ],
                ], 422);
            }

            $categoria->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Categoría eliminada correctamente.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }
}
