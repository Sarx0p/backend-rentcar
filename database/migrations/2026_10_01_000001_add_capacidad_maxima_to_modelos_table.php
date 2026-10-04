<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('modelos', function (Blueprint $table) {
            $table->unsignedTinyInteger('capacidad_maxima')->default(5)->after('marca_id');
        });

        // Los modelos que ya tienen vehículos toman como máximo la mayor capacidad registrada,
        // para que ningún vehículo existente quede por encima del límite de su modelo.
        DB::table('modelos')->update([
            'capacidad_maxima' => DB::raw('GREATEST(5, COALESCE((SELECT MAX(v.capacidad_pasajeros) FROM vehiculos v WHERE v.modelo_id = modelos.id), 0))'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modelos', function (Blueprint $table) {
            $table->dropColumn('capacidad_maxima');
        });
    }
};
