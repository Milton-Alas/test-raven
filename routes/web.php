<?php

use App\Http\Controllers\CandidateAuthController;
use Illuminate\Support\Facades\Route;

// Redirige la raíz a la página de login
Route::get('/', function () {
    return to_route('login');
});

// Rutas de autenticación para candidatos (invitados)
Route::middleware('guest:candidate')->group(function () {
    // Login
    Route::get('login', [CandidateAuthController::class, 'create'])->name('login');
    Route::post('login', [CandidateAuthController::class, 'store']);

    // Register
    Route::get('register', [CandidateAuthController::class, 'createRegister'])->name('register');
    Route::post('register', [CandidateAuthController::class, 'storeRegister']);
});

// Rutas protegidas para candidatos autenticados
Route::middleware('auth:candidate')->group(function () {
    // Ruta de ejemplo para las instrucciones del test
    Route::get('/instrucciones', function () {
        // Aquí iría la lógica para mostrar las instrucciones del test
        return '<h1>Instrucciones del Test (Ruta Protegida)</h1>';
    })->name('instrucciones');

    // Ruta para cerrar sesión
    Route::post('logout', [CandidateAuthController::class, 'destroy'])->name('logout');
});
