<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\RolEnum;
use App\Enums\UsuarioEstadoEnum;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

    public function store(Request $request)
    {
        try {
            if ($request->filled('rol')) {
                $request->merge(['rol' => strtoupper($request->rol)]);
            }

            $validator = Validator::make($request->all(), [
                'nombre'   => 'required|string|max:100',
                'apellido' => 'required|string|max:100',
                'correo'   => 'required|email|max:150|unique:users,correo',
                'password' => 'required|string|min:8|max:255',
                'rol'      => ['required', Rule::enum(RolEnum::class)],
            ], [
                'password.min'  => 'La contraseña debe tener al menos 8 caracteres.',
                'correo.unique' => 'Ya existe un usuario con ese correo electrónico.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Error de validación.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

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

    public function update(Request $request, string $id)
    {
        try {
            if ($request->filled('rol')) {
                $request->merge(['rol' => strtoupper($request->rol)]);
            }

            if ($request->filled('estado')) {
                $request->merge(['estado' => strtoupper($request->estado)]);
            }

            $validator = Validator::make($request->all(), [
                'nombre'   => 'sometimes|string|max:100',
                'apellido' => 'sometimes|string|max:100',
                'correo'   => [
                    'sometimes', 'email', 'max:150',
                    Rule::unique('users', 'correo')->ignore($id),
                ],
                'password' => 'sometimes|string|min:8|max:255',
                'estado'   => ['sometimes', Rule::enum(UsuarioEstadoEnum::class)],
                'rol'      => ['sometimes', Rule::enum(RolEnum::class)],
            ], [
                'password.min'  => 'La contraseña debe tener al menos 8 caracteres.',
                'correo.unique' => 'Ese correo ya está en uso por otro usuario.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Error de validación.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

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

            if ($request->filled('nombre'))   $usuario->nombre   = $request->nombre;
            if ($request->filled('apellido')) $usuario->apellido = $request->apellido;
            if ($request->filled('correo'))   $usuario->correo   = $request->correo;
            if ($request->filled('estado'))   $usuario->estado   = $request->estado;
            if ($request->filled('password')) $usuario->password = Hash::make($request->password);

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

  
    public function destroy(string $id)
    {
        try {
            $usuario = User::findOrFail($id);

            if (auth('api')->id() === $usuario->id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No puedes eliminar tu propia cuenta de administrador.',
                ], 403);
            }

            $usuario->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Usuario eliminado correctamente.',
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
