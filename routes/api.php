<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\MarcaController;
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

Route::get('/marcas', [MarcaController::class, 'index']);
Route::get('/marcas/{id}', [MarcaController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::post('/marcas', [MarcaController::class, 'store']);
    Route::put('/marcas/{id}', [MarcaController::class, 'update']);
});

Route::get('/modelos', [ModeloController::class, 'index']);
Route::get('/marcas/{marcaId}/modelos', [ModeloController::class, 'porMarca']);

Route::middleware('auth:api')->group(function () {
    Route::post('/modelos', [ModeloController::class, 'store']);
    Route::put('/modelos/{id}', [ModeloController::class, 'update']);
});

Route::get('categorias', [CategoriaController::class, 'index']);
Route::get('categorias/{id}', [CategoriaController::class, 'show']);

Route::group(['middleware' => ['auth:api']], function () {
    Route::post('categorias', [CategoriaController::class, 'store']);
    Route::put('categorias/{id}', [CategoriaController::class, 'update']);
    Route::delete('categorias/{id}', [CategoriaController::class, 'destroy']);
});

Route::prefix('auth')->group(function () {

    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me',       [AuthController::class, 'me']);
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::prefix('admin')->group(function () {

    Route::middleware(['auth:api', 'role:ADMINISTRADOR'])->group(function () {
        Route::apiResource('usuarios', UsuarioController::class);
    });

    Route::middleware(['auth:api', 'role:ADMINISTRADOR|EMPLEADO'])->group(function () {
        Route::apiResource('clientes', ClienteController::class);
        Route::get('clientes/{id}/licencia-vigente', [ClienteController::class, 'licenciaVigente']);
        Route::apiResource('reservas', ReservaController::class)->except(['destroy']);
        // SE QUITO POR LA OPTIMISACION DE EL INDEX DE VEHIUCLOS DONDE QEUDABA LA RUTA INUTILIZADA
        Route::apiResource('vehiculos', VehiculoController::class);
        Route::get('contratos/{id}/pdf', [ContratoController::class, 'generarPdf']);
        Route::post('contratos/directo', [ContratoController::class, 'storeDirecto']);
        Route::apiResource('contratos', ContratoController::class)->only(['index', 'show', 'store']);
        Route::apiResource('pagos', PagoController::class)->only(['index', 'show', 'store']);
        Route::apiResource('cargos-adicionales', CargoAdicionalController::class)->only(['index', 'show', 'store']);
        Route::apiResource('incidencias', IncidenciaController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::apiResource('cierres-renta', CierreRentaController::class)->only(['index', 'show', 'store']);
        Route::apiResource('cancelaciones', CancelarController::class)->only(['index', 'show', 'store']);
        Route::apiResource('propietarios', PropietarioController::class);
        Route::apiResource('seguros', SeguroController::class);
        Route::get('/departamentos', [DepartamentoController::class, 'index']);
        Route::apiResource('mantenimientos', MantenimientoController::class);
        Route::get('/departamentos/{departamentoId}/municipios', [DepartamentoController::class, 'porDepartamento']);



        Route::prefix('clientes/{cliente}/historial')->group(function () {
            Route::get('/resumen', [HistorialController::class, 'resumen']);
            Route::get('/reservas', [HistorialController::class, 'reservas']);
            Route::get('/contratos', [HistorialController::class, 'contratos']);
            Route::get('/incidencias', [HistorialController::class, 'incidencias']);
        });

        Route::prefix('reportes')->group(function () {
            Route::get('ingresos', [ReporteController::class, 'ingresos']);
            Route::get('estado-flota', [ReporteController::class, 'estadoFlota']);
            Route::get('licencias-por-vencer', [ReporteController::class, 'licenciasPorVencer']);
            Route::get('reservas-canceladas', [ReporteController::class, 'reservasCanceladas']);
            Route::get('desempeno-general', [ReporteController::class, 'desempenoGeneral']);
            Route::get('ingresos-por-vehiculo', [ReporteController::class, 'ingresosPorVehiculo']);
            Route::get('gastos-por-vehiculo', [ReporteController::class, 'gastosPorVehiculo']);
            Route::get('resultado-neto-por-vehiculo', [ReporteController::class, 'resultadoNetoPorVehiculo']);
            Route::get('saldos-pendientes', [ReporteController::class, 'saldosPendientes']);
        });
    });
});

Route::middleware('auth:api')->group(function () {
    Route::get('dashboard/resumen', [DashboardController::class, 'resumen']);
});
