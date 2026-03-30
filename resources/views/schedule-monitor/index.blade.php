@extends('coreui::layouts.admin')
@section('title', 'Schedule Monitor')

@push('styles')
<style>
  .task-card {
    transition: all 0.2s ease;
    cursor: pointer;
  }
  .task-card:hover {
    background-color: rgba(0,0,0,0.02);
  }
  .badge-status-success {
    background-color: #198754;
  }
  .badge-status-failed {
    background-color: #dc3545;
  }
  .badge-status-running {
    background-color: #0dcaf0;
  }
  .badge-status-pending {
    background-color: #6c757d;
  }
  .next-run-badge {
    font-family: monospace;
    font-size: 0.8rem;
  }
  .task-dependencies {
    font-size: 0.7rem;
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 12px;
    display: inline-block;
  }
  .stat-card {
    transition: all 0.3s ease;
  }
  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
  }
</style>
@endpush

@section('content')
<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-0">
      <i class="bi bi-calendar-check me-2 text-primary"></i>Schedule Monitor
    </h4>
    <p class="text-muted small mt-1 mb-0">
      Monitor dan kelola seluruh task terjadwal di aplikasi
    </p>
  </div>
  <div class="mt-2 mt-sm-0">
    <button class="btn btn-outline-primary btn-sm" onclick="window.location.reload()">
      <i class="bi bi-arrow-repeat"></i> Refresh
    </button>
  </div>
</div>

<!-- Statistik Ringkas -->
<div class="row mb-4">
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card stat-card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Total Tasks</h6>
            <h3 class="mb-0">{{ $totalTasks }}</h3>
          </div>
          <div class="stat-icon bg-primary bg-opacity-10">
            <i class="bi bi-list-check fs-4 text-primary"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card stat-card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Active Tasks</h6>
            <h3 class="mb-0">{{ $activeTasks }}</h3>
          </div>
          <div class="stat-icon bg-success bg-opacity-10">
            <i class="bi bi-check-circle fs-4 text-success"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card stat-card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Failed Today</h6>
            <h3 class="mb-0">{{ $failedToday }}</h3>
          </div>
          <div class="stat-icon bg-danger bg-opacity-10">
            <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card stat-card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Groups</h6>
            <h3 class="mb-0">{{ count($groups) }}</h3>
          </div>
          <div class="stat-icon bg-info bg-opacity-10">
            <i class="bi bi-folder fs-4 text-info"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tabel Daftar Task -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-0 pt-4 pb-0">
    <ul class="nav nav-tabs card-header-tabs" id="taskTabs" role="tablist">
      <li class="nav-item">
        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">All Tasks</button>
      </li>
      @foreach($groups as $group => $count)
      <li class="nav-item">
        <button class="nav-link" id="{{ Str::slug($group) }}-tab" data-bs-toggle="tab" data-bs-target="#{{ Str::slug($group) }}" type="button" role="tab">{{ $group }} ({{ $count }})</button>
      </li>
      @endforeach
    </ul>
  </div>
  <div class="card-body p-0">
    <div class="tab-content">
      <!-- Tab All Tasks -->
      <div class="tab-pane fade show active" id="all" role="tabpanel">
        <div class="table-responsive">
          @include('log::schedule-monitor.task-table', ['tasks' => $tasks])
        </div>
      </div>
      @foreach($groups as $group => $count)
      <div class="tab-pane fade" id="{{ Str::slug($group) }}" role="tabpanel">
        <div class="table-responsive">
          @include('log::schedule-monitor.task-table', ['tasks' => collect($tasks)->where('group', $group)->values()->toArray()])
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>

<!-- Modal Detail Task -->
<div class="modal fade" id="taskDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Task Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="taskDetailBody">
        <div class="text-center py-4">
          <div class="spinner-border text-primary"></div>
          <p class="mt-2">
            Loading...
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function showTaskDetail(taskIdentifier) {
    const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
    document.getElementById('taskDetailBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p>Loading...</p></div>';
    modal.show();

    fetch(`{{ secure_url(config('app.url')) }}/api/schedule-monitor/task-detail/${taskIdentifier}`)
    .then(response => response.json())
    .then(data => {
    let html = `
    <div class="row">
    <div class="col-md-6">
    <h6>Task Information</h6>
    <table class="table table-sm">
    <tr><th>Name:</th><td>${data.task.name}</td></tr>
    <tr><th>Label:</th><td>${data.task.label}</td></tr>
    <tr><th>Group:</th><td>${data.task.group || '-'}</td></tr>
    <tr><th>Schedule:</th><td><code>${data.task.schedule}</code></td></tr>
    <tr><th>Description:</th><td>${data.task.description || '-'}</td></tr>
    <tr><th>Enabled:</th><td>${data.task.enabled ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'}</td></tr>
    </table>
    </div>
    <div class="col-md-6">
    <h6>Last Execution</h6>
    <table class="table table-sm">
    <tr><th>Started:</th><td>${data.task.last_run ? new Date(data.task.last_run).toLocaleString() : '-'}</td></tr>
    <tr><th>Duration:</th><td>${data.task.last_duration ? data.task.last_duration + ' seconds' : '-'}</td></tr>
    <tr><th>Status:</th><td><span class="badge bg-${data.task.last_status === 'success' ? 'success' : (data.task.last_status === 'failed' ? 'danger' : 'secondary')}">${data.task.last_status || '-'}</span></td></tr>
    <tr><th>Next Run:</th><td>${data.task.next_run_human} (${new Date(data.task.next_run).toLocaleString()})</td></tr>
    </table>
    </div>
    </div>
    <div class="mt-3">
    <h6>Recent Logs</h6>
    <div style="max-height: 300px; overflow-y: auto;">
    ${data.recent_logs.length ? data.recent_logs.map(log => `
    <div class="border-bottom mb-2 pb-2">
    <div class="small text-muted">${new Date(log.created_at).toLocaleString()}</div>
    <div><strong>Exit Code:</strong> ${log.exit_code ?? '-'}</div>
    <pre class="small bg-light p-2 rounded" style="white-space: pre-wrap;">${log.output || log.error || '(no output)'}</pre>
    </div>
    `).join('') : '<p class="text-muted">No logs yet</p>'}
    </div>
    </div>
    `;
    document.getElementById('taskDetailBody').innerHTML = html;
    })
    .catch(error => {
    document.getElementById('taskDetailBody').innerHTML = `<div class="alert alert-danger">Failed to load details: ${error.message}</div>`;
    });
  }

  function runTask(taskIdentifier) {
    if (!confirm('Run this task now?')) return;
    fetch(`{{ secure_url(config('app.url')) }}/api/schedule-monitor/run/${taskIdentifier}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => {
    alert(data.message);
    if (data.success) window.location.reload();
    })
    .catch(error => alert('Error: ' + error.message));
  }

  function toggleTask(taskIdentifier) {
    fetch(`{{ secure_url(config('app.url')) }}/api/schedule-monitor/toggle/${taskIdentifier}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => {
    if (data.success) window.location.reload();
    else alert('Toggle failed');
    })
    .catch(error => alert('Error: ' + error.message));
  }

  function viewLogs(taskName) {
    window.location.href = `{{ route('admin.schedule-monitor.logs') }}?task=${encodeURIComponent(taskName)}`;
  }
</script>
@endpush