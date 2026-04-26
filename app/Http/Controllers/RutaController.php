<?php

namespace App\Http\Controllers;

use App\Models\CondicionAtmosferica;
use App\Models\Ruta;
use App\Traits\InterpreteClima;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RutaController extends Controller
{
    use InterpreteClima;
    /**
     * Obtener todas las rutas del usuario autenticado.
     * GET /api/rutas
     */
    public function index(Request $solicitud)
    {
        try {
            $rutas = Ruta::where('user_id', $solicitud->user()->id)
                ->with(['condicionesAtmosfericas'])
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
                'nombre'              => 'required|string|max:255',
                'fecha'               => 'nullable|date',
                'descripcion'         => 'nullable|string',
                'tipo_moto'           => 'nullable|string|max:100',
                'estilo_conduccion'   => 'nullable|string|max:100',
                'latitud'             => 'nullable|numeric|between:-90,90',
                'longitud'            => 'nullable|numeric|between:-180,180',
                'distancia_km'        => 'nullable|numeric|min:0',
                'nivel_dificultad'    => 'nullable|integer|between:1,5',
                'valoracion_personal' => 'nullable|integer|between:1,5',
                'inicio.latitud'      => 'nullable|numeric|between:-90,90',
                'inicio.longitud'     => 'nullable|numeric|between:-180,180',
                'inicio.hora'         => 'nullable|date_format:H:i',
                'medio.latitud'       => 'nullable|numeric|between:-90,90',
                'medio.longitud'      => 'nullable|numeric|between:-180,180',
                'medio.hora'          => 'nullable|date_format:H:i',
                'fin.latitud'         => 'nullable|numeric|between:-90,90',
                'fin.longitud'        => 'nullable|numeric|between:-180,180',
                'fin.hora'            => 'nullable|date_format:H:i',
            ]);

            // Crear ruta asociada al usuario autenticado
            $ruta = Ruta::create([
                'user_id' => $solicitud->user()->id,
                'nombre' => $validado['nombre'],
                'fecha' => $validado['fecha'] ?? null,
                'descripcion' => $validado['descripcion'] ?? null,
                'tipo_moto' => $validado['tipo_moto'] ?? null,
                'estilo_conduccion' => $validado['estilo_conduccion'] ?? null,
                'latitud' => $validado['latitud'] ?? $solicitud->input('inicio.latitud') ?? null,
                'longitud' => $validado['longitud'] ?? $solicitud->input('inicio.longitud') ?? null,
                'latitud_medio' => $solicitud->input('medio.latitud') ?? null,
                'longitud_medio' => $solicitud->input('medio.longitud') ?? null,
                'latitud_fin' => $solicitud->input('fin.latitud') ?? null,
                'longitud_fin' => $solicitud->input('fin.longitud') ?? null,
                'distancia_km' => $validado['distancia_km'] ?? null,
                'nivel_dificultad' => $validado['nivel_dificultad'] ?? 1,
                'valoracion_personal' => $validado['valoracion_personal'] ?? null,
            ]);

            if ($ruta->fecha && $ruta->fecha->lt(now())) {
                try {
                    $this->guardarClimaHistorico($ruta, $solicitud);
                } catch (\Exception $excepcion) {
                    Log::warning('Error al guardar clima histórico para ruta ' . $ruta->id . ': ' . $excepcion->getMessage());
                }
            }

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
            if ($ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado: Esta ruta no te pertenece',
                ], 403);
            }

            // Cargar relaciones
            $ruta->load(['condicionesAtmosfericas']);

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
            if ($ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado: Esta ruta no te pertenece',
                ], 403);
            }

            // Validar datos
            $validado = $solicitud->validate([
                'nombre' => 'sometimes|string|max:255',
                'fecha' => 'sometimes|nullable|date',
                'descripcion' => 'sometimes|nullable|string',
                'tipo_moto' => 'sometimes|nullable|string|max:100',
                'estilo_conduccion' => 'sometimes|nullable|string|max:100',
                'latitud' => 'sometimes|nullable|numeric|between:-90,90',
                'longitud' => 'sometimes|nullable|numeric|between:-180,180',
                'distancia_km' => 'sometimes|nullable|numeric|min:0',
                'nivel_dificultad' => 'sometimes|nullable|integer|between:1,5',
                'valoracion_personal' => 'sometimes|nullable|integer|between:1,5',
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
            if ($ruta->user_id !== $solicitud->user()->id) {
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

    /**
     * Consulta Open-Meteo Archive para los 3 puntos de la ruta y guarda las condiciones
     * meteorológicas históricas. Solo se ejecuta si la ruta tiene fecha pasada y
     * el request incluye coordenadas de inicio, medio o fin. No duplica si ya existen.
     */
    private function guardarClimaHistorico(Ruta $ruta, Request $solicitud): void
    {
        if ($ruta->condicionesAtmosfericas()->exists()) {
            return;
        }

        $fecha = $ruta->fecha->format('Y-m-d');
        $puntos = [
            'inicio' => $solicitud->input('inicio'),
            'medio'  => $solicitud->input('medio'),
            'fin'    => $solicitud->input('fin'),
        ];

        foreach ($puntos as $nombrePunto => $coords) {
            if (!$coords || !isset($coords['latitud'], $coords['longitud'])) {
                continue;
            }

            $hora = isset($coords['hora']) ? (int) explode(':', $coords['hora'])[0] : 12;

            $datos = $this->consultarArchivoOpenMeteo(
                $coords['latitud'],
                $coords['longitud'],
                $fecha,
                $hora
            );

            if (!$datos) {
                continue;
            }

            CondicionAtmosferica::create(array_merge([
                'ruta_id'       => $ruta->id,
                'punto_en_ruta' => $nombrePunto,
                'fecha'         => $fecha,
            ], $datos));
        }
    }

}
