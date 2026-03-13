<template>
  <backendLayouts>
    <div class="container-xxl flex-grow-1 container-p-y">

      <h4 class="fw-bold py-3">
        <span class="text-muted fw-light">Absensi /</span> Laporan Kehadiran Saya
      </h4>

      <div v-if="store.loadingReport || holidayStore.loadingHoliday" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="text-muted mt-2">
          {{ store.loadingReport ? 'Memuat data absensi...' : 'Memuat kalender libur nasional...' }}
        </p>
      </div>

      <template v-else>

        <!-- Profile + Filter -->
        <div class="row mb-4">
          <div class="col-md-12">
            <div class="card">
              <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">

                  <div style="width:56px;height:56px;border-radius:50%;background:#696cff1a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <span class="fw-bold text-primary" style="font-size:20px;">
                      {{ store.getInitials(store.reportData?.user?.fullname) }}
                    </span>
                  </div>

                  <div class="flex-grow-1">
                    <h5 class="mb-0 fw-bold">{{ store.reportData?.user?.fullname ?? '-' }}</h5>
                    <small class="text-muted">
                      {{ store.reportData?.user?.username }} &bull; {{ store.reportData?.user?.email }}
                    </small>
                  </div>

                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <select class="form-select form-select-sm" :value="store.selectedMonth" @change="onChangeMonth(+$event.target.value)" style="width:auto;">
                      <option v-for="(bln, idx) in bulanList" :key="idx" :value="idx + 1">{{ bln }}</option>
                    </select>
                    <select class="form-select form-select-sm" :value="store.selectedYear" @change="onChangeYear(+$event.target.value)" style="width:auto;">
                      <option v-for="yr in yearList" :key="yr" :value="yr">{{ yr }}</option>
                    </select>

                    <div class="dropdown">
                      <button class="btn btn-danger btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" :disabled="!store.reportData">
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
              </div>
            </div>
          </div>
        </div>

        <!-- Info libur bulan ini -->
        <div v-if="holidaysThisMonth.length > 0" class="row mb-3">
          <div class="col-12">
            <div class="alert alert-info d-flex align-items-start gap-2 py-2 mb-0" style="font-size:13px;">
              <i class="bx bx-calendar-event mt-1 flex-shrink-0"></i>
              <div>
                <strong>Libur Nasional bulan ini:</strong>
                <span v-for="(h, i) in holidaysThisMonth" :key="h.date">
                  {{ h.name }} ({{ formatTanggal(h.date) }})<span v-if="i < holidaysThisMonth.length - 1">, </span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4 g-3">
          <div class="col-6 col-md-2" v-for="stat in summaryStats" :key="stat.label">
            <div class="card h-100 text-center">
              <div class="card-body py-3 px-2">
                <div class="mb-1 mx-auto d-flex align-items-center justify-content-center" style="width:38px;height:38px;border-radius:8px;" :style="{ background: stat.color + '22' }">
                  <i class="bx" :class="stat.icon" :style="{ color: stat.color, fontSize: '20px' }"></i>
                </div>
                <h4 class="fw-bold mb-0" :style="{ color: stat.color }">{{ stat.value }}</h4>
                <small class="text-muted">{{ stat.label }}</small>
              </div>
            </div>
          </div>
        </div>


        

        <!-- Tabel -->
        <div class="row">
          <div class="col-md-12">

            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rekap Absensi — <span class="text-primary">{{ store.reportData?.period?.label }}</span></h5>
                <small class="text-muted">{{ store.reportData?.user?.fullname }}</small>
              </div>

              <div class="card-body p-1">
                  <router-link 
                    to="/sales-timesheets-leave-reports-history"
                    class="btn btn-primary"
                  >
                    <i class="fa-solid fa-timeline"></i> Lihat Riwayat
                  </router-link>
              </div>
              
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-bordered table-hover mb-0 text-center align-middle" style="min-width:900px;font-size:12px;">
                    <thead>
                      <tr>
                        <th class="bg-primary text-white" style="min-width:40px;" rowspan="2">No</th>
                        <th class="bg-primary text-white" style="min-width:130px;" rowspan="2">Nama</th>
                        <th class="bg-primary text-white" style="min-width:90px;" rowspan="2">Username</th>
                        <th
                          v-for="day in mergedDays" :key="'h-'+day.day"
                          class="text-white"
                          :class="day.is_off ? 'bg-secondary' : 'bg-primary'"
                          style="min-width:30px;padding:4px 2px;"
                          :title="day.holiday_name ?? (day.is_weekend ? 'Weekend' : '')"
                        >
                          {{ day.day }}
                          <span v-if="day.is_holiday" style="font-size:8px;display:block;">🔴</span>
                        </th>
                        <th colspan="5" class="bg-primary text-white">Keterangan</th>
                        <th class="bg-primary text-white" style="min-width:55px;" rowspan="2">Total<br/>Hadir</th>
                      </tr>
                      <tr>
                        <th
                          v-for="day in mergedDays" :key="'dn-'+day.day"
                          class="text-white"
                          :class="day.is_off ? 'bg-secondary' : 'bg-info'"
                          style="padding:2px;font-size:10px;"
                        >{{ day.day_name }}</th>
                        <th class="bg-success   text-white" style="min-width:34px;">H</th>
                        <th class="bg-warning   text-dark"  style="min-width:34px;">T</th>
                        <th class="bg-secondary text-white" style="min-width:34px;">L</th>
                        <th style="min-width:34px;background:#8b5cf6;color:#fff;">N</th>
                        <th class="bg-danger    text-white" style="min-width:34px;">A</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>1</td>
                        <td class="text-start ps-2 fw-semibold">{{ store.reportData?.user?.fullname }}</td>
                        <td class="text-start ps-2 text-muted">{{ store.reportData?.user?.username }}</td>
                        <td
                          v-for="day in mergedDays" :key="'att-'+day.day"
                          :class="getCellClass(day)"
                          style="padding:2px;font-weight:600;font-size:11px;"
                          :style="day.check_in || day.check_out ? 'cursor:pointer;' : ''"
                          :title="getCellTooltip(day)"
                          @click="openDetail(day)"
                        >{{ getCellLabel(day) }}</td>
                        <td class="fw-bold text-success">{{ store.reportSummary.ONTIME }}</td>
                        <td class="fw-bold text-warning">{{ store.reportSummary.LATE }}</td>
                        <td class="fw-bold text-secondary">{{ liburWeekendCount }}</td>
                        <td class="fw-bold" style="color:#8b5cf6;">{{ liburNasionalCount }}</td>
                        <td class="fw-bold text-danger">{{ absenCount }}</td>
                        <td class="fw-bold text-primary">{{ store.reportSummary.TOTAL_HADIR }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="card-footer">
                <div class="row align-items-end">
                  <div class="col-md-8">
                    <p class="fw-semibold mb-2" style="font-size:13px;">Keterangan :</p>
                    <div class="d-flex flex-wrap gap-2">
                      <span class="badge bg-success   px-3 py-2">H = Hadir (Ontime)</span>
                      <span class="badge bg-warning text-dark px-3 py-2">T = Terlambat</span>
                      <span class="badge bg-secondary px-3 py-2">L = Libur Weekend</span>
                      <span class="badge px-3 py-2" style="background:#8b5cf6;">N = Libur Nasional</span>
                      <span class="badge bg-danger    px-3 py-2">A = Absen</span>
                    </div>
                  </div>
                  <div class="col-md-4 text-end mt-3 mt-md-0">
                    <p class="mb-0 text-muted" style="font-size:13px;">Jakarta, {{ signDate }}</p>
                    <p class="mb-4 text-muted" style="font-size:13px;">Mengetahui,</p>
                    <p class="fw-bold mb-0" style="font-size:13px;">Admin HR</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </template>

      <!-- Modal Detail Per Hari -->
      <div class="modal fade" id="modalDetailHari" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                Detail — {{ selectedDay?.date }}
                <span class="badge ms-2" :class="getStatusBadge(selectedDay?.status)">{{ selectedDay?.status ?? '-' }}</span>
                <span v-if="selectedDay?.is_holiday" class="badge ms-1" style="background:#8b5cf6;">
                  🔴 {{ selectedDay?.holiday_name }}
                </span>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" v-if="selectedDay">
              <!-- Badge libur nasional -->
              <div v-if="selectedDay.is_holiday" class="alert alert-warning py-2 mb-3" style="font-size:13px;">
                <i class="bx bx-calendar-event me-1"></i>
                Hari ini adalah <strong>Libur Nasional</strong>: {{ selectedDay.holiday_name }}
              </div>

              <p class="fw-bold text-success mb-2"><i class="bx bx-log-in me-1"></i>Check In</p>
              <div v-if="selectedDay.check_in" class="d-flex gap-3 align-items-start mb-3">
                <img v-if="selectedDay.check_in.photo_url" :src="selectedDay.check_in.photo_url" class="rounded" style="width:80px;height:80px;object-fit:cover;" />
                <div style="font-size:13px;">
                  <p class="mb-1"><i class="bx bx-time me-1"></i>{{ selectedDay.check_in.time }}</p>
                  <p class="mb-1 text-muted" style="font-size:11px;">{{ selectedDay.check_in.location_name }}</p>
                  <div class="d-flex flex-wrap gap-1">
                    <span class="badge" :class="selectedDay.check_in.policy_status === 'ALLOWED' ? 'bg-success' : 'bg-danger'">{{ selectedDay.check_in.policy_status }}</span>
                    <span class="badge bg-secondary">{{ selectedDay.check_in.attendance_mode }}</span>
                    <span class="badge bg-info">{{ selectedDay.check_in.device_type }}</span>
                  </div>
                </div>
              </div>
              <p v-else class="text-muted mb-3" style="font-size:13px;">Tidak ada data check in</p>
              <hr class="my-2" />
              <p class="fw-bold text-danger mb-2"><i class="bx bx-log-out me-1"></i>Check Out</p>
              <div v-if="selectedDay.check_out" class="d-flex gap-3 align-items-start">
                <img v-if="selectedDay.check_out.photo_url" :src="selectedDay.check_out.photo_url" class="rounded" style="width:80px;height:80px;object-fit:cover;" />
                <div style="font-size:13px;">
                  <p class="mb-1"><i class="bx bx-time me-1"></i>{{ selectedDay.check_out.time }}</p>
                  <p class="mb-1 text-muted" style="font-size:11px;">{{ selectedDay.check_out.location_name }}</p>
                  <div class="d-flex flex-wrap gap-1">
                    <span class="badge" :class="selectedDay.check_out.policy_status === 'ALLOWED' ? 'bg-success' : 'bg-danger'">{{ selectedDay.check_out.policy_status }}</span>
                    <span class="badge bg-secondary">{{ selectedDay.check_out.attendance_mode }}</span>
                  </div>
                </div>
              </div>
              <p v-else class="text-muted" style="font-size:13px;">Tidak ada data check out</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </backendLayouts>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useDataAttendanceStore } from '../../../../stores/AttendanceFreeLocationStore';
import { exportReportExcel, exportReportPdf } from '../../../../stores/Useexportattendance';
import { useHolidayStore } from '../../../../stores/Useholidaystore';

const store        = useDataAttendanceStore()
const holidayStore = useHolidayStore()
const selectedDay  = ref(null)

const bulanList = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']
const yearList  = [2024, 2025, 2026]

// =============================================
// MERGED DAYS (attendance + libur nasional)
// =============================================
const mergedDays = computed(() =>
  holidayStore.mergeHolidaysWithDays(
    store.attendanceDays,
    store.selectedMonth,
    store.selectedYear
  )
)

const holidaysThisMonth = computed(() =>
  holidayStore.getHolidaysByMonth(store.selectedMonth, store.selectedYear)
)

// =============================================
// CELL LOGIC (mengenal is_holiday)
// =============================================
function getCellLabel(day) {
  if (day.is_holiday && !day.check_in) return 'N'   // libur nasional, tidak masuk
  if (day.is_weekend && !day.check_in) return 'L'
  if (!day.check_in) return ''
  if (day.status === 'LATE') return 'T'
  return 'H'
}

function getCellClass(day) {
  if (day.is_holiday && !day.check_in) return 'bg-opacity-25'   // ungu muda
  if (day.is_off && !day.check_in)     return 'table-secondary'
  if (!day.check_in)                   return ''
  if (day.status === 'LATE')           return 'table-warning'
  return 'table-success'
}

function getCellStyle(day) {
  if (day.is_holiday && !day.check_in) return 'background:#ede9fe;color:#7c3aed;font-weight:700;'
  return ''
}

function getCellTooltip(day) {
  let tip = ''
  if (day.is_holiday) tip = `🔴 ${day.holiday_name} | `
  if (day.is_weekend && !day.is_holiday) tip += 'Weekend | '
  if (!day.check_in) return tip + 'Tidak ada absensi'
  const out = day.check_out?.time ?? 'Belum checkout'
  return tip + `IN: ${day.check_in.time} | OUT: ${out}`
}

// =============================================
// SUMMARY
// =============================================
const liburWeekendCount  = computed(() => mergedDays.value.filter(d => d.is_weekend && !d.is_holiday).length)
const liburNasionalCount = computed(() => mergedDays.value.filter(d => d.is_holiday && !d.check_in).length)
const absenCount         = computed(() => mergedDays.value.filter(d => !d.is_off && !d.is_holiday && !d.check_in).length)

const summaryStats = computed(() => [
  { label: 'Hadir',        value: store.reportSummary.TOTAL_HADIR,    icon: 'bx-check-circle', color: '#28a745' },
  { label: 'Ontime',       value: store.reportSummary.ONTIME,         icon: 'bx-trophy',       color: '#696cff' },
  { label: 'Terlambat',    value: store.reportSummary.LATE,           icon: 'bx-time',         color: '#ffc107' },
  { label: 'Checkout',     value: store.reportSummary.TOTAL_CHECKOUT, icon: 'bx-log-out',      color: '#17a2b8' },
  { label: 'Libur Nasional', value: liburNasionalCount.value,         icon: 'bx-calendar-star', color: '#8b5cf6' },
  { label: 'Absen',        value: absenCount.value,                    icon: 'bx-x-circle',     color: '#dc3545' },
])

const signDate = computed(() => {
  const d = new Date(store.selectedYear, store.selectedMonth, 1)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
})

function formatTanggal(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'long' })
}

// =============================================
// FILTER (load absensi + libur sekaligus)
// =============================================
async function onChangeMonth(val) {
  store.changeMonth(val)
  await holidayStore.fetchHolidays(val, store.selectedYear)
}

async function onChangeYear(val) {
  store.changeYear(val)
  await holidayStore.fetchHolidays(store.selectedMonth, val)
}

// =============================================
// EXPORT
// =============================================
function handleExportExcel() {
  exportReportExcel(store.reportData, mergedDays.value, store.reportSummary)
}
function handleExportPdf() {
  exportReportPdf(store.reportData, mergedDays.value, store.reportSummary)
}

// =============================================
// MODAL
// =============================================
function openDetail(day) {
  if (!day.check_in && !day.check_out && !day.is_holiday) return
  selectedDay.value = day
  new window.bootstrap.Modal(document.getElementById('modalDetailHari')).show()
}

function getStatusBadge(status) {
  const map = { ONTIME: 'bg-success', LATE: 'bg-warning text-dark', COMPLETED: 'bg-info' }
  return map[status] ?? 'bg-light text-dark border'
}

// =============================================
// INIT
// =============================================
onMounted(async () => {
  await store.fetchMyReport()
  await holidayStore.fetchHolidays(store.selectedMonth, store.selectedYear)
})
</script>

<style scoped>
/* Cell libur nasional (ungu muda) */
td.bg-opacity-25 {
  background-color: #ede9fe !important;
  color: #7c3aed !important;
  font-weight: 700;
}
</style>
