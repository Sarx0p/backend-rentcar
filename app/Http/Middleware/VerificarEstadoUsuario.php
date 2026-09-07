<?php

namespace App\Http\Middleware;

use App\Enums\UsuarioEstadoEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarEstadoUsuario
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if ($user && $user->estado !== UsuarioEstadoEnum::ACTIVO->value) {
            auth('api')->logout();

            return response()->json([
                'status'  => 'error',
                'message' => 'Tu cuenta se encuentra inactiva. Contacta al administrador.',
            ], 403);
        }

        return $next($request);
    }
}
