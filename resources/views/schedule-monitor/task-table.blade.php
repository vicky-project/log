<table class="table table-hover align-middle mb-0">
  <thead class="table-light">
    <th style="min-width: 220px">Task</th>
    <th style="min-width: 130px">Schedule</th>
    <th style="min-width: 100px">Last Run</th>
    <th style="min-width: 80px">Status</th>
    <th style="min-width: 100px">Next Run</th>
    <th style="min-width: 80px">Duration</th>
    <th style="width: 110px">Actions</th>
  </tr>
</thead>
<tbody>
  @forelse($tasks as $task)
  <tr class="task-card" onclick="showTaskDetail('{{ $task['identifier'] }}')">
    <td>
      <div class="fw-semibold">
        {{ $task['label'] }}
      </div>
      <small class="text-muted">{{ $task['name'] }}</small>
      @if(isset($task['description']) && $task['description'])
      <div class="small text-muted mt-1">
        {{ \Illuminate\Support\Str::limit($task['description'], 50) }}
      </div>
      @endif
    </td>
    <td>
      <code class="small">{{ $task['schedule'] }}</code>
      <div class="small text-muted">
        {{ $task['next_run']->format('H:i d/m') }}
      </div>
    </td>
    <td>
      @if($task['last_run'])
      <span class="small">{{ \Carbon\Carbon::parse($task['last_run'])->diffForHumans() }}</span>
      <div class="small text-muted">
        {{ \Carbon\Carbon::parse($task['last_run'])->format('d/m/Y H:i') }}
      </div>
      @else
      <span class="text-muted">Never</span>
      @endif
    </td>
    <td>
      @php
      $statusClass = match($task['last_status']) {
      'success' => 'success',
      'failed' => 'danger',
      'running' => 'info',
      default => 'secondary'
      };
      @endphp
      <span class="badge bg-{{ $statusClass }} rounded-pill px-3">
        @if($task['last_status'] === 'running')
        <i class="bi bi-arrow-repeat spinner-grow spinner-grow-sm me-1"></i>
        @elseif($task['last_status'] === 'success')
        <i class="bi bi-check-circle me-1"></i>
        @elseif($task['last_status'] === 'failed')
        <i class="bi bi-x-circle me-1"></i>
        @else
        <i class="bi bi-clock me-1"></i>
        @endif
        {{ ucfirst($task['last_status']) }}
      </span>
    </td>
    <td>
      <span class="next-run-badge">{{ $task['next_run_human'] }}</span>
      <div class="small text-muted">
        {{ $task['next_run']->format('d/m/Y H:i') }}
      </div>
    </td>
    <td>
      @if($task['last_duration'])
      <span class="small">{{ number_format($task['last_duration'], 2) }}s</span>
      @else
      <span class="text-muted">-</span>
      @endif
    </td>
    <td>
      <div class="btn-group btn-group-sm" role="group" onclick="event.stopPropagation()">
        @if($task['is_command'])
        <button class="btn btn-outline-primary" onclick="runTask('{{ $task['identifier'] }}')" title="Run now">
          <i class="bi bi-play-fill"></i>
        </button>
        @else
        <button class="btn btn-outline-secondary" disabled title="Cannot run closure tasks">
          <i class="bi bi-play-fill"></i>
        </button>
        @endif
        <button class="btn btn-outline-secondary" onclick="viewLogs('{{ $task['name'] }}')" title="View logs">
          <i class="bi bi-file-text"></i>
        </button>
        <button class="btn btn-outline-{{ $task['enabled'] ? 'warning' : 'success' }}" onclick="toggleTask('{{ $task['identifier'] }}')" title="{{ $task['enabled'] ? 'Disable' : 'Enable' }}">
          <i class="bi bi-{{ $task['enabled'] ? 'pause-circle' : 'play-circle' }}"></i>
        </button>
      </div>
    </td>
  </tr>
  @empty
  <tr>
    <td colspan="7" class="text-center text-muted py-5">
      <i class="bi bi-inbox fs-1"></i>
      <p class="mt-2">
        No scheduled tasks found. Make sure you have defined tasks in Laravel's scheduler.
      </p>
    </td>
  </tr>
  @endforelse
</tbody>
</table>