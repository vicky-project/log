@extends('coreui::layouts.admin')
@section('title', 'Schedule Execution Logs')

@push('styles')
<style>
  .stat-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
  }
  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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
  .chart-container {
    position: relative;
    height: 300px;
    width: 100%;
  }
  .log-output {
    max-height: 100px;
    overflow-y: auto;
    font-size: 0.75rem;
    white-space: pre-wrap;
    word-break: break-word;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-0">
      <i class="bi bi-journal-code me-2 text-primary"></i>Execution Logs
    </h4>
    <p class="text-muted small mt-1 mb-0">
      Riwayat eksekusi task terjadwal dengan analisis statistik
    </p>
  </div>
  <a href="{{ route('admin.schedule-monitor.index') }}" class="btn btn-outline-secondary mt-2 mt-sm-0">
    <i class="bi bi-arrow-left me-1"></i> Back to Monitor
  </a>
</div>

<!-- Filter Task -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-0 pt-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-4">
        <label for="taskFilter" class="form-label fw-semibold">
          <i class="bi bi-funnel me-1"></i> Filter by Task
        </label>
        <select id="taskFilter" class="form-select" onchange="window.location.href='?task='+encodeURIComponent(this.value)">
          <option value="">All Tasks</option>
          @foreach($taskList as $name)
          <option value="{{ $name }}" {{ $taskName == $name ? 'selected' : '' }}>{{ $name }}</option>
          @endforeach
        </select>
      </div>
      @if($taskName)
      <div class="col-md-4">
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle me-1"></i>
          Menampilkan statistik untuk task: <strong>{{ $taskName }}</strong>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>

<!-- Statistik Cards (hanya jika ada data) -->
@if($stats['total_executions'] > 0)
<div class="row mb-4">
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card stat-card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Total Executions</h6>
            <h3 class="mb-0">{{ number_format($stats['total_executions']) }}</h3>
          </div>
          <div class="stat-icon bg-primary bg-opacity-10">
            <i class="bi bi-play-circle fs-4 text-primary"></i>
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
            <h6 class="text-muted mb-1">Success Rate</h6>
            <h3 class="mb-0">{{ $stats['success_rate'] }}%</h3>
          </div>
          <div class="stat-icon bg-success bg-opacity-10">
            <i class="bi bi-check-circle fs-4 text-success"></i>
          </div>
        </div>
        <div class="progress mt-2" style="height: 5px;">
          <div class="progress-bar bg-success" style="width: {{ $stats['success_rate'] }}%"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 mb-3">
    <div class="card stat-card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Success / Failed</h6>
            <h3 class="mb-0">{{ number_format($stats['success_count']) }} / {{ number_format($stats['failed_count']) }}</h3>
          </div>
          <div class="stat-icon bg-info bg-opacity-10">
            <i class="bi bi-bar-chart fs-4 text-info"></i>
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
            <h6 class="text-muted mb-1">Avg. Duration</h6>
            <h3 class="mb-0">{{ number_format($stats['avg_duration'], 2) }}s</h3>
          </div>
          <div class="stat-icon bg-warning bg-opacity-10">
            <i class="bi bi-stopwatch fs-4 text-warning"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chart Section (hanya jika ada task yang difilter) -->
@if($taskName && !empty($stats['chart_labels']) && !empty($stats['chart_data']))
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-0 pt-4 pb-0">
    <h6 class="mb-0">
      <i class="bi bi-graph-up me-2 text-primary"></i>Daily Execution Trend (Last 7 Days)
    </h6>
  </div>
  <div class="card-body">
    <div class="chart-container">
      <canvas id="executionChart"></canvas>
    </div>
  </div>
</div>
@endif
@endif

<!-- Tabel Logs -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-0 pt-4 pb-0">
    <div class="d-flex justify-content-between align-items-center">
      <h6 class="mb-0">
        <i class="bi bi-table me-2 text-primary"></i>Execution Details
      </h6>
      <span class="badge bg-light text-dark">{{ $logs->total() }} records</span>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
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
              <span class="badge bg-success rounded-pill px-3">0 (Success)</span>
              @elseif($log->exit_code !== null)
              <span class="badge bg-danger rounded-pill px-3">{{ $log->exit_code }}</span>
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
              <div class="log-output text-muted">
                {{ \Illuminate\Support\Str::limit($log->output, 300) }}
              </div>
              @elseif($log->error)
              <div class="log-output text-danger">
                {{ \Illuminate\Support\Str::limit($log->error, 300) }}
              </div>
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
    @if($logs->hasPages())
    <div class="d-flex justify-content-end p-3">
      {{ $logs->links() }}
    </div>
    @endif
  </div>
</div>

@if($taskName && !empty($stats['chart_labels']) && !empty($stats['chart_data']))
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
  const ctx = document.getElementById('executionChart').getContext('2d');
  new Chart(ctx, {
  type: 'line',
  data: {
  labels: {!! $stats['chart_labels'] !!},
  datasets: [{
  label: 'Executions',
  data: {!! $stats['chart_data'] !!},
  borderColor: 'rgb(75, 192, 192)',
  backgroundColor: 'rgba(75, 192, 192, 0.2)',
  tension: 0.3,
  fill: true,
  pointBackgroundColor: 'rgb(75, 192, 192)',
  pointBorderColor: '#fff',
  pointRadius: 4,
  pointHoverRadius: 6
  }]
  },
  options: {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
  tooltip: {
  callbacks: {
  title: function(context) {
  return 'Date: ' + context[0].label;
  },
  label: function(context) {
  return `Executions: ${context.raw}`;
  }
  }
  },
  legend: {
  position: 'top',
  }
  },
  scales: {
  y: {
  beginAtZero: true,
  title: {
  display: true,
  text: 'Number of Executions',
  font: {
  weight: 'bold'
  }
  },
  ticks: {
  stepSize: 1
  }
  },
  x: {
  title: {
  display: true,
  text: 'Date',
  font: {
  weight: 'bold'
  }
  }
  }
  }
  }
  });
  });
</script>
@endpush
@endif
@endsection