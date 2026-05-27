@extends('coreui::layouts.admin')
@section('title', 'User Authentication Log')
@use('Modules\Log\Constants\Permission')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-0 pt-4 pb-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
          <div>
            <h5 class="card-title mb-0">
              <i class="bi bi-journal-text me-2 text-primary"></i> Riwayat Login Pengguna
            </h5>
            <p class="text-muted small mt-1 mb-0">
              Catatan aktivitas login seluruh pengguna
            </p>
          </div>
          <div class="mt-2 mt-sm-0">
            <span class="badge bg-light text-dark">
              @if(method_exists($logs, 'total'))
              {{ $logs->total() ?? count($logs) }} total
              @else
              0 total
              @endif
            </span>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col" style="min-width: 180px;">Pengguna</th>
                <th scope="col" style="min-width: 140px;">Alamat IP</th>
                <th scope="col" style="min-width: 160px;">Perangkat</th>
                <th scope="col" style="min-width: 150px;">Waktu Login</th>
                <th scope="col" style="min-width: 100px;">Status</th>
                <th scope="col" style="min-width: 140px;">Lokasi</th>
                <th scope="col" style="width: 60px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($logs as $log)
              <tr>
                <td>
                  <div class="fw-semibold text-truncate" style="max-width: 180px;" title="{{ $log->authenticatable->name ?? 'Guest' }}">
                    {{ $log->authenticatable->name ?? 'Guest' }}
                  </div>
                  <div class="small text-muted text-truncate" style="max-width: 180px;" title="{{ $log->authenticatable->email ?? '-' }}">
                    {{ $log->authenticatable->email ?? '-' }}
                  </div>
                </td>
                <td>
                  <code class="small text-truncate d-block" style="max-width: 140px;" title="{{ $log->ip_address ?? '-' }}">
                    {{ $log->ip_address ?? '-' }}
                  </code>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="small text-truncate" style="max-width: 160px;" title="{{ $log->device_name ?? ($log->user_agent ?: '-') }}">
                      {{ $log->device_name ?? ($log->user_agent ? Str::limit($log->user_agent, 40) : '-') }}
                    </span>
                    @if($log->device_name && $log->user_agent)
                    <span class="small text-muted text-truncate" style="max-width: 160px;" title="{{ $log->user_agent }}">
                      {{ Str::limit($log->user_agent, 40) }}
                    </span>
                    @endif
                  </div>
                </td>
                <td>
                  <span class="small text-nowrap">
                    {{ $log->login_at ? $log->login_at->format('d/m/Y H:i') : '-' }}
                  </span>
                </td>
                <td>
                  @if($log->login_successful)
                  <span class="badge bg-success rounded-pill px-3">
                    <i class="bi bi-check-circle me-1"></i> Berhasil
                  </span>
                  @else
                  <span class="badge bg-danger rounded-pill px-3">
                    <i class="bi bi-x-circle me-1"></i> Gagal
                  </span>
                  @endif
                </td>
                <td>
                  @php
                  $location = is_array($log->location) ? $log->location : (json_decode($log->location, true) ?: []);
                  $city = $location['city'] ?? ($location['city'] ?? null);
                  $country = $location['country_name'] ?? $location['country'] ?? null;
                  $locationText = trim(($city ? $city . ', ' : '') . ($country ?? ''));
                  @endphp
                  <span class="small text-muted text-truncate d-block" style="max-width: 140px;" title="{{ $locationText ?: '-' }}">
                    {{ $locationText ?: '-' }}
                  </span>
                </td>
                <td class="text-center">
                  @can(Permission::VIEW_AUTH_LOGS)
                  <a href="{{ route('admin.logs.auth.show', $log->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat detail">
                    <i class="bi bi-eye"></i>
                  </a>
                  @endcan
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  <i class="bi bi-inbox fs-1"></i>
                  <p class="mt-2">
                    Belum ada data riwayat login.
                  </p>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if(method_exists($logs, 'links'))
        <div class="d-flex justify-content-end mt-3">
          {{ $logs->links() }}
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection