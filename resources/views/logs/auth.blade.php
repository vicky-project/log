@extends('coreui::layouts.admin')

@use('Modules\LogManagement\Constants\Permissions')

@section('title', 'User Authentication Log')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="h5 card-title">
          Auth Logs
        </div>
        <span class="small ms-2">User authentication log</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead>
              <th>User</th>
              <th>IP Addresss</th>
              <th>Browser</th>
              <th>Log Success</th>
              <th>Action</th>
            </thead>
            <tbody>
              @foreach($logs as $log)
              <tr>
                <td>
                  @if($log["name"])
                  <div>
                    {{$log["name"]}}
                  </div>
                  <small>{{$log["email"]}}</small>
                  @else
                  Guest
                  @endif
                </td>
                <td>
                  <code>{{$log["ip_address"]}}</code>
                </td>
                <td>{{$log["user_agent"]}}</td>
                <td>
                  <span class="badge @if($log['login_successful']) badge-success @else badge-danger @endif">
                    @if($log["login_successful"])
                    Yes
                    @else
                    No
                    @endif
                  </span>
                </td>
                <td>
                  <nobr>
                    @can(Permissions::VIEW_AUTHLOG)
                    <a href="{{ route('admin.logs.auth.show', ['auth_log' => $log['id']]) }}" class="btn btn-outline-primary" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    @endcan
                  </nobr>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection