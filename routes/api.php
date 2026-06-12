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
        Route::get('vehiculos', [VehiculoController::class, 'index']);
        Route::get('vehiculos/{id}', [VehiculoController::class, 'show']);
        Route::post('vehiculos', [VehiculoController::class, 'store']);
        Route::get('contratos/{id}/pdf', [ContratoController::class, 'generarPdf']);
        Route::apiResource('contratos', ContratoController::class)->only(['index', 'show', 'store']);
        Route::apiResource('pagos', PagoController::class)->only(['index', 'show', 'store']);
        Route::apiResource('cargos-adicionales', CargoAdicionalController::class)->only(['index', 'show', 'store']);
        Route::apiResource('incidencias', IncidenciaController::class)->only(['index', 'show', 'store']);
        Route::apiResource('cierres-renta', CierreRentaController::class)->only(['index', 'show', 'store']);
        Route::apiResource('cancelaciones', CancelarController::class)->only(['index', 'show', 'store']);
    });

});


Route::middleware('auth:api')->group(function () {
    Route::get('dashboard/resumen', [DashboardController::class, 'resumen']);
});
