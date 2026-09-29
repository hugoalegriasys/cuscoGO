<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resena extends Model
{
    // Permite utilizar Resena::factory() en las pruebas.
    use HasFactory;

    // Campos que se pueden guardar mediante asignación masiva.
    protected $fillable = [
        'calificacion',
        'comentario',
    ];
}