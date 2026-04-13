<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CondicionAtmosferica extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'condiciones_atmosfericas';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ruta_id',
        'fecha',
        'temperatura',
        'humedad',
        'velocidad_viento',
        'precipitacion',
        'tipo_clima',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha' => 'date',
        'temperatura' => 'float',
        'humedad' => 'integer',
        'velocidad_viento' => 'float',
        'precipitacion' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Una condición atmosférica pertenece a una ruta.
     */
    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    /**
     * Obtener descripción textual del tipo de clima.
     *
     * @return string
     */
    public function obtenerDescripcionClima(): string
    {
        return match($this->tipo_clima) {
            'soleado' => '☀️ Soleado',
            'nublado' => '☁️ Nublado',
            'lluvia' => '🌧️ Lluvia',
            'nieve' => '❄️ Nieve',
            'niebla' => '🌫️ Niebla',
            'tormenta' => '⛈️ Tormenta',
            default => '❓ Desconocido',
        };
    }

    /**
     * Verificar si las condiciones son seguras para conducir.
     *
     * @return bool
     */
    public function esSegurParaConductir(): bool
    {
        // Lluvia fuerte o velocidad de viento muy alta = no seguro
        if ($this->precipitacion > 10 || $this->velocidad_viento > 50) {
            return false;
        }

        // Nieve o tormenta = no seguro
        if (in_array($this->tipo_clima, ['nieve', 'tormenta'])) {
            return false;
        }

        return true;
    }

    /**
     * Obtener índice de peligro (0-10).
     *
     * @return int
     */
    public function obtenerIndiceDepeligro(): int
    {
        $indice = 0;

        // Lluvia: +2 por cada mm de precipitación
        $indice += min(($this->precipitacion ?? 0) * 2, 8);

        // Viento: +1 por cada 10 km/h
        $indice += min((($this->velocidad_viento ?? 0) / 10), 5);

        // Temperatura muy baja: +2
        if (($this->temperatura ?? 0) < 0) {
            $indice += 2;
        }

        // Clima peligroso: +3
        if (in_array($this->tipo_clima, ['nieve', 'tormenta', 'niebla'])) {
            $indice += 3;
        }

        return min($indice, 10);
    }
}