<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait InterpreteClima
{
    /**
     * Consulta Open-Meteo Archive y devuelve las condiciones de una hora concreta.
     * Devuelve null si la petición falla o no hay datos disponibles.
     */
    private function consultarArchivoOpenMeteo(float $latitud, float $longitud, string $fecha, int $hora = 12): ?array
    {
        $respuesta = Http::get('https://archive-api.open-meteo.com/v1/archive', [
            'latitude'   => $latitud,
            'longitude'  => $longitud,
            'start_date' => $fecha,
            'end_date'   => $fecha,
            'hourly'     => 'temperature_2m,precipitation,windspeed_10m,weathercode',
            'timezone'   => 'Europe/Madrid',
        ]);

        if (!$respuesta->successful() || !isset($respuesta->json()['hourly']['time'])) {
            return null;
        }

        $hourly = $respuesta->json()['hourly'];

        return [
            'temperatura'      => $hourly['temperature_2m'][$hora],
            'precipitacion'    => $hourly['precipitation'][$hora],
            'velocidad_viento' => $hourly['windspeed_10m'][$hora],
            'tipo_clima'       => $this->interpretarCodigoWmo($hourly['weathercode'][$hora] ?? 0),
        ];
    }

    /**
     * Consulta Open-Meteo Forecast para una fecha futura.
     * Si se pasa hora devuelve datos horarios; si no, resumen diario.
     * Devuelve null si la petición falla o no hay datos.
     */
    private function consultarPronosticoOpenMeteo(float $latitud, float $longitud, string $fecha, ?int $hora = null): ?array
    {
        if ($hora !== null) {
            $respuesta = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude'   => $latitud,
                'longitude'  => $longitud,
                'start_date' => $fecha,
                'end_date'   => $fecha,
                'hourly'     => 'temperature_2m,precipitation,weathercode,windspeed_10m',
                'timezone'   => 'Europe/Madrid',
            ]);

            if (!$respuesta->successful() || !isset($respuesta->json()['hourly']['time'][$hora])) {
                return null;
            }

            $hourly = $respuesta->json()['hourly'];

            return [
                'fecha'            => $fecha,
                'hora'             => str_pad($hora, 2, '0', STR_PAD_LEFT) . ':00',
                'temperatura'      => $hourly['temperature_2m'][$hora],
                'precipitacion_mm' => $hourly['precipitation'][$hora],
                'velocidad_viento' => $hourly['windspeed_10m'][$hora],
                'tipo_clima'       => $this->interpretarCodigoWmo($hourly['weathercode'][$hora] ?? 0),
            ];
        }

        $respuesta = Http::get('https://api.open-meteo.com/v1/forecast', [
            'latitude'   => $latitud,
            'longitude'  => $longitud,
            'start_date' => $fecha,
            'end_date'   => $fecha,
            'daily'      => 'temperature_2m_max,temperature_2m_min,precipitation_sum,windspeed_10m_max,weathercode',
            'timezone'   => 'Europe/Madrid',
        ]);

        if (!$respuesta->successful() || !isset($respuesta->json()['daily']['time'][0])) {
            return null;
        }

        $daily = $respuesta->json()['daily'];

        return [
            'fecha'                   => $daily['time'][0],
            'temperatura_maxima'      => $daily['temperature_2m_max'][0],
            'temperatura_minima'      => $daily['temperature_2m_min'][0],
            'precipitacion_mm'        => $daily['precipitation_sum'][0],
            'velocidad_viento_maxima' => $daily['windspeed_10m_max'][0],
            'tipo_clima'              => $this->interpretarCodigoWmo($daily['weathercode'][0] ?? 0),
        ];
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
