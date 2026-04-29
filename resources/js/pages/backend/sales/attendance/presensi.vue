
<script setup>
import { ref, onMounted, onUnmounted, computed, watch  } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useDataAttendanceStore } from '../../../../stores/AttendanceFreeLocationStore';
import { useMenuStore } from "@/stores/menuStore";
import { toasts } from "@/utils/toasts"
import { useRoute, useRouter } from "vue-router";

import Swal from 'sweetalert2'
const PagesTitle = 'Attendance Presensi'

const dataAttendances = useDataAttendanceStore();
const fetchAttendanceDetail = dataAttendances.fetchAttendanceDetail
const menuStore = useMenuStore();
const route = useRoute();
const router = useRouter();

const permission = ref(null);
const loadingPermission = ref(true);
const detailType = ref('IN')


// start code frontend camera and location
// ===== DATE TIME =====
const currentDate = ref('')
const currentTime = ref('')
let timer = null
const attendanceType = ref(null) 

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


// wrap text untuk watermakr
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

const rawCanvas = ref(null)
const isProcessingPhoto = ref(false)



// const takePhoto = async () => {
//   if (!videoRef.value) return

//   const canvas = document.createElement('canvas')
//   canvas.width = videoRef.value.videoWidth
//   canvas.height = videoRef.value.videoHeight

//   const ctx = canvas.getContext('2d')
//   ctx.drawImage(videoRef.value, 0, 0, canvas.width, canvas.height)

//   // 🔥 SIMPAN FOTO RAW
//   rawCanvas.value = canvas

//   // 🔥 BARU AMBIL LOKASI (SESUAI RULE)
//   await getLocation()

//   // 🔥 FINALISASI FOTO
//   finalizePhoto()
// }

const takePhoto = async () => {
  if (!videoRef.value) return

  isProcessingPhoto.value = true

  // 1️⃣ Capture cepat
  const canvas = document.createElement('canvas')
  canvas.width = videoRef.value.videoWidth
  canvas.height = videoRef.value.videoHeight

  const ctx = canvas.getContext('2d')
  ctx.drawImage(videoRef.value, 0, 0, canvas.width, canvas.height)

  rawCanvas.value = canvas

  // 2️⃣ TAMPILKAN PREVIEW SEGERA (tanpa watermark dulu)
  const quickBlob = await new Promise(resolve =>
    canvas.toBlob(resolve, 'image/jpeg', 0.7)
  )

  photo.value = new File(
    [quickBlob],
    `attendance_preview.jpg`,
    { type: 'image/jpeg' }
  )

  // 3️⃣ JALANKAN LOKASI DI BELAKANG
  getLocation().then(() => {
    finalizePhoto() // replace preview dengan versi final
    isProcessingPhoto.value = false
  })
}



const finalizePhoto = async () => {
  const canvas = rawCanvas.value
  if (!canvas) return

  const ctx = canvas.getContext('2d')

  // =============================
  // WATERMARK
  // =============================
  const padding = 20
  const lineHeight = 26
  const maxTextWidth = canvas.width - padding * 2

  ctx.font = '16px Arial'

  const addressLines = wrapText(
    ctx,
    `📍 ${address.value}`,
    maxTextWidth
  )

  const timeLines = [
    `🕒 ${currentDate.value} ${currentTime.value}`
  ]

  const allLines = [...addressLines, ...timeLines]
  const boxHeight = allLines.length * lineHeight + padding * 2
  const boxY = canvas.height - boxHeight

  ctx.fillStyle = 'rgba(0, 0, 0, 0.55)'
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

  // =============================
  // CONVERT
  // =============================
  const blob = await new Promise(resolve =>
    canvas.toBlob(resolve, 'image/jpeg', 0.9)
  )

  photo.value = new File(
    [blob],
    `attendance_${Date.now()}.jpg`,
    { type: 'image/jpeg' }
  )
}




// const takePhoto = async () => {
//   if (!videoRef.value) return

//   const canvas = document.createElement('canvas')
//   canvas.width = videoRef.value.videoWidth
//   canvas.height = videoRef.value.videoHeight

//   const ctx = canvas.getContext('2d')

//   // gambar foto dari kamera
//   ctx.drawImage(videoRef.value, 0, 0, canvas.width, canvas.height)

// // =============================
// // WATERMARK (FIX POSITION)
// // =============================
// const padding = 20
// const lineHeight = 26
// const maxTextWidth = canvas.width - padding * 2

// ctx.font = '16px Arial'

// // wrap alamat
// const addressLines = wrapText(
//   ctx,
//   `📍 ${address.value || 'Detecting location...'}`,
//   maxTextWidth
// )

// // waktu
// const timeLines = [
//   `🕒 ${currentDate.value} ${currentTime.value}`
// ]

// // gabung semua baris
// const allLines = [...addressLines, ...timeLines]

// // hitung tinggi box
// const boxHeight = allLines.length * lineHeight + padding * 2

// // posisi box (DARI BAWAH)
// const boxY = canvas.height - boxHeight

// // background
// ctx.fillStyle = 'rgba(0, 0, 0, 0.55)'
// ctx.fillRect(
//   0,
//   boxY,
//   canvas.width,
//   boxHeight
// )

// // text
// ctx.fillStyle = '#ffffff'
// ctx.textBaseline = 'top'

// // 🔥 FIX DI SINI
// allLines.forEach((line, index) => {
//   ctx.fillText(
//     line,
//     padding,
//     boxY + padding + index * lineHeight
//   )
// })


  // =============================
  // CONVERT TO FILE
  // =============================
//   const blob = await new Promise(resolve =>
//     canvas.toBlob(resolve, 'image/jpeg', 0.9)
//   )

//   photo.value = new File(
//     [blob],
//     `attendance_${Date.now()}.jpg`,
//     { type: 'image/jpeg' }
//   )

//   await getLocation()
// }



  //const takePhoto = async () => {
        //if (!videoRef.value) return

        //const canvas = document.createElement('canvas')
        //canvas.width = videoRef.value.videoWidth
       //canvas.height = videoRef.value.videoHeight
        //const ctx = canvas.getContext('2d')
        //ctx.drawImage(videoRef.value, 0, 0)

        // const blob = await new Promise(resolve =>
        //   canvas.toBlob(resolve, 'image/jpeg', 0.9)
        // )

  //       photo.value = new File(
  //         [blob],
  //         `attendance_${Date.now()}.jpg`,
  //         { type: 'image/jpeg' }
  //       )

  //       await getLocation()
  // }

const photoPreview = computed(() => {
  return photo.value ? URL.createObjectURL(photo.value) : null
})


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


// start code untuk get(view) data attendance by user
onMounted(async () => {
  try {
    if (!localStorage.getItem("auth_token")) {
      alert("Silakan login terlebih dahulu!");
      router.push('/login');
      return;
    }
    await dataAttendances.fetchAttendanceToday();
    await dataAttendances.fetchAttendanceData(dataAttendances.buildUrl());
    await menuStore.fetchMenus();
 

    permission.value = menuStore.getPermission(route.path);
  } catch (error) {
    console.error(error);
  } finally {
    loadingPermission.value = false;
  }
});




// Fungsi Format Tanggal Indonesia
const formatDateID = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(date);
};



const groupedAttendance = computed(() => {
  const groups = {};
  const rawData = dataAttendances.attendanceData || [];
  const photoBaseUrl = 'http://localhost:8000/storage/attendance/photos/';

  rawData.forEach(item => {
  const dateKey = item.attendance_date

  if (!groups[dateKey]) {
    groups[dateKey] = {
      rawDate: dateKey,
      formattedDate: formatDateID(dateKey),
      in: null,
      out: null,
      attendance_status: item.attendance_status // AMBIL DARI BACKEND
    }
  }

  const dataObj = {
    id: item.id,
    time: item.attendance_time?.substring(0, 5) ?? '-',
    location: item.location_name,
    photo: item.photo_path ? photoBaseUrl + item.photo_path : null,
    status: item.attendance_status
  }

  if (item.attendance_type === 'IN') {
    groups[dateKey].in = dataObj
    groups[dateKey].attendance_status = item.attendance_status
  }

  if (item.attendance_type === 'OUT') {
    groups[dateKey].out = dataObj
  }
})


  return Object.values(groups).sort(
    (a, b) => new Date(b.rawDate) - new Date(a.rawDate)
  );
});


watch(
  () => dataAttendances.searchAttendance,
  dataAttendances.searchWithDelay
);
// end code untuk get(view) data attendance by user





// start code untuk submit data attendance by user

const attendanceTypeLabel = computed(() => {
  return attendanceType.value === 'IN' ? 'In' : 'Out'
})



// const isSubmitDisabled = computed(() => {
//   return (
//     saving.value ||          // sedang submit
//     !photo.value ||          // foto belum ada
//     !latitude.value ||       // lokasi belum ada
//     !longitude.value         // (kalau dipakai)
//   )
// })

const isSubmitDisabled = computed(() => {
  return (
    saving.value ||          // sedang submit
    isProcessingPhoto.value || // 🔥 foto belum final
    !photo.value ||          // foto belum ada
    !latitude.value ||       // lokasi belum ada
    !longitude.value
  )
})




// COMPUTED
const saving = computed(() => dataAttendances.savingAttendance)

// SUBMIT
const submitAttendance = async () => {
  if (saving.value) return

  if (!latitude.value || !longitude.value) {
    Swal.fire('Warning', 'Lokasi belum tersedia', 'warning')
    return
  }

  if (!photo.value) {
    Swal.fire('Warning', 'Foto wajib diambil', 'warning')
    return
  }

  try {
    await dataAttendances.storeAttendance({
      attendance_type: attendanceType.value,
      latitude: latitude.value,
      longitude: longitude.value,
      accuracy: accuracy.value,
      location_name: locationName.value,
      photo_path: photo.value,
      device_type: 'WEB'
    })

      //  TUTUP MODAL
    const modalEl = document.getElementById('modal-presensi')
    if (modalEl && window.bootstrap) {
      const modal = window.bootstrap.Modal.getInstance(modalEl)
        || new window.bootstrap.Modal(modalEl)
      modal.hide()
    }

    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: `attendance ${attendanceType.value} success`,
      showConfirmButton: false,
      timer: 1500
    })

    


    // reset
    photo.value = null

    //  WAJIB: refresh status attendance hari ini
  await dataAttendances.fetchAttendanceToday()

  } catch (err) {
    Swal.fire(
      'Failed',
      err.response?.data?.message || 'Attendance gagal',
      'error'
    )
  }
}
// end code untuk submit data attendance by user


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

          <div class="d-flex flex-column align-items-center gap-2">

 

        <div class="d-flex flex-column align-items-center gap-2">
          <!-- Tombol IN & OUT -->
          <div class="d-flex justify-content-center gap-3 flex-wrap">
            <button
              class="btn btn-outline-primary"
              data-bs-toggle="modal"
              data-bs-target="#modal-presensi"
              @click="attendanceType = 'IN'"
              :disabled="dataAttendances.hasAttendanceToday && dataAttendances.attendanceStatus !== 'OUT'"
            >
              <i class="fa-solid fa-camera-rotate"></i> Presensi IN
            </button>

            <button
              class="btn btn-outline-success"
              data-bs-toggle="modal"
              data-bs-target="#modal-presensi"
              @click="attendanceType = 'OUT'"
              :disabled="!dataAttendances.hasAttendanceToday || dataAttendances.attendanceStatus === 'COMPLETE'"
            >
            <i class="fa-solid fa-camera-rotate"></i>  Presensi OUT
            </button>
          </div>

          <!-- Tombol/Label "Sudah Absen" muncul hanya setelah check-in & check-out selesai -->
          <div v-if="dataAttendances.attendanceStatus === 'COMPLETE'" class="mt-2 text-center">
            <button class="btn btn-secondary" disabled>
              You are absent today
            </button>
          </div>
        </div>
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
                     v-model.number="dataAttendances.pagination.per_page" 
                     @change="dataAttendances.changePageSize"
                    >
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                    </select>
                </div>

                 <button 
                    class="btn btn-outline-warning btn-sm d-flex align-items-center justify-content-center gap-2"
                    @click="dataAttendances.resetFilters"
                  >
                    <i class="fas fa-undo"></i>
                    <span>Reset</span>
                  </button>



                </div>


              <!-- Kanan -->
             <div class="d-flex flex-column gap-3 align-items-end" style="min-width:300px;">
                <!-- Pencarian -->
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Searching by date/year/time"
                     v-model="dataAttendances.searchAttendance">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" v-model="dataAttendances.sort.column" 
                    @change="dataAttendances.changeSorting">
                       <option value="attendance_date">By Date time</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto" v-model="dataAttendances.sort.direction" 
                    @change="dataAttendances.changeSorting">
                    <option value="asc">Ascending</option>
                    <option value="desc">Descending</option>
                    </select>
                </div>
                </div>
            </div>
          </div>

          <!-- Card: Table -->
          <div class="card mb-4 shadow-sm">
  <div class="card-header bg-white py-3">
    <h6 class="card-title text-secondary fw-bolder mb-0">Your Data Attendance</h6>
  </div>
  
  <div class="table-responsive">
  <table class="table card-table table-vcenter text-nowrap">
    <thead>
      <tr>
        <th style="width: 5%;" class="text-center">No.</th>
        <th>Date</th>
        <th>Presensi In</th>
        <th>Presensi Out</th>
        <th class="text-center">Status</th>
        <th style="width: 5%;" class="text-center">Details</th>
      </tr>
    </thead>

    <tbody>

      <!-- LOADING -->
      <tr v-if="dataAttendances.loadingAttendance">
        <td colspan="6" class="text-center py-4">
          <div class="spinner-border text-primary"></div>
        </td>
      </tr>

      <!-- EMPTY -->
      <tr
        v-else-if="!dataAttendances.loadingAttendance && groupedAttendance.length === 0"
      >
        <td colspan="6" class="text-center py-5">
          <div class="d-flex flex-column align-items-center">
            <img
              src="https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif"
              style="max-width: 250px"
              class="mb-3"
            />
            <p class="text-danger fw-bold fst-italic">
              <i class="fa fa-exclamation-circle me-1"></i>
              Attendance data not found.
            </p>
          </div>
        </td>
      </tr>

      <!-- DATA -->
      <tr
        v-else
        v-for="(group, index) in groupedAttendance"
        :key="group.rawDate"
      >
        <!-- NO -->
        <td class="text-center text-muted">{{ index + 1 }}.</td>

        <!-- DATE -->
        <td>
          <div class="fw-bold">{{ group.formattedDate }}</div>
          <small class="text-muted">{{ group.rawDate }}</small>
        </td>

        <!-- IN -->
        <td>
          <div v-if="group.in" class="d-flex align-items-center">
            <img
              v-if="group.in.photo"
              :src="group.in.photo"
              class="rounded me-3"
              style="width:45px;height:45px;object-fit:cover"
            />

          <div>
              <div class="fw-bolder text-primary fs-4">
                {{ group.in.time }}
           <span 
            v-if="group.attendance_status === 'LATE'"
            class="badge rounded-pill bg-warning-lt text-warning"
            style="font-size:11px; padding:2px 6px;"
            >
            <i class="fa-regular fa-alarm-clock fa-sm text-warning"></i>
            Late
          </span>


              </div>
              <div class="small text-muted text-truncate" style="max-width:150px">
                <i class="fa fa-map-marker-alt text-danger me-1"></i>
                {{ group.in.location }}
              </div>

              <a
                v-if="group.in.photo"
                :href="group.in.photo"
                target="_blank"
                class="badge bg-blue-lt text-primary mt-1"
              >
                <i class="fa fa-eye me-1"></i> see photo
              </a>
            </div>
          </div>

          <div v-else class="text-muted fst-italic">--:--</div>
        </td>

        <!-- OUT -->
        <td>
          <div v-if="group.out" class="d-flex align-items-center">
            <img
              v-if="group.out.photo"
              :src="group.out.photo"
              class="rounded me-3"
              style="width:45px;height:45px;object-fit:cover"
            />

            <div>
              <div class="fw-bolder text-success fs-4">
                {{ group.out.time }}
              </div>
              <div class="small text-muted text-truncate" style="max-width:150px">
                <i class="fa fa-map-marker-alt text-danger me-1"></i>
                {{ group.out.location }}
              </div>

              <a
                v-if="group.out.photo"
                :href="group.out.photo"
                target="_blank"
                class="badge bg-blue-lt text-success mt-1"
              >
                <i class="fa fa-eye me-1"></i> see photo
              </a>
            </div>
          </div>

          <div v-else class="text-muted fst-italic">--:--</div>
        </td>

        <!-- STATUS -->
          <td class="text-center">
            <!-- COMPLETED -->
            <span
              v-if="group.attendance_status === 'COMPLETED'"
              class="badge bg-success-lt text-success"
            >
              <i class="fa fa-check-circle me-1"></i> COMPLETED
            </span>

            <!-- LATE -->
            <span
              v-else-if="group.attendance_status === 'LATE'"
              class="badge bg-warning-lt text-warning"
            >
              <i class="fa-regular fa-alarm-clock"></i> LATE
            </span>

            <!-- READY -->
            <span
              v-else-if="group.attendance_status === 'READY'"
              class="badge bg-info-lt text-info"
            >
              <i class="fa fa-hourglass-half me-1"></i> READY
            </span>

            <!-- REJECTED -->
            <span
              v-else-if="group.attendance_status === 'REJECTED'"
              class="badge bg-danger-lt text-danger"
            >
              <i class="fa fa-times-circle me-1"></i> REJECTED
            </span>

            <!-- DRAFT / fallback -->
            <span v-else class="badge bg-secondary-lt text-dark">
              {{ group.attendance_status }}
            </span>
          </td>


        <!-- DETAIL -->
        <td class="text-center">
          <!-- IN -->
          <button
            class="btn btn-outline-primary btn-sm btn-icon position-relative me-3"
            data-bs-toggle="modal"
            data-bs-target="#attendanceDetailModal"
            @click="detailType='IN'; fetchAttendanceDetail(group.in.id)"
          >
            <i class="fa fa-eye"></i>
            <span class="badge bg-primary position-absolute top-0 start-100 translate-middle">
              IN
            </span>
          </button>

          <!-- OUT (hidden kalau belum ada OUT) -->
          <button
            v-if="group.out"
            class="btn btn-outline-success btn-sm btn-icon position-relative"
            data-bs-toggle="modal"
            data-bs-target="#attendanceDetailModal"
            @click="detailType='OUT'; fetchAttendanceDetail(group.out.id)"
          >
            <i class="fa fa-eye"></i>
            <span class="badge bg-success position-absolute top-0 start-100 translate-middle">
              OUT
            </span>
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
                 :disabled="!dataAttendances.pagination.prev_page_url || dataAttendances.loadingMenus"
                  @click="dataAttendances.fetchAttendanceData(dataAttendances.pagination.prev_page_url)">
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                   <span class="badge border text-secondary px-3 py-2">
                      {{ groupedAttendance.length }} data | on page {{ dataAttendances.pagination.current_page }}
                   </span>

                   <span class="badge border text-secondary px-3 py-2">
                    Total: {{ groupedAttendance.length }} Data
                   </span>

                </div>
  
                <button class="btn btn-danger btn-sm"
                 :disabled="!dataAttendances.pagination.next_page_url || dataAttendances.loadingAttendance"
                  @click="dataAttendances.fetchAttendanceData(dataAttendances.pagination.next_page_url)"
               >
                    Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>

        </div>
      </div>

     
    </div>



<!-- MODAL DETAIL ATTENDANCE -->
<div class="modal modal-blur fade" id="attendanceDetailModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">

      <!-- HEADER -->
      <div class="modal-header">
        <h5 class="modal-title text-decoration-underline">
          Details Attendance
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- BODY -->
      <div class="modal-body">

        <!-- LOADING -->
        <div
          v-if="dataAttendances.loadingDetail"
          class="d-flex justify-content-center align-items-center"
          style="min-height: 200px"
        >
          <div class="spinner-border text-primary"></div>
        </div>

        <!-- DATA AVAILABLE -->
        <div
          v-else-if="dataAttendances.attendanceDetail"
          class="row g-4"
        >

          <!-- USER INFO -->
          <div class="col-12">
            <div class="card bg-light">
              <div class="card-body">
                <h5 class="text-decoration-underline mb-3">
                  <i class="fa fa-user me-1"></i> Employee Information
                </h5>

                <div class="row">
                  <div class="col-md-4">
                    <strong>Name Employe</strong>
                    <p class="text-muted mb-0">
                      {{ dataAttendances.attendanceDetail.user?.fullname }}
                    </p>
                  </div>

                  <div class="col-md-4">
                    <strong>Email</strong>
                    <p class="text-muted mb-0">
                      {{ dataAttendances.attendanceDetail.user?.email }}
                    </p>
                  </div>

                  <div class="col-md-4">
                    <strong>Employee Identity number</strong>
                    <p class="text-muted mb-0">
                      {{ dataAttendances.attendanceDetail.employee?.nik }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ATTENDANCE DETAIL -->
          <div class="col-md-12">
            <div
              class="card h-100"
              :class="detailType === 'IN' ? 'border-primary' : 'border-success'"
            >
              <div class="card-body">

                <!-- DYNAMIC TITLE -->
                <h5
                  class="text-decoration-underline mb-3"
                  :class="detailType === 'IN' ? 'text-primary' : 'text-success'"
                >
                  Attendance {{ detailType }}
                </h5>

                <!-- BASIC INFO -->
                <p class="mb-1">
                  <strong>date of absence:</strong>
                  {{ dataAttendances.attendanceDetail.attendance_date }}
                </p>

                <p class="mb-2">
                  <strong>Time of absence:</strong>
                  {{ dataAttendances.attendanceDetail.attendance_time }}
                </p>

                <span
                  class="badge mb-3"
                  :class="detailType === 'IN' ? 'bg-primary' : 'bg-success'"
                >
                  {{ dataAttendances.attendanceDetail.attendance_status }}
                </span>

                <hr>

                <!-- DEVICE INFO -->
                <p class="mb-1">
                  <strong>Mode Location of absence:</strong>
                  {{ dataAttendances.attendanceDetail.attendance_mode }}
                </p>

                <p class="mb-2">
                  <strong>Device Detect:</strong>
                  {{ dataAttendances.attendanceDetail.device_type }}
                </p>

                <hr>

                <!-- LOCATION -->
                <strong>Location</strong>
                <p class="mb-1">
                  {{ dataAttendances.attendanceDetail.location_name }}
                </p>

                <p class="small text-muted mb-2">
                  Lat: {{ dataAttendances.attendanceDetail.latitude }} <br>
                  Long: {{ dataAttendances.attendanceDetail.longitude }}
                </p>

                <p class="mb-0">
                  Accuracy:
                  {{ dataAttendances.attendanceDetail.accuracy }} m
                  <span class="badge bg-success-lt ms-1 text-secondary">
                  |  {{ dataAttendances.attendanceDetail.accuracy_status }}
                  </span>
                </p>

              </div>
            </div>
          </div>

        </div>

        <!-- EMPTY STATE -->
        <div v-else class="text-center text-muted py-5">
         Data not available
        </div>

      </div>

      <!-- FOOTER -->
      <div class="modal-footer">
        <button
          class="btn btn-secondary btn-link link-secondary"
          data-bs-dismiss="modal"
        >
          Close
        </button>
      </div>

    </div>
  </div>
</div>




<!-- modal presensi -->
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
                    v-if="photoPreview"
                    :src="photoPreview"
                      class="w-110 h-100 rounded img-thumbnail shadow-sm"
                                style="object-fit: contain;" 
                    alt="Hasil Presensi"
                  />
                  <div v-else class="text-center text-muted opacity-50">
                    <i class="fa-solid fa-image-portrait fs-1 mb-2"></i>
                    <!-- <p class="small mb-0">No photo yet</p> -->
                     <div v-if="isProcessingPhoto" class="processing-overlay">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Processing location...
                      </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Attendance Info -->
            <div class="card">
              <div class="card-body">
              <h6 class="fw-semibold mb-3 text-center">
                <i
                  class="fa me-1"
                  :class="attendanceType === 'IN' ? 'fa-sign-in-alt text-success' : 'fa-sign-out-alt text-danger'"
                ></i>
                Presensi {{ attendanceTypeLabel }}
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
                              :class="attendanceType === 'IN'
                                ? 'bg-success-lt text-primary'
                                : 'bg-danger-lt text-success'"
                            >
                              <i class="fa-solid fa-person-chalkboard me-1"></i>
                              Attendance {{ attendanceType === 'IN' ? 'Check In' : 'Check Out' }}
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
      <div class="modal-footer">
         
  
        <button class="btn btn-secondary btn-link link-secondary" data-bs-dismiss="modal">
         <i class="fa-solid fa-arrow-left"></i> Cancel
        </button>
      
          <button
          class="btn btn-primary ms-auto"
          :disabled="isSubmitDisabled"
          @click="submitAttendance"
          >
          <i class="fa-solid fa-paper-plane me-1"></i>
          {{ saving ? 'Processing...' : 'Submit Attendance' }}
        </button>

      </div>  
      </div>
    </div>
  </div>




  </backendLayouts>
</template>


<style scoped>
.processing-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 14px;
}


.photo-result-lg {
  height: 360px;   /* 👉 ubah sesuai selera: 320 / 400 */
}


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
