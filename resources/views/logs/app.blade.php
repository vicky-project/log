@extends('coreui::layouts.admin')
@section('title', 'Log Viewer')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-12 col-lg-10">
    <div class="card shadow">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="bi bi-journal-code me-2"></i>Log Viewer</h4>
      </div>
      <div class="card-body">
        <!-- Filter bar -->
        <div class="row mb-3 g-2">
          <div class="col-12 col-sm-6">
            <div class="input-group">
              <label class="input-group-text" for="dateSelect">Tanggal</label>
              <select id="dateSelect" class="form-select">
                <option value="">Memuat...</option>
              </select>
            </div>
          </div>
          <div class="col-12 col-sm-3">
            <div class="input-group">
              <label class="input-group-text" for="levelSelect">Tipe</label>
              <select id="levelSelect" class="form-select">
                <option value="">Semua</option>
                <option value="DEBUG">DEBUG</option>
                <option value="INFO">INFO</option>
                <option value="NOTICE">NOTICE</option>
                <option value="WARNING">WARNING</option>
                <option value="ERROR">ERROR</option>
                <option value="CRITICAL">CRITICAL</option>
                <option value="ALERT">ALERT</option>
                <option value="EMERGENCY">EMERGENCY</option>
              </select>
            </div>
          </div>
          <div class="col-12 col-sm-3">
            <div class="input-group">
              <label class="input-group-text" for="envSelect">Env</label>
              <select id="envSelect" class="form-select">
                <option value="">Semua</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Info jumlah log -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
          <span id="resultCount" class="text-muted"></span>
          <button id="refreshBtn" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-repeat me-1"></i>Refresh
          </button>
        </div>

        <!-- Loading spinner -->
        <div id="loadingSpinner" class="text-center py-5" style="display: none;">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <!-- Log entries container with responsive table -->
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th style="min-width: 100px;">Waktu</th>
                <th style="min-width: 80px;">Env</th>
                <th style="min-width: 80px;">Tipe</th>
                <th style="min-width: 500px;">Pesan</th>
              </tr>
            </thead>
            <tbody id="logsTableBody">
              <tr><td colspan="4" class="text-center text-muted">Pilih tanggal untuk melihat log.</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination controls -->
        <nav aria-label="Log pagination" class="mt-3">
          <ul class="pagination justify-content-center" id="paginationControls"></ul>
        </nav>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Global state
  let currentDate = '';
  let allLogs = []; // logs mentah dari API
  let filteredLogs = []; // setelah filter level & env
  let currentPage = 1;
  const rowsPerPage = 50;

  // DOM elements
  const dateSelect = document.getElementById('dateSelect');
  const levelSelect = document.getElementById('levelSelect');
  const envSelect = document.getElementById('envSelect');
  const logsTableBody = document.getElementById('logsTableBody');
  const paginationControls = document.getElementById('paginationControls');
  const loadingSpinner = document.getElementById('loadingSpinner');
  const resultCount = document.getElementById('resultCount');
  const refreshBtn = document.getElementById('refreshBtn');

  // Base API URL
  const apiUrl = 'https://vickyserver.my.id/app/admin/api/log-reader';

  // -----------------------------------------------------------------
  // 1. Ambil daftar tanggal yang tersedia (tanpa parameter date)
  // -----------------------------------------------------------------
  async function fetchAvailableDates() {
    loadingSpinner.style.display = 'block';
    // Bersihkan tabel sebelum loading
    logsTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Memuat daftar tanggal...</td></tr>';
    try {
      const response = await fetch(apiUrl);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const json = await response.json();
      if (!json.success) throw new Error(json.message || 'Failed to fetch');
      const data = json.data;
      const dates = data.available_log_dates || [];
      populateDateSelect(dates);
      // Jika tanggal hari ini tersedia, pilih langsung
      if (dates.includes(data.date)) {
        dateSelect.value = data.date;
        await loadLogsForDate(data.date);
      } else if (dates.length > 0) {
        dateSelect.value = dates[0];
        await loadLogsForDate(dates[0]);
      } else {
        dateSelect.innerHTML = '<option value="">Tidak ada log tersedia</option>';
        logsTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Tidak ada log untuk tanggal yang tersedia.</td></tr>';
      }
    } catch (error) {
      console.error('Error fetching dates:', error);
      dateSelect.innerHTML = '<option value="">Gagal memuat tanggal</option>';
      logsTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Gagal memuat data log. Periksa koneksi atau autentikasi.</td></tr>';
    } finally {
      loadingSpinner.style.display = 'none';
    }
  }

  // -----------------------------------------------------------------
  // 2. Isi dropdown tanggal
  // -----------------------------------------------------------------
  function populateDateSelect(dates) {
    dateSelect.innerHTML = '<option value="">Pilih tanggal</option>';
    dates.forEach(date => {
    const option = document.createElement('option');
    option.value = date;
    option.textContent = date;
    dateSelect.appendChild(option);
    });
  }

  // -----------------------------------------------------------------
  // 3. Muat log untuk tanggal tertentu
  // -----------------------------------------------------------------
  async function loadLogsForDate(date) {
    // Bersihkan data lama & tampilkan loading
    allLogs = [];
    filteredLogs = [];
    currentPage = 1;
    logsTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Memuat log...</td></tr>';
    paginationControls.innerHTML = '';
    resultCount.textContent = '';
    loadingSpinner.style.display = 'block';

    try {
      const url = `${apiUrl}?date=${date}`;
      const response = await fetch(url);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const json = await response.json();
      if (!json.success) throw new Error(json.message || 'Failed to fetch');
      const logs = json.data.logs || [];
      allLogs = logs;
      // Perbarui dropdown environment berdasarkan data
      updateEnvFilter(logs);
      // Terapkan filter (awalnya tanpa filter)
      applyFilters();
    } catch (error) {
      console.error('Error loading logs:', error);
      logsTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Gagal memuat log untuk tanggal ini.</td></tr>';
      resultCount.textContent = '';
      paginationControls.innerHTML = '';
      envSelect.innerHTML = '<option value="">Semua</option>';
    } finally {
      loadingSpinner.style.display = 'none';
    }
  }

  // -----------------------------------------------------------------
  // 4. Update dropdown environment berdasarkan data log
  // -----------------------------------------------------------------
  function updateEnvFilter(logs) {
    const envSet = new Set();
    logs.forEach(log => {
    if (log.env) envSet.add(log.env);
    });
    const envs = Array.from(envSet).sort();
    envSelect.innerHTML = '<option value="">Semua</option>';
    envs.forEach(env => {
    const option = document.createElement('option');
    option.value = env;
    option.textContent = env;
    envSelect.appendChild(option);
    });
  }

  // -----------------------------------------------------------------
  // 5. Filter berdasarkan level dan env
  // -----------------------------------------------------------------
  function applyFilters() {
    const selectedLevel = levelSelect.value;
    const selectedEnv = envSelect.value;

    filteredLogs = allLogs.filter(log => {
    const matchesLevel = selectedLevel === '' || log.type === selectedLevel;
    const matchesEnv = selectedEnv === '' || log.env === selectedEnv;
    return matchesLevel && matchesEnv;
    });

    resultCount.textContent = `Menampilkan ${filteredLogs.length} dari ${allLogs.length} log`;
    currentPage = 1;
    renderCurrentPage();
    renderPaginationControls();
  }

  function extractTime(timestampStr) {
    if (!timestampStr) return '-';
    // Format yang diharapkan: "YYYY-MM-DD HH:MM:SS" atau "YYYY-MM-DD HH:MM:SS.micro"
    const parts = timestampStr.split(' ');
    if (parts.length < 2) return '-';
    return parts[1];
  }

  // -----------------------------------------------------------------
  // 6. Render halaman saat ini
  // -----------------------------------------------------------------
  function renderCurrentPage() {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const pageLogs = filteredLogs.slice(start, end);

    if (pageLogs.length === 0) {
      logsTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Tidak ada log yang cocok.</td></tr>';
      return;
    }

    logsTableBody.innerHTML = pageLogs.map(log => {
    const timeDisplay = log.timestamp ? extractTime(log.timestamp) : '-';
    return `
    <tr>
    <td class="text-nowrap">${escapeHtml(timeDisplay)}</td>
    <td><span class="badge bg-secondary">${escapeHtml(log.env || '-')}</span></td>
    <td><span class="badge bg-${getLevelBadgeClass(log.type)}">${escapeHtml(log.type)}</span></td>
    <td class="text-break">${escapeHtml(log.message)}</td>
    </tr>
    `;
    }).join('');
  }

  // Helper untuk badge color
  function getLevelBadgeClass(level) {
    switch (level) {
      case 'DEBUG': return 'secondary';
      case 'INFO': return 'info';
      case 'NOTICE': return 'light';
      case 'WARNING': return 'warning';
      case 'ERROR': return 'danger';
      case 'CRITICAL': return 'danger';
      case 'ALERT': return 'danger';
      case 'EMERGENCY': return 'danger';
      default: return 'secondary';
    }
  }

  // -----------------------------------------------------------------
  // 7. Pagination controls
  // -----------------------------------------------------------------
  function renderPaginationControls() {
    const totalPages = Math.ceil(filteredLogs.length / rowsPerPage);
    if (totalPages <= 1) {
      paginationControls.innerHTML = '';
      return;
    }

    let html = '';
    // Previous button
    html += `<li class="page-item ${currentPage === 1 ? 'disabled': ''}">
    <a class="page-link" href="#" data-page="${currentPage - 1}">«</a>
    </li>`;
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
        html += `<li class="page-item ${i === currentPage ? 'active': ''}">
        <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>`;
      } else if (i === currentPage - 3 || i === currentPage + 3) {
        html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
      }
    }
    // Next button
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled': ''}">
    <a class="page-link" href="#" data-page="${currentPage + 1}">»</a>
    </li>`;

    paginationControls.innerHTML = html;

    // Attach event listeners
    document.querySelectorAll('#paginationControls .page-link').forEach(link => {
    link.addEventListener('click', (e) => {
    e.preventDefault();
    const page = parseInt(link.dataset.page);
    if (page && page !== currentPage && page >= 1 && page <= totalPages) {
    currentPage = page;
    renderCurrentPage();
    renderPaginationControls();
    window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    });
    });
  }

  // -----------------------------------------------------------------
  // 8. Escape HTML
  // -----------------------------------------------------------------
  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // -----------------------------------------------------------------
  // 9. Event listeners
  // -----------------------------------------------------------------
  dateSelect.addEventListener('change', async () => {
  const selected = dateSelect.value;
  if (selected) {
  await loadLogsForDate(selected);
  } else {
  // Reset tampilan
  allLogs = [];
  filteredLogs = [];
  logsTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Pilih tanggal untuk melihat log.</td></tr>';
  resultCount.textContent = '';
  paginationControls.innerHTML = '';
  envSelect.innerHTML = '<option value="">Semua</option>';
  }
  });

  levelSelect.addEventListener('change', () => {
  applyFilters();
  });

  envSelect.addEventListener('change', () => {
  applyFilters();
  });

  refreshBtn.addEventListener('click', async () => {
  if (dateSelect.value) {
  await loadLogsForDate(dateSelect.value);
  } else {
  await fetchAvailableDates();
  }
  });

  // -----------------------------------------------------------------
  // 10. Inisialisasi
  // -----------------------------------------------------------------
  fetchAvailableDates();
</script>
@endpush

@push('styles')
<style>
  /* Responsif untuk layar kecil */
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .badge {
    font-weight: normal;
  }
  /* Teks pesan log bisa wrap */
  .text-break {
    word-break: break-word;
    white-space: normal;
  }
  /* Atur min-width kolom agar tidak terlalu sempit di mobile */
  @media (max-width: 576px) {
    .table th, .table td {
      white-space: normal;
    }
  }
</style>
@endpush