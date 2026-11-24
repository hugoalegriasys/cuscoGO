# CUS-05 — Búsqueda de Sitios por Cercanía (Geolocalización / GPS)

## 1. Ficha de la Historia de Usuario y resumen de la funcionalidad

| Campo | Detalle |
|---|---|
| Identificador | CUS-05 |
| Título | Búsqueda de Sitios por Cercanía (Geolocalización / GPS) |
| Módulo | Descubrimiento de destinos turísticos |
| Actor principal | Turista / visitante |
| Estado | Implementado (Web + API + pruebas) |

**Narración:** Como turista que visita Cusco, quiero encontrar los sitios turísticos más cercanos a mi ubicación actual (o a un punto de referencia) dentro de un radio determinado, para organizar mi itinerario según la distancia real.

**Criterios de aceptación:**

- El sistema debe aceptar únicamente latitud entre `-90.0` y `90.0` y longitud entre `-180.0` y `180.0`, rechazando `NAN`/`INF`.
- El sistema debe calcular distancias esféricas con la fórmula de Haversine (`R = 6371.0 km`) y redondear a 2 decimales (~`72.86 km` entre Cusco y Machu Picchu).
- La búsqueda debe rechazar `radioKm <= 0` y filtrar solo sitios activos con coordenadas registradas.
- Los resultados deben ordenarse de menor a mayor distancia (`distancia_km ASC`) y exponer la distancia calculada como atributo del sitio.
- La búsqueda debe estar disponible tanto en la interfaz web como en un endpoint REST (GET y POST).
- La interfaz debe ofrecer GPS del navegador, dos presets reales y un selector rápido de radio (`1`, `5`, `10`, `50`, `100` km).
- Todas las validaciones deben mostrarse al usuario y devolverse en la API con HTTP `422`.

**Funcionalidad:**

- **Web:** `GET /sitios/cercanos` muestra el formulario con presets, GPS y selector de radio. El envío a `POST /sitios/cercanos` redirige a la misma vista con el resultado en sesión (`resultado_cercania`) o con los errores de validación.
- **API:** `GET` y `POST /api/sitios/cercanos` reciben los mismos datos y responden JSON: `200` con `status: "ok"`, `origen`, `total` y `data`, o `422` con errores de validación o de negocio.
- El cálculo se delega en `CercaniaService` a través del contrato `CercaniaServiceInterface`. El controlador solo traduce la petición HTTP y da forma a la respuesta.

## 2. Arquitectura implementada: rutas y archivos por capa

### Rutas

| Método | URL | Nombre | Controlador / acción | Middleware |
|---|---|---|---|---|
| GET | `/sitios/cercanos` | `sitios.cercanos` | `CercaniaController@index` | `web` |
| POST | `/sitios/cercanos` | `sitios.cercanos.buscar` | `CercaniaController@buscarWeb` | `web` + CSRF |
| GET | `/api/sitios/cercanos` | `api.sitios.cercanos` | `CercaniaController@buscarApi` | `api` |
| POST | `/api/sitios/cercanos` | `api.sitios.cercanos` | `CercaniaController@buscarApi` | `api` |

No hay autenticación ni autorización en estas rutas (`authorize(): true`). Las dos rutas API se registran con `Route::match(['get', 'post'], ...)` para compartir un único nombre de ruta.

### Archivos por capa

- **Rutas:** `routes/web.php` (modificado), `routes/api.php` (modificado).
- **HTTP:** `app/Http/Controllers/CercaniaController.php` (creado), `app/Http/Requests/BuscarSitiosCercanosRequest.php` (creado).
- **Dominio:** `app/Contracts/CercaniaServiceInterface.php` (creado), `app/Services/CercaniaService.php` (creado), `app/Providers/AppServiceProvider.php` (binding interfaz → implementación).
- **Persistencia:** `database/migrations/2026_11_24_000001_add_coordenadas_to_sitios_table.php` (creada), `app/Models/Sitio.php` (modificado), `database/factories/SitioFactory.php` (modificado), `database/seeders/SitioSeeder.php` (modificado).
- **Vista:** `resources/views/sitios/cercanos.blade.php` (creada).
- **Pruebas:** `tests/Unit/CercaniaServiceTest.php` (creado), `tests/Feature/CercaniaFeatureTest.php` (creado), `cypress/e2e/cercania_local.cy.js` (creado).
- **Documentación:** este archivo.

### Diagrama de dependencias

```
BuscarSitiosCercanosRequest ──► CercaniaController ──► CercaniaServiceInterface
                                       │                      │
                                       │                      ▼
                                       │           CercaniaService → Sitio (SQL Haversine)
                                       ▼
                        resources/views/sitios/cercanos.blade.php
```

`AppServiceProvider::register()` registra:

```php
$this->app->bind(CercaniaServiceInterface::class, CercaniaService::class);
```

## 3. Entradas, validaciones y mensajes de error exactos

Web y API comparten `BuscarSitiosCercanosRequest`.

| Campo | Reglas exactas | Mensaje exacto |
|---|---|---|
| `latitud` | `required`, `numeric`, `between:-90,90` | `La latitud es obligatoria.` / `La latitud debe ser un número válido.` / `La latitud debe estar entre -90 y 90 grados.` |
| `longitud` | `required`, `numeric`, `between:-180,180` | `La longitud es obligatoria.` / `La longitud debe ser un número válido.` / `La longitud debe estar entre -180 y 180 grados.` |
| `radio_km` | `nullable`, `numeric`, `gt:0` | `El radio debe ser un número válido.` / `El radio debe ser mayor a cero kilómetros.` |

- **Web:** el formulario incluye `@csrf` y el atributo `novalidate` para que las validaciones de negocio sean evaluadas por Laravel (y no bloqueadas por el navegador). Si `radio_km` se omite, el controlador aplica el valor por defecto `5.0`. Ante cualquier fallo redirige atrás con `withInput()` y los errores en la bolsa estándar.
- **API, fallo de validación (422):**
  ```json
  {
    "message": "Los datos proporcionados no son válidos.",
    "errors": {
      "latitud": ["La latitud debe estar entre -90 y 90 grados."],
      "longitud": ["La longitud debe estar entre -180 y 180 grados."]
    }
  }
  ```
- No se envía ni se acepta la distancia calculada desde el cliente: el servidor recalcula siempre con Haversine.

### Errores de negocio del servicio

Se lanzan como `\InvalidArgumentException` y se capturan en el controlador. En API devuelven `422` con `status: "error"`, `message` y `errors.cercania`; en Web redirigen atrás con `errors.cercania`.

| Situación | Mensaje exacto |
|---|---|
| Coordenadas fuera de rango o no finitas (`NAN`/`INF`) | `Las coordenadas proporcionadas no son válidas.` |
| Radio menor o igual a cero, o no finito | `El radio de búsqueda debe ser mayor a cero kilómetros.` |

Ejemplo de error de negocio API:

```json
{
  "status": "error",
  "message": "El radio de búsqueda debe ser mayor a cero kilómetros.",
  "errors": {
    "cercania": ["El radio de búsqueda debe ser mayor a cero kilómetros."]
  }
}
```

## 4. Lógica de negocio, matriz de distancias y respuestas del sistema

### Reglas en `CercaniaService`

1. `validarCoordenadas()` exige valores finitos dentro de `-90..90` y `-180..180`; los límites exactos se aceptan.
2. `calcularDistanciaKm()` valida las dos parejas antes de aplicar Haversine; el mismo punto devuelve `0.0` sin cálculo y el resto se redondea a 2 decimales.
3. `buscarSitiosCercanos()` valida coordenadas y `radioKm > 0` antes de consultar la BD.
4. La consulta Eloquent calcula `sin²(Δlat/2) + cos(latOrigen)·cos(latSitio)·sin²(Δlng/2)` en SQL, aplica `ACOS/ASIN` sobre la Tierra esférica y filtra por radio.
5. En SQLite se registran `RADIANS`, `SIN`, `COS`, `SQRT` y `ASIN` en el PDO con `sqliteCreateFunction`, para que la misma consulta funcione en MySQL y en las pruebas `:memory:`.
6. El radio se inyecta como literal numérico validado: PDO lo bindea como texto y SQLite compararía `REAL <= TEXTO` como siempre verdadero.
7. Se ignoran sitios inactivos y sin coordenadas; se ordena por `distancia_km ASC` y se redondea el atributo a 2 decimales.

### Constante interna

| Constante | Valor |
|---|---|
| `CercaniaService::RADIO_TIERRA_KM` (privada) | `6371.0` |

### Matriz de distancias desde la Plaza de Armas de Cusco

| Sitio | Coordenadas | Distancia | ¿Entra con radio 5 km? |
|---|---|---|---|
| Plaza de Armas Cusco | `-13.5167, -71.9781` | `0.00 km` | Sí |
| Qorikancha | `-13.5200, -71.9750` | `0.50 km` | Sí |
| Sacsayhuaman | `-13.5098, -71.9817` | `0.86 km` | Sí |
| Ollantaytambo | `-13.2584, -72.2633` | `42.15 km` | No |
| Machu Picchu | `-13.1631, -72.5450` | `72.86 km` | No (sí con radio 100 km) |

### Especificación del endpoint REST

`GET|POST /api/sitios/cercanos` — contenedor: `Request` JSON, formulario o query string — sin autenticación.

**Petición de ejemplo:**

```json
{
  "latitud": -13.5167,
  "longitud": -71.9781,
  "radio_km": 100
}
```

**Respuesta `200 OK`:**

```json
{
  "status": "ok",
  "origen": {
    "latitud": -13.5167,
    "longitud": -71.9781,
    "radio_km": 100.0
  },
  "total": 2,
  "data": [
    {
      "id": 1,
      "nombre": "Plaza de Armas Cusco",
      "categoria": "Urbano",
      "ubicacion": "Centro historico de Cusco",
      "latitud": -13.5167,
      "longitud": -71.9781,
      "distancia_km": 0.0
    },
    {
      "id": 6,
      "nombre": "Machu Picchu",
      "categoria": "Arqueologico",
      "ubicacion": "Distrito de Machupicchu, Urubamba",
      "latitud": -13.1631,
      "longitud": -72.5450,
      "distancia_km": 72.86
    }
  ]
}
```

**Respuesta `422 Unprocessable Entity` (validación):**

```json
{
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "radio_km": ["El radio debe ser mayor a cero kilómetros."]
  }
}
```

### Visualización en la vista Blade

La vista replica la estética editorial de CUS-03 y CUS-04: fondo piedra cálido (`bg-surface`), tarjetas blancas con borde arena (`bg-surface-container-lowest` / `border-surface-container-highest`), verde bosque andino (`#1E4D3B` vía `bg-primary` / `text-primary-container`) y acentos terracota (`#A0401D` vía `bg-secondary` / `text-secondary`), sin menús de navegación adicionales. Se usan clases canónicas de Tailwind v4 (`max-w-275`, `text-body-md`, `bg-secondary`) para evitar `suggestCanonicalClasses` y `cssConflict`.

- **Puntos de referencia:** botones *«Plaza de Armas Cusco»* y *«Ollantaytambo»* autorrellenan latitud y longitud desde JavaScript.
- **GPS:** el botón *«Usar mi ubicación GPS»* usa `navigator.geolocation.getCurrentPosition`; si no hay permiso o el navegador no lo admite, muestra un mensaje en `#gps-estado`.
- **Estado inicial:** tarjeta *"Sin búsqueda todavía"* con la instrucción de uso.
- **Estado resultado:** tarjetas `item-sitio-cercano` ordenadas, con badge `badge-distancia` en kilómetros y encabezado `N resultados en X km`.
- **Estado vacío:** tarjeta *"Sin resultados en este radio"* con sugerencia de ampliar el radio.
- **Estado error:** aviso terracota `alerta-error` con el listado de mensajes y tarjeta derecha con badge `ERROR DE VALIDACIÓN`.
- **Envío:** el botón *«Buscar sitios cercanos»* cambia a *«Buscando...»* y se deshabilita.

### Atributos `data-cy` expuestos

| Elemento | Atributo |
|---|---|
| Formulario | `form-cercania` |
| Latitud / longitud / radio | `latitud`, `longitud`, `radio-km` |
| GPS y presets | `btn-gps`, `btn-preset-cusco`, `btn-preset-ollantaytambo` |
| Envío | `btn-buscar-cercanos` |
| Resultados y tarjetas | `resultados-cercania`, `item-sitio-cercano`, `badge-distancia` |
| Estados | `estado-vacio`, `alerta-error` |

## 5. Datos de referencia y restricciones

### Tabla `sitios` (columnas añadidas)

| Columna | Tipo | Restricciones |
|---|---|---|
| `latitud` | DECIMAL(10,7) | NULL; rango válido de aplicación `-90.0` a `90.0` |
| `longitud` | DECIMAL(10,7) | NULL; rango válido de aplicación `-180.0` a `180.0` |

La migración es **aditiva**: no altera `id`, `nombre`, `categoria`, `ubicacion`, `descripcion`, `activo` ni el índice `sitios_activo_nombre_index`. El `down()` elimina solo `latitud` y `longitud`. No se añaden CHECK: en SQLite (PHPUnit) no aplican y la validación en aplicación cubre ambos motores, igual que en CUS-03.

`SitioSeeder` siembra de forma idempotente (`updateOrCreate` por nombre) estos sitios reales:

| Sitio | Latitud | Longitud |
|---|---|---|
| Plaza de Armas Cusco | `-13.5167` | `-71.9781` |
| Qorikancha | `-13.5200` | `-71.9750` |
| Sacsayhuaman | `-13.5098` | `-71.9817` |
| Ollantaytambo | `-13.2584` | `-72.2633` |
| Machu Picchu | `-13.1631` | `-72.5450` |

`Museo de Arte Religioso` conserva coordenadas `NULL` y por tanto queda fuera de los resultados: demuestra el filtro `whereNotNull`. `SitioFactory` genera por defecto coordenadas aleatorias cercanas al centro de Cusco (±0.03°) y expone el estado `enCoordenadas(lat, lng)` para escenarios deterministas.

## 6. Escenarios verificados en pruebas automatizadas

### Cypress (4 escenarios sobre `/sitios/cercanos`)

| Escenario | Acción | Resultado esperado |
|---|---|---|
| CA-01 | Preset Plaza de Armas Cusco + radio `5 km` | Tarjetas ordenadas con badges `0.00`, `0.50` y `0.86 km` |
| CA-02 | Radio `100 km` desde Cusco | Machu Picchu incluido al final con `72.86 km` |
| CA-03 | Punto `(0, 0)` con radio `0.01 km` | Estado vacío *"Sin resultados en este radio"* y cero tarjetas |
| CA-04 | Latitud `-95.0000` | `alerta-error` con *"La latitud debe estar entre -90 y 90 grados."* |

### PHPUnit — Unit (`CercaniaServiceTest`, 7 pruebas)

- Haversine Cusco → Machu Picchu dentro de 1 km de `73.0`; distancia `0` en el mismo punto.
- Excepción con latitud `-95.0000` y con `NAN`.
- `validarCoordenadas()` con `(-13.5167, -71.9781)` y `(-90, 180)` → `true`; con `(-95.0000, -71.9781)` y `(0, 185)` → `false`.
- Búsqueda con radio 5 km: solo Plaza, Qorikancha y Sacsayhuaman, ordenados `0.0`, `0.5`, `0.86`, sin Machu Picchu.
- Excepción con radio `0`.

### PHPUnit — Feature (`CercaniaFeatureTest`, 9 pruebas)

- Render de `/sitios/cercanos` con formulario, GPS, presets y `data-cy`.
- Búsqueda web: resultado en sesión (`total`, `origen`, `data` ordenado) y tarjetas visibles con badges.
- Estado vacío en la vista y rechazo web de latitud `-95.0000`.
- API: `200` con GET ordenado por distancia (Machu Picchu `72.86 km` al final con radio 100), `200` con POST, `422` por coordenadas y por radio inválidos.

Resultado global de la suite: **154 pruebas PHPUnit passing (435 assertions)** y **13/13 escenarios Cypress passing** (5 de CUS-03, 4 de CUS-04 y 4 de CUS-05), más `npm run build` exitoso sin advertencias de Tailwind CSS v4.

## 7. Casos de prueba funcionales para QA / Jira

| ID | Título | Precondición | Pasos | Resultado esperado | Evidencia |
|---|---|---|---|---|---|
| CP-CUSCOGO-013 | Buscar cerca de la Plaza de Armas | Sitios sembrados y `/sitios/cercanos` abierto | Presionar «Plaza de Armas Cusco», elegir `5 km` y buscar | Plaza (0.00), Qorikancha (0.50) y Sacsayhuaman (0.86) ordenados con badges | Captura de tarjetas ordenadas |
| CP-CUSCOGO-014 | Ampliar el radio hasta Machu Picchu | Búsqueda anterior realizada | Elegir `100 km` y buscar | Machu Picchu aparece al final con `72.86 km` | Captura del último badge |
| CP-CUSCOGO-015 | Radio sin coincidencias | Acceso a `/sitios/cercanos` | Ingresar `(0, 0)` con radio `0.01 km` y buscar | Tarjeta «Sin resultados en este radio», sin items | Captura del estado vacío |
| CP-CUSCOGO-016 | Validar coordenada imposible | Acceso a `/sitios/cercanos` | Ingresar latitud `-95.0000` y buscar | Alerta terracota con «La latitud debe estar entre -90 y 90 grados.» | Captura de la alerta de error |

**Comandos de ejecución:**

```bash
php artisan migrate --force
php artisan db:seed --class=SitioSeeder --force
npm run build
php artisan test
npx cypress run
```
