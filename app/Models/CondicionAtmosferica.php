<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CondicionAtmosferica extends Model
{
    use HasFactory;
    protected $table = 'condiciones_atmosfericas';
    protected $fillable = [
        'nombre'
    ];
    protected $hidden = [];

    //Relación con rutas (1:n), una condición, muchas rutas
    public function rutas(): HasMany
    {
        return $this->hasMany(Ruta::class, 'id_condicion_atmosferica');
    }

}
