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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('dui', 20)->unique();
            $table->date('nacimiento_dui');
            $table->string('numero_licencia', 30)->unique();
            $table->date('vencimiento_licencia');
            $table->string('telefono', 25);
            $table->string('departamento', 50);
            $table->string('municipio', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
