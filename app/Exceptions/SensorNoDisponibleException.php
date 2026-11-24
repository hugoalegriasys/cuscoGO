<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/**
 * Excepción de dominio para fallos del sensor IoT (CUS-06).
 *
 * Centralizar el código HTTP y el identificador de error evita que cada
 * controlador repita la lógica de respuesta. El render() se encarga de que
 * los clientes API reciban siempre el mismo contrato JSON, incluso cuando
 * la excepción no se capture explícitamente.
 */
class SensorNoDisponibleException extends RuntimeException
{
    /** Código HTTP por defecto: el sensor está caído, no es un error del cliente. */
    private const CODIGO_HTTP = 503;

    /** Identificador estable para que el frontend distinga este error de otros 503. */
    private const CODIGO_ERROR = 'SENSOR_NO_DISPONIBLE';

    public function __construct(?string $mensaje = null, ?Throwable $anterior = null)
    {
        // getCode() devuelve 503 por defecto; las pruebas pueden comprobarlo sin hacer HTTP.
        parent::__construct(
            $mensaje ?? 'El sensor IoT no está disponible en este momento.',
            self::CODIGO_HTTP,
            $anterior,
        );
    }

    /**
     * Devuelve el identificador contractual del error.
     */
    public function codigoError(): string
    {
        return self::CODIGO_ERROR;
    }

    /**
     * Renderiza la excepción según el tipo de petición.
     *
     * expectsJson() cubre peticiones con Accept: application/json y las
     * peticiones XHR; is('api/*') cubre además a clientes que llaman a /api
     * sin cabeceras. En esos casos respondemos JSON 503 con message y error.
     * Para navegador usamos redirect back con withErrors(): la sesión flash
     * conserva el mensaje y la vista puede mostrar una alerta controlada en
     * lugar de una página de error genérica.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => self::CODIGO_ERROR,
            ], self::CODIGO_HTTP);
        }

        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['sensor' => $this->getMessage()]);
    }
}
