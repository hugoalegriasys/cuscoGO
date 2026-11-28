# CUS-03 — Disponibilidad de Servicio / Sitios Turísticos

## 1. Resumen de la funcionalidad

CUS-03 permite determinar si un sitio patrimonial está operativo (`ABIERTO` / `CERRADO`) para una fecha y hora dadas.

- **Web:** `GET /sitios/disponibilidad` muestra el formulario de consulta. El usuario puede elegir un sitio activo registrado o ingresar un horario manual (`apertura` y `cierre`), indicar una fecha opcional y la hora a evaluar. El envío a `POST /sitios/disponibilidad/evaluar` redirige a la misma vista con el resultado en sesión o con los errores de validación.
- **API:** `POST /api/disponibilidad/evaluar` recibe los mismos datos y responde JSON: `200` con el estado calculado o `422` con errores de validación o de negocio.
- El cálculo se delega en `DisponibilidadService`; el controlador resuelve la zona horaria, la fecha, el sitio y el horario aplicable.

## 2. Rutas y archivos implementados

### Rutas

| Método | URL | Nombre | Controlador / acción | Middleware |
|---|---|---|---|---|
| GET | `/sitios/disponibilidad` | `sitios.disponibilidad.index` | `DisponibilidadController@index` | `web` |
| POST | `/sitios/disponibilidad/evaluar` | `sitios.disponibilidad.evaluar` | `DisponibilidadController@evaluar` | `web` + CSRF |
| POST | `/api/disponibilidad/evaluar` | `api.disponibilidad.evaluar` | `DisponibilidadController@evaluar` | `api` |

No hay autenticación ni autorización en estas rutas (`authorize(): true`).

### Archivos por capa

- **Rutas:** `routes/web.php` (modificado), `routes/api.php` (creado), `bootstrap/app.php` (modificado para registrar `routes/api.php`).
- **HTTP:** `app/Http/Controllers/DisponibilidadController.php`, `app/Http/Requests/ConsultarDisponibilidadRequest.php`.
- **Dominio:** `app/Contracts/DisponibilidadServiceInterface.php`, `app/Services/DisponibilidadService.php`, `app/Providers/AppServiceProvider.php` (binding interfaz → implementación).
- **Persistencia:** `app/Models/Sitio.php`, `app/Models/HorarioAtencion.php`, `database/migrations/2026_09_30_000000_create_sitios_table.php`, `database/migrations/2026_10_01_000000_create_horarios_atencion_table.php`, `database/factories/SitioFactory.php`, `database/factories/HorarioAtencionFactory.php`, `database/seeders/SitioSeeder.php`, `database/seeders/DatabaseSeeder.php`.
- **Vista:** `resources/views/sitios/disponibilidad.blade.php`.
- **Pruebas:** `tests/Unit/DisponibilidadServiceTest.php`, `tests/Unit/EstadoOperatividadSitioServiceTest.php`, `tests/Feature/DisponibilidadFeatureTest.php`, `tests/Feature/DisponibilidadModelTest.php`, `tests/Feature/SitioPatrimonialControllerTest.php`, `cypress/e2e/disponibilidad_local.cy.js`, `cypress.config.js`, `package.json`.

## 3. Entradas, validaciones y mensajes de error exactos

Web y API comparten `ConsultarDisponibilidadRequest`.

| Campo | Reglas exactas | Mensaje exacto |
|---|---|---|
| `sitio_id` | `nullable`, `integer`, `exists:sitios,id` | `El sitio seleccionado no es válido.` / `El sitio seleccionado no existe en el sistema.` |
| `fecha` | `nullable`, `date_format:Y-m-d` | `La fecha debe tener el formato AAAA-MM-DD.` |
| `hora_actual` | `required`, `string`, `regex:/^([0-1][0-9]\|2[0-3]):[0-5][0-9]$/` | `La hora a evaluar es obligatoria.` / `La hora a evaluar debe tener el formato HH:MM (00:00 a 23:59).` |
| `apertura` | `nullable`, `required_without:sitio_id`, `string`, `regex:/^([0-1][0-9]\|2[0-3]):[0-5][0-9]$/` | `La hora de apertura es obligatoria cuando no se selecciona un sitio.` / `La hora de apertura debe tener el formato HH:MM (00:00 a 23:59).` |
| `cierre` | `nullable`, `required_without:sitio_id`, `string`, `regex:/^([0-1][0-9]\|2[0-3]):[0-5][0-9]$/` | `La hora de cierre es obligatoria cuando no se selecciona un sitio.` / `La hora de cierre debe tener el formato HH:MM (00:00 a 23:59).` |
| `estado` | `prohibited` | `No está permitido enviar el estado desde el cliente.` |
| `abierto` | `prohibited` | `No está permitido enviar el resultado desde el cliente.` |
| `operativo` | `prohibited` | `No está permitido enviar el resultado desde el cliente.` |

- **Validación cruzada (solo modo manual):** si no hay `sitio_id` y no existen errores previos, se exige `apertura < cierre` mediante `DisponibilidadService::esHorarioValido()`. El error se asigna al campo `cierre` con el mensaje exacto: `La hora de cierre debe ser posterior a la hora de apertura.`
- **Web:** el formulario incluye `@csrf`; ante cualquier fallo redirige atrás con `withInput()` y los errores en la bolsa estándar.
- **API, fallo de validación (422):**
  ```json
  {
    "message": "Los datos proporcionados no son válidos.",
    "errors": {
      "hora_actual": ["La hora a evaluar debe tener el formato HH:MM (00:00 a 23:59)."]
    }
  }
  ```
- Las reglas `string` no tienen mensaje personalizado; con `APP_LOCALE=en` Laravel usaría su mensaje por defecto en inglés para ese caso límite.

### Errores de negocio

Se lanzan como `InvalidArgumentException` y se capturan en el controlador. En API devuelven `422` con `errors.disponibilidad`; en Web redirigen atrás con `errors.disponibilidad`.

| Situación | Mensaje exacto |
|---|---|
| Sitio no encontrado tras la validación | `El sitio patrimonial seleccionado no existe.` |
| Sitio inactivo | `El sitio patrimonial "%s" no se encuentra habilitado para visitas.` |
| Sin horario activo para el día ISO de la fecha | `El sitio patrimonial "%s" no tiene un horario de atención activo para el día %s.` |
| Fecha inválida en el controlador | `La fecha proporcionada no es válida.` |
| Hora actual inválida en el servicio | `La hora actual "%s" no es válida. Debe usar el formato HH:MM dentro del rango 00:00 - 23:59.` |
| Horario incoherente en el servicio | `El horario de atención "%s - %s" no es válido. La apertura debe ser anterior al cierre y ambos deben usar el formato HH:MM.` |

Ejemplo de error de negocio API:

```json
{
  "message": "El sitio patrimonial \"Qorikancha\" no tiene un horario de atención activo para el día Martes.",
  "errors": {
    "disponibilidad": ["El sitio patrimonial \"Qorikancha\" no tiene un horario de atención activo para el día Martes."]
  }
}
```

## 4. Lógica de negocio y respuestas del sistema

### Cálculo en `DisponibilidadService`

1. `esFormatoHoraValido()` acepta únicamente `HH:MM` de 24 horas con el patrón `^([0-1][0-9]|2[0-3]):[0-5][0-9]$`.
2. `esHorarioValido()` exige ambos formatos válidos y `apertura < cierre` estricto; no admite horarios nocturnos ni apertura igual al cierre.
3. `estaOperativo()` lanza excepción si la hora actual o el horario son inválidos; convierte a minutos y devuelve `hora_actual >= apertura && hora_actual <= cierre` (ambos extremos inclusive).
4. El servicio no conoce la zona horaria ni la base de datos.

### Zona horaria, fecha y modo

- El controlador fija `America/Lima` (`CarbonImmutable::now('America/Lima')`).
- `index()` calcula `horaActual` (`H:i`), `fechaActual` (`Y-m-d`) y `diaSemanaActual` (ISO: 1 = Lunes … 7 = Domingo).
- Si no se envía `fecha`, se usa el día actual en `America/Lima`. Si se envía, se parsea con `!Y-m-d` en `America/Lima` y se obtiene `dayOfWeekIso`.
- `hora_actual` se interpreta literalmente como `HH:MM`; no se convierte de zona horaria.
- `fecha` no restringe fechas pasadas o futuras; solo debe ser válida.

| Condición | `apertura` / `cierre` usados | `sitio` en la respuesta | `origen` |
|---|---|---|---|
| `sitio_id` vacío | Los enviados en la petición | `null` | `manual` |
| `sitio_id` presente | Horario activo del sitio para el día ISO de `fecha` | Nombre del sitio | `sitio` |

En modo sitio se valida que el sitio exista, esté activo y tenga un horario activo para ese día. Si se envían `apertura`/`cierre` junto con `sitio_id`, se validan por formato si están presentes, pero no se usan para el cálculo.

### Respuesta JSON `200` (modo manual)

Petición: `hora_actual=10:00`, `apertura=09:00`, `cierre=17:00`, `fecha=2026-11-10` (martes).

```json
{
  "abierto": true,
  "estado": "ABIERTO",
  "mensaje": "El sitio \"Horario consultado\" está ABIERTO en este momento.",
  "hora_actual": "10:00",
  "apertura": "09:00",
  "cierre": "17:00",
  "horario": "09:00 - 17:00",
  "sitio": null,
  "fecha": "2026-11-10",
  "dia_semana": 2,
  "dia_semana_nombre": "Martes",
  "origen": "manual"
}
```

En modo sitio, `sitio` contiene el nombre (por ejemplo `"Qorikancha"`), `origen` es `"sitio"` y el mensaje usa ese nombre.

### Visualización en la vista Blade

- **Resultado exitoso:** tarjeta verde si `abierto = true` o roja si es `false`; badge con `estado` (`ABIERTO` / `CERRADO`); mensaje principal; filas: Hora evaluada, Horario de atención (`09:00 - 17:00`), Sitio (nombre o `Horario manual`), Fecha y Día de la semana.
- **Errores:** tarjeta roja con badge `ERROR DE VALIDACIÓN` y la lista de mensajes; además, un bloque superior `Revisa los datos ingresados:`.
- **UI:** el modo manual oculta/deshabilita `apertura` y `cierre` al elegir un sitio; el botón `Ahora` completa `hora_actual` con la hora de Lima; al enviar se deshabilita el botón y se muestra `Evaluando...`.

## 5. Base de datos y restricciones

### Tabla `sitios`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | BIGINT UNSIGNED | PK autoincremental |
| `nombre` | VARCHAR(150) | NOT NULL, UNIQUE `sitios_nombre_unique` |
| `categoria` | VARCHAR(80) | NULL |
| `ubicacion` | VARCHAR(200) | NULL |
| `descripcion` | TEXT | NULL |
| `activo` | BOOLEAN | NOT NULL, DEFAULT `true` |
| `created_at`, `updated_at` | TIMESTAMP | NULL |

Índice: `sitios_activo_nombre_index` sobre (`activo`, `nombre`).

### Tabla `horarios_atencion`

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | BIGINT UNSIGNED | PK autoincremental |
| `sitio_id` | BIGINT UNSIGNED | NOT NULL, FK → `sitios.id`, `ON DELETE CASCADE` |
| `dia_semana` | TINYINT UNSIGNED | NOT NULL; comentario: 1 = Lunes, 7 = Domingo (ISO 8601) |
| `apertura` | TIME | NOT NULL |
| `cierre` | TIME | NOT NULL |
| `activo` | BOOLEAN | NOT NULL, DEFAULT `true` |
| `created_at`, `updated_at` | TIMESTAMP | NULL |

- UNIQUE `horarios_atencion_sitio_dia_unique` sobre (`sitio_id`, `dia_semana`).
- Índice `horarios_atencion_sitio_activo_index` sobre (`sitio_id`, `activo`).

### Restricciones CHECK

Se crean únicamente cuando el driver es MySQL:

- `chk_horarios_dia_semana`: `CHECK (dia_semana BETWEEN 1 AND 7)`.
- `chk_horarios_rango_horario`: `CHECK (cierre > apertura)`.

En SQLite (entorno local y PHPUnit `:memory:`) no se crean estos `CHECK`; la coherencia queda cubierta por la validación de aplicación. Los accessors de `HorarioAtencion` normalizan `TIME` de MySQL (`HH:MM:SS`) a `HH:MM`.

## 6. Tipos de pruebas y escenarios verificados

### Tipos de prueba existentes

| Tipo | Estado | Archivos / ubicación | Qué cubre |
|---|---|---|---|
| Unitarias | Ejecutadas | `tests/Unit/DisponibilidadServiceTest.php`, `tests/Unit/EstadoOperatividadSitioServiceTest.php` | Lógica pura del servicio: formato `HH:MM`, coherencia `apertura < cierre`, límites inclusivos, excepciones y conversión del DTO de sitio. |
| Integración / Feature | Ejecutadas | `tests/Feature/DisponibilidadFeatureTest.php`, `tests/Feature/DisponibilidadModelTest.php`, `tests/Feature/SitioPatrimonialControllerTest.php` | HTTP web y API, validaciones, sesión, persistencia SQLite `:memory:`, relación sitio–horario, normalización de horas y `sitios.verificar`. |
| Aceptación / E2E | Ejecutadas | `cypress/e2e/disponibilidad_local.cy.js` | 5 escenarios `CA-01` a `CA-04` sobre `/sitios/disponibilidad` en navegador real, usando los selectores `data-cy`. |
| API / contrato | Ejecutadas | Dentro de `DisponibilidadFeatureTest.php` y `SitioPatrimonialControllerTest.php` | `POST /api/disponibilidad/evaluar` y `POST /sitios/{sitio}/verificar`: respuestas `200`/`422` con las 12 claves y los mensajes exactos. |
| No funcionales | Artefactos creados, sin ejecución final | `tests/no-funcionales/cuscogo_rendimiento.jmx`, `tests/no-funcionales/cuscogo_carga.js`, `tests/no-funcionales/cuscogo_seguridad.yaml` | Rendimiento (JMeter), carga (k6) y seguridad (OWASP ZAP). JMeter y ZAP no están instalados; la ejecución de k6 se abortó. |
| Build / estáticas | Ejecutadas | `npm run build`, `php artisan view:clear`, `git diff --check` | Compilación Vite/Tailwind v4, compilación de vistas Blade y diff sin errores. No hay scripts `lint`/`typecheck` definidos en `package.json`. |

A nivel proyecto, Cypress tiene 4 specs y 17 escenarios en total; CUS-03 aporta los 5 listados a continuación.

### Cypress (5 escenarios, todos en modo manual sobre `/sitios/disponibilidad`)

| Escenario | Hora evaluada | Apertura | Cierre | Resultado esperado |
|---|---|---|---|---|
| CA-01 | `10:00` | `09:00` | `17:00` | Badge y mensaje `ABIERTO` |
| CA-02 | `18:00` | `09:00` | `17:00` | Badge y mensaje `CERRADO` |
| CA-03a | `09:00` | `09:00` | `17:00` | `ABIERTO` (apertura exacta) |
| CA-03b | `17:00` | `09:00` | `17:00` | `ABIERTO` (cierre exacto) |
| CA-04 | `10:00` | `18:00` | `09:00` | Badge `ERROR DE VALIDACIÓN` y mensaje `La hora de cierre debe ser posterior a la hora de apertura.` |

Resultado de ejecución: **5/5 passing**.

### PHPUnit — Unit (`DisponibilidadServiceTest`, `EstadoOperatividadSitioServiceTest`)

- Operatividad: `10:00` en `09:00–17:00` = abierto; `18:00` = cerrado; `09:00` y `17:00` exactos = abiertos; `08:59` y `17:01` = cerrados; `00:00` en `00:00–23:59` = abierto.
- Coherencia: `08:00–16:00` válido; `18:00–09:00`, `09:00–09:00`, `9:00`, `24:00`, `08:60` y apertura vacía = inválidos.
- Formato: válidos `00:00`, `09:05`, `23:59`; inválidos `24:00`, `12:60`, `7:05`, `12:00:00`.
- Excepciones: horario `18:00–09:00`, hora `-10:00`, hora `25:00`, hora vacía y apertura `09:60`.
- Sitio patrimonial (`EstadoOperatividadSitioServiceTest`): abierto dentro de horario, cerrado fuera de horario, sitio inactivo, evaluación con la hora actual, DTO abierto/cerrado/inactivo y normalización de hora al persistir.

### PHPUnit — Feature e integración (`DisponibilidadFeatureTest`, 21 pruebas; `DisponibilidadModelTest`, 2; `SitioPatrimonialControllerTest`, 22)

- Render de `/sitios/disponibilidad` con los campos `sitio_id`, `fecha`, `hora_actual`, `apertura` y `cierre`.
- Modo manual: `10:00` con `09:00–17:00` → `ABIERTO`, `origen = manual`; `18:00` → `CERRADO`; límites `09:00` y `17:00` → abiertos.
- Validaciones: `25:00`, `-10:00`, horario `18:00–09:00`, ausencia de `apertura`/`cierre`, `sitio_id = 999999` y `estado = ABIERTO`.
- Modo sitio: `"Qorikancha de prueba"`, fecha `2026-11-10` (martes), hora `12:30`, horario `09:00–17:00` → `ABIERTO`; también se cubren sitio sin horario, sitio inactivo y horario persistido incoherente (`18:00–09:00`).
- Persistencia: relación sitio–horario, normalización de hora a `HH:MM` y unicidad (`sitio_id`, `dia_semana`).
- API: `200` con las 12 claves de respuesta para `10:00`/`09:00–17:00`; `422` para `25:00`, para `18:00–09:00` y para sitio sin horario.
- Integración y sitios: resolución del servicio desde el contenedor, `sitios.verificar` con hora enviada y con hora actual, borrado lógico y `404` para sitios inexistentes o eliminados.

Resultado global actual de la suite completa: **164 pruebas PHPUnit passing (477 assertions)** y **17/17 escenarios Cypress passing** (4 specs: CUS-03, CUS-04, CUS-05 y CUS-06), más `npm run build` exitoso. CUS-03 aporta los 5 escenarios E2E de esta sección y sus pruebas unitarias, feature e integración.

## 7. Casos de prueba para Jira

_(Sección reservada para pegar los tickets finales de QA.)_
