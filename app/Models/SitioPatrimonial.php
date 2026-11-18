<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class SitioPatrimonial extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo en la base de datos.
     */
    protected $table = 'sitios_patrimoniales';

    /**
     * Campos que se pueden guardar mediante asignación masiva.
     */
    protected $fillable = [
        'nombre',
        'direccion',
        'descripcion',
        'hora_apertura',
        'hora_cierre',
        'activo',
    ];

    /**
     * Conversión explícita de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'hora_apertura' => 'string',
            'hora_cierre' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Normaliza la hora de apertura al formato "HH:MM" al leer y
     * valida el formato antes de persistir al escribir.
     */
    protected function horaApertura(): Attribute
    {
        return $this->atributoHora('hora de apertura');
    }

    /**
     * Normaliza la hora de cierre al formato "HH:MM" al leer y
     * valida el formato antes de persistir al escribir.
     */
    protected function horaCierre(): Attribute
    {
        return $this->atributoHora('hora de cierre');
    }

    /**
     * Construye el accessor/mutator compartido para las columnas de hora.
     */
    private function atributoHora(string $etiquetaCampo): Attribute
    {
        return new Attribute(
            get: function ($valor) use ($etiquetaCampo): ?string {
                if ($valor === null || trim((string) $valor) === '') {
                    return null;
                }

                try {
                    return Carbon::parse($valor)->format('H:i');
                } catch (\Throwable $e) {
                    throw new InvalidArgumentException(
                        sprintf('La %s registrada en el sitio patrimonial no es interpretable.', $etiquetaCampo)
                    );
                }
            },
            set: function ($valor) use ($etiquetaCampo): string {
                if (! is_string($valor) || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', trim($valor))) {
                    throw new InvalidArgumentException(
                        sprintf('La %s debe tener el formato HH:MM dentro del rango 00:00 a 23:59.', $etiquetaCampo)
                    );
                }

                return trim($valor);
            },
        );
    }

    /**
     * Devuelve el horario de atención legible del sitio (apertura - cierre).
     */
    public function horarioAtencion(): string
    {
        $apertura = $this->hora_apertura ?? '--:--';
        $cierre = $this->hora_cierre ?? '--:--';

        return sprintf('%s - %s', $apertura, $cierre);
    }

    /**
     * Scope para obtener únicamente los sitios activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
