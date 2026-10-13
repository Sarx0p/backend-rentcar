<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las categorías solo manejan un máximo de pasajeros; el mínimo es siempre 1 para todas
 * (así categorías como motos no necesitan configurar un mínimo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn('capacidad_minima');
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->unsignedTinyInteger('capacidad_minima')->default(1)->after('precio_dia');
        });
    }
};
