<template>
  <div class="visits-wrapper">

    <!-- HEADER -->
    <div class="page-header">
      <div class="page-title">
        <i class="bx bx-map-pin"></i>
        <div>
          <h4>Visit Data</h4>
          <span>Histori & monitoring kunjungan sales</span>
        </div>
      </div>
      <button class="btn-export" @click="exportExcel" :disabled="isExporting">
        <i class="bx bx-download"></i>
        {{ isExporting ? 'Exporting...' : 'Export Excel' }}
      </button>
    </div>

    <!-- FILTERS -->
    <div class="filter-bar">
      <div class="filter-left">
        <div class="filter-group">
          <i class="bx bx-calendar"></i>
          <input type="date" v-model="filters.dateFrom" @change="fetchData" />
          <span>→</span>
          <input type="date" v-model="filters.dateTo" @change="fetchData" />
        </div>
        <select v-model="filters.status" @change="fetchData" class="filter-select">
          <option value="">Semua Status</option>
          <option value="PLANNED">Planned</option>
          <option value="ONGOING">Ongoing</option>
          <option value="DONE">Done</option>
        </select>
        <select v-model="filters.visitType" @change="fetchData" class="filter-select">
          <option value="">Semua Type</option>
          <option value="LEAD">Lead</option>
          <option value="CUSTOMER">Customer</option>
        </select>
      </div>
      <div class="filter-right">
        <div class="search-box">
          <i class="bx bx-search"></i>
          <input
            type="text"
            v-model="filters.search"
            placeholder="Cari sales, perusahaan, kode..."
            @input="onSearch"
          />
          <button v-if="filters.search" class="search-clear" @click="clearSearch">
            <i class="bx bx-x"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- STATS BAR -->
    <div class="stats-bar">
      <div class="stat-item stat-total">
        <span class="stat-num">{{ pagination.total }}</span>
        <span class="stat-label">Total</span>
      </div>
      <div class="stat-item stat-planned">
        <span class="stat-num">{{ stats.planned }}</span>
        <span class="stat-label">Planned</span>
      </div>
      <div class="stat-item stat-ongoing">
        <span class="stat-num">{{ stats.ongoing }}</span>
        <span class="stat-label">Ongoing</span>
      </div>
      <div class="stat-item stat-done">
        <span class="stat-num">{{ stats.done }}</span>
        <span class="stat-label">Done</span>
      </div>
      <div class="stat-item stat-lead">
        <span class="stat-num">{{ stats.lead }}</span>
        <span class="stat-label">Lead</span>
      </div>
      <div class="stat-item stat-customer">
        <span class="stat-num">{{ stats.customer }}</span>
        <span class="stat-label">Customer</span>
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
      <div class="table-loading" v-if="isLoading">
        <div class="loader-ring"></div>
        <span>Memuat data...</span>
      </div>
     <div class="table-responsive" v-else>
      <table class="visits-table">
        <thead>
          <tr>
            <th @click="setSort('visit_date')" class="sortable">
              Tanggal Visit <i :class="sortIcon('visit_date')"></i>
            </th>
            <th>Kode Visit</th>
            <th @click="setSort('company_name')" class="sortable">
              Perusahaan <i :class="sortIcon('company_name')"></i>
            </th>
            <th>Sales</th>
            <th>Type</th>
            <th>Progress</th>
            <th>Check-in</th>
            <th @click="setSort('check_out')" class="sortable">
              Check-out <i :class="sortIcon('check_out')"></i>
            </th>
            <th>Durasi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="visits.length === 0">
            <td colspan="10" class="empty-row">
              <i class="bx bx-inbox"></i>
              <p>Tidak ada data visit</p>
            </td>
          </tr>
          <tr
            v-for="visit in visits"
            :key="visit.id"
            class="table-row"
            @click="openDetail(visit)"
          >
            <td>
              <div class="date-cell">
                <span class="date-main">{{ formatDate(visit.visit_at) }}</span>
                <span class="date-time">{{ formatTimeOnly(visit.visit_at) }}</span>
              </div>
            </td>
            <td><span class="visit-code">{{ visit.visit_code }}</span></td>
            <td>
              <div class="company-cell">
                <span class="company-name">{{ visit.company_name }}</span>
                <span class="contact-name">{{ visit.target_contact }}</span>
              </div>
            </td>
            <td><span class="sales-name">{{ visit.sales_name }}</span></td>
            <td>
              <span class="type-badge" :class="visit.visit_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                {{ visit.visit_type }}
              </span>
            </td>
            <td>
              <span class="progress-badge" :class="progressClass(visit.visit_progress)">
                {{ visit.visit_progress }}
              </span>
            </td>
            <td class="time-cell">{{ formatTimeOnly(visit.check_in_at) ?? '-' }}</td>
            <td class="time-cell">{{ formatTimeOnly(visit.check_out_at) ?? '-' }}</td>
            <td class="time-cell">{{ visit.total_time_result ?? '-' }}</td>
            <td @click.stop>
              <button class="btn-detail" @click="openDetail(visit)">
                <i class="bx bx-show"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>

    <!-- PAGINATION -->
    <div class="pagination-bar">
      <div class="pagination-info">
        Menampilkan {{ paginationFrom }}–{{ paginationTo }} dari {{ pagination.total }} data
      </div>
      <div class="pagination-controls">
        <select v-model="filters.perPage" @change="fetchData" class="per-page-select">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <button class="page-btn" :disabled="pagination.currentPage <= 1" @click="goPage(1)">
          <i class="bx bx-chevrons-left"></i>
        </button>
        <button class="page-btn" :disabled="pagination.currentPage <= 1" @click="goPage(pagination.currentPage - 1)">
          <i class="bx bx-chevron-left"></i>
        </button>
        <span class="page-indicator">{{ pagination.currentPage }} / {{ pagination.lastPage }}</span>
        <button class="page-btn" :disabled="pagination.currentPage >= pagination.lastPage" @click="goPage(pagination.currentPage + 1)">
          <i class="bx bx-chevron-right"></i>
        </button>
        <button class="page-btn" :disabled="pagination.currentPage >= pagination.lastPage" @click="goPage(pagination.lastPage)">
          <i class="bx bx-chevrons-right"></i>
        </button>
      </div>
    </div>

    <!-- DETAIL MODAL -->
    <transition name="fade">
      <div class="modal-overlay" v-if="selectedVisit" @click.self="selectedVisit = null">
        <div class="modal-card">
          <div class="modal-header">
            <div>
              <h5>{{ selectedVisit.visit_code }}</h5>
              <span class="progress-badge" :class="progressClass(selectedVisit.visit_progress)">
                {{ selectedVisit.visit_progress }}
              </span>
            </div>
            <button class="modal-close" @click="selectedVisit = null">
              <i class="bx bx-x"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="modal-section">
              <div class="modal-row">
                <span class="modal-label">Perusahaan</span>
                <span>
                  <span class="type-badge" :class="selectedVisit.visit_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                    {{ selectedVisit.visit_type }}
                  </span>
                  {{ selectedVisit.company_name }}
                </span>
              </div>
              <div class="modal-row">
                <span class="modal-label">Kontak</span>
                <span>{{ selectedVisit.target_contact ?? '-' }}</span>
              </div>
              <div class="modal-row">
                <span class="modal-label">Telepon</span>
                <span>{{ selectedVisit.target_phone ?? '-' }}</span>
              </div>
              <div class="modal-row">
                <span class="modal-label">Alamat</span>
                <span>{{ selectedVisit.target_address ?? '-' }}</span>
              </div>
            </div>
            <div class="modal-divider"></div>
            <div class="modal-section">
              <div class="modal-row">
                <span class="modal-label">Sales</span>
                <span>{{ selectedVisit.sales_name }}</span>
              </div>
              <div class="modal-row">
                <span class="modal-label">Jadwal Visit</span>
                <span>{{ formatDateTime(selectedVisit.visit_at) }}</span>
              </div>
              <div class="modal-row">
                <span class="modal-label">Check-in</span>
                <span>{{ formatDateTime(selectedVisit.check_in_at) ?? '-' }}</span>
              </div>
              <div class="modal-row">
                <span class="modal-label">Check-out</span>
                <span>{{ formatDateTime(selectedVisit.check_out_at) ?? '-' }}</span>
              </div>
              <div class="modal-row">
                <span class="modal-label">Durasi</span>
                <span>{{ selectedVisit.total_time_result ?? '-' }}</span>
              </div>
            </div>
            <div class="modal-divider"></div>
            <div class="modal-section">
              <div class="modal-row">
                <span class="modal-label">Lokasi GPS</span>
                <span>{{ selectedVisit.gps_snapshot ?? '-' }}</span>
              </div>
              <div class="modal-row">
                <span class="modal-label">Notes</span>
                <span>{{ selectedVisit.notes ?? '-' }}</span>
              </div>
              <div class="modal-row">
                <span class="modal-label">Hasil Visit</span>
                <span>{{ selectedVisit.visit_result ?? '-' }}</span>
              </div>
              <div class="modal-row">
                <span class="modal-label">Respon Customer</span>
                <span>{{ selectedVisit.customer_response ?? '-' }}</span>
              </div>
            </div>
            <div v-if="selectedVisit.photo_url" class="modal-photo">
              <span class="modal-label">Foto Check-in</span>
              <img :src="selectedVisit.photo_url" alt="Foto Visit" />
            </div>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// --- STATE ---
const visits = ref([])
const isLoading = ref(false)
const isExporting = ref(false)
const selectedVisit = ref(null)

const filters = ref({
  dateFrom: new Date().toISOString().split('T')[0],
  dateTo: new Date().toISOString().split('T')[0],
  status: '',
  visitType: '',
  search: '',
  sortBy: 'visit_date',
  sortDir: 'desc',
  perPage: 25,
  page: 1,
})

const pagination = ref({
  total: 0,
  currentPage: 1,
  lastPage: 1,
  perPage: 25,
})

// --- COMPUTED ---
const paginationFrom = computed(() => {
  if (pagination.value.total === 0) return 0
  return (pagination.value.currentPage - 1) * pagination.value.perPage + 1
})

const paginationTo = computed(() => {
  return Math.min(pagination.value.currentPage * pagination.value.perPage, pagination.value.total)
})

const stats = computed(() => ({
  planned:  visits.value.filter(v => v.visit_progress === 'PLANNED').length,
  ongoing:  visits.value.filter(v => v.visit_progress === 'ONGOING').length,
  done:     visits.value.filter(v => v.visit_progress === 'DONE').length,
  lead:     visits.value.filter(v => v.visit_type === 'LEAD').length,
  customer: visits.value.filter(v => v.visit_type === 'CUSTOMER').length,
}))

// --- HELPERS ---
const formatDate = (dt) => {
  if (!dt) return '-'
  return new Date(dt).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}
const formatTimeOnly = (dt) => {
  if (!dt) return null
  return new Date(dt).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}
const formatDateTime = (dt) => {
  if (!dt) return null
  return new Date(dt).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const progressClass = (progress) => {
  if (progress === 'PLANNED') return 'badge-planned'
  if (progress === 'ONGOING') return 'badge-ongoing'
  if (progress === 'DONE')    return 'badge-done'
  return 'badge-unknown'
}

const sortIcon = (col) => {
  if (filters.value.sortBy !== col) return 'bx bx-sort'
  return filters.value.sortDir === 'asc' ? 'bx bx-sort-up' : 'bx bx-sort-down'
}

const setSort = (col) => {
  if (filters.value.sortBy === col) {
    filters.value.sortDir = filters.value.sortDir === 'asc' ? 'desc' : 'asc'
  } else {
    filters.value.sortBy = col
    filters.value.sortDir = 'desc'
  }
  fetchData()
}

// --- FETCH ---
const fetchData = async () => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/data-visits-all-data', {
      params: {
        date_from:    filters.value.dateFrom,
        date_to:      filters.value.dateTo,
        visit_status: filters.value.status,
        visit_type:   filters.value.visitType,
        search:       filters.value.search,
        sort_by:      filters.value.sortBy,
        sort_dir:     filters.value.sortDir,
        per_page:     filters.value.perPage,
        page:         filters.value.page,
      }
    })
    visits.value     = res.data.data?.data ?? []
    pagination.value = {
    total:       res.data.data?.pagination?.total ?? 0,
    currentPage: res.data.data?.pagination?.current_page ?? 1,
    lastPage:    res.data.data?.pagination?.last_page ?? 1,
    perPage:     res.data.data?.pagination?.per_page ?? 25,
    }
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

let searchTimer = null
const onSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    filters.value.page = 1
    fetchData()
  }, 400)
}

const clearSearch = () => {
  filters.value.search = ''
  filters.value.page = 1
  fetchData()
}

const goPage = (page) => {
  filters.value.page = page
  fetchData()
}

const openDetail = (visit) => {
  selectedVisit.value = visit
}

// --- EXPORT ---
const exportExcel = async () => {
  isExporting.value = true
  try {
    const res = await axios.get('/api/data-visits-all-data', {
      params: {
        date_from:    filters.value.dateFrom,
        date_to:      filters.value.dateTo,
        visit_status: filters.value.status,
        visit_type:   filters.value.visitType,
        search:       filters.value.search,
        sort_by:      filters.value.sortBy,
        sort_dir:     filters.value.sortDir,
        per_page:     9999,
        page:         1,
      }
    })
  const rows = res.data.data?.data ?? []
    const headers = [
      'Kode Visit','Tanggal Visit','Perusahaan','Kontak','Telepon',
      'Sales','Type','Progress','Check-in','Check-out','Durasi','Alamat','Notes'
    ]
    const csvRows = [
      headers.join(';'),
      ...rows.map(v => [
        v.visit_code,
        formatDateTime(v.visit_at),
        v.company_name,
        v.target_contact ?? '',
        v.target_phone ?? '',
        v.sales_name,
        v.visit_type,
        v.visit_progress,
        formatDateTime(v.check_in_at) ?? '',
        formatDateTime(v.check_out_at) ?? '',
        v.total_time_result ?? '',
        v.target_address ?? '',
        v.notes ?? '',
      ].map(val => `"${String(val).replace(/"/g, '""')}"`).join(';'))
    ]
    const csvContent = '\uFEFF' + csvRows.join('\n')
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url  = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href  = url
    link.download = `visit-data-${filters.value.dateFrom}-to-${filters.value.dateTo}.csv`
    link.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    console.error(e)
  } finally {
    isExporting.value = false
  }
}

onMounted(() => fetchData())
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap');

* { box-sizing: border-box; }

.visits-wrapper {
  font-family: 'DM Sans', sans-serif;
  padding: 24px;
  background: transparent;
  min-height: 100vh;
  color: #333;
}

/* HEADER */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.page-title { display: flex; align-items: center; gap: 12px; }
.page-title i { font-size: 28px; color: #696cff; }
.page-title h4 { font-size: 18px; font-weight: 700; color: #333; margin: 0; }
.page-title span { font-size: 12px; color: #9ca3af; }

.btn-export {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #71dd37;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 18px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}
.btn-export:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-export:hover:not(:disabled) { opacity: 0.85; }

/* FILTER BAR */
.filter-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 16px;
  flex-wrap: wrap;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.filter-left  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.filter-right { display: flex; align-items: center; gap: 10px; }

.filter-group {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f8f9fa;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  color: #9ca3af;
}
.filter-group input {
  background: transparent;
  border: none;
  color: #333;
  font-size: 12px;
  font-family: 'DM Sans', sans-serif;
  outline: none;
  cursor: pointer;
}

.filter-select {
  background: #f8f9fa;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 6px 12px;
  color: #333;
  font-size: 12px;
  font-family: 'DM Sans', sans-serif;
  outline: none;
  cursor: pointer;
}
.filter-select option { background: #fff; color: #333; }

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f8f9fa;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 6px 12px;
  min-width: 240px;
}
.search-box i { color: #9ca3af; font-size: 16px; }
.search-box input {
  background: transparent;
  border: none;
  color: #333;
  font-size: 13px;
  font-family: 'DM Sans', sans-serif;
  outline: none;
  width: 100%;
}
.search-box input::placeholder { color: #9ca3af; }
.search-clear { background: none; border: none; color: #9ca3af; cursor: pointer; padding: 0; display: flex; font-size: 16px; }

/* STATS BAR */
.stats-bar { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px 20px;
  min-width: 80px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.stat-num { font-size: 20px; font-weight: 700; font-family: 'Space Mono', monospace; }
.stat-label { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.stat-total    .stat-num { color: #333; }
.stat-planned  .stat-num { color: #ffab00; }
.stat-ongoing  .stat-num { color: #03c3ec; }
.stat-done     .stat-num { color: #71dd37; }
.stat-lead     .stat-num { color: #696cff; }
.stat-customer .stat-num { color: #03c3ec; }

/* TABLE */
.table-wrap {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 16px;
  position: relative;
  min-height: 200px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.table-loading {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  background: rgba(255,255,255,0.85);
  z-index: 10;
  font-size: 13px;
  color: #6b7280;
}
.loader-ring {
  width: 32px; height: 32px;
  border: 3px solid #e5e7eb;
  border-top-color: #696cff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.visits-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.visits-table thead tr { background: #f8f9fa; border-bottom: 2px solid #e5e7eb; }
.visits-table th {
  padding: 12px 14px;
  text-align: left;
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
  user-select: none;
}
.visits-table th.sortable {
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
}
.visits-table th.sortable:hover { color: #333; }
.visits-table td {
  padding: 12px 14px;
  border-bottom: 1px solid #f1f5f9;
  color: #6b7280;
  vertical-align: middle;
}
.table-row { cursor: pointer; transition: background 0.15s; }
.table-row:hover { background: #f8fafc; }
.table-row:last-child td { border-bottom: none; }

.date-cell { display: flex; flex-direction: column; }
.date-main { color: #111; font-weight: 500; }
.date-time { font-size: 11px; color: #9ca3af; font-family: 'Space Mono', monospace; }

.visit-code { font-family: 'Space Mono', monospace; font-size: 11px; color: #696cff; }
.company-cell { display: flex; flex-direction: column; }
.company-name { color: #111; font-weight: 500; }
.contact-name { font-size: 11px; color: #9ca3af; }
.sales-name { color: #333; }
.time-cell { font-family: 'Space Mono', monospace; font-size: 12px; }

.empty-row { text-align: center; padding: 48px !important; color: #9ca3af; }
.empty-row i { font-size: 32px; display: block; margin-bottom: 8px; }
.empty-row p { margin: 0; font-size: 13px; }

/* BADGES */
.type-badge { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 4px; }
.badge-lead     { background: rgba(105,108,255,0.15); color: #696cff; }
.badge-customer { background: rgba(3,195,236,0.15);   color: #0ea5e9; }

.progress-badge { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.3px; }
.badge-planned { background: rgba(255,171,0,0.15);  color: #d97706; }
.badge-ongoing { background: rgba(3,195,236,0.15);  color: #0ea5e9; }
.badge-done    { background: rgba(113,221,55,0.15); color: #16a34a; }
.badge-unknown { background: rgba(156,163,175,0.2); color: #6b7280; }

.btn-detail {
  background: #f8f9fa;
  border: 1px solid #e5e7eb;
  color: #6b7280;
  width: 30px; height: 30px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s;
}
.btn-detail:hover { background: #696cff; border-color: #696cff; color: #fff; }

/* PAGINATION */
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  font-size: 13px;
  color: #6b7280;
  flex-wrap: wrap;
  gap: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.pagination-controls { display: flex; align-items: center; gap: 6px; }
.page-btn {
  background: #f8f9fa;
  border: 1px solid #e5e7eb;
  color: #6b7280;
  width: 32px; height: 32px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s;
}
.page-btn:hover:not(:disabled) { background: #696cff; border-color: #696cff; color: #fff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-indicator { font-family: 'Space Mono', monospace; font-size: 12px; color: #333; padding: 0 8px; }
.per-page-select {
  background: #f8f9fa;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 4px 8px;
  color: #333;
  font-size: 12px;
  outline: none;
  cursor: pointer;
}

/* MODAL */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}
.modal-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  width: 100%;
  max-width: 520px;
  max-height: 85vh;
  overflow-y: auto;
  box-shadow: 0 24px 64px rgba(0,0,0,0.15);
}
.modal-card::-webkit-scrollbar { width: 4px; }
.modal-card::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
}
.modal-header h5 { font-family: 'Space Mono', monospace; font-size: 14px; color: #696cff; margin: 0 0 6px; }
.modal-close {
  background: #f8f9fa;
  border: none;
  color: #6b7280;
  width: 32px; height: 32px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
  transition: background 0.2s;
}
.modal-close:hover { background: #e5e7eb; color: #333; }

.modal-body { padding: 16px 20px; }
.modal-section { display: flex; flex-direction: column; gap: 10px; }
.modal-row { display: flex; gap: 12px; font-size: 13px; align-items: flex-start; color: #333; }
.modal-label { min-width: 130px; color: #9ca3af; font-size: 12px; padding-top: 1px; flex-shrink: 0; }
.modal-divider { border-top: 1px solid #f1f5f9; margin: 14px 0; }
.modal-photo { margin-top: 14px; }
.modal-photo img { width: 100%; border-radius: 8px; margin-top: 8px; object-fit: cover; max-height: 200px; }

/* TRANSITIONS */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@keyframes spin { to { transform: rotate(360deg); } }
</style>