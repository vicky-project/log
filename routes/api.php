<?php

use Illuminate\Support\Facades\Route;
use Modules\Log\Http\Controllers\LogController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('logs', LogController::class)->names('log');
});
