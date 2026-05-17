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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 20)->unique();
            $table->string('color', 30);
            $table->integer('anio');
            $table->string('estado', 50); 
            $table->foreignId('modelo_id')->constrained('modelos');
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->foreignId('propietario_id')->constrained('propietarios');
            $table->foreignId('seguro_id')->constrained('seguros');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
