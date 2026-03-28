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
    $logs = AuthenticationLog::with('authenticatable')->latest("login_at")
    ->get()
    ->map(function ($log) {
      $agent = tap(
        new Agent(),
        fn($agent) => $agent->setUserAgent($log->user_agent),
      );

      return [
        "id" => $log->id,
        "name" => $log->authenticatable ? $log->authenticatable->name : null,
        "email" => $log->authenticatable
        ? $log->authenticatable->email
        : null,
        "ip_address" => $log->ip_address,
        "user_agent" => $agent->platform() . " - " . $agent->browser(),
        "location" =>
        $log->location && $log->location["default"] === false
        ? $log->location["city"] . ", " . ($log->location["state_prov"] ?? $log->location["district"])
        : "-",
        "login_at" => $log->login_at
        ? $log->login_at->format("d-m-Y H:i:s")
        : "-",
        "login_successful" => $log->login_successful,
        "logout_at" => $log->logout_at
        ? $log->logout_at->format("d-m-Y H:i:s")
        : "Never",
        "cleared_by_user" => $log->cleared_by_user ? "Yes" : "No",
      ];
    });
    return view('log::logs.auth', compact('logs'));
  }

  /**
  * Show the specified resource.
  */
  public function show(AuthenticationLog $auth_log) {
    return view("log::logs.show-authlog", compact("auth_log"));
  }
}