<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resenas', function (Blueprint $table) {
            $table->foreignId('sitio_id')->nullable()->after('id')->constrained('sitios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resenas', function (Blueprint $table) {
            $table->dropForeign(['sitio_id']);
            $table->dropColumn('sitio_id');
        });
    }
};