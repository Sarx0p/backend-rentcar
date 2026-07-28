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
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->foreignId('contrato_id')->nullable()->constrained('contratos');
            $table->foreignId('usuario_id')->constrained('users');

            $table->string('tipo_incidencia', 50);
            $table->string('responsable_tipo', 50);
            $table->string('estado_incidencia', 30);

            $table->string('descripcion', 500)->nullable();
            $table->date('fecha');
            $table->decimal('costo', 8, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
