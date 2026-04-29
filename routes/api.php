<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\CondicionAtmosfericaController;
use App\Http\Controllers\GpxController;
// use App\Http\Controllers\ControladorRecomendacion;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí se definen todas las rutas de la API REST de Njord
| (Sistema de Recomendación de Rutas en Motocicleta)
|
| Prefix: /api
| Middleware: api
|
*/

// ============================================================================
// RUTAS PÚBLICAS (Sin autenticación)
// ============================================================================

Route::prefix('auth')->group(function () {
    // Registrar nuevo usuario
    Route::post('registrar', [AuthController::class, 'register'])
        ->name('auth.registrar');

    // Iniciar sesión
    Route::post('iniciar-sesion', [AuthController::class, 'login'])
        ->name('auth.iniciarSesion');
});

// Route::post('login', [AuthController::class, 'login'])
//     ->name('login');


// Health check (para verificar que el servidor está activo)
Route::get('salud', function () {
    return response()->json([
        'estado' => 'exito',
        'mensaje' => 'Servidor Njord API operacional',
        'timestamp' => now(),
    ]);
})->name('health.check');

// ============================================================================
// RUTAS PROTEGIDAS (Requieren autenticación con Sanctum)
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {

    // ========================================================================
    // AUTENTICACIÓN - Rutas de usuario
    // ========================================================================

    Route::prefix('auth')->group(function () {
        // Cerrar sesión
        Route::post('cerrar-sesion', [AuthController::class, 'cerrarSesion'])
            ->name('auth.cerrarSesion');

        // Obtener perfil del usuario autenticado
        Route::get('perfil', [AuthController::class, 'profile'])
            ->name('auth.perfil');

        // Actualizar perfil del usuario
        Route::put('perfil', [AuthController::class, 'actualizarPerfil'])
            ->name('auth.actualizarPerfil');
    });

    // ========================================================================
    // RUTAS - CRUD completo
    // ========================================================================

    Route::prefix('rutas')->group(function () {
        // Listar todas mis rutas
        Route::get('/', [RutaController::class, 'index'])
            ->name('rutas.index');

        // Crear nueva ruta
        Route::post('/', [RutaController::class, 'store'])
            ->name('rutas.store');

        // Obtener una ruta específica
        Route::get('{ruta}', [RutaController::class, 'show'])
            ->name('rutas.show');

        // Actualizar una ruta
        Route::put('{ruta}', [RutaController::class, 'update'])
            ->name('rutas.update');

        // Eliminar una ruta
        Route::delete('{ruta}', [RutaController::class, 'destroy'])
            ->name('rutas.destroy');

        // Route::post('{id}/compartir-telegram', [RutaController::class, 'compartirTelegram']);

        // ====================================================================
        // ARCHIVOS GPX - Subir, descargar, obtener info, eliminar
        // ====================================================================

        // Subir archivo GPX
        Route::post('{ruta}/subir-gpx', [GpxController::class, 'subirGpx'])
            ->name('rutas.subir-gpx');

        // Descargar archivo GPX
        Route::get('{ruta}/descargar-gpx', [GpxController::class, 'descargarGpx'])
            ->name('rutas.descargar-gpx');

        // Eliminar archivo GPX
        Route::delete('{ruta}/gpx', [GpxController::class, 'eliminarGpx'])
            ->name('rutas.eliminar-gpx');

        Route::get('{ruta}/gpx', [GpxController::class, 'verGpx'])
            ->name('rutas.ver-ruta-gpx');

        // ====================================================================
        // CLIMA - Rutas anidadas bajo /rutas/{id}/clima
        // ====================================================================

        // Obtener condiciones atmosféricas de una ruta
        Route::get('{ruta}/clima', [CondicionAtmosfericaController::class, 'indicePorRuta'])
            ->name('rutas.clima.index');

    });

    // ========================================================================
    // CLIMA / CONDICIONES ATMOSFÉRICAS - Rutas independientes
    // ========================================================================

    Route::prefix('condiciones-atmosfericas')->group(function () {
        Route::get('{condicionAtmosferica}', [CondicionAtmosfericaController::class, 'show'])
            ->name('condiciones-atmosfericas.show');
    });

    // ========================================================================
    // CLIMA - Predicción e histórico
    // ========================================================================

    Route::prefix('clima')->group(function () {
        // Predicción futura: GET /api/clima/prediccion?latitud=X&longitud=Y&fecha=YYYY-MM-DD&hora=HH:MM
        Route::get('prediccion', [CondicionAtmosfericaController::class, 'obtenerPrediccion'])
            ->name('clima.prediccion');

        // Histórico puntual sin guardar: POST /api/clima/historico
        Route::post('historico', [CondicionAtmosfericaController::class, 'consultarHistorico'])
            ->name('clima.historico');
    });

});

// ============================================================================
// FALLBACK - Ruta para errores 404
// ============================================================================

Route::fallback(function () {
    return response()->json([
        'estado' => 'error',
        'mensaje' => 'Ruta no encontrada',
        'ruta_solicitada' => request()->path(),
    ], 404);
});