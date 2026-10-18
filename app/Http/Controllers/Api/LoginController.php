<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    
public function login(Request $request)
{
    $datos = $request->validate([
        'correo' => 'required|email',
        'passwordd' => 'required|string'
    ]);

    // Buscar usuario mediante el procedimiento almacenado.
    $resultado = DB::select(
        'CALL sp_Usuario_Login(?)',
        [$datos['correo']]
    );

    if (empty($resultado)) {
        return response()->json([
            'success' => false,
            'message' => 'Correo o contraseña incorrectos'
        ], 401);
    }

    $usuario = $resultado[0];

    // Verificar la contraseña protegida.
    if (!Hash::check($datos['passwordd'], $usuario->Passwordd)) {
        return response()->json([
            'success' => false,
            'message' => 'Correo o contraseña incorrectos'
        ], 401);
    }

    return response()->json([
        'success' => true,
        'message' => 'Login correcto',
        'usuario' => [
            'id' => $usuario->id,
            'nombres' => $usuario->Nombres,
            'correo' => $usuario->Correo
        ]
    ]);
}

public function registrar(Request $request)
{
    $datos = $request->validate([
        'nombre' => 'required|string|max:50',
        'apellido' => 'required|string|max:50',
        'cumpleanos' => 'required|date|before:today',
        'correo' => 'required|email|max:50',
        'passwordd' => 'required|string|min:8|max:72|confirmed',
    ]);

    // Comprobar si el correo ya está registrado
    $existe = DB::table('Usuario')
        ->where('Correo', $datos['correo'])
        ->exists();

    if ($existe) {
        return response()->json([
            'success' => false,
            'message' => 'El correo ya está registrado.'
        ], 409);
    }

    // Proteger la contraseña
    $passwordHash = Hash::make($datos['passwordd']);

    try {
        // Registrar mediante el procedimiento almacenado
        DB::select('CALL sp_Usuario_Guardar(?, ?, ?, ?, ?)', [
            $datos['nombre'],
            $datos['apellido'],
            $datos['cumpleanos'],
            $datos['correo'],
            $passwordHash
        ]);

        // Buscar el usuario recién registrado
        $usuario = DB::table('Usuario')
            ->where('Correo', $datos['correo'])
            ->first();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo localizar la cuenta registrada.'
            ], 500);
        }

    } catch (\Illuminate\Database\QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'No se pudo completar el registro.'
        ], 409);
    }

    // Enviar el código de verificación
    try {
        $this->enviarCodigoVerificacion(
            $usuario->id,
            $usuario->Correo
        );

    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error(
            'Error al enviar verificación de correo',
            ['usuario_id' => $usuario->id]
        );

        return response()->json([
            'success' => true,
            'verificacion_pendiente' => true,
            'correo_enviado' => false,
            'message' => 'Cuenta creada, pero no se pudo enviar el código. Podrás solicitar otro.'
        ], 201);
    }

    return response()->json([
        'success' => true,
        'verificacion_pendiente' => true,
        'correo_enviado' => true,
        'message' => 'Cuenta creada. Revisa tu correo para verificarla.'
    ], 201);
}

    
public function actualizarPassword(Request $request)
{
    $datos = $request->validate([
        'correo' => 'required|email',
        'passwordd' => 'required|string|max:20'
    ]);

    DB::statement(
        'CALL sp_Usuario_Update(?, ?)',
        [
            $datos['correo'],
            $datos['passwordd']
        ]
    );

    return response()->json([
        'success' => true,
        'message' => 'Solicitud de actualización procesada'
    ]);
}

    
public function recuperar(Request $request)
{
    $datos = $request->validate([
        'correo' => 'required|email'
    ]);

    $correo = $datos['correo'];

    // Generar un código temporal para pruebas.
    $codigo = (string) random_int(100000, 999999);

    // Verificar si el usuario existe.
    $usuario = DB::select(
        'SELECT id FROM Usuario WHERE Correo = ?',
        [$correo]
    );

    if (!empty($usuario)) {
        // Guardar el código mediante el procedimiento.
    $codigoHash = hash_hmac(
    'sha256',
    $codigo,
    config('app.key')
);

DB::statement(
    'CALL sp_Usuario_Codigo(?, ?)',
    [$correo, $codigoHash]
);

        // Registrar el correo de prueba.
        Mail::raw(
            "Tu código de recuperación es: {$codigo}. Caduca en 10 minutos.",
            function ($mensaje) use ($correo) {
                $mensaje->to($correo)
                    ->subject('Recuperación de contraseña');
            }
        );
    }

    return response()->json([
        'success' => true,
        'message' => 'Si el correo está registrado, se ha procesado la solicitud de recuperación.'
    ]);
}

    
public function validarCodigo(Request $request)
{
    $datos = $request->validate([
        'correo' => 'required|email',
        'codigo' => 'required|digits:6'
    ]);

    $codigoHash = hash_hmac(
    'sha256',
    $datos['codigo'],
    config('app.key')
);

$resultado = DB::select(
    'CALL sp_Usuario_Validar(?, ?)',
    [
        $datos['correo'],
        $codigoHash
    ]
);

    $valido = !empty($resultado)
        && (int) $resultado[0]->valido === 1;

    return response()->json([
        'success' => $valido,
        'message' => $valido
            ? 'Código validado correctamente'
            : 'Código inválido o expirado'
    ]);
}


public function cambiarPassword(Request $request)
{
    $datos = $request->validate([
        'correo' => 'required|email|max:50',
        'codigo' => 'required|digits:6',
        'passwordd' => 'required|string|min:8|max:72'
    ]);

    
$claveIntentos = 'recuperacion:' . hash(
    'sha256',
    strtolower($datos['correo']) . '|' . $request->ip()
);

if (RateLimiter::tooManyAttempts($claveIntentos, 5)) {
    return response()->json([
        'success' => false,
        'message' => 'Demasiados intentos. Intenta más tarde.'
    ], 429);
}

RateLimiter::hit($claveIntentos, 600);


    // Proteger el código de recuperación
    $codigoHash = hash_hmac(
        'sha256',
        $datos['codigo'],
        config('app.key')
    );

    // Proteger la nueva contraseña
    $passwordHash = Hash::make($datos['passwordd']);

    // Ejecutar el procedimiento almacenado
    $resultado = DB::select(
        'CALL sp_Usuario_UpdatePasswordd(?, ?, ?)',
        [
            $datos['correo'],
            $codigoHash,
            $passwordHash
        ]
    );

    $actualizado = !empty($resultado)
        && (int) $resultado[0]->actualizado === 1;

    if (!$actualizado) {
        return response()->json([
            'success' => false,
            'message' => 'Código inválido, utilizado o expirado'
        ], 400);
    }

    return response()->json([
        'success' => true,
        'message' => 'Contraseña actualizada correctamente'
    ]);
}

private function enviarCodigoVerificacion(int $usuarioId, string $correo): void
{
    // Generar un código aleatorio de 6 dígitos
    $codigo = (string) random_int(100000, 999999);

    // Proteger el código antes de guardarlo
    $codigoHash = hash_hmac(
        'sha256',
        $codigo,
        config('app.key')
    );

    // Invalidar códigos anteriores del mismo usuario
    DB::table('Usuario_VerificacionCorreo')
        ->where('usuario_id', $usuarioId)
        ->update(['usado' => true]);

    // Guardar el nuevo código con vencimiento de 10 minutos
    DB::table('Usuario_VerificacionCorreo')->insert([
        'usuario_id' => $usuarioId,
        'codigo_hash' => $codigoHash,
        'expira_en' => now()->addMinutes(10),
        'usado' => false,
    ]);

    // Enviar el código al correo registrado
    Mail::raw(
        "Hola,\n\n" .
        "Tu código de verificación de correo es: {$codigo}\n\n" .
        "Este código vence en 10 minutos.\n\n" .
        "Si no solicitaste este registro, ignora este mensaje.",
        function ($mensaje) use ($correo) {
            $mensaje->to($correo)
                ->subject('Verifica tu correo - Login Laravel');
        }
    );
}

public function verificarCorreo(Request $request)
{
    // Validar los datos recibidos
    $datos = $request->validate([
        'correo' => 'required|email',
        'codigo' => 'required|digits:6',
    ]);
    
        // Identificar los intentos por correo e IP
        $clave = 'verificar-correo:' . hash(
            'sha256',
            strtolower(trim($datos['correo'])) . '|' . $request->ip()
        );

        // Comprobar el límite de intentos
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($clave, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos de verificación. Espera 10 minutos.'
            ], 429);
        }

        // Registrar el intento durante 10 minutos
        \Illuminate\Support\Facades\RateLimiter::hit($clave, 600);

    // Buscar el usuario registrado
    $usuario = DB::table('Usuario')
        ->where('Correo', $datos['correo'])
        ->first();

    if (!$usuario) {
        return response()->json([
            'success' => false,
            'message' => 'Código incorrecto o expirado.'
        ], 422);
    }

    // Comprobar si el correo ya fue verificado
    if ($usuario->email_verified_at !== null) {
        return response()->json([
            'success' => true,
            'message' => 'Este correo ya está verificado.'
        ]);
    }

    // Buscar el código de verificación más reciente
    $verificacion = DB::table('Usuario_VerificacionCorreo')
        ->where('usuario_id', $usuario->id)
        ->where('usado', false)
        ->orderByDesc('id')
        ->first();

    if (!$verificacion) {
        return response()->json([
            'success' => false,
            'message' => 'Código incorrecto o expirado.'
        ], 422);
    }

    // Comprobar que no haya expirado
    if (now()->greaterThan($verificacion->expira_en)) {
        return response()->json([
            'success' => false,
            'message' => 'El código ha expirado. Solicita uno nuevo.'
        ], 422);
    }

    // Generar el hash del código ingresado
    $codigoHash = hash_hmac(
        'sha256',
        $datos['codigo'],
        config('app.key')
    );

    // Comparar el código recibido con el almacenado
    if (!hash_equals($verificacion->codigo_hash, $codigoHash)) {
        return response()->json([
            'success' => false,
            'message' => 'Código incorrecto o expirado.'
        ], 422);
    }

    // Actualizar la verificación dentro de una transacción
    DB::transaction(function () use ($usuario, $verificacion) {

        DB::table('Usuario')
            ->where('id', $usuario->id)
            ->whereNull('email_verified_at')
            ->update([
                'email_verified_at' => now()
            ]);

        DB::table('Usuario_VerificacionCorreo')
            ->where('usuario_id', $usuario->id)
            ->update([
                'usado' => true
            ]);
    });
    
        // Reiniciar los intentos después de verificar correctamente
        \Illuminate\Support\Facades\RateLimiter::clear($clave);

    return response()->json([
        'success' => true,
        'message' => 'Correo verificado correctamente. Ya puedes iniciar sesión.'
    ]);
}

public function reenviarCodigoVerificacion(Request $request)
{
    // Validar el correo recibido
    $datos = $request->validate([
        'correo' => 'required|email|max:50',
    ]);

    // Buscar al usuario
    $usuario = DB::table('Usuario')
        ->where('Correo', $datos['correo'])
        ->first();

    // Respuesta genérica para no revelar si existe una cuenta
    $respuesta = [
        'success' => true,
        'message' => 'Si el correo tiene una verificación pendiente, recibirás un nuevo código.'
    ];

    // No enviar códigos a cuentas inexistentes o ya verificadas
    if (!$usuario || $usuario->email_verified_at !== null) {
        return response()->json($respuesta);
    }

    try {
        // Reutilizar el método que ya creamos
        $this->enviarCodigoVerificacion(
            $usuario->id,
            $usuario->Correo
        );

        return response()->json($respuesta);

    } catch (\Throwable $e) {

        \Illuminate\Support\Facades\Log::error(
            'Error al reenviar código de verificación',
            ['usuario_id' => $usuario->id]
        );

        return response()->json([
            'success' => false,
            'message' => 'No se pudo enviar el código. Inténtalo más tarde.'
        ], 500);
    }
}

}
