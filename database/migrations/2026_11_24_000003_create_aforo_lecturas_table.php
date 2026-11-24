<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el histórico de lecturas de aforo usado como respaldo (CUS-06).
     *
     * Cada fila es una fotografía del estado del sensor en un instante:
     * contemplamos sitio, código de sensor, ocupación, fuente y fecha de
     * registro. Guardar el histórico permite responder con la última lectura
     * válida cuando el proveedor IoT deja de responder (stale = true).
     *
     * unsignedTinyInteger limita el almacenamiento a 0..255; el rango de
     * negocio 0..100 se valida en el cliente HTTP antes de persistir.
     */
    public function up(): void
    {
        Schema::create('aforo_lecturas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sitio_id')->constrained('sitios')->cascadeOnDelete();
            $table->string('sensor_id', 50)->index();
            $table->unsignedTinyInteger('ocupacion');
            $table->string('fuente', 30)->default('iot');
            $table->timestamp('registrado_en');
            $table->timestamps();

            /*
             * Índice compuesto para el fallback: la consulta ordena por
             * registrado_en DESC, id DESC filtrando por sitio_id. Sin este
             * índice la búsqueda de la última lectura degradaría al crecer
             * el histórico de telemetría.
             */
            $table->index(['sitio_id', 'registrado_en', 'id'], 'aforo_lecturas_sitio_tiempo_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aforo_lecturas');
    }
};
