
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoginController;

// Inicio de sesión
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1');

// Registro de usuarios
Route::post('/login/registrar', [LoginController::class, 'registrar'])
    ->middleware('throttle:5,1');

 // Verificar correo electrónico después del registro
Route::post('/login/verificar-correo', [LoginController::class, 'verificarCorreo'])
    ->middleware('throttle:5,10');

Route::post('/login/reenviar-codigo', [LoginController::class, 'reenviarCodigoVerificacion'])
    ->middleware('throttle:3,10');


// Recuperación de contraseña
Route::post('/login/recuperar', [LoginController::class, 'recuperar'])
    ->middleware('throttle:3,10');


 // Validar código de recuperación
Route::post('/login/validar', [LoginController::class, 'validarCodigo'])
    ->middleware('throttle:5,10');


// Cambio de contraseña
Route::post('/login/passwordd', [LoginController::class, 'cambiarPassword'])
    ->middleware('throttle:5,10');