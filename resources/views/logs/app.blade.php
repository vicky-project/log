@extends('coreui::layouts.mini-app')
@section('title', 'Log Viewer')

@section('content')
<div class="container py-3">
  <div class="row justify-content-center mb-3">
    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('telegram.home') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
      </div>
    </div>
  </div>
  <div class="row justify-content-center">
    <div class="col-md-12 col-lg-10">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><i class="bi bi-journal-code me-2"></i>Log Viewer</h4>
        </div>
        <div class="card-body">
          <!-- Filter bar -->
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="input-group">
                <label class="input-group-text" for="dateSelect">Tanggal</label>
                <select id="dateSelect" class="form-select">
                  <option value="">Memuat...</option>
                </select>
                <label class="input-group-text ms-2" for="levelSelect">Tipe</label>
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
            <div class="col-md-6 text-end">
              <span id="resultCount" class="text-muted"></span>
            </div>
          </div>

          <!-- Loading spinner -->
          <div id="loadingSpinner" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <!-- Log entries container -->
          <div id="logsContainer">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th style="width: 20%">Waktu</th>
                  <th style="width: 10%">Tipe</th>
                  <th>Pesan</th>
                </tr>
              </thead>
              <tbody id="logsTableBody">
                <tr><td colspan="3" class="text-center text-muted">Pilih tanggal untuk melihat log.</td></tr>
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
</div>
@endsection

@push('scripts')
<script>
  // Global state
  let currentDate = '';
  let allLogs = [];
  let filteredLogs = [];
  let currentPage = 1;
  const rowsPerPage = 50;

  // DOM elements
  const dateSelect = document.getElementById('dateSelect');
  const levelSelect = document.getElementById('levelSelect');
  const logsTableBody = document.getElementById('logsTableBody');
  const paginationControls = document.getElementById('paginationControls');
  const loadingSpinner = document.getElementById('loadingSpinner');
  const resultCount = document.getElementById('resultCount');

  // Base API URL
  const apiUrl = 'https://vickyserver.my.id/app/admin/api/log-reader';

  // Fetch available dates (first request without date)
  async function fetchAvailableDates() {
    loadingSpinner.style.display = 'block';
    try {
      const response = await fetch(apiUrl);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const json = await response.json();
      if (!json.success) throw new Error(json.message || 'Failed to fetch');
      const data = json.data;
      const dates = data.available_log_dates || [];
      populateDateSelect(dates);
      // If today's date is available, select it and load logs
      if (dates.includes(data.date)) {
        dateSelect.value = data.date;
        loadLogsForDate(data.date);
      } else if (dates.length > 0) {
        dateSelect.value = dates[0];
        loadLogsForDate(dates[0]);
      } else {
        dateSelect.innerHTML = '<option value="">Tidak ada log tersedia</option>';
      }
    } catch (error) {
      console.error('Error fetching dates:', error);
      dateSelect.innerHTML = '<option value="">Gagal memuat tanggal</option>';
      logsTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Gagal memuat data log.</td></tr>';
    } finally {
      loadingSpinner.style.display = 'none';
    }
  }

  // Populate dropdown with available dates
  function populateDateSelect(dates) {
    dateSelect.innerHTML = '<option value="">Pilih tanggal</option>';
    dates.forEach(date => {
    const option = document.createElement('option');
    option.value = date;
    option.textContent = date;
    dateSelect.appendChild(option);
    });
  }

  // Load logs for a specific date
  async function loadLogsForDate(date) {
    loadingSpinner.style.display = 'block';
    try {
      const url = `${apiUrl}?date=${date}`;
      const response = await fetch(url);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const json = await response.json();
      if (!json.success) throw new Error(json.message || 'Failed to fetch');
      const logs = json.data.logs || [];
      allLogs = logs;
      applyFilters(); // This will trigger filtering and rendering
    } catch (error) {
      console.error('Error loading logs:', error);
      logsTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Gagal memuat log untuk tanggal ini.</td></tr>';
      resultCount.textContent = '';
      paginationControls.innerHTML = '';
    } finally {
      loadingSpinner.style.display = 'none';
    }
  }

  // Filter logs based on selected level
  function applyFilters() {
    const selectedLevel = levelSelect.value;
    filteredLogs = allLogs.filter(log => {
    return selectedLevel === '' || log.type === selectedLevel;
    });

    resultCount.textContent = `Menampilkan ${filteredLogs.length} dari ${allLogs.length} log`;
    currentPage = 1;
    renderCurrentPage();
    renderPaginationControls();
  }

  // Render current page of logs
  function renderCurrentPage() {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const pageLogs = filteredLogs.slice(start, end);

    if (pageLogs.length === 0) {
      logsTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Tidak ada log yang cocok.</td></tr>';
      return;
    }

    logsTableBody.innerHTML = pageLogs.map(log => `
    <tr>
    <td class="text-nowrap">${escapeHtml(log.timestamp)}</td>
    <td><span class="badge bg-${getLevelBadgeClass(log.type)}">${escapeHtml(log.type)}</span></td>
    <td>${escapeHtml(log.message)}</td>
    </tr>
    `).join('');
  }

  // Helper for badge color
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

  // Pagination controls
  function renderPaginationControls() {
    const totalPages = Math.ceil(filteredLogs.length / rowsPerPage);
    if (totalPages <= 1) {
      paginationControls.innerHTML = '';
      return;
    }

    let html = '';
    // Previous
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
    // Next
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

  // Escape HTML to prevent XSS
  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Event listeners
  dateSelect.addEventListener('change', () => {
  const selected = dateSelect.value;
  if (selected) {
  loadLogsForDate(selected);
  } else {
  // Reset view
  allLogs = [];
  filteredLogs = [];
  logsTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Pilih tanggal untuk melihat log.</td></tr>';
  resultCount.textContent = '';
  paginationControls.innerHTML = '';
  }
  });

  levelSelect.addEventListener('change', () => {
  applyFilters();
  });

  // Initial load
  fetchAvailableDates();
</script>
@endpush

@push('styles')
<style>
  /* Tema Telegram (sudah diatur oleh layout) */
  .table th, .table td {
    border-color: var(--tg-theme-section-separator-color);
  }
  .badge {
    font-weight: normal;
  }
</style>
@endpush