<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Vincula cada sitio con el código de su sensor IoT (CUS-06).
     *
     * El catálogo heredado todavía no tenía columna slug, por eso primero
     * creamos ese identificador público y luego ubicamos sensor_id justo
     * después de él: after('slug') solo altera el orden físico en MySQL,
     * pero declararlo así mantiene el esquema coherente en todos los motores.
     *
     * sensor_id es nullable porque un sitio puede existir antes de que se
     * instale su sensor; unique evita que dos sitios compartan dispositivo
     * y mezclen lecturas de aforo procedentes de fuentes distintas.
     */
    public function up(): void
    {
        Schema::table('sitios', function (Blueprint $table): void {
            $table->string('slug', 100)->nullable()->unique()->after('nombre');
            $table->string('sensor_id', 50)->nullable()->unique()->after('slug');
        });

        /*
         * Backfill de slugs para sitios ya registrados. El sufijo con el id
         * garantiza unicidad aunque dos sitios tengan exactamente el mismo
         * nombre; sin ese sufijo la restricción unique haría fallar la
         * migración en una base de datos poblada.
         */
        DB::table('sitios')->chunkById(100, static function ($sitios): void {
            foreach ($sitios as $sitio) {
                DB::table('sitios')
                    ->where('id', $sitio->id)
                    ->update(['slug' => Str::slug((string) $sitio->nombre).'-'.$sitio->id]);
            }
        });
    }

    public function down(): void
    {
        // Al eliminar las columnas también se eliminan sus índices únicos.
        Schema::table('sitios', function (Blueprint $table): void {
            $table->dropColumn(['sensor_id', 'slug']);
        });
    }
};
