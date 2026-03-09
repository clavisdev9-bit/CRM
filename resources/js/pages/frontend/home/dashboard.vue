<template>
  <div class="dashboard-wrapper">

    <!-- HEADER -->
    <div class="page-header">
      <div class="page-title">
        <i class="bx bx-category"></i>
        <div>
          <h4>Dashboard</h4>
          <span>Ringkasan performa sales & kunjungan</span>
        </div>
      </div>
      <div class="header-right">
        <div class="filter-group">
          <i class="bx bx-calendar"></i>
          <!-- <input type="month" v-model="selectedMonth" @change="fetchAll" /> -->
          <input type="month" v-model="selectedMonth" @input="fetchAll" />

        </div>
        <div class="last-sync">
          <span class="sync-dot"></span>
          Updated {{ lastUpdated }}
        </div>
      </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="summary-grid">
      <div class="summary-card" v-for="(card, i) in summaryCards" :key="i" :style="{ animationDelay: i * 0.08 + 's' }">
        <div class="card-icon" :style="{ background: card.bg }">
          <i :class="card.icon" :style="{ color: card.color }"></i>
        </div>
        <div class="card-info">
          <div class="card-label">{{ card.label }}</div>
          <div class="card-value" :style="{ color: card.color }">{{ card.value }}</div>
          <div class="card-sub" v-if="card.sub">{{ card.sub }}</div>
        </div>
      </div>
    </div>

    <!-- ROW 2: Visit Chart + Visit Status -->
    <div class="row-2">

      <!-- VISIT CHART -->
      <div class="chart-card chart-main">
        <div class="chart-header">
          <div>
            <div class="chart-title">Grafik Kunjungan</div>
            <div class="chart-sub">Tren visit per periode</div>
          </div>
          <div class="period-tabs">
            <button
              v-for="p in periods"
              :key="p.value"
              class="period-tab"
              :class="{ active: chartPeriod === p.value }"
              @click="changePeriod(p.value)"
            >{{ p.label }}</button>
          </div>
        </div>
        <div class="chart-body">
          <canvas ref="visitChartRef"></canvas>
        </div>
      </div>

      <!-- VISIT STATUS DONUT -->
      <div class="chart-card chart-donut">
        <div class="chart-header">
          <div>
            <div class="chart-title">Status Visit</div>
            <div class="chart-sub">Hari ini</div>
          </div>
        </div>
        <div class="chart-body donut-body">
          <canvas ref="statusChartRef"></canvas>
          <div class="donut-center">
            <div class="donut-total">{{ visitStatus.total }}</div>
            <div class="donut-label">Total</div>
          </div>
        </div>
        <div class="donut-legend">
          <div class="legend-item" v-for="l in statusLegends" :key="l.label">
            <span class="legend-dot" :style="{ background: l.color }"></span>
            <span class="legend-label">{{ l.label }}</span>
            <span class="legend-val">{{ l.value }}</span>
          </div>
        </div>
      </div>

    </div>

    <!-- ROW 3: Top Sales + Conversion + Recent -->
    <div class="row-3">

      <!-- TOP SALES -->
      <div class="chart-card top-sales-card">
        <div class="chart-header">
          <div>
            <div class="chart-title">Top Sales</div>
            <div class="chart-sub">Performa bulan ini</div>
          </div>
        </div>
        <div class="top-sales-list">
          <div class="top-sales-item" v-for="(s, i) in topSales" :key="s.sales_id">
            <div class="rank" :class="'rank-' + (i + 1)">{{ i + 1 }}</div>
            <img :src="s.sales_photo_url" class="sales-avatar" />
            <div class="sales-info">
              <div class="sales-name">{{ s.sales_name }}</div>
              <div class="sales-bar-wrap">
                <div class="sales-bar" :style="{ width: (s.total_visits / maxVisits * 100) + '%', background: barColor(i) }"></div>
              </div>
            </div>
            <div class="sales-stats">
              <div class="sales-total" :style="{ color: barColor(i) }">{{ s.total_visits }}</div>
              <div class="sales-rate">{{ s.completion_rate }}%</div>
            </div>
          </div>
          <div class="empty-state" v-if="topSales.length === 0">
            <i class="bx bx-trophy"></i>
            <p>Belum ada data</p>
          </div>
        </div>
      </div>

      <!-- CONVERSION RATE -->
      <div class="chart-card conversion-card">
        <div class="chart-header">
          <div>
            <div class="chart-title">Conversion Rate</div>
            <div class="chart-sub">Lead → Customer</div>
          </div>
        </div>
        <div class="conversion-main">
          <div class="conversion-circle" :style="{ '--rate': conversion.conversion_rate }">
            <svg viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="40" fill="none" stroke="#f1f5f9" stroke-width="10"/>
              <circle
                cx="50" cy="50" r="40"
                fill="none"
                stroke="#696cff"
                stroke-width="10"
                stroke-linecap="round"
                :stroke-dasharray="`${conversion.conversion_rate * 2.51} 251`"
                stroke-dashoffset="62.75"
                transform="rotate(-90 50 50)"
                style="transition: stroke-dasharray 1s ease"
              />
            </svg>
            <div class="circle-text">
              <div class="circle-rate">{{ conversion.conversion_rate }}%</div>
              <div class="circle-label">Rate</div>
            </div>
          </div>
        </div>
        <div class="conversion-stats">
          <div class="conv-stat">
            <div class="conv-num">{{ conversion.total_leads }}</div>
            <div class="conv-label">Total Leads</div>
          </div>
          <div class="conv-divider"></div>
          <div class="conv-stat">
            <div class="conv-num" style="color: #696cff">{{ conversion.total_converted }}</div>
            <div class="conv-label">Converted</div>
          </div>
          <div class="conv-divider"></div>
          <div class="conv-stat">
            <div class="conv-num" style="color: #71dd37">{{ conversion.converted_this_month }}</div>
            <div class="conv-label">Bulan Ini</div>
          </div>
        </div>
        <div class="chart-body conversion-chart-body">
          <canvas ref="conversionChartRef"></canvas>
        </div>
      </div>


      <!-- CONVERSION CUSTOMERS (card baru) -->
<div class="chart-card conversion-card">
  <div class="chart-header">
    <div>
      <div class="chart-title">Deal Rate</div>
      <div class="chart-sub">Customer → Deal</div>
    </div>
    <span style="font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(105,108,255,0.15);color:#696cff;">
      CUSTOMER
    </span>
  </div>
  <div class="conversion-main">
    <div class="conversion-circle">
      <svg viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="40" fill="none" stroke="#f1f5f9" stroke-width="10"/>
        <circle cx="50" cy="50" r="40" fill="none" stroke="#696cff" stroke-width="10"
          stroke-linecap="round"
          :stroke-dasharray="`${conversionCustomers.conversion_rate * 2.51} 251`"
          stroke-dashoffset="62.75"
          transform="rotate(-90 50 50)"
          style="transition: stroke-dasharray 1s ease"
        />
      </svg>
      <div class="circle-text">
        <div class="circle-rate" style="color:#696cff">{{ conversionCustomers.conversion_rate }}%</div>
        <div class="circle-label">Rate</div>
      </div>
    </div>
  </div>
  <div class="conversion-stats">
    <div class="conv-stat">
      <div class="conv-num">{{ conversionCustomers.total_customers }}</div>
      <div class="conv-label">Total Customers</div>
    </div>
    <div class="conv-divider"></div>
    <div class="conv-stat">
      <div class="conv-num" style="color:#696cff">{{ conversionCustomers.total_deal }}</div>
      <div class="conv-label">Berhasil Deal</div>
    </div>
    <div class="conv-divider"></div>
    <div class="conv-stat">
      <div class="conv-num" style="color:#ff3e1d">{{ conversionCustomers.total_not_deal }}</div>
      <div class="conv-label">Belum Deal</div>
    </div>
  </div>
  <div class="chart-body conversion-chart-body">
    <canvas ref="conversionCustomersChartRef"></canvas>
  </div>
</div>

      <!-- RECENT ACTIVITY -->
      <div class="chart-card recent-card">
        <div class="chart-header">
          <div>
            <div class="chart-title">Aktivitas Terbaru</div>
            <div class="chart-sub">10 visit terakhir</div>
          </div>
        </div>
        <div class="recent-list">
          <div class="recent-item" v-for="visit in recentActivity" :key="visit.id">
            <img :src="visit.sales_photo_url" class="recent-avatar" />
            <div class="recent-info">
              <div class="recent-sales">{{ visit.sales_name }}</div>
              <div class="recent-company">
                <span class="type-badge" :class="visit.visit_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                  {{ visit.visit_type }}
                </span>
                {{ visit.company_name }}
              </div>
              <div class="recent-time">{{ formatTime(visit.visit_at) }}</div>
            </div>
            <span class="progress-badge" :class="progressClass(visit.visit_progress)">
              {{ visit.visit_progress }}
            </span>
          </div>
          <div class="empty-state" v-if="recentActivity.length === 0">
            <i class="bx bx-time"></i>
            <p>Belum ada aktivitas</p>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'

// --- STATE ---
const lastUpdated    = ref('-')
const selectedMonth  = ref(new Date().toISOString().slice(0, 7))
const chartPeriod    = ref('daily')

const summary        = ref({})
const visitChartData = ref({ labels: [], total: [], done: [], ongoing: [], planned: [] })
const visitStatus    = ref({ planned: 0, ongoing: 0, done: 0, total: 0, lead_visits: 0, customer_visits: 0 })
const topSales       = ref([])
const conversion     = ref({ total_leads: 0, total_converted: 0, converted_this_month: 0, conversion_rate: 0, monthly_labels: [], monthly_converted: [] })
const recentActivity = ref([])
const conversionCustomersChartRef = ref(null)
let conversionCustomersChartInstance = null

// Chart instances
const visitChartRef      = ref(null)
const statusChartRef     = ref(null)
const conversionChartRef = ref(null)
let visitChartInstance      = null
let statusChartInstance     = null
let conversionChartInstance = null


const periods = [
  { label: 'Harian',   value: 'daily'   },
  { label: 'Mingguan', value: 'weekly'  },
  { label: 'Bulanan',  value: 'monthly' },
]

// --- COMPUTED ---
const summaryCards = computed(() => [
  {
    label: 'Leads Baru', 
    value: summary.value.total_leads ?? 0,
    icon:  'bx bx-user-plus bx-sm',
    color: '#696cff',
    bg:    '#eeedff',
    sub:   null,
  },
  {
    label: 'Customers Baru',   
    value: summary.value.total_customers ?? 0,
    icon:  'bx bx-group bx-sm',
    color: '#03c3ec',
    bg:    '#e0f7fc',
    sub:   null,
  },
  {
   label: 'Visit Hari Ini',   
    value: summary.value.visits_today ?? 0,
    icon:  'bx bx-map-pin bx-sm',
    color: '#ff3e1d',
    bg:    '#ffe8e5',
    sub:   null,
  },
  {
    label: 'Total Visit', 
    value: summary.value.visits_this_month ?? 0,
    icon:  'bx bx-calendar-check bx-sm',
    color: '#71dd37',
    bg:    '#eafbdf',
    sub:   summary.value.visit_growth != null
      ? (summary.value.visit_growth >= 0 ? '▲' : '▼') + ' ' + Math.abs(summary.value.visit_growth) + '% vs bulan lalu'
      : null,
  },
  {
     label: 'Sales Aktif', 
    value: summary.value.active_sales_today ?? 0,
    icon:  'bx bx-briefcase bx-sm',
    color: '#ffab00',
    bg:    '#fff8e1',
    sub:   null,
  },
])

const maxVisits = computed(() => {
  if (topSales.value.length === 0) return 1
  return Math.max(...topSales.value.map(s => s.total_visits))
})

const statusLegends = computed(() => [
  { label: 'Planned',  value: visitStatus.value.planned,  color: '#ffab00' },
  { label: 'Ongoing',  value: visitStatus.value.ongoing,  color: '#03c3ec' },
  { label: 'Done',     value: visitStatus.value.done,     color: '#71dd37' },
])



// --- HELPERS ---
const formatTime = (dt) => {
  if (!dt) return '-'
  return new Date(dt).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

const progressClass = (p) => {
  if (p === 'PLANNED') return 'badge-planned'
  if (p === 'ONGOING') return 'badge-ongoing'
  if (p === 'DONE')    return 'badge-done'
  return 'badge-unknown'
}

const barColor = (i) => {
  const colors = ['#696cff', '#03c3ec', '#71dd37', '#ffab00', '#ff3e1d', '#20c997', '#fd7e14']
  return colors[i % colors.length]
}

// --- FETCH ---
const fetchAll = async () => {
  await Promise.all([
    fetchSummary(),
    fetchVisitChart(),
    fetchTopSales(),
    fetchConversion(),
    fetchVisitStatus(),
    fetchRecentActivity(),
       fetchConversionCustomers(),
  ])
  lastUpdated.value = new Date().toLocaleTimeString('id-ID')
}

const fetchSummary = async () => {
  const res = await axios.get('/api/dashboard/summary', {
    params: { month: selectedMonth.value } // 👈 tambahkan
  })
  summary.value = res.data.data ?? {}
}
const fetchVisitChart = async () => {
  const res = await axios.get('/api/dashboard/visit-chart', {
    params: {
      period: chartPeriod.value,
      month: selectedMonth.value // 👈 tambahkan
    }
  })
  visitChartData.value = res.data.data ?? {}
  await nextTick()
  renderVisitChart()
}

const fetchTopSales = async () => {
  const res = await axios.get('/api/dashboard/top-sales', { params: { month: selectedMonth.value } })
  topSales.value = res.data.data ?? []
}

const fetchConversion = async () => {
  const res = await axios.get('/api/dashboard/conversion-rate', { params: { month: selectedMonth.value } })
  conversion.value = res.data.data ?? {}
  await nextTick()
  renderConversionChart()
}

const fetchConversionCustomers = async () => {
  const res = await axios.get('/api/dashboard/conversion-rate-customers', {
    params: { month: selectedMonth.value }
  })
  conversionCustomers.value = res.data.data ?? {}
  await nextTick()
  renderConversionCustomersChart()
}


const fetchVisitStatus = async () => {
  // Ambil date_from dan date_to dari selectedMonth
  const start = selectedMonth.value + '-01'
  const end   = new Date(
    new Date(selectedMonth.value + '-01').getFullYear(),
    new Date(selectedMonth.value + '-01').getMonth() + 1,
    0
  ).toISOString().split('T')[0]

  const res = await axios.get('/api/dashboard/visit-status', {
    params: { date_from: start, date_to: end } // 👈 filter bulan
  })
  visitStatus.value = res.data.data ?? {}
  await nextTick()
  renderStatusChart()
}

const fetchRecentActivity = async () => {
  const res = await axios.get('/api/dashboard/recent-activity', {
    params: { month: selectedMonth.value } // 👈 tambahkan
  })
  recentActivity.value = res.data.data ?? []
}

const changePeriod = async (period) => {
  chartPeriod.value = period
  await fetchVisitChart()
}

// --- CHARTS ---
const renderVisitChart = () => {
  if (!visitChartRef.value) return
  const Chart = window.Chart
  if (visitChartInstance) visitChartInstance.destroy()

  visitChartInstance = new Chart(visitChartRef.value, {
    type: 'bar',
    data: {
      labels: visitChartData.value.labels ?? [],
      datasets: [
        {
          label: 'Done',
          data: visitChartData.value.done ?? [],
          backgroundColor: '#71dd37',
          borderRadius: 4,
        },
        {
          label: 'Ongoing',
          data: visitChartData.value.ongoing ?? [],
          backgroundColor: '#03c3ec',
          borderRadius: 4,
        },
        {
          label: 'Planned',
          data: visitChartData.value.planned ?? [],
          backgroundColor: '#ffab00',
          borderRadius: 4,
        },
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top', labels: { font: { family: 'DM Sans', size: 12 }, color: '#6b7280' } },
        tooltip: { mode: 'index', intersect: false }
      },
      scales: {
        x: {
          stacked: true,
          grid: { display: false },
          ticks: { color: '#9ca3af', font: { family: 'DM Sans', size: 11 } }
        },
        y: {
          stacked: true,
          grid: { color: '#f1f5f9' },
          ticks: { color: '#9ca3af', font: { family: 'DM Sans', size: 11 }, stepSize: 1 }
        }
      }
    }
  })
}

const renderStatusChart = () => {
  if (!statusChartRef.value) return
  const Chart = window.Chart
  if (statusChartInstance) statusChartInstance.destroy()

  statusChartInstance = new Chart(statusChartRef.value, {
    type: 'doughnut',
    data: {
      labels: ['Planned', 'Ongoing', 'Done'],
      datasets: [{
        data: [
          visitStatus.value.planned,
          visitStatus.value.ongoing,
          visitStatus.value.done,
        ],
        backgroundColor: ['#ffab00', '#03c3ec', '#71dd37'],
        borderWidth: 0,
        hoverOffset: 8,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${ctx.raw}` } }
      }
    }
  })
}

const renderConversionChart = () => {
  if (!conversionChartRef.value) return
  const Chart = window.Chart
  if (conversionChartInstance) conversionChartInstance.destroy()

  conversionChartInstance = new Chart(conversionChartRef.value, {
    type: 'line',
    data: {
      labels: conversion.value.monthly_labels ?? [],
      datasets: [{
        label: 'Converted',
        data: conversion.value.monthly_converted ?? [],
        borderColor: '#696cff',
        backgroundColor: 'rgba(105,108,255,0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#696cff',
        pointRadius: 4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#9ca3af', font: { family: 'DM Sans', size: 10 } }
        },
        y: {
          grid: { color: '#f1f5f9' },
          ticks: { color: '#9ca3af', font: { family: 'DM Sans', size: 10 }, stepSize: 1 }
        }
      }
    }
  })
}




// Render chart baru
const conversionCustomers = ref({
  total_customers: 0, total_deal: 0, total_not_deal: 0,
  conversion_rate: 0, monthly_labels: [], monthly_converted: []
})

const renderConversionCustomersChart = () => {
  if (!conversionCustomersChartRef.value) return
  const Chart = window.Chart
  if (conversionCustomersChartInstance) conversionCustomersChartInstance.destroy()

  conversionCustomersChartInstance = new Chart(conversionCustomersChartRef.value, {
    type: 'line',
    data: {
      labels: conversionCustomers.value.monthly_labels ?? [],
      datasets: [{
        label: 'Deal',
        data: conversionCustomers.value.monthly_converted ?? [],
        borderColor: '#696cff',
        backgroundColor: 'rgba(105,108,255,0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#696cff',
        pointRadius: 4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } },
        y: { grid: { color: '#f1f5f9' }, ticks: { color: '#9ca3af', font: { size: 10 }, stepSize: 1 } }
      }
    }
  })
}

// --- LIFECYCLE ---
onMounted(async () => {
  // Load Chart.js
  if (!window.Chart) {
    await new Promise((resolve) => {
      const script = document.createElement('script')
      script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
      script.onload = resolve
      document.head.appendChild(script)
    })
  }
  await fetchAll()
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap');

* { box-sizing: border-box; }

.dashboard-wrapper {
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
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  color: #9ca3af;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.filter-group input {
  background: transparent;
  border: none;
  color: #333;
  font-size: 12px;
  font-family: 'DM Sans', sans-serif;
  outline: none;
}

.last-sync {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #9ca3af;
  font-family: 'Space Mono', monospace;
}
.sync-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #71dd37;
  box-shadow: 0 0 6px #71dd37;
  animation: pulse 2s infinite;
}

/* SUMMARY CARDS */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}
@media (max-width: 1200px) { .summary-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .summary-grid { grid-template-columns: repeat(2, 1fr); } }

.summary-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  animation: fadeSlideUp 0.5s ease both;
  transition: transform 0.2s, box-shadow 0.2s;
}
.summary-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }

.card-icon {
  width: 48px; height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.card-icon i { font-size: 22px; }
.card-label { font-size: 12px; color: #9ca3af; margin-bottom: 4px; }
.card-value { font-size: 22px; font-weight: 700; font-family: 'Space Mono', monospace; line-height: 1; }
.card-sub { font-size: 11px; color: #9ca3af; margin-top: 4px; }

/* ROW 2 */
.row-2 {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 16px;
  margin-bottom: 20px;
}
@media (max-width: 1024px) { .row-2 { grid-template-columns: 1fr; } }

/* ROW 3 */
.row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 16px;
}
@media (max-width: 1200px) { .row-3 { grid-template-columns: 1fr 1fr; } }
@media (max-width: 768px)  { .row-3 { grid-template-columns: 1fr; } }

/* CHART CARD */
.chart-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.chart-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 16px;
}
.chart-title { font-size: 14px; font-weight: 600; color: #111; }
.chart-sub   { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.chart-body  { height: 240px; position: relative; }

/* PERIOD TABS */
.period-tabs { display: flex; gap: 4px; }
.period-tab {
  background: #f8f9fa;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 4px 12px;
  font-size: 12px;
  color: #6b7280;
  cursor: pointer;
  font-family: 'DM Sans', sans-serif;
  transition: all 0.2s;
}
.period-tab.active { background: #696cff; border-color: #696cff; color: #fff; }
.period-tab:hover:not(.active) { background: #e5e7eb; }

/* DONUT */
.donut-body {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}
.donut-center {
  position: absolute;
  text-align: center;
}
.donut-total { font-size: 24px; font-weight: 700; font-family: 'Space Mono', monospace; color: #111; }
.donut-label { font-size: 11px; color: #9ca3af; }

.donut-legend { display: flex; flex-direction: column; gap: 8px; margin-top: 16px; }
.legend-item  { display: flex; align-items: center; gap: 8px; font-size: 13px; }
.legend-dot   { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.legend-label { flex: 1; color: #6b7280; }
.legend-val   { font-weight: 600; color: #111; font-family: 'Space Mono', monospace; font-size: 12px; }

/* TOP SALES */
.top-sales-list { display: flex; flex-direction: column; gap: 14px; }
.top-sales-item { display: flex; align-items: center; gap: 10px; }
.rank {
  width: 22px; height: 22px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #6b7280;
  font-size: 11px;
  font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.rank-1 { background: #fef3c7; color: #d97706; }
.rank-2 { background: #f1f5f9; color: #475569; }
.rank-3 { background: #fde8d8; color: #c2410c; }

.sales-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; flex-shrink: 0; }
.sales-info { flex: 1; min-width: 0; }
.sales-name { font-size: 13px; font-weight: 500; color: #111; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sales-bar-wrap { height: 4px; background: #f1f5f9; border-radius: 4px; margin-top: 6px; }
.sales-bar { height: 100%; border-radius: 4px; transition: width 1s ease; }
.sales-stats { text-align: right; flex-shrink: 0; }
.sales-total { font-size: 15px; font-weight: 700; font-family: 'Space Mono', monospace; }
.sales-rate  { font-size: 10px; color: #9ca3af; }

/* CONVERSION */
.conversion-main { display: flex; justify-content: center; margin-bottom: 16px; }
.conversion-circle {
  position: relative;
  width: 130px; height: 130px;
}
.conversion-circle svg { width: 100%; height: 100%; }
.circle-text {
  position: absolute;
  inset: 0;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
}
.circle-rate  { font-size: 22px; font-weight: 700; font-family: 'Space Mono', monospace; color: #696cff; }
.circle-label { font-size: 11px; color: #9ca3af; }

.conversion-stats {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-bottom: 16px;
}
.conv-stat { text-align: center; }
.conv-num   { font-size: 18px; font-weight: 700; font-family: 'Space Mono', monospace; color: #111; }
.conv-label { font-size: 11px; color: #9ca3af; }
.conv-divider { width: 1px; height: 32px; background: #e5e7eb; }
.conversion-chart-body { height: 100px; }

/* RECENT */
.recent-list { display: flex; flex-direction: column; }
.recent-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}
.recent-item:last-child { border-bottom: none; }
.recent-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; flex-shrink: 0; }
.recent-info { flex: 1; min-width: 0; }
.recent-sales   { font-size: 13px; font-weight: 500; color: #111; }
.recent-company {
  font-size: 11px; color: #9ca3af;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  display: flex; align-items: center; gap: 4px;
  margin-top: 2px;
}
.recent-time { font-size: 10px; color: #9ca3af; font-family: 'Space Mono', monospace; margin-top: 2px; }

/* BADGES */
.type-badge { font-size: 9px; font-weight: 700; padding: 2px 5px; border-radius: 4px; flex-shrink: 0; }
.badge-lead     { background: rgba(105,108,255,0.15); color: #696cff; }
.badge-customer { background: rgba(3,195,236,0.15);   color: #0ea5e9; }

.progress-badge { font-size: 9px; font-weight: 700; padding: 3px 8px; border-radius: 20px; text-transform: uppercase; white-space: nowrap; flex-shrink: 0; }
.badge-planned { background: rgba(255,171,0,0.15);  color: #d97706; }
.badge-ongoing { background: rgba(3,195,236,0.15);  color: #0ea5e9; }
.badge-done    { background: rgba(113,221,55,0.15); color: #16a34a; }
.badge-unknown { background: rgba(156,163,175,0.2); color: #6b7280; }

.empty-state { text-align: center; padding: 24px; color: #9ca3af; }
.empty-state i { font-size: 28px; display: block; margin-bottom: 6px; }
.empty-state p { margin: 0; font-size: 12px; }

.row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr;
  gap: 16px;
}
@media (max-width: 1400px) { .row-3 { grid-template-columns: 1fr 1fr; } }
@media (max-width: 768px)  { .row-3 { grid-template-columns: 1fr; } }

/* ANIMATIONS */
@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 4px #71dd37; }
  50%       { box-shadow: 0 0 10px #71dd37; }
}
</style>