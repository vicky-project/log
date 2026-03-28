<?php

namespace Modules\Log\Http\Controllers;

use Illuminate\Routing\Controller;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
  public function index(Request $request) {
    $activities = Activity::with('causer')
    ->orderBy('created_at', 'desc')
    ->paginate(20);

    return view('log::logs.activities', compact('activities'));
  }
}