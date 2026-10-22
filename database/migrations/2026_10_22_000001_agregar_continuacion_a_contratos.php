<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('contrato_origen_id')->nullable()->after('reserva_id')->constrained('contratos');
            $table->decimal('saldo_trasladado', 8, 2)->default(0)->after('monto_total_renta');
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contrato_origen_id');
            $table->dropColumn('saldo_trasladado');
        });
    }
};
