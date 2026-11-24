<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CercaniaServiceInterface;
use App\Models\Sitio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDO;

class CercaniaService implements CercaniaServiceInterface
{
    /** Radio medio de la Tierra en kilómetros, usado por la fórmula de Haversine. */
    private const RADIO_TIERRA_KM = 6371.0;

    public function validarCoordenadas(float $latitud, float $longitud): bool
    {
        return is_finite($latitud)
            && is_finite($longitud)
            && $latitud >= -90.0
            && $latitud <= 90.0
            && $longitud >= -180.0
            && $longitud <= 180.0;
    }

    public function calcularDistanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        if (! $this->validarCoordenadas($lat1, $lng1) || ! $this->validarCoordenadas($lat2, $lng2)) {
            throw new InvalidArgumentException('Las coordenadas proporcionadas no son válidas.');
        }

        if ($lat1 === $lat2 && $lng1 === $lng2) {
            return 0.0;
        }

        $latitud1 = deg2rad($lat1);
        $latitud2 = deg2rad($lat2);
        $deltaLatitud = deg2rad($lat2 - $lat1);
        $deltaLongitud = deg2rad($lng2 - $lng1);

        $haversine = sin($deltaLatitud / 2) ** 2
            + cos($latitud1) * cos($latitud2) * sin($deltaLongitud / 2) ** 2;

        $haversine = max(0.0, min(1.0, $haversine));
        $distanciaKm = self::RADIO_TIERRA_KM * 2 * asin(sqrt($haversine));

        return round($distanciaKm, 2);
    }

    public function buscarSitiosCercanos(float $latitud, float $longitud, float $radioKm = 5.0): Collection
    {
        if (! $this->validarCoordenadas($latitud, $longitud)) {
            throw new InvalidArgumentException('Las coordenadas proporcionadas no son válidas.');
        }

        if (! is_finite($radioKm) || $radioKm <= 0.0) {
            throw new InvalidArgumentException('El radio de búsqueda debe ser mayor a cero kilómetros.');
        }

        $this->registrarFuncionesTrigonometricasSqlite();

        $latitudRad = number_format(deg2rad($latitud), 12, '.', '');
        $longitudRad = number_format(deg2rad($longitud), 12, '.', '');
        $cosenoLatitud = number_format(cos(deg2rad($latitud)), 12, '.', '');

        // Haversine SQL: sin²(Δlat/2) + cos(latOrigen)·cos(latSitio)·sin²(Δlng/2).
        // El CASE evita que un redondeo por encima de 1 rompa ASIN en MySQL.
        $mitadHaversine = sprintf(
            'SIN((RADIANS(sitios.latitud) - %1$s) / 2) * SIN((RADIANS(sitios.latitud) - %1$s) / 2)'
            .' + %2$s * COS(RADIANS(sitios.latitud))'
            .' * SIN((RADIANS(sitios.longitud) - %3$s) / 2) * SIN((RADIANS(sitios.longitud) - %3$s) / 2)',
            $latitudRad,
            $cosenoLatitud,
            $longitudRad,
        );

        $distanciaSql = sprintf(
            '%1$s * 2 * ASIN(SQRT(CASE WHEN %2$s < 0 THEN 0 WHEN %2$s > 1 THEN 1 ELSE %2$s END))',
            self::RADIO_TIERRA_KM,
            $mitadHaversine,
        );

        // El radio se inyecta como literal numérico: PDO bindea los floats como texto
        // y SQLite compararía "REAL <= TEXTO" como siempre verdadero.
        $radioSql = number_format($radioKm, 12, '.', '');

        return Sitio::query()
            ->select(['sitios.*'])
            ->selectRaw("ROUND({$distanciaSql}, 2) AS distancia_km")
            ->where('sitios.activo', true)
            ->whereNotNull('sitios.latitud')
            ->whereNotNull('sitios.longitud')
            ->whereRaw("{$distanciaSql} <= {$radioSql}")
            ->orderByRaw('distancia_km ASC')
            ->get();
    }

    /**
     * SQLite no trae funciones trigonométricas habilitadas por defecto.
     * Las registramos en el PDO para que la misma consulta SQL funcione
     * tanto en las pruebas (:memory:) como en MySQL.
     */
    private function registrarFuncionesTrigonometricasSqlite(): void
    {
        $conexion = DB::connection();

        if ($conexion->getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = $conexion->getPdo();

        if (! $pdo instanceof PDO) {
            return;
        }

        $funciones = [
            'RADIANS' => static fn ($valor): float => deg2rad((float) $valor),
            'SIN' => static fn ($valor): float => sin((float) $valor),
            'COS' => static fn ($valor): float => cos((float) $valor),
            'SQRT' => static fn ($valor): float => sqrt(max(0.0, (float) $valor)),
            'ASIN' => static fn ($valor): float => asin(max(-1.0, min(1.0, (float) $valor))),
        ];

        foreach ($funciones as $nombre => $funcion) {
            $pdo->sqliteCreateFunction($nombre, $funcion, 1);
        }
    }
}
