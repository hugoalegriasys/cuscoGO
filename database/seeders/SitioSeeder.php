<?php

namespace Database\Seeders;

use App\Models\Sitio;
use Illuminate\Database\Seeder;

class SitioSeeder extends Seeder
{
    public function run(): void
    {
        $sitios = [
            ['nombre' => 'Plaza de Armas', 'slug' => 'plaza-de-armas', 'horario_apertura' => '06:00', 'horario_cierre' => '22:00'],
            ['nombre' => 'Qorikancha', 'slug' => 'qorikancha', 'horario_apertura' => '08:30', 'horario_cierre' => '17:30'],
            ['nombre' => 'Catedral de Cusco', 'slug' => 'catedral-de-cusco', 'horario_apertura' => '10:00', 'horario_cierre' => '18:00'],
            ['nombre' => 'Sacsayhuamán', 'slug' => 'sacsayhuaman', 'horario_apertura' => '07:00', 'horario_cierre' => '17:30'],
            ['nombre' => 'Tambomachay', 'slug' => 'tambomachay', 'horario_apertura' => '07:00', 'horario_cierre' => '17:00'],
            ['nombre' => 'Puka Pukara', 'slug' => 'puka-pukara', 'horario_apertura' => '07:00', 'horario_cierre' => '17:00'],
            ['nombre' => "Q'enqo", 'slug' => 'qenqo', 'horario_apertura' => '07:00', 'horario_cierre' => '17:00'],
            ['nombre' => 'Museo Inka', 'slug' => 'museo-inka', 'horario_apertura' => '08:00', 'horario_cierre' => '17:00'],
            ['nombre' => 'Mercado San Pedro', 'slug' => 'mercado-san-pedro', 'horario_apertura' => '06:00', 'horario_cierre' => '18:00'],
        ];

        foreach ($sitios as $sitio) {
            Sitio::query()->updateOrCreate(['slug' => $sitio['slug']], $sitio);
        }
    }
}