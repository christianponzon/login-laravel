<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class WebLoginController extends Controller
{
    public function login(Request $request)
    {
        $datos = $request->validate([
            'correo' => 'required|email',
            'passwordd' => 'required|string',
            'recordarme' => 'sometimes|boolean',
        ]);

        $clave = 'web-login:' . hash(
            'sha256',
            strtolower($datos['correo']) . '|' . $request->ip()
        );

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos. Intenta más tarde.'
            ], 429);
        }

        RateLimiter::hit($clave, 60);

        // Consultar mediante el procedimiento almacenado
        $usuarios = DB::select(
            'CALL sp_Usuario_Login(?)',
            [$datos['correo']]
        );

        $usuarioBD = $usuarios[0] ?? null;

        // Comprobar la contraseña
        if (!$usuarioBD || !Hash::check(
            $datos['passwordd'],
            $usuarioBD->Passwordd
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Correo o contraseña incorrectos.'
            ], 401);
        }

        // Buscar el usuario mediante el modelo de Laravel
        $usuario = Usuario::find($usuarioBD->id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el usuario.'
            ], 404);
        }

        
        // Comprobar si el correo electrónico está verificado
        if ($usuario->email_verified_at === null) {
            return response()->json([
                'success' => false,
                'verificacion_pendiente' => true,
                'message' => 'Debes verificar tu correo electrónico antes de iniciar sesión.'
            ], 403);
        }

        RateLimiter::clear($clave);

        // Activar Recordarme si está marcado
        $recordarme = $request->boolean('recordarme');

        Auth::guard('web')->login($usuario, $recordarme);

        // Renovar la sesión para mayor seguridad
        $request->session()->regenerate();

        // Mantener temporalmente los datos de sesión existentes
        $request->session()->put([
            'usuario_id' => $usuario->id,
            'usuario_nombre' => $usuario->Nombres,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'redirect' => url('/bienvenida')
        ]);
    }
}
