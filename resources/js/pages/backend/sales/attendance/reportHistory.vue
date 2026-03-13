<template>
  <backendLayouts>
    <div class="container-xxl flex-grow-1 container-p-y">

      <h4 class="fw-bold py-3">
        <span class="text-muted fw-light">Absensi /</span> Riwayat Absensi Saya
      </h4>

      <div class="row">
        <div class="col-md-12">
          <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <h5 class="mb-0">Riwayat Absensi</h5>
              <small class="text-muted">Data milik akun yang sedang login</small>
            </div>

            <div class="card-body">

              <!-- Filter Bar -->
              <div class="row g-2 mb-3 align-items-center">
                <div class="col-md-4">
                  <div class="input-group input-group-merge">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input
                      type="text"
                      class="form-control"
                      placeholder="Cari tanggal, lokasi, tipe..."
                      :value="store.searchHistory"
                      @input="store.searchHistoryWithDelay($event.target.value)"
                    />
                  </div>
                </div>
                <div class="col-auto">
                  <select class="form-select form-select-sm" :value="store.historyPagination.per_page"
                    @change="store.historyPagination.per_page = +$event.target.value; store.changeHistoryPageSize()" style="width:auto;">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                  </select>
                </div>
                <div class="col-auto">
                  <button class="btn btn-outline-secondary btn-sm me-1" @click="store.resetHistoryFilters()">
                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Reset
                  </button>

                  <button 
                        class="btn btn-outline-secondary btn-sm"
                        @click="$router.push('/sales-timesheets-leave-reports')"
                    >
                        <i class="fa-solid fa-arrow-left"></i> Back 
                  </button>
                </div>
                <div class="col-auto ms-auto">
                  <div class="dropdown">
                    <button class="btn btn-danger btn-sm dropdown-toggle" type="button"
                      data-bs-toggle="dropdown" :disabled="store.historyData.length === 0">
                      <i class="bx bx-download me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li>
                        <button class="dropdown-item" @click="handleExportExcel">
                          <i class="bx bx-spreadsheet me-2 text-success"></i> Excel (.xlsx)
                        </button>
                      </li>
                      <li>
                        <button class="dropdown-item" @click="handleExportPdf">
                          <i class="bx bxs-file-pdf me-2 text-danger"></i> PDF
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Table -->
              <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle" style="font-size:13px;">
                  <thead>
                    <tr>
                      <th class="bg-primary text-white text-center" style="width:50px;">No</th>
                      <th class="bg-primary text-white" style="cursor:pointer;min-width:130px;" @click="store.toggleHistorySort('attendance_date')">
                        Tanggal
                        <i class="bx ms-1" :class="store.historySort.column === 'attendance_date' ? (store.historySort.direction === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort'"></i>
                      </th>
                      <th class="bg-primary text-white text-center" style="min-width:70px;">Waktu</th>
                      <th class="bg-primary text-white text-center" style="min-width:60px;">Tipe</th>
                      <th class="bg-primary text-white text-center" style="min-width:80px;">Status</th>
                      <!-- <th class="bg-primary text-white text-center" style="min-width:70px;">Mode</th> -->
                      <th class="bg-primary text-white" style="min-width:200px;">Lokasi</th>
                      <th class="bg-primary text-white text-center" style="min-width:70px;">Device</th>
                      <th class="bg-primary text-white text-center" style="min-width:60px;">Foto</th>
                      <th class="bg-primary text-white text-center" style="min-width:60px;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="store.loadingHistory">
                      <td colspan="10" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                        Memuat data...
                      </td>
                    </tr>
                    <tr v-else-if="store.historyData.length === 0">
                      <td colspan="10" class="text-center py-4 text-muted">
                        <i class="bx bx-calendar-x fs-4 d-block mb-1"></i>
                        Tidak ada data absensi
                      </td>
                    </tr>
                    <tr v-else v-for="(item, idx) in store.historyData" :key="item.id">
                      <td class="text-center">
                        {{ (store.historyPagination.current_page - 1) * store.historyPagination.per_page + idx + 1 }}
                      </td>

                      <!-- Tanggal + badge libur nasional -->
                      <td>
                        <span class="fw-semibold">{{ store.formatDate(item.attendance_date) }}</span>
                        <br/>
                        <small class="text-muted">{{ item.attendance_time }}</small>
                        <!-- Badge libur nasional jika ada -->
                        <span
                          v-if="getHolidayName(item.attendance_date)"
                          class="badge d-block mt-1"
                          style="background:#8b5cf6;font-size:10px;white-space:normal;text-align:left;"
                        >
                          🔴 {{ getHolidayName(item.attendance_date) }}
                        </span>
                      </td>

                      <td class="text-center fw-semibold">{{ item.attendance_time }}</td>
                      <td class="text-center">
                        <span class="badge" :class="store.getTypeBadgeClass(item.attendance_type)">{{ item.attendance_type }}</span>
                      </td>
                      <td class="text-center">
                        <span class="badge" :class="store.getStatusBadgeClass(item.attendance_status)">{{ item.attendance_status }}</span>
                      </td>
                      <!-- <td class="text-center">
                        <span class="badge bg-secondary">{{ item.attendance_mode }}</span>
                      </td> -->
                      <td>
                        <span class="text-muted" style="font-size:11px;">
                          <i class="bx bx-map-pin me-1 text-danger"></i>{{ item.location_name ?? '-' }}
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-info">{{ item.device_type }}</span>
                      </td>
                      <td class="text-center">
                        <img v-if="store.getPhotoUrl(item.photo_path)" :src="store.getPhotoUrl(item.photo_path)"
                          class="rounded" style="width:36px;height:36px;object-fit:cover;cursor:pointer;"
                          @click="openPhoto(store.getPhotoUrl(item.photo_path))" />
                        <span v-else class="text-muted">-</span>
                      </td>
                      <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary px-2 py-1" @click="openDetail(item)" title="Lihat Detail">
                          <i class="bx bx-show"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <small class="text-muted">
                  Menampilkan {{ store.historyData.length }} dari {{ store.historyPagination.total }} data
                </small>
                <nav>
                  <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: !store.historyPagination.prev_page_url }">
                      <button class="page-link" @click="goToPage(store.historyPagination.current_page - 1)">
                        <i class="bx bx-chevron-left"></i>
                      </button>
                    </li>
                    <li class="page-item" v-for="page in store.historyPagination.last_page" :key="page"
                      :class="{ active: page === store.historyPagination.current_page }">
                      <button class="page-link" @click="goToPage(page)">{{ page }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: !store.historyPagination.next_page_url }">
                      <button class="page-link" @click="goToPage(store.historyPagination.current_page + 1)">
                        <i class="bx bx-chevron-right"></i>
                      </button>
                    </li>
                  </ul>
                </nav>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Modal Detail -->
      <div class="modal fade" id="modalHistoryDetail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                Detail Absensi
                <span class="badge ms-2" :class="store.getTypeBadgeClass(selectedItem?.attendance_type)">{{ selectedItem?.attendance_type }}</span>
                <span class="badge ms-1" :class="store.getStatusBadgeClass(selectedItem?.attendance_status)">{{ selectedItem?.attendance_status }}</span>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" v-if="selectedItem">
              <!-- Alert libur nasional -->
              <div v-if="getHolidayName(selectedItem.attendance_date)" class="alert alert-warning py-2 mb-3" style="font-size:13px;">
                <i class="bx bx-calendar-event me-1"></i>
                Hari ini adalah <strong>Libur Nasional</strong>: {{ getHolidayName(selectedItem.attendance_date) }}
              </div>
              <div class="row g-3">
                <div class="col-md-4 text-center">
                  <img v-if="store.getPhotoUrl(selectedItem.photo_path)" :src="store.getPhotoUrl(selectedItem.photo_path)"
                    class="rounded img-fluid" style="max-height:200px;object-fit:cover;" />
                  <div v-else class="bg-light rounded d-flex align-items-center justify-content-center" style="height:150px;">
                    <span class="text-muted">Tidak ada foto</span>
                  </div>
                </div>
                <div class="col-md-8">
                  <table class="table table-sm table-borderless mb-0" style="font-size:13px;">
                    <tr><td class="text-muted" style="width:140px;">Tanggal</td><td>{{ store.formatDate(selectedItem.attendance_date) }}</td></tr>
                    <tr><td class="text-muted">Waktu</td><td class="fw-semibold">{{ selectedItem.attendance_time }}</td></tr>
                    <tr><td class="text-muted">Tipe</td><td><span class="badge" :class="store.getTypeBadgeClass(selectedItem.attendance_type)">{{ selectedItem.attendance_type }}</span></td></tr>
                    <tr><td class="text-muted">Status</td><td><span class="badge" :class="store.getStatusBadgeClass(selectedItem.attendance_status)">{{ selectedItem.attendance_status }}</span></td></tr>
                    <!-- <tr><td class="text-muted">Mode</td><td><span class="badge bg-secondary">{{ selectedItem.attendance_mode }}</span></td></tr> -->
                    <tr><td class="text-muted">Device</td><td><span class="badge bg-info">{{ selectedItem.device_type }}</span></td></tr>
                    <tr><td class="text-muted">IP Address</td><td>{{ selectedItem.ip_address }}</td></tr>
                    <tr>
                      <td class="text-muted">Policy</td>
                      <td>
                        <span class="badge" :class="selectedItem.policy_status === 'ALLOWED' ? 'bg-success' : 'bg-danger'">{{ selectedItem.policy_status }}</span>
                        <small class="text-muted ms-1">{{ selectedItem.policy_reason }}</small>
                      </td>
                    </tr>
                    <tr><td class="text-muted">Lokasi</td><td style="font-size:11px;">{{ selectedItem.location_name ?? '-' }}</td></tr>
                    <tr v-if="selectedItem.noted"><td class="text-muted">Catatan</td><td>{{ selectedItem.noted }}</td></tr>
                  </table>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Foto -->
      <div class="modal fade" id="modalFotoPreview" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-body text-center p-2">
              <img :src="previewPhotoUrl" class="img-fluid rounded" alt="preview" />
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </backendLayouts>
</template>

<script setup>
import { ref, onMounted } from 'vue'

import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useDataAttendanceStore } from '../../../../stores/AttendanceFreeLocationStore';
import { exportHistoryExcel, exportHistoryPdf, exportHistoryCsv } from '../../../../stores/Useexportattendance';
import { useHolidayStore } from '../../../../stores/Useholidaystore';

const store           = useDataAttendanceStore()
const holidayStore    = useHolidayStore()
const selectedItem    = ref(null)
const previewPhotoUrl = ref(null)

// =============================================
// CEK LIBUR DARI TANGGAL ITEM
// =============================================
function getHolidayName(dateStr) {
  if (!dateStr) return null
  const [year, month] = dateStr.split('-')
  const { isHoliday, name } = holidayStore.checkHoliday(dateStr)
  // Pastikan bulan itu sudah di-fetch
  holidayStore.fetchHolidays(+month, +year)
  return isHoliday ? name : null
}

// =============================================
// EXPORT
// =============================================
function handleExportExcel() {
  exportHistoryExcel(store.historyData)
}
function handleExportPdf() {
  exportHistoryPdf(store.historyData, store.historyData[0]?.user?.fullname ?? '')
}

// =============================================
// PAGINATION
// =============================================
function goToPage(page) {
  if (page < 1 || page > store.historyPagination.last_page) return
  store.historyPagination.current_page = page
  store.fetchMyHistory()
}

// =============================================
// MODAL
// =============================================
function openDetail(item) {
  selectedItem.value = item
  new window.bootstrap.Modal(document.getElementById('modalHistoryDetail')).show()
}
function openPhoto(url) {
  previewPhotoUrl.value = url
  new window.bootstrap.Modal(document.getElementById('modalFotoPreview')).show()
}

// =============================================
// INIT — fetch history + libur bulan ini
// =============================================
onMounted(async () => {
  await store.fetchMyHistory()

  // Pre-fetch libur untuk bulan yang muncul di data history
  const months = new Set()
  store.historyData.forEach(item => {
    if (item.attendance_date) {
      const [y, m] = item.attendance_date.split('-')
      months.add(`${m}|${y}`)
    }
  })
  months.forEach(key => {
    const [m, y] = key.split('|')
    holidayStore.fetchHolidays(+m, +y)
  })
})
</script>
