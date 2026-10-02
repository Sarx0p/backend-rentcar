<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\UsuarioEstadoEnum;
use App\Mail\RecuperarPasswordMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    public function olvidePassword(Request $request)
    {

        $request->validate(['correo' => 'required|email']);

        $user = User::where('correo', $request->correo)->first();

        if ($user && $user->estado === UsuarioEstadoEnum::ACTIVO->value) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['correo' => $user->correo],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $url = config('app.frontend_url') . '/restablecer-password?token=' . $token . '&correo=' . urlencode($user->correo);

            Mail::to($user->correo)->send(new RecuperarPasswordMail($user, $url));
        }

        return response()->json([
            'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.',
        ], 200);
    }

    public function restablecerPassword(Request $request)
    {
        $request->validate([
            'correo'   => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $registro = DB::table('password_reset_tokens')->where('correo', $request->correo)->first();

        if (
            !$registro
            || !Hash::check($request->token, $registro->token)
            || Carbon::parse($registro->created_at)->addMinutes(60)->isPast()
        ) {
            return response()->json(['message' => 'El enlace no es válido o ya venció.'], 422);
        }

        $user = User::where('correo', $request->correo)->first();
        if (!$user) {
            return response()->json(['message' => 'El enlace no es válido o ya venció.'], 422);
        }

        $user->password = $request->password;
        $user->save();

        DB::table('password_reset_tokens')->where('correo', $request->correo)->delete();

        return response()->json(['message' => 'Contraseña actualizada correctamente.'], 200);
    }
}
