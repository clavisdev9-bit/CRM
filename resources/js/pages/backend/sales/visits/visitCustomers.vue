<script setup>
import { ref, reactive, onMounted , watch, onUnmounted, computed } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
const PagesTitle = 'Data Customers Ready To Visit';


// start code frontend camera and location
// ===== DATE TIME =====
const currentDate = ref('')
const currentTime = ref('')
let timer = null


// ===== CAMERA =====
const videoRef = ref(null)
const photo = ref(null)
let stream = null

// ===== LOCATION =====
const latitude = ref(null)
const longitude = ref(null)
const accuracy = ref(15)
const locationStatus = ref('Waiting photo...')
const address = ref('')
const locationName = ref('')

// ===== DATE TIME =====
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

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(t => t.stop())
    stream = null
  }
}



const takePhoto = async () => {
  if (!videoRef.value) return

  updateDateTime()

  // 🚀 LANGSUNG ambil frame dulu
  const canvas = document.createElement('canvas')
  canvas.width = videoRef.value.videoWidth
  canvas.height = videoRef.value.videoHeight
  const ctx = canvas.getContext('2d')
  ctx.drawImage(videoRef.value, 0, 0)

  const blob = await new Promise(resolve =>
    canvas.toBlob(resolve, 'image/jpeg', 0.9)
  )

  photo.value = new File([blob], `visit_${Date.now()}.jpg`, {
    type: 'image/jpeg'
  })

  // ⬇️ lokasi jalan BELAKANGAN (tidak block preview)
  getLocation()
}



const photoPreview = computed(() => {
  return photo.value ? URL.createObjectURL(photo.value) : null
})



const isCameraReady = ref(false)


const getAddressFromLatLng = async (lat, lng) => {
  try {
    address.value = 'Detecting address...'
    const res = await fetch(`/api/reverse-geocode?lat=${lat}&lon=${lng}`)
    const data = await res.json()
    address.value = data.display_name || 'Address not found'
    locationName.value = address.value // ⬅️ INI PENTING
  } catch {
    address.value = 'Failed to get address'
    locationName.value = 'Unknown location'
  }
}

// ===== GEOLOCATION =====
const getLocation = () => {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      locationStatus.value = 'Geolocation not supported'
      return resolve()
    }

    locationStatus.value = 'Detecting location...'

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        latitude.value = pos.coords.latitude
        longitude.value = pos.coords.longitude
        accuracy.value = pos.coords.accuracy
        locationStatus.value = 'Location detected'
        await getAddressFromLatLng(latitude.value, longitude.value)
        resolve()
      },
      () => {
        locationStatus.value = 'Failed to detect location'
        resolve()
      },
      {
        enableHighAccuracy: false,
        timeout: 20000,
        maximumAge: 0
      }
    )
  })
}

// ===== LIFECYCLE =====
onMounted(() => {
  updateDateTime()
  timer = setInterval(updateDateTime, 1000)

  const modalEl = document.getElementById('modal-presensi')
  if (modalEl) {
    modalEl.addEventListener('shown.bs.modal', () => {
      startCamera()
      //  TIDAK ADA getLocation DI SINI
    })

    modalEl.addEventListener('hidden.bs.modal', () => {
      stopCamera()
      photo.value = null
      latitude.value = null
      longitude.value = null
      address.value = ''
      locationStatus.value = 'Waiting photo...'
    })
  }
})
onUnmounted(() => clearInterval(timer))
// end code frontend camera and location



const form = reactive({
  notes: '',
  status: 'follow_up' // Default status
})

const statusOptions = [
  { value: 'Collection / Payment Follow-up', label: 'Collection / Payment Follow-up', desc: 'Collection / Payment Follow-up' },
  { value: 'Restocking / Taking Order (TO)', label: 'Restocking / Taking Order (TO)', desc: 'Restocking / Taking Order (TO)' },
  { value: 'Routine Maintenance / Courtesy Call', label: 'Routine Maintenance / Courtesy Call', desc: 'Routine Maintenance / Courtesy Call' },
  { value: 'Product Handling / Complaint', label: 'Product Handling / Complaint', desc: 'Product Handling / Complaint' },
  { value: 'Active & Productive', label: 'Active & Productive', desc: 'Active & Productive' },
  { value: 'Inactive / No Order', label: 'Inactive / No Order', desc: 'Inactive / No Order' },
  { value: 'At Risk / Complaint', label: 'At Risk / Complaint', desc: 'At Risk / Complaint' },
  { value: 'Churn / Closed', label: 'Churn / Closed', desc: 'Churn / Closed' }
]

// GANTI START CAMERA AGAR PAKAI KAMERA BELAKANG
const startCamera = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      // "environment" untuk kamera belakang, "user" untuk kamera depan
      video: { facingMode: "environment" }, 
      audio: false
    })
    if (videoRef.value) {
      videoRef.value.srcObject = stream
    }
  } catch (err) {
    console.error("Camera error:", err)
    locationStatus.value = 'Camera permission denied'
  }
}

// PERBAIKI LIFECYCLE MOUNTED
onMounted(() => {
  updateDateTime()
  timer = setInterval(updateDateTime, 1000)

  // Pastikan ID sesuai dengan ID di HTML modal
  const modalEl = document.getElementById('modal-input-visit') 
  if (modalEl) {
    modalEl.addEventListener('shown.bs.modal', () => {
      startCamera()
    })

    modalEl.addEventListener('hidden.bs.modal', () => {
      stopCamera()
      // Reset form saat modal tutup
      photo.value = null
      latitude.value = null
      longitude.value = null
      address.value = ''
      form.notes = ''
    })
  }
})

const submitVisit = () => {
  if (!photo.value) return alert('Ambil foto kunjungan terlebih dahulu!')
  
  console.log('Mengirim Data:', {
    photo: photo.value,
    location: { lat: latitude.value, lng: longitude.value },
    notes: form.notes,
    status: form.status
  })
  alert('Data Berhasil Disimpan!')
  // Tambahkan logika kirim ke API disini
}
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
                    >
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                    </select>
                </div>

                <!-- Tombol add -->
                 <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-add-data">
                  <i class="fa-solid fa-arrow-left"></i> Back
                    </button>
                </div>


              <!-- Kanan -->
             <div class="d-flex flex-column gap-3 align-items-end" style="min-width:300px;">
                <!-- Pencarian -->
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Searching....">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto">
                    <option value="fullname">By Name</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto">
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

            

                <tbody>
                    <tr>
                      <td>1</td>
                      <td>sdsdsdsd</td>
                      <td>dsdsdsdsd</td>
                      <td>dsdsdsdsd</td>
                      <td>dsdsdsdsd</td>
                    
                      <td>12313</td>
                      <td>
                       

                       <button class="btn btn-outline-primary btn-sm"   
                        data-bs-toggle="modal" data-bs-target="#modal-input-visit"
                          >
                            <i class="fa-solid fa-street-view"></i> Visit Now
                        </button>
                    
                         
                      </td>
                    </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Card: Pagination -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <button class="btn btn-danger btn-sm" 
                 >
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                    <span class="badge border text-secondary px-3 py-2"> 10 data | on page 19</span>
                    <span class="badge border text-secondary px-3 py-2">Total: 19  data</span>
                </div>
  
                <button class="btn btn-danger btn-sm"
               >
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
        <h5 class="modal-title">Customer Visit Report</h5>
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
                class="btn btn-primary w-100 mt-2 mb-2"
                :disabled="!isCameraReady"
                @click="takePhoto"
                >
                Get Photo Proof
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

                            <div
                                class="position-absolute bottom-0 start-0 w-100 p-2"
                                style="background: rgba(0,0,0,0.6); color:white; font-size:11px;"
                            >
                                <div>{{ currentDate }} - {{ currentTime }}</div>
                                <div>{{ address || 'Detecting location...' }}</div>
                            </div>
                            </template>

                           
                          <div
                            v-else
                            class="w-100 h-100 d-flex flex-column align-items-center
                                justify-content-center
                                text-center text-muted opacity-50"
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
        <button type="button" class="btn btn-success ms-auto" @click="submitVisit">
          <i class="fa-solid fa-cloud-arrow-up me-2"></i> Save Report Leads
        </button>
      </div>
    </div>
  </div>
</div>


  </backendLayouts>
</template>


<style scoped>


</style>