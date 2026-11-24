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

    /*
     * slug y sensor_id se asignan en masa desde seeders y factories (CUS-06).
     * sensor_id es el puente con el proveedor IoT; usamos su valor tal cual
     * para construir la URL /sensores/{sensorId}/aforo del cliente HTTP.
     */
    protected $fillable = [
        'nombre',
        'slug',
        'sensor_id',
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

    /**
     * Histórico de telemetría del sitio (CUS-06).
     *
     * La relación permite consultar las lecturas desde el modelo y, sobre
     * todo, sirve de respaldo cuando el sensor IoT no responde.
     */
    public function aforoLecturas(): HasMany
    {
        return $this->hasMany(AforoLectura::class);
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
