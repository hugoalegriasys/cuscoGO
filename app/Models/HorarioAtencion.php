<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioAtencion extends Model
{
    use HasFactory;

    protected $table = 'horarios_atencion';

    protected $fillable = [
        'sitio_id',
        'dia_semana',
        'apertura',
        'cierre',
        'activo',
    ];

    protected $attributes = [
        'activo' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sitio_id' => 'integer',
            'dia_semana' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function sitio(): BelongsTo
    {
        return $this->belongsTo(Sitio::class);
    }

    /**
     * Normaliza el valor TIME de MySQL (HH:MM:SS) al formato HH:MM.
     */
    protected function apertura(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): ?string => $value === null ? null : substr($value, 0, 5),
        );
    }

    /**
     * Normaliza el valor TIME de MySQL (HH:MM:SS) al formato HH:MM.
     */
    protected function cierre(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): ?string => $value === null ? null : substr($value, 0, 5),
        );
    }

    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }
}
