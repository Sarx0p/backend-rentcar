<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ReservaController;

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
        Route::patch('reservas/{id}/cancelar', [ReservaController::class, 'cancelar']);

    });

});