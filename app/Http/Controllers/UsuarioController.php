<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\RolEnum;
use App\Enums\UsuarioEstadoEnum;
use App\Http\Requests\UsuarioController\StoreUsuarioRequest;
use App\Http\Requests\UsuarioController\UpdateUsuarioRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = User::with('roles');

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre',   'LIKE', "%{$buscar}%")
                        ->orWhere('apellido', 'LIKE', "%{$buscar}%")
                        ->orWhere('correo',   'LIKE', "%{$buscar}%");
                });
            }

            if ($request->filled('rol')) {
                $query->role(strtoupper($request->rol));
            }

            if ($request->filled('estado')) {
                $estadoFormateado = strtoupper($request->estado);

                if (! UsuarioEstadoEnum::tryFrom($estadoFormateado)) {
                    return response()->json([
                        'status'          => 'error',
                        'message'         => 'El estado proporcionado no es válido.',
                        'estados_validos' => array_column(UsuarioEstadoEnum::cases(), 'value'),
                    ], 422);
                }

                $query->where('estado', $estadoFormateado);
            }

            $usuarios = $query->paginate(10);

            return response()->json([
                'status' => 'success',
                'meta'   => [
                    'opciones_roles'   => array_column(RolEnum::cases(), 'value'),
                    'opciones_estados' => array_column(UsuarioEstadoEnum::cases(), 'value'),
                ],
                'data' => $usuarios,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    public function store(StoreUsuarioRequest $request)
    {
        try {
           
            $rolEnum = RolEnum::tryFrom($request->rol);

            if ($rolEnum === RolEnum::ADMINISTRADOR) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No se permite crear usuarios con rol ADMINISTRADOR.',
                ], 403);
            }

            DB::beginTransaction();

            $usuario = User::create([
                'nombre'   => $request->nombre,
                'apellido' => $request->apellido,
                'correo'   => $request->correo,
                'password' => Hash::make($request->password),
                'estado'   => UsuarioEstadoEnum::ACTIVO->value,
            ]);

            $usuario->assignRole($rolEnum->value);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => "Usuario creado correctamente con el rol {$rolEnum->value}.",
                'data'    => $usuario->load('roles'),
            ], 201);
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
            $usuario = User::with('roles')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $usuario,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Usuario no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    public function update(UpdateUsuarioRequest $request, string $id)
    {
        try {
          
            $usuario = User::with('roles')->findOrFail($id);

            if ($request->filled('rol')) {
                $rolEnum = RolEnum::tryFrom($request->rol);

                if ($rolEnum === RolEnum::ADMINISTRADOR) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'No se puede asignar el rol ADMINISTRADOR.',
                    ], 403);
                }

                $usuario->syncRoles([$rolEnum->value]);
            }

            if ($request->filled('estado') && auth('api')->id() === $usuario->id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No puedes cambiar el estado de tu propia cuenta.',
                ], 403);
            }

            // Reemplaze los 5 `if` por una sola asignación masiva haciendo la exepcion de el rol y el password 
            $usuario->fill($request->safe()->except(['password', 'rol']));

            if ($request->filled('password')) {
                $usuario->password = Hash::make($request->password);
            }

            $usuario->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'Usuario actualizado correctamente.',
                'data'    => $usuario->fresh('roles'),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Usuario no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $usuario = User::findOrFail($id);

            if (auth('api')->id() === $usuario->id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No puedes alterar tu propia cuenta.',
                ], 403);
            }

            $nuevoEstado = strtoupper($request->get('estado', 'INACTIVO'));

            if (!UsuarioEstadoEnum::tryFrom($nuevoEstado)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El estado solicitado no es válido.',
                    'estados_validos' => array_column(UsuarioEstadoEnum::cases(), 'value'),
                ], 422);
            }

            $usuario->estado = $nuevoEstado;
            $usuario->save();

            return response()->json([
                'status'  => 'success',
                'message' => "El usuario ahora se encuentra en estado: {$nuevoEstado}.",
                'data'    => $usuario->load('roles'),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Usuario no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }
}