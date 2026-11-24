<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HorarioAtencion;
use App\Models\Sitio;
use Illuminate\Database\Seeder;

class SitioSeeder extends Seeder
{
    public function run(): void
    {
        $sitios = [
            [
                'nombre' => 'Plaza de Armas Cusco',
                'categoria' => 'Urbano',
                'ubicacion' => 'Centro historico de Cusco',
                'latitud' => -13.5167,
                'longitud' => -71.9781,
                'descripcion' => 'Plaza principal de la ciudad del Cusco.',
            ],
            [
                'nombre' => 'Qorikancha',
                'categoria' => 'Arqueologico',
                'ubicacion' => 'Av. El Sol, Cusco',
                'latitud' => -13.5200,
                'longitud' => -71.9750,
                'descripcion' => 'Templo inca del Sol y convento de Santo Domingo.',
            ],
            [
                'nombre' => 'Sacsayhuaman',
                'categoria' => 'Arqueologico',
                'ubicacion' => 'Zona norte de Cusco',
                'latitud' => -13.5098,
                'longitud' => -71.9817,
                'descripcion' => 'Complejo arqueologico inca ubicado sobre la ciudad del Cusco.',
            ],
            [
                'nombre' => 'Museo de Arte Religioso',
                'categoria' => 'Museo',
                'ubicacion' => 'Plaza Nazarenas, Cusco',
                'descripcion' => 'Coleccion de arte colonial y religioso del Cusco.',
            ],
            [
                'nombre' => 'Ollantaytambo',
                'categoria' => 'Arqueologico',
                'ubicacion' => 'Valle Sagrado de los Incas',
                'latitud' => -13.2584,
                'longitud' => -72.2633,
                'descripcion' => 'Fortaleza inca y pueblo tradicional del Valle Sagrado.',
            ],
            [
                'nombre' => 'Machu Picchu',
                'categoria' => 'Arqueologico',
                'ubicacion' => 'Distrito de Machupicchu, Urubamba',
                'latitud' => -13.1631,
                'longitud' => -72.5450,
                'descripcion' => 'Ciudadela inca declarada Patrimonio de la Humanidad.',
            ],
        ];

        foreach ($sitios as $datosSitio) {
            $sitio = Sitio::query()->updateOrCreate(
                ['nombre' => $datosSitio['nombre']],
                $datosSitio + ['activo' => true],
            );

            foreach (range(1, 7) as $diaSemana) {
                HorarioAtencion::query()->updateOrCreate(
                    [
                        'sitio_id' => $sitio->id,
                        'dia_semana' => $diaSemana,
                    ],
                    [
                        'apertura' => '09:00',
                        'cierre' => '17:00',
                        'activo' => true,
                    ],
                );
            }
        }
    }
}
