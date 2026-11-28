# CUS-06 — Telemetría IoT y Resiliencia de Aforo (Persistencia & Fallback)

## 1. Ficha de la Historia de Usuario y resumen de la funcionalidad

| Campo | Detalle |
|---|---|
| Identificador | CUS-06 |
| Título | Telemetría IoT y Resiliencia de Aforo (Persistencia & Fallback) |
| Módulo | Aforo y monitoreo de sitios turísticos |
| Actor principal | Turista / operador del destino |
| Estado | Implementado (Web + API + pruebas unitarias, feature y E2E) |

**Narración:** Como operador de cuscoGO, quiero consultar la ocupación de un sitio a partir de su sensor IoT y, cuando el sensor pierda señal, ver la última lectura registrada con una advertencia clara, para no entregar un error 500 ni un dato engañoso.

**Criterios de aceptación:**

- El cliente IoT consulta `GET /sensores/{sensorId}/aforo` con `acceptJson()`, `timeout(3)` y `retry(2, 200, throw: false)`.
- Solo se persiste una ocupación entera dentro de `0..100`; un payload inválido, HTTP 500, timeout o fallo de conexión se traducen a `SensorNoDisponibleException` (HTTP 503).
- La caché por sitio usa la clave `telemetria:sitio:{id}` y expira a los 15 segundos. Una respuesta desde caché tiene `origen = "cache"` y `stale = false`.
- Las lecturas válidas se guardan en `aforo_lecturas` con `registrado_en`; el fallback ordena por `registrado_en DESC, id DESC`.
- Sin sensor, pero con histórico: HTTP 200 con `stale = true` y `origen = "fallback_bd"`. Sin sensor y sin histórico: HTTP 503.
- El fallback **no sobrescribe** la caché viva, para permitir una recuperación inmediata cuando el sensor vuelve.
- El panel web mantiene la identidad editorial cálida (`#FAF6F0`, verde bosque `#0F3D2E`, serif Newsreader y sans Manrope) y muestra badges de origen y de estado degradado.

## 2. Arquitectura implementada: rutas y archivos por capa

### Rutas

| Método | URL | Nombre | Controlador / acción | Middleware |
|---|---|---|---|---|
| GET | `/api/sitios/{sitio}/telemetria` | `api.sitios.telemetria` | `TelemetriaController@showApi` | `api` |
| GET | `/sitios/telemetria` | `sitios.telemetria.index` | `TelemetriaController@index` | `web` |

`{sitio}` se enlaza condicionalmente en `routes/web.php`: para la ruta API se resuelve `App\Models\Sitio`, mientras que el resto de rutas con el mismo nombre (CUS-03) siguen resolviendo `SitioPatrimonial`. Sin este binding, la API recibiría el modelo equivocado.

### Archivos creados

- **Base de datos:** `database/migrations/2026_11_24_000002_add_sensor_id_to_sitios_table.php`, `database/migrations/2026_11_24_000003_create_aforo_lecturas_table.php`.
- **Modelos:** `app/Models/AforoLectura.php`; `app/Models/Sitio.php` (modificado con `slug`, `sensor_id` y `aforoLecturas()`).
- **Excepción:** `app/Exceptions/SensorNoDisponibleException.php`.
- **DTO y contratos:** `app/DTOs/TelemetriaAforoDTO.php`, `app/Contracts/SiteSensorClientInterface.php`, `app/Contracts/TelemetriaAforoServiceInterface.php`.
- **Servicios:** `app/Services/HttpSiteSensorClient.php`, `app/Services/TelemetriaAforoService.php`, `app/Services/SensorDemoClient.php` (demo web).
- **HTTP:** `app/Http/Controllers/TelemetriaController.php`.
- **Vista:** `resources/views/sitios/telemetria.blade.php`.
- **Pruebas:** `tests/Unit/TelemetriaAforoServiceTest.php`, `tests/Feature/TelemetriaFeatureTest.php`, `cypress/e2e/telemetria_local.cy.js`.
- **Documentación:** este archivo.

### Archivos modificados

- `config/services.php` y `.env.example`: bloque `services.iot_sensor.base_url` con valor por defecto `https://iot.cuscogo.local/api`.
- `app/Providers/AppServiceProvider.php`: bindings de `SiteSensorClientInterface` y `TelemetriaAforoServiceInterface`.
- `database/factories/SitioFactory.php` y `database/seeders/SitioSeeder.php`: códigos de sensor y lecturas históricas de ejemplo.
- `routes/api.php`, `routes/web.php`: nuevas rutas y binding condicional de `{sitio}`.

### Diagrama de dependencias

```
HttpSiteSensorClient ──► SiteSensorClientInterface ──► TelemetriaAforoService
                                                           │
                            Cache 15 s ────────────────────┤
                            AforoLectura (BD) ─────────────┤
                                                           ▼
                                          TelemetriaAforoDTO ─► Controller ─► API / Blade
```

`AppServiceProvider::register()` añade:

```php
$this->app->bind(SiteSensorClientInterface::class, HttpSiteSensorClient::class);
$this->app->bind(TelemetriaAforoServiceInterface::class, TelemetriaAforoService::class);
```

## 3. Entradas, validaciones y mensajes de error exactos

| Situación | Mensaje | Respuesta |
|---|---|---|
| Sensor HTTP 500, timeout o conexión fallida | `El sensor IoT no está disponible en este momento.` (o detalle del sensor) | Excepción de dominio → fallback |
| Payload sin `ocupacion` válida | `El sensor {id} devolvió una ocupación inválida.` | Excepción de dominio → fallback |
| Ocupación fuera de `0..100` | `La ocupación {n} está fuera del rango 0..100.` | Excepción de dominio → fallback |
| Sitio sin `sensor_id` | `El sitio no tiene un código de sensor IoT registrado.` | Excepción de dominio → fallback o 503 |
| Caída sin lecturas previas | `El sensor IoT no está disponible en este momento.` | HTTP 503 con `error: "SENSOR_NO_DISPONIBLE"` |

`SensorNoDisponibleException::render()` devuelve JSON 503 cuando `expectsJson()` o la ruta empieza por `api/`; en web redirige atrás con `withErrors(['sensor' => ...])` para mostrar una alerta controlada.

### Respuesta 503

```json
{
  "message": "El sensor IoT no está disponible en este momento.",
  "error": "SENSOR_NO_DISPONIBLE"
}
```

## 4. Estrategia de resiliencia y ejemplos de respuesta

### Cliente HTTP

`Http::baseUrl(config('services.iot_sensor.base_url'))->acceptJson()->timeout(3)->retry(2, 200, throw: false)`:

- `timeout(3)` limita cada intento a 3 segundos y evita bloquear la respuesta del usuario.
- `retry(2, 200, throw: false)` hace un segundo intento tras 200 ms y evita que Laravel convierta un HTTP 500 en `RequestException`; el cliente traduce el estado a excepción de dominio.
- `Http::fake()` intercepta estas peticiones en pruebas antes de que salgan a la red.

### Servicio de resiliencia

1. **Caché (15 s):** si `telemetria:sitio:{id}` existe, se devuelve un DTO con `origen = "cache"` y `stale = false` sin consultar al sensor.
2. **En vivo + persistencia:** se obtiene la lectura, se crea un registro en `aforo_lecturas`, se guarda en caché y se responde con `origen = "iot_vivo"`.
3. **Fallback:** si el cliente lanza `SensorNoDisponibleException`, se busca la última lectura del sitio; se responde con `stale = true` y `origen = "fallback_bd"` sin tocar la caché. Si no hay lectura, se relanza la excepción (503).

### DTO

```json
{
  "sitio_id": 6,
  "sitio_nombre": "Machu Picchu",
  "sensor_id": "MP-01",
  "ocupacion": 85,
  "stale": false,
  "origen": "iot_vivo",
  "actualizado_en": "2026-11-24 12:30:00"
}
```

La API envuelve el DTO como `{ "data": { ... } }` con HTTP 200.

### Modos del panel web

El panel usa `consultar=1` para no llamar al proveedor en la carga inicial. El modo por defecto es `vivo` para que el visitante vea "En vivo" sin depender de un proveedor configurado. El selector `modo` ofrece:

| Etiqueta visible | Modo | Cliente usado | Uso |
|---|---|---|---|
| En vivo · demo (85%) | `vivo` | `SensorDemoClient(false)` | Demo determinista con ocupación `85`; es la selección inicial. |
| Fallback · simular caída (resiliencia) | `fallback` | `SensorDemoClient(true)` | Simula caída y fuerza el camino de respaldo. |
| En vivo · sensor real (URL configurada) | `real` | `HttpSiteSensorClient` | Consulta real contra `IOT_SENSOR_BASE_URL`. |

Los modos demo invalidan la clave de caché del sitio para que "En vivo" no se confunda con "Caché (15s)" y "Fallback" sea reproducible. Son solo del panel web; la API siempre usa el cliente HTTP real.

## 5. Datos sembrados y selectores de prueba

| Sitio | Sensor | Lectura histórica inicial |
|---|---|---|
| Plaza de Armas Cusco | `PA-05` | 55 |
| Qorikancha | `QK-02` | 45 |
| Sacsayhuaman | `SC-03` | 62 |
| Museo de Arte Religioso | `MA-06` | Sin histórico (demo HTTP 503) |
| Ollantaytambo | `OL-04` | 38 |
| Machu Picchu | `MP-01` | 70 |

| `data-cy` | Elemento |
|---|---|
| `form-telemetria` | Formulario GET de consulta |
| `selector-sitio` | Selector de sitio con sensor |
| `modo` | Selector `vivo` / `fallback` / `real`, con `vivo` por defecto |
| `btn-consultar` | Botón de consulta |
| `resultado-telemetria` | Tarjeta de resultado |
| `ocupacion`, `barra-aforo` | Porcentaje y barra visual |
| `badge-origen` | En vivo (IoT) / Caché (15s) / Fallback · Respaldo histórico |
| `badge-stale` | `Sensor sin señal · Mostrando última lectura registrada` |
| `alerta-503` | Error controlado sin lecturas previas |
| `estado-vacio` | Estado inicial del panel |

## 6. Tipos de pruebas implementados

Todas las pruebas automatizadas están documentadas con la estructura `Given / When / Then` en sus comentarios. La cobertura de CUS-06 se organiza así:

| Tipo de prueba | Archivo | Alcance | Escenarios | Resultado |
|---|---|---|---|---|
| Unitaria | `tests/Unit/TelemetriaAforoServiceTest.php` | Aísla `HttpSiteSensorClient` y `TelemetriaAforoService` con `Http::fake()`, caché en memoria y SQLite; no levanta rutas HTTP. | 6 casos: payload válido, HTTP 500, timeout, fallback con `stale=true`, sin histórico (503) y persistencia con una sola petición. | Verde |
| Integración | `tests/Feature/TelemetriaFeatureTest.php` | Recorre ruta real, binding condicional de `{sitio}`, controlador, servicio, base de datos y render Blade. | 4 casos: API en vivo persiste, API con histórico devuelve `stale`, API sin histórico devuelve 503 y vista web carga. | Verde |
| Contrato HTTP | `tests/Feature/TelemetriaFeatureTest.php` | Validado dentro de integración: envelope `{ data: ... }`, códigos HTTP `200`/`503` y error `SENSOR_NO_DISPONIBLE`. | 3 casos API | Verde |
| Aceptación / E2E | `cypress/e2e/telemetria_local.cy.js` | Navegador real contra `php artisan serve` y base sembrada. Usa modos demo deterministas porque `Http::fake()` solo existe en PHPUnit. | 4 casos: carga inicial, vivo 85%, fallback 45% con `stale` y alerta HTTP 503. | Verde |
| Aceptación manual (QA/Jira) | Sección 7 | Guía ejecutable por QA en el entorno local, sin automatizar. | 5 casos `CP-CUSCOGO-026` a `CP-CUSCOGO-030` | Documentado |

Comandos de verificación:

```bash
php artisan migrate:fresh --seed
php artisan test
npm run build
php artisan serve
npx cypress run
```

CUS-06 aporta 10 pruebas PHPUnit y 4 pruebas Cypress. La suite completa incluye CUS-01 a CUS-06: `php artisan test` ejecuta 164 pruebas con SQLite `:memory:` y `CACHE_STORE=array`; Cypress usa la base sembrada del servidor local en `127.0.0.1:8000`.

## 7. Casos de prueba funcionales para QA / Jira

| ID | Pasos | Resultado esperado |
|---|---|---|
| CP-CUSCOGO-026 | Seleccionar Machu Picchu, modo `vivo`, consultar | 85%, badge `En vivo (IoT)`, barra al 85% |
| CP-CUSCOGO-027 | Seleccionar Qorikancha, modo `fallback`, consultar | 45%, badge `Fallback · Respaldo histórico` y aviso `stale = true` |
| CP-CUSCOGO-028 | Seleccionar Museo de Arte Religioso, modo `fallback`, consultar | Alerta HTTP 503 con `SENSOR_NO_DISPONIBLE` |
| CP-CUSCOGO-029 | Llamar dos veces seguidas a `GET /api/sitios/{id}/telemetria` con sensor activo | Segunda respuesta con `origen = "cache"`; una sola fila nueva en `aforo_lecturas` |
| CP-CUSCOGO-030 | Simular HTTP 500 del sensor con histórico en BD | HTTP 200 con `stale = true`; la caché viva no se sobrescribe |
