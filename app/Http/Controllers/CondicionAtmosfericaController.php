<?php

namespace App\Http\Controllers;

use App\Models\CondicionAtmosferica;
use App\Models\Ruta;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CondicionAtmosfericaController extends Controller
{
    /**
     * Obtener todas las condiciones atmosféricas de una ruta.
     * GET /api/rutas/{ruta_id}/clima
     */
    public function indicePorRuta(Request $solicitud, Ruta $ruta)
    {
        try {
            // Verificar autorización
            if ($ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado: Esta ruta no te pertenece',
                ], 403);
            }

            $condiciones = $ruta->condicionesAtmosfericas()->get();

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Condiciones atmosféricas obtenidas exitosamente',
                'datos' => $condiciones,
                'cantidad' => count($condiciones),
            ], 200);

        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudieron obtener las condiciones',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Registrar una nueva condición atmosférica para una ruta.
     * POST /api/rutas/{ruta_id}/clima
     */
    public function store(Request $solicitud, Ruta $ruta)
    {
        try {
            // Verificar autorización
            if ($ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado: Esta ruta no te pertenece',
                ], 403);
            }

            // Validar datos
            $validado = $solicitud->validate([
                'fecha' => 'required|date',
                'temperatura' => 'nullable|numeric|between:-50,60',
                'humedad' => 'nullable|integer|between:0,100',
                'velocidad_viento' => 'nullable|numeric|min:0',
                'precipitacion' => 'nullable|numeric|min:0',
                'tipo_clima' => 'nullable|string|max:100',
            ]);

            // Crear condición
            $condicion = $ruta->condicionesAtmosfericas()->create([
                'fecha' => $validado['fecha'],
                'temperatura' => $validado['temperatura'] ?? null,
                'humedad' => $validado['humedad'] ?? null,
                'velocidad_viento' => $validado['velocidad_viento'] ?? null,
                'precipitacion' => $validado['precipitacion'] ?? null,
                'tipo_clima' => $validado['tipo_clima'] ?? null,
            ]);

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Condición atmosférica registrada exitosamente',
                'datos' => $condicion,
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
                'mensaje' => 'No se pudo registrar la condición',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener una condición atmosférica específica.
     * GET /api/condiciones-atmosfericas/{id}
     */
    public function show(Request $solicitud, CondicionAtmosferica $condicion)
    {
        try {
            // Verificar autorización
            if ($condicion->ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }

            return response()->json([
                'estado' => 'exito',
                'datos' => $condicion,
            ], 200);

        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo obtener la condición',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar una condición atmosférica.
     * PUT /api/condiciones-atmosfericas/{id}
     */
    public function update(Request $solicitud, CondicionAtmosferica $condicion)
    {
        try {
            // Verificar autorización
            if ($condicion->ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }

            // Validar datos
            $validado = $solicitud->validate([
                'fecha' => 'sometimes|date',
                'temperatura' => 'sometimes|nullable|numeric|between:-50,60',
                'humedad' => 'sometimes|nullable|integer|between:0,100',
                'velocidad_viento' => 'sometimes|nullable|numeric|min:0',
                'precipitacion' => 'sometimes|nullable|numeric|min:0',
                'tipo_clima' => 'sometimes|nullable|string|max:100',
            ]);

            // Actualizar condición
            $condicion->update($validado);

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Condición actualizada exitosamente',
                'datos' => $condicion,
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
                'mensaje' => 'No se pudo actualizar la condición',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar una condición atmosférica.
     * DELETE /api/condiciones-atmosfericas/{id}
     */
    public function destroy(Request $solicitud, CondicionAtmosferica $condicion)
    {
        try {
            // Verificar autorización
            if ($condicion->ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }

            $condicion->delete();

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Condición eliminada exitosamente',
            ], 200);

        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo eliminar la condición',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener predicción de clima para una ubicación y fecha.
     * GET /api/clima/prediccion?latitud=40.4168&longitud=-3.7038&fecha=2024-04-15
     * 
     * Usa API abierta: Open-Meteo (gratuita, sin API key)
     */
    public function obtenerPrediccion(Request $solicitud)
    {
        try {
            // Validar parámetros
            $validado = $solicitud->validate([
                'latitud' => 'required|numeric|between:-90,90',
                'longitud' => 'required|numeric|between:-180,180',
                'fecha' => 'required|date',
            ]);

            // Llamar a Open-Meteo API
            $respuesta = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $validado['latitud'],
                'longitude' => $validado['longitud'],
                'start_date' => $validado['fecha'],
                'end_date' => $validado['fecha'],
                'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,windspeed_10m_max,relative_humidity_2m_max',
                'timezone' => 'Europe/Madrid',
            ]);

            if (!$respuesta->successful()) {
                throw new \Exception('Error al obtener predicción de clima');
            }

            $datos = $respuesta->json();

            // Procesar respuesta
            if (!isset($datos['daily']['time'][0])) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No hay datos disponibles para esa fecha',
                ], 404);
            }

            $prediccion = [
                'fecha' => $datos['daily']['time'][0],
                'temperatura_maxima' => $datos['daily']['temperature_2m_max'][0],
                'temperatura_minima' => $datos['daily']['temperature_2m_min'][0],
                'precipitacion_mm' => $datos['daily']['precipitation_sum'][0],
                'velocidad_viento_maxima' => $datos['daily']['windspeed_10m_max'][0],
                'humedad_relativa_maxima' => $datos['daily']['relative_humidity_2m_max'][0],
            ];

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Predicción obtenida exitosamente',
                'datos' => $prediccion,
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
                'mensaje' => 'No se pudo obtener la predicción',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }
}