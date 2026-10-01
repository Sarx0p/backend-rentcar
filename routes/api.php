<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ModeloController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\CargoAdicionalController;
use App\Http\Controllers\CierreRentaController;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\CancelarController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\PropietarioController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SeguroController;
use App\Http\Controllers\HistorialController;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware(['auth:api', 'estado.activo'])->group(function () {
        Route::get('me',       [AuthController::class, 'me']);
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::prefix('admin')->middleware(['auth:api', 'estado.activo'])->group(function () {

    Route::middleware(['role:ADMINISTRADOR'])->group(function () {
        Route::apiResource('usuarios', UsuarioController::class);
        Route::apiResource('propietarios', PropietarioController::class)->except(['index', 'show']);
        Route::apiResource('seguros', SeguroController::class)->except(['index', 'show']);
    });

    // el empleado no ve el dashboard
    Route::middleware(['role:ADMINISTRADOR|CONTADOR'])->group(function () {
        Route::get('/dashboard/resumen', [DashboardController::class, 'resumen']);
    });

    // el contador entra solo a pagos y reportes, lo demas lo lee para esas pantallas
    Route::middleware(['role:ADMINISTRADOR|EMPLEADO|CONTADOR'])->group(function () {
        Route::apiResource('pagos', PagoController::class)->only(['index', 'show', 'store', 'destroy']);
        Route::apiResource('contratos', ContratoController::class)->only(['index', 'show']);
        Route::apiResource('cierres-renta', CierreRentaController::class)->only(['index', 'show']);
        Route::apiResource('propietarios', PropietarioController::class)->only(['index', 'show']);

        Route::prefix('reportes')->group(function () {
            Route::get('ingresos', [ReporteController::class, 'ingresos']);
            Route::get('estado-flota', [ReporteController::class, 'estadoFlota']);
            Route::get('resultado-neto-por-propietario', [ReporteController::class, 'resultadoNetoPorPropietario']);
            Route::get('licencias-por-vencer', [ReporteController::class, 'licenciasPorVencer']);
            Route::get('reservas-canceladas', [ReporteController::class, 'reservasCanceladas']);
            Route::get('desempeno-general', [ReporteController::class, 'desempenoGeneral']);
            Route::get('ingresos-por-vehiculo', [ReporteController::class, 'ingresosPorVehiculo']);
            Route::get('gastos-por-vehiculo', [ReporteController::class, 'gastosPorVehiculo']);
            Route::get('resultado-neto-por-vehiculo', [ReporteController::class, 'resultadoNetoPorVehiculo']);
            Route::get('saldos-pendientes', [ReporteController::class, 'saldosPendientes']);
        });
    });

    Route::middleware(['role:ADMINISTRADOR|EMPLEADO'])->group(function () {
        Route::apiResource('marcas', MarcaController::class);
        Route::apiResource('modelos', ModeloController::class)->except(['show', 'destroy']);
        Route::get('/marcas/{marcaId}/modelos', [ModeloController::class, 'porMarca']);
        Route::apiResource('categorias', CategoriaController::class);

        Route::apiResource('clientes', ClienteController::class)->except(['destroy']);
        Route::get('clientes/{id}/licencia-vigente', [ClienteController::class, 'licenciaVigente']);
        Route::apiResource('reservas', ReservaController::class)->except(['destroy']);

        Route::apiResource('vehiculos', VehiculoController::class);
        Route::get('/notificaciones', [NotificacionController::class, 'index']);
        Route::patch('/vehiculos/{id}/restaurar', [VehiculoController::class, 'restaurar']);

        Route::get('contratos/{id}/pdf', [ContratoController::class, 'generarPdf']);
        Route::post('contratos/directo', [ContratoController::class, 'storeDirecto']);
        Route::post('contratos/{id}/cambiar-vehiculo', [ContratoController::class, 'cambiarVehiculo']);
        Route::patch('contratos/{id}/anular', [ContratoController::class, 'anular']);
        Route::post('contratos', [ContratoController::class, 'store'])->name('contratos.store');
        Route::apiResource('cargos-adicionales', CargoAdicionalController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::apiResource('incidencias', IncidenciaController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::post('cierres-renta', [CierreRentaController::class, 'store'])->name('cierres-renta.store');
        Route::apiResource('cancelaciones', CancelarController::class)->only(['index', 'show', 'store']);

        Route::apiResource('seguros', SeguroController::class)->only(['index', 'show']);
        Route::apiResource('mantenimientos', MantenimientoController::class);
        Route::get('/departamentos', [DepartamentoController::class, 'index']);
        Route::get('/departamentos/{departamentoId}/municipios', [DepartamentoController::class, 'porDepartamento']);

        Route::prefix('clientes/{cliente}/historial')->group(function () {
            Route::get('/resumen', [HistorialController::class, 'resumen']);
            Route::get('/reservas', [HistorialController::class, 'reservas']);
            Route::get('/contratos', [HistorialController::class, 'contratos']);
            Route::get('/incidencias', [HistorialController::class, 'incidencias']);
        });
    });
});
