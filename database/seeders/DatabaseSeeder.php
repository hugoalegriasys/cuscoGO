<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SitioPatrimonial;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(SitioSeeder::class);

        // Registra sitios patrimoniales de ejemplo solo si la tabla está vacía,
        // para no duplicar datos en ejecuciones repetidas del seeder.
        if (SitioPatrimonial::query()->count() === 0) {
            $sitios = [
                [
                    'nombre' => 'Machu Picchu',
                    'direccion' => 'Distrito de Machupicchu, Provincia de Urubamba',
                    'descripcion' => 'Ciudadela inca declarada Patrimonio de la Humanidad por la UNESCO.',
                    'hora_apertura' => '06:00',
                    'hora_cierre' => '17:00',
                    'activo' => true,
                ],
                [
                    'nombre' => 'Qorikancha',
                    'direccion' => 'Calle Santo Domingo, Cusco',
                    'descripcion' => 'Templo del Sol, recinto ceremonial inca de gran significado religioso.',
                    'hora_apertura' => '08:30',
                    'hora_cierre' => '18:00',
                    'activo' => true,
                ],
                [
                    'nombre' => 'Sacsayhuamán',
                    'direccion' => 'Camino Sacsayhuamán s/n, Cusco',
                    'descripcion' => 'Fortaleza ceremonial inca con muros de piedra megalítica.',
                    'hora_apertura' => '07:00',
                    'hora_cierre' => '18:00',
                    'activo' => true,
                ],
                [
                    'nombre' => 'Salineras de Maras',
                    'direccion' => 'Distrito de Maras, Provincia de Urubamba',
                    'descripcion' => 'Complejo de pozas de sal explotadas desde épocas preincaicas.',
                    'hora_apertura' => '08:00',
                    'hora_cierre' => '17:30',
                    'activo' => false,
                ],
            ];

            foreach ($sitios as $sitio) {
                SitioPatrimonial::query()->create($sitio);
            }
        }
    }
}
