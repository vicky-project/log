@extends('coreui::layouts.admin')
@section('title', 'User Log')

@use("Jenssegers\Agent\Agent")
@php
$agent = tap(new Agent, fn($agent) => $agent->setUserAgent($auth_log->user_agent));
$browser = $agent->platform() . " - " . $agent->browser();
@endphp

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('admin.logs.auth') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i>
      </a>
    </div>
    <h5 class="card-title">{{$auth_log->authenticatable->name}}</h5>
  </div>
  <div class="card-body">
    <ul class="list-group list-group-flush">
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Email</strong>
        <span class="text-muted"><i>{{$auth_log->authenticatable->email}}</i></span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>IP</strong>
        <span class="text-muted">{{$auth_log->ip_address}}</span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Browser</strong>
        <span class="text-muted">{{$browser}}</span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Location</strong>
        <span class="text-muted">
          {{$auth_log->location && $auth_log->location["default"] === false ? $auth_log->location["city"] . ", " . $auth_log->location["state"] : "-"}}
        </span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Login At</strong>
        <span class="text-muted">
          {{$auth_log->login_at ? $auth_log->login_at->format("d-m-Y H:i:s") : "Never"}}
        </span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Logout At</strong>
        <span class="text-muted">
          {{$auth_log->logout_at ? $auth_log->logout_at->format("d-m-Y H:i:s") : "Never"}}
        </span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Login Successful</strong>
        <span class="badge bg-{{$auth_log->login_successful ? 'success' : 'danger'}}">
          {{$auth_log->login_successful ? "Yes" : "No"}}
        </span>
      </li>
    </ul>
  </div>
</div>
@endsection