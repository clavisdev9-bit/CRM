<template>
  <div class="map-tracking-wrapper">

    <!-- TOP BAR -->
    <div class="topbar">
      <div class="topbar-left">
        <div class="brand">
          <span class="brand-dot"></span>
          <span>Live Field Tracker</span>
        </div>
        <div class="date-filter">
          <div class="filter-group">
            <label>From</label>
            <input type="date" v-model="dateFrom" @change="fetchVisits" />
          </div>
          <div class="filter-sep">→</div>
          <div class="filter-group">
            <label>To</label>
            <input type="date" v-model="dateTo" @change="fetchVisits" />
          </div>
        </div>


        <!-- Filter Sales -->
            <div class="filter-group">
            <label>Sales</label>
            <select v-model="selectedSalesId" @change="renderMarkers">
                <option value="">All Sales</option>
                <option v-for="s in salesList" :key="s.id" :value="s.id">
                {{ s.name }}
                </option>
            </select>
            </div>
        </div>

      

      <!-- di topbar-right, sebelum stat-pill -->
        <div class="last-update">
        <span class="brand-dot"></span>
        Live · Updated {{ lastUpdated }}
        </div>



      <div class="topbar-right">
        <div class="stat-pill" v-for="s in statusSummary" :key="s.label" :class="s.cls">
          <span class="pill-dot"></span>
          {{ s.count }} {{ s.label }}
        </div>
      </div>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="main-layout">

      <!-- SIDEBAR -->
      <div class="sidebar" :class="{ collapsed: sidebarCollapsed }">
        <div class="sidebar-header">
          <span v-if="!sidebarCollapsed">All Visits <em>({{ visits.length }})</em></span>
          <button class="collapse-btn" @click="sidebarCollapsed = !sidebarCollapsed">
            <i :class="sidebarCollapsed ? 'bx bx-chevron-right' : 'bx bx-chevron-left'"></i>
          </button>
        </div>

        <div class="sidebar-search" v-if="!sidebarCollapsed">
          <i class="bx bx-search"></i>
          <input type="text" v-model="search" placeholder="Search sales / company..." />
        </div>

        <div class="visit-list" v-if="!sidebarCollapsed">
          <div
            v-for="visit in filteredVisits"
            :key="visit.id"
            class="visit-item"
            :class="{ active: selectedVisit?.id === visit.id }"
            @click="selectVisit(visit)"
          >
            <div class="visit-avatar">
              <img :src="visit.sales_photo_url" :alt="visit.sales_name" />
              <span class="status-dot" :class="statusClass(visit.visit_status_label)"></span>
            </div>
            <div class="visit-info">
              <div class="visit-sales">{{ visit.sales_name }}</div>
              <div class="visit-company">
                <span class="type-badge" :class="visit.target_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                  {{ visit.target_type }}
                </span>
                {{ visit.target_name }}
              </div>
              <div class="visit-time">{{ formatTime(visit.visit_at) }}</div>
            </div>
            <div class="visit-status-badge" :class="statusClass(visit.visit_status_label)">
              {{ visit.visit_status_label }}
            </div>
          </div>

          <div class="empty-state" v-if="filteredVisits.length === 0">
            <i class="bx bx-map-alt"></i>
            <p>No visits found</p>
          </div>
        </div>
      </div>

      <!-- MAP -->
      <div class="map-container">
        <div id="leaflet-map" ref="mapRef"></div>

        <!-- LOADING OVERLAY -->
        <div class="map-loading" v-if="isLoading">
          <div class="loader-ring"></div>
          <span>Loading visits...</span>
        </div>

        <!-- SELECTED POPUP CARD -->
        <transition name="slide-up">
          <div class="detail-card" v-if="selectedVisit">
            <button class="detail-close" @click="selectedVisit = null">
              <i class="bx bx-x"></i>
            </button>
            <div class="detail-header">
              <img :src="selectedVisit.sales_photo_url" class="detail-avatar" />
              <div>
                <div class="detail-sales">{{ selectedVisit.sales_name }}</div>
                <div class="detail-status" :class="statusClass(selectedVisit.visit_status_label)">
                  {{ selectedVisit.visit_status_label }}
                </div>
              </div>
            </div>
            <div class="detail-body">
              <div class="detail-row">
                <i class="bx bx-buildings"></i>
                <span>
                  <span class="type-badge" :class="selectedVisit.target_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                    {{ selectedVisit.target_type }}
                  </span>
                  {{ selectedVisit.target_name }}
                </span>
              </div>
              <div class="detail-row">
                <i class="bx bx-map-pin"></i>
                <span>{{ selectedVisit.gps_snapshot ?? 'No address' }}</span>
              </div>
              <div class="detail-row">
                <i class="bx bx-time"></i>
                <span>Visit: {{ formatTime(selectedVisit.visit_at) }}</span>
              </div>
              <div class="detail-row" v-if="selectedVisit.check_in_at">
                <i class="bx bx-log-in"></i>
                <span>Check-in: {{ formatTime(selectedVisit.check_in_at) }}</span>
              </div>
              <div class="detail-row" v-if="selectedVisit.check_out_at">
                <i class="bx bx-log-out"></i>
                <span>Check-out: {{ formatTime(selectedVisit.check_out_at) }}</span>
              </div>
            </div>
          </div>
        </transition>
      </div>

      <!-- LEGEND -->
<div class="map-legend">
  <div class="legend-title">Status</div>
  <div class="legend-item" v-for="l in legends" :key="l.label">
    <span class="legend-dot" :style="{ background: l.color }"></span>
    {{ l.label }}
  </div>
  <div class="legend-divider"></div>
  <div class="legend-title">Type</div>
  <div class="legend-item">
    <span class="legend-badge badge-lead">LEAD</span>
  </div>
  <div class="legend-item">
    <span class="legend-badge badge-customer">CUSTOMER</span>
  </div>
</div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import axios from 'axios'

// --- STATE ---
const mapRef = ref(null)
const mapInstance = ref(null)
const markersLayer = ref(null)
const visits = ref([])
const selectedVisit = ref(null)
const isLoading = ref(false)
const sidebarCollapsed = ref(false)
const search = ref('')
const polylinesLayer = ref(null)

const today = new Date().toISOString().split('T')[0]
const dateFrom = ref(today)
const dateTo = ref(today)

const legends = [
  { label: 'Belum Check-in', color: '#ffab00' },
  { label: 'Sedang Check-in', color: '#03c3ec' },
  { label: 'Selesai',         color: '#71dd37' },
  { label: 'Unknown',         color: '#8592a3' },
]

// untuk realtime

onMounted(async () => {
  if (!document.getElementById('leaflet-css')) {
    const link = document.createElement('link')
    link.id = 'leaflet-css'
    link.rel = 'stylesheet'
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    document.head.appendChild(link)
  }

  if (!window.L) {
    await new Promise((resolve) => {
      const script = document.createElement('script')
      script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
      script.onload = resolve
      document.head.appendChild(script)
    })

    // jika suatu saat pakai googlr maps aktifkan ini
    // GANTI dengan:
        // await new Promise((resolve) => {
        //   const script = document.createElement('script')
        //   script.src = `https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY`
        //   script.onload = resolve
        //   document.head.appendChild(script)
        // })
  }

  initMap()
  await fetchVisits()
  startPolling() // 👈 tambahkan ini
})

onUnmounted(() => {
  stopPolling() // 👈 tambahkan ini
})



// --- COMPUTED ---
// const filteredVisits = computed(() => {
//   const q = search.value.toLowerCase()
//   return visits.value.filter(v =>
//     v.sales_name?.toLowerCase().includes(q) ||
//     v.target_name?.toLowerCase().includes(q)
//   )
// })


// code untuk filter
// STATE baru
const selectedSalesId = ref('')

// Computed list sales dari data visit (tidak perlu API baru)
const salesList = computed(() => {
  const map = {}
  visits.value.forEach(v => {
    if (v.sales_id && !map[v.sales_id]) {
      map[v.sales_id] = { id: v.sales_id, name: v.sales_name }
    }
  })
  return Object.values(map).sort((a, b) => a.name.localeCompare(b.name))
})

// Update filteredVisits dengan tambahan filter sales
const filteredVisits = computed(() => {
  const q = search.value.toLowerCase()
  return visits.value.filter(v => {
    const matchSearch = v.sales_name?.toLowerCase().includes(q) ||
                        v.target_name?.toLowerCase().includes(q)
    const matchSales  = selectedSalesId.value === '' || 
                        v.sales_id == selectedSalesId.value
    return matchSearch && matchSales
  })
})




const statusSummary = computed(() => {
  const planned  = visits.value.filter(v => v.visit_status_label === 'BELUM_CHECK_IN').length
  const ongoing  = visits.value.filter(v => v.visit_status_label === 'SEDANG_CHECK_IN').length
  const done     = visits.value.filter(v => v.visit_status_label === 'SELESAI').length
  return [
    { label: 'Planned',  count: planned, cls: 'pill-planned'  },
    { label: 'On-Site',  count: ongoing, cls: 'pill-ongoing'  },
    { label: 'Done',     count: done,    cls: 'pill-done'     },
  ]
})

// --- HELPERS ---
const statusClass = (label) => {
  if (label === 'BELUM_CHECK_IN') return 'status-planned'
  if (label === 'SEDANG_CHECK_IN') return 'status-ongoing'
  if (label === 'SELESAI') return 'status-done'
  return 'status-unknown'
}

const formatTime = (dt) => {
  if (!dt) return '-'
  return new Date(dt).toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short' })
}

const markerColor = (label) => {
  if (label === 'BELUM_CHECK_IN') return '#ffab00'
  if (label === 'SEDANG_CHECK_IN') return '#03c3ec'
  if (label === 'SELESAI') return '#71dd37'
  return '#8592a3'
}

// --- MAP INIT ---
const initMap = () => {
  if (mapInstance.value) return
  const L = window.L
  mapInstance.value = L.map(mapRef.value, {
    center: [-6.2, 106.8],
    zoom: 11,
    zoomControl: false,
  })
  L.control.zoom({ position: 'bottomright' }).addTo(mapInstance.value)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(mapInstance.value)

  markersLayer.value = window.L.layerGroup().addTo(mapInstance.value)
}

// jika suatu saat mengunkan google maps aktifkan ini
// const initMap = () => {
//   if (mapInstance.value) return
//   mapInstance.value = new google.maps.Map(mapRef.value, {
//     center: { lat: -6.2, lng: 106.8 },
//     zoom: 11,
//   })
//   markersLayer.value = [] // Google Maps tidak pakai layerGroup
// }


// --- FETCH ---
const lastUpdated = ref('-')

const fetchVisits = async () => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/data-visits-leads-map', {
      params: { date_from: dateFrom.value, date_to: dateTo.value }
    })
    visits.value = res.data.data ?? []
    renderMarkers()
    lastUpdated.value = new Date().toLocaleTimeString('id-ID')
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

// --- MARKERS ---
const renderMarkers = () => {
  if (!mapInstance.value || !markersLayer.value) return // 👈 guard
  
  const L = window.L
  markersLayer.value.clearLayers()
  const bounds = []

  filteredVisits.value.forEach(visit => {
    if (!visit.show_on_map || !visit.latitude || !visit.longitude) return
    const lat = parseFloat(visit.latitude)
    const lng = parseFloat(visit.longitude)
    const color = markerColor(visit.visit_status_label)

    const icon = L.divIcon({
      className: '',
      html: `
        <div style="position:relative;width:42px;height:42px;">
          <div style="width:42px;height:42px;border-radius:50%;border:3px solid ${color};overflow:hidden;background:white;box-shadow:0 4px 12px rgba(0,0,0,0.25);">
            <img src="${visit.sales_photo_url}" style="width:100%;height:100%;object-fit:cover;" />
          </div>
          <div style="position:absolute;bottom:-5px;right:-2px;width:14px;height:14px;border-radius:50%;background:${color};border:2px solid white;"></div>
        </div>
      `,
      iconSize: [42, 42],
      iconAnchor: [21, 42],
    })

    const marker = L.marker([lat, lng], { icon })
      .on('click', () => selectVisit(visit))
    markersLayer.value.addLayer(marker)
    bounds.push([lat, lng])
  })

   //jilka suatu saat mengunkan google maps aktifkan ini
  // Leaflet: L.marker([lat, lng])
    // Google Maps:
    // new google.maps.Marker({
    //   position: { lat, lng },
    //   map: mapInstance.value,
    //   icon: { ... }
    // })

  // 👈 Tambahkan try-catch di fitBounds
  if (bounds.length > 0) {
    try {
      mapInstance.value.fitBounds(bounds, { padding: [60, 60] })
    } catch (e) {
      console.warn('fitBounds error:', e)
    }
  }

  nextTick(() => renderPolylines()) // 👈 delay polyline sampai map selesai render
}



// --- SELECT VISIT ---
const selectVisit = (visit) => {
  selectedVisit.value = visit
  if (visit.latitude && visit.longitude) {
    mapInstance.value.setView([visit.latitude, visit.longitude], 15, { animate: true })
  }
}

watch(filteredVisits, () => {
  if (mapInstance.value && mapInstance.value.getContainer()) {
    nextTick(() => renderMarkers())
  }
})


// Gambar polyline per sales
const renderPolylines = () => {
if (!mapInstance.value || !window.L) return // 👈 guard

  const L = window.L

  // Hapus polyline lama kalau ada
  if (polylinesLayer.value) {
    polylinesLayer.value.clearLayers()
  }
  polylinesLayer.value = L.layerGroup().addTo(mapInstance.value)

  // Group visit per sales_id
  const grouped = {}
  filteredVisits.value.forEach(visit => {
    if (!visit.latitude || !visit.longitude) return
    if (!grouped[visit.sales_id]) grouped[visit.sales_id] = []
    grouped[visit.sales_id].push(visit)
  })

  // Buat polyline per sales, urut berdasarkan visit_at
  Object.values(grouped).forEach(salesVisits => {
    if (salesVisits.length < 2) return // minimal 2 titik baru bisa dibuat garis

    const sorted = [...salesVisits].sort(
      (a, b) => new Date(a.visit_at) - new Date(b.visit_at)
    )

    const coords = sorted.map(v => [parseFloat(v.latitude), parseFloat(v.longitude)])
    const color  = salesColor(sorted[0].sales_id)

    // Garis utama
    L.polyline(coords, {
      color,
      weight: 2,
      opacity: 0.6,
      dashArray: '8, 6',  // <-- efek putus-putus
    }).addTo(polylinesLayer.value)

    //jika suatu saat menggunkan google maps
    // Leaflet: L.polyline(coords)
        // Google Maps:
        // new google.maps.Polyline({
        //   path: coords.map(([lat, lng]) => ({ lat, lng })),
        //   map: mapInstance.value,
        //   strokeColor: color,
        //   strokeOpacity: 0.6,
        //   strokeWeight: 2,
        //   icons: [{ icon: { path: 'M 0,-1 0,1', strokeOpacity: 1, scale: 3 }, repeat: '15px' }]
        // })

    // Nomor urut di tiap titik
    sorted.forEach((visit, index) => {
      const numIcon = L.divIcon({
        className: '',
        html: `
          <div style="
            background: ${color};
            color: white;
            width: 20px; height: 20px;
            border-radius: 50%;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
          ">${index + 1}</div>
        `,
        iconSize: [20, 20],
        iconAnchor: [10, 10],
      })
      L.marker([parseFloat(visit.latitude), parseFloat(visit.longitude)], { icon: numIcon })
        .addTo(polylinesLayer.value)
    })
  })
}

// Warna unik per sales (biar beda-beda garisnya)
const salesColor = (salesId) => {
  const colors = ['#03c3ec', '#ffab00', '#ff3e1d', '#71dd37', '#696cff', '#20c997', '#fd7e14']
  return colors[salesId % colors.length]
}

// Ref untuk polling
const pollingInterval = ref(null)

// Jalankan polling setiap 30 detik
const startPolling = () => {
  pollingInterval.value = setInterval(() => {
    fetchVisits()
  }, 30000) // 30 detik
}

const stopPolling = () => {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value)
    pollingInterval.value = null
  }
}



</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap');

* { box-sizing: border-box; margin: 0; padding: 0; }

.map-tracking-wrapper {
  font-family: 'DM Sans', sans-serif;
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #0f1117;
  color: #e2e8f0;
}

/* TOPBAR */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  height: 56px;
  background: #161b27;
  border-bottom: 1px solid #1e2535;
  flex-shrink: 0;
  gap: 16px;
}
.topbar-left { display: flex; align-items: center; gap: 24px; }
.topbar-right { display: flex; align-items: center; gap: 8px; }

.brand {
  font-family: 'Space Mono', monospace;
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
}
.brand-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: #03c3ec;
  box-shadow: 0 0 8px #03c3ec;
  animation: pulse 2s infinite;
}

.date-filter {
  display: flex;
  align-items: center;
  gap: 8px;
}
.filter-group {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #1e2535;
  border: 1px solid #2a3348;
  border-radius: 8px;
  padding: 4px 10px;
}
.filter-group label {
  font-size: 10px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.filter-group input {
  background: transparent;
  border: none;
  color: #e2e8f0;
  font-size: 12px;
  font-family: 'DM Sans', sans-serif;
  outline: none;
  cursor: pointer;
}
.filter-sep { color: #64748b; font-size: 12px; }

.stat-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}
.pill-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: currentColor;
}
.pill-planned  { background: rgba(255,171,0,0.15);  color: #ffab00; }
.pill-ongoing  { background: rgba(3,195,236,0.15);  color: #03c3ec; }
.pill-done     { background: rgba(113,221,55,0.15); color: #71dd37; }

/* MAIN LAYOUT */
.main-layout {
  display: flex;
  flex: 1;
  overflow: hidden;
}

/* SIDEBAR */
.sidebar {
  width: 320px;
  background: #161b27;
  border-right: 1px solid #1e2535;
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease;
  flex-shrink: 0;
}
.sidebar.collapsed { width: 48px; }

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 12px;
  border-bottom: 1px solid #1e2535;
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
  white-space: nowrap;
  overflow: hidden;
}
.sidebar-header em { color: #03c3ec; font-style: normal; margin-left: 4px; }

.collapse-btn {
  background: #1e2535;
  border: none;
  color: #94a3b8;
  width: 28px; height: 28px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.2s;
}
.collapse-btn:hover { background: #2a3348; color: #fff; }

.sidebar-search {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-bottom: 1px solid #1e2535;
}
.sidebar-search i { color: #64748b; font-size: 16px; }
.sidebar-search input {
  background: transparent;
  border: none;
  color: #e2e8f0;
  font-size: 13px;
  font-family: 'DM Sans', sans-serif;
  outline: none;
  width: 100%;
}
.sidebar-search input::placeholder { color: #4a5568; }

.visit-list {
  overflow-y: auto;
  flex: 1;
}
.visit-list::-webkit-scrollbar { width: 4px; }
.visit-list::-webkit-scrollbar-track { background: transparent; }
.visit-list::-webkit-scrollbar-thumb { background: #2a3348; border-radius: 4px; }

.visit-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-bottom: 1px solid #1a2030;
  cursor: pointer;
  transition: background 0.15s;
}
.visit-item:hover { background: #1a2232; }
.visit-item.active { background: #1e2a3a; border-left: 3px solid #03c3ec; }

.visit-avatar {
  position: relative;
  flex-shrink: 0;
}
.visit-avatar img {
  width: 36px; height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #2a3348;
}
.status-dot {
  position: absolute;
  bottom: 0; right: 0;
  width: 10px; height: 10px;
  border-radius: 50%;
  border: 2px solid #161b27;
}

.visit-info { flex: 1; min-width: 0; }
.visit-sales {
  font-size: 13px;
  font-weight: 600;
  color: #e2e8f0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.visit-company {
  font-size: 11px;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 2px;
}
.visit-time {
  font-size: 10px;
  color: #475569;
  margin-top: 2px;
  font-family: 'Space Mono', monospace;
}

.visit-status-badge {
  font-size: 9px;
  font-weight: 700;
  padding: 3px 7px;
  border-radius: 20px;
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  flex-shrink: 0;
}

.type-badge {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 5px;
  border-radius: 4px;
  flex-shrink: 0;
}
.badge-lead     { background: rgba(105,108,255,0.2); color: #696cff; }
.badge-customer { background: rgba(3,195,236,0.2);   color: #03c3ec; }

/* STATUS COLORS */
.status-planned  { background: rgba(255,171,0,0.15);  color: #ffab00; }
.status-ongoing  { background: rgba(3,195,236,0.15);  color: #03c3ec; }
.status-done     { background: rgba(113,221,55,0.15); color: #71dd37; }
.status-unknown  { background: rgba(133,146,163,0.15); color: #8592a3; }

/* MAP */
.map-container {
  flex: 1;
  position: relative;
}
#leaflet-map {
  width: 100%;
  height: 100%;
}

/* LOADING */
.map-loading {
  position: absolute;
  inset: 0;
  background: rgba(15,17,23,0.75);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  z-index: 1000;
  font-size: 13px;
  color: #94a3b8;
}
.loader-ring {
  width: 36px; height: 36px;
  border: 3px solid #1e2535;
  border-top-color: #03c3ec;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* DETAIL CARD */
.detail-card {
  position: absolute;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  width: 360px;
  background: #161b27;
  border: 1px solid #1e2535;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.5);
  z-index: 999;
  overflow: hidden;
}
.detail-close {
  position: absolute;
  top: 10px; right: 10px;
  background: #1e2535;
  border: none;
  color: #94a3b8;
  width: 28px; height: 28px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: background 0.2s;
}
.detail-close:hover { background: #2a3348; color: #fff; }

.detail-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 16px 12px;
  border-bottom: 1px solid #1e2535;
}
.detail-avatar {
  width: 48px; height: 48px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #2a3348;
}
.detail-sales { font-size: 15px; font-weight: 600; color: #fff; }
.detail-status {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  display: inline-block;
  margin-top: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-body { padding: 12px 16px 16px; }
.detail-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 6px 0;
  font-size: 13px;
  color: #94a3b8;
  border-bottom: 1px solid #1a2030;
}
.detail-row:last-child { border-bottom: none; }
.detail-row i { color: #475569; font-size: 15px; margin-top: 1px; flex-shrink: 0; }
.detail-row span { line-height: 1.5; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

/* TRANSITIONS */
.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(20px);
}

.last-update {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #475569;
  font-family: 'Space Mono', monospace;
}


.filter-group select {
  background: transparent;
  border: none;
  color: #e2e8f0;
  font-size: 12px;
  font-family: 'DM Sans', sans-serif;
  outline: none;
  cursor: pointer;
  max-width: 140px;
}

.filter-group select option {
  background: #1e2535;
  color: #e2e8f0;
}


/* LEGEND */
.map-legend {
  position: absolute;
  bottom: 24px;
  right: 16px;
  background: #161b27;
  border: 1px solid #1e2535;
  border-radius: 12px;
  padding: 12px 14px;
  z-index: 998;
  min-width: 160px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
}
.legend-title {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #475569;
  margin-bottom: 8px;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #94a3b8;
  margin-bottom: 6px;
}
.legend-item:last-child { margin-bottom: 0; }
.legend-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.legend-divider {
  border-top: 1px solid #1e2535;
  margin: 10px 0;
}
.legend-badge {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 4px;
}

/* ANIMATIONS */
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 6px #03c3ec; }
  50% { box-shadow: 0 0 14px #03c3ec; }
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>