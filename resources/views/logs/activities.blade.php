@extends('coreui::layouts.admin')
@section('title', 'Aktivitas Pengguna')

@push('styles')
<style>
  .activity-properties {
    max-width: 250px;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 0.85rem;
  }
  .table-activity td {
    vertical-align: middle;
  }
  @media (max-width: 768px) {
    .table-activity th, .table-activity td {
      white-space: normal;
    }
  }
</style>
@endpush

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-0 pt-4 pb-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
          <div>
            <h5 class="card-title mb-0">
              <i class="bi bi-activity me-2 text-primary"></i> Aktivitas Pengguna
            </h5>
            <p class="text-muted small mt-1 mb-0">
              Semua aktivitas yang tercatat di sistem
            </p>
          </div>
          <div class="mt-2 mt-sm-0">
            <span class="badge bg-light text-dark">{{ $activities->total() }} total</span>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle table-activity">
            <thead class="table-light">
              <tr>
                <th style="min-width: 140px;">Waktu</th>
                <th style="min-width: 180px;">Pelaku</th>
                <th style="min-width: 200px;">Aksi</th>
                <th style="min-width: 200px;">Subject</th>
                <th>Detail Perubahan</th>
              </tr>
            </thead>
            <tbody>
              @forelse($activities as $activity)
              <tr>
                <td class="small text-nowrap">
                  {{ $activity->created_at->format('d/m/Y H:i:s') }}
                </td>
                <td>
                  @if($activity->causer)
                  @php
                  $causer = $activity->causer;
                  $causerName = '';
                  $causerLink = '';
                  if ($causer instanceof \Modules\Users\Models\User) {
                  $causerName = $causer->name . ' (' . $causer->email . ')';
                  $causerLink = route('admin.users.show', $causer->id);
                  } elseif ($causer instanceof \Modules\Telegram\Models\TelegramUser) {
                  $causerName = $causer->first_name . ' ' . $causer->last_name . ' (@' . $causer->username . ')';
                  $causerLink = route('admin.telegram.show', $causer->id);
                  } else {
                  $causerName = class_basename($causer) . ' #' . $causer->id;
                  }
                  @endphp
                  @if($causerLink)
                  <a href="{{ $causerLink }}" class="text-decoration-none">
                    <i class="bi bi-person-circle me-1"></i> {{ $causerName }}
                  </a>
                  @else
                  <i class="bi bi-person-circle me-1"></i> {{ $causerName }}
                  @endif
                  @else
                  <span class="text-muted"><i class="bi bi-question-circle me-1"></i> Sistem / Tamu</span>
                  @endif
                </td>
                <td>
                  <div class="fw-semibold">
                    {{ $activity->description }}
                  </div>
                  @if($activity->log_name)
                  <div class="small text-muted">
                    Log: {{ $activity->log_name }}
                  </div>
                  @endif
                </td>
                <td>
                  @if($activity->subject)
                  @php
                  $subject = $activity->subject;
                  $subjectName = '';
                  $subjectLink = '';
                  if ($subject instanceof \Modules\Users\Models\User) {
                  $subjectName = $subject->name . ' (' . $subject->email . ')';
                  $subjectLink = route('admin.users.show', $subject->id);
                  } elseif ($subject instanceof \Modules\Telegram\Models\TelegramUser) {
                  $subjectName = $subject->first_name . ' ' . $subject->last_name . ' (@' . $subject->username . ')';
                  $subjectLink = route('admin.telegram.show', $subject->id);
                  } else {
                  $subjectName = class_basename($subject) . ' #' . $subject->id;
                  }
                  @endphp
                  @if($subjectLink)
                  <a href="{{ $subjectLink }}" class="text-decoration-none">
                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ $subjectName }}
                  </a>
                  @else
                  <i class="bi bi-box-arrow-up-right me-1"></i> {{ $subjectName }}
                  @endif
                  @else
                  <span class="text-muted">-</span>
                  @endif
                </td>
                <td class="activity-properties">
                  @if($activity->properties && $activity->properties->count())
                  <pre class="small mb-0 text-muted" style="font-size: 0.75rem;">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT) }}</pre>
                  @else
                  <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-5">
                  <i class="bi bi-inbox fs-1"></i>
                  <p class="mt-2">
                    Belum ada aktivitas tercatat.
                  </p>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($activities->hasPages())
        <div class="d-flex justify-content-end mt-3">
          {{ $activities->links() }}
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection