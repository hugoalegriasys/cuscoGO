<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sitio extends Model
{
    protected $fillable = [
        'nombre',
        'slug',
        'horario_apertura',
        'horario_cierre',
    ];
}