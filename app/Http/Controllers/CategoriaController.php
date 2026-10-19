<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Models\Categoria;
use App\Rules\TextoValido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoriaController extends Controller
{
    private function mensajesValidacion(): array
    {
        return [
            'nombre.max'               => 'El nombre de la categoría no puede tener más de 50 caracteres.',
            'precio_dia.numeric'        => 'El precio por día debe ser un número.',
            'precio_dia.min'            => 'El precio por día debe ser al menos $1.00.',
            'precio_dia.max'            => 'El precio por día no puede ser mayor a $9,999.99.',
            'precio_dia.unique'         => 'Ya existe una categoría con ese precio por día.',
            'capacidad_maxima.required' => 'El máximo de pasajeros es obligatorio.',
            'capacidad_maxima.integer'  => 'El máximo de pasajeros debe ser un número entero.',
            'capacidad_maxima.min'      => 'El máximo de pasajeros debe ser al menos 1.',
            'capacidad_maxima.max'      => 'El máximo de pasajeros no puede ser mayor a 15.',
        ];
    }

    /**
     * Display a listing of the resource.
     */
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
                    'message' => 'No tienes permiso para ver las categorías.',
                ], 403);
            }

            $categorias = Categoria::orderBy('nombre')->get();

            return response()->json([
                'status' => 'success',
                'data'   => $categorias,
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
                'nombre'           => ['required', 'string', 'min:2', 'max:50', new TextoValido(3, false, '-'), 'unique:categorias,nombre'],
                'precio_dia'       => ['required', 'numeric', 'min:1', 'max:9999.99', 'unique:categorias,precio_dia'],
                'capacidad_maxima' => 'required|integer|min:1|max:15',
            ], $this->mensajesValidacion());

            DB::beginTransaction();

            $categoria = Categoria::create([
                'nombre'           => $request->nombre,
                'precio_dia'       => $request->precio_dia,
                'capacidad_maxima' => $request->capacidad_maxima,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Categoria registrada correctamente.',
                'data'    => $categoria,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Faltan campos requeridos.',
                'errors'  => $e->errors(),
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
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver esta categoría.',
                ], 403);
            }

            $categoria = Categoria::find($id);

            if (!$categoria) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Categoria no encontrada.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $categoria,
            ], 200);
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

            $request->validate([
                'nombre'           => ['required', 'string', 'min:2', 'max:50', new TextoValido(3, false, '-'), Rule::unique('categorias', 'nombre')->ignore($id)],
                'precio_dia'       => ['required', 'numeric', 'min:1', 'max:9999.99', Rule::unique('categorias', 'precio_dia')->ignore($id)],
                'capacidad_maxima' => 'sometimes|integer|min:1|max:15',
            ], $this->mensajesValidacion());

            $categoria = Categoria::find($id);

            if (!$categoria) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Categoria no encontrada.',
                ], 404);
            }

            // Subir el máximo siempre se puede; bajarlo solo si ningún vehículo registrado queda por encima
            if ($request->has('capacidad_maxima')) {
                $mayorRegistrado = $categoria->vehiculos()->max('capacidad_pasajeros');

                if ($mayorRegistrado && $mayorRegistrado > (int) $request->capacidad_maxima) {
                    throw ValidationException::withMessages([
                        'capacidad_maxima' => ["Hay vehículos de esta categoría con {$mayorRegistrado} pasajeros. El máximo no puede ser menor."],
                    ]);
                }
            }

            $categoria->update($request->only(['nombre', 'precio_dia', 'capacidad_maxima']));

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
