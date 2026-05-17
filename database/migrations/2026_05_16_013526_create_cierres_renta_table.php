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
        Schema::create('cierres_renta', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_hora_recepcion');
            $table->string('nivel_combustible_recepcion', 30);
            $table->string('estado_vehiculo_recepcion', 50);
            $table->text('observaciones')->nullable();
            $table->integer('horas_retraso')->default(0);
            $table->decimal('monto_extras', 8, 2)->default(0.00);
            $table->string('estado', 30);
            $table->foreignId('contrato_id')->constrained('contratos');
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cierres_renta');
    }
};
