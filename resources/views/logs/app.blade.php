@extends('coreui::layouts.mini-app')
@section('title', 'Log Viewer')

@section('content')
<div class="container py-3">
  <div class="row justify-content-center">
    <div class="col-md-12">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><i class="bi bi-journal-code me-2"></i>Log Viewer</h4>
        </div>
        <div class="card-body">
          <!-- Filter bar -->
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="input-group">
                <label class="input-group-text" for="dateFilter">Tanggal</label>
                <select id="dateFilter" class="form-select">
                  <option value="">Semua</option>
                </select>

                <label class="input-group-text ms-2" for="levelFilter">Tipe</label>
                <select id="levelFilter" class="form-select">
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

                <button id="resetBtn" class="btn btn-outline-secondary ms-2">Reset</button>
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
                  <th style="width: 20%">Tanggal</th>
                  <th style="width: 10%">Tipe</th>
                  <th>Pesan</th>
                </tr>
              </thead>
              <tbody id="logsTableBody">
                <tr><td colspan="3" class="text-center text-muted">Belum ada data</td></tr>
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
  // Global data
  let allLogs = [];
  let filteredLogs = [];
  let currentPage = 1;
  const rowsPerPage = 50;

  // DOM elements
  const dateFilter = document.getElementById('dateFilter');
  const levelFilter = document.getElementById('levelFilter');
  const resetBtn = document.getElementById('resetBtn');
  const logsTableBody = document.getElementById('logsTableBody');
  const paginationControls = document.getElementById('paginationControls');
  const loadingSpinner = document.getElementById('loadingSpinner');
  const resultCount = document.getElementById('resultCount');

  // Fetch data dari endpoint
  async function fetchLogs() {
    loadingSpinner.style.display = 'block';
    try {
      const response = await fetch('https://vickyserver.my.id/app/admin/api/log-reader', {
        headers: {
          'X-Telegram-Init-Data': window.Telegram?.WebApp?.initData || ''
        }
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      // Pastikan data berupa array
      allLogs = Array.isArray(data) ? data: [];
      // Urutkan berdasarkan tanggal descending (terbaru di atas)
      allLogs.sort((a, b) => new Date(b.date) - new Date(a.date));
      populateDateFilter();
      applyFilters();
    } catch (error) {
      console.error('Error fetching logs:', error);
      logsTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Gagal memuat data log. Periksa koneksi atau autentikasi.</td></tr>';
    } finally {
      loadingSpinner.style.display = 'none';
    }
  }

  // Isi dropdown tanggal dengan nilai unik dari data
  function populateDateFilter() {
    const dates = [...new Set(allLogs.map(log => log.date?.split(' ')[0] || ''))].filter(d => d).sort().reverse();
    dateFilter.innerHTML = '<option value="">Semua</option>' +
    dates.map(d => `<option value="${d}">${d}</option>`).join('');
  }

  // Filter berdasarkan tanggal dan level
  function applyFilters() {
    const selectedDate = dateFilter.value;
    const selectedLevel = levelFilter.value;

    filteredLogs = allLogs.filter(log => {
    const logDate = log.date?.split(' ')[0] || '';
    const matchesDate = !selectedDate || logDate === selectedDate;
    const matchesLevel = !selectedLevel || log.level === selectedLevel;
    return matchesDate && matchesLevel;
    });

    resultCount.textContent = `Menampilkan ${filteredLogs.length} dari ${allLogs.length} log`;
    currentPage = 1;
    renderCurrentPage();
    renderPaginationControls();
  }

  // Render tabel untuk halaman saat ini
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
    <td class="text-nowrap">${escapeHtml(log.date || '')}</td>
    <td><span class="badge bg-${getLevelBadgeClass(log.level)}">${escapeHtml(log.level || '')}</span></td>
    <td>${escapeHtml(log.message || '')}</td>
    </tr>
    `).join('');
  }

  // Helper untuk warna badge
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

  // Render pagination
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

    // Attach event listeners to pagination links
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

  // Helper untuk menghindari XSS
  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Event listeners untuk filter
  dateFilter.addEventListener('change', () => applyFilters());
  levelFilter.addEventListener('change', () => applyFilters());
  resetBtn.addEventListener('click', () => {
  dateFilter.value = '';
  levelFilter.value = '';
  applyFilters();
  });

  // Inisialisasi
  fetchLogs();
</script>
@endpush