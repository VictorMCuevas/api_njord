<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use App\Models\Valoracion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValoracionController extends Controller
{
     /**
     * Obtener todas las valoraciones de una ruta.
     * GET /api/rutas/{ruta_id}/valoraciones
     */
    public function indiceporRuta(Request $solicitud, Ruta $ruta)
    {
        try {
            // Verificar autorización
            if ($ruta->usuario_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado: Esta ruta no te pertenece',
                ], 403);
            }
 
            $valoraciones = $ruta->valoraciones()->get();
            $promedioValoracion = $ruta->obtenerPromedioValoracion();
 
            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Valoraciones obtenidas exitosamente',
                'datos' => $valoraciones,
                'promedio' => $promedioValoracion,
                'cantidad' => count($valoraciones),
            ], 200);
 
        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudieron obtener las valoraciones',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }
 
    /**
     * Crear una nueva valoración para una ruta.
     * POST /api/rutas/{ruta_id}/valoraciones
     */
    public function store(Request $solicitud, Ruta $ruta)
    {
        try {
            // Verificar autorización
            if ($ruta->usuario_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado: Esta ruta no te pertenece',
                ], 403);
            }
 
            // Validar datos
            $validado = $solicitud->validate([
                'puntuacion' => 'required|integer|between:1,5',
                'comentario' => 'nullable|string|max:1000',
            ]);
 
            // Crear valoración
            $valoracion = $ruta->valoraciones()->create([
                'puntuacion' => $validado['puntuacion'],
                'comentario' => $validado['comentario'] ?? null,
            ]);
 
            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Valoración creada exitosamente',
                'datos' => $valoracion,
            ], 201);
 
        } catch (ValidationException $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'Validación fallida',
                'errores' => $excepcion->errors(),
            ], 422);
        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo crear la valoración',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }
 
    /**
     * Obtener una valoración específica.
     * GET /api/valoraciones/{id}
     */
    public function show(Request $solicitud, Valoracion $valoracion)
    {
        try {
            // Verificar autorización
            if ($valoracion->ruta->usuario_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }
 
            return response()->json([
                'estado' => 'exito',
                'datos' => $valoracion,
            ], 200);
 
        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo obtener la valoración',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }
 
    /**
     * Actualizar una valoración.
     * PUT /api/valoraciones/{id}
     */
    public function update(Request $solicitud, Valoracion $valoracion)
    {
        try {
            // Verificar autorización
            if ($valoracion->ruta->usuario_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }
 
            // Validar datos
            $validado = $solicitud->validate([
                'puntuacion' => 'sometimes|integer|between:1,5',
                'comentario' => 'sometimes|nullable|string|max:1000',
            ]);
 
            // Actualizar valoración
            $valoracion->update($validado);
 
            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Valoración actualizada exitosamente',
                'datos' => $valoracion,
            ], 200);
 
        } catch (ValidationException $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'Validación fallida',
                'errores' => $excepcion->errors(),
            ], 422);
        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo actualizar la valoración',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }
 
    /**
     * Eliminar una valoración.
     * DELETE /api/valoraciones/{id}
     */
    public function destroy(Request $solicitud, Valoracion $valoracion)
    {
        try {
            // Verificar autorización
            if ($valoracion->ruta->usuario_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }
 
            $valoracion->delete();
 
            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Valoración eliminada exitosamente',
            ], 200);
 
        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo eliminar la valoración',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }
}
