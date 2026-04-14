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
                
                // Actualizar distancia si está disponible
                if (isset($infoGpx['distancia_total_km']) && !$ruta->distancia_km) {
                    $ruta->update(['distancia_km' => $infoGpx['distancia_total_km']]);
                }

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

    /**
     * Parsear archivo GPX y extraer información
     * 
     * Usa sibyx/phpgpx (librería oficial verificada)
     * Con fallback a SimpleXML si falla
     * 
     * @return array
     */
    private function parsearArchivoGpx(string $ruta): array
    {
        $contenidoGpx = Storage::get($ruta);

        // Intentar usar sibyx/phpgpx
        try {
            return $this->parsearGpxConSibyx($contenidoGpx);
        } catch (\Exception $excepcion) {
            Log::warning('Error con sibyx/phpgpx, usando SimpleXML: ' . $excepcion->getMessage());
            // Fallback: parseo manual con SimpleXML
            return $this->parsearGpxConSimpleXml($contenidoGpx);
        }
    }

    /**
     * Parsear GPX usando librería sibyx/phpgpx
     * 
     * Instalar: composer require sibyx/phpgpx
     */
    private function parsearGpxConSibyx(string $contenidoGpx): array
    {
        try {
            // Crear instancia de phpGPX
            $phpGPX = new phpGPX();
            
            // Parsear el contenido XML
            $gpxFile = $phpGPX->parse($contenidoGpx);

            $datos = [
                'cantidad_waypoints' => count($gpxFile->waypoints ?? []),
                'cantidad_tracks' => count($gpxFile->tracks ?? []),
                'distancia_total_km' => 0,
                'latitud_inicio' => null,
                'longitud_inicio' => null,
                'elevacion_inicio' => null,
            ];

            // Calcular distancia total de todos los tracks
            $distanciaTotal = 0;
            if (!empty($gpxFile->tracks)) {
                foreach ($gpxFile->tracks as $track) {
                    // Recalcular estadísticas del track
                    $track->recalculateStats();
                    // Sumar la distancia del track (viene en metros)
                    $distanciaTotal += $track->length ?? 0;
                }
            }

            $datos['distancia_total_km'] = round($distanciaTotal / 1000, 2);

            // Obtener primer punto del primer track o waypoint
            if (!empty($gpxFile->tracks)) {
                $primerTrack = $gpxFile->tracks[0];
                if (!empty($primerTrack->segments)) {
                    $primerSegmento = $primerTrack->segments[0];
                    if (!empty($primerSegmento->points)) {
                        $primerPunto = $primerSegmento->points[0];
                        $datos['latitud_inicio'] = $primerPunto->latitude;
                        $datos['longitud_inicio'] = $primerPunto->longitude;
                        $datos['elevacion_inicio'] = $primerPunto->elevation ?? null;
                    }
                }
            } elseif (!empty($gpxFile->waypoints)) {
                // Si no hay tracks, usar primer waypoint
                $primerWaypoint = $gpxFile->waypoints[0];
                $datos['latitud_inicio'] = $primerWaypoint->latitude;
                $datos['longitud_inicio'] = $primerWaypoint->longitude;
                $datos['elevacion_inicio'] = $primerWaypoint->elevation ?? null;
            }

            return $datos;

        } catch (\Exception $excepcion) {
            throw new \Exception('Error al parsear GPX con sibyx/phpgpx: ' . $excepcion->getMessage());
        }
    }

    /**
     * Parsear GPX manualmente con SimpleXML (FALLBACK)
     */
    private function parsearGpxConSimpleXml(string $contenidoGpx): array
    {
        try {
            $xml = @simplexml_load_string($contenidoGpx);

            if (!$xml) {
                throw new \Exception('Formato XML de GPX inválido');
            }

            $datos = [];

            // Contar waypoints
            $waypoints = $xml->wpt ?? [];
            $datos['cantidad_waypoints'] = count($waypoints);

            // Contar tracks
            $tracks = $xml->trk ?? [];
            $datos['cantidad_tracks'] = count($tracks);

            // Extraer primer punto de waypoints
            if (count($waypoints) > 0) {
                $primerPunto = $waypoints[0];
                $datos['latitud_inicio'] = (float)$primerPunto['lat'];
                $datos['longitud_inicio'] = (float)$primerPunto['lon'];
                if (isset($primerPunto->ele)) {
                    $datos['elevacion_inicio'] = (float)$primerPunto->ele;
                }
            } 
            // Si no hay waypoints, extraer del primer track
            elseif (count($tracks) > 0 && isset($tracks[0]->trkseg)) {
                $trkseg = $tracks[0]->trkseg;
                if (isset($trkseg->trkpt) && count($trkseg->trkpt) > 0) {
                    $primerPuntoTrack = $trkseg->trkpt[0];
                    $datos['latitud_inicio'] = (float)$primerPuntoTrack['lat'];
                    $datos['longitud_inicio'] = (float)$primerPuntoTrack['lon'];
                    if (isset($primerPuntoTrack->ele)) {
                        $datos['elevacion_inicio'] = (float)$primerPuntoTrack->ele;
                    }
                }
            }

            // Calcular distancia (usando Haversine)
            $distancia = 0;
            foreach ($tracks as $track) {
                foreach ($track->trkseg as $segmento) {
                    $puntos = $segmento->trkpt ?? [];
                    for ($i = 0; $i < count($puntos) - 1; $i++) {
                        $lat1 = (float)$puntos[$i]['lat'];
                        $lon1 = (float)$puntos[$i]['lon'];
                        $lat2 = (float)$puntos[$i + 1]['lat'];
                        $lon2 = (float)$puntos[$i + 1]['lon'];

                        $distancia += $this->calcularDistancia($lat1, $lon1, $lat2, $lon2);
                    }
                }
            }

            $datos['distancia_total_km'] = round($distancia, 2);

            return $datos;

        } catch (\Exception $excepcion) {
            throw new \Exception('Error al parsear GPX con SimpleXML: ' . $excepcion->getMessage());
        }
    }

    /**
     * Calcular distancia entre dos puntos usando fórmula Haversine
     * 
     * @param float $lat1 Latitud del primer punto
     * @param float $lon1 Longitud del primer punto
     * @param float $lat2 Latitud del segundo punto
     * @param float $lon2 Longitud del segundo punto
     * @return float Distancia en km
     */
    private function calcularDistancia(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $radioTierra = 6371; // km

        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * asin(sqrt($a));

        return $radioTierra * $c;
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
}