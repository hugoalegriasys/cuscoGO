<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega coordenadas geográficas opcionales al catálogo de sitios.
     *
     * La migración es aditiva: los sitios ya registrados conservan sus datos
     * y simplemente quedan sin coordenadas hasta que se siembren o editen.
     * decimal(10,7) admite los rangos completos de latitud (-90..90) y
     * longitud (-180..180) con siete decimales de precisión.
     */
    public function up(): void
    {
        Schema::table('sitios', function (Blueprint $table): void {
            $table->decimal('latitud', 10, 7)->nullable()->after('ubicacion');
            $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
        });
    }

    public function down(): void
    {
        Schema::table('sitios', function (Blueprint $table): void {
            $table->dropColumn(['latitud', 'longitud']);
        });
    }
};
