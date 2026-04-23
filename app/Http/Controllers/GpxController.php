<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use phpGPX\phpGPX;

class GpxController extends Controller
{
    /**
     * Subir archivo GPX para una ruta
     * POST /api/rutas/{id}/subir-gpx
     * 
     * Content-Type: multipart/form-data
     * Body:
     *   - archivo_gpx: archivo .gpx (máximo 10MB)
     */
    public function subirGpx(Request $solicitud, Ruta $ruta)
    {
        try {
            // Verificar autorización
            if ($ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado: Esta ruta no te pertenece',
                ], 403);
            }

            // Validar archivo
            $validado = $solicitud->validate([
                'archivo_gpx' => 'required|file|mimes:gpx,xml|max:10240', // máximo 10MB
            ]);

            // Si ya existe un archivo anterior, eliminarlo
            if ($ruta->ruta_gpx && Storage::exists($ruta->ruta_gpx)) {
                Storage::delete($ruta->ruta_gpx);
            }

            // Guardar nuevo archivo
            $archivo = $solicitud->file('archivo_gpx');
            $nombreArchivo = time() . '_' . $this->sanitizarNombreArchivo($archivo->getClientOriginalName());
            $ruta_almacenamiento = $archivo->storeAs(
                'gpx/usuario_' . $solicitud->user()->id,
                $nombreArchivo,
                'local'
            );

            // Actualizar ruta con path del archivo
            $ruta->update([
                'ruta_gpx' => $ruta_almacenamiento,
                'nombre_archivo_gpx_original' => $archivo->getClientOriginalName(),
            ]);

            // Intentar parsear el GPX para extraer información
            try {
                $infoGpx = $this->parsearArchivoGpx($ruta_almacenamiento);

                // Actualizar coordenadas iniciales si están disponibles
                if (isset($infoGpx['latitud_inicio']) && !$ruta->latitud) {
                    $ruta->update([
                        'latitud' => $infoGpx['latitud_inicio'],
                        'longitud' => $infoGpx['longitud_inicio'],
                    ]);
                }
            } catch (\Exception $excepcion) {
                // Si falla el parseo, no es crítico
                Log::warning('Error al parsear GPX para ruta ' . $ruta->id . ': ' . $excepcion->getMessage());
            }

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Archivo GPX subido exitosamente',
                'datos' => [
                    'ruta_id' => $ruta->id,
                    'ruta_gpx' => $ruta->ruta_gpx,
                    'nombre_archivo_gpx_original' => $ruta->nombre_archivo_gpx_original,
                    'url_descarga' => $ruta->obtenerUrlGpx(),
                    'tamaño_kb' => round($archivo->getSize() / 1024, 2),
                ],
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
                'mensaje' => 'No se pudo subir el archivo GPX',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Descargar archivo GPX de una ruta
     * GET /api/rutas/{id}/descargar-gpx
     */
    public function descargarGpx(Request $solicitud, Ruta $ruta)
    {
        try {
            // Verificar autorización
            if ($ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }

            // Verificar que existe el archivo
            if (!$ruta->archivoGpxExiste()) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'Archivo GPX no encontrado',
                ], 404);
            }

            // Descargar archivo
            return Storage::download(
                $ruta->ruta_gpx,
                $ruta->nombre_archivo_gpx_original ?? $ruta->nombre . '.gpx'
            );
        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo descargar el archivo GPX',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener información del archivo GPX (sin descargarlo)
     * GET /api/rutas/{id}/info-gpx
     */
    public function obtenerInfoGpx(Request $solicitud, Ruta $ruta)
    {
        try {
            // Verificar autorización
            if ($ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }

            // Verificar que existe el archivo
            if (!$ruta->archivoGpxExiste()) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'Archivo GPX no encontrado',
                ], 404);
            }

            // Parsear el archivo
            $infoGpx = $this->parsearArchivoGpx($ruta->ruta_gpx);

            return response()->json([
                'estado' => 'exito',
                'datos' => array_merge([
                    'ruta_id' => $ruta->id,
                    'ruta_gpx' => $ruta->ruta_gpx,
                    'nombre_archivo_gpx_original' => $ruta->nombre_archivo_gpx_original,
                    'tamaño_kb' => round(Storage::size($ruta->ruta_gpx) / 1024, 2),
                ], $infoGpx),
            ], 200);
        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo parsear el archivo GPX',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar archivo GPX de una ruta
     * DELETE /api/rutas/{id}/gpx
     */
    public function eliminarGpx(Request $solicitud, Ruta $ruta)
    {
        try {
            // Verificar autorización
            if ($ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }

            // Eliminar archivo
            if ($ruta->ruta_gpx && Storage::exists($ruta->ruta_gpx)) {
                Storage::delete($ruta->ruta_gpx);
            }

            // Actualizar ruta
            $ruta->update([
                'ruta_gpx' => null,
                'nombre_archivo_gpx_original' => null,
            ]);

            return response()->json([
                'estado' => 'exito',
                'mensaje' => 'Archivo GPX eliminado exitosamente',
            ], 200);
        } catch (\Exception $excepcion) {
            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo eliminar el archivo GPX',
                'error' => $excepcion->getMessage(),
            ], 500);
        }
    }

    private function parsearArchivoGpx(string $ruta): array
    {
        $gpxFile = (new phpGPX())->parse(Storage::get($ruta));

        $datos = [
            'cantidad_waypoints' => count($gpxFile->waypoints ?? []),
            'cantidad_tracks' => count($gpxFile->tracks ?? []),
            'latitud_inicio' => null,
            'longitud_inicio' => null,
            'elevacion_inicio' => null,
        ];

        if (!empty($gpxFile->tracks)) {
            $primerPunto = $gpxFile->tracks[0]->segments[0]->points[0] ?? null;
            if ($primerPunto) {
                $datos['latitud_inicio'] = $primerPunto->latitude;
                $datos['longitud_inicio'] = $primerPunto->longitude;
                $datos['elevacion_inicio'] = $primerPunto->elevation ?? null;
            }
        } elseif (!empty($gpxFile->waypoints)) {
            $primerWaypoint = $gpxFile->waypoints[0];
            $datos['latitud_inicio'] = $primerWaypoint->latitude;
            $datos['longitud_inicio'] = $primerWaypoint->longitude;
            $datos['elevacion_inicio'] = $primerWaypoint->elevation ?? null;
        }

        return $datos;
    }

    /**
     * Sanitizar nombre de archivo
     * 
     * @param string $nombreArchivo Nombre del archivo
     * @return string Nombre sanitizado
     */
    private function sanitizarNombreArchivo(string $nombreArchivo): string
    {
        // Eliminar caracteres especiales
        $nombreArchivo = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreArchivo);
        // Limitar longitud
        return substr($nombreArchivo, 0, 255);
    }

    /**
     * Obtener contenido GPX (XML) de una ruta
     * GET /api/rutas/{id}/gpx (o /gpx-contenido)
     * 
     * Retorna el archivo GPX como XML inline (visualización en navegador)
     */


    public function verGpx(Request $solicitud, Ruta $ruta){
        try {
            // Autorización
            if ($ruta->user_id !== $solicitud->user()->id) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'No autorizado',
                ], 403);
            }

            // Verificar existencia
            if (!$ruta->archivoGpxExiste()) {
                return response()->json([
                    'estado' => 'error',
                    'mensaje' => 'Archivo GPX no encontrado',
                ], 404);
            }

            // Nombre del archivo
            $nombreArchivo = $ruta->nombre_archivo_gpx_original
                ?? $ruta->nombre . '.gpx';

            // Respuesta optimizada (stream / descarga controlada)
            return Storage::download(
                $ruta->ruta_gpx,
                $nombreArchivo,
                [
                    'Content-Type' => 'application/gpx+xml; charset=UTF-8',
                    'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"',
                ]
            );
        } catch (\Throwable $excepcion) {
            Log::error('Error al servir GPX', [
                'ruta_id' => $ruta->id,
                'error' => $excepcion->getMessage(),
            ]);

            return response()->json([
                'estado' => 'error',
                'mensaje' => 'No se pudo obtener el contenido del GPX',
            ], 500);
        }
    }
}
