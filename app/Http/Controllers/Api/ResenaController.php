<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resena;
use App\Models\Sitio;
use App\Services\ResenaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResenaController extends Controller
{
    public function __construct(private ResenaService $resenaService)
    {
    }

    public function index(int $id): JsonResponse
    {
        $sitio = Sitio::query()->findOrFail($id);

        $resenas = Resena::query()
            ->where('sitio_id', $sitio->id)
            ->orderByDesc('created_at')
            ->get(['id', 'sitio_id', 'calificacion', 'comentario', 'created_at']);

        $promedio = $this->resenaService->calcularPromedioCalificacionesPorSitio($sitio->id);

        return response()->json([
            'resenas' => $resenas,
            'promedio' => $promedio,
        ], 200);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $sitio = Sitio::query()->findOrFail($id);

        $calificacion = $request->input('calificacion');
        $comentario = $request->input('comentario');

        if (!$this->resenaService->validarCalificacion($calificacion)) {
            return response()->json(['mensaje' => 'La calificación debe estar entre 1 y 5'], 422);
        }

        $comentarioTexto = is_string($comentario) ? $comentario : '';

        if (trim($comentarioTexto) === '') {
            return response()->json(['mensaje' => 'El comentario no puede estar vacío'], 422);
        }

        if (!$this->resenaService->validarComentario($comentarioTexto)) {
            return response()->json(['mensaje' => 'El comentario no debe superar los 500 caracteres'], 422);
        }

        $resena = Resena::query()->create([
            'sitio_id' => $sitio->id,
            'calificacion' => $calificacion,
            'comentario' => trim($comentarioTexto),
        ]);

        return response()->json([
            'mensaje' => 'Reseña registrada correctamente.',
            'resena' => $resena,
        ], 201);
    }
}
