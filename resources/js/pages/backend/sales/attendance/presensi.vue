<!-- <script setup>
import { ref, reactive, onMounted , watch, onUnmounted} from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
const PagesTitle = 'Attendance Presensi';


// variable for date and time card front
const currentDate = ref('')
const currentTime = ref('')
let timer = null


// variable for camera access
const videoRef = ref(null)
const photo = ref(null)
let stream = null


// ===== DYNAMIC LOCATION =====
const latitude = ref(null)
const longitude = ref(null)
const accuracy = ref(null)
const locationStatus = ref('Waiting location...')
const address = ref('')
const attendanceType = ref('in') // 'in' | 'out'




// start code for date and time card front
const updateDateTime = () => {
  const now = new Date()

  // Date: Monday, 30 January / 2026
  currentDate.value = now.toLocaleDateString('en-GB', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  }).replace(',', ' /')

  // Time: 08:17
  currentTime.value = now.toLocaleTimeString('en-GB', {
  hour: '2-digit',
  minute: '2-digit',
  second: '2-digit'
})
}


onUnmounted(() => {
  clearInterval(timer)
})



// start code camera access
const startCamera = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ 
      video: { facingMode: "user" }, 
      audio: false 
    })
    if (videoRef.value) {
      videoRef.value.srcObject = stream
    }
  } catch (err) {
    alert("Gagal mengakses kamera: " + err.message)
  }
}

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(t => t.stop())
    stream = null
  }
}


const takePhoto = async () => {
  if (!videoRef.value) return

  const canvas = document.createElement('canvas')
  canvas.width = videoRef.value.videoWidth
  canvas.height = videoRef.value.videoHeight
  const ctx = canvas.getContext('2d')

  ctx.drawImage(videoRef.value, 0, 0, canvas.width, canvas.height)
  photo.value = canvas.toDataURL('image/png')

  // ⬇️ BARU AMBIL LOKASI SETELAH FOTO
  if (!latitude.value) {
    getLocation()
  }
}



// const takePhoto = () => {
//   if (!videoRef.value) return
  
//   const canvas = document.createElement('canvas')
//   canvas.width = videoRef.value.videoWidth
//   canvas.height = videoRef.value.videoHeight
//   const ctx = canvas.getContext('2d')
  
//   // Membalikkan gambar jika kamera mirror (opsional)
//   ctx.drawImage(videoRef.value, 0, 0, canvas.width, canvas.height)
  
//   photo.value = canvas.toDataURL('image/png')
// }

// Tambahkan listener untuk modal agar kamera otomatis nyala/mati
// onMounted(() => {
//   updateDateTime()
//   timer = setInterval(updateDateTime, 1000)

//   const modalEl = document.getElementById('modal-presensi')
//   if (modalEl) {
//     modalEl.addEventListener('shown.bs.modal', startCamera)
//     modalEl.addEventListener('hidden.bs.modal', () => {
//       stopCamera()
//       photo.value = null // Reset foto saat modal ditutup
//     })
//   }
// })



// start code geo loca
const getAddressFromLatLng = async (lat, lng) => {
  try {
    address.value = 'Detecting address...'

    const res = await fetch(
      `/api/reverse-geocode?lat=${lat}&lon=${lng}`
    )

    const data = await res.json()
    address.value = data.display_name || 'Address not found'
  } catch (error) {
    console.error(error)
    address.value = 'Failed to get address'
  }
}



// const getLocation = () => {
//   if (!navigator.geolocation) {
//     locationStatus.value = 'Geolocation not supported'
//     return
//   }

//   locationStatus.value = 'Detecting location...'

//   navigator.geolocation.getCurrentPosition(
//     (pos) => {
//       latitude.value = pos.coords.latitude
//       longitude.value = pos.coords.longitude
//       accuracy.value = pos.coords.accuracy

//       locationStatus.value = 'Location detected'
//     },
//     (err) => {
//       console.error(err)

//       if (err.code === 3) {
//         locationStatus.value = 'Location timeout, please retry'
//       } else if (err.code === 1) {
//         locationStatus.value = 'Location permission denied'
//       } else {
//         locationStatus.value = 'Unable to detect location'
//       }
//     },
//     {
//       enableHighAccuracy: false,
//       timeout: 20000,           
//       maximumAge: 60000         
//     }
//   )
// }


const getLocation = () => {
  if (!navigator.geolocation) {
    locationStatus.value = 'Geolocation not supported'
    return
  }

  locationStatus.value = 'Detecting location...'

  navigator.geolocation.getCurrentPosition(
    async (pos) => {
      latitude.value = pos.coords.latitude
      longitude.value = pos.coords.longitude
      accuracy.value = pos.coords.accuracy

      locationStatus.value = 'Location detected'

      // ⬇ AMBIL NAMA LOKASI
      await getAddressFromLatLng(latitude.value, longitude.value)
    },
    (err) => {
      console.error(err)
      locationStatus.value = 'Failed to detect location'
    },
    {
      enableHighAccuracy: false,
      timeout: 20000,
      maximumAge: 60000
    }
  )
}




onMounted(() => {
  updateDateTime()
  timer = setInterval(updateDateTime, 1000)

  const modalEl = document.getElementById('modal-presensi')
  if (modalEl) {
    modalEl.addEventListener('shown.bs.modal', () => {
      startCamera()
      getLocation()
    })

    modalEl.addEventListener('hidden.bs.modal', () => {
      stopCamera()
      photo.value = null
      latitude.value = null
      longitude.value = null
      locationStatus.value = 'Waiting location...'
    })
  }
}) -->




<!-- 
</script> -->

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";

const PagesTitle = 'Attendance Presensi'

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
const accuracy = ref(null)
const locationStatus = ref('Waiting photo...')
const address = ref('')

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

// ===== CAMERA =====
const startCamera = async () => {
  stream = await navigator.mediaDevices.getUserMedia({
    video: { facingMode: "user" },
    audio: false
  })
  if (videoRef.value) {
    videoRef.value.srcObject = stream
  }
}

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(t => t.stop())
    stream = null
  }
}

// ===== TAKE PHOTO (TRIGGER LOCATION) =====
const takePhoto = async () => {
  if (!videoRef.value) return

  const canvas = document.createElement('canvas')
  canvas.width = videoRef.value.videoWidth
  canvas.height = videoRef.value.videoHeight
  const ctx = canvas.getContext('2d')

  ctx.drawImage(videoRef.value, 0, 0)
  photo.value = canvas.toDataURL('image/png')

  // ⬇️ BARU AMBIL LOKASI SETELAH FOTO
  await getLocation()
}

// ===== REVERSE GEOCODE =====
const getAddressFromLatLng = async (lat, lng) => {
  try {
    address.value = 'Detecting address...'
    const res = await fetch(`/api/reverse-geocode?lat=${lat}&lon=${lng}`)
    const data = await res.json()
    address.value = data.display_name || 'Address not found'
  } catch {
    address.value = 'Failed to get address'
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
      // ❌ TIDAK ADA getLocation DI SINI
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

         

          <div class="card mb-4 attendance-sneat">
          <div class="card-body text-center">

            <!-- Title -->
            <div class="text-start mb-3">
              <span class="fw-semibold text-muted">Form Attendance</span>
            </div>

            <!-- Date -->
            <div class="d-flex justify-content-center mb-3">
              <div class="attendance-date-sneat">
               {{ currentDate }}
                <i class="bx bx-calendar ms-2"></i>
              </div>
            </div>

            <!-- Time -->
            <div class="attendance-time-sneat mb-4">
             {{ currentTime }}
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-center gap-3 flex-wrap">
              
            

              <button
              class="btn btn-outline-primary"
              data-bs-toggle="modal"
              data-bs-target="#modal-presensi"
              @click="attendanceType = 'in'"
            >
              Presensi IN
            </button>

            <button
              class="btn btn-outline-success"
              data-bs-toggle="modal"
              data-bs-target="#modal-presensi"
              @click="attendanceType = 'out'"
            >
              Presensi OUT
            </button>
            </div>

          </div>
        </div>


      


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
                    <option value="fullname">By Date</option>
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
              <h4 class="card-title text-secondary fw-bolder">Your Data Attendance</h4>
            </div>
            <div class="table-responsive">
              <table class="table card-table table-vcenter text-nowrap">
                <thead>
                  <tr>
                    <th style="width: 5%;">No.</th>
                    <th>Date</th>
                    <th>Presensi In</th>
                    <th>Presensi Out</th>
                    <th>Status</th>
                    <th style="width: 8%;">Details</th>
                  </tr>
                </thead>

            

                <tbody>
                    <tr>
                      <td>1</td>
                      <td>Thursday  25-Januari-2026</td>
                      <td>08.10</td>
                      <td>17.09</td>
                      <td>Present</td>
                      <td>
                         <button class="btn btn-outline-primary btn-sm"  data-bs-toggle="modal"
                            data-bs-target="#userDetailModal"
                          >
                            <i class="fa fa-eye"></i> 
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




  <!-- Code Modal: Detail Data -->
<div class="modal modal-blur fade" id="userDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      
      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">Detail Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
         <div class="modal-body">                   
          <div class="d-flex justify-content-center align-items-center" style="min-height:150px;">
  <div class="spinner-border text-secondary" role="status">
    <span class="visually-hidden">Loading...</span>
  </div>
</div>

          <div >
            <p><strong>Role:</strong> </p>
            <p><strong>Description:</strong></p>
          </div>
      </div>
      </div>
      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
          Close
        </button>
      </div>
    </div>
  </div>
</div>




<div class="modal modal-blur fade" id="modal-presensi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-xl mt-4">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">Presensi Page</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <div class="row g-3 g-lg-4">

          <!-- LEFT -->
          <div class="col-12 col-lg-6">

            <!-- Notes -->
            <div class="mb-3">
              <label class="form-label">Notes</label>
              <div class="border rounded p-3 small text-warning">
                 Enable your camera and location permissions and take a selfie.
              </div>
            </div>

            <!-- Camera -->
            <div class="card">
              <div class="card-body text-center">
                <h6 class="fw-semibold mb-2">Camera</h6>

                <div class="camera-box mb-3">
                <video 
                  ref="videoRef" 
                  autoplay 
                  playsinline 
                  style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.5rem;"
                ></video>
                </div>

                <button class="btn btn-outline-primary btn-sm w-100 w-lg-auto" @click="takePhoto">
                  <i class="fa-solid fa-camera me-1"></i> Get Photo
                </button>
              </div>
            </div>

          </div>

          <!-- RIGHT -->
          <div class="col-12 col-lg-6">

            <!-- Result Photo -->
            <div class="card mb-3">
              <div class="card-body text-center">
                <h6 class="fw-semibold mb-2">Result Photo In Here</h6>
              <div class="photo-result border-0">
        <img 
          v-if="photo" 
          :src="photo" 
          class="img-fluid w-100 h-100 rounded img-thumbnail shadow-sm object-fit-cover"
          alt="Hasil Presensi"
        />
        <div v-else class="text-center text-muted opacity-50">
          <i class="fa-solid fa-image-portrait fs-1 mb-2"></i>
          <p class="small mb-0">No photo yet</p>
        </div>
      </div>
              </div>
            </div>

            <!-- Attendance Info -->
            <div class="card">
              <div class="card-body">

                <h6 class="fw-semibold mb-3 text-center">
                  Presensi In
                </h6>

                <div class="row align-items-center mb-2">
                  <label class="col-5 col-sm-4 col-form-label">Presence Time</label>
                  <div class="col-7 col-sm-8">
                    <div class="input-group input-group-sm">
                      <span class="badge bg-primary">{{ currentTime }}</span>
                      <span class="input-group-text">
                        <i class="fa-regular fa-clock"></i>
                      </span>
                    </div>
                  </div>
                </div>

               <div class="row align-items-center mb-2">
                  <label class="col-5 col-sm-4 col-form-label">Location Point</label>
                  <div class="col-7 col-sm-8">

                    <span
                      v-if="photo && latitude && longitude"
                      class="badge bg-primary-lt text-primary"
                    >
                      <i class="fa-solid fa-location-dot me-1"></i>
                      {{ latitude.toFixed(6) }}, {{ longitude.toFixed(6) }}
                    </span>

                    <span
                      v-else
                      class="badge bg-warning-lt text-warning"
                    >
                      <i class="fa-solid fa-spinner fa-spin me-1"></i>
                       Take photo to detect location...
                    </span>

                  </div>
                </div>




               

               <div class="row align-items-center mb-2">
                <label class="col-5 col-sm-4 col-form-label">Location Status</label>
                <div class="col-7 col-sm-8">

                  <!-- SETELAH FOTO -->
                  <span
                    v-if="photo && locationStatus"
                    class="badge"
                    :class="locationStatus === 'Location detected'
                      ? 'bg-success-lt text-success'
                      : 'bg-warning-lt text-warning'"
                  >
                    <i class="fa-solid fa-location-crosshairs me-1"></i>
                    {{ locationStatus }}
                  </span>

                  <!-- SEBELUM FOTO -->
                  <span
                    v-else
                    class="badge bg-warning-lt text-warning"
                  >
                    <i class="fa-solid fa-spinner fa-spin me-1"></i>
                    Take photo to detect location status
                  </span>

                </div>
              </div>



              <div class="row align-items-start mb-2">
                <label class="col-5 col-sm-4 col-form-label">Location Name</label>
                <div class="col-7 col-sm-8">

                  <!-- SETELAH FOTO & ALAMAT SIAP -->
                  <div
                    v-if="photo && address"
                    class="p-2 rounded border bg-light small text-muted"
                  >
                    <i class="fa-solid fa-location-dot me-1 text-primary"></i>
                    {{ address }}
                  </div>

                  <!-- SEBELUM FOTO -->
                  <span
                    v-else-if="!photo"
                    class="badge bg-warning-lt text-warning"
                  >
                   <i class="fa-solid fa-spinner fa-spin me-1"></i>
                    Take photo to detect location name
                  </span>

                  <!-- FOTO ADA, ALAMAT MASIH PROSES -->
                  <span
                    v-else
                    class="badge bg-warning-lt text-warning"
                  >
                    <i class="fa-solid fa-spinner fa-spin me-1"></i>
                    Detecting location name...
                  </span>

                </div>
              </div>





              <div class="row align-items-center mb-2">
                <label class="col-5 col-sm-4 col-form-label">Location Policy</label>
                <div class="col-7 col-sm-8">

                  <!-- BELUM FOTO -->
                  <span
                    v-if="!photo"
                    class="badge bg-warning-lt text-warning"
                  >
                    <i class="fa-solid fa-spinner fa-spin me-1"></i>
                    Take photo to validate location
                  </span>

                  <!-- FOTO ADA, LOKASI BELUM -->
                  <span
                    v-else-if="photo && (!latitude || !longitude)"
                    class="badge bg-warning-lt text-warning"
                  >
                    <i class="fa-solid fa-spinner fa-spin me-1"></i>
                    Waiting location
                  </span>

                  <!-- FOTO ADA, LOKASI ADA & AKURASI BAGUS -->
                  <span
                    v-else-if="accuracy !== null && accuracy <= 200"
                    class="badge bg-success-lt text-success"
                  >
                    <i class="fa-solid fa-location-dot me-1"></i>
                    Location recorded
                  </span>

                  <!-- FOTO ADA, LOKASI ADA TAPI AKURASI BURUK -->
                  <span
                    v-else
                    class="badge bg-danger-lt text-danger"
                  >
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Low GPS accuracy
                  </span>

                </div>
              </div>





                    <div class="row align-items-center mb-3">
                          <label class="col-5 col-sm-4 col-form-label">Type Attendance</label>
                          <div class="col-7 col-sm-8">

                            <!-- SUDAH DIPILIH -->
                            <span
                              v-if="attendanceType"
                              class="badge"
                              :class="attendanceType === 'in'
                                ? 'bg-success-lt text-primary'
                                : 'bg-danger-lt text-success'"
                            >
                              <i class="fa-solid fa-person-chalkboard me-1"></i>
                              Attendance {{ attendanceType === 'in' ? 'Check In' : 'Check Out' }}
                            </span>

                            <!-- BELUM DIPILIH -->
                            <span
                              v-else
                              class="badge bg-secondary-lt text-secondary"
                            >
                              <i class="fa-solid fa-hand-pointer me-1"></i>
                              Select attendance type
                            </span>

                          </div>
                  </div>




            <div class="mt-3">
              <span v-if="!photo || !latitude" class="badge bg-secondary-lt w-100 text-warning">
                <i class="fa-solid fa-circle-exclamation me-1"></i>
                Take selfie & allow location
              </span>

              <span v-else class="badge bg-success-lt w-100 text-success animate__animated animate__pulse">
                <i class="fa-solid fa-check-circle me-1"></i>
                Attendance ready to send
              </span>
            </div>


              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Footer -->
      <!-- Footer -->
      <div class="modal-footer">
        <button class="btn btn-link link-secondary" data-bs-dismiss="modal">
          Batal
        </button>
       <button
  class="btn btn-primary ms-auto"
  :disabled="!photo || !latitude"
>
  <i class="bx bx-check me-1"></i> Submit Presensi
</button>
      </div>

    </div>
  </div>
</div>




  </backendLayouts>
</template>


<style scoped>
.attendance-sneat {
  border: 1.5px solid #d9dee3;
  border-radius: 0.5rem;
}

.attendance-date-sneat {
  border: 1.5px solid #d9dee3;
  padding: 6px 14px;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #566a7f;
  display: inline-flex;
  align-items: center;
}

.attendance-time-sneat {
  font-size: 2.5rem;
  font-weight: 600;
  color: #566a7f;
  letter-spacing: 1px;
}



/* Gabungkan agar tingginya selalu sama */
/* Pastikan kedua kotak memiliki tinggi yang sama */
.camera-box,
.photo-result {
  height: 220px; /* Tinggi untuk HP */
  background: #f5f6f8;
  border: 1px dashed #d9dee3;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden; /* Supaya foto tidak keluar dari border kotak */
  position: relative;
}

/* Tinggi untuk Desktop agar lebih proporsional */
@media (min-width: 992px) {
  .camera-box,
  .photo-result {
    height: 280px; 
  }
}

/* Agar gambar tidak gepeng */
.object-fit-cover {
  object-fit: cover;
}






</style>
