<?php

namespace Modules\Log\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class AuthLogController extends Controller
{
  /**
  * Display a listing of the resource.
  */
  public function index() {
    $logs = new stdClass();
    if (class_exists(\Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog::class)) {
      $logs = \Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog::with('authenticatable')
      ->latest("login_at")
      ->paginate(10)
      ->withQueryString();
    }

    return view('log::logs.auth', compact('logs'));
  }

  /**
  * Show the specified resource.
  */
  public function show(int $authlog_id) {
    if (!class_exists(\Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog::class)) {
      return back()->with('errors', 'Package Rappasoft authentication log not installed yet.');
    }
    $auth_log = \Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog::find($authlog_id);
    return view("log::logs.show-authlog", compact("auth_log"));
  }
}