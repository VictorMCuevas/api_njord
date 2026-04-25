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
            if ($ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado: Esta ruta no te pertenece',
                ], 403);
            }

            $condiciones = $ruta->condicionesAtmosfericas()->get();

            return response()->json([
                'estado' => 'exito',
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
     * Obtener una condición atmosférica específica.
     * GET /api/condiciones-atmosfericas/{id}
     */
    public function show(Request $solicitud, CondicionAtmosferica $condicion)
    {
        try {
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
     * Consultar condiciones históricas de Open-Meteo sin guardar.
     * POST /api/clima/historico
     */
    public function consultarHistorico(Request $solicitud)
    {
        try {
            $validado = $solicitud->validate([
                'latitud'  => 'required|numeric|between:-90,90',
                'longitud' => 'required|numeric|between:-180,180',
                'fecha'    => 'required|date|before:today',
            ]);

            $respuesta = Http::get('https://archive-api.open-meteo.com/v1/archive', [
                'latitude'   => $validado['latitud'],
                'longitude'  => $validado['longitud'],
                'start_date' => $validado['fecha'],
                'end_date'   => $validado['fecha'],
                'daily'      => 'temperature_2m_max,precipitation_sum,windspeed_10m_max,weathercode',
                'timezone'   => 'Europe/Madrid',
            ]);

            if (!$respuesta->successful()) {
                throw new \Exception('Error al consultar Open-Meteo');
            }

            $datos = $respuesta->json();

            if (!isset($datos['daily']['time'][0])) {
                return response()->json([
                    'estado'  => 'error',
                    'mensaje' => 'No hay datos disponibles para esa fecha',
                ], 404);
            }

            return response()->json([
                'estado' => 'exito',
                'datos'  => [
                    'fecha'            => $datos['daily']['time'][0],
                    'temperatura'      => $datos['daily']['temperature_2m_max'][0],
                    'precipitacion_mm' => $datos['daily']['precipitation_sum'][0],
                    'velocidad_viento' => $datos['daily']['windspeed_10m_max'][0],
                    'tipo_clima'       => $this->interpretarCodigoWmo($datos['daily']['weathercode'][0] ?? 0),
                ],
            ], 200);

        } catch (ValidationException $excepcion) {
            return response()->json([
                'estado'  => 'error',
                'mensaje' => 'Validación fallida',
                'errores' => $excepcion->errors(),
            ], 422);
        } catch (\Exception $excepcion) {
            return response()->json([
                'estado'  => 'error',
                'mensaje' => 'No se pudo consultar el histórico',
                'error'   => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener predicción meteorológica de Open-Meteo sin guardar.
     * GET /api/clima/prediccion?latitud=X&longitud=Y&fecha=YYYY-MM-DD&hora=HH:MM
     *
     * Si se incluye hora, devuelve datos horarios precisos.
     * Si no, devuelve resumen diario.
     */
    public function obtenerPrediccion(Request $solicitud)
    {
        try {
            $validado = $solicitud->validate([
                'latitud'  => 'required|numeric|between:-90,90',
                'longitud' => 'required|numeric|between:-180,180',
                'fecha'    => 'required|date',
                'hora'     => 'nullable|date_format:H:i',
            ]);

            if (isset($validado['hora'])) {
                $respuesta = Http::get('https://api.open-meteo.com/v1/forecast', [
                    'latitude'   => $validado['latitud'],
                    'longitude'  => $validado['longitud'],
                    'start_date' => $validado['fecha'],
                    'end_date'   => $validado['fecha'],
                    'hourly'     => 'temperature_2m,precipitation,weathercode,windspeed_10m',
                    'timezone'   => 'Europe/Madrid',
                ]);

                if (!$respuesta->successful()) {
                    throw new \Exception('Error al obtener predicción');
                }

                $datos = $respuesta->json();
                $indiceHora = (int) explode(':', $validado['hora'])[0];

                if (!isset($datos['hourly']['time'][$indiceHora])) {
                    return response()->json([
                        'estado'  => 'error',
                        'mensaje' => 'No hay datos para esa hora',
                    ], 404);
                }

                return response()->json([
                    'estado' => 'exito',
                    'datos'  => [
                        'fecha'            => $validado['fecha'],
                        'hora'             => $validado['hora'],
                        'temperatura'      => $datos['hourly']['temperature_2m'][$indiceHora],
                        'precipitacion_mm' => $datos['hourly']['precipitation'][$indiceHora],
                        'velocidad_viento' => $datos['hourly']['windspeed_10m'][$indiceHora],
                        'tipo_clima'       => $this->interpretarCodigoWmo($datos['hourly']['weathercode'][$indiceHora] ?? 0),
                    ],
                ], 200);
            }

            // Sin hora: resumen diario
            $respuesta = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude'   => $validado['latitud'],
                'longitude'  => $validado['longitud'],
                'start_date' => $validado['fecha'],
                'end_date'   => $validado['fecha'],
                'daily'      => 'temperature_2m_max,temperature_2m_min,precipitation_sum,windspeed_10m_max,weathercode',
                'timezone'   => 'Europe/Madrid',
            ]);

            if (!$respuesta->successful()) {
                throw new \Exception('Error al obtener predicción');
            }

            $datos = $respuesta->json();

            if (!isset($datos['daily']['time'][0])) {
                return response()->json([
                    'estado'  => 'error',
                    'mensaje' => 'No hay datos disponibles para esa fecha',
                ], 404);
            }

            return response()->json([
                'estado' => 'exito',
                'datos'  => [
                    'fecha'                   => $datos['daily']['time'][0],
                    'temperatura_maxima'      => $datos['daily']['temperature_2m_max'][0],
                    'temperatura_minima'      => $datos['daily']['temperature_2m_min'][0],
                    'precipitacion_mm'        => $datos['daily']['precipitation_sum'][0],
                    'velocidad_viento_maxima' => $datos['daily']['windspeed_10m_max'][0],
                    'tipo_clima'              => $this->interpretarCodigoWmo($datos['daily']['weathercode'][0] ?? 0),
                ],
            ], 200);

        } catch (ValidationException $excepcion) {
            return response()->json([
                'estado'  => 'error',
                'mensaje' => 'Validación fallida',
                'errores' => $excepcion->errors(),
            ], 422);
        } catch (\Exception $excepcion) {
            return response()->json([
                'estado'  => 'error',
                'mensaje' => 'No se pudo obtener la predicción',
                'error'   => $excepcion->getMessage(),
            ], 500);
        }
    }

    private function interpretarCodigoWmo(int $codigo): string
    {
        return match(true) {
            $codigo === 0 => 'soleado',
            $codigo <= 3  => 'nublado',
            $codigo <= 48 => 'niebla',
            $codigo <= 67 => 'lluvia',
            $codigo <= 77 => 'nieve',
            $codigo <= 82 => 'lluvia',
            default       => 'tormenta',
        };
    }
}
