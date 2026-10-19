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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validacion = Validator::make($request->all(), [
            'correo'   => 'required|email|max:150',
            'password' => 'required|string|max:255',
        ], [
            'correo.required'   => 'El correo es obligatorio.',
            'correo.email'      => 'Escribe un correo válido.',
            'correo.max'        => 'El correo no puede tener más de 150 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string'   => 'La contraseña no es válida.',
            'password.max'      => 'La contraseña no puede tener más de 255 caracteres.',
        ]);

        if ($validacion->fails()) {
            return response()->json([
                'message' => $validacion->errors()->first(),
                'errors'  => $validacion->errors(),
            ], 422);
        }

        $credenciales = [
            'correo'   => $request->input('correo'),
            'password' => $request->input('password'),
        ];

        if (!$token = auth('api')->attempt($credenciales)) {
            return response()->json([
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        if (auth('api')->user()->estado !== UsuarioEstadoEnum::ACTIVO->value) {
            auth('api')->logout();

            return response()->json([
                'message' => 'Tu cuenta se encuentra bloqueada. Contacta al administrador.',
            ], 403);
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
        try {
            $token = auth('api')->refresh();
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Tu sesión expiró. Inicia sesión de nuevo.',
            ], 401);
        }

        $user = auth('api')->setToken($token)->user();

        if (!$user) {
            return response()->json([
                'message' => 'Tu sesión expiró. Inicia sesión de nuevo.',
            ], 401);
        }

        if ($user->estado !== UsuarioEstadoEnum::ACTIVO->value) {
            auth('api')->logout();

            return response()->json([
                'message' => 'Tu cuenta se encuentra bloqueada. Contacta al administrador.',
            ], 403);
        }

        return $this->responseWithToken($token);
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

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.'
        ], 200);
    }
}
