
<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue"
import { useDataCustomerVisitStore } from '../../../../stores/customersVisitsStore'
import { useRoute, useRouter } from "vue-router"
import { toasts } from "@/utils/toasts"

// ======================================================
// BASIC
// ======================================================
const PagesTitle = 'Data Customers Ready To Visit'
const router = useRouter()
const route = useRoute()
const dataCustomerVisit = useDataCustomerVisitStore()

// ======================================================
// STATUS OPTIONS (FIX ERROR UTAMA)
// ======================================================
const statusOptions = [
  { value: 'Collection / Payment Follow-up', label: 'Collection / Payment Follow-up' },
  { value: 'Restocking / Taking Order (TO)', label: 'Restocking / Taking Order (TO)' },
  { value: 'Routine Maintenance / Courtesy Call', label: 'Routine Maintenance / Courtesy Call' },
  { value: 'Product Handling / Complaint', label: 'Product Handling / Complaint' },
  { value: 'Active & Productive', label: 'Active & Productive' },
  { value: 'Inactive / No Order', label: 'Inactive / No Order' },
  { value: 'At Risk / Complaint', label: 'At Risk / Complaint' },
  { value: 'Churn / Closed', label: 'Churn / Closed' }
]

const statusBadgeMap = {
  Active: 'bg-success',
  Dormant: 'bg-warning text-dark',
  Inactive: 'bg-secondary',
  Lost: 'bg-danger',
  Blacklist: 'bg-dark'
}

const getStatusBadgeClass = (status) => {
  if (!status) return 'bg-light text-muted'
  return statusBadgeMap[status] || 'bg-light text-dark'
}


const selectedCustomer = ref(null)

const openVisitModal = (customer) => {
  selectedCustomer.value = customer
}


// ======================================================
// DATE & TIME
// ======================================================
const currentDate = ref('')
const currentTime = ref('')
let timer = null

const updateDateTime = () => {
  const now = new Date()
  currentDate.value = now.toLocaleDateString('en-GB', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  }).replace(',', ' /')

  currentTime.value = now.toLocaleTimeString('en-GB', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

// ======================================================
// CAMERA
// ======================================================
const videoRef = ref(null)
let stream = null
const isCameraReady = ref(false)
const isProcessingPhoto = ref(false)

const startCamera = async () => {
  if (stream) return
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { exact: "environment" } },
      audio: false
    })
  } catch {
    stream = await navigator.mediaDevices.getUserMedia({ video: true })
  }
  videoRef.value.srcObject = stream
}

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(t => t.stop())
    stream = null
  }
  if (videoRef.value) videoRef.value.srcObject = null
}

// ======================================================
// PHOTO
// ======================================================
const photoBlob = ref(null)
const photoPreview = ref(null)
const rawCanvas = ref(null)

watch(photoBlob, (newBlob, oldBlob) => {
  if (photoPreview.value) URL.revokeObjectURL(photoPreview.value)
  photoPreview.value = newBlob ? URL.createObjectURL(newBlob) : null
})

// ======================================================
// LOCATION
// ======================================================
const latitude = ref(null)
const longitude = ref(null)
const accuracy = ref(null)
const address = ref('')
const locationStatus = ref('Waiting photo...')

const getAddressFromLatLng = async (lat, lng) => {
  try {
    const res = await fetch(`/api/reverse-geocode?lat=${lat}&lon=${lng}`)
    const data = await res.json()
    address.value = data.display_name || 'Address not found'
  } catch {
    address.value = 'Unknown location'
  }
}

const getLocation = () => {
  return new Promise(resolve => {
    if (!navigator.geolocation) return resolve()
    navigator.geolocation.getCurrentPosition(
      async pos => {
        latitude.value = pos.coords.latitude
        longitude.value = pos.coords.longitude
        accuracy.value = pos.coords.accuracy
        await getAddressFromLatLng(latitude.value, longitude.value)
        resolve()
      },
      () => resolve(),
      { timeout: 20000 }
    )
  })
}

// ======================================================
// WATERMARK
// ======================================================
const wrapText = (ctx, text, maxWidth) => {
  const words = text.split(' ')
  const lines = []
  let line = ''
  words.forEach(word => {
    const test = line + word + ' '
    if (ctx.measureText(test).width > maxWidth && line) {
      lines.push(line)
      line = word + ' '
    } else line = test
  })
  lines.push(line)
  return lines
}

const finalizePhotoWithWatermark = async () => {
  const canvas = rawCanvas.value
  const ctx = canvas.getContext('2d')

  const padding = 20
  const lineHeight = 22
  const maxWidth = canvas.width - padding * 2

  ctx.font = '14px Arial'
  const lines = wrapText(ctx, `📍 ${address.value}`, maxWidth)
  lines.push(`🕒 ${currentDate.value} ${currentTime.value}`)

  const boxHeight = lines.length * lineHeight + padding * 2
  const boxY = canvas.height - boxHeight

  ctx.fillStyle = 'rgba(0,0,0,0.6)'
  ctx.fillRect(0, boxY, canvas.width, boxHeight)

  ctx.fillStyle = '#fff'
  lines.forEach((l, i) => {
    ctx.fillText(l, padding, boxY + padding + i * lineHeight)
  })

  photoBlob.value = await new Promise(r =>
    canvas.toBlob(r, 'image/jpeg', 0.9)
  )
}

// ======================================================
// TAKE PHOTO
// ======================================================
const takePhoto = async () => {
  if (isProcessingPhoto.value) return
  isProcessingPhoto.value = true

  updateDateTime()

  const canvas = document.createElement('canvas')
  canvas.width = videoRef.value.videoWidth
  canvas.height = videoRef.value.videoHeight
  const ctx = canvas.getContext('2d')
  ctx.drawImage(videoRef.value, 0, 0)

  rawCanvas.value = canvas
  photoBlob.value = await new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.8))

  await getLocation()
  await finalizePhotoWithWatermark()

  isProcessingPhoto.value = false
}

// ======================================================
// FORM
// ======================================================
const form = reactive({
  notes: '',
  status: statusOptions[0].value
})

const isSubmitDisabled = computed(() =>
  !photoBlob.value || !latitude.value || !longitude.value || isProcessingPhoto.value
)

const submitVisit = () => {
  if (!photoBlob.value) return
  console.log({
    photo: photoBlob.value,
    lat: latitude.value,
    lng: longitude.value,
    address: address.value,
    notes: form.notes,
    status: form.status
  })
  toasts.success('Customer visit saved')
}

// ======================================================
// RESET
// ======================================================
const resetVisitState = () => {
  stopCamera()
  photoBlob.value = null
  rawCanvas.value = null
  latitude.value = longitude.value = null
  address.value = ''
  form.notes = ''
  form.status = statusOptions[0].value
}

// ======================================================
// LIFECYCLE
// ======================================================
onMounted(async () => {
  updateDateTime()
  timer = setInterval(updateDateTime, 1000)
  await dataCustomerVisit.fetchCustomersVisitStore(
    dataCustomerVisit.buildUrl()
  )

  const modal = document.getElementById('modal-input-visit')
  modal?.addEventListener('shown.bs.modal', startCamera)
  modal?.addEventListener('hidden.bs.modal', resetVisitState)
})

onUnmounted(() => {
  clearInterval(timer)
  resetVisitState()
})
</script>






<template>
  <backendLayouts>
    <div class="page d-flex flex-column">
      <!-- Page Header -->
      <div class="page-header d-print-none">
        <div class="container-xl">
          <div class="row g-2 align-items-center">
            <div class="col">
              <div class="page-pretitle">Overview</div>
              <h4 class="page-title"> {{ PagesTitle }}</h4>
            </div>
            <div class="col-auto ms-auto d-print-none">
              <div class="btn-list">
              
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page"> {{ PagesTitle }}</li>
                </ol>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Page Body -->
      <div class="page-body flex-grow-1">
        <div class="container-xl">
          <!-- Card: Filter & Sort -->
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between flex-wrap gap-3">
              <!-- Kiri -->
             <div class="d-flex flex-column gap-3">
                <!-- Dropdown Tampilkan -->
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 fw-semibold">
                    <i class="fas fa-list-ul me-1"></i> Showing:
                    </label>
                    <select class="form-select w-auto"
                    v-model.number="dataCustomerVisit.pagination.per_page"
                    @change="dataCustomerVisit.changePageSize(dataCustomerVisit.pagination.per_page)">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                    </select>
                </div>

                 <router-link
                  to="/sales-visit"
                  class="btn btn-secondary"
                >
                  <i class="fa-solid fa-arrow-left"></i> Back
                </router-link>
                </div>


              <!-- Kanan -->
             <div class="d-flex flex-column gap-3 align-items-end" style="min-width:300px;">
                <!-- Pencarian -->
                <div class="input-group">
                    <input
                      type="text"
                      class="form-control"
                      placeholder="Searching...."
                      :value="dataCustomerVisit.searchCustomersVisit"
                      @input="dataCustomerVisit.searchWithDelay($event.target.value)"
                    >
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" v-model="dataCustomerVisit.sort.column" @change="dataCustomerVisit.changeSorting">
                    <option value="company_name">By Name</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto" v-model="dataCustomerVisit.sort.direction" @change="dataCustomerVisit.changeSorting">
                    <option value="asc">Ascending</option>
                    <option value="desc">Descending</option>
                    </select>
                </div>
                </div>
            </div>
          </div>

          <!-- Card: Table -->
          <div class="card mb-4">
            <div class="card-header">
             
            </div>
            <div class="table-responsive">
              <table class="table card-table table-vcenter text-nowrap">
                <thead>
                  <tr>
                    <th style="width: 5%;">No.</th>
                    <th>Store / Company Name</th>
                    <th>Contact Name</th>
                    <th>Address</th>
                    <th>Telephone</th>
                    <th>Status</th>
                    <th style="width: 8%;">Actions</th>
                  </tr>
                </thead>

                <tbody v-if="dataCustomerVisit.loadingCustomersVisit">
                <tr>
                  <td colspan="8" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>
              </tbody>

              <!-- EMPTY DATA -->
              <tbody v-else-if="dataCustomerVisit.customersVisitData.length === 0">
                   <tr>
                      <td colspan="8" class="text-center">
                        <div class="d-flex flex-column align-items-center justify-content-center">
                          <img
                            src="https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif"
                            alt="No data found"
                            style="max-width: 250px; height: auto;"
                            class="mb-3"
                          />
                          <p class="text-danger fw-bold fst-italic">
                            <i class="fa fa-exclamation-circle me-1"></i>
                            Customers data not found.
                          </p>
                        </div>
                      </td>
                    </tr>
                </tbody>

                  <tbody v-else>
                  <tr v-for="(lvd, index) in dataCustomerVisit.customersVisitData" :key="lvd.id">
                    <td>{{ index + 1 + dataCustomerVisit.pagination.per_page * (dataCustomerVisit.pagination.current_page - 1) }}.</td>
                    <td>{{ lvd.company_name }}</td>
                    <td>{{ lvd.contact_name }}</td>
                    <td>
                      <!-- Desktop -->
                      <textarea
                        class="form-control d-none d-md-block"
                        rows="2"
                        readonly
                      >{{ lvd.address }}</textarea>

                      <!-- Mobile -->
                      <div class="d-block d-md-none small text-muted text-wrap">
                        {{ lvd.address }}
                      </div>
                    </td>
                    <td>{{ lvd.phone }}</td>
                   <td>
                       <span
                          class="badge rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1"
                          :class="getStatusBadgeClass(lvd.customer_status)"
                        >
                          <i
                            class="fa-solid"
                            :class="{
                              'fa-circle-check': lvd.customer_status === 'Active',
                              'fa-clock': lvd.customer_status === 'Dormant',
                              'fa-pause': lvd.customer_status === 'Inactive',
                              'fa-xmark': lvd.customer_status === 'Lost',
                              'fa-ban': lvd.customer_status === 'Blacklist'
                            }"
                          ></i>
                          {{ lvd.customer_status }}
                        </span>
                    </td>
                    <td>
                     <td>
                      <button
                        class="btn btn-outline-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#modal-input-visit"
                        @click="openVisitModal(lvd)"
                      >
                        <i class="fa-solid fa-street-view"></i> Visit Now
                      </button>
                    </td>
                    </td>
                  </tr>
                
                </tbody>
            

                
            
              
              </table>
            </div>
          </div>

          <!-- Card: Pagination -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <button
                class="btn btn-danger btn-sm"
                :disabled="!dataCustomerVisit.pagination.prev_page_url || dataCustomerVisit.loadingCustomersVisit"
                @click="dataCustomerVisit.goToPage(dataCustomerVisit.pagination.prev_page_url)"
              >
                <i class="fa-solid fa-circle-chevron-left"></i> Prev
              </button>
  
                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                     <span class="badge border text-secondary px-3 py-2">
                      {{ dataCustomerVisit.customersVisitData.length }} data | page {{ dataCustomerVisit.pagination.current_page }}
                    </span>
                    <span class="badge border text-secondary px-3 py-2">
                      Total: {{ dataCustomerVisit.pagination.total }}
                    </span>
                </div>
  
                <button class="btn btn-danger btn-sm"
                :disabled="!dataCustomerVisit.pagination.next_page_url || dataCustomerVisit.loadingCustomersVisit"
                 @click="dataCustomerVisit.goToPage(dataCustomerVisit.pagination.next_page_url)">
                    Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>

        </div>
      </div>
    </div>



<div class="modal modal-blur fade" id="modal-input-visit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          Customer Visit Report
          <div v-if="selectedCustomer" class="text-muted small">
            {{ selectedCustomer.company_name }} — {{ selectedCustomer.contact_name }}
          </div>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <div class="card bg-light text-white overflow-hidden" style="height: 250px;">
              <video 
                  ref="videoRef" 
                  autoplay 
                  playsinline 
                   @loadedmetadata="isCameraReady = true"
                  style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.5rem;"
                ></video>
            </div>
          
            <button
                class="btn btn-primary w-100 mt-2"
                :disabled="!isCameraReady || isProcessingPhoto"
                @click="takePhoto"
              >
                {{ isProcessingPhoto ? 'Processing...' : 'Get Photo Proof' }}
            </button>

          </div>

          
                <div class="col-12 col-lg-6 mt-2">
                  
                    <div class="card mb-2">
                        <div class="position-relative w-100 h-100" style="height:200px">

                            <!-- FOTO + OVERLAY (HANYA JIKA ADA PREVIEW) -->
                            <template v-if="photoPreview" class="card-body p-0 d-flex align-items-center justify-content-center bg-light" style="height: 200px; overflow: hidden;">
                            <img
                                :src="photoPreview"
                                class="w-100 h-100 rounded img-thumbnail shadow-sm"
                                style="object-fit: contain;"
                            />
                            </template>
                          <div
                            v-else
                           class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted opacity-50"
                            >
                            <i class="fa-solid fa-image-portrait fs-1 mb-2 mt-2"></i>
                            <p class="small mb-0">No photo yet</p>
                            </div>
                        </div>
                    </div>



                    <div v-if="photoPreview" class="p-3 border rounded bg-light shadow-sm">
                        <div class="d-flex align-items-center mb-2">
                        <i class="fa-regular fa-calendar-check text-primary me-2"></i>
                        <div>
                            <div class="fw-bold" style="font-size: 0.7rem; color: #666;">VISIT DATE</div>
                            <div class="small fw-semibold text-dark">{{ currentDate }} - {{ currentTime }}</div>
                        </div>
                        </div>

                        <hr class="my-2">

                        <div class="d-flex align-items-start">
                        <i class="fa-solid fa-location-dot text-danger me-2 mt-1"></i>
                        <div style="word-break: break-word;">
                            <div class="fw-bold" style="font-size: 0.7rem; color: #666;">LOCATION SNAPSHOT</div>
                            <div class="small text-muted" style="font-size: 11px; line-height: 1.3;">
                            {{ address || 'Detecting address...' }}
                            </div>
                        </div>
                        </div>
                </div>
                </div>

            <div class="col-12">
            <div class="mb-3">
              <label class="form-label">Notes on Visit Results</label>
              <textarea class="form-control" v-model="form.notes" rows="2" placeholder="Write a note here..."></textarea>
            </div>

            <label class="form-label fw-bold">Update Data Status To (Based on customer response):</label>
            <div class="row g-2">
            <div 
                class="col-12 col-sm-4" 
                v-for="status in statusOptions" 
                :key="status.value"
            >
                <div class="input-group">
                <div class="input-group-text">
                    <input 
                    class="form-check-input mt-0" 
                    type="radio" 
                    v-model="form.status" 
                    :value="status.value"
                    :id="'status-' + status.value"
                    >
                </div>
                <label 
                    class="form-control bg-white cursor-pointer" 
                    :for="'status-' + status.value"
                    style="cursor: pointer;"
                >
                    {{ status.label }}
                </label>
                </div>
            </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
       
      <button
        type="button"
        class="btn btn-success ms-auto"
        :disabled="isSubmitDisabled"
        @click="submitVisit"
      >
        <i class="fa-solid fa-cloud-arrow-up me-2"></i>
        Save Report Customer
      </button>


      </div>
    </div>
  </div>
</div>


  </backendLayouts>
</template>


<style scoped>


</style>