<?php

use Illuminate\Support\Facades\Route;
use Modules\Log\Http\Controllers\AppLogController;

Route::prefix('admin')
->name('admin.')
->middleware(['auth'])
->group(function() {
  Route::prefix('logs')
  ->name('logs.')->group(function() {
    Route::get('app', [AppLogController::class, 'index'])->name('app');
  });
});