<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de sitios patrimoniales con su horario de atención.
     *
     * Restricciones nativas de MySQL 8:
     * - UNIQUE sobre "nombre" para impedir duplicados.
     * - CHECK "hora_apertura < hora_cierre" para garantizar horarios coherentes.
     * - CHECK "activo IN (0, 1)" para garantizar un dominio binario.
     * Nota: los CHECK se registran solo en MySQL (SQLite, usado en pruebas,
     * no soporta ALTER TABLE ... ADD CONSTRAINT; la coherencia se garantiza
     * igualmente en la capa de validación y servicio).
     */
    public function up(): void
    {
        Schema::create('sitios_patrimoniales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('direccion', 255);
            $table->text('descripcion')->nullable();
            $table->time('hora_apertura');
            $table->time('hora_cierre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('nombre', 'uq_sitios_patrimoniales_nombre');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE `sitios_patrimoniales` '.
                'ADD CONSTRAINT `chk_sitios_patrimoniales_horario` '.
                'CHECK (`hora_apertura` < `hora_cierre`)'
            );
            DB::statement(
                'ALTER TABLE `sitios_patrimoniales` '.
                'ADD CONSTRAINT `chk_sitios_patrimoniales_activo` '.
                'CHECK (`activo` IN (0, 1))'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sitios_patrimoniales');
    }
};
