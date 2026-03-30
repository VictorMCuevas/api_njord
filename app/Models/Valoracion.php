<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Valoracion extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'valoraciones';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ruta_id',
        'puntuacion',
        'comentario',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'puntuacion' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Una valoración pertenece a una ruta.
     */
    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    /**
     * Validar que la puntuación esté entre 1 y 5.
     *
     * @return bool
     */
    public function tienePuntuacionValida(): bool
    {
        return $this->puntuacion >= 1 && $this->puntuacion <= 5;
    }

    /**
     * Obtener descripción textual de la puntuación.
     *
     * @return string
     */
    public function obtenerDescripcionPuntuacion(): string
    {
        return match($this->puntuacion) {
            1 => 'Muy malo',
            2 => 'Malo',
            3 => 'Aceptable',
            4 => 'Bueno',
            5 => 'Excelente',
            default => 'Desconocido',
        };
    }
}