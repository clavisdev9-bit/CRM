<template>
  <backendLayouts>

    <div class="page-header d-print-none">
      <div class="container-xl">
        <div class="row g-2 align-items-center">
          <div class="col">
            <div class="page-pretitle">Dashboard IT Page</div>
            <h4 class="page-title">Dashboard IT Page</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="container-xxl flex-grow-1 container-p-y">

      <!-- WELCOME -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card welcome-card">
            <div class="d-flex align-items-end row">
              <div class="col-sm-8">
                <div class="card-body">
                  <h5 class="card-title text-primary mb-1">
                    Welcome, {{ auth.user?.fullname || '...' }} 👋
                  </h5>
                  <p class="text-muted mb-0" style="font-size:13px;">
                    System berjalan normal · Database
                    <strong style="color:#696cff">{{ stats.storage?.db_size }}</strong> ·
                    Storage terpakai
                    <strong :style="{ color: storageColor }">{{ stats.storage?.used_percent }}%</strong>
                  </p>
                </div>
              </div>
              <div class="col-sm-4 text-center">
                <div class="card-body pb-0">
                  <img :src="bannerImages" height="120" alt="banner" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- STAT CARDS -->
      <div class="row mb-4">
        <div class="col-6 col-lg-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#eeedff">
              <i class="bx bx-group" style="color:#696cff"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Total Users</div>
              <div class="stat-value" style="color:#696cff">{{ stats.users?.total ?? 0 }}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#eafbdf">
              <i class="bx bx-check-circle" style="color:#71dd37"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Users Aktif</div>
              <div class="stat-value" style="color:#71dd37">{{ stats.users?.active ?? 0 }}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#e0f7fc">
              <i class="bx bx-user-plus" style="color:#03c3ec"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">User Baru Bulan Ini</div>
              <div class="stat-value" style="color:#03c3ec">{{ stats.users?.new_this_month ?? 0 }}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#fff8e1">
              <i class="bx bx-briefcase" style="color:#ffab00"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Total Employees</div>
              <div class="stat-value" style="color:#ffab00">{{ stats.employees?.total ?? 0 }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-4">

        <!-- USER PER ROLE -->
        <div class="col-lg-4 mb-4">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-shield" style="color:#696cff"></i>
                Users per Role
              </div>
            </div>
            <div class="role-list">
              <div class="role-item" v-for="r in stats.users?.per_role" :key="r.role">
                <div class="role-left">
                  <div class="role-icon" :style="{ background: roleColor(r.role) + '22' }">
                    <i class="bx bx-user" :style="{ color: roleColor(r.role) }"></i>
                  </div>
                  <div>
                    <div class="role-name">{{ r.role }}</div>
                    <div class="role-active">{{ r.active }} aktif</div>
                  </div>
                </div>
                <div class="role-right">
                  <div class="role-total" :style="{ color: roleColor(r.role) }">{{ r.total }}</div>
                  <div class="role-bar-wrap">
                    <div
                      class="role-bar"
                      :style="{
                        width: (r.total / stats.users?.total * 100) + '%',
                        background: roleColor(r.role)
                      }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Attendance Mode -->
            <div class="section-divider"></div>
            <div class="section-subtitle">Attendance Mode</div>
            <div class="mode-list">
              <div class="mode-item" v-for="m in stats.employees?.per_mode" :key="m.attendance_mode">
                <span class="mode-badge" :class="modeClass(m.attendance_mode)">
                  {{ m.attendance_mode }}
                </span>
                <span class="mode-total">{{ m.total }} karyawan</span>
              </div>
            </div>
          </div>
        </div>

        <!-- LOGIN ACTIVITY CHART -->
        <div class="col-lg-8 mb-4">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-line-chart" style="color:#03c3ec"></i>
                Aktivitas Login
              </div>
              <span style="font-size:11px;color:#9ca3af;">7 hari terakhir</span>
            </div>
            <div class="chart-body">
              <canvas ref="loginChartRef"></canvas>
            </div>
          </div>
        </div>

      </div>

      <div class="row">

        <!-- USER GROWTH CHART -->
        <div class="col-lg-6 mb-4">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-trending-up" style="color:#71dd37"></i>
                Pertumbuhan User
              </div>
              <span style="font-size:11px;color:#9ca3af;">6 bulan terakhir</span>
            </div>
            <div class="chart-body">
              <canvas ref="growthChartRef"></canvas>
            </div>
          </div>
        </div>

        <!-- STORAGE -->
        <div class="col-lg-6 mb-4">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-hdd" style="color:#ffab00"></i>
                Storage & Database
              </div>
            </div>

            <!-- Storage Bar -->
            <div class="storage-section">
              <div class="storage-label-row">
                <span class="storage-label">Disk Usage</span>
                <span class="storage-pct" :style="{ color: storageColor }">
                  {{ stats.storage?.used_percent }}%
                </span>
              </div>
              <div class="storage-bar-bg">
                <div
                  class="storage-bar-fill"
                  :style="{
                    width: Math.min(stats.storage?.used_percent ?? 0, 100) + '%',
                    background: storageColor
                  }"
                ></div>
              </div>
              <div class="storage-detail-row">
                <div class="storage-detail-item">
                  <div class="storage-detail-val">{{ stats.storage?.used }}</div>
                  <div class="storage-detail-label">Terpakai</div>
                </div>
                <div class="storage-detail-item">
                  <div class="storage-detail-val" style="color:#71dd37">{{ stats.storage?.free }}</div>
                  <div class="storage-detail-label">Tersedia</div>
                </div>
                <div class="storage-detail-item">
                  <div class="storage-detail-val" style="color:#9ca3af">{{ stats.storage?.total }}</div>
                  <div class="storage-detail-label">Total</div>
                </div>
              </div>
            </div>

            <div class="section-divider"></div>

            <!-- DB Size -->
            <div class="db-section">
              <div class="db-icon">
                <i class="bx bx-data" style="color:#696cff;font-size:28px;"></i>
              </div>
              <div class="db-info">
                <div class="db-label">Database Size</div>
                <div class="db-value" style="color:#696cff">{{ stats.storage?.db_size }}</div>
                <div class="db-sub">PostgreSQL · {{ dbName }}</div>
              </div>
            </div>

          </div>
        </div>

      </div>

    </div>
  </backendLayouts>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import backendLayouts from '../../../../layouts/backendLayouts.vue'
import { exportsLoginStore } from '@/stores/loginStore'

const auth = exportsLoginStore()
const bannerImages = '/images/man-with-laptop-light.png'
const stats = ref({})
const dbName = window.location.hostname

const loginChartRef  = ref(null)
const growthChartRef = ref(null)
let loginChartInstance  = null
let growthChartInstance = null

// --- COMPUTED ---
const storageColor = computed(() => {
  const p = stats.value.storage?.used_percent ?? 0
  if (p >= 80) return '#ff3e1d'
  if (p >= 60) return '#ffab00'
  return '#71dd37'
})

const roleColor = (role) => {
  const map = {
    administrator: '#696cff',
    manager:       '#03c3ec',
    sales:         '#71dd37',
    it:            '#ffab00',
  }
  return map[role?.toLowerCase()] ?? '#9ca3af'
}

const modeClass = (mode) => {
  const map = {
    OFFICE: 'mode-office',
    FREE:   'mode-free',
    WFH:    'mode-wfh',
    HYBRID: 'mode-hybrid',
  }
  return map[mode] ?? 'mode-office'
}

// --- CHARTS ---
const renderLoginChart = () => {
  if (!loginChartRef.value) return
  const Chart = window.Chart
  if (loginChartInstance) loginChartInstance.destroy()

  loginChartInstance = new Chart(loginChartRef.value, {
    type: 'bar',
    data: {
      labels: stats.value.login_activity?.labels ?? [],
      datasets: [{
        label: 'Active Users',
        data: stats.value.login_activity?.data ?? [],
        backgroundColor: 'rgba(3,195,236,0.8)',
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } } },
        y: { grid: { color: '#f1f5f9' }, ticks: { color: '#9ca3af', font: { size: 11 }, stepSize: 1 } }
      }
    }
  })
}

const renderGrowthChart = () => {
  if (!growthChartRef.value) return
  const Chart = window.Chart
  if (growthChartInstance) growthChartInstance.destroy()

  growthChartInstance = new Chart(growthChartRef.value, {
    type: 'line',
    data: {
      labels: stats.value.users?.growth_labels ?? [],
      datasets: [{
        label: 'New Users',
        data: stats.value.users?.growth_data ?? [],
        borderColor: '#71dd37',
        backgroundColor: 'rgba(113,221,55,0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#71dd37',
        pointRadius: 5,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } } },
        y: { grid: { color: '#f1f5f9' }, ticks: { color: '#9ca3af', font: { size: 11 }, stepSize: 1 } }
      }
    }
  })
}

// --- FETCH ---
const fetchDashboard = async () => {
  try {
    const res = await axios.get('/api/dashboard/it')
    stats.value = res.data.data ?? {}
    await nextTick()
    renderLoginChart()
    renderGrowthChart()
  } catch (e) {
    console.error('Failed to fetch IT dashboard:', e)
  }
}

onMounted(async () => {
  if (!auth.user) await auth.fetchProfile()

  if (!window.Chart) {
    await new Promise((resolve) => {
      const script = document.createElement('script')
      script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
      script.onload = resolve
      document.head.appendChild(script)
    })
  }

  await fetchDashboard()
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap');
* { box-sizing: border-box; }

.welcome-card { border: none; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }

/* STAT CARDS */
.stat-card {
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 12px; padding: 16px;
  display: flex; align-items: center; gap: 14px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  transition: transform 0.2s, box-shadow 0.2s;
  height: 100%;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.stat-icon {
  width: 48px; height: 48px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.stat-icon i { font-size: 22px; }
.stat-label { font-size: 12px; color: #9ca3af; margin-bottom: 4px; }
.stat-value { font-size: 24px; font-weight: 700; font-family: 'Space Mono', monospace; line-height: 1; }

/* SECTION CARD */
.section-card {
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 12px; padding: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  height: 100%;
}
.section-header {
  display: flex; align-items: center;
  justify-content: space-between; margin-bottom: 16px;
}
.section-title {
  display: flex; align-items: center; gap: 8px;
  font-size: 14px; font-weight: 600; color: #111;
}
.section-title i { font-size: 18px; }
.section-divider { border-top: 1px solid #f1f5f9; margin: 16px 0; }
.section-subtitle { font-size: 12px; font-weight: 600; color: #9ca3af; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }

/* ROLE LIST */
.role-list { display: flex; flex-direction: column; gap: 12px; }
.role-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.role-left { display: flex; align-items: center; gap: 10px; }
.role-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.role-icon i { font-size: 16px; }
.role-name { font-size: 13px; font-weight: 500; color: #111; text-transform: capitalize; }
.role-active { font-size: 11px; color: #9ca3af; }
.role-right { text-align: right; min-width: 80px; }
.role-total { font-size: 15px; font-weight: 700; font-family: 'Space Mono', monospace; }
.role-bar-wrap { height: 3px; background: #f1f5f9; border-radius: 4px; margin-top: 4px; }
.role-bar { height: 100%; border-radius: 4px; transition: width 1s ease; }

/* MODE */
.mode-list { display: flex; flex-wrap: wrap; gap: 8px; }
.mode-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280; }
.mode-badge {
  font-size: 10px; font-weight: 700;
  padding: 3px 8px; border-radius: 20px;
}
.mode-office { background: rgba(105,108,255,0.12); color: #696cff; }
.mode-free   { background: rgba(113,221,55,0.12);  color: #16a34a; }
.mode-wfh    { background: rgba(3,195,236,0.12);   color: #0ea5e9; }
.mode-hybrid { background: rgba(255,171,0,0.12);   color: #d97706; }

/* CHART */
.chart-body { height: 220px; position: relative; }

/* STORAGE */
.storage-section { margin-bottom: 4px; }
.storage-label-row {
  display: flex; justify-content: space-between;
  font-size: 13px; font-weight: 500; margin-bottom: 8px;
}
.storage-label { color: #6b7280; }
.storage-pct { font-family: 'Space Mono', monospace; font-weight: 700; }
.storage-bar-bg {
  height: 10px; background: #f1f5f9;
  border-radius: 8px; overflow: hidden; margin-bottom: 16px;
}
.storage-bar-fill { height: 100%; border-radius: 8px; transition: width 1s ease; }
.storage-detail-row { display: flex; gap: 20px; }
.storage-detail-item { text-align: center; }
.storage-detail-val { font-size: 15px; font-weight: 700; font-family: 'Space Mono', monospace; color: #111; }
.storage-detail-label { font-size: 11px; color: #9ca3af; margin-top: 2px; }

/* DB */
.db-section { display: flex; align-items: center; gap: 16px; }
.db-icon {
  width: 56px; height: 56px; background: #eeedff;
  border-radius: 12px; display: flex;
  align-items: center; justify-content: center; flex-shrink: 0;
}
.db-label { font-size: 12px; color: #9ca3af; }
.db-value { font-size: 22px; font-weight: 700; font-family: 'Space Mono', monospace; }
.db-sub { font-size: 11px; color: #9ca3af; margin-top: 2px; }
</style>