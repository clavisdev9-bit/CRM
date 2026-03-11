<template>
  <backendLayouts>

    <div class="page-header d-print-none">
      <div class="container-xl">
        <div class="row g-2 align-items-center">
          <div class="col">
            <div class="page-pretitle">Manager Page</div>
            <h4 class="page-title">Manager Dashboard</h4>
          </div>
          <div class="col-auto">
            <span class="last-sync">
              <span class="sync-dot"></span>
              Updated {{ lastUpdated }}
            </span>
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
                  <p class="text-muted mb-2" style="font-size:13px;">
                    Bulan ini tim kamu menghasilkan
                    <strong style="color:#71dd37">{{ stats.summary?.visits_this_month ?? 0 }}</strong> visit,
                    <strong style="color:#696cff">{{ stats.summary?.customers_this_month ?? 0 }}</strong> customer baru
                    <span v-if="stats.summary?.overdue_follow_ups > 0">
                      dan ada <strong style="color:#ff3e1d">{{ stats.summary?.overdue_follow_ups }}</strong> follow up overdue.
                    </span>
                    <span v-else>dan semua follow up on track! 🎉</span>
                  </p>
                  <div style="font-size:12px;color:#9ca3af;">{{ currentMonth }}</div>
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

      <!-- SUMMARY CARDS -->
      <div class="row mb-4">
        <div class="col-6 col-lg-2 mb-3" v-for="(card, i) in summaryCards" :key="i">
          <div class="stat-card">
            <div class="stat-icon" :style="{ background: card.bg }">
              <i :class="card.icon" :style="{ color: card.color }"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">{{ card.label }}</div>
              <div class="stat-value" :style="{ color: card.color }">{{ card.value }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-4">

        <!-- SALES PERFORMANCE -->
        <div class="col-lg-5 mb-4">
          <div class="section-card h-100">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-trophy" style="color:#ffab00"></i>
                Performa Tim Sales
              </div>
              <span style="font-size:11px;color:#9ca3af;">Bulan ini</span>
            </div>
            <div class="perf-list">
              <div
                class="perf-item"
                v-for="(s, i) in stats.sales_performance"
                :key="s.sales_id"
              >
                <div class="perf-rank" :class="'rank-' + (i+1)">{{ i + 1 }}</div>
                <img :src="s.sales_photo_url" class="perf-avatar" />
                <div class="perf-info">
                  <div class="perf-name">{{ s.sales_name }}</div>
                  <div class="perf-stats-row">
                    <span class="perf-stat-item" style="color:#71dd37">
                      <i class="bx bx-check"></i> {{ s.done }}
                    </span>
                    <span class="perf-stat-item" style="color:#03c3ec">
                      <i class="bx bx-walk"></i> {{ s.ongoing }}
                    </span>
                    <span class="perf-stat-item" style="color:#ffab00">
                      <i class="bx bx-time"></i> {{ s.planned }}
                    </span>
                    <span class="perf-stat-item" style="color:#696cff" v-if="s.deals > 0">
                      <i class="bx bx-dollar"></i> {{ s.deals }} deal
                    </span>
                  </div>
                  <div class="perf-bar-wrap">
                    <div
                      class="perf-bar"
                      :style="{
                        width: (s.total_visits / maxVisits * 100) + '%',
                        background: barColor(i)
                      }"
                    ></div>
                  </div>
                </div>
                <div class="perf-total" :style="{ color: barColor(i) }">
                  {{ s.total_visits }}
                </div>
              </div>
              <div class="empty-state" v-if="!stats.sales_performance?.length">
                <i class="bx bx-trophy"></i>
                <p>Belum ada data</p>
              </div>
            </div>

            <!-- Inactive Sales -->
            <div v-if="stats.inactive_sales?.length">
              <div class="section-divider"></div>
              <div class="inactive-header">
                <i class="bx bx-error-circle" style="color:#ff3e1d"></i>
                Sales Belum Ada Aktivitas Hari Ini
              </div>
              <div class="inactive-list">
                <div class="inactive-item" v-for="s in stats.inactive_sales" :key="s.id_user">
                  <img :src="s.photo_url" class="inactive-avatar" />
                  <span class="inactive-name">{{ s.fullname }}</span>
                  <span class="inactive-badge">Tidak Aktif</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CONVERSION RATE -->
        <div class="col-lg-3 mb-4">
          <div class="section-card h-100">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-line-chart" style="color:#696cff"></i>
                Conversion Rate
              </div>
            </div>

            <!-- Lead Rate -->
            <div class="conv-block">
              <div class="conv-label-row">
                <span class="conv-label">Lead → Customer</span>
                <span class="conv-pct" style="color:#71dd37">{{ stats.conversion?.lead_rate ?? 0 }}%</span>
              </div>
              <div class="conv-bar-bg">
                <div class="conv-bar-fill" :style="{
                  width: (stats.conversion?.lead_rate ?? 0) + '%',
                  background: '#71dd37'
                }"></div>
              </div>
              <div class="conv-detail">
                <span>{{ stats.conversion?.converted_leads }} converted</span>
                <span>dari {{ stats.conversion?.total_leads }} leads</span>
              </div>
            </div>

            <div class="section-divider"></div>

            <!-- Deal Rate -->
            <div class="conv-block">
              <div class="conv-label-row">
                <span class="conv-label">Customer → Deal</span>
                <span class="conv-pct" style="color:#696cff">{{ stats.conversion?.deal_rate ?? 0 }}%</span>
              </div>
              <div class="conv-bar-bg">
                <div class="conv-bar-fill" :style="{
                  width: (stats.conversion?.deal_rate ?? 0) + '%',
                  background: '#696cff'
                }"></div>
              </div>
              <div class="conv-detail">
                <span>{{ stats.conversion?.deals }} deal</span>
                <span>dari {{ stats.conversion?.total_customers }} customers</span>
              </div>
            </div>

            <div class="section-divider"></div>

            <!-- Circle summary -->
            <div class="conv-circles">
              <div class="conv-circle-item">
                <svg viewBox="0 0 100 100" width="80" height="80">
                  <circle cx="50" cy="50" r="38" fill="none" stroke="#f1f5f9" stroke-width="10"/>
                  <circle cx="50" cy="50" r="38" fill="none" stroke="#71dd37" stroke-width="10"
                    stroke-linecap="round"
                    :stroke-dasharray="`${(stats.conversion?.lead_rate ?? 0) * 2.39} 239`"
                    stroke-dashoffset="59.75"
                    transform="rotate(-90 50 50)"
                    style="transition: stroke-dasharray 1s ease"
                  />
                </svg>
                <div class="conv-circle-text">
                  <div class="conv-circle-val" style="color:#71dd37">{{ stats.conversion?.lead_rate ?? 0 }}%</div>
                  <div class="conv-circle-label">Lead</div>
                </div>
              </div>
              <div class="conv-circle-item">
                <svg viewBox="0 0 100 100" width="80" height="80">
                  <circle cx="50" cy="50" r="38" fill="none" stroke="#f1f5f9" stroke-width="10"/>
                  <circle cx="50" cy="50" r="38" fill="none" stroke="#696cff" stroke-width="10"
                    stroke-linecap="round"
                    :stroke-dasharray="`${(stats.conversion?.deal_rate ?? 0) * 2.39} 239`"
                    stroke-dashoffset="59.75"
                    transform="rotate(-90 50 50)"
                    style="transition: stroke-dasharray 1s ease"
                  />
                </svg>
                <div class="conv-circle-text">
                  <div class="conv-circle-val" style="color:#696cff">{{ stats.conversion?.deal_rate ?? 0 }}%</div>
                  <div class="conv-circle-label">Deal</div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- OVERDUE FOLLOW UPS -->
        <div class="col-lg-4 mb-4">
          <div class="section-card h-100">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-error-circle" style="color:#ff3e1d"></i>
                Follow Up Overdue
              </div>
              <span class="count-badge danger">{{ stats.overdue_follow_ups?.length ?? 0 }}</span>
            </div>
            <div class="fu-list">
              <div
                class="fu-item"
                v-for="fu in stats.overdue_follow_ups"
                :key="fu.id"
              >
                <img :src="fu.sales_photo_url" class="fu-avatar" />
                <div class="fu-info">
                  <div class="fu-sales">{{ fu.sales_name }}</div>
                  <div class="fu-company">
                    <span class="type-badge" :class="fu.target_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                      {{ fu.target_type }}
                    </span>
                    {{ fu.company_name }}
                  </div>
                  <div class="fu-meta">
                    <span class="fu-type-icon" :class="fuTypeClass(fu.follow_up_type)">
                      <i :class="fuTypeIcon(fu.follow_up_type)"></i>
                      {{ fu.follow_up_type }}
                    </span>
                    <span class="fu-time overdue">
                      <i class="bx bx-time"></i>
                      {{ formatTime(fu.follow_up_at) }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="empty-state" v-if="!stats.overdue_follow_ups?.length">
                <i class="bx bx-check-circle" style="color:#71dd37"></i>
                <p>Semua follow up on track! 🎉</p>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- VISIT HARI INI -->
      <div class="row">
        <div class="col-12">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-map-pin" style="color:#03c3ec"></i>
                Visit Hari Ini
                <span style="font-size:12px;color:#9ca3af;font-weight:400;">— semua sales</span>
              </div>
              <span class="count-badge">{{ stats.visits_today?.length ?? 0 }}</span>
            </div>
            <div class="visit-table-wrap">
              <table class="visit-table">
                <thead>
                  <tr>
                    <th>Sales</th>
                    <th>Perusahaan</th>
                    <th>Type</th>
                    <th>Waktu Visit</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="v in stats.visits_today" :key="v.id">
                    <td>
                      <div class="table-sales">
                        <img :src="v.sales_photo_url" class="table-avatar" />
                        <span>{{ v.sales_name }}</span>
                      </div>
                    </td>
                    <td>{{ v.company_name }}</td>
                    <td>
                      <span class="type-badge" :class="v.target_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                        {{ v.target_type }}
                      </span>
                    </td>
                    <td class="mono">{{ formatTime(v.visit_at) }}</td>
                    <td class="mono">{{ v.check_in_at ? formatTime(v.check_in_at) : '-' }}</td>
                    <td class="mono">{{ v.check_out_at ? formatTime(v.check_out_at) : '-' }}</td>
                    <td>
                      <span class="progress-badge" :class="progressClass(v.visit_progress)">
                        {{ v.visit_progress }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="!stats.visits_today?.length">
                    <td colspan="7" class="empty-row">Belum ada visit hari ini</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </backendLayouts>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import backendLayouts from '../../../../layouts/backendLayouts.vue'
import { exportsLoginStore } from '@/stores/loginStore'

const auth = exportsLoginStore()
const bannerImages = '/images/man-with-laptop-light.png'
const stats = ref({})
const lastUpdated = ref('-')
const currentMonth = new Date().toLocaleString('id-ID', { month: 'long', year: 'numeric' })
let pollingInterval = null

// --- COMPUTED ---
const summaryCards = computed(() => [
  { label: 'Leads Baru',      value: stats.value.summary?.leads_this_month ?? 0,     icon: 'bx bx-user-plus',       color: '#696cff', bg: '#eeedff' },
  { label: 'Customers Baru',  value: stats.value.summary?.customers_this_month ?? 0, icon: 'bx bx-group',           color: '#03c3ec', bg: '#e0f7fc' },
  { label: 'Visit Hari Ini',  value: stats.value.summary?.visits_today ?? 0,          icon: 'bx bx-map-pin',         color: '#71dd37', bg: '#eafbdf' },
  { label: 'Visit Bulan Ini', value: stats.value.summary?.visits_this_month ?? 0,     icon: 'bx bx-calendar-check',  color: '#ffab00', bg: '#fff8e1' },
  { label: 'Deals',           value: stats.value.summary?.deals_this_month ?? 0,      icon: 'bx bx-dollar-circle',   color: '#20c997', bg: '#e0faf3' },
  { label: 'Overdue FU',      value: stats.value.summary?.overdue_follow_ups ?? 0,    icon: 'bx bx-error-circle',    color: '#ff3e1d', bg: '#ffe8e5' },
])

const maxVisits = computed(() => {
  const perf = stats.value.sales_performance ?? []
  return Math.max(...perf.map(s => s.total_visits), 1)
})

// --- HELPERS ---
const barColor = (i) => {
  const colors = ['#ffab00', '#9ca3af', '#cd7c4f', '#696cff', '#03c3ec']
  return colors[i] ?? '#696cff'
}

const formatTime = (dt) => {
  if (!dt) return '-'
  return new Date(dt).toLocaleString('id-ID', {
    day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
  })
}

const progressClass = (p) => {
  if (p === 'PLANNED') return 'badge-planned'
  if (p === 'ONGOING') return 'badge-ongoing'
  if (p === 'DONE')    return 'badge-done'
  return 'badge-unknown'
}

const fuTypeClass = (type) => {
  const map = { CALL: 'fu-call', EMAIL: 'fu-email', WHATSAPP: 'fu-wa', MEETING: 'fu-meeting', VISIT: 'fu-visit', OTHER: 'fu-other' }
  return map[type] ?? 'fu-other'
}

const fuTypeIcon = (type) => {
  const map = { CALL: 'bx bx-phone', EMAIL: 'bx bx-envelope', WHATSAPP: 'bx bxl-whatsapp', MEETING: 'bx bx-group', VISIT: 'bx bx-map-pin', OTHER: 'bx bx-dots-horizontal' }
  return map[type] ?? 'bx bx-dots-horizontal'
}

// --- FETCH ---
const fetchDashboard = async () => {
  try {
    const res = await axios.get('/api/dashboard/manager')
    stats.value = res.data.data ?? {}
    lastUpdated.value = new Date().toLocaleTimeString('id-ID')
  } catch (e) {
    console.error('Failed to fetch manager dashboard:', e)
  }
}

onMounted(async () => {
  if (!auth.user) await auth.fetchProfile()
  await fetchDashboard()
  pollingInterval = setInterval(fetchDashboard, 30000) // realtime 30 detik
})

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap');
* { box-sizing: border-box; }

.welcome-card { border: none; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }

.last-sync {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; color: #9ca3af;
  font-family: 'Space Mono', monospace;
}
.sync-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #71dd37; box-shadow: 0 0 6px #71dd37;
  animation: pulse 2s infinite;
}

/* STAT CARDS */
.stat-card {
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 12px; padding: 14px;
  display: flex; align-items: center; gap: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  transition: transform 0.2s, box-shadow 0.2s;
  height: 100%;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.stat-icon {
  width: 44px; height: 44px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.stat-icon i { font-size: 20px; }
.stat-label { font-size: 11px; color: #9ca3af; margin-bottom: 3px; }
.stat-value { font-size: 22px; font-weight: 700; font-family: 'Space Mono', monospace; line-height: 1; }

/* SECTION CARD */
.section-card {
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 12px; padding: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
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

.count-badge { background: #eeedff; color: #696cff; font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 20px; }
.count-badge.danger { background: #ffe8e5; color: #ff3e1d; }

/* PERFORMANCE */
.perf-list { display: flex; flex-direction: column; gap: 14px; }
.perf-item { display: flex; align-items: center; gap: 10px; }
.perf-rank {
  width: 24px; height: 24px; border-radius: 50%;
  background: #f1f5f9; color: #6b7280;
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.rank-1 { background: #fef3c7; color: #d97706; }
.rank-2 { background: #f1f5f9; color: #475569; }
.rank-3 { background: #fde8d8; color: #c2410c; }
.perf-avatar { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; flex-shrink: 0; }
.perf-info { flex: 1; min-width: 0; }
.perf-name { font-size: 13px; font-weight: 500; color: #111; }
.perf-stats-row { display: flex; gap: 8px; margin: 3px 0; flex-wrap: wrap; }
.perf-stat-item { font-size: 11px; display: flex; align-items: center; gap: 2px; }
.perf-bar-wrap { height: 3px; background: #f1f5f9; border-radius: 4px; }
.perf-bar { height: 100%; border-radius: 4px; transition: width 1s ease; }
.perf-total { font-size: 18px; font-weight: 700; font-family: 'Space Mono', monospace; flex-shrink: 0; }

/* INACTIVE */
.inactive-header { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #ff3e1d; margin-bottom: 10px; }
.inactive-list { display: flex; flex-direction: column; gap: 8px; }
.inactive-item { display: flex; align-items: center; gap: 8px; }
.inactive-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; }
.inactive-name { flex: 1; font-size: 13px; color: #333; }
.inactive-badge { font-size: 9px; font-weight: 700; background: #ffe8e5; color: #ff3e1d; padding: 2px 7px; border-radius: 20px; }

/* CONVERSION */
.conv-block { margin-bottom: 4px; }
.conv-label-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px; }
.conv-label { color: #6b7280; font-weight: 500; }
.conv-pct { font-family: 'Space Mono', monospace; font-weight: 700; }
.conv-bar-bg { height: 8px; background: #f1f5f9; border-radius: 8px; overflow: hidden; margin-bottom: 6px; }
.conv-bar-fill { height: 100%; border-radius: 8px; transition: width 1s ease; }
.conv-detail { display: flex; justify-content: space-between; font-size: 11px; color: #9ca3af; }
.conv-circles { display: flex; justify-content: center; gap: 24px; margin-top: 8px; }
.conv-circle-item { position: relative; display: flex; align-items: center; justify-content: center; }
.conv-circle-text { position: absolute; text-align: center; }
.conv-circle-val { font-size: 14px; font-weight: 700; font-family: 'Space Mono', monospace; }
.conv-circle-label { font-size: 10px; color: #9ca3af; }

/* FOLLOW UP */
.fu-list { display: flex; flex-direction: column; gap: 12px; max-height: 320px; overflow-y: auto; }
.fu-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px; background: #fff8f8; border-radius: 8px; border-left: 3px solid #ff3e1d; }
.fu-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; flex-shrink: 0; }
.fu-info { flex: 1; min-width: 0; }
.fu-sales { font-size: 12px; font-weight: 600; color: #111; }
.fu-company { font-size: 11px; color: #6b7280; display: flex; align-items: center; gap: 4px; margin: 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fu-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.fu-type-icon { display: flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 20px; }
.fu-call    { background: rgba(105,108,255,0.12); color: #696cff; }
.fu-email   { background: rgba(255,171,0,0.12);   color: #d97706; }
.fu-wa      { background: rgba(37,211,102,0.12);  color: #16a34a; }
.fu-meeting { background: rgba(3,195,236,0.12);   color: #0ea5e9; }
.fu-visit   { background: rgba(255,62,29,0.12);   color: #dc2626; }
.fu-other   { background: rgba(156,163,175,0.15); color: #6b7280; }
.fu-time { font-size: 10px; color: #ff3e1d; font-family: 'Space Mono', monospace; display: flex; align-items: center; gap: 3px; }

/* VISIT TABLE */
.visit-table-wrap { overflow-x: auto; }
.visit-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.visit-table th {
  text-align: left; padding: 8px 12px;
  font-size: 11px; font-weight: 600; color: #9ca3af;
  text-transform: uppercase; letter-spacing: 0.5px;
  border-bottom: 2px solid #f1f5f9;
}
.visit-table td { padding: 10px 12px; border-bottom: 1px solid #f8f9fa; color: #333; }
.visit-table tr:last-child td { border-bottom: none; }
.visit-table tr:hover td { background: #f8f9fa; }
.table-sales { display: flex; align-items: center; gap: 8px; }
.table-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; }
.mono { font-family: 'Space Mono', monospace; font-size: 11px; }
.empty-row { text-align: center; color: #9ca3af; padding: 24px; }

/* BADGES */
.type-badge { font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px; flex-shrink: 0; }
.badge-lead     { background: rgba(105,108,255,0.12); color: #696cff; }
.badge-customer { background: rgba(3,195,236,0.12);   color: #0ea5e9; }
.progress-badge { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 20px; white-space: nowrap; }
.badge-planned { background: rgba(255,171,0,0.15);  color: #d97706; }
.badge-ongoing { background: rgba(3,195,236,0.15);  color: #0ea5e9; }
.badge-done    { background: rgba(113,221,55,0.15); color: #16a34a; }
.badge-unknown { background: rgba(156,163,175,0.2); color: #6b7280; }

/* EMPTY */
.empty-state { text-align: center; padding: 24px; color: #9ca3af; }
.empty-state i { font-size: 28px; display: block; margin-bottom: 6px; }
.empty-state p { margin: 0; font-size: 12px; }

@keyframes pulse {
  0%, 100% { box-shadow: 0 0 4px #71dd37; }
  50%       { box-shadow: 0 0 10px #71dd37; }
}
</style>