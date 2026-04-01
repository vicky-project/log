<?php

use Illuminate\Support\Facades\Route;
use Modules\Log\Http\Controllers\AppLogController;
use Modules\Log\Http\Controllers\ActivityLogController;
use Modules\Log\Http\Controllers\AuthLogController;
use Modules\Log\Http\Controllers\ScheduleMonitorController;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;

Route::prefix('admin')
->name('admin.')
->middleware(['auth'])
->group(function() {
  Route::prefix('logs')
  ->name('logs.')
  ->group(function() {
    Route::get('app', [LogViewerController::class, 'index'])->name('app');
    Route::get('auth', [AuthLogController::class, 'index'])->name('auth');
    Route::get('auth/{auth_log}', [AuthLogController::class, 'show'])->name('auth.show');
    Route::get("activity", [ActivityLogController::class, "index"])->name("activity");
  });

  Route::prefix("schedule-monitor")
  ->name("schedule-monitor.")
  ->group(function() {
    Route::get("/", [ScheduleMonitorController::class, "index"])->name("index");
    Route::get("logs", [ScheduleMonitorController::class, "logs"])->name("logs");
  });
});