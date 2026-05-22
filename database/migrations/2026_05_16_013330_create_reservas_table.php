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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_solicitud');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('tipo_reserva', 50);
            $table->string('estado', 30);
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('cancelacion_id')->nullable()->constrained('cancelaciones');//problema logico si lo dejaba como antes era obligatorio qeu existiera una cancelacion desde el principio pero hoy ya esta corrregido
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
