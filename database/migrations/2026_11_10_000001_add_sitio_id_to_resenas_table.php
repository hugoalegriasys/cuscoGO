<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('resenas') && !Schema::hasColumn('resenas', 'sitio_id')) {
            Schema::table('resenas', function (Blueprint $table) {
                $table->string('sitio_id')->default('machu-picchu')->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('resenas') && Schema::hasColumn('resenas', 'sitio_id')) {
            Schema::table('resenas', function (Blueprint $table) {
                $table->dropColumn('sitio_id');
            });
        }
    }
};
