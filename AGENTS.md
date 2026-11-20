# AGENTS.md — Reglas del proyecto CuscoGO

Este archivo define cómo debe trabajar cualquier agente de IA en este repositorio. Es obligatorio.
Si lo que te piden choca con este archivo, avisa del choque y espera confirmación antes de actuar.

---

## 1. Contexto

- **Proyecto:** CuscoGO, aplicación web de guía turística de Cusco.
- **Curso:** Pruebas y Calidad de Software, Universidad Continental. Equipo: CulinBus Team.
- **Estado:** se construye la capa de lógica de negocio (Services) con pruebas. Donde no hay datos reales se trabaja con simulaciones. El sensor externo de aforo todavía no existe.
- **Stack:** PHP 8.2, Laravel 12, MySQL (XAMPP), PHPUnit. Pruebas de interfaz con Cypress o Dusk solo si se piden. Verifica versiones con `php -v` y `php artisan --version`; no las cambies.
- **Entorno:** Windows y **PowerShell 5.1**. Servidor local: `php artisan serve` en `http://127.0.0.1:8000`.

---

## 2. Alcance: solo estas 4 historias de usuario

La fuente de verdad de cada historia es su documento en `docs/historias/HU-0X.md`.
Cada documento tiene una sección **Pendientes de confirmar**. Mientras un punto esté pendiente, **pregunta; no lo decidas tú**.
Nunca inventes reglas, datos, campos ni endpoints.

| HU | Nombre | Servicio previsto |
|---|---|---|
| HU-01 | Consulta de aforo en tiempo real | `AforoService` |
| HU-02 | Registrar reseña | `ResenaService` |
| HU-03 | Estado de operatividad de sitios patrimoniales | `DisponibilidadService` |
| HU-04 | Consulta de disponibilidad y costo de transporte turístico | `TransporteService` |

### Métodos fijados por las historias (no cambies sus nombres ni su significado)
`calcularNivelAforo`, `validarCalificacion`, `validarComentario`, `calcularPromedioCalificaciones`, `estaOperativo`, `calcularCostoTransporte`.

### Reglas de negocio vigentes (salen de las tablas de pruebas del equipo)

**HU-01 · Aforo**
- La capacidad y la ocupación las informa el sensor (`SiteSensorInterface`).
- Casos confirmados con capacidad 100: ocupación 30 y 40 → Bajo; 60 y 75 → Moderado; 90 → Alto; 120 → error (`InvalidArgumentException`).
- El código actual clasifica Bajo hasta 50 %, Moderado hasta 80 % y Alto por encima. La tabla no fija los umbrales exactos (**pendiente P1**): no los cambies.
- Capacidad menor o igual a 0 → `InvalidArgumentException` → HTTP 422 (**propuesta, pendiente P2**).
- Sensor no disponible → `RuntimeException` → HTTP 503.

**HU-02 · Reseñas**
- `validarCalificacion`: `true` para 1 a 5; `false` para 0 y 6. Devuelve booleano, **no lanza excepción**.
- `validarComentario`: `true` si tiene contenido; `false` si está vacío o solo tiene espacios. Devuelve booleano.
- `calcularPromedioCalificaciones`: promedio de la lista; lista vacía → `0`.

**HU-03 · Operatividad**
- `estaOperativo(horaActual, apertura, cierre)`: `true` si `apertura <= horaActual <= cierre` (ambos extremos incluidos); `false` fuera del rango.
- Casos con horario 09:00 a 17:00: 10:00 → true; 18:00 → false; 09:00 → true; 17:00 → true.
- Formato de hora inválido (ej. 25:00) → `InvalidArgumentException`. Viene de un documento anterior del equipo (CUS-03); **pendiente P1**.

**HU-04 · Transporte**
- `BUS_COLECTIVO` y `TREN`: costo = pasajeros × precio unitario. `PRIVADO`: tarifa plana = precio unitario.
- Errores con mensaje exacto:
  - Colectivo con más pasajeros que asientos → "No hay suficientes asientos disponibles".
  - Privado con más pasajeros que asientos → "La cantidad de pasajeros excede la capacidad máxima del vehículo".
  - Pasajeros igual a 0 → "La cantidad de pasajeros debe ser mayor a cero".
  - Tipo desconocido → "Tipo de transporte no reconocido".
- `TREN` con la misma validación de asientos que `BUS_COLECTIVO` es una inferencia (**pendiente P2**).

### Endpoints previstos (propuesta; confirmar en el documento de cada HU antes de cambiarlos)
- `GET /api/sitios/{sitioId}/aforo` → 200 `{ sitio_id, nivel_aforo }`, 422 o 503 con `{ mensaje }`.
- `POST /api/sitios/{id}/resenas` y `GET /api/sitios/{id}/resenas` (lista y promedio).
- `GET /api/sitios/{id}/operatividad` (por definir).
- `POST /api/transporte/costo` (por definir).

### Fuera de alcance (no lo implementes aunque parezca útil)
Autenticación y roles, reporte de incidencias, audio guías, mapa interactivo, galería de fotos, pagos, app móvil, panel de administración, notificaciones, traducciones, IA.
La API actual no tiene autenticación; es un riesgo conocido y documentado, no lo "arregles" por tu cuenta.

---

## 3. Arquitectura (obligatoria)

```
Petición HTTP → routes/api.php → Controller (delgado)
             → Service (reglas de negocio)
             → Contract (dependencia externa)  |  Model (base de datos)
             → respuesta JSON
```

| Carpeta | Contenido |
|---|---|
| `app/Contracts/` | Interfaces de dependencias externas (ej. `SiteSensorInterface`) |
| `app/Services/` | Lógica de negocio y adaptadores que implementan los contratos (ej. `HttpSiteSensor`) |
| `app/Http/Controllers/Api/` | Controllers de la API |
| `app/Models/` | Modelos Eloquent |
| `database/migrations/` | Migraciones |
| `routes/api.php` | Rutas de la API |
| `tests/Unit/` | Pruebas unitarias de Services |
| `tests/Feature/` | Pruebas de integración de endpoints |
| `docs/historias/` | Documentos de cada historia de usuario |

### Reglas por capa
- **Controller:** valida con `$request->validate()`, llama al Service, convierte excepciones en códigos HTTP y devuelve JSON. **Sin lógica de negocio** (nada de umbrales, cálculos ni reglas).
- **Service:** lógica pura. No usa `Request` ni `Response`. Recibe sus dependencias por constructor. Lanza `InvalidArgumentException` para datos inválidos, salvo donde la HU define otra cosa (por ejemplo, `validarCalificacion` devuelve `false` y el promedio de una lista vacía devuelve `0`).
- **Dependencias externas:** siempre detrás de una interfaz en `app/Contracts`, con su implementación en `app/Services` y el enlace (`bind`) en `AppServiceProvider`.
- **Model:** solo Eloquent (`$fillable`, `$table` si el nombre no sigue la convención). Sin lógica de negocio.

### Convenciones
- Códigos HTTP: 200 lectura, 201 creación, 422 datos inválidos o regla de negocio, 503 dependencia externa caída. No uses 500 para errores previsibles.
- JSON en `snake_case`; los errores van en la clave `mensaje`.
- Nombres de dominio y mensajes al usuario en **español** (`calcularNivelAforo`, `validarCalificacion`). Lo técnico propio de Laravel va en inglés.
- Tipos declarados en parámetros y retornos.
- Archivos guardados en **UTF-8**.
- **Patrón de referencia (HU-01):** `AforoService`, `SiteSensorInterface`, `HttpSiteSensor`, `SitioAforoController` y `ConsultarAforoIntegracionTest`. Si existen en el repositorio, copia su estilo. Si no existen, pregunta antes de crearlos.

---

## 4. Forma de trabajar: pruebas primero

Para cada tarea, en este orden:
1. Lee `docs/historias/HU-0X.md` y su tabla de casos de prueba.
2. Muestra un plan breve: archivos que tocarás y pruebas que escribirás.
3. Escribe las pruebas unitarias del Service. Deben fallar al principio.
4. Implementa el Service con lo mínimo para que pasen.
5. Escribe la prueba de integración del endpoint.
6. Implementa controller y ruta.
7. Ejecuta `php artisan test` completo.
8. Informa el resultado real.

### Convenciones de pruebas
- Unitarias: `tests/Unit/NombreServiceTest.php`. Integración: `tests/Feature/NombreIntegracionTest.php`.
- Métodos `test_` en `snake_case`, con comentarios `// Given`, `// When`, `// Then` en español.
- Cubre todos los casos de la tabla de la HU, incluidos los valores límite y las excepciones.
- Dependencias externas: siempre `Http::fake()` o dobles. **Nunca llames a un servicio real.**
- `RefreshDatabase` solo en pruebas con base de datos.
- Para mensajes de error, prefiere comprobar el código HTTP y la estructura (`assertJsonStructure(['mensaje'])`). Verifica el texto exacto solo si la HU lo exige.

### Definición de Hecho (una HU está lista cuando)
- [ ] Service, controller y ruta implementados (y vista, si la HU la tiene).
- [ ] Pruebas unitarias del Service con todos los casos de la HU.
- [ ] Prueba de integración del endpoint.
- [ ] `php artisan test` en verde, **ejecutado de verdad**.
- [ ] Ninguna prueba anterior se rompió.
- [ ] Ningún archivo fuera del alcance fue modificado.
- [ ] La persona revisó el cambio y lo integra mediante Pull Request.

---

## 5. Restricciones: lo que NUNCA debes hacer

**Alcance**
- No implementes nada fuera de las 4 HU ni "mejoras" que no te pidieron (refactors masivos, renombrar, reorganizar carpetas, reformatear archivos existentes).
- No cambies reglas de negocio (umbrales, rangos, tipos, prioridades). Si crees que una regla está mal, avísalo; no la cambies.
- No inventes endpoints, campos ni columnas.

**Dependencias**
- No ejecutes `composer require`, `composer update`, `composer remove` ni `npm install` sin permiso explícito.
- No cambies versiones de PHP ni de Laravel.

**Archivos protegidos (pregunta antes de tocarlos)**
`.env` (nunca lo leas ni lo muestres), `phpunit.xml`, `composer.json`, `composer.lock`, `package.json`, `bootstrap/app.php`, `config/*`, migraciones ya existentes, `docs/historias/*`, `AGENTS.md`, `opencode.json`.

**Base de datos**
- No ejecutes `migrate:fresh`, `migrate:reset` ni `db:wipe`, ni borres datos, sin confirmación.
- No edites una migración ya aplicada: crea una nueva.
- Las pruebas con `RefreshDatabase` o `DatabaseMigrations` borran tablas. Confirma contra qué base apuntan antes de ejecutarlas.

**Pruebas**
- No borres, comentes ni saltes pruebas (`markTestSkipped`), ni las debilites para que pasen.
- No cambies un resultado esperado para adaptarlo al código, salvo que la regla de negocio documentada haya cambiado.
- No afirmes que algo funciona sin haberlo ejecutado.

**Git**
- Nunca ejecutes `push`, `merge`, `rebase`, `reset`, `clean` ni `branch -D`.
- Nunca trabajes directamente sobre `main` ni `develop`.
- Haz commit solo si la persona te lo pide.

**Seguridad**
- No leas ni imprimas `.env` ni credenciales. No escribas claves, tokens ni datos privados en el código.
- No desactives validaciones, protecciones ni middleware para "hacer pasar" algo.
- En pruebas usa solo datos ficticios.

**Comandos**
- Nada destructivo del sistema (`rm -rf`, `Remove-Item -Recurse`, `del /s`, formatear).
- No instales programas globales.
- No lances procesos que no terminan (como `php artisan serve`); los levanta la persona.

---

## 6. Detente y pregunta cuando

- Falta una regla de negocio o está ambigua.
- La tarea toca un punto marcado como pendiente en el documento de su HU.
- Necesitas una dependencia nueva o tocar un archivo protegido.
- Una prueba falla dos veces por causas que no entiendes.
- Lo que piden choca con este archivo.
- El cambio afectaría a más de una HU.

---

## 7. Git y trabajo en equipo

- Una rama por tarea, creada desde `develop`:
  - `feature/CUS-NN-descripcion-corta` para historias nuevas.
  - `fix/SCRUM-NN-descripcion-corta` para correcciones posteriores.
- Mensajes de commit: `tipo(CLAVE): descripción corta en presente`. Tipos: `feat`, `fix`, `test`, `docs`, `refactor`, `chore`.
- Un cambio = una HU. No mezcles historias en la misma rama.
- La persona abre el Pull Request y el Scrum Master lo revisa y aprueba.

---

## 8. Entorno y comandos

| Acción | Comando |
|---|---|
| Todas las pruebas | `php artisan test` |
| Una prueba | `php artisan test --filter=NombreDeLaPrueba` |
| Ver rutas | `php artisan route:list --path=api` |
| Crear clases | `php artisan make:...` |
| Migrar | `php artisan migrate` (pide confirmación) |

- Usa sintaxis de **PowerShell 5.1**. No uses `&&` para encadenar comandos; usa `;` o ejecútalos por separado.
- Rutas de Windows con `\`.

---

## 9. Cómo responder

- En **español**, breve y directo.
- Antes de cambiar algo: plan de 3 a 5 líneas con los archivos que tocarás.
- Después: lista de archivos modificados, comando ejecutado y **resultado real** (resumen de la salida).
- Si no pudiste ejecutar algo, dilo. No supongas resultados.

## 10. Si algo falla

- Lee el error completo antes de actuar.
- No lo "arregles" desactivando pruebas, validaciones ni configuración.
- Máximo dos intentos distintos. Si no se resuelve, detente y reporta qué probaste y qué viste.
