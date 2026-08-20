<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clientes')
            && Schema::hasColumn('clientes', 'nacimiento_dui')
            && ! Schema::hasColumn('clientes', 'vencimiento_dui')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->renameColumn('nacimiento_dui', 'vencimiento_dui');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clientes')
            && Schema::hasColumn('clientes', 'vencimiento_dui')
            && ! Schema::hasColumn('clientes', 'nacimiento_dui')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->renameColumn('vencimiento_dui', 'nacimiento_dui');
            });
        }
    }
};
