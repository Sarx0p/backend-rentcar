<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClienteController extends Controller
{
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
                    'message' => 'No tienes permiso para ver los clientes',
                ], 403);
            }

            $clientes = Cliente::latest()->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $clientes,
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
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para registrar clientes',
                ], 403);
            }

            $request->validate([
                'nombre'               => 'required|string|max:100',
                'dui'                  => 'required|string|max:20|unique:clientes,dui',
                'nacimiento_dui'       => 'required|date',
                'numero_licencia'      => 'required|string|max:30|unique:clientes,numero_licencia',
                'vencimiento_licencia' => 'required|date|after:today',
                'telefono'             => 'required|string|max:25',
                'departamento'         => 'required|string|max:50',
                'municipio'            => 'required|string|max:50',
            ]);

            $cliente = Cliente::create($request->all());

            return response()->json([
                'status'  => 'success',
                'message' => 'Cliente registrado con éxito',
                'data'    => $cliente,
            ], 201);

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
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para ver este cliente',
                ], 403);
            }

            $cliente = Cliente::with([
                'reservas' => function ($query) {
                    $query->select('id', 'cliente_id', 'vehiculo_id', 'fecha_inicio', 'fecha_fin', 'estado')
                          ->latest();
                },
                'reservas.vehiculo' => function ($query) {
                    $query->select('id', 'placa', 'color', 'estado', 'modelo_id');
                },
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $cliente,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cliente no encontrado',
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
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para actualizar clientes',
                ], 403);
            }

            $cliente = Cliente::findOrFail($id);

            $request->validate([
                'nombre'               => 'sometimes|string|max:100',
                'dui'                  => 'sometimes|string|max:20|unique:clientes,dui,' . $id,
                'nacimiento_dui'       => 'sometimes|date',
                'numero_licencia'      => 'sometimes|string|max:30|unique:clientes,numero_licencia,' . $id,
                'vencimiento_licencia' => 'sometimes|date|after:today',
                'telefono'             => 'sometimes|string|max:25',
                'departamento'         => 'sometimes|string|max:50',
                'municipio'            => 'sometimes|string|max:50',
            ]);

            $cliente->update($request->all());

            return response()->json([
                'status'  => 'success',
                'message' => 'Cliente actualizado con éxito',
                'data'    => $cliente,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cliente no encontrado',
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
       
    }
     public function licenciaVigente(string $id)
    {
        try {
            $userAuth = auth('api')->user();

            if (
                !$userAuth->hasRole(RolEnum::ADMINISTRADOR->value) &&
                !$userAuth->hasRole(RolEnum::EMPLEADO->value)
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tienes permiso para realizar esta acción',
                ], 403);
            }

            $cliente = Cliente::findOrFail($id);

            $vigente = $cliente->vencimiento_licencia->isFuture();

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'cliente_id'           => $cliente->id,
                    'nombre'               => $cliente->nombre,
                    'vigente'              => $vigente,
                    'vencimiento_licencia' => $cliente->vencimiento_licencia,
                ],
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cliente no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
