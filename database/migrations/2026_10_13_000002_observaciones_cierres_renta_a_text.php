<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las observaciones del cierre de renta se arman juntando las observaciones del usuario (hasta 500),
 * la lista de cargos adicionales y la de incidencias no resueltas, así que pueden pasar de 500 caracteres.
 * Por eso la columna vuelve a ser text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cierres_renta', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cierres_renta', function (Blueprint $table) {
            $table->string('observaciones', 500)->nullable()->change();
        });
    }
};
