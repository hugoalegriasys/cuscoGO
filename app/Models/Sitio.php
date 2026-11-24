<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sitio extends Model
{
    use HasFactory;

    protected $table = 'sitios';

    protected $fillable = [
        'nombre',
        'categoria',
        'ubicacion',
        'latitud',
        'longitud',
        'descripcion',
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
            'activo' => 'boolean',
            'latitud' => 'float',
            'longitud' => 'float',
            'distancia_km' => 'float',
        ];
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioAtencion::class);
    }

    public function horariosActivos(): HasMany
    {
        return $this->hasMany(HorarioAtencion::class)->where('activo', true);
    }

    public function horarioDelDia(int $diaSemana): ?HorarioAtencion
    {
        if ($diaSemana < 1 || $diaSemana > 7) {
            return null;
        }

        return $this->horariosActivos()
            ->where('dia_semana', $diaSemana)
            ->first();
    }
}
