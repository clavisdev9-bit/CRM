<template>
  <backendLayouts>

    <div class="page-header d-print-none">
      <div class="container-xl">
        <div class="row g-2 align-items-center">
          <div class="col">
            <div class="page-pretitle">Sales Page</div>
            <h4 class="page-title">My Dashboard</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="container-xxl flex-grow-1 container-p-y">

      <!-- WELCOME CARD -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card welcome-card">
            <div class="d-flex align-items-end row">
              <div class="col-sm-8">
                <div class="card-body">
                  <h5 class="card-title text-primary mb-1">
                    Welcome, {{ auth.user?.fullname || '...' }} 👋
                  </h5>
                  <p class="text-muted mb-3" style="font-size:13px;">
                    Bulan ini kamu sudah menyelesaikan
                    <strong style="color:#696cff">{{ stats.target?.actual ?? 0 }}</strong> 
                    <!-- dari
                    <strong>{{ stats.target?.target ?? 20 }}</strong> target visit -->
                    <strong></strong> visit 
                    <span v-if="stats.ranking?.rank !== '-'">
                      dan berada di posisi
                      <strong style="color:#ffab00">#{{ stats.ranking?.rank }}</strong>
                      dari {{ stats.ranking?.total_sales }} sales.
                    </span>
                  </p>
                  <div class="achievement-bar-wrap">
                    <div class="achievement-bar-label">
                      <span>Achievement</span>
                      <span :style="{ color: achievementColor }">{{ stats.target?.achievement ?? 0 }}%</span>
                    </div>
                    <div class="achievement-bar-bg">
                      <div
                        class="achievement-bar-fill"
                        :style="{
                          width: Math.min(stats.target?.achievement ?? 0, 100) + '%',
                          background: achievementColor
                        }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-4 text-center">
                <div class="card-body pb-0">
                  <img :src="bannerImages" height="130" alt="banner" />
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
              <i class="bx bx-map-pin" style="color:#696cff"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Visit Hari Ini</div>
              <div class="stat-value" style="color:#696cff">{{ stats.visits_today?.length ?? 0 }}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#fff8e1">
              <i class="bx bx-trophy" style="color:#ffab00"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Ranking Bulan Ini</div>
              <div class="stat-value" style="color:#ffab00">
                #{{ stats.ranking?.rank ?? '-' }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#ffe8e5">
              <i class="bx bx-error-circle" style="color:#ff3e1d"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Follow Up Overdue</div>
              <div class="stat-value" style="color:#ff3e1d">{{ overdueCount }}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#eafbdf">
              <i class="bx bx-check-circle" style="color:#71dd37"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Visit Done Bulan Ini</div>
              <div class="stat-value" style="color:#71dd37">{{ stats.ranking?.done_visits ?? 0 }}</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#eeedff">
              <i class="bx bx-user-plus" style="color:#696cff"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Total My Leads</div>
              <div class="stat-value" style="color:#696cff">{{ stats.total_leads ?? 0 }}</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon" style="background:#e0f7fc">
              <i class="bx bx-group" style="color:#03c3ec"></i>
            </div>
            <div class="stat-info">
              <div class="stat-label">Total My Customers</div>
              <div class="stat-value" style="color:#03c3ec">{{ stats.total_customers ?? 0 }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-4">

        <!-- VISIT HARI INI -->
        <div class="col-lg-6 mb-4">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-map-pin" style="color:#696cff"></i>
                Visit Hari Ini
              </div>
              <span class="count-badge">{{ stats.visits_today?.length ?? 0 }}</span>
            </div>
            <div class="visit-list">
              <div
                class="visit-item"
                v-for="visit in stats.visits_today"
                :key="visit.id"
              >
                <div class="visit-left">
                  <span class="type-badge" :class="visit.target_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                    {{ visit.target_type }}
                  </span>
                  <div>
                    <div class="visit-company">{{ visit.company_name }}</div>
                    <div class="visit-time">{{ formatTime(visit.visit_at) }}</div>
                  </div>
                </div>
                <span class="progress-badge" :class="progressClass(visit.visit_progress)">
                  {{ visit.visit_progress }}
                </span>
              </div>
              <div class="empty-state" v-if="!stats.visits_today?.length">
                <i class="bx bx-map-alt"></i>
                <p>Tidak ada visit hari ini</p>
              </div>
            </div>
          </div>
        </div>

        <!-- FOLLOW UP PENDING -->
        <div class="col-lg-6 mb-4">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-phone-call" style="color:#ff3e1d"></i>
                Follow Up Pending
              </div>
              <span class="count-badge danger">{{ stats.follow_ups?.length ?? 0 }}</span>
            </div>
            <div class="fu-list">
              <div
                class="fu-item"
                v-for="fu in stats.follow_ups"
                :key="fu.id"
                :class="{ overdue: fu.is_overdue }"
              >
                <div class="fu-left">
                  <div class="fu-type-icon" :class="fuTypeClass(fu.follow_up_type)">
                    <i :class="fuTypeIcon(fu.follow_up_type)"></i>
                  </div>
                  <div>
                    <div class="fu-company">{{ fu.company_name }}</div>
                    <div class="fu-subject">{{ fu.subject ?? '-' }}</div>
                    <div class="fu-time" :class="{ 'text-danger': fu.is_overdue }">
                      <i class="bx bx-time"></i>
                      {{ formatTime(fu.follow_up_at) }}
                      <span v-if="fu.is_overdue" class="overdue-tag">OVERDUE</span>
                    </div>
                  </div>
                </div>
                <span class="type-badge" :class="fu.target_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                  {{ fu.target_type }}
                </span>
              </div>
              <div class="empty-state" v-if="!stats.follow_ups?.length">
                <i class="bx bx-check-circle"></i>
                <p>Semua follow up selesai 🎉</p>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div class="row">

        <!-- TARGET VS AKTUAL -->
        <div class="col-lg-6 mb-4">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-bar-chart-alt-2" style="color:#03c3ec"></i>
                Target vs Aktual
              </div>
              <span style="font-size:11px;color:#9ca3af;font-family:'Space Mono',monospace;">
                {{ currentMonth }}
              </span>
            </div>
            <div class="target-summary">
              <div class="target-item">
                <div class="target-num">{{ stats.target?.actual ?? 0 }}</div>
                <div class="target-label">Aktual</div>
              </div>
              <div class="target-divider"></div>
              <div class="target-item">
                <div class="target-num" style="color:#9ca3af">{{ stats.target?.target ?? 20 }}</div>
                <div class="target-label">Target</div>
              </div>
              <div class="target-divider"></div>
              <div class="target-item">
                <div class="target-num" :style="{ color: achievementColor }">
                  {{ stats.target?.achievement ?? 0 }}%
                </div>
                <div class="target-label">Achievement</div>
              </div>
            </div>
            <!-- Mini bar chart per hari -->
            <div class="mini-chart">
              <div
                class="mini-bar-wrap"
                v-for="d in stats.target?.per_day"
                :key="d.day"
              >
                <div
                  class="mini-bar"
                  :style="{
                    height: (d.total / maxPerDay * 100) + '%',
                    background: '#696cff'
                  }"
                  :title="`Tgl ${d.day}: ${d.total} visit`"
                ></div>
                <div class="mini-bar-label">{{ d.day }}</div>
              </div>
              <div class="empty-state" v-if="!stats.target?.per_day?.length">
                <i class="bx bx-bar-chart-alt-2"></i>
                <p>Belum ada data</p>
              </div>
            </div>
          </div>
        </div>

        <!-- LEADERBOARD -->
        <div class="col-lg-6 mb-4">
          <div class="section-card">
            <div class="section-header">
              <div class="section-title">
                <i class="bx bx-trophy" style="color:#ffab00"></i>
                Leaderboard Bulan Ini
              </div>
            </div>
            <div class="leaderboard-list">
              <div
                class="leaderboard-item"
                v-for="(s, i) in stats.ranking?.leaderboard"
                :key="s.sales_id"
                :class="{ 'my-row': s.sales_id == auth.user?.id_user }"
              >
                <div class="lb-rank" :class="'rank-' + (i+1)">{{ i + 1 }}</div>
                <img :src="s.sales_photo_url" class="lb-avatar" />
                <div class="lb-info">
                  <div class="lb-name">
                    {{ s.sales_name }}
                    <span v-if="s.sales_id == auth.user?.id_user" class="you-badge">You</span>
                  </div>
                  <div class="lb-bar-wrap">
                    <div
                      class="lb-bar"
                      :style="{
                        width: (s.total_visits / maxVisits * 100) + '%',
                        background: barColor(i)
                      }"
                    ></div>
                  </div>
                </div>
                <div class="lb-stats">
                  <div class="lb-total" :style="{ color: barColor(i) }">{{ s.total_visits }}</div>
                  <div class="lb-done">{{ s.done }} done</div>
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
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import backendLayouts from '../../../../layouts/backendLayouts.vue'
import { exportsLoginStore } from '@/stores/loginStore'

const auth = exportsLoginStore()
const bannerImages = '/images/man-with-laptop-light.png'
const stats = ref({})

const currentMonth = new Date().toLocaleString('id-ID', { month: 'long', year: 'numeric' })

const achievementColor = computed(() => {
  const a = stats.value.target?.achievement ?? 0
  if (a >= 80) return '#71dd37'
  if (a >= 50) return '#ffab00'
  return '#ff3e1d'
})

const overdueCount = computed(() =>
  (stats.value.follow_ups ?? []).filter(f => f.is_overdue).length
)

const maxPerDay = computed(() => {
  const days = stats.value.target?.per_day ?? []
  return Math.max(...days.map(d => d.total), 1)
})

const maxVisits = computed(() => {
  const lb = stats.value.ranking?.leaderboard ?? []
  return Math.max(...lb.map(s => s.total_visits), 1)
})

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
  const map = {
    CALL: 'fu-call', EMAIL: 'fu-email', WHATSAPP: 'fu-wa',
    MEETING: 'fu-meeting', VISIT: 'fu-visit', OTHER: 'fu-other'
  }
  return map[type] ?? 'fu-other'
}

const fuTypeIcon = (type) => {
  const map = {
    CALL: 'bx bx-phone', EMAIL: 'bx bx-envelope',
    WHATSAPP: 'bx bxl-whatsapp', MEETING: 'bx bx-group',
    VISIT: 'bx bx-map-pin', OTHER: 'bx bx-dots-horizontal'
  }
  return map[type] ?? 'bx bx-dots-horizontal'
}

const fetchDashboard = async () => {
  if (!auth.user?.id_user) return
  try {
    const res = await axios.get('/api/dashboard/sales', {
      params: { user_id: auth.user.id_user }
    })
    stats.value = res.data.data ?? {}
  } catch (e) {
    console.error('Failed to fetch sales dashboard:', e)
  }
}

onMounted(async () => {
  if (!auth.user) await auth.fetchProfile()
  await fetchDashboard()
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap');
* { box-sizing: border-box; }

/* WELCOME CARD */
.welcome-card { border: none; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }

.achievement-bar-wrap { margin-top: 8px; }
.achievement-bar-label {
  display: flex; justify-content: space-between;
  font-size: 12px; color: #6b7280; margin-bottom: 6px; font-weight: 500;
}
.achievement-bar-bg {
  height: 8px; background: #f1f5f9; border-radius: 8px; overflow: hidden;
}
.achievement-bar-fill {
  height: 100%; border-radius: 8px;
  transition: width 1s ease;
}

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
.count-badge {
  background: #eeedff; color: #696cff;
  font-size: 11px; font-weight: 700;
  padding: 2px 10px; border-radius: 20px;
}
.count-badge.danger { background: #ffe8e5; color: #ff3e1d; }

/* VISIT LIST */
.visit-list { display: flex; flex-direction: column; gap: 10px; }
.visit-item {
  display: flex; align-items: center;
  justify-content: space-between; gap: 10px;
  padding: 10px 12px;
  background: #f8f9fa; border-radius: 8px;
  transition: background 0.15s;
}
.visit-item:hover { background: #f1f5f9; }
.visit-left { display: flex; align-items: center; gap: 8px; }
.visit-company { font-size: 13px; font-weight: 500; color: #111; }
.visit-time { font-size: 11px; color: #9ca3af; font-family: 'Space Mono', monospace; }

/* FOLLOW UP LIST */
.fu-list { display: flex; flex-direction: column; gap: 10px; }
.fu-item {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 10px;
  padding: 10px 12px;
  background: #f8f9fa; border-radius: 8px;
  border-left: 3px solid transparent;
  transition: background 0.15s;
}
.fu-item.overdue { border-left-color: #ff3e1d; background: #fff8f8; }
.fu-left { display: flex; align-items: flex-start; gap: 10px; flex: 1; min-width: 0; }
.fu-type-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.fu-type-icon i { font-size: 16px; }
.fu-call    { background: rgba(105,108,255,0.12); color: #696cff; }
.fu-email   { background: rgba(255,171,0,0.12);   color: #d97706; }
.fu-wa      { background: rgba(37,211,102,0.12);  color: #16a34a; }
.fu-meeting { background: rgba(3,195,236,0.12);   color: #0ea5e9; }
.fu-visit   { background: rgba(255,62,29,0.12);   color: #dc2626; }
.fu-other   { background: rgba(156,163,175,0.15); color: #6b7280; }
.fu-company { font-size: 13px; font-weight: 500; color: #111; }
.fu-subject {
  font-size: 11px; color: #6b7280;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  max-width: 220px;
}
.fu-time {
  font-size: 11px; color: #9ca3af;
  font-family: 'Space Mono', monospace;
  display: flex; align-items: center; gap: 4px; margin-top: 2px;
}
.overdue-tag {
  background: #ff3e1d; color: #fff;
  font-size: 9px; font-weight: 700;
  padding: 1px 5px; border-radius: 4px;
}

/* TARGET */
.target-summary {
  display: flex; align-items: center;
  justify-content: center; gap: 20px;
  margin-bottom: 20px;
}
.target-item { text-align: center; }
.target-num { font-size: 24px; font-weight: 700; font-family: 'Space Mono', monospace; color: #111; }
.target-label { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.target-divider { width: 1px; height: 40px; background: #e5e7eb; }

/* MINI CHART */
.mini-chart {
  display: flex; align-items: flex-end;
  gap: 4px; height: 80px; padding-top: 8px;
}
.mini-bar-wrap {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; height: 100%; justify-content: flex-end;
}
.mini-bar {
  width: 100%; border-radius: 4px 4px 0 0;
  min-height: 4px; transition: height 0.5s ease;
}
.mini-bar-label { font-size: 9px; color: #9ca3af; margin-top: 4px; }

/* LEADERBOARD */
.leaderboard-list { display: flex; flex-direction: column; gap: 12px; }
.leaderboard-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 12px; border-radius: 8px;
  transition: background 0.15s;
}
.leaderboard-item:hover { background: #f8f9fa; }
.leaderboard-item.my-row { background: #eeedff; border: 1px solid #d4d4ff; }
.lb-rank {
  width: 24px; height: 24px; border-radius: 50%;
  background: #f1f5f9; color: #6b7280;
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.rank-1 { background: #fef3c7; color: #d97706; }
.rank-2 { background: #f1f5f9; color: #475569; }
.rank-3 { background: #fde8d8; color: #c2410c; }
.lb-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  object-fit: cover; border: 2px solid #e5e7eb; flex-shrink: 0;
}
.lb-info { flex: 1; min-width: 0; }
.lb-name {
  font-size: 13px; font-weight: 500; color: #111;
  display: flex; align-items: center; gap: 6px;
}
.you-badge {
  font-size: 9px; font-weight: 700;
  background: #696cff; color: #fff;
  padding: 1px 6px; border-radius: 20px;
}
.lb-bar-wrap { height: 4px; background: #f1f5f9; border-radius: 4px; margin-top: 6px; }
.lb-bar { height: 100%; border-radius: 4px; transition: width 1s ease; }
.lb-stats { text-align: right; flex-shrink: 0; }
.lb-total { font-size: 15px; font-weight: 700; font-family: 'Space Mono', monospace; }
.lb-done { font-size: 10px; color: #9ca3af; }

/* BADGES */
.type-badge {
  font-size: 9px; font-weight: 700;
  padding: 2px 6px; border-radius: 4px; flex-shrink: 0;
}
.badge-lead     { background: rgba(105,108,255,0.12); color: #696cff; }
.badge-customer { background: rgba(3,195,236,0.12);   color: #0ea5e9; }

.progress-badge {
  font-size: 10px; font-weight: 700;
  padding: 3px 8px; border-radius: 20px; white-space: nowrap;
}
.badge-planned { background: rgba(255,171,0,0.15);  color: #d97706; }
.badge-ongoing { background: rgba(3,195,236,0.15);  color: #0ea5e9; }
.badge-done    { background: rgba(113,221,55,0.15); color: #16a34a; }
.badge-unknown { background: rgba(156,163,175,0.2); color: #6b7280; }

/* EMPTY */
.empty-state { text-align: center; padding: 24px; color: #9ca3af; }
.empty-state i { font-size: 28px; display: block; margin-bottom: 6px; }
.empty-state p { margin: 0; font-size: 12px; }
</style>