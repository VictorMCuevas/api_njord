<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Condicion_Atmosferica extends Model
{
    use HasFactory;
    protected $table = 'condiciones_atmosfericas';
    protected $fillable = [
        'nombre'
    ];
    protected $hidden = [];
}
