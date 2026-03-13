<template>
  <backendLayouts>
    <div class="container-xxl flex-grow-1 container-p-y">
      <h4 class="fw-bold py-3">
        <span class="text-muted fw-light">HR /</span> Laporan Absensi
      </h4>

      <div class="row">
        <div class="col-md-12">
          <div class="card">

            <!-- Card Header -->
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <h5 class="mb-0">Absensi Karyawan PT Sebutu Jaya</h5>
                <small class="text-muted">Periode: {{ selectedPeriod }}</small>
              </div>
              <div class="d-flex gap-2 align-items-center flex-wrap">
                <!-- Filter Bulan -->
                <select class="form-select form-select-sm" v-model="selectedMonth" style="width: auto;">
                  <option v-for="(bln, idx) in bulanList" :key="idx" :value="idx + 1">{{ bln }}</option>
                </select>
                <!-- Filter Tahun -->
                <select class="form-select form-select-sm" v-model="selectedYear" style="width: auto;">
                  <option v-for="yr in yearList" :key="yr" :value="yr">{{ yr }}</option>
                </select>
                <button class="btn btn-danger btn-sm" @click="exportData">
                  <i class="bx bx-download me-1"></i> Export
                </button>
              </div>
            </div>

            <!-- Card Body -->
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle" style="min-width: 1400px; font-size: 12px;">

                  <!-- Table Head -->
                  <thead>
                    <tr>
                      <th rowspan="2" class="bg-primary text-white" style="min-width: 50px;">No</th>
                      <th rowspan="2" class="bg-primary text-white" style="min-width: 110px;">NIP</th>
                      <th rowspan="2" class="bg-primary text-white" style="min-width: 130px;">Nama</th>
                      <th rowspan="2" class="bg-primary text-white" style="min-width: 130px;">Jabatan</th>
                      <!-- Tanggal 1 - jumlah hari -->
                      <th
                        v-for="day in daysInMonth"
                        :key="day"
                        class="text-white"
                        :class="isWeekend(day) ? 'bg-secondary' : 'bg-primary'"
                        style="min-width: 32px; padding: 4px 2px;"
                      >
                        {{ day }}
                      </th>
                      <!-- Keterangan -->
                      <th colspan="6" class="bg-primary text-white">Keterangan</th>
                      <th rowspan="2" class="bg-primary text-white" style="min-width: 55px;">Total<br/>Hadir</th>
                    </tr>
                    <tr>
                      <th
                        v-for="day in daysInMonth"
                        :key="'dow-' + day"
                        class="text-white"
                        :class="isWeekend(day) ? 'bg-secondary' : 'bg-info'"
                        style="padding: 2px; font-size: 10px;"
                      >
                        {{ getDayName(day) }}
                      </th>
                      <th class="bg-success text-white" style="min-width: 38px;">H</th>
                      <th class="bg-warning text-dark" style="min-width: 38px;">S</th>
                      <th class="bg-info text-white" style="min-width: 38px;">I</th>
                      <th class="bg-danger text-white" style="min-width: 38px;">A</th>
                      <th style="min-width: 38px; background:#17a2b8; color:#fff">C</th>
                      <th class="bg-secondary text-white" style="min-width: 38px;">L</th>
                    </tr>
                  </thead>

                  <!-- Table Body -->
                  <tbody>
                    <tr v-for="(emp, idx) in employees" :key="emp.nip">
                      <td>{{ idx + 1 }}</td>
                      <td>{{ emp.nip }}</td>
                      <td class="text-start ps-2">{{ emp.nama }}</td>
                      <td class="text-start ps-2">{{ emp.jabatan }}</td>

                      <!-- Kolom per hari -->
                      <td
                        v-for="day in daysInMonth"
                        :key="'att-' + day"
                        :class="getCellClass(emp.attendance[day])"
                        style="padding: 2px; font-weight: 600; font-size: 11px;"
                      >
                        {{ emp.attendance[day] || '' }}
                      </td>

                      <!-- Rekap Keterangan -->
                      <td class="fw-bold text-success">{{ countStatus(emp, 'H') }}</td>
                      <td class="fw-bold text-warning">{{ countStatus(emp, 'S') }}</td>
                      <td class="fw-bold text-info">{{ countStatus(emp, 'I') }}</td>
                      <td class="fw-bold text-danger">{{ countStatus(emp, 'A') }}</td>
                      <td class="fw-bold" style="color:#17a2b8">{{ countStatus(emp, 'C') }}</td>
                      <td class="fw-bold text-secondary">{{ countStatus(emp, 'L') }}</td>

                      <!-- Total Hadir -->
                      <td class="fw-bold text-primary">{{ countStatus(emp, 'H') }}</td>
                    </tr>
                  </tbody>

                </table>
              </div>
            </div>

            <!-- Card Footer: Keterangan Legend + TTD -->
            <div class="card-footer">
              <div class="row">
                <!-- Legenda -->
                <div class="col-md-6">
                  <p class="fw-bold mb-2" style="font-size: 13px;">Keterangan :</p>
                  <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-success px-3 py-2">H = Hadir</span>
                    <span class="badge bg-warning text-dark px-3 py-2">S = Sakit</span>
                    <span class="badge bg-info px-3 py-2">I = Izin</span>
                    <span class="badge bg-danger px-3 py-2">A = Alfa</span>
                    <span class="badge px-3 py-2" style="background:#17a2b8; color:#fff">C = Cuti</span>
                    <span class="badge bg-secondary px-3 py-2">L = Libur</span>
                  </div>
                </div>

                <!-- Tanda Tangan -->
                <div class="col-md-6 text-end">
                  <p class="mb-0" style="font-size: 13px;">Jakarta, {{ signDate }}</p>
                  <p class="mb-4" style="font-size: 13px;">Dibuat oleh,</p>
                  <p class="fw-bold mb-0" style="font-size: 13px;">Anton</p>
                  <p class="text-muted" style="font-size: 12px;">Admin HR</p>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </backendLayouts>
</template>

<script setup>
import { ref, computed } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";

// === State ===
const selectedMonth = ref(8)  // Agustus
const selectedYear  = ref(2021)

const bulanList = [
  'Januari','Februari','Maret','April','Mei','Juni',
  'Juli','Agustus','September','Oktober','November','Desember'
]
const yearList = [2020, 2021, 2022, 2023, 2024, 2025]

// === Computed ===
const selectedPeriod = computed(() =>
  `${bulanList[selectedMonth.value - 1]} ${selectedYear.value}`
)

const daysInMonth = computed(() => {
  const total = new Date(selectedYear.value, selectedMonth.value, 0).getDate()
  return Array.from({ length: total }, (_, i) => i + 1)
})

const signDate = computed(() => {
  const d = new Date(selectedYear.value, selectedMonth.value, 1)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
})

// === Helpers ===
function isWeekend(day) {
  const d = new Date(selectedYear.value, selectedMonth.value - 1, day)
  return d.getDay() === 0 // Sunday
}

function getDayName(day) {
  const d = new Date(selectedYear.value, selectedMonth.value - 1, day)
  return ['M','S','S','R','K','J','S'][d.getDay()]
}

function getCellClass(status) {
  if (!status) return ''
  const map = {
    H: 'table-success',
    S: 'table-warning',
    I: 'table-info',
    A: 'table-danger',
    C: 'bg-info bg-opacity-25',
    L: 'table-secondary',
  }
  return map[status] || ''
}

function countStatus(emp, status) {
  return Object.values(emp.attendance).filter(v => v === status).length
}

function exportData() {
  alert('Fitur export akan diimplementasikan sesuai kebutuhan (Excel/PDF).')
}

// === Sample Data ===
// Hari libur Agustus 2021: Minggu = 1,8,15,22,29 + 17 Agustus
function buildAttendance(nip) {
  const att = {}
  const sundays = [1, 8, 15, 22, 29]
  const holiday = [17]
  const days = new Date(2021, 8, 0).getDate() // 31
  const seed = nip.charCodeAt(nip.length - 1)
  const choices = ['H','H','H','H','H','H','S','I','A','C']

  for (let d = 1; d <= days; d++) {
    if (sundays.includes(d) || holiday.includes(d)) {
      att[d] = 'L'
    } else {
      const pick = choices[(d * seed) % choices.length]
      att[d] = pick
    }
  }
  return att
}

const employees = ref([
  { nip: '27/002', nama: 'Agil',    jabatan: 'Graphic Design',        attendance: buildAttendance('27/002') },
  { nip: '27/003', nama: 'Rima',    jabatan: 'Administrasi Commerce',  attendance: buildAttendance('27/003') },
  { nip: '27/004', nama: 'Cinthya', jabatan: 'Social Media',           attendance: buildAttendance('27/004') },
  { nip: '27/005', nama: 'Deni',    jabatan: 'Stock Checker',          attendance: buildAttendance('27/005') },
  { nip: '27/006', nama: 'Tika',    jabatan: 'Marketing',              attendance: buildAttendance('27/006') },
])
</script>