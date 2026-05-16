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

        <!-- Filter Status -->
        <div class="filter-group">
          <label>Status</label>
          <select v-model="selectedStatus" @change="renderMarkers">
            <option value="">All Status</option>
            <option value="BELUM_CHECK_IN">Planned</option>
            <option value="SEDANG_CHECK_IN">On-Site</option>
            <option value="SELESAI">Done</option>
          </select>
        </div>
      </div>

      <div class="last-update">
        <span class="brand-dot"></span>
        Live · Updated {{ lastUpdated }}
      </div>

      <button class="export-btn" @click="exportMap" :disabled="isExporting">
        <i class="bx bx-download"></i>
        <span>{{ isExporting ? 'Exporting...' : 'Export Map' }}</span>
      </button>

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

    <!-- MODAL DETAIL -->
    <transition name="modal-fade">
      <div class="modal-overlay" v-if="selectedVisit" @click.self="selectedVisit = null">
        <div class="modal-card">

          <!-- Header -->
          <div class="modal-header" :class="statusClass(selectedVisit.visit_status_label)">
            <button class="modal-close" @click="selectedVisit = null">
              <i class="bx bx-x"></i>
            </button>
            <div class="modal-hero">
              <div class="modal-avatar-wrap">
                <img
                  :src="selectedVisit.sales_photo_url"
                  class="modal-avatar"
                  @error="$event.target.src=`https://ui-avatars.com/api/?name=${encodeURIComponent(selectedVisit.sales_name)}&background=1e2535&color=fff&size=80&bold=true`"
                />
                <span class="modal-avatar-ring" :class="statusClass(selectedVisit.visit_status_label)"></span>
              </div>
              <div class="modal-hero-info">
                <div class="modal-sales-name">{{ selectedVisit.sales_name }}</div>
                <div class="modal-status-badge" :class="statusClass(selectedVisit.visit_status_label)">
                  <span class="badge-dot"></span>
                  {{ selectedVisit.visit_status_label }}
                </div>
              </div>
            </div>
          </div>

          <!-- Body -->
          <div class="modal-body">

            <!-- Visit Code -->
            <div class="modal-code">
              <i class="bx bx-barcode"></i>
              {{ selectedVisit.visit_code }}
            </div>

            <!-- Target -->
            <div class="modal-section">
              <div class="modal-section-title">Target</div>
              <div class="modal-info-row">
                <i class="bx bx-buildings"></i>
                <div>
                  <span class="type-badge" :class="selectedVisit.target_type === 'LEAD' ? 'badge-lead' : 'badge-customer'">
                    {{ selectedVisit.target_type }}
                  </span>
                  <span class="modal-info-value">{{ selectedVisit.target_name }}</span>
                </div>
              </div>
              <div class="modal-info-row" v-if="selectedVisit.target_contact">
                <i class="bx bx-user"></i>
                <span class="modal-info-value">{{ selectedVisit.target_contact }}</span>
              </div>
              <div class="modal-info-row">
                <i class="bx bx-map-pin"></i>
                <span class="modal-info-value">{{ selectedVisit.gps_snapshot ?? 'No address' }}</span>
              </div>
            </div>

            <!-- Timeline -->
            <div class="modal-section">
              <div class="modal-section-title">Timeline</div>
              <div class="modal-timeline">
                <div class="timeline-item">
                  <div class="timeline-dot dot-plan"></div>
                  <div class="timeline-content">
                    <div class="timeline-label">Planned</div>
                    <div class="timeline-time">{{ formatTime(selectedVisit.visit_at) }}</div>
                  </div>
                </div>
                <div class="timeline-line"></div>
                <div class="timeline-item">
                  <div class="timeline-dot" :class="selectedVisit.check_in_at ? 'dot-checkin' : 'dot-empty'"></div>
                  <div class="timeline-content">
                    <div class="timeline-label">Check-in</div>
                    <div class="timeline-time">{{ selectedVisit.check_in_at ? formatTime(selectedVisit.check_in_at) : '-' }}</div>
                  </div>
                </div>
                <div class="timeline-line"></div>
                <div class="timeline-item">
                  <div class="timeline-dot" :class="selectedVisit.check_out_at ? 'dot-checkout' : 'dot-empty'"></div>
                  <div class="timeline-content">
                    <div class="timeline-label">Check-out</div>
                    <div class="timeline-time">{{ selectedVisit.check_out_at ? formatTime(selectedVisit.check_out_at) : '-' }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Duration -->
            <div class="modal-duration" v-if="selectedVisit.check_in_at && selectedVisit.check_out_at">
              <i class="bx bx-time-five"></i>
              <span>Durasi kunjungan: <strong>{{ calcDuration(selectedVisit.check_in_at, selectedVisit.check_out_at) }}</strong></span>
            </div>

          </div>

          <!-- Footer -->
          <div class="modal-footer">
            <a
              :href="`https://www.google.com/maps?q=${selectedVisit.latitude},${selectedVisit.longitude}`"
              target="_blank"
              class="modal-btn btn-maps"
            >
              <i class="bx bx-map-alt"></i> Buka di Maps
            </a>
            <button class="modal-btn btn-close" @click="selectedVisit = null">
              Tutup
            </button>
          </div>

        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick, markRaw } from 'vue'
import axios from 'axios'

// --- STATE ---
const mapRef        = ref(null)
const mapInstance   = ref(null)
const visits        = ref([])
const selectedVisit = ref(null)
const isLoading     = ref(false)
const sidebarCollapsed = ref(false)
const search        = ref('')
const googleMarkers = ref([])
const activePolylines = ref([])
const AdvancedMarkerElement = ref(null)

const today    = new Date().toISOString().split('T')[0]
const dateFrom = ref(today)
const dateTo   = ref(today)
const lastUpdated  = ref('-')
const isExporting  = ref(false)
const pollingInterval = ref(null)

const selectedSalesId = ref('')
const selectedStatus  = ref('')

const legends = [
  { label: 'Belum Check-in', color: '#ffab00' },
  { label: 'Sedang Check-in', color: '#03c3ec' },
  { label: 'Selesai',         color: '#71dd37' },
  { label: 'Unknown',         color: '#8592a3' },
]

// --- LIFECYCLE ---
onMounted(async () => {
  document.getElementById('leaflet-css')?.remove()

  if (!document.getElementById('google-maps-script')) {
    const script = document.createElement('script')
    script.id  = 'google-maps-script'
    script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyAukPU7EoLvUeD4yGtxYkgyeOuxIgATl2A&libraries=marker,geometry&v=weekly`
    script.async = true
    script.defer = true
    script.onload = async () => {
      await initMap()
      await fetchVisits()
      startPolling()
    }
    document.head.appendChild(script)
  } else {
    await initMap()
    await fetchVisits()
    startPolling()
  }
})

onUnmounted(() => stopPolling())

// --- COMPUTED ---
const salesList = computed(() => {
  const map = {}
  visits.value.forEach(v => {
    if (v.sales_id && !map[v.sales_id]) {
      map[v.sales_id] = { id: v.sales_id, name: v.sales_name }
    }
  })
  return Object.values(map).sort((a, b) => a.name.localeCompare(b.name))
})

const filteredVisits = computed(() => {
  const q = search.value.toLowerCase()
  return visits.value.filter(v => {
    const matchSearch = v.sales_name?.toLowerCase().includes(q) || v.target_name?.toLowerCase().includes(q)
    const matchSales  = selectedSalesId.value === '' || v.sales_id == selectedSalesId.value
    const matchStatus = selectedStatus.value  === '' || v.visit_status_label === selectedStatus.value
    return matchSearch && matchSales && matchStatus
  })
})

const statusSummary = computed(() => {
  const planned = visits.value.filter(v => v.visit_status_label === 'BELUM_CHECK_IN').length
  const ongoing = visits.value.filter(v => v.visit_status_label === 'SEDANG_CHECK_IN').length
  const done    = visits.value.filter(v => v.visit_status_label === 'SELESAI').length
  return [
    { label: 'Planned (Sedang Visit)',    count: planned, cls: 'pill-planned' },
    { label: 'On-Site (Sedang Check IN)', count: ongoing, cls: 'pill-ongoing' },
    { label: 'Done (Check OUT Selesai)',  count: done,    cls: 'pill-done'    },
  ]
})

// --- HELPERS ---
const statusClass = (label) => {
  if (label === 'BELUM_CHECK_IN')  return 'status-planned'
  if (label === 'SEDANG_CHECK_IN') return 'status-ongoing'
  if (label === 'SELESAI')         return 'status-done'
  return 'status-unknown'
}

const formatTime = (dt) => {
  if (!dt) return '-'
  return new Date(dt).toLocaleString('id-ID', {
    hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short'
  })
}

const markerColor = (label) => {
  if (label === 'BELUM_CHECK_IN')  return '#ffab00'
  if (label === 'SEDANG_CHECK_IN') return '#03c3ec'
  if (label === 'SELESAI')         return '#71dd37'
  return '#8592a3'
}

const salesColor = (salesId) => {
  const colors = ['#03c3ec', '#ffab00', '#ff3e1d', '#71dd37', '#696cff', '#20c997', '#fd7e14']
  return colors[salesId % colors.length]
}

const calcDuration = (checkIn, checkOut) => {
  const diff = new Date(checkOut) - new Date(checkIn)
  const mins = Math.floor(diff / 60000)
  if (mins < 60) return `${mins} menit`
  const h = Math.floor(mins / 60)
  const m = mins % 60
  return m > 0 ? `${h} jam ${m} menit` : `${h} jam`
}

// --- MAP INIT ---
const initMap = async () => {
  if (mapInstance.value || !window.google) return

  const [{ Map }, { AdvancedMarkerElement: AME }] = await Promise.all([
    google.maps.importLibrary("maps"),
    google.maps.importLibrary("marker"),
  ])

  AdvancedMarkerElement.value = markRaw(AME)

  const map = new Map(mapRef.value, {
    center: { lat: -6.1574, lng: 106.7110 },
    zoom: 20,
    mapId: 'b690a5c1fdb329231a42c571',
    mapTypeId: 'hybrid',
    streetViewControl: true,
    streetViewControlOptions: { position: google.maps.ControlPosition.LEFT_BOTTOM },
    fullscreenControl: true,
    fullscreenControlOptions: { position: google.maps.ControlPosition.LEFT_BOTTOM },
    zoomControl: true,
    zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_BOTTOM },
    disableDefaultUI: false,
  })

  mapInstance.value = markRaw(map)
}

// --- FETCH ---
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
  if (!mapInstance.value || !AdvancedMarkerElement.value) return

  googleMarkers.value.forEach(m => (m.map = null))
  googleMarkers.value = []

  const bounds = new google.maps.LatLngBounds()

  // Sort ascending visit_at — terlama = no. 1
  const sorted = [...filteredVisits.value].sort((a, b) =>
    new Date(a.visit_at) - new Date(b.visit_at)
  )

  // Counter nomor urut per sales
  const salesCounter = {}

//   sorted.forEach((visit, index) => {
//     if (!visit.latitude || !visit.longitude) return

//     if (!salesCounter[visit.sales_id]) salesCounter[visit.sales_id] = 0
//     salesCounter[visit.sales_id]++
//     const number = salesCounter[visit.sales_id]

//     const lat   = parseFloat(visit.latitude)
//     const lng   = parseFloat(visit.longitude)
//     const color = markerColor(visit.visit_status_label)

//     const markerEl = document.createElement('div')
//     markerEl.style.cssText = `
//       display: flex;
//       flex-direction: column;
//       align-items: center;
//       cursor: pointer;
//       transition: transform 0.2s ease;
//     `
//     markerEl.innerHTML = `
//       <div style="position: relative; width: 44px; height: 44px;">

//         <!-- Nomor urut per sales -->
//         <div style="
//           position: absolute; top: -6px; left: -6px;
//           width: 18px; height: 18px; border-radius: 50%;
//           background: #0f1117; border: 2px solid ${color}; color: ${color};
//           font-size: 9px; font-weight: 700; font-family: 'Space Mono', monospace;
//           display: flex; align-items: center; justify-content: center;
//           z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.5);
//         ">${number}</div>

//         <!-- Ring warna status -->
//         <div style="
//           position: absolute; inset: 0; border-radius: 50%;
//           border: 3px solid ${color}; box-shadow: 0 0 10px ${color}88;
//         "></div>

//         <!-- Foto sales -->
//         <img
//           src="${visit.sales_photo_url}"
//           style="width:100%; height:100%; border-radius:50%; object-fit:cover; border:2px solid white; display:block;"
//           onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(visit.sales_name)}&background=1e2535&color=ffffff&size=44&bold=true'"
//         />

//         <!-- Badge status dot kanan bawah -->
//         <div style="
//           position: absolute; bottom: 0; right: 0;
//           width: 12px; height: 12px; border-radius: 50%;
//           background: ${color}; border: 2px solid white;
//           box-shadow: 0 1px 3px rgba(0,0,0,0.4);
//         "></div>
//       </div>

//       <!-- Ekor pin segitiga -->
//       <div style="
//         width:0; height:0;
//         border-left:6px solid transparent; border-right:6px solid transparent;
//         border-top:8px solid ${color}; margin-top:-1px;
//         filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));
//       "></div>

//       <!-- Label nama + badge type -->
//       <!-- Label nama + badge type -->
// <div style="
//   margin-top: 3px; background: rgba(15,17,23,0.85); color: white;
//   font-size: 10px; font-weight: 600; font-family: 'DM Sans', sans-serif;
//   padding: 2px 7px; border-radius: 10px; white-space: nowrap;
//   border: 1px solid ${color}55; box-shadow: 0 2px 6px rgba(0,0,0,0.4);
//   max-width: 120px; overflow: hidden; text-overflow: ellipsis;
//   display: flex; align-items: center; gap: 4px;
// ">
//   <span style="
//     font-size: 8px; font-weight: 700; padding: 1px 4px; border-radius: 3px; flex-shrink: 0;
//     background: ${visit.target_type === 'LEAD' ? 'rgba(105,108,255,0.3)' : 'rgba(3,195,236,0.3)'};
//     color: ${visit.target_type === 'LEAD' ? '#696cff' : '#03c3ec'};
//     border: 1px solid ${visit.target_type === 'LEAD' ? '#696cff55' : '#03c3ec55'};
//   ">${visit.target_type}</span>
//   ${visit.sales_name.split(' ')[0]}
// </div>

// <!-- Nama perusahaan -->
// <div style="
//   margin-top: 2px; background: rgba(15,17,23,0.75); color: #94a3b8;
//   font-size: 9px; font-weight: 500; font-family: 'DM Sans', sans-serif;
//   padding: 1px 6px; border-radius: 8px; white-space: nowrap;
//   max-width: 130px; overflow: hidden; text-overflow: ellipsis;
//   text-align: center;
// ">${visit.target_name}</div>
//     `

//     markerEl.addEventListener('mouseenter', () => {
//       markerEl.style.transform = 'scale(1.15) translateY(-4px)'
//       markerEl.style.zIndex = '999'
//     })
//     markerEl.addEventListener('mouseleave', () => {
//       markerEl.style.transform = 'scale(1)'
//       markerEl.style.zIndex = ''
//     })

//     try {
//       const marker = new AdvancedMarkerElement.value({
//         map: mapInstance.value,
//         position: { lat, lng },
//         content: markerEl,
//         title: `#${number} · ${visit.sales_name} - ${visit.target_name}`,
//       })

//       marker.addEventListener('gmp-click', () => selectVisit(visit))
//       googleMarkers.value.push(markRaw(marker))
//       bounds.extend({ lat, lng })
//     } catch (err) {
//       console.error(`Marker [${index}] FAILED:`, err)
//     }
//   })

sorted.forEach((visit, index) => {
    if (!visit.latitude || !visit.longitude) return

    if (!salesCounter[visit.sales_id]) salesCounter[visit.sales_id] = 0
    salesCounter[visit.sales_id]++
    const number = salesCounter[visit.sales_id]

    const lat   = parseFloat(visit.latitude)
    const lng   = parseFloat(visit.longitude)
    const color = markerColor(visit.visit_status_label)

    const markerEl = document.createElement('div')
    markerEl.style.cssText = `
      display: flex;
      flex-direction: column;
      align-items: center;
      cursor: pointer;
      transition: transform 0.2s ease;
    `
    markerEl.innerHTML = `
      <div style="position: relative; width: 44px; height: 44px;">
        <div style="
          position: absolute; top: -6px; left: -6px;
          width: 18px; height: 18px; border-radius: 50%;
          background: #0f1117; border: 2px solid ${color}; color: ${color};
          font-size: 9px; font-weight: 700; font-family: 'Space Mono', monospace;
          display: flex; align-items: center; justify-content: center;
          z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.5);
        ">${number}</div>
        <div style="
          position: absolute; inset: 0; border-radius: 50%;
          border: 3px solid ${color}; box-shadow: 0 0 10px ${color}88;
        "></div>
        <img
          src="${visit.sales_photo_url}"
          style="width:100%; height:100%; border-radius:50%; object-fit:cover; border:2px solid white; display:block;"
          onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(visit.sales_name)}&background=1e2535&color=ffffff&size=44&bold=true'"
        />
        <div style="
          position: absolute; bottom: 0; right: 0;
          width: 12px; height: 12px; border-radius: 50%;
          background: ${color}; border: 2px solid white;
          box-shadow: 0 1px 3px rgba(0,0,0,0.4);
        "></div>
      </div>
      <div style="
        width:0; height:0;
        border-left:6px solid transparent; border-right:6px solid transparent;
        border-top:8px solid ${color}; margin-top:-1px;
        filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));
      "></div>
      <div style="
        margin-top: 3px; background: rgba(15,17,23,0.85); color: white;
        font-size: 10px; font-weight: 600; font-family: 'DM Sans', sans-serif;
        padding: 2px 7px; border-radius: 10px; white-space: nowrap;
        border: 1px solid ${color}55; box-shadow: 0 2px 6px rgba(0,0,0,0.4);
        max-width: 120px; overflow: hidden; text-overflow: ellipsis;
        display: flex; align-items: center; gap: 4px;
      ">
        <span style="
          font-size: 8px; font-weight: 700; padding: 1px 4px; border-radius: 3px; flex-shrink: 0;
          background: ${visit.target_type === 'LEAD' ? 'rgba(105,108,255,0.3)' : 'rgba(3,195,236,0.3)'};
          color: ${visit.target_type === 'LEAD' ? '#696cff' : '#03c3ec'};
          border: 1px solid ${visit.target_type === 'LEAD' ? '#696cff55' : '#03c3ec55'};
        ">${visit.target_type}</span>
        ${visit.sales_name.split(' ')[0]}
      </div>
      <div style="
        margin-top: 2px; background: rgba(15,17,23,0.75); color: #94a3b8;
        font-size: 9px; font-weight: 500; font-family: 'DM Sans', sans-serif;
        padding: 1px 6px; border-radius: 8px; white-space: nowrap;
        max-width: 130px; overflow: hidden; text-overflow: ellipsis;
        text-align: center;
      ">${visit.target_name}</div>
    `

    // ✅ TARUH DI SINI — setelah innerHTML, sebelum try
    markerEl.addEventListener('click', () => selectVisit(visit))

    markerEl.addEventListener('mouseenter', () => {
      markerEl.style.transform = 'scale(1.15) translateY(-4px)'
      markerEl.style.zIndex = '999'
    })
    markerEl.addEventListener('mouseleave', () => {
      markerEl.style.transform = 'scale(1)'
      markerEl.style.zIndex = ''
    })

    try {
      const marker = new AdvancedMarkerElement.value({
        map: mapInstance.value,
        position: { lat, lng },
        content: markerEl,
        title: `#${number} · ${visit.sales_name} - ${visit.target_name}`,
      })

      marker.addEventListener('gmp-click', () => selectVisit(visit))
      googleMarkers.value.push(markRaw(marker))
      bounds.extend({ lat, lng })
    } catch (err) {
      console.error(`Marker [${index}] FAILED:`, err)
    }
  })

  if (googleMarkers.value.length > 0) {
    mapInstance.value.fitBounds(bounds, { padding: 80 })
  }

  renderPolylines()
}

// --- SELECT VISIT ---
const selectVisit = (visit) => {
  selectedVisit.value = visit

  if (visit.latitude && visit.longitude && mapInstance.value) {
    const pos = {
      lat: parseFloat(visit.latitude),
      lng: parseFloat(visit.longitude)
    }

    mapInstance.value.setZoom(11)

    setTimeout(() => {
      mapInstance.value.panTo(pos)
      setTimeout(() => {
        mapInstance.value.setZoom(18)
      }, 600)
    }, 300)
  }
}

// --- POLYLINES ---
const renderPolylines = () => {
  if (!mapInstance.value || !window.google) return

  activePolylines.value.forEach(p => p.setMap(null))
  activePolylines.value = []

  const grouped = {}
  filteredVisits.value.forEach(v => {
    if (!v.latitude || !v.longitude) return
    if (!grouped[v.sales_id]) grouped[v.sales_id] = []
    grouped[v.sales_id].push(v)
  })

  Object.entries(grouped).forEach(([salesId, salesVisits]) => {
    if (salesVisits.length < 2) return

    const sorted = [...salesVisits].sort((a, b) => new Date(a.visit_at) - new Date(b.visit_at))
    const path   = sorted.map(v => ({ lat: parseFloat(v.latitude), lng: parseFloat(v.longitude) }))
    const color  = salesColor(parseInt(salesId))

    const line = new google.maps.Polyline({
      path,
      strokeColor: color,
      strokeOpacity: 0.6,
      strokeWeight: 2,
      map: mapInstance.value,
      icons: [{
        icon: { path: 'M 0,-1 0,1', strokeOpacity: 1, scale: 2 },
        offset: '0',
        repeat: '10px'
      }],
    })
    activePolylines.value.push(line)
  })
}

// --- WATCHERS ---
watch(filteredVisits, () => {
  if (mapInstance.value) nextTick(() => renderMarkers())
}, { deep: true })

watch(sidebarCollapsed, () => {
  setTimeout(() => {
    if (mapInstance.value) google.maps.event.trigger(mapInstance.value, "resize")
  }, 350)
})

// --- POLLING ---
const startPolling = () => {
  pollingInterval.value = setInterval(() => fetchVisits(), 30000)
}

const stopPolling = () => {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value)
    pollingInterval.value = null
  }
}

// --- EXPORT MAP ---
const exportMap = async () => {
  isExporting.value = true
  try {
    if (!window.html2canvas) {
      await new Promise((resolve) => {
        const script = document.createElement('script')
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'
        script.onload = resolve
        document.head.appendChild(script)
      })
    }

    const mapEl = document.querySelector('.map-container')
    const canvas = await window.html2canvas(mapEl, {
      useCORS: true, allowTaint: true,
      backgroundColor: '#0f1117', scale: 2, logging: false,
    })

    const ctx  = canvas.getContext('2d')
    const text = `Live Field Tracker · ${dateFrom.value} s/d ${dateTo.value} · Updated ${lastUpdated.value}`
    ctx.fillStyle = 'rgba(0,0,0,0.5)'
    ctx.fillRect(0, canvas.height - 36, canvas.width, 36)
    ctx.fillStyle = '#ffffff'
    ctx.font = '13px monospace'
    ctx.fillText(text, 16, canvas.height - 12)

    const link = document.createElement('a')
    link.download = `field-tracker-${dateFrom.value}.png`
    link.href = canvas.toDataURL('image/png')
    link.click()
  } catch (e) {
    console.error('Export failed:', e)
    alert('Export gagal, coba lagi.')
  } finally {
    isExporting.value = false
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
.topbar-left  { display: flex; align-items: center; gap: 24px; }
.topbar-right { display: flex; align-items: center; gap: 8px; }

.brand {
  font-family: 'Space Mono', monospace;
  font-size: 13px; font-weight: 700; color: #fff;
  display: flex; align-items: center; gap: 8px; white-space: nowrap;
}
.brand-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #03c3ec; box-shadow: 0 0 8px #03c3ec;
  animation: pulse 2s infinite;
}

.date-filter { display: flex; align-items: center; gap: 8px; }
.filter-group {
  display: flex; align-items: center; gap: 6px;
  background: #1e2535; border: 1px solid #2a3348;
  border-radius: 8px; padding: 4px 10px;
}
.filter-group label {
  font-size: 10px; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.5px;
}
.filter-group input, .filter-group select {
  background: transparent; border: none; color: #e2e8f0;
  font-size: 12px; font-family: 'DM Sans', sans-serif;
  outline: none; cursor: pointer; max-width: 140px;
}
.filter-group select option { background: #1e2535; color: #e2e8f0; }
.filter-sep { color: #64748b; font-size: 12px; }

.stat-pill {
  display: flex; align-items: center; gap: 6px;
  padding: 4px 12px; border-radius: 20px;
  font-size: 12px; font-weight: 500;
}
.pill-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.pill-planned { background: rgba(255,171,0,0.15);  color: #ffab00; }
.pill-ongoing { background: rgba(3,195,236,0.15);  color: #03c3ec; }
.pill-done    { background: rgba(113,221,55,0.15); color: #71dd37; }

.last-update {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; color: #475569;
  font-family: 'Space Mono', monospace;
}

.export-btn {
  display: flex; align-items: center; gap: 6px;
  background: rgba(113,221,55,0.15); border: 1px solid rgba(113,221,55,0.3);
  color: #71dd37; border-radius: 8px; padding: 5px 12px;
  font-size: 12px; font-family: 'DM Sans', sans-serif;
  cursor: pointer; transition: all 0.2s; white-space: nowrap;
}
.export-btn:hover    { background: rgba(113,221,55,0.25); }
.export-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.export-btn i { font-size: 14px; }

/* MAIN LAYOUT */
.main-layout { display: flex; flex: 1; overflow: hidden; position: relative; }

/* SIDEBAR */
.sidebar {
  width: 320px; background: #161b27;
  border-right: 1px solid #1e2535;
  display: flex; flex-direction: column;
  transition: width 0.3s ease; flex-shrink: 0;
}
.sidebar.collapsed { width: 48px; }

.sidebar-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 12px; border-bottom: 1px solid #1e2535;
  font-size: 13px; font-weight: 600; color: #94a3b8;
  white-space: nowrap; overflow: hidden;
}
.sidebar-header em { color: #03c3ec; font-style: normal; margin-left: 4px; }

.collapse-btn {
  background: #1e2535; border: none; color: #94a3b8;
  width: 28px; height: 28px; border-radius: 6px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: background 0.2s;
}
.collapse-btn:hover { background: #2a3348; color: #fff; }

.sidebar-search {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 12px; border-bottom: 1px solid #1e2535;
}
.sidebar-search i { color: #64748b; font-size: 16px; }
.sidebar-search input {
  background: transparent; border: none; color: #e2e8f0;
  font-size: 13px; font-family: 'DM Sans', sans-serif;
  outline: none; width: 100%;
}
.sidebar-search input::placeholder { color: #4a5568; }

.visit-list { overflow-y: auto; flex: 1; }
.visit-list::-webkit-scrollbar { width: 4px; }
.visit-list::-webkit-scrollbar-track { background: transparent; }
.visit-list::-webkit-scrollbar-thumb { background: #2a3348; border-radius: 4px; }

.visit-item {
  display: flex; align-items: center; gap: 10px;
  padding: 12px; border-bottom: 1px solid #1a2030;
  cursor: pointer; transition: background 0.15s;
}
.visit-item:hover  { background: #1a2232; }
.visit-item.active { background: #1e2a3a; border-left: 3px solid #03c3ec; }

.visit-avatar { position: relative; flex-shrink: 0; }
.visit-avatar img {
  width: 36px; height: 36px; border-radius: 50%;
  object-fit: cover; border: 2px solid #2a3348;
}
.status-dot {
  position: absolute; bottom: 0; right: 0;
  width: 10px; height: 10px; border-radius: 50%;
  border: 2px solid #161b27;
}

.visit-info { flex: 1; min-width: 0; }
.visit-sales {
  font-size: 13px; font-weight: 600; color: #e2e8f0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.visit-company {
  font-size: 11px; color: #64748b;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  display: flex; align-items: center; gap: 4px; margin-top: 2px;
}
.visit-time {
  font-size: 10px; color: #475569; margin-top: 2px;
  font-family: 'Space Mono', monospace;
}
.visit-status-badge {
  font-size: 9px; font-weight: 700; padding: 3px 7px;
  border-radius: 20px; white-space: nowrap;
  text-transform: uppercase; letter-spacing: 0.3px; flex-shrink: 0;
}

.type-badge {
  font-size: 9px; font-weight: 700;
  padding: 2px 5px; border-radius: 4px; flex-shrink: 0;
}
.badge-lead     { background: rgba(105,108,255,0.2); color: #696cff; }
.badge-customer { background: rgba(3,195,236,0.2);   color: #03c3ec; }

.status-planned { background: rgba(255,171,0,0.15);   color: #ffab00; }
.status-ongoing { background: rgba(3,195,236,0.15);   color: #03c3ec; }
.status-done    { background: rgba(113,221,55,0.15);  color: #71dd37; }
.status-unknown { background: rgba(133,146,163,0.15); color: #8592a3; }

.empty-state {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 8px; padding: 40px 20px;
  color: #475569; font-size: 13px;
}
.empty-state i { font-size: 32px; }

/* MAP */
.map-container { flex: 1; position: relative; }
#leaflet-map   { width: 100%; height: 100%; }

/* LOADING */
.map-loading {
  position: absolute; inset: 0;
  background: rgba(15,17,23,0.75);
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 12px; z-index: 1000; font-size: 13px; color: #94a3b8;
}
.loader-ring {
  width: 36px; height: 36px;
  border: 3px solid #1e2535; border-top-color: #03c3ec;
  border-radius: 50%; animation: spin 0.8s linear infinite;
}

/* LEGEND */
.map-legend {
  position: absolute; top: 60px; right: 10px;
  background: #161b27; border: 1px solid #1e2535;
  border-radius: 12px; padding: 12px 14px;
  z-index: 998; min-width: 160px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
}
.legend-title {
  font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.8px; color: #94a3b8; margin-bottom: 8px;
}
.legend-item {
  display: flex; align-items: center; gap: 8px;
  font-size: 12px; color: #94a3b8; margin-bottom: 6px;
}
.legend-item:last-child { margin-bottom: 0; }
.legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.legend-divider { border-top: 1px solid #1e2535; margin: 10px 0; }
.legend-badge { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 4px; }

/* MODAL */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
  z-index: 2000; display: flex;
  align-items: center; justify-content: center; padding: 20px;
}
.modal-card {
  background: #161b27; border: 1px solid #1e2535;
  border-radius: 20px; width: 100%; max-width: 420px;
  box-shadow: 0 30px 80px rgba(0,0,0,0.6); overflow: hidden;
}

.modal-header {
  position: relative; padding: 24px 20px 20px; overflow: hidden;
}
.modal-header.status-planned { border-top: 3px solid #ffab00; }
.modal-header.status-ongoing { border-top: 3px solid #03c3ec; }
.modal-header.status-done    { border-top: 3px solid #71dd37; }
.modal-header.status-unknown { border-top: 3px solid #8592a3; }

.modal-close {
  position: absolute; top: 12px; right: 12px;
  background: #1e2535; border: none; color: #94a3b8;
  width: 30px; height: 30px; border-radius: 50%; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; transition: all 0.2s; z-index: 2;
}
.modal-close:hover { background: #2a3348; color: #fff; }

.modal-hero { display: flex; align-items: center; gap: 14px; }
.modal-avatar-wrap { position: relative; flex-shrink: 0; }
.modal-avatar {
  width: 60px; height: 60px; border-radius: 50%;
  object-fit: cover; border: 3px solid #1e2535; display: block;
}
.modal-avatar-ring {
  position: absolute; inset: -4px; border-radius: 50%;
  border: 2px solid transparent;
}
.modal-avatar-ring.status-planned { border-color: #ffab00; box-shadow: 0 0 10px #ffab0066; }
.modal-avatar-ring.status-ongoing { border-color: #03c3ec; box-shadow: 0 0 10px #03c3ec66; }
.modal-avatar-ring.status-done    { border-color: #71dd37; box-shadow: 0 0 10px #71dd3766; }

.modal-sales-name { font-size: 17px; font-weight: 700; color: #fff; }
.modal-status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 700; padding: 3px 10px;
  border-radius: 20px; margin-top: 5px;
  text-transform: uppercase; letter-spacing: 0.5px;
}
.badge-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.modal-body { padding: 16px 20px; }
.modal-code {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; font-family: 'Space Mono', monospace;
  color: #475569; margin-bottom: 14px;
}
.modal-code i { font-size: 13px; }

.modal-section { margin-bottom: 16px; }
.modal-section-title {
  font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.8px; color: #475569; margin-bottom: 8px;
}
.modal-info-row {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 6px 0; border-bottom: 1px solid #1a2030; font-size: 13px;
}
.modal-info-row:last-child { border-bottom: none; }
.modal-info-row i { color: #475569; font-size: 15px; margin-top: 1px; flex-shrink: 0; }
.modal-info-value { color: #94a3b8; line-height: 1.5; }

.modal-timeline { display: flex; align-items: center; }
.timeline-item {
  display: flex; flex-direction: column;
  align-items: center; gap: 6px; flex: 1;
}
.timeline-line { flex: 1; height: 2px; background: #1e2535; margin-bottom: 28px; }
.timeline-dot  { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
.dot-plan     { background: #ffab00; box-shadow: 0 0 6px #ffab0066; }
.dot-checkin  { background: #03c3ec; box-shadow: 0 0 6px #03c3ec66; }
.dot-checkout { background: #71dd37; box-shadow: 0 0 6px #71dd3766; }
.dot-empty    { background: #2a3348; border: 2px solid #1e2535; }
.timeline-content { text-align: center; }
.timeline-label { font-size: 10px; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
.timeline-time  { font-size: 11px; font-family: 'Space Mono', monospace; color: #94a3b8; margin-top: 2px; }

.modal-duration {
  display: flex; align-items: center; gap: 8px;
  background: rgba(3,195,236,0.08); border: 1px solid rgba(3,195,236,0.2);
  border-radius: 10px; padding: 8px 12px;
  font-size: 12px; color: #03c3ec;
}
.modal-duration i { font-size: 15px; }
.modal-duration strong { color: #fff; }

.modal-footer {
  display: flex; gap: 10px;
  padding: 14px 20px; border-top: 1px solid #1e2535;
}
.modal-btn {
  flex: 1; display: flex; align-items: center; justify-content: center;
  gap: 6px; padding: 9px; border-radius: 10px;
  font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif;
  cursor: pointer; transition: all 0.2s; text-decoration: none; border: none;
}
.btn-maps {
  background: rgba(3,195,236,0.15);
  border: 1px solid rgba(3,195,236,0.3); color: #03c3ec;
}
.btn-maps:hover  { background: rgba(3,195,236,0.25); }
.btn-close       { background: #1e2535; color: #94a3b8; }
.btn-close:hover { background: #2a3348; color: #fff; }

/* TRANSITIONS */
.modal-fade-enter-active, .modal-fade-leave-active { transition: all 0.25s ease; }
.modal-fade-enter-from,   .modal-fade-leave-to     { opacity: 0; transform: scale(0.95); }

/* ANIMATIONS */
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 6px #03c3ec; }
  50%       { box-shadow: 0 0 14px #03c3ec; }
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>