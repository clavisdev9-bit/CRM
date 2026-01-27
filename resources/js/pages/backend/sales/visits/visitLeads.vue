<script setup>
import { ref, reactive, onMounted , watch, onUnmounted, computed } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
const PagesTitle = 'Data Leads Ready To Visit';


// start code frontend camera and location
// ===== DATE TIME =====
const currentDate = ref('')
const currentTime = ref('')
let timer = null
// ===== PHOTO RESULT =====
const photoBlob = ref(null)

const isLocationLoading = ref(false)

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
    stream.getTracks().forEach(track => track.stop())
    stream = null
  }

  if (videoRef.value) {
    videoRef.value.srcObject = null // ⬅️ WAJIB
  }
}


const wrapText = (ctx, text, maxWidth) => {
  const words = text.split(' ')
  const lines = []
  let currentLine = ''

  words.forEach(word => {
    const testLine = currentLine + word + ' '
    const { width } = ctx.measureText(testLine)

    if (width > maxWidth && currentLine !== '') {
      lines.push(currentLine)
      currentLine = word + ' '
    } else {
      currentLine = testLine
    }
  })

  lines.push(currentLine)
  return lines
}


const isProcessingPhoto = ref(false)
const rawCanvas = ref(null) // simpan frame mentah


const takePhoto = async () => {
  if (!videoRef.value || isProcessingPhoto.value) return

  isProcessingPhoto.value = true
  updateDateTime()

  // ambil frame mentah
  const canvas = document.createElement('canvas')
  canvas.width = videoRef.value.videoWidth
  canvas.height = videoRef.value.videoHeight
  const ctx = canvas.getContext('2d')
  ctx.drawImage(videoRef.value, 0, 0)

  rawCanvas.value = canvas

  // PREVIEW CEPAT (tanpa watermark)
  photoBlob.value = await new Promise(resolve =>
    canvas.toBlob(resolve, 'image/jpeg', 0.8)
  )

  // lokasi jalan BELAKANG
  await getLocation()

  // setelah lokasi ready → FINALIZE
  await finalizePhotoWithWatermark()

  isProcessingPhoto.value = false
}


const finalizePhotoWithWatermark = async () => {
  if (!rawCanvas.value) return

  const canvas = rawCanvas.value
  const ctx = canvas.getContext('2d')

  const padding = 20
  const lineHeight = 24
  const maxTextWidth = canvas.width - padding * 2

  ctx.font = '14px Arial'

  const addressLines = wrapText(
    ctx,
    `📍 ${address.value}`,
    maxTextWidth
  )

  const timeLines = [`🕒 ${currentDate.value} ${currentTime.value}`]
  const allLines = [...addressLines, ...timeLines]

  const boxHeight = allLines.length * lineHeight + padding * 2
  const boxY = canvas.height - boxHeight

  ctx.fillStyle = 'rgba(0,0,0,0.6)'
  ctx.fillRect(0, boxY, canvas.width, boxHeight)

  ctx.fillStyle = '#fff'
  ctx.textBaseline = 'top'

  allLines.forEach((line, i) => {
    ctx.fillText(
      line,
      padding,
      boxY + padding + i * lineHeight
    )
  })

  // GANTI photoBlob JADI FINAL
  photoBlob.value = await new Promise(resolve =>
    canvas.toBlob(resolve, 'image/jpeg', 0.9)
  )
}




const photoPreview = computed(() => {
  return photoBlob.value
    ? URL.createObjectURL(photoBlob.value)
    : null
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

const resetVisitState = () => {
  // stop camera
  stopCamera()

  // revoke preview URL (anti memory leak)
  if (photoBlob.value) {
    URL.revokeObjectURL(photoPreview.value)
  }

  // clear photo
  photoBlob.value = null
  rawCanvas.value = null
  isProcessingPhoto.value = false
  isCameraReady.value = false

  // clear location
  latitude.value = null
  longitude.value = null
  accuracy.value = null
  address.value = ''
  locationName.value = ''
  locationStatus.value = 'Waiting photo...'

  // clear form
  form.notes = ''
  form.status = 'follow_up'
}



onMounted(() => {
  updateDateTime()
  timer = setInterval(updateDateTime, 1000)

  const modalEl = document.getElementById('modal-input-visit')
  if (!modalEl) return

  modalEl.addEventListener('shown.bs.modal', () => {
    startCamera()
  })

  modalEl.addEventListener('hidden.bs.modal', () => {
    resetVisitState()
  })
})

onUnmounted(() => {
  clearInterval(timer)
  resetVisitState()
})

onUnmounted(() => clearInterval(timer))
// end code frontend camera and location



const form = reactive({
  notes: '',
  status: 'follow_up' // Default status
})

const statusOptions = [
  { value: 'potential_customers', label: 'Potential Customers', desc: 'potential customers' },
  { value: 'consideration_stage', label: 'Consideration Stage', desc: 'consideration stage' },
  { value: 'prospective_customers', label: 'Prospective Customers', desc: 'Prospective Customers' },
  { value: 'failed', label: 'Failed', desc: 'Failed OR Rejected' },
  { value: 'customer', label: 'Covert To Customer', desc: 'Closing' }
]

// GANTI START CAMERA AGAR PAKAI KAMERA BELAKANG
const startCamera = async () => {
  if (stream) return // ⬅️ PENTING

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: "environment" },
      audio: false
    })

    if (videoRef.value) {
      videoRef.value.srcObject = stream
    }
  } catch (err) {
    console.error("Camera error:", err)
  }
}


document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    resetVisitState()
  }
})


const isSubmitDisabled = computed(() => {
  return (
    isProcessingPhoto.value ||
    !photoBlob.value ||
    !latitude.value ||
    !longitude.value
  )
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
        <h5 class="modal-title">Lead Visit Report</h5>
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
       
      <button
        type="button"
        class="btn btn-success ms-auto"
        :disabled="isSubmitDisabled"
        @click="submitVisit"
      >
        <i class="fa-solid fa-cloud-arrow-up me-2"></i>
        Save Report Lead
      </button>


      </div>
    </div>
  </div>
</div>


  </backendLayouts>
</template>


<style scoped>


</style>