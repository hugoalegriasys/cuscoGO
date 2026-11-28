<?php

namespace App\Http\Controllers;

use App\Services\ResenaService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ResenaController extends Controller
{
    private array $sitiosTuristicos = [
        'machu-picchu' => [
            'id' => 'machu-picchu',
            'nombre' => 'Machu Picchu',
            'subtitulo' => 'Santuario Histórico y Maravilla del Mundo',
            'descripcion' => 'Impresionante ciudadela inca construida en el siglo XV, situada sobre una montaña a 2,430 m.s.n.m. Es el mayor ícono arquitectónico y turístico de Cusco y del Perú.',
            'ubicacion' => 'Provincia de Urubamba, Cusco',
            'altitud' => '2,430 m.s.n.m.',
            'horario' => '06:00 - 17:30',
        ],
        'sacsayhuaman' => [
            'id' => 'sacsayhuaman',
            'nombre' => 'Sacsayhuamán',
            'subtitulo' => 'Fortaleza Ceremonial Inca',
            'descripcion' => 'Colosal complejo arquitectónico inca famoso por sus enormes muros de piedra pulida encajadas con asombrosa precisión milimétrica.',
            'ubicacion' => 'A 2 km al norte de la Plaza Mayor de Cusco',
            'altitud' => '3,700 m.s.n.m.',
            'horario' => '07:00 - 17:30',
        ],
        'qorikancha' => [
            'id' => 'qorikancha',
            'nombre' => 'Qorikancha (Coricancha)',
            'subtitulo' => 'Templo del Sol',
            'descripcion' => 'El recinto sagrado más venerado del Imperio Incaico, dedicado a la deidad del Sol (Inti), sobre cuyas bases se edificó el convento de Santo Domingo.',
            'ubicacion' => 'Centro Histórico de Cusco',
            'altitud' => '3,400 m.s.n.m.',
            'horario' => '08:30 - 17:30',
        ],
        'ollantaytambo' => [
            'id' => 'ollantaytambo',
            'nombre' => 'Ollantaytambo',
            'subtitulo' => 'Ciudad Inca Viviente y Fortaleza',
            'descripcion' => 'Imponente sitio arqueológico en el Valle Sagrado de los Incas, conocido por sus terrazas agrícolas monumentales y su trazado urbano original conservado.',
            'ubicacion' => 'Valle Sagrado de los Incas, Urubamba',
            'altitud' => '2,792 m.s.n.m.',
            'horario' => '07:00 - 17:00',
        ],
    ];

    public function __construct(
        private ResenaService $resenaService
    ) {
    }

    /**
     * Muestra la página principal con los sitios turísticos de CuscoGO.
     */
    public function home(): View
    {
        $sitios = [];
        foreach ($this->sitiosTuristicos as $id => $info) {
            $info['promedio'] = $this->resenaService->calcularPromedioCalificaciones($id);
            $info['total_resenas'] = count($this->resenaService->obtenerResenas($id));
            $sitios[$id] = $info;
        }

        return view('home', compact('sitios'));
    }

    /**
     * Muestra la vista detallada de un sitio turístico con sus reseñas y formulario.
     */
    public function sitio(?string $sitioId = 'machu-picchu'): View
    {
        $sitioId = $sitioId ?? 'machu-picchu';
        $sitio = $this->sitiosTuristicos[$sitioId] ?? [
            'id' => $sitioId,
            'nombre' => ucwords(str_replace('-', ' ', $sitioId)),
            'subtitulo' => 'Atractivo Turístico de Cusco',
            'descripcion' => 'Descubre la magia, historia y cultura viva de este sitio turístico en Cusco.',
            'ubicacion' => 'Cusco, Perú',
            'altitud' => '3,399 m.s.n.m.',
            'horario' => '08:00 - 17:00',
        ];

        $resenas = $this->resenaService->obtenerResenas($sitioId);
        $promedio = $this->resenaService->calcularPromedioCalificaciones($sitioId);
        $totalResenas = count($resenas);

        return view('sitio', compact('sitio', 'resenas', 'promedio', 'totalResenas'));
    }

    /**
     * Procesa y registra una nueva reseña en la base de datos.
     */
    public function store(Request $request): RedirectResponse
    {
        $sitioId = $request->input('sitio_id', 'machu-picchu');
        $calificacionRaw = $request->input('calificacion');
        $comentarioRaw = $request->input('comentario');

        $errores = [];

        // Validación de calificación obligatoria y rango 1 a 5
        if ($calificacionRaw === null || $calificacionRaw === '') {
            $errores['calificacion'] = 'La calificación es obligatoria y debe ser entre 1 y 5 estrellas.';
        } elseif (!is_numeric($calificacionRaw) || (int) $calificacionRaw < 1 || (int) $calificacionRaw > 5) {
            $errores['calificacion'] = 'La calificación debe estar entre 1 y 5.';
        }

        // Validación de comentario obligatorio y no compuesto solo por espacios
        if ($comentarioRaw === null || trim((string) $comentarioRaw) === '') {
            $errores['comentario'] = 'El comentario es obligatorio y no puede estar vacío ni contener solo espacios.';
        }

        if (!empty($errores)) {
            return redirect()->back()
                ->withErrors($errores)
                ->withInput();
        }

        $this->resenaService->registrarResena(
            (int) $calificacionRaw,
            (string) $comentarioRaw,
            (string) $sitioId
        );

        return redirect()->route('sitios.show', ['sitio' => $sitioId])
            ->with('success', '¡Reseña registrada con éxito! Gracias por compartir tu experiencia.');
    }
}
