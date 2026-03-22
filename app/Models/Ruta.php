<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ruta extends Model
{
    use HasFactory;
    protected $table = 'rutas';
    protected $fillable = [
        'id_user',
        'path',
        'descripcion',
        'provincia_inicio',
        'provincia_fin',
        'temperatura',
        'id_condicion_atmosferica',
        'puntuacion'
    ];
    protected $hidden = [];

    // Relación con CondicionAtmosferica (n:1), una ruta tiene una condición
    public function condicionAtmosferica(): BelongsTo
    {
        return $this->belongsTo(CondicionAtmosferica::class, 'id_condicion_atmosferica');
    }

    // Relación con User (n:1), una ruta pertenece a un usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
