<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\ValoracionController;
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

Route::post('login', [AuthController::class, 'login'])
    ->name('login');


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

        // ====================================================================
        // ARCHIVOS GPX - Subir, descargar, obtener info, eliminar
        // ====================================================================

        // Subir archivo GPX
        Route::post('{ruta}/subir-gpx', [GpxController::class, 'subirGpx'])
            ->name('rutas.subir-gpx');

        // Descargar archivo GPX
        Route::get('{ruta}/descargar-gpx', [GpxController::class, 'descargarGpx'])
            ->name('rutas.descargar-gpx');

        // Obtener información del GPX (sin descargarlo)
        Route::get('{ruta}/info-gpx', [GpxController::class, 'obtenerInfoGpx'])
            ->name('rutas.info-gpx');

        // Eliminar archivo GPX
        Route::delete('{ruta}/gpx', [GpxController::class, 'eliminarGpx'])
            ->name('rutas.eliminar-gpx');

        // ====================================================================
        // VALORACIONES - Rutas anidadas bajo /rutas/{id}/valoraciones
        // ====================================================================

        // Obtener valoraciones de una ruta específica
        Route::get('{ruta}/valoraciones', [ValoracionController::class, 'indiceporRuta'])
            ->name('rutas.valoraciones.index');

        // Crear valoración para una ruta
        Route::post('{ruta}/valoraciones', [ValoracionController::class, 'store'])
            ->name('rutas.valoraciones.store');

        // ====================================================================
        // CLIMA - Rutas anidadas bajo /rutas/{id}/clima
        // ====================================================================

        // Obtener condiciones atmosféricas de una ruta
        Route::get('{ruta}/clima', [CondicionAtmosfericaController::class, 'indicePorRuta'])
            ->name('rutas.clima.index');

        // Registrar condición atmosférica para una ruta
        Route::post('{ruta}/clima', [CondicionAtmosfericaController::class, 'store'])
            ->name('rutas.clima.store');
    });

    // ========================================================================
    // VALORACIONES - Rutas independientes
    // ========================================================================

    Route::prefix('valoraciones')->group(function () {
        // Obtener una valoración específica
        Route::get('{valoracion}', [ValoracionController::class, 'mostrar'])
            ->name('valoraciones.show');

        // Actualizar una valoración
        Route::put('{valoracion}', [ValoracionController::class, 'actualizar'])
            ->name('valoraciones.update');

        // Eliminar una valoración
        Route::delete('{valoracion}', [ValoracionController::class, 'eliminar'])
            ->name('valoraciones.destroy');
    });

    // ========================================================================
    // CLIMA / CONDICIONES ATMOSFÉRICAS - Rutas independientes
    // ========================================================================

    Route::prefix('condiciones-atmosfericas')->group(function () {
        // Obtener una condición específica
        Route::get('{condicionAtmosferica}', [CondicionAtmosfericaController::class, 'mostrar'])
            ->name('condiciones-atmosfericas.show');

        // Actualizar una condición
        Route::put('{condicionAtmosferica}', [CondicionAtmosfericaController::class, 'actualizar'])
            ->name('condiciones-atmosfericas.update');

        // Eliminar una condición
        Route::delete('{condicionAtmosferica}', [CondicionAtmosfericaController::class, 'eliminar'])
            ->name('condiciones-atmosfericas.destroy');
    });

    // ========================================================================
    // CLIMA - Predicción y Pronóstico
    // ========================================================================

    Route::prefix('clima')->group(function () {
        // Obtener predicción del clima para una ubicación y fecha
        // Parámetros: latitud, longitud, fecha
        // Ejemplo: GET /api/clima/prediccion?latitud=40.4168&longitud=-3.7038&fecha=2024-04-15
        Route::get('prediccion', [CondicionAtmosfericaController::class, 'obtenerPrediccion'])
            ->name('clima.prediccion');
    });

    // // ========================================================================
    // // RECOMENDACIONES - Motor inteligente de recomendación de rutas
    // // ========================================================================

    // Route::prefix('recomendaciones')->group(function () {
    //     // Obtener rutas recomendadas basadas en:
    //     // - Ubicación (latitud, longitud)
    //     // - Fecha
    //     // - Clima predicho vs clima histórico
    //     // - Valoraciones previas (>=4 estrellas)
    //     //
    //     // Parámetros: latitud, longitud, fecha
    //     // Ejemplo: GET /api/recomendaciones?latitud=40.4168&longitud=-3.7038&fecha=2024-04-15
    //     Route::get('/', [ControladorRecomendacion::class, 'recomendaciones'])
    //         ->name('recomendaciones.index');
    // });

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