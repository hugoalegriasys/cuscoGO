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
                'nombre' => 'Qorikancha',
                'categoria' => 'Arqueologico',
                'ubicacion' => 'Av. El Sol, Cusco',
                'descripcion' => 'Templo inca del Sol y convento de Santo Domingo.',
            ],
            [
                'nombre' => 'Sacsayhuaman',
                'categoria' => 'Arqueologico',
                'ubicacion' => 'Zona norte de Cusco',
                'descripcion' => 'Complejo arqueologico inca ubicado sobre la ciudad del Cusco.',
            ],
            [
                'nombre' => 'Museo de Arte Religioso',
                'categoria' => 'Museo',
                'ubicacion' => 'Plaza Nazarenas, Cusco',
                'descripcion' => 'Coleccion de arte colonial y religioso del Cusco.',
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
