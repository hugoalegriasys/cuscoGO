# CUS-04 — Cálculo del Costo del Transporte Turístico

## 1. Ficha de la Historia de Usuario y resumen de la funcionalidad

| Campo | Detalle |
|---|---|
| Identificador | CUS-04 |
| Título | Cálculo del Costo del Transporte Turístico |
| Módulo | Cotizaciones de servicios turísticos |
| Actor principal | Turista / visitante |
| Estado | Implementado (Web + API + pruebas) |

**Narración:** Como turista que visita Cusco, quiero calcular el costo de mi transporte según la modalidad (`BUS_COLECTIVO`, `TREN` o `PRIVADO`) y la cantidad de pasajeros, para conocer el monto exacto a pagar antes de confirmar mi viaje.

**Criterios de aceptación:**

- El sistema debe multiplicar `precioUnitario * pasajeros` para `BUS_COLECTIVO` y `TREN`.
- El sistema debe cobrar una tarifa fija por vehículo (`precioUnitario`) para `PRIVADO`, sin importar la cantidad de pasajeros.
- debe rechazar `pasajeros <= 0` con el mensaje exacto `La cantidad de pasajeros debe ser mayor a cero`.
- debe rechazar tipos no reconocidos con el mensaje exacto `Tipo de transporte no reconocido`.
- debe rechazar `precioUnitario < 0` con el mensaje exacto `El precio unitario no puede ser negativo`.
- La cotización debe estar disponible tanto en la interfaz web como en un endpoint REST.
- Todas las validaciones deben mostrarse al usuario y devolverse en la API con HTTP `422`.

**Funcionalidad:**

- **Web:** `GET /transporte/calcular` muestra el formulario con un selector rápido de rutas turísticas reales de Cusco. El envío a `POST /transporte/calcular` redirige a la misma vista con el resultado en sesión (`resultado_transporte`) o con los errores de validación.
- **API:** `POST /api/transporte/calcular` recibe los mismos datos y responde JSON: `200` con `status: "ok"` y el desglose, o `422` con errores de validación o de negocio.
- El cálculo se delega en `TransporteService` a través del contrato `TransporteServiceInterface`. El controlador solo traduce la petición HTTP y da forma a la respuesta.

## 2. Arquitectura implementada: rutas y archivos por capa

### Rutas

| Método | URL | Nombre | Controlador / acción | Middleware |
|---|---|---|---|---|
| GET | `/transporte/calcular` | `transporte.calcular` | `TransporteController@index` | `web` |
| POST | `/transporte/calcular` | `transporte.calcular.evaluar` | `TransporteController@calcularWeb` | `web` + CSRF |
| POST | `/api/transporte/calcular` | `api.transporte.calcular` | `TransporteController@calcularApi` | `api` |

No hay autenticación ni autorización en estas rutas (`authorize(): true`).

### Archivos por capa

- **Rutas:** `routes/web.php` (modificado), `routes/api.php` (modificado).
- **HTTP:** `app/Http/Controllers/TransporteController.php` (creado), `app/Http/Requests/CalcularCostoTransporteRequest.php` (creado).
- **Dominio:** `app/Contracts/TransporteServiceInterface.php` (creado), `app/Services/TransporteService.php` (modificado de forma aditiva), `app/Providers/AppServiceProvider.php` (binding interfaz → implementación).
- **Vista:** `resources/views/transporte/calcular.blade.php` (creada).
- **Pruebas:** `tests/Unit/TransporteServiceTest.php` (ampliado), `tests/Feature/TransporteFeatureTest.php` (creado), `cypress/e2e/transporte_local.cy.js` (creado).
- **Documentación:** este archivo.

### Diagrama de dependencias

```
CalcularCostoTransporteRequest ──► TransporteController ──► TransporteServiceInterface
                                          │                         │
                                          │                         ▼
                                          │              TransporteService
                                          │              (sin constructor,
                                          │               new TransporteService())
                                          ▼
                              resources/views/transporte/calcular.blade.php
```

`AppServiceProvider::register()` registra:

```php
$this->app->bind(TransporteServiceInterface::class, TransporteService::class);
```

La extensión del servicio fue **estrictamente aditiva**: se conservaron el nombre y orden de los parámetros `calcularCostoTransporte(string $tipoServicio, int $pasajeros, float $precioUnitario): float`, el orden de validación (pasajeros → tipo → precio), los mensajes originales de `\InvalidArgumentException` y la posibilidad de instanciarlo con `new TransporteService()` sin argumentos.

## 3. Entradas, validaciones y mensajes de error exactos

Web y API comparten `CalcularCostoTransporteRequest`.

| Campo | Reglas exactas | Mensaje exacto |
|---|---|---|
| `tipo_servicio` | `required`, `string`, `in:BUS_COLECTIVO,TREN,PRIVADO` | `Tipo de transporte no reconocido` (regla `in`) |
| `pasajeros` | `required`, `integer`, `min:1` | `La cantidad de pasajeros debe ser mayor a cero` (regla `min`) |
| `precio_unitario` | `required`, `numeric`, `min:0` | `El precio unitario debe ser un monto válido mayor o igual a cero` (regla `min`) |

- **Web:** el formulario incluye `@csrf` y el atributo `novalidate` para que las validaciones de negocio sean evaluadas por Laravel (y no bloqueadas por el navegador). Ante cualquier fallo redirige atrás con `withInput()` y los errores en la bolsa estándar.
- **API, fallo de validación (422):**
  ```json
  {
    "message": "Los datos proporcionados no son válidos.",
    "errors": {
      "tipo_servicio": ["Tipo de transporte no reconocido"],
      "pasajeros": ["La cantidad de pasajeros debe ser mayor a cero"],
      "precio_unitario": ["El precio unitario debe ser un monto válido mayor o igual a cero"]
    }
  }
  ```
- No se envía ni se acepta el resultado calculado desde el cliente: el monto siempre se calcula en el servidor.

### Errores de negocio del servicio

Se lanzan como `\InvalidArgumentException` y se capturan en el controlador. En API devuelven `422` con `status: "error"`, `message` y `errors.transporte`; en Web redirigen atrás con `errors.transporte`.

| Situación | Mensaje exacto |
|---|---|
| `pasajeros <= 0` | `La cantidad de pasajeros debe ser mayor a cero` |
| Tipo no incluido en la lista de reconocidos | `Tipo de transporte no reconocido` |
| `precioUnitario < 0` | `El precio unitario no puede ser negativo` |

Ejemplo de error de negocio API:

```json
{
  "status": "error",
  "message": "El precio unitario no puede ser negativo",
  "errors": {
    "transporte": ["El precio unitario no puede ser negativo"]
  }
}
```

## 4. Lógica de negocio, matriz de modalidades y respuestas del sistema

### Reglas en `TransporteService`

1. Se normaliza el tipo con `trim()` (por eso `' TREN '` calcula correctamente).
2. Se valida `pasajeros <= 0` antes que cualquier otra regla.
3. Se valida que el tipo limpio esté en `$tiposServicioValidos` (`BUS_COLECTIVO`, `TREN`, `PRIVADO`).
4. Se valida `precioUnitario < 0`.
5. Si el tipo es `PRIVADO`, se devuelve el precio unitario tal cual (tarifa fija por vehículo).
6. En caso contrario se devuelve `$pasajeros * $precioUnitario`.

No se aplica `strtoupper()`: las comparaciones son sensibles a mayúsculas después del `trim()`.

### Constantes públicas

| Constante | Valor |
|---|---|
| `TransporteService::BUS_COLECTIVO` | `'BUS_COLECTIVO'` |
| `TransporteService::TREN` | `'TREN'` |
| `TransporteService::PRIVADO` | `'PRIVADO'` |

### Matriz de modalidades

| Modalidad | Cálculo | Ejemplo | Resultado | Modalidad de cobro en la respuesta |
|---|---|---|---|---|
| `BUS_COLECTIVO` | `precioUnitario * pasajeros` | 2 × S/ 35.00 | S/ 70.00 | `COBRO POR PASAJERO` |
| `TREN` | `precioUnitario * pasajeros` | 1 × S/ 220.00 | S/ 220.00 | `COBRO POR PASAJERO` |
| `PRIVADO` | `precioUnitario` (fijo) | 3 pasajeros, S/ 150.00 | S/ 150.00 | `TARIFA FIJA POR VEHÍCULO` |

### Estructura del resultado en sesión (`resultado_transporte`)

| Clave | Tipo | Descripción |
|---|---|---|
| `tipo_servicio` | string | Código de modalidad enviado |
| `tipo_etiqueta` | string | `Bus Colectivo`, `Tren Turístico` o `Transporte Privado` |
| `pasajeros` | int | Cantidad de pasajeros |
| `precio_unitario` | float | Tarifa unitaria o base |
| `costo_total` | float | Monto calculado por el servicio |
| `modalidad_cobro` | string | `COBRO POR PASAJERO` o `TARIFA FIJA POR VEHÍCULO` |
| `mensaje` | string | `El costo total del servicio %s es de S/ %s.` |

### Especificación del endpoint REST

`POST /api/transporte/calcular` — contenedor: `Request` JSON o formulario — sin autenticación.

**Petición de ejemplo:**

```json
{
  "tipo_servicio": "BUS_COLECTIVO",
  "pasajeros": 2,
  "precio_unitario": 35.00
}
```

**Respuesta `200 OK`:**

```json
{
  "status": "ok",
  "data": {
    "tipo_servicio": "BUS_COLECTIVO",
    "pasajeros": 2,
    "precio_unitario": 35.0,
    "costo_total": 70.0,
    "modalidad_cobro": "COBRO POR PASAJERO"
  }
}
```

**Respuesta `422 Unprocessable Entity` (validación):**

```json
{
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "tipo_servicio": ["Tipo de transporte no reconocido"]
  }
}
```

### Visualización en la vista Blade

La vista replica la estética editorial de CUS-03: fondo piedra cálido (`bg-surface`), tarjetas blancas con borde arena (`bg-surface-container-lowest` / `border-surface-container-highest`), verde bosque andino (`#1E4D3B` vía `bg-primary` / `text-primary-container`) y acentos terracota (`#A0401D`), sin menús de navegación adicionales.

- **Selector rápido:** al elegir una ruta se autorrellenan `tipo_servicio` y `precio_unitario` desde los atributos `data-tipo` y `data-precio` de la opción.
- **Estado inicial:** tarjeta *"Sin cálculo todavía"* con la instrucción de uso.
- **Estado resultado:** monto grande `S/ 70.00`, badge de modalidad y desglose 2×2 (Tipo de servicio, Cantidad de pasajeros, Tarifa unitaria/base, Total a pagar).
- **Estado error:** aviso superior *"Revisa los datos ingresados:"*, tarjeta terracota suave con badge `ERROR DE VALIDACIÓN` y el listado de errores.
- **Envío:** el botón *"Calcular costo total"* cambia a *"Calculando..."* y se deshabilita.

## 5. Datos de referencia y restricciones

CUS-04 no introduce tablas, migraciones ni modelos: los precios se reciben en la petición y las rutas de referencia se exponen desde el servicio.

`TransporteService::obtenerServiciosReferencia()` devuelve:

| Ruta | Modalidad | Tarifa de referencia |
|---|---|---|
| Bus Colectivo Cusco – Ollantaytambo | `BUS_COLECTIVO` | S/ 35.00 |
| Tren Turístico Ollantaytambo – Machu Picchu | `TREN` | S/ 220.00 |
| Transporte Privado Valle Sagrado | `PRIVADO` | S/ 150.00 |

Estas tarifas son solo valores precargados en el formulario; el servidor recalcula siempre el costo con el `precio_unitario` efectivamente enviado. Una futura administración de tarifas en base de datos (CUS-08) debe conservar la firma del servicio y los mensajes de excepción aquí documentados.

## 6. Escenarios verificados en pruebas automatizadas

### Cypress (4 escenarios sobre `/transporte/calcular`)

| Escenario | Acción | Resultado esperado |
|---|---|---|
| CA-01 | `BUS_COLECTIVO`, 2 pasajeros, S/ 35.00 | Badge `COBRO POR PASAJERO` y `S/ 70.00` |
| CA-02 | `PRIVADO`, 3 pasajeros, S/ 150.00 | Badge `TARIFA FIJA POR VEHÍCULO` y `S/ 150.00` |
| CA-03 | Seleccionar ruta `tren` en el selector rápido | Autorrelleno `TREN` / `220.00`; 2 pasajeros → `S/ 440.00` |
| CA-04 | `BUS_COLECTIVO`, 0 pasajeros | Badge `ERROR DE VALIDACIÓN` y mensaje de pasajeros |

### PHPUnit — Unit (`TransporteServiceTest`, 9 pruebas)

Conserva los 6 casos originales (bus, tren, privado, pasajeros 0, pasajeros negativos, tipo no reconocido) y agrega: precio decimal `35.50 × 3 = 106.50`, precio negativo con su excepción exacta y tipo con espacios `' TREN '` → `220.0`.

### PHPUnit — Feature (`TransporteFeatureTest`, 11 pruebas)

- Render de `/transporte/calcular` con las opciones de modalidad y las rutas de referencia.
- Cálculo web para `BUS_COLECTIVO` (70.00), `TREN` (220.00 con desglose en vista) y `PRIVADO` (150.00, tarifa fija).
- Validaciones web: pasajeros `0`, pasajeros `-2`, `HELICOPTERO` y precio `-10`.
- API: `200` con `status: "ok"` y las 5 claves de `data`; `422` ante datos inválidos; `200` con tarifa fija para `PRIVADO`.

Resultado global de la suite: **138 pruebas PHPUnit passing (341 assertions)** y **9/9 escenarios Cypress passing** (5 de CUS-03 + 4 de CUS-04), más `npm run build` exitoso.

## 7. Casos de prueba funcionales para QA / Jira

| ID | Título | Precondición | Pasos | Resultado esperado | Evidencia |
|---|---|---|---|---|---|
| CP-CUSCOGO-009 | Cotizar bus colectivo | Acceso a `/transporte/calcular` | Elegir `BUS_COLECTIVO`, ingresar 2 pasajeros y S/ 35.00, presionar "Calcular costo total" | Tarjeta con `COBRO POR PASAJERO` y total `S/ 70.00`; desglose 2×2 completo | Captura de tarjeta de resultado |
| CP-CUSCOGO-010 | Cotizar tren con selector rápido | Acceso a `/transporte/calcular` | Elegir ruta "Tren Turístico Ollantaytambo – Machu Picchu", verificar autorrelleno, ingresar 2 pasajeros y calcular | `tipo_servicio = TREN`, `precio_unitario = 220.00`, badge `COBRO POR PASAJERO`, total `S/ 440.00` | Captura del autorrelleno y del resultado |
| CP-CUSCOGO-011 | Cotizar transporte privado | Acceso a `/transporte/calcular` | Elegir `PRIVADO`, ingresar 3 pasajeros y S/ 150.00, calcular | Badge `TARIFA FIJA POR VEHÍCULO`, total `S/ 150.00` (no se multiplica por pasajeros) | Captura de tarjeta de resultado |
| CP-CUSCOGO-012 | Validar pasajeros en cero | Acceso a `/transporte/calcular` | Ingresar 0 en pasajeros y calcular | Aviso "Revisa los datos ingresados:", badge `ERROR DE VALIDACIÓN`, mensaje `La cantidad de pasajeros debe ser mayor a cero` | Captura de tarjeta de error |

**Comandos de ejecución:**

```bash
npm run build
php artisan test
npx cypress run
```
