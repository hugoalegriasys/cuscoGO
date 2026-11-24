<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AforoLectura;
use App\Models\HorarioAtencion;
use App\Models\Sitio;
use Illuminate\Database\Seeder;

class SitioSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Códigos de sensor realistas y estables (CUS-06). Los usamos tanto
         * en la demo web como en Cypress: al ser fijos, la última lectura
         * histórica de cada sitio siempre es la misma después de sembrar.
         * El Museo de Arte Religioso se deja sin histórico a propósito para
         * poder demostrar el HTTP 503 cuando no hay respaldo en BD.
         */
        $lecturasIniciales = [
            'MP-01' => 70,
            'QK-02' => 45,
            'SC-03' => 62,
            'OL-04' => 38,
            'PA-05' => 55,
        ];

        $sitios = [
            [
                'nombre' => 'Plaza de Armas Cusco',
                'slug' => 'plaza-de-armas-cusco',
                'sensor_id' => 'PA-05',
                'categoria' => 'Urbano',
                'ubicacion' => 'Centro historico de Cusco',
                'latitud' => -13.5167,
                'longitud' => -71.9781,
                'descripcion' => 'Plaza principal de la ciudad del Cusco.',
            ],
            [
                'nombre' => 'Qorikancha',
                'slug' => 'qorikancha',
                'sensor_id' => 'QK-02',
                'categoria' => 'Arqueologico',
                'ubicacion' => 'Av. El Sol, Cusco',
                'latitud' => -13.5200,
                'longitud' => -71.9750,
                'descripcion' => 'Templo inca del Sol y convento de Santo Domingo.',
            ],
            [
                'nombre' => 'Sacsayhuaman',
                'slug' => 'sacsayhuaman',
                'sensor_id' => 'SC-03',
                'categoria' => 'Arqueologico',
                'ubicacion' => 'Zona norte de Cusco',
                'latitud' => -13.5098,
                'longitud' => -71.9817,
                'descripcion' => 'Complejo arqueologico inca ubicado sobre la ciudad del Cusco.',
            ],
            [
                'nombre' => 'Museo de Arte Religioso',
                'slug' => 'museo-de-arte-religioso',
                'sensor_id' => 'MA-06',
                'categoria' => 'Museo',
                'ubicacion' => 'Plaza Nazarenas, Cusco',
                'descripcion' => 'Coleccion de arte colonial y religioso del Cusco.',
            ],
            [
                'nombre' => 'Ollantaytambo',
                'slug' => 'ollantaytambo',
                'sensor_id' => 'OL-04',
                'categoria' => 'Arqueologico',
                'ubicacion' => 'Valle Sagrado de los Incas',
                'latitud' => -13.2584,
                'longitud' => -72.2633,
                'descripcion' => 'Fortaleza inca y pueblo tradicional del Valle Sagrado.',
            ],
            [
                'nombre' => 'Machu Picchu',
                'slug' => 'machu-picchu',
                'sensor_id' => 'MP-01',
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

            /*
             * Sembramos una lectura anterior para que el fallback tenga un
             * respaldo real desde el primer arranque. updateOrCreate evita
             * duplicados si el seeder se ejecuta más de una vez sobre la
             * misma base de datos.
             */
            $sensorId = (string) $datosSitio['sensor_id'];
            if (isset($lecturasIniciales[$sensorId])) {
                AforoLectura::query()->updateOrCreate(
                    [
                        'sitio_id' => $sitio->id,
                        'sensor_id' => $sensorId,
                        'fuente' => 'historial_demo',
                    ],
                    [
                        'ocupacion' => $lecturasIniciales[$sensorId],
                        'registrado_en' => now()->subMinutes(5),
                    ],
                );
            }

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
