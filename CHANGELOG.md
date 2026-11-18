# Changelog

Registro de cambios notables del proyecto **cuscoGO**.
Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).

## [CUS-03] - 2026-11-10 - Evaluación de Estado de Operatividad

### 📢 Reporte de Cambios y Documentación de Implementación

#### 1. Archivos Creados / Modificados:
- `database/migrations/2026_11_10_000001_create_sitios_patrimoniales_table.php` -> Creación - Tabla `sitios_patrimoniales` con columnas TIME, UNIQUE, CHECK (MySQL 8) y soft deletes.
- `app/Models/SitioPatrimonial.php` -> Creación - Modelo con fillable, casts explícitos, mutators validadores de hora (HH:MM) y normalización al leer desde BD.
- `database/factories/SitioPatrimonialFactory.php` -> Creación - Factory con estados `inactivo()` y `horarioExtendido()`.
- `database/seeders/DatabaseSeeder.php` -> Modificación - Siembra idempotente de 4 sitios patrimoniales reales del Cusco.
- `app/DTOs/EstadoOperatividadDTO.php` -> Creación - DTO `final readonly` inmutable con `toArray()` para respuestas JSON.
- `app/Contracts/DisponibilidadServiceInterface.php` -> Modificación - Se añadieron `verificarSitioAbierto()` y `evaluarEstadoOperatividad()`.
- `app/Services/DisponibilidadService.php` -> Modificación - Métodos orientados al sitio patrimonial + `declare(strict_types=1)`, delegando en la lógica pura existente.
- `app/Http/Requests/SitioPatrimonialRequest.php` -> Creación - Validación CRUD con `date_format:H:i` + `after:hora_apertura`, regla unique con ignore en update y mensajes en español.
- `app/Http/Requests/VerificarOperatividadRequest.php` -> Creación - Hora de consulta opcional; `failedValidation` sobrescrito con envolvente JSON estandarizada (422).
- `app/Http/Controllers/SitioPatrimonialController.php` -> Creación - CRUD RESTful con inyección del contrato + endpoint AJAX `verificar` con captura tipada de `InvalidArgumentException`.
- `routes/web.php` -> Modificación - `Route::model('sitio', ...)` + resource RESTful + POST `sitios/{sitio}/verificar`.
- `resources/views/layouts/app.blade.php` -> Creación - Layout responsive con alertas flash auto-ocultables (Tailwind/Alpine por CDN).
- `resources/views/sitios/index.blade.php` -> Creación - Tarjetas con insignia de estado evaluada en servidor y estado vacío.
- `resources/views/sitios/create.blade.php` -> Creación - Vista de registro de sitio.
- `resources/views/sitios/edit.blade.php` -> Creación - Vista de edición de sitio.
- `resources/views/sitios/partials/form.blade.php` -> Creación - Formulario compartido con validación visual (`@error`), inputs `type="time"` y estado de carga.
- `resources/views/sitios/partials/verificar.blade.php` -> Creación - Widget Alpine.js (fetch AJAX, spinner, insignias por estado, manejo de errores).
- `resources/views/sitios/show.blade.php` -> Creación - Detalle del sitio con evaluación de operatividad.
- `resources/views/welcome.blade.php` -> Modificación - Enlace de navegación hacia el listado de sitios patrimoniales.
- `tests/Unit/EstadoOperatividadSitioServiceTest.php` -> Creación - 14 pruebas unitarias de los métodos de sitio (modelos en memoria, sin BD).
- `tests/Feature/SitioPatrimonialControllerTest.php` -> Creación - 22 pruebas de integración del flujo HTTP/BD/vista.
- `tests/Unit/DisponibilidadServiceTest.php` -> Existente - Cubre el 100% de la Matriz de Pruebas Unitarias de CUS-03 (9 casos, sin cambios).

#### 2. Resumen de Decisiones Técnicas y Arquitectura:
- La lógica pura de CUS-03 ya existía en `DisponibilidadService` (métodos `estaOperativo`/`esHorarioValido`); se extendió sin romper la API, manteniendo el patrón Contrato -> Implementación con binding IoC existente en `AppServiceProvider`.
- Estados posibles del sitio: `abierto` / `cerrado` / `inactivo`; un sitio desactivado administrativamente jamás se reporta como abierto (regla de negocio).
- Triple defensa del horario de atención: mutator del modelo, FormRequest (`after:hora_apertura`) y CHECK de BD; ante datos corruptos el servicio lanza `InvalidArgumentException` y el controlador responde 422 con envolvente `{exitoso, mensaje, errores}`.
- El endpoint AJAX acepta `hora_consulta` opcional; vacío evalúa con la hora actual del sistema (`Carbon::now()`).
- UI usa Tailwind/Alpine por CDN dado que el proyecto no tiene assets compilados (`npm run build` no ejecutado).
- `Route::model('sitio', ...)` resuelve el binding del parámetro de ruta al modelo `SitioPatrimonial`.

#### 3. Cambios en Base de Datos:
- *Tabla:* `sitios_patrimoniales` (id, nombre VARCHAR(150), direccion VARCHAR(255), descripcion TEXT NULL, hora_apertura TIME, hora_cierre TIME, activo TINYINT(1) DEFAULT 1, timestamps, deleted_at).
- *Constraints / Índices:* `uq_sitios_patrimoniales_nombre` (UNIQUE en nombre); `chk_sitios_patrimoniales_horario` (hora_apertura < hora_cierre) y `chk_sitios_patrimoniales_activo` (activo IN (0,1)) aplicados en MySQL 8 (SQLite de pruebas no soporta `ADD CONSTRAINT`; la coherencia queda garantizada por validación y servicio).

#### 4. Comandos de Verificación para el Desarrollador:
```bash
# Aplicar migración y datos de ejemplo
php artisan migrate
php artisan db:seed

# Suites de pruebas del caso de uso
php artisan test --filter=EstadoOperatividadSitioServiceTest
php artisan test --filter=SitioPatrimonialControllerTest
php artisan test --filter=DisponibilidadServiceTest   # Matriz de pruebas original

# Verificación manual en el navegador
php artisan serve   # http://127.0.0.1:8000/sitios
```

**Resultado de la suite completa:** 92 pruebas exitosas / 183 aserciones (Unit + Feature).
