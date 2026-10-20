<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    // Enviar al usuario a Google
    public function redirectToGoogle()
{
    /** @var \Laravel\Socialite\Two\GoogleProvider $google */
    $google = Socialite::driver('google');

    return $google
        ->with(['prompt' => 'select_account'])
        ->redirect();
}

    // Iniciar la vinculación de Google con una cuenta existente
    public function vincularGoogle()
{
    // Comprobar que el usuario haya iniciado sesión
    if (!Auth::guard('web')->check()) {
        return redirect('/login');
    }

    // Guardar temporalmente el ID del usuario autenticado
    session(['google_link_user_id' => Auth::guard('web')->id()]);

    // Enviar al usuario a Google
    /** @var \Laravel\Socialite\Two\GoogleProvider $google */
$google = Socialite::driver('google');

return $google
    ->with(['prompt' => 'select_account'])
    ->redirect();
}

    // Recibir la respuesta de Google
    public function handleGoogleCallback()
{
    try {
        $googleUser = Socialite::driver('google')->user();

        $googleId = (string) $googleUser->getId();
        $correo = $googleUser->getEmail();
        $datosGoogle = $googleUser->user;

        if (!$googleId || !$correo ||
            ($datosGoogle['email_verified'] ?? false) !== true) {
            return redirect('/login')->with(
                'error',
                'Google no proporcionó un correo verificado.'
            );
        }

        // Comprobar si se solicitó vincular una cuenta existente
        $usuarioVincularId = session()->pull('google_link_user_id');

        if ($usuarioVincularId !== null) {
            /** @var Usuario|null $usuarioActual */
            $usuarioActual = Auth::guard('web')->user();
            if (!$usuarioActual ||
                (string) $usuarioActual->id !== (string) $usuarioVincularId) {
                return redirect('/login')->with(
                    'error',
                    'Debes iniciar sesión nuevamente para vincular Google.'
                );
            }

            // El correo de Google debe coincidir con el registrado
            if (strcasecmp($usuarioActual->Correo, $correo) !== 0) {
                return redirect('/bienvenida')->with(
                    'error',
                    'Debes seleccionar la cuenta de Google con el mismo correo.'
                );
            }

            // Evitar vincular una cuenta de Google utilizada por otro usuario
            $googleExistente = Usuario::where('google_id', $googleId)
                ->where('id', '!=', $usuarioActual->id)
                ->exists();

            if ($googleExistente) {
                return redirect('/bienvenida')->with(
                    'error',
                    'Esta cuenta de Google ya está vinculada a otro usuario.'
                );
            }

            $usuarioActual->google_id = $googleId;
            $usuarioActual->save();

            return redirect('/bienvenida')->with(
                'success',
                'Tu cuenta de Google se vinculó correctamente.'
            );
        }

        // Inicio de sesión normal mediante Google
        $usuario = Usuario::where('google_id', $googleId)->first();

        if (!$usuario) {
            // No vincular automáticamente cuentas tradicionales
            if (Usuario::where('Correo', $correo)->exists()) {
                return redirect('/login')->with(
                    'error',
                    'Este correo ya tiene una cuenta. Inicia sesión con tu contraseña.'
                );
            }

            if (strlen($correo) > 50) {
                return redirect('/login')->with(
                    'error',
                    'El correo supera la longitud permitida.'
                );
            }

            $nombreCompleto = trim(
                $googleUser->getName() ?: 'Usuario Google'
            );

            $partes = explode(' ', $nombreCompleto, 2);

            $usuario = new Usuario();
            $usuario->Nombres = mb_substr($partes[0], 0, 50);
            $usuario->Apellido = mb_substr($partes[1] ?? '', 0, 50);
            $usuario->Correo = $correo;
            $usuario->Passwordd = Hash::make(
                bin2hex(random_bytes(32))
            );
            $usuario->google_id = $googleId;
            $usuario->email_verified_at = now();
            $usuario->save();
        }

        if ($usuario->email_verified_at === null) {
            return redirect('/login')->with(
                'error',
                'Tu cuenta todavía no está verificada.'
            );
        }

        Auth::guard('web')->login($usuario);
        request()->session()->regenerate();

        return redirect('/bienvenida');

    } catch (\Throwable $e) {
        report($e);

        return redirect('/login')->with(
            'error',
            'No se pudo completar la autenticación con Google.'
        );
    }
}
}
