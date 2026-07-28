<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Http\Requests\ClienteController\StoreClienteRequest;
use App\Http\Requests\ClienteController\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ClienteController extends Controller
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
                    'message' => 'No tienes permiso para ver los clientes',
                ], 403);
            }

            $clientes = Cliente::with('municipio:id,nombre,departamento_id', 'municipio.departamento:id,nombre')
                ->when($request->search, function ($query, $search) {
                    $query->where('nombre', 'like', '%' . $search . '%')
                          ->orWhere('dui', 'like', '%' . $search . '%')
                          ->orWhere('numero_licencia', 'like', '%' . $search . '%')
                          ->orWhere('telefono', 'like', '%' . $search . '%');
                })
                ->latest()
                ->paginate(10);

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
    public function store(StoreClienteRequest $request)
    {
        try {
            // authorize() y rules() ya se resolvieron automáticamente
            $cliente = Cliente::create($request->validated());

            $cliente->load('municipio:id,nombre,departamento_id', 'municipio.departamento:id,nombre');

            return response()->json([
                'status'  => 'success',
                'message' => 'Cliente registrado con éxito',
                'data'    => $cliente,
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
                    'message' => 'No tienes permiso para ver este cliente',
                ], 403);
            }

            $cliente = Cliente::with([
                'municipio:id,nombre,departamento_id',
                'municipio.departamento:id,nombre',
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
    public function update(UpdateClienteRequest $request, string $id)
    {
        try {
            // authorize() y rules() ya se resolvieron automáticamente
            $cliente = Cliente::findOrFail($id);

            $cliente->update($request->validated());

            $cliente->load('municipio:id,nombre,departamento_id', 'municipio.departamento:id,nombre');

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
        //
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
