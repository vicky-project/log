<?php

use Illuminate\Support\Facades\Route;
use Modules\Log\Http\Controllers\ScheduleMonitorController;

Route::prefix("schedule-monitor")
->name("schedule-monitor.")
->group(function() {
  Route::get("task-detail/{taskIdentifier}", [ScheduleMonitorController::class, "apiTaskDetail"]);
  Route::post("run/{taskIdentifier}", [ScheduleMonitorController::class, "run"]);
  Route::post("toggle/{taskIdentifier}", [ScheduleMonitorController::class, "toggle"]);
});