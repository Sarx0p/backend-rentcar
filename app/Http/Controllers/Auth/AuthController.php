<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {

        $credenciales = [
            'correo'   => $request->input('correo'),
            'password' => $request->input('password'),
        ];

        if (!$token = auth('api')->attempt($credenciales)) {
            return response()->json([
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        return $this->responseWithToken($token);
    }

    public function responseWithToken($token)
    {
        $user = auth('api')->user();

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'user' => [
                'id'       => $user->id,
                'nombre'   => $user->nombre,
                'apellido' => $user->apellido,
                'correo'   => $user->correo,
                'roles'    => $user->getRoleNames(),
            ],
            'token_expires' => auth('api')->factory()->getTTL() * 60,
        ], 200);
    }

    public function me()
    {

        return response()->json(auth('api')->user());
    }

    public function logout()
    {

        auth('api')->logout();

        return response()->json([
            'message' => 'Sesión Cerrada correctamente',
        ], 200);
    }

    public function refresh()
    {
    
        return $this->responseWithToken(auth('api')->refresh());
    }
}
