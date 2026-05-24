<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'nombre'     => 'required|string|max:80|unique:categorias,nombre',
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
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Faltan campos requeridos.',
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