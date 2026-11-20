<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('horario_apertura', 5);
            $table->string('horario_cierre', 5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitios');
    }
};