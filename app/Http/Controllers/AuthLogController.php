<?php

namespace Modules\Log\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class AuthLogController extends Controller
{
  /**
  * Display a listing of the resource.
  */
  public function index() {
    $logs = AuthenticationLog::with('authenticatable')
    ->latest("login_at")
    ->paginate(10)
    ->withQueryString();

    return view('log::logs.auth', compact('logs'));
  }

  /**
  * Show the specified resource.
  */
  public function show(AuthenticationLog $auth_log) {
    return view("log::logs.show-authlog", compact("auth_log"));
  }
}