<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RutaController extends Controller
{
    /**
     * Obtener todas las rutas del usuario autenticado.
     * GET /api/rutas
     */
    public function index(Request $solicitud)
    {
        try {
            $rutas = Ruta::where('usuario_id', $solicitud->user()->id)
                ->with(['valoraciones', 'condicionesAtmosfericas'])
                ->get();

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Rutas obtenidas',
                'datos' => $rutas,
                'cantidad' => count($rutas),
            ], 200);

        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudieron obtener las rutas',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva ruta.
     * POST /api/rutas
     */
    public function store(Request $solicitud)
    {
        try {
            // Validar datos de entrada
            $validado = $solicitud->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'tipo_moto' => 'nullable|string|max:100',
                'estilo_conduccion' => 'nullable|string|max:100',
                'latitud' => 'nullable|numeric|between:-90,90',
                'longitud' => 'nullable|numeric|between:-180,180',
                'distancia_km' => 'nullable|numeric|min:0',
                'nivel_dificultad' => 'nullable|integer|between:1,5',
            ]);

            // Crear ruta asociada al usuario autenticado
            $ruta = Ruta::create([
                'usuario_id' => $solicitud->user()->id,
                'nombre' => $validado['nombre'],
                'descripcion' => $validado['descripcion'] ?? null,
                'tipo_moto' => $validado['tipo_moto'] ?? null,
                'estilo_conduccion' => $validado['estilo_conduccion'] ?? null,
                'latitud' => $validado['latitud'] ?? null,
                'longitud' => $validado['longitud'] ?? null,
                'distancia_km' => $validado['distancia_km'] ?? null,
                'nivel_dificultad' => $validado['nivel_dificultad'] ?? 1,
            ]);

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Ruta creada exitosamente. Sube el archivo GPX usando POST /api/rutas/{id}/subir-gpx',
                'datos' => $ruta,
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
                'mensaje' => 'No se pudo crear la ruta',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener una ruta específica.
     * GET /api/rutas/{id}
     */
    public function show(Request $solicitud, Ruta $ruta)
    {
        try {
            // Verificar autorización
            if ($ruta->usuario_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado: Esta ruta no te pertenece',
                ], 403);
            }

            // Cargar relaciones
            $ruta->load(['valoraciones', 'condicionesAtmosfericas']);

            return response()->json([
                'estado' => 'exito',
                'datos' => $ruta,
            ], 200);

        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo obtener la ruta',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar una ruta.
     * PUT /api/rutas/{id}
     */
    public function update(Request $solicitud, Ruta $ruta)
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
                'nombre' => 'sometimes|string|max:255',
                'descripcion' => 'sometimes|nullable|string',
                'tipo_moto' => 'sometimes|nullable|string|max:100',
                'estilo_conduccion' => 'sometimes|nullable|string|max:100',
                'latitud' => 'sometimes|nullable|numeric|between:-90,90',
                'longitud' => 'sometimes|nullable|numeric|between:-180,180',
                'distancia_km' => 'sometimes|nullable|numeric|min:0',
                'nivel_dificultad' => 'sometimes|nullable|integer|between:1,5',
            ]);

            // Actualizar ruta
            $ruta->update($validado);

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Ruta actualizada exitosamente',
                'datos' => $ruta,
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
                'mensaje' => 'No se pudo actualizar la ruta',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar una ruta.
     * DELETE /api/rutas/{id}
     */
    public function destroy(Request $solicitud, Ruta $ruta)
    {
        try {
            // Verificar autorización
            if ($ruta->usuario_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado: Esta ruta no te pertenece',
                ], 403);
            }

            // Eliminar archivo GPX asociado (automático via hook)
            $ruta->delete();

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Ruta eliminada exitosamente',
            ], 200);

        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo eliminar la ruta',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }
}