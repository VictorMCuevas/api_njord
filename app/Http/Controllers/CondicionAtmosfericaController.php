<?php

namespace App\Http\Controllers;

use App\Models\CondicionAtmosferica;
use App\Models\Ruta;
use App\Traits\InterpreteClima;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CondicionAtmosfericaController extends Controller
{
    use InterpreteClima;
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
    public function show(Request $solicitud, CondicionAtmosferica $condicionAtmosferica)
    {
        try {
            if ($condicionAtmosferica->ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }

            return response()->json([
                'estado' => 'exito',
                'datos' => $condicionAtmosferica,
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
                'hora'     => 'nullable|date_format:H:i',
            ]);

            $hora = isset($validado['hora']) ? (int) explode(':', $validado['hora'])[0] : 12;

            $datos = $this->consultarArchivoOpenMeteo(
                $validado['latitud'],
                $validado['longitud'],
                $validado['fecha'],
                $hora
            );

            if (!$datos) {
                return response()->json([
                    'estado'  => 'error',
                    'mensaje' => 'No hay datos disponibles para esa fecha',
                ], 404);
            }

            return response()->json([
                'estado' => 'exito',
                'datos'  => array_merge(['fecha' => $validado['fecha']], $datos),
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
                'fecha'    => 'required|date|after_or_equal:today',
                'hora'     => 'nullable|date_format:H:i',
            ]);

            $hora = isset($validado['hora']) ? (int) explode(':', $validado['hora'])[0] : null;

            $datos = $this->consultarPronosticoOpenMeteo(
                $validado['latitud'],
                $validado['longitud'],
                $validado['fecha'],
                $hora
            );

            if (!$datos) {
                return response()->json([
                    'estado'  => 'error',
                    'mensaje' => 'No hay datos disponibles para esa fecha',
                ], 404);
            }

            return response()->json([
                'estado' => 'exito',
                'datos'  => $datos,
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

}
