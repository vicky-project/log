@extends('coreui::layouts.admin')
@section('title', 'Schedule Execution Logs')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-0">
      <i class="bi bi-journal-code me-2 text-primary"></i>Execution Logs
    </h4>
    <p class="text-muted small mt-1 mb-0">
      Riwayat eksekusi task terjadwal
    </p>
  </div>
  <a href="{{ route('admin.schedule-monitor.index') }}" class="btn btn-outline-secondary mt-2 mt-sm-0">
    <i class="bi bi-arrow-left me-1"></i> Back to Monitor
  </a>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-0 pt-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-4">
        <label for="taskFilter" class="form-label">Filter by Task</label>
        <select id="taskFilter" class="form-select" onchange="window.location.href='?task='+encodeURIComponent(this.value)">
          <option value="">All Tasks</option>
          @foreach($taskList as $name)
          <option value="{{ $name }}" {{ $taskName == $name ? 'selected' : '' }}>{{ $name }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <th style="min-width: 180px">Task</th>
          <th style="min-width: 160px">Started At</th>
          <th style="min-width: 160px">Finished At</th>
          <th style="min-width: 80px">Duration</th>
          <th style="min-width: 80px">Exit Code</th>
          <th style="min-width: 100px">Triggered By</th>
          <th>Output / Error</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $log)
        <tr>
          <td class="fw-semibold">{{ $log->task_name }}</td>
          <td class="small">{{ $log->started_at ? $log->started_at->format('d/m/Y H:i:s') : '-' }}</td>
          <td class="small">{{ $log->finished_at ? $log->finished_at->format('d/m/Y H:i:s') : '-' }}</td>
          <td class="small">{{ $log->duration ? number_format($log->duration,2).'s' : '-' }}</td>
          <td>
            @if($log->exit_code === 0)
            <span class="badge bg-success">0 (Success)</span>
            @elseif($log->exit_code !== null)
            <span class="badge bg-danger">{{ $log->exit_code }}</span>
            @else
            <span class="text-muted">-</span>
            @endif
          </td>
          <td>
            @if($log->triggered_by === 'manual')
            <span class="badge bg-info">Manual</span>
            @else
            <span class="badge bg-secondary">Schedule</span>
            @endif
          </td>
          <td>
            @if($log->output)
            <pre class="small text-muted mb-0" style="white-space: pre-wrap; max-height: 100px; overflow-y: auto;">{{ \Illuminate\Support\Str::limit($log->output, 300) }}</pre>
            @elseif($log->error)
            <pre class="small text-danger mb-0" style="white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($log->error, 300) }}</pre>
            @else
            <span class="text-muted">-</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-1"></i>
            <p class="mt-2">
              No execution logs found.
            </p>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if(method_exists($logs, 'links'))
  <div class="d-flex justify-content-end p-3">
    {{ $logs->links() }}
  </div>
  @endif
</div>
</div>
@endsection