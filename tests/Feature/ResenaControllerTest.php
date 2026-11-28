<?php

namespace Tests\Feature;

use App\Models\Resena;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResenaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_principal_muestra_sitios_turisticos(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Sitios Turísticos de Cusco');
        $response->assertSee('Machu Picchu');
        $response->assertSee('Sacsayhuamán');
        $response->assertSee('Ingresar al Sitio');
    }

    public function test_pagina_sitio_turistico_carga_correctamente(): void
    {
        $response = $this->get(route('sitios.show', 'machu-picchu'));

        $response->assertStatus(200);
        $response->assertSee('Machu Picchu');
        $response->assertSee('Publicar una Reseña');
        $response->assertSee('Calificación (1 a 5 estrellas)');
    }

    public function test_registro_exitoso_de_resena_persiste_en_base_de_datos(): void
    {
        $datos = [
            'sitio_id' => 'machu-picchu',
            'calificacion' => 5,
            'comentario' => 'Una maravilla del mundo que superó todas mis expectativas.',
        ];

        $response = $this->post(route('resenas.store'), $datos);

        $response->assertRedirect(route('sitios.show', ['sitio' => 'machu-picchu']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('resenas', [
            'sitio_id' => 'machu-picchu',
            'calificacion' => 5,
            'comentario' => 'Una maravilla del mundo que superó todas mis expectativas.',
        ]);
    }

    public function test_registro_falla_si_falta_calificacion(): void
    {
        $datos = [
            'sitio_id' => 'machu-picchu',
            'calificacion' => '',
            'comentario' => 'Excelente lugar histórico.',
        ];

        $response = $this->post(route('resenas.store'), $datos);

        $response->assertSessionHasErrors(['calificacion']);
        $this->assertDatabaseCount('resenas', 0);
    }

    public function test_registro_falla_si_calificacion_es_menor_a_uno(): void
    {
        $datos = [
            'sitio_id' => 'machu-picchu',
            'calificacion' => 0,
            'comentario' => 'Visita regular.',
        ];

        $response = $this->post(route('resenas.store'), $datos);

        $response->assertSessionHasErrors(['calificacion']);
        $this->assertDatabaseCount('resenas', 0);
    }

    public function test_registro_falla_si_calificacion_es_mayor_a_cinco(): void
    {
        $datos = [
            'sitio_id' => 'machu-picchu',
            'calificacion' => 6,
            'comentario' => 'Increíble lugar.',
        ];

        $response = $this->post(route('resenas.store'), $datos);

        $response->assertSessionHasErrors(['calificacion']);
        $this->assertDatabaseCount('resenas', 0);
    }

    public function test_registro_falla_si_falta_comentario(): void
    {
        $datos = [
            'sitio_id' => 'machu-picchu',
            'calificacion' => 5,
            'comentario' => '',
        ];

        $response = $this->post(route('resenas.store'), $datos);

        $response->assertSessionHasErrors(['comentario']);
        $this->assertDatabaseCount('resenas', 0);
    }

    public function test_registro_rechaza_comentario_compuesto_solo_por_espacios(): void
    {
        $datos = [
            'sitio_id' => 'machu-picchu',
            'calificacion' => 4,
            'comentario' => '       ',
        ];

        $response = $this->post(route('resenas.store'), $datos);

        $response->assertSessionHasErrors(['comentario']);
        $this->assertDatabaseCount('resenas', 0);
    }

    public function test_promedio_de_calificaciones_se_calcula_y_muestra_en_la_vista(): void
    {
        Resena::create([
            'sitio_id' => 'machu-picchu',
            'calificacion' => 5,
            'comentario' => 'Increíble experiencia.',
        ]);

        Resena::create([
            'sitio_id' => 'machu-picchu',
            'calificacion' => 3,
            'comentario' => 'Buen viaje.',
        ]);

        $response = $this->get(route('sitios.show', 'machu-picchu'));

        $response->assertStatus(200);
        // El promedio de 5 y 3 es 4.0
        $response->assertSee('4.0');
        $response->assertSee('2 reseñas registradas');
        $response->assertSee('Increíble experiencia.');
        $response->assertSee('Buen viaje.');
    }
}
