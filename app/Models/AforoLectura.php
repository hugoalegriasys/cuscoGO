<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lectura histórica de ocupación de un sensor IoT (CUS-06).
 *
 * Estas filas son el respaldo del sistema: nunca se escriben durante un
 * fallback, solo cuando se recibe una lectura válida del sensor en vivo.
 */
class AforoLectura extends Model
{
    use HasFactory;

    protected $table = 'aforo_lecturas';

    protected $fillable = [
        'sitio_id',
        'sensor_id',
        'ocupacion',
        'fuente',
        'registrado_en',
    ];

    /**
     * Los casts garantizan que la ocupación sea entera y que registrado_en
     * sea Carbon; así podemos ordenar y formatear fechas con seguridad.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ocupacion' => 'integer',
            'registrado_en' => 'datetime',
        ];
    }

    /**
     * Sitio turístico al que pertenece la lectura.
     */
    public function sitio(): BelongsTo
    {
        return $this->belongsTo(Sitio::class);
    }
}
