<?php

use Illuminate\Support\Facades\Route;
use Modules\Log\Http\Controllers\AppLogController;
use Modules\Log\Http\Controllers\AuthLogController;

Route::prefix('admin')
->name('admin.')
->middleware(['auth'])
->group(function() {
  Route::prefix('logs')
  ->name('logs.')->group(function() {
    Route::get('app', [AppLogController::class, 'index'])->name('app');
    Route::get('auth', [AuthLogController::class, 'index'])->name('auth');
    Route::get('auth/{auth_log}', [AuthLogController::class, 'show'])->name('auth.show');
  });
});