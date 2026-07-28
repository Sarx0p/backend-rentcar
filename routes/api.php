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
use App\Http\Controllers\CargoAdicionalController;
use App\Http\Controllers\CierreRentaController;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\CancelarController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\PropietarioController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SeguroController;

Route::get('/marcas', [MarcaController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::post('/marcas', [MarcaController::class, 'store']);
});

Route::get('/modelos', [ModeloController::class, 'index']);
Route::get('/marcas/{marcaId}/modelos', [ModeloController::class, 'porMarca']);

Route::middleware('auth:api')->group(function () {
    Route::post('/modelos', [ModeloController::class, 'store']);
});

Route::get('categorias', [CategoriaController::class, 'index']);
Route::get('categorias/{id}', [CategoriaController::class, 'show']);

Route::group(['middleware' => ['auth:api']], function () {
    Route::post('categorias', [CategoriaController::class, 'store']);
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
        Route::get('vehiculos/disponibles', [VehiculoController::class, 'index']);
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
        Route::get('/departamentos/{departamentoId}/municipios', [DepartamentoController::class, 'porDepartamento']);

        Route::prefix('reportes')->group(function () {
            Route::get('ingresos', [ReporteController::class, 'ingresos']);
            Route::get('estado-flota', [ReporteController::class, 'estadoFlota']);
            Route::get('licencias-por-vencer', [ReporteController::class, 'licenciasPorVencer']);
            Route::get('reservas-canceladas', [ReporteController::class, 'reservasCanceladas']);
        });
    });
});

Route::middleware('auth:api')->group(function () {
    Route::get('dashboard/resumen', [DashboardController::class, 'resumen']);
});
