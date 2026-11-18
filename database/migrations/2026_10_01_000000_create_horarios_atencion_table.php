<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_atencion', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sitio_id')
                ->constrained('sitios')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana')
                ->comment('1 = Lunes, 7 = Domingo (ISO 8601)');
            $table->time('apertura');
            $table->time('cierre');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['sitio_id', 'dia_semana'], 'horarios_atencion_sitio_dia_unique');
            $table->index(['sitio_id', 'activo'], 'horarios_atencion_sitio_activo_index');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE horarios_atencion ADD CONSTRAINT chk_horarios_dia_semana CHECK (dia_semana BETWEEN 1 AND 7)');
            DB::statement('ALTER TABLE horarios_atencion ADD CONSTRAINT chk_horarios_rango_horario CHECK (cierre > apertura)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_atencion');
    }
};
