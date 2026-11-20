<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resena extends Model
{
    // Permite utilizar Resena::factory() en las pruebas.
    use HasFactory;

    // Campos que se pueden guardar mediante asignación masiva.
    protected $fillable = [
        'sitio_id',
        'calificacion',
        'comentario',
    ];

    public function sitio(): BelongsTo
    {
        return $this->belongsTo(Sitio::class);
    }
}