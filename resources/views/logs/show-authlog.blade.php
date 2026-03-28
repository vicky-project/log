@extends('coreui::layouts.admin')
@section('title', 'Detail User Log')

@use("Jenssegers\Agent\Agent")
@php
$agent = tap(new Agent, fn($agent) => $agent->setUserAgent($auth_log->user_agent));
$browser = $agent->platform() . ' - ' . $agent->browser();

// Parse location data (already casted to array by model)
$location = $auth_log->location ?? [];
$isLocationDefault = $location['default'] ?? true;
@endphp

@section('content')
<!-- Tombol Kembali & Judul -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <a href="{{ route('admin.logs.auth') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Log
  </a>
  <h4 class="mb-0">Detail Riwayat Login</h4>
  <div></div>
  <!-- Placeholder untuk menjaga keseimbangan flex -->
</div>

<div class="row">
  <!-- Kolom Informasi Utama -->
  <div class="col-lg-6 mb-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="card-title mb-0">
          <i class="bi bi-person-circle me-2 text-primary"></i> Informasi Pengguna
        </h5>
      </div>
      <div class="card-body">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-person me-2 text-secondary"></i> Nama</span>
            <span class="text-muted">{{ $auth_log->authenticatable->name ?? '-' }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-envelope me-2 text-secondary"></i> Email</span>
            <span class="text-muted">{{ $auth_log->authenticatable->email ?? '-' }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-hdd-network me-2 text-secondary"></i> IP Address</span>
            <code class="text-muted">{{ $auth_log->ip_address ?? '-' }}</code>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-browser-chrome me-2 text-secondary"></i> Browser / Platform</span>
            <span class="text-muted">{{ $browser }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-device-ssd me-2 text-secondary"></i> Nama Perangkat</span>
            <span class="text-muted">{{ $auth_log->device_name ?? '-' }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-fingerprint me-2 text-secondary"></i> Device ID</span>
            <span class="text-muted font-monospace small">{{ $auth_log->device_id ?? '-' }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Kolom Waktu & Status -->
  <div class="col-lg-6 mb-4">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="card-title mb-0">
          <i class="bi bi-clock-history me-2 text-primary"></i> Waktu & Status
        </h5>
      </div>
      <div class="card-body">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-box-arrow-in-right me-2 text-secondary"></i> Login Pada</span>
            <span class="text-muted">{{ $auth_log->login_at ? $auth_log->login_at->format('d-m-Y H:i:s') : '-' }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-box-arrow-right me-2 text-secondary"></i> Logout Pada</span>
            <span class="text-muted">{{ $auth_log->logout_at ? $auth_log->logout_at->format('d-m-Y H:i:s') : 'Masih aktif' }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-activity me-2 text-secondary"></i> Aktivitas Terakhir</span>
            <span class="text-muted">{{ $auth_log->last_activity_at ? $auth_log->last_activity_at->format('d-m-Y H:i:s') : '-' }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-check-circle me-2 text-secondary"></i> Login Berhasil</span>
            <span>
              @if($auth_log->login_successful)
              <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i> Ya</span>
              @else
              <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i> Tidak</span>
              @endif
            </span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-shield-check me-2 text-secondary"></i> Perangkat Terpercaya</span>
            <span>
              @if($auth_log->is_trusted)
              <span class="badge bg-info text-dark"><i class="bi bi-check-lg me-1"></i> Ya</span>
              @else
              <span class="badge bg-secondary"><i class="bi bi-x-lg me-1"></i> Tidak</span>
              @endif
            </span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-flag me-2 text-secondary"></i> Dicurigai</span>
            <span>
              @if($auth_log->is_suspicious)
              <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i> Ya</span>
              @else
              <span class="badge bg-success"><i class="bi bi-shield-check me-1"></i> Tidak</span>
              @endif
            </span>
          </li>
          @if($auth_log->suspicious_reason)
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-chat-text me-2 text-secondary"></i> Alasan Dicurigai</span>
            <span class="text-muted small">{{ $auth_log->suspicious_reason }}</span>
          </li>
          @endif
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span class="fw-semibold"><i class="bi bi-trash me-2 text-secondary"></i> Dihapus oleh User</span>
            <span>
              @if($auth_log->cleared_by_user)
              <span class="badge bg-danger"><i class="bi bi-check-lg me-1"></i> Ya</span>
              @else
              <span class="badge bg-secondary"><i class="bi bi-x-lg me-1"></i> Tidak</span>
              @endif
            </span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Kolom Lokasi (Lebar Penuh) -->
<div class="row">
  <div class="col-12 mb-4">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="card-title mb-0">
          <i class="bi bi-geo-alt me-2 text-primary"></i> Informasi Lokasi
        </h5>
      </div>
      <div class="card-body">
        @if(!empty($location))
        @if(!$isLocationDefault)
        {{-- Data lokasi lengkap dari IPGeolocation --}}
        <div class="row g-3">
          <div class="col-md-6">
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-globe me-1"></i> Negara:</span>
              <span class="text-muted ms-2">{{ $location['country_name'] ?? $location['country'] ?? '-' }}</span>
              @if(isset($location['country_flag']))
              <img src="{{ $location['country_flag'] }}" alt="flag" width="20" class="ms-2">
              @endif
            </div>
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-building me-1"></i> Provinsi / Kota:</span>
              <span class="text-muted ms-2">
                {{ ($location['state_prov'] ?? $location['state'] ?? '') . (isset($location['city']) ? ', ' . $location['city'] : '') }}
              </span>
            </div>
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-pin-map me-1"></i> Kode Pos:</span>
              <span class="text-muted ms-2">{{ $location['zipcode'] ?? $location['postal_code'] ?? '-' }}</span>
            </div>
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-arrows-move me-1"></i> Koordinat:</span>
              <span class="text-muted ms-2">
                @if(isset($location['latitude']) && isset($location['longitude']))
                {{ $location['latitude'] }}, {{ $location['longitude'] }}
                <a href="https://www.google.com/maps?q={{ $location['latitude'] }},{{ $location['longitude'] }}" target="_blank" class="ms-2 text-decoration-none">
                  <i class="bi bi-box-arrow-up-right"></i>
                </a>
                @elseif(isset($location['lat']) && isset($location['lon']))
                {{ $location['lat'] }}, {{ $location['lon'] }}
                <a href="https://www.google.com/maps?q={{ $location['lat'] }},{{ $location['lon'] }}" target="_blank" class="ms-2 text-decoration-none">
                  <i class="bi bi-box-arrow-up-right"></i>
                </a>
                @else
                -
                @endif
              </span>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-wifi me-1"></i> ISP / Koneksi:</span>
              <span class="text-muted ms-2">{{ $location['isp'] ?? '-' }} ({{ $location['connection_type'] ?? '-' }})</span>
            </div>
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-currency-dollar me-1"></i> Mata Uang:</span>
              <span class="text-muted ms-2">
                @if(isset($location['currency']))
                {{ $location['currency']['name'] ?? '-' }} ({{ $location['currency']['symbol'] ?? '' }})
                @else
                -
                @endif
              </span>
            </div>
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-clock me-1"></i> Zona Waktu:</span>
              <span class="text-muted ms-2">
                @if(isset($location['time_zone']))
                {{ $location['time_zone']['name'] ?? '-' }} (UTC {{ $location['time_zone']['offset'] ?? 0 }})
                @else
                -
                @endif
              </span>
            </div>
          </div>
        </div>
        @else
        {{-- Data lokasi default (minimal) --}}
        <div class="row">
          <div class="col-md-6">
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-globe me-1"></i> Negara:</span>
              <span class="text-muted ms-2">{{ $location['country'] ?? '-' }}</span>
              @if(isset($location['country_flag']))
              <img src="{{ $location['country_flag'] }}" alt="flag" width="20" class="ms-2">
              @endif
            </div>
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-building me-1"></i> Kota:</span>
              <span class="text-muted ms-2">{{ $location['city'] ?? '-' }}</span>
            </div>
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-pin-map me-1"></i> Provinsi:</span>
              <span class="text-muted ms-2">{{ $location['state'] ?? $location['state_name'] ?? '-' }}</span>
            </div>
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-arrows-move me-1"></i> Koordinat:</span>
              <span class="text-muted ms-2">
                @if(isset($location['lat']) && isset($location['lon']))
                {{ $location['lat'] }}, {{ $location['lon'] }}
                <a href="https://www.google.com/maps?q={{ $location['lat'] }},{{ $location['lon'] }}" target="_blank" class="ms-2 text-decoration-none">
                  <i class="bi bi-box-arrow-up-right"></i>
                </a>
                @else
                -
                @endif
              </span>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-currency-dollar me-1"></i> Mata Uang:</span>
              <span class="text-muted ms-2">{{ $location['currency'] ?? '-' }}</span>
            </div>
            <div class="mb-2">
              <span class="fw-semibold"><i class="bi bi-clock me-1"></i> Zona Waktu:</span>
              <span class="text-muted ms-2">{{ $location['timezone'] ?? '-' }}</span>
            </div>
          </div>
        </div>
        @endif
        @else
        <p class="text-muted mb-0">
          Tidak ada informasi lokasi tersedia.
        </p>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Catatan Keterangan (Opsional) -->
<div class="row">
  <div class="col-12">
    <div class="alert alert-light border d-flex align-items-center" role="alert">
      <i class="bi bi-info-circle-fill me-2 text-primary"></i>
      <small>Informasi perangkat dan lokasi diambil saat login. Data lokasi yang ditandai "default" menunjukkan informasi geolokasi terbatas.</small>
    </div>
  </div>
</div>
@endsection