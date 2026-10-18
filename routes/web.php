<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebLoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// Mostrar el Login visual
Route::get('/login', function () {

    // Si el usuario ya inició sesión, enviarlo a Bienvenida
    if (Auth::check()) {
        return redirect('/bienvenida');
    }

    // Si no inició sesión, mostrar el Login
    return view('login');

})->name('login');



// Procesar el inicio de sesión
Route::post('/login', [WebLoginController::class, 'login'])
    ->middleware('throttle:5,1');

// Mostrar pantalla de registro
Route::get('/registro', function () {
    return view('registro');
});


Route::get('/bienvenida', function () {

    return view('bienvenida', [
        'nombre' => Auth::user()->Nombres
    ]);

})->middleware('auth');

Route::post('/logout', function (Request $request) {

    Auth::logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

    return redirect('/login');

})->middleware('auth');

 // Mostrar pantalla de recuperación de contraseña
Route::get('/recuperar', function () {
    return view('recuperar');
});

 // Mostrar pantalla de verificación del código
Route::get('/verificar-codigo', function () {
    return view('verificar-codigo');
});

 // Mostrar pantalla para cambiar contraseña
Route::get('/nueva-password', function () {
    return view('nueva-password');
});

 // Mostrar pantalla de verificación de correo
Route::get('/verificar-correo', function () {
    return view('verificar-correo');
});
