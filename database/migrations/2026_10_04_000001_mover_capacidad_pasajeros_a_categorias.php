<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El límite de pasajeros pasa de cada modelo a cada categoría, con un mínimo y un máximo.
 */
return new class extends Migration
{
    // Rangos de referencia según el tipo de vehículo (se pueden editar después desde el sistema)
    private array $rangosPorNombre = [
        'compacto' => [2, 5],
        'sedan'    => [4, 5],
        'sedán'    => [4, 5],
        'suv'      => [5, 8],
        'pickup'   => [2, 5],
        'pick up'  => [2, 5],
        'microbus' => [8, 15],
        'microbús' => [8, 15],
    ];

    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->unsignedTinyInteger('capacidad_minima')->default(1)->after('precio_dia');
            $table->unsignedTinyInteger('capacidad_maxima')->default(5)->after('capacidad_minima');
        });

        // Cada categoría toma su rango de referencia, ampliado si ya tiene vehículos fuera de él,
        // para que ningún vehículo registrado quede fuera del rango de su categoría.
        foreach (DB::table('categorias')->get() as $categoria) {
            [$minimo, $maximo] = $this->rangosPorNombre[mb_strtolower(trim($categoria->nombre))] ?? [1, 5];

            $vehiculos = DB::table('vehiculos')->where('categoria_id', $categoria->id);
            $menorRegistrado = (clone $vehiculos)->min('capacidad_pasajeros');
            $mayorRegistrado = (clone $vehiculos)->max('capacidad_pasajeros');

            DB::table('categorias')->where('id', $categoria->id)->update([
                'capacidad_minima' => $menorRegistrado ? min($minimo, $menorRegistrado) : $minimo,
                'capacidad_maxima' => $mayorRegistrado ? max($maximo, $mayorRegistrado) : $maximo,
            ]);
        }

        Schema::table('modelos', function (Blueprint $table) {
            $table->dropColumn('capacidad_maxima');
        });
    }

    public function down(): void
    {
        Schema::table('modelos', function (Blueprint $table) {
            $table->unsignedTinyInteger('capacidad_maxima')->default(5)->after('marca_id');
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn(['capacidad_minima', 'capacidad_maxima']);
        });
    }
};
