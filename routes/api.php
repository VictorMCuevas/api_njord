<?php

use App\Http\Controllers\CondicionAtmosfericaController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/rutas', RutaController::class);
Route::apiResource('/usuarios', UserController::class);
Route::apiResource('/condiciones-atmosfericas', CondicionAtmosfericaController::class)
    ->parameters(['condiciones-atmosfericas' => 'condicionAtmosferica']);