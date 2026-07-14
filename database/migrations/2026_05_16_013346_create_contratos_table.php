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
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_contrato', 50)->unique();

            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->foreignId('reserva_id')->nullable()->constrained('reservas');
            $table->dateTime('fecha_hora_entrega');
            $table->dateTime('fecha_hora_devolucion');
            $table->integer('dias_acordados');
            $table->decimal('precio_por_dia', 8, 2);
            $table->decimal('monto_descuento', 8, 2)->default(0.00);
            $table->decimal('monto_total_renta', 8, 2);
            $table->string('nivel_combustible_entrega', 50);
            $table->string('observaciones_entrega', 500)->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->string('estado_contrato', 30);
            $table->string('estado_pago', 30);
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
