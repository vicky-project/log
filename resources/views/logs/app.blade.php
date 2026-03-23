@extends('coreui::layouts.admin')
@section('title', 'Log Reader')

@push('styles')
<style>
  /* Tambahan style */
  .log-table-container {
    overflow-x: auto;
  }
  .log-table {
    font-size: 0.9rem;
  }
  .log-table th {
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
  }
  .log-table th i {
    margin-left: 5px;
    font-size: 0.8rem;
  }
  .log-type-INFO {
    color: #0d6efd;
    font-weight: 500;
  }
  .log-type-ERROR {
    color: #dc3545;
    font-weight: 500;
  }
  .log-type-WARNING {
    color: #ffc107;
    font-weight: 500;
  }
  .log-type-DEBUG {
    color: #6c757d;
    font-weight: 500;
  }
  .search-box {
    max-width: 300px;
  }
  .filter-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
  }
  .loading-overlay {
    position: relative;
    min-height: 200px;
  }
  .loading-spinner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10;
  }
  .pagination .page-link {
    cursor: pointer;
  }
  @media (max-width: 768px) {
    .filter-group {
      width: 100%;
    }
    .filter-group select, .filter-group .input-group {
      flex: 1;
    }
  }
</style>
@endpush

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="mb-0">
          <i class="bi bi-file-text-fill me-2"></i> Log Reader
        </h5>
        <div class="filter-group">
          <!-- Filter Tanggal -->
          <select id="logDateSelect" class="form-select" style="width: auto; min-width: 130px;">
            <option value="">Memuat tanggal...</option>
          </select>
          <!-- Filter Environment -->
          <select id="logEnvSelect" class="form-select" style="width: auto; min-width: 130px;">
            <option value="">Semua Environment</option>
          </select>
          <!-- Filter Type Log -->
          <select id="logTypeSelect" class="form-select" style="width: auto; min-width: 120px;">
            <option value="">Semua Type</option>
          </select>
          <!-- Pencarian -->
          <div class="input-group search-box">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Cari log...">
          </div>
        </div>
      </div>
      <div class="card-body">
        <!-- Loading / Error -->
        <div id="logLoader" class="loading-overlay text-center py-5 d-none">
          <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">
              Memuat data log...
            </p>
          </div>
        </div>
        <div id="logError" class="alert alert-danger d-none" role="alert"></div>

        <!-- Tabel log -->
        <div class="log-table-container">
          <table class="table table-hover log-table" id="logTable">
            <thead>
              <th data-sort="timestamp">Timestamp <i class="bi bi-arrow-down-up"></i></th>
              <th data-sort="env">Environment <i class="bi bi-arrow-down-up"></i></th>
              <th data-sort="type">Type <i class="bi bi-arrow-down-up"></i></th>
              <th data-sort="message">Message <i class="bi bi-arrow-down-up"></i></th>
            </tr>
          </thead>
          <tbody id="logTableBody">
            <td colspan="4" class="text-center text-muted">Pilih tanggal untuk menampilkan log</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-3">
      <div id="paginationInfo" class="text-muted small"></div>
      <nav>
        <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
      </nav>
    </div>
  </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
// Elemen DOM
const logDateSelect = document.getElementById('logDateSelect');
const logEnvSelect = document.getElementById('logEnvSelect');
const logTypeSelect = document.getElementById('logTypeSelect');
const searchInput = document.getElementById('searchInput');
const logTableBody = document.getElementById('logTableBody');
const logLoader = document.getElementById('logLoader');
const logError = document.getElementById('logError');
const paginationInfo = document.getElementById('paginationInfo');
const paginationControls = document.getElementById('paginationControls');
const tableHeaders = document.querySelectorAll('#logTable th[data-sort]');

// State
let originalLogs = [];          // Semua log dari API
let logsByDate = {};            // Objek: key=YYYY-MM-DD, value=array log
let availableDates = [];         // Array tanggal yang tersedia
let uniqueEnvs = [];             // Array environment unik
let uniqueTypes = [];            // Array type unik
let currentDate = '';            // Tanggal yang sedang dipilih
let currentEnv = '';             // Environment yang dipilih (kosong = semua)
let currentType = '';            // Type yang dipilih (kosong = semua)
let currentFilteredLogs = [];    // Hasil filter setelah sorting dan pencarian
let currentSort = { column: 'timestamp', direction: 'desc' };
let currentPage = 1;
const rowsPerPage = 15;

const API_URL = 'https://vickyserver.my.id/app/admin/api/log-reader';

// Helper: ekstrak tanggal dari timestamp (format "YYYY-MM-DD HH:MM:SS")
function getDateFromTimestamp(timestamp) {
if (!timestamp) return '';
return timestamp.substring(0, 10);
}

// Tampilkan loading / error
function showLoading(show) {
if (show) {
logLoader.classList.remove('d-none');
logTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Memuat...</td></tr>';
logError.classList.add('d-none');
} else {
logLoader.classList.add('d-none');
}
}

function showError(message) {
logError.textContent = message;
logError.classList.remove('d-none');
logTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Gagal memuat data</td></tr>';
paginationControls.innerHTML = '';
paginationInfo.textContent = '';
}

// Fetch data sekali
async function fetchLogs() {
showLoading(true);
try {
const response = await fetch(API_URL);
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const result = await response.json();

if (!result.success) throw new Error(result.message || 'Gagal mengambil data');

const data = result.data;
originalLogs = data.logs || [];

// Kelompokkan log berdasarkan tanggal (dari timestamp)
logsByDate = {};
originalLogs.forEach(log => {
const date = getDateFromTimestamp(log.timestamp);
if (!logsByDate[date]) logsByDate[date] = [];
logsByDate[date].push(log);
});

// Ambil daftar tanggal unik (urut descending)
availableDates = Object.keys(logsByDate).sort().reverse();
if (availableDates.length === 0) {
throw new Error('Tidak ada data log yang ditemukan');
}

// Ambil daftar environment unik dari semua log
const envSet = new Set();
originalLogs.forEach(log => {
if (log.env) envSet.add(log.env);
});
uniqueEnvs = Array.from(envSet).sort();

// Ambil daftar type unik dari semua log
const typeSet = new Set();
originalLogs.forEach(log => {
if (log.type) typeSet.add(log.type);
});
uniqueTypes = Array.from(typeSet).sort();

// Update dropdown tanggal, environment, type
updateDateDropdown();
updateEnvDropdown();
updateTypeDropdown();

// Tentukan tanggal default: yang pertama di dropdown (terbaru)
currentDate = availableDates[0];
logDateSelect.value = currentDate;

// Reset filter lainnya
currentEnv = '';
logEnvSelect.value = '';
currentType = '';
logTypeSelect.value = '';
searchInput.value = '';
currentSort = { column: 'timestamp', direction: 'desc' };
currentPage = 1;

// Terapkan semua filter
applyFilters();

} catch (err) {
console.error(err);
showError(err.message || 'Terjadi kesalahan saat mengambil data');
} finally {
showLoading(false);
}
}

// Update dropdown tanggal
function updateDateDropdown() {
logDateSelect.innerHTML = '';
availableDates.forEach(date => {
const option = document.createElement('option');
option.value = date;
option.textContent = date;
logDateSelect.appendChild(option);
});
}

// Update dropdown environment
function updateEnvDropdown() {
logEnvSelect.innerHTML = '<option value="">Semua Environment</option>';
uniqueEnvs.forEach(env => {
const option = document.createElement('option');
option.value = env;
option.textContent = env;
logEnvSelect.appendChild(option);
});
}

// Update dropdown type
function updateTypeDropdown() {
logTypeSelect.innerHTML = '<option value="">Semua Type</option>';
uniqueTypes.forEach(type => {
const option = document.createElement('option');
option.value = type;
option.textContent = type;
logTypeSelect.appendChild(option);
});
}

// Fungsi utama filter: tanggal + environment + type + pencarian
function applyFilters() {
// 1. Ambil log berdasarkan tanggal
let filtered = logsByDate[currentDate] || [];

// 2. Filter berdasarkan environment (jika dipilih)
if (currentEnv !== '') {
filtered = filtered.filter(log => log.env === currentEnv);
}

// 3. Filter berdasarkan type (jika dipilih)
if (currentType !== '') {
filtered = filtered.filter(log => log.type === currentType);
}

// 4. Filter berdasarkan pencarian (teks bebas)
const searchTerm = searchInput.value.trim().toLowerCase();
if (searchTerm !== '') {
filtered = filtered.filter(log => {
return (log.timestamp && log.timestamp.toLowerCase().includes(searchTerm)) ||
(log.env && log.env.toLowerCase().includes(searchTerm)) ||
(log.type && log.type.toLowerCase().includes(searchTerm)) ||
(log.message && log.message.toLowerCase().includes(searchTerm));
});
}

currentFilteredLogs = filtered;
// Urutkan
sortLogs();
// Reset ke halaman pertama
currentPage = 1;
renderTable();
}

// Sorting berdasarkan currentSort
function sortLogs() {
const { column, direction } = currentSort;
currentFilteredLogs.sort((a, b) => {
let valA = a[column] || '';
let valB = b[column] || '';
if (column === 'timestamp') {
return direction === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
}
valA = valA.toString().toLowerCase();
valB = valB.toString().toLowerCase();
return direction === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
});
}

// Render tabel dengan paginasi
function renderTable() {
const start = (currentPage - 1) * rowsPerPage;
const end = start + rowsPerPage;
const pageLogs = currentFilteredLogs.slice(start, end);

if (pageLogs.length === 0) {
logTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Tidak ada log ditemukan</td></tr>';
paginationControls.innerHTML = '';
paginationInfo.textContent = '';
return;
}

let html = '';
pageLogs.forEach(log => {
let typeClass = '';
if (log.type === 'INFO') typeClass = 'log-type-INFO';
else if (log.type === 'ERROR') typeClass = 'log-type-ERROR';
else if (log.type === 'WARNING') typeClass = 'log-type-WARNING';
else if (log.type === 'DEBUG') typeClass = 'log-type-DEBUG';

html += `
<tr>
<td class="text-nowrap">${escapeHtml(log.timestamp || '-')}</td>
<td>${escapeHtml(log.env || '-')}</td>
<td><span class="${typeClass}">${escapeHtml(log.type || '-')}</span></td>
<td>${escapeHtml(log.message || '-')}</td>
</tr>
`;
});
logTableBody.innerHTML = html;

// Pagination
const totalPages = Math.ceil(currentFilteredLogs.length / rowsPerPage);
updatePaginationControls(totalPages);
paginationInfo.textContent = `Menampilkan ${start+1} - ${Math.min(end, currentFilteredLogs.length)} dari ${currentFilteredLogs.length} log`;
}

// Update tombol pagination
function updatePaginationControls(totalPages) {
if (totalPages <= 1) {
paginationControls.innerHTML = '';
return;
}

let pagesHtml = '';
// Prev
pagesHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
<a class="page-link" data-page="${currentPage-1}" href="#">«</a>
</li>`;

let startPage = Math.max(1, currentPage - 2);
let endPage = Math.min(totalPages, startPage + 4);
if (endPage - startPage < 4 && startPage > 1) startPage = Math.max(1, endPage - 4);

for (let i = startPage; i <= endPage; i++) {
pagesHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
<a class="page-link" data-page="${i}" href="#">${i}</a>
</li>`;
}

// Next
pagesHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
<a class="page-link" data-page="${currentPage+1}" href="#">»</a>
</li>`;

paginationControls.innerHTML = pagesHtml;

// Attach event listener ke tombol pagination
document.querySelectorAll('#paginationControls .page-link').forEach(link => {
link.addEventListener('click', (e) => {
e.preventDefault();
const page = parseInt(link.getAttribute('data-page'));
if (!isNaN(page) && page >= 1 && page <= totalPages && page !== currentPage) {
currentPage = page;
renderTable();
}
});
});
}

// Update ikon sort pada header tabel
function updateSortIcons(activeColumn) {
tableHeaders.forEach(th => {
const col = th.getAttribute('data-sort');
const icon = th.querySelector('i');
if (col === activeColumn) {
icon.className = currentSort.direction === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';
} else {
icon.className = 'bi bi-arrow-down-up';
}
});
}

// Escape HTML untuk keamanan
function escapeHtml(str) {
if (!str) return '';
return str.replace(/[&<>]/g, function(m) {
if (m === '&') return '&amp;';
if (m === '<') return '&lt;';
if (m === '>') return '&gt;';
return m;
});
}

// Event listeners
logDateSelect.addEventListener('change', function() {
currentDate = this.value;
applyFilters();
});

logEnvSelect.addEventListener('change', function() {
currentEnv = this.value;
applyFilters();
});

logTypeSelect.addEventListener('change', function() {
currentType = this.value;
applyFilters();
});

searchInput.addEventListener('input', function() {
applyFilters();
});

// Sorting via header klik
tableHeaders.forEach(header => {
header.addEventListener('click', () => {
const column = header.getAttribute('data-sort');
if (currentSort.column === column) {
currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
} else {
currentSort.column = column;
currentSort.direction = 'asc';
}
sortLogs();
currentPage = 1;
renderTable();
updateSortIcons(column);
});
});

// Mulai ambil data
fetchLogs();
});
</script>
@endpush