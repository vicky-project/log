<table class="table table-hover align-middle schedule-table mb-0">
  <thead class="table-light">
    <th style="min-width: 180px">Cron Expression</th>
    <th style="min-width: 60px">Repeat</th>
    <th>Command / Description</th>
    <th style="min-width: 140px">Next Due</th>
    <th style="min-width: 80px">Status</th>
    <th style="min-width: 110px">Actions</th>
  </tr>
</thead>
<tbody>
  @forelse($tasks as $task)
  <tr class="task-card" onclick="showTaskDetail('{{ $task['identifier'] }}')">
    <td><span class="cron-expression">{{ $task['expression'] }}</span></td>
    <td>@if($task['repeat'])<span class="repeat-badge">{{ $task['repeat'] }}</span>@else - @endif</td>
    <td>
      <div class="command-text">
        @if($task['command'])
        {{ $task['command'] }}
        @elseif($task['description'])
        {{ $task['description'] }}
        @else
        <span class="text-muted">[Closure]</span>
        @endif
      </div>
      @if($task['has_mutex'])
      <small class="text-muted"><i class="bi bi-lock"></i> Has mutex</small>
      @endif
    </td>
    <td class="next-due" title="{{ $task['next_due']->format('Y-m-d H:i:s') }}">{{ $task['next_due_human'] }}</td>
    <td>
      @php
      $statusClass = match($task['status']) {
      'success' => 'success',
      'failed' => 'danger',
      'running' => 'info',
      default => 'secondary'
      };
      @endphp
      <span class="badge bg-{{ $statusClass }} rounded-pill px-3">
        @if($task['status'] === 'running')
        <i class="bi bi-arrow-repeat spinner-grow spinner-grow-sm me-1"></i>
        @elseif($task['status'] === 'success')
        <i class="bi bi-check-circle me-1"></i>
        @elseif($task['status'] === 'failed')
        <i class="bi bi-x-circle me-1"></i>
        @else
        <i class="bi bi-clock me-1"></i>
        @endif
        {{ ucfirst($task['status']) }}
      </span>
    </td>
    <td>
      <div class="btn-group btn-group-sm" role="group" onclick="event.stopPropagation()">
        @if($task['is_command'])
        <button class="btn btn-outline-primary" onclick="runTask('{{ $task['identifier'] }}')" title="Run now">
          <i class="bi bi-play-fill"></i>
        </button>
        @else
        <button class="btn btn-outline-secondary" disabled title="Cannot run this type of task">
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
    <td colspan="6" class="text-center text-muted py-5">
      <i class="bi bi-inbox fs-1"></i>
      <p class="mt-2">
        No scheduled tasks defined. Add tasks in <code>app/Console/Kernel.php</code> or via service providers.
      </p>
    </td>
  </tr>
  @endforelse
</tbody>
</table>