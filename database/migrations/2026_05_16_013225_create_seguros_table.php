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
        Schema::create('seguros', function (Blueprint $table) {
            $table->id();
            $table->string('aseguradora', 150);
            $table->string('numero_poliza', 50)->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');
            $table->text('cobertura')->nullable();
            $table->string('estado', 30);
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguros');
    }
};
