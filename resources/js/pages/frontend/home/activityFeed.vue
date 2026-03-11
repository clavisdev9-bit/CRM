<template>
  <div class="activity-wrapper">

    <!-- HEADER -->
    <div class="page-header">
      <div class="page-title">
        <i class="bx bx-pulse"></i>
        <div>
          <h4>Activity Feed</h4>
          <span>Riwayat visit & follow up tim sales</span>
        </div>
      </div>
      <div class="header-right">
        <div class="filter-group">
          <i class="bx bx-calendar"></i>
          <input type="month" v-model="selectedMonth" @input="resetAndFetch" />
        </div>
        <div class="last-sync">
          <span class="sync-dot"></span>
          Updated {{ lastUpdated }}
        </div>
      </div>
    </div>

    <!-- TABS -->
    <div class="tabs">
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'visits' }"
        @click="switchTab('visits')"
      >
        <i class="bx bx-map-pin"></i>
        Visit
        <span class="tab-count">{{ totalVisits }}</span>
      </button>
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'followups' }"
        @click="switchTab('followups')"
      >
        <i class="bx bx-phone-call"></i>
        Follow Up
        <span class="tab-count">{{ totalFollowUps }}</span>
      </button>
    </div>

    <!-- FEED VISITS -->
    <div v-if="activeTab === 'visits'" class="feed-container">
      <div class="feed-item" v-for="item in visits" :key="item.id">
        <img :src="item.sales_photo_url" class="feed-avatar" />
        <div class="feed-line"></div>
        <div class="feed-content">
          <div class="feed-header">
            <div class="feed-meta">
              <span class="feed-sales">{{ item.sales_name }}</span>
              <span class="type-badge" :class="item.target_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                {{ item.target_type }}
              </span>
              <span class="feed-company">{{ item.company_name }}</span>
            </div>
            <span class="feed-time">{{ formatTime(item.visit_at) }}</span>
          </div>
          <div class="feed-body">
            <div class="feed-tags">
              <span class="progress-badge" :class="progressClass(item.visit_progress)">
                {{ item.visit_progress }}
              </span>
              <span class="feed-code">{{ item.visit_code }}</span>
            </div>
            <div class="feed-detail" v-if="item.visit_result">
              <i class="bx bx-note"></i>
              {{ item.visit_result }}
            </div>
            <div class="feed-times">
              <span v-if="item.check_in_at">
                <i class="bx bx-log-in"></i> Check-in: {{ formatTime(item.check_in_at) }}
              </span>
              <span v-if="item.check_out_at">
                <i class="bx bx-log-out"></i> Check-out: {{ formatTime(item.check_out_at) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- LOAD MORE -->
      <div class="load-more-wrap" v-if="hasMoreVisits">
        <button class="load-more-btn" @click="loadMoreVisits" :disabled="loadingVisits">
          <span v-if="loadingVisits"><i class="bx bx-loader-alt bx-spin"></i> Loading...</span>
          <span v-else><i class="bx bx-chevron-down"></i> Load More</span>
        </button>
      </div>

      <div class="empty-state" v-if="visits.length === 0 && !loadingVisits">
        <i class="bx bx-map-alt"></i>
        <p>Belum ada aktivitas visit</p>
      </div>

      <div class="loading-initial" v-if="visits.length === 0 && loadingVisits">
        <i class="bx bx-loader-alt bx-spin"></i>
        <p>Memuat data...</p>
      </div>
    </div>

    <!-- FEED FOLLOW UPS -->
    <div v-if="activeTab === 'followups'" class="feed-container">
      <div class="feed-item" v-for="item in followUps" :key="item.id">
        <img :src="item.sales_photo_url" class="feed-avatar" />
        <div class="feed-line"></div>
        <div class="feed-content">
          <div class="feed-header">
            <div class="feed-meta">
              <span class="feed-sales">{{ item.sales_name }}</span>
              <span class="type-badge" :class="item.target_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                {{ item.target_type }}
              </span>
              <span class="feed-company">{{ item.company_name }}</span>
            </div>
            <span class="feed-time">{{ formatTime(item.follow_up_at) }}</span>
          </div>
          <div class="feed-body">
            <div class="feed-tags">
              <span class="type-fu-badge" :class="fuTypeClass(item.follow_up_type)">
                <i :class="fuTypeIcon(item.follow_up_type)"></i>
                {{ item.follow_up_type }}
              </span>
              <span class="status-badge" :class="fuStatusClass(item.status)">
                {{ item.status }}
              </span>
              <span class="result-badge" v-if="item.result" :class="fuResultClass(item.result)">
                {{ item.result }}
              </span>
            </div>
            <div class="feed-detail" v-if="item.subject">
              <i class="bx bx-chat"></i>
              {{ item.subject }}
            </div>
            <div class="feed-times" v-if="item.completed_at">
              <span>
                <i class="bx bx-check-circle"></i> Completed: {{ formatTime(item.completed_at) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- LOAD MORE -->
      <div class="load-more-wrap" v-if="hasMoreFollowUps">
        <button class="load-more-btn" @click="loadMoreFollowUps" :disabled="loadingFollowUps">
          <span v-if="loadingFollowUps"><i class="bx bx-loader-alt bx-spin"></i> Loading...</span>
          <span v-else><i class="bx bx-chevron-down"></i> Load More</span>
        </button>
      </div>

      <div class="empty-state" v-if="followUps.length === 0 && !loadingFollowUps">
        <i class="bx bx-phone-call"></i>
        <p>Belum ada aktivitas follow up</p>
      </div>

      <div class="loading-initial" v-if="followUps.length === 0 && loadingFollowUps">
        <i class="bx bx-loader-alt bx-spin"></i>
        <p>Memuat data...</p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// --- STATE ---
const selectedMonth  = ref(new Date().toISOString().slice(0, 7))
const lastUpdated    = ref('-')
const activeTab      = ref('visits')

// Visits
const visits         = ref([])
const pageVisits     = ref(1)
const totalVisits    = ref(0)
const hasMoreVisits  = ref(false)
const loadingVisits  = ref(false)

// Follow Ups
const followUps         = ref([])
const pageFollowUps     = ref(1)
const totalFollowUps    = ref(0)
const hasMoreFollowUps  = ref(false)
const loadingFollowUps  = ref(false)

// --- FETCH ---
const fetchVisits = async (append = false) => {
  loadingVisits.value = true
  try {
    const res = await axios.get('/api/dashboard/activity-visits', {
      params: { month: selectedMonth.value, page: pageVisits.value }
    })
    const data = res.data.data ?? {}
    visits.value      = append ? [...visits.value, ...(data.data ?? [])] : (data.data ?? [])
    totalVisits.value = data.total ?? 0
    hasMoreVisits.value = data.has_more ?? false
    lastUpdated.value = new Date().toLocaleTimeString('id-ID')
  } catch (e) {
    console.error(e)
  } finally {
    loadingVisits.value = false
  }
}

const fetchFollowUps = async (append = false) => {
  loadingFollowUps.value = true
  try {
    const res = await axios.get('/api/dashboard/activity-follow-ups', {
      params: { month: selectedMonth.value, page: pageFollowUps.value }
    })
    const data = res.data.data ?? {}
    followUps.value      = append ? [...followUps.value, ...(data.data ?? [])] : (data.data ?? [])
    totalFollowUps.value = data.total ?? 0
    hasMoreFollowUps.value = data.has_more ?? false
  } catch (e) {
    console.error(e)
  } finally {
    loadingFollowUps.value = false
  }
}

const loadMoreVisits = async () => {
  pageVisits.value++
  await fetchVisits(true)
}

const loadMoreFollowUps = async () => {
  pageFollowUps.value++
  await fetchFollowUps(true)
}

const resetAndFetch = async () => {
  pageVisits.value   = 1
  pageFollowUps.value = 1
  visits.value       = []
  followUps.value    = []
  await fetchVisits()
  await fetchFollowUps()
}

const switchTab = async (tab) => {
  activeTab.value = tab
  if (tab === 'visits' && visits.value.length === 0) await fetchVisits()
  if (tab === 'followups' && followUps.value.length === 0) await fetchFollowUps()
}

// --- HELPERS ---
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
    CALL: 'bx bx-phone', EMAIL: 'bx bx-envelope', WHATSAPP: 'bx bxl-whatsapp',
    MEETING: 'bx bx-group', VISIT: 'bx bx-map-pin', OTHER: 'bx bx-dots-horizontal'
  }
  return map[type] ?? 'bx bx-dots-horizontal'
}

const fuStatusClass = (s) => {
  if (s === 'DONE')      return 'status-done'
  if (s === 'PENDING')   return 'status-pending'
  if (s === 'CANCELLED') return 'status-cancelled'
  if (s === 'CLOSED')    return 'status-closed'
  return ''
}

const fuResultClass = (r) => {
  if (r === 'DEAL')             return 'result-deal'
  if (r === 'INTERESTED')       return 'result-interested'
  if (r === 'NOT_INTERESTED')   return 'result-notinterested'
  if (r === 'STILL_CONSIDERING') return 'result-considering'
  if (r === 'NO_RESPONSE')      return 'result-noresponse'
  return ''
}

// --- LIFECYCLE ---
onMounted(async () => {
  await fetchVisits()
  await fetchFollowUps()
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap');

* { box-sizing: border-box; }

.activity-wrapper {
  font-family: 'DM Sans', sans-serif;
  padding: 24px;
  background: transparent;
  min-height: 100vh;
  color: #333;
  max-width: 860px;
  margin: 0 auto;
}

/* HEADER */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 12px;
}
.page-title { display: flex; align-items: center; gap: 12px; }
.page-title i { font-size: 28px; color: #696cff; }
.page-title h4 { font-size: 18px; font-weight: 700; color: #333; margin: 0; }
.page-title span { font-size: 12px; color: #9ca3af; }
.header-right { display: flex; align-items: center; gap: 12px; }

.filter-group {
  display: flex; align-items: center; gap: 8px;
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 8px; padding: 6px 12px;
  font-size: 12px; color: #9ca3af;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.filter-group input {
  background: transparent; border: none;
  color: #333; font-size: 12px;
  font-family: 'DM Sans', sans-serif; outline: none;
}

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

/* TABS */
.tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  border-bottom: 2px solid #f1f5f9;
  padding-bottom: 0;
}
.tab-btn {
  display: flex; align-items: center; gap: 6px;
  background: transparent; border: none;
  padding: 10px 16px;
  font-size: 13px; font-weight: 500;
  color: #9ca3af; cursor: pointer;
  font-family: 'DM Sans', sans-serif;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
}
.tab-btn i { font-size: 15px; }
.tab-btn.active { color: #696cff; border-bottom-color: #696cff; }
.tab-btn:hover:not(.active) { color: #6b7280; }
.tab-count {
  background: #f1f5f9; color: #6b7280;
  font-size: 11px; font-weight: 700;
  padding: 2px 7px; border-radius: 20px;
}
.tab-btn.active .tab-count { background: #eeedff; color: #696cff; }

/* FEED */
.feed-container { display: flex; flex-direction: column; }

.feed-item {
  display: flex;
  gap: 14px;
  padding-bottom: 20px;
  position: relative;
}

.feed-avatar {
  width: 38px; height: 38px;
  border-radius: 50%; object-fit: cover;
  border: 2px solid #e5e7eb;
  flex-shrink: 0; z-index: 1;
}

.feed-line {
  position: absolute;
  left: 18px; top: 42px;
  width: 2px;
  bottom: 0;
  background: #f1f5f9;
}
.feed-item:last-child .feed-line { display: none; }

.feed-content {
  flex: 1;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 14px 16px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  transition: box-shadow 0.2s;
}
.feed-content:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }

.feed-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 10px;
  gap: 8px;
}
.feed-meta {
  display: flex; align-items: center;
  gap: 6px; flex-wrap: wrap;
}
.feed-sales { font-size: 13px; font-weight: 600; color: #111; }
.feed-company { font-size: 12px; color: #6b7280; }
.feed-time {
  font-size: 11px; color: #9ca3af;
  font-family: 'Space Mono', monospace;
  white-space: nowrap; flex-shrink: 0;
}

.feed-body { display: flex; flex-direction: column; gap: 8px; }

.feed-tags { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

.feed-code {
  font-size: 10px; color: #9ca3af;
  font-family: 'Space Mono', monospace;
  background: #f8f9fa; padding: 2px 6px;
  border-radius: 4px;
}

.feed-detail {
  display: flex; align-items: flex-start; gap: 6px;
  font-size: 12px; color: #6b7280;
}
.feed-detail i { color: #9ca3af; font-size: 14px; flex-shrink: 0; margin-top: 1px; }

.feed-times {
  display: flex; gap: 12px; flex-wrap: wrap;
}
.feed-times span {
  display: flex; align-items: center; gap: 4px;
  font-size: 11px; color: #9ca3af;
  font-family: 'Space Mono', monospace;
}
.feed-times i { font-size: 13px; }

/* BADGES */
.type-badge {
  font-size: 9px; font-weight: 700;
  padding: 2px 6px; border-radius: 4px; flex-shrink: 0;
}
.badge-lead     { background: rgba(105,108,255,0.12); color: #696cff; }
.badge-customer { background: rgba(3,195,236,0.12);   color: #0ea5e9; }

.progress-badge {
  font-size: 10px; font-weight: 700;
  padding: 3px 8px; border-radius: 20px;
}
.badge-planned { background: rgba(255,171,0,0.15);  color: #d97706; }
.badge-ongoing { background: rgba(3,195,236,0.15);  color: #0ea5e9; }
.badge-done    { background: rgba(113,221,55,0.15); color: #16a34a; }
.badge-unknown { background: rgba(156,163,175,0.2); color: #6b7280; }

/* FU TYPE */
.type-fu-badge {
  display: flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700;
  padding: 3px 8px; border-radius: 20px;
}
.fu-call    { background: rgba(105,108,255,0.12); color: #696cff; }
.fu-email   { background: rgba(255,171,0,0.12);   color: #d97706; }
.fu-wa      { background: rgba(37,211,102,0.12);  color: #16a34a; }
.fu-meeting { background: rgba(3,195,236,0.12);   color: #0ea5e9; }
.fu-visit   { background: rgba(255,62,29,0.12);   color: #dc2626; }
.fu-other   { background: rgba(156,163,175,0.15); color: #6b7280; }

/* FU STATUS */
.status-badge {
  font-size: 10px; font-weight: 700;
  padding: 3px 8px; border-radius: 20px;
}
.status-done      { background: rgba(113,221,55,0.15); color: #16a34a; }
.status-pending   { background: rgba(255,171,0,0.15);  color: #d97706; }
.status-cancelled { background: rgba(156,163,175,0.15); color: #6b7280; }
.status-closed    { background: rgba(255,62,29,0.12);  color: #dc2626; }

/* FU RESULT */
.result-badge {
  font-size: 10px; font-weight: 700;
  padding: 3px 8px; border-radius: 20px;
}
.result-deal          { background: rgba(113,221,55,0.2);  color: #16a34a; border: 1px solid rgba(113,221,55,0.4); }
.result-interested    { background: rgba(3,195,236,0.15);  color: #0ea5e9; }
.result-notinterested { background: rgba(255,62,29,0.12);  color: #dc2626; }
.result-considering   { background: rgba(255,171,0,0.15);  color: #d97706; }
.result-noresponse    { background: rgba(156,163,175,0.15); color: #6b7280; }

/* LOAD MORE */
.load-more-wrap {
  display: flex; justify-content: center;
  padding: 20px 0;
}
.load-more-btn {
  display: flex; align-items: center; gap: 8px;
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 8px; padding: 10px 24px;
  font-size: 13px; color: #6b7280;
  cursor: pointer; font-family: 'DM Sans', sans-serif;
  transition: all 0.2s;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.load-more-btn:hover { background: #f8f9fa; color: #333; }
.load-more-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* EMPTY & LOADING */
.empty-state, .loading-initial {
  text-align: center; padding: 48px 24px; color: #9ca3af;
}
.empty-state i, .loading-initial i {
  font-size: 36px; display: block; margin-bottom: 8px;
}
.empty-state p, .loading-initial p { margin: 0; font-size: 13px; }

/* ANIMATIONS */
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 4px #71dd37; }
  50%       { box-shadow: 0 0 10px #71dd37; }
}
</style>