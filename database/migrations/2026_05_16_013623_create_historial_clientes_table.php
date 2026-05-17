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
        Schema::create('historial_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users'); 
            $table->string('tipo_registro', 50);
            $table->text('descripcion')->nullable();
            $table->decimal('monto_pendiente', 8, 2)->default(0.00);
            $table->dateTime('fecha_registro');
            $table->string('estado', 30);
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('contrato_id')->constrained('contratos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_clientes');
    }
};
