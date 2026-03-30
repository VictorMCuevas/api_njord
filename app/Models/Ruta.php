<?php

namespace App\Models;

use App\Models\Valoracion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Ruta extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'rutas';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario_id',
        'nombre',
        'descripcion',
        'ruta_gpx',
        'nombre_archivo_gpx_original',
        'tipo_moto',
        'estilo_conduccion',
        'latitud',
        'longitud',
        'distancia_km',
        'nivel_dificultad',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'distancia_km' => 'float',
    ];

    /**
     * Relación: Una ruta pertenece a un usuario.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación: Una ruta tiene muchas valoraciones.
     */
    public function valoraciones(): HasMany
    {
        return $this->hasMany(Valoracion::class, 'ruta_id');
    }

    /**
     * Relación: Una ruta tiene muchas condiciones atmosféricas.
     */
    public function condicionesAtmosfericas(): HasMany
    {
        return $this->hasMany(CondicionAtmosferica::class, 'ruta_id');
    }

    /**
     * Obtener el promedio de valoración de la ruta.
     *
     * @return float
     */
    public function obtenerPromedioValoracion(): float
    {
        return $this->valoraciones()->avg('puntuacion') ?? 0;
    }

    /**
     * Obtener la última condición atmosférica registrada.
     *
     * @return CondicionAtmosferica|null
     */
    public function obtenerUltimaCondicionClimatica()
    {
        return $this->condicionesAtmosfericas()->latest()->first();
    }

    /**
     * Obtener URL del archivo GPX para descargar.
     *
     * @return string|null
     */
    public function obtenerUrlGpx()
    {
        if (!$this->ruta_gpx) {
            return null;
        }
        return url('api/rutas/' . $this->id . '/descargar-gpx');
    }

    /**
     * Obtener ruta completa del archivo GPX en el servidor.
     *
     * @return string|null
     */
    public function obtenerRutaCompletaGpx()
    {
        if (!$this->ruta_gpx) {
            return null;
        }
        return Storage::path($this->ruta_gpx);
    }

    /**
     * Verificar si el archivo GPX existe.
     *
     * @return bool
     */
    public function archivoGpxExiste(): bool
    {
        if (!$this->ruta_gpx) {
            return false;
        }
        return Storage::exists($this->ruta_gpx);
    }

    /**
     * Obtener contenido del archivo GPX.
     *
     * @return string|null
     */
    public function obtenerContenidoGpx()
    {
        if (!$this->archivoGpxExiste()) {
            return null;
        }
        return Storage::get($this->ruta_gpx);
    }

    /**
     * Eliminar archivo GPX del servidor.
     *
     * @return bool
     */
    public function eliminarArchivoGpx(): bool
    {
        if (!$this->ruta_gpx) {
            return true;
        }

        if (Storage::exists($this->ruta_gpx)) {
            return Storage::delete($this->ruta_gpx);
        }

        return true;
    }

    /**
     * Hook: Eliminar archivo cuando se elimina la ruta.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($ruta) {
            // Eliminar archivo GPX cuando se elimina la ruta
            $ruta->eliminarArchivoGpx();
        });
    }
}