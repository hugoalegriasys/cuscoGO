<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SiteSensorClientInterface;
use App\Exceptions\SensorNoDisponibleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP del sensor IoT de aforo (CUS-06).
 *
 * Aísla la comunicación externa detrás de SiteSensorClientInterface. Gracias
 * a este límite, el resto de la aplicación no sabe si el dato viene de una
 * URL real o de un Http::fake() en las pruebas.
 */
class HttpSiteSensorClient implements SiteSensorClientInterface
{
    /**
     * Construye la petición con configuration resistente.
     *
     * timeout(3) corta cada intento a los 3 segundos: sin este límite un
     * sensor colgado bloquearía la respuesta HTTP del usuario indefinidamente.
     * retry(2, 200, throw: false) permite un segundo intento tras 200 ms y
     * evita que Laravel lance RequestException por un HTTP 500; así podemos
     * traducir ese estado a nuestra excepción de dominio con código 503.
     */
    private function peticion(): PendingRequest
    {
        return Http::baseUrl((string) config('services.iot_sensor.base_url'))
            ->acceptJson()
            ->timeout(3)
            ->retry(2, 200, throw: false);
    }

    /**
     * Consulta /sensores/{sensorId}/aforo y devuelve una ocupación válida.
     *
     * @throws SensorNoDisponibleException Si hay error de red, HTTP 500 o un payload inválido.
     */
    public function obtenerLecturaSensor(string $sensorId): int
    {
        if (trim($sensorId) === '') {
            throw new SensorNoDisponibleException('El sitio no tiene un código de sensor IoT registrado.');
        }

        try {
            $respuesta = $this->peticion()->get("sensores/{$sensorId}/aforo");
        } catch (ConnectionException|RequestException $excepcion) {
            // ConnectionException cubre timeouts y caídas de red;
            // RequestException cubre fallos de transporte tras los reintentos.
            throw new SensorNoDisponibleException(
                'No se pudo conectar con el sensor IoT.',
                $excepcion,
            );
        }

        // successful() solo es true para 2xx: un 500 se transforma aquí en
        // error de dominio para que el servicio active su respaldo.
        if (! $respuesta->successful()) {
            throw new SensorNoDisponibleException("El sensor {$sensorId} devolvió HTTP {$respuesta->status()}.");
        }

        $ocupacion = $respuesta->json('ocupacion');

        /*
         * Aceptamos enteros y cadenas numéricas porque algunos sensores
         * serializan "85" como texto. Rechazamos decimales, booleanos y
         * valores ausentes para no persistir datos corruptos en el histórico.
         */
        if (! is_int($ocupacion) && ! (is_string($ocupacion) && ctype_digit($ocupacion))) {
            throw new SensorNoDisponibleException("El sensor {$sensorId} devolvió una ocupación inválida.");
        }

        $ocupacion = (int) $ocupacion;

        if ($ocupacion < 0 || $ocupacion > 100) {
            throw new SensorNoDisponibleException("La ocupación {$ocupacion} está fuera del rango 0..100.");
        }

        return $ocupacion;
    }
}
