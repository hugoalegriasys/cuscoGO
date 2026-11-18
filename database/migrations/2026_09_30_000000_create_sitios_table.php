<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitios', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 150)->unique('sitios_nombre_unique');
            $table->string('categoria', 80)->nullable();
            $table->string('ubicacion', 200)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'nombre'], 'sitios_activo_nombre_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitios');
    }
};
