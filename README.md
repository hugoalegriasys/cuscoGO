# CuscoGo - API & Plataforma Turística de Cusco

<p align="left">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel Version">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php" alt="PHP Version">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql" alt="MySQL Version">
  <img src="https://img.shields.io/badge/Tests-Passing-brightgreen?style=for-the-badge" alt="Build Status">
</p>

**CuscoGo** es un sistema backend y API REST diseñado para optimizar la experiencia turística en la región de Cusco. Permite a los visitantes consultar en tiempo real el aforo de los principales atractivos turísticos, verificar la operatividad y horarios de los sitios patrimoniales, cotizar servicios de transporte oficial y registrar reseñas verificadas.

---

## 🚀 Módulos y Historias de Usuario (CUS-01 a CUS-10)

- **CUS-01 — Consulta de Aforo en Tiempo Real:** Cálculo dinámico de niveles de ocupación (`Bajo`, `Moderado`, `Alto`) con validación defensiva de capacidad máxima.
- **CUS-02 — Registrar Reseña:** Validación de calificaciones (1 a 5 estrellas), validación de comentarios y cálculo de promedios ponderados.
- **CUS-03 — Evaluación de Estado de Operatividad:** Verificación en tiempo real del estado de apertura (`abierto/cerrado`) comparando la hora actual con el horario de atención.
- **CUS-04 — Cálculo del Costo del Transporte Turístico:** Cotización por modalidad (`BUS_COLECTIVO`, `TREN`, `PRIVADO`) multiplicando por cantidad de pasajeros o tarifa plana por vehículo.
- **CUS-05 — Búsqueda de Sitios por Cercanía (GPS):** Listado y ordenamiento de atractivos turísticos según proximidad geográfica utilizando la fórmula de Haversine.
- **CUS-06 — Telemetría IoT y Resiliencia de Aforo:** Cliente HTTP con timeout (3s), reintentos, caché de 15 segundos, persistencia en `aforo_lecturas` y fallback a última lectura conocida.
- **CUS-07 — Excepciones de Horario y Turnos Nocturnos:** Soporte para rangos horarios que cruzan la medianoche (ej. 20:00 a 02:00), cierres por feriados y maintenance días.
- **CUS-08 — Gestión Administrativa y Persistencia de Tarifas:** Catálogo centralizado en BD con precios almacenados en centavos y validación HTTP `prohibited` para evitar manipulación de precios.
- **CUS-09 — Unicidad y Moderación de Reseñas:** Constraint único `(user_id, sitio_id)` en MySQL, políticas de autorización (`ResenaPolicy`) y Soft Deletes.
- **CUS-10 — Autenticación, Tokens de Acceso y Control de Roles:** Autenticación Bearer con Laravel Sanctum, roles (`turista`, `admin`) y manejo uniforme de respuestas JSON (401/403).

---

## 🛠️ Requisitos del Sistema

* **PHP:** `>= 8.2` (extensiones `pdo`, `mbstring`, `bcmath`, `curl`)
* **Base de Datos:** MySQL `8.0+`
* **Composer:** `2.x`

---

## ⚙️ Instalación y Configuración

```bash
# 1. Clonar el repositorio e instalar dependencias
git clone [https://github.com/tu-usuario/cuscogo.git](https://github.com/tu-usuario/cuscogo.git)
cd cuscogo
composer install

# 2. Configurar variables de entorno
cp .env.example .env
php artisan key:generate

# 3. Migraciones y Seeders (Sitios de Cusco y Tarifas Base)
php artisan migrate --seed

# 4. Configurar Laravel Sanctum
php artisan install:api

# 5. Iniciar el servidor
php artisan serve