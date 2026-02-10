
<script setup>
import { ref, reactive, onMounted , watch, computed } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useDataLeadsVisitStore } from '../../../../stores/leadsVisitsStore';
import { useMenuStore } from "@/stores/menuStore";
import { toasts } from "@/utils/toasts";
import { useRoute, useRouter } from "vue-router";
import Swal from 'sweetalert2';


const PagesTitle = 'Data Leads Ready To Visit';

// ==============================
// Stores
// ==============================
const dataLeadsVisit = useDataLeadsVisitStore();

const menuStore = useMenuStore();

// ==============================
// Router
// ==============================
const route = useRoute();
const router = useRouter();
const selectedLead = ref(null)

// ==============================
// Permission
// ==============================
const permission = ref(null);
const loadingPermission = ref(true);


// start code helper untuk data
const leadStatusBadge = (status) => {
  switch (status) {
    case 'New':
      return 'bg-success';
    case 'Contacted':
      return 'bg-info';
    case 'Qualified':
        return 'bg-primary';
  }
};

const formatLeadStatus = (status) => {
  return status
    .replace(/_/g, ' ')
    .replace(/\b\w/g, char => char.toUpperCase());
};
// end code helper untuk data


// start code for get data
onMounted(async () => {
  try {
    if (!localStorage.getItem("auth_token")) {
      alert("Silakan login terlebih dahulu!");
      router.push('/login');
      return;
    }

    await dataLeadsVisit.fetchLeadsVisitStore(dataLeadsVisit.buildUrl());
    await menuStore.fetchMenus();

    permission.value = menuStore.getPermission(route.path);
  } catch (error) {
    console.error(error);
  } finally {
    loadingPermission.value = false;
  }
});
// end code for get data



// start code for check In
const selectedLeadscheckIn = ref(null)
const openVisitModalCheckIn = (leads) => {
  selectedLeadscheckIn.value = leads
  startCamera();

  console.log('this is modal for check in');
  
}
// ==============================
// Camera & Location State
// ==============================
const videoRef = ref(null);
const photoBlob = ref(null);
const rawCanvas = ref(null);
const isCameraReady = ref(false);
const isProcessingPhoto = ref(false);

const latitude = ref(null);
const longitude = ref(null);
const accuracy = ref(15);
const locationStatus = ref('Waiting photo...');
const address = ref('');
const locationName = ref('');

const currentDate = ref('');
const currentTime = ref('');
let timer = null;
const errors = ref({})

// ==============================
// Date Time
// ==============================
const updateDateTime = () => {
  const now = new Date();
  currentDate.value = now.toLocaleDateString('en-GB', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  }).replace(',', ' /');

  currentTime.value = now.toLocaleTimeString('en-GB', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
};

// ==============================
// Camera
// ==============================
let stream = null;
const startCamera = async () => {
  if (stream) return;
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
    if (videoRef.value) videoRef.value.srcObject = stream;
  } catch (err) {
    console.error("Camera error:", err);
  }
};

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
    stream = null;
  }
  if (videoRef.value) videoRef.value.srcObject = null;
};

// ==============================
// Location
// ==============================
const getAddressFromLatLng = async (lat, lng) => {
  try {
    address.value = 'Detecting address...';
    const res = await fetch(`/api/reverse-geocode?lat=${lat}&lon=${lng}`);
    const data = await res.json();
    address.value = data.display_name || 'Address not found';
    locationName.value = address.value;
  } catch {
    address.value = 'Failed to get address';
    locationName.value = 'Unknown location';
  }
};

const getLocation = () => {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      locationStatus.value = 'Geolocation not supported';
      return resolve();
    }

    locationStatus.value = 'Detecting location...';

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        latitude.value = pos.coords.latitude;
        longitude.value = pos.coords.longitude;
        accuracy.value = pos.coords.accuracy;
        locationStatus.value = 'Location detected';
        await getAddressFromLatLng(latitude.value, longitude.value);
        resolve();
      },
      () => {
        locationStatus.value = 'Failed to detect location';
        resolve();
      },
      { enableHighAccuracy: false, timeout: 20000, maximumAge: 0 }
    );
  });
};

// ==============================
// Photo & Watermark
// ==============================
const wrapText = (ctx, text, maxWidth) => {
  const words = text.split(' ');
  const lines = [];
  let currentLine = '';
  words.forEach(word => {
    const testLine = currentLine + word + ' ';
    if (ctx.measureText(testLine).width > maxWidth && currentLine !== '') {
      lines.push(currentLine);
      currentLine = word + ' ';
    } else {
      currentLine = testLine;
    }
  });
  lines.push(currentLine);
  return lines;
};

const takePhoto = async () => {
  if (!videoRef.value || isProcessingPhoto.value) return;

  isProcessingPhoto.value = true;
  updateDateTime();

  // Ambil frame mentah
  const canvas = document.createElement('canvas');
  canvas.width = videoRef.value.videoWidth;
  canvas.height = videoRef.value.videoHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(videoRef.value, 0, 0);
  rawCanvas.value = canvas;

  // Preview cepat
  photoBlob.value = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.8));

  // Ambil lokasi
  await getLocation();

  // Tambahkan watermark
  await finalizePhotoWithWatermark();

  isProcessingPhoto.value = false;
};

const finalizePhotoWithWatermark = async () => {
  if (!rawCanvas.value) return;

  const canvas = rawCanvas.value;
  const ctx = canvas.getContext('2d');

  const padding = 20;
  const lineHeight = 24;
  const maxTextWidth = canvas.width - padding * 2;

  ctx.font = '14px Arial';
  const addressLines = wrapText(ctx, `📍 ${address.value}`, maxTextWidth);
  const timeLines = [`🕒 ${currentDate.value} ${currentTime.value}`];
  const allLines = [...addressLines, ...timeLines];

  const boxHeight = allLines.length * lineHeight + padding * 2;
  const boxY = canvas.height - boxHeight;

  ctx.fillStyle = 'rgba(0,0,0,0.6)';
  ctx.fillRect(0, boxY, canvas.width, boxHeight);

  ctx.fillStyle = '#fff';
  ctx.textBaseline = 'top';
  allLines.forEach((line, i) => {
    ctx.fillText(line, padding, boxY + padding + i * lineHeight);
  });

  photoBlob.value = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.9));
};

const photoPreview = computed(() => {
  return photoBlob.value ? URL.createObjectURL(photoBlob.value) : null;
});

// ==============================
// Reset State
// ==============================
const resetVisitState = () => {
  stopCamera();

  if (photoBlob.value) URL.revokeObjectURL(photoPreview.value);

  photoBlob.value = null;
  rawCanvas.value = null;
  isProcessingPhoto.value = false;
  isCameraReady.value = false;

  latitude.value = null;
  longitude.value = null;
  accuracy.value = 15;
  address.value = '';
  locationName.value = '';
  locationStatus.value = 'Waiting photo...';

};

const canSubmitCheckIn = computed(() => {
  return (
    photoBlob.value &&
    latitude.value !== null &&
    longitude.value !== null &&
    !isProcessingPhoto.value
  )
})

const cancelCheckIn = () => {
  if (dataLeadsVisit.checkingInVisit) return // safety

  resetVisitState()

  const modalEl = document.getElementById('modal-input-check-in')
  const instance = bootstrap.Modal.getOrCreateInstance(modalEl)
  instance.hide()
}


const submitCheckIn = async () => {
  if (!selectedLeadscheckIn.value) return

  try {
    await dataLeadsVisit.checkInVisit({
      visitId: selectedLeadscheckIn.value.active_visit_id,
      latitude: latitude.value,
      longitude: longitude.value,
      gps_snapshot: address.value,
      photoBlob: photoBlob.value
    })

    resetVisitState()

    // ✅ CLOSE MODAL (STYLE KAMU)
    const modal = document.getElementById("modal-input-check-in")
    const instance =
      bootstrap.Modal.getInstance(modal) ||
      new bootstrap.Modal(modal)

    instance.hide()

    // ✅ ALERT SETELAH MODAL BENAR-BENAR TUTUP
    modal.addEventListener(
      "hidden.bs.modal",
      () => {
        Swal.fire({
          icon: "success",
          title: "Check In Successful",
          text: "Location & photo saved successfully",
          timer: 1500,
          showConfirmButton: false
        })
      },
      { once: true }
    )
  } catch (err) {
    Swal.fire(
      "Check In Failed",
      err.response?.data?.message ?? "There is an error",
      "error"
    )
  }
}
// end code for check In




// ==============================
// CHECK OUT STATE
// ==============================
const selectedLeadscheckOut = ref(null)

const form = reactive({
  notes: '',
  status: '' // customer_response
})

const statusOptions = [
  { value: 'potential_customers', label: 'Potential Customers' },
  { value: 'consideration_stage', label: 'Consideration Stage' },
  { value: 'prospective_customers', label: 'Prospective Customers' },
  { value: 'failed', label: 'Failed' },
  { value: 'convert_to_customer', label: 'Convert To Customer' }
]

// ==============================
// OPEN MODAL
// ==============================
const openVisitModalCheckOut = (leads) => {
  selectedLeadscheckOut.value = leads
  form.notes = ''
  form.status = ''
  errors.value = {}

  const modalEl = document.getElementById('modal-input-check-out')
  const instance =
    bootstrap.Modal.getInstance(modalEl) ||
    new bootstrap.Modal(modalEl)

  instance.show()
}




// ==============================
// SUBMIT CHECK OUT
// ==============================

const submitCheckOut = async () => {
  if (!selectedLeadscheckOut.value) return

  try {
    errors.value = {} //  reset error dulu

    await dataLeadsVisit.checkOutVisit({
      visitId: selectedLeadscheckOut.value.active_visit_id,
      notes: form.notes,
      customer_response: form.status
    })

    // tutup modal
    const modalEl = document.getElementById('modal-input-check-out')
    const instance = bootstrap.Modal.getInstance(modalEl)
    instance.hide()

    Swal.fire({
      icon: 'success',
      title: 'Check Out Successful',
      text: 'Visit completed successfully',
      timer: 1500,
      showConfirmButton: false
    })

  } catch (err) {
    //  AMBIL ERROR VALIDASI
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors
      return
    }

    //  ERROR LAIN (SERVER / LOGIC)
    toasts.fire({
      icon: "error",
      title: err.response?.data?.message || "Failed to save data",
    })
  }
}


const confirmStartVisit = (lead) => {
  Swal.fire({
    title: 'Start Visiting?',
    text: 'Make sure you are really going to start this visit.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, start',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#0d6efd',
    cancelButtonColor: '#6c757d',
    reverseButtons: true
  }).then(async (result) => {
    if (!result.isConfirmed) return

    try {
      await dataLeadsVisit.startVisit(lead.id)
    } catch (err) {
      Swal.fire(
        'Failed',
        err.response?.data?.message ?? 'Cannot start visit',
        'error'
      )
    }
  })
}


// ==============================
// Watchers
// ==============================
watch(() => dataLeadsVisit.searchLeadVisit, dataLeadsVisit.searchWithDelay);
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
                    v-model.number="dataLeadsVisit.pagination.per_page"
                    @change="dataLeadsVisit.changePageSize(dataLeadsVisit.pagination.per_page)"
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
                    <input type="text" class="form-control" placeholder="Searching...." v-model="dataLeadsVisit.searchLeadVisit">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto"  v-model="dataLeadsVisit.sort.column" @change="dataLeadsVisit.changeSorting">
                    <option value="company_name">By Company Name</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto" v-model="dataLeadsVisit.sort.direction" @change="dataLeadsVisit.changeSorting">
                    <option value="asc">Ascending</option>
                    <option value="desc">Descending</option>
                    </select>
                </div>
                </div>
            </div>
          </div>

          <!-- Card: Table -->
          <div class="card mb-4">
           
            <div class="table-responsive">
              <table class="table card-table table-vcenter text-nowrap">
                <thead>
                  <tr>
                    <th style="width: 5%;">No.</th>
                    <th>Company Name</th>
                    <th>Contact Name</th>
                    <th>Address</th>
                    <th>Telephone</th>
                    <th>Status</th>
                    <th style="width: 8%;">Actions</th>
                  </tr>
                </thead>


                <tbody v-if="dataLeadsVisit.loadingLeadVisit">
                  <tr>
                    <td colspan="7" class="text-center py-4">
                      <div class="spinner-border text-primary"></div>
                    </td>
                  </tr>
                </tbody>


                  <tbody v-else-if="dataLeadsVisit.leadVisitData.length === 0">
                   <tr>
                      <td colspan="9" class="text-center">
                        <div class="d-flex flex-column align-items-center justify-content-center">
                          <img
                            src="https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif"
                            alt="No data found"
                            style="max-width: 250px; height: auto;"
                            class="mb-3"
                          />
                          <p class="text-danger fw-bold fst-italic">
                            <i class="fa fa-exclamation-circle me-1"></i>
                             data not found.
                          </p>
                        </div>
                      </td>
                    </tr>
                  </tbody>



                  <tbody v-else>
                  <tr v-for="(lvd, index) in dataLeadsVisit.leadVisitData" :key="lvd.id">
                    <td>{{ index + 1 + dataLeadsVisit.pagination.per_page * (dataLeadsVisit.pagination.current_page - 1) }}.</td>
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
                      <span :class="['badge', leadStatusBadge(lvd.lead_status)]">
                        {{ formatLeadStatus(lvd.lead_status) }}
                      </span>
                  </td>


                    <td>
                    <!-- VISIT NOW -->
                    <button
                      v-if="!lvd.active_visit_id"
                      class="btn btn-primary btn-sm me-1"
                      @click="confirmStartVisit(lvd)"
                    >
                      <i class="fa-solid fa-street-view"></i> Visit Now
                    </button>

                    <!-- ONGOING (BELUM CHECK IN) -->
                    <button
                      v-else-if="lvd.visit_status === 'ONGOING'"
                      class="btn btn-secondary btn-sm me-1"
                      disabled
                    >
                      <i class="fa-solid fa-car-on"></i> Visit Ongoing
                    </button>

                    <!-- SEDANG CHECK IN -->
                    <button
                      v-else-if="lvd.visit_status === 'CHECKED_IN'"
                      class="btn btn-warning btn-sm me-1"
                      disabled
                    >
                      <i class="fa-solid fa-location-dot"></i> Currently Checking In
                    </button>

                    <!-- CHECK IN -->
                    <button
                      class="btn btn-sm btn-primary me-1"
                      :disabled="lvd.visit_status !== 'ONGOING'"
                      data-bs-toggle="modal"
                      data-bs-target="#modal-input-check-in"
                      @click="openVisitModalCheckIn(lvd)"
                    >
                      <i class="fa-solid fa-building-circle-arrow-right"></i> Check In
                    </button>

                    <!-- CHECK OUT -->
                    <button
                      class="btn btn-sm btn-success"
                      :disabled="lvd.visit_status !== 'CHECKED_IN'"
                      data-bs-toggle="modal"
                      data-bs-target="#modal-input-check-out"
                      @click="openVisitModalCheckOut(lvd)"
                    >
                      <i class="fa-solid fa-building-circle-check"></i> Check Out
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
              :disabled="!dataLeadsVisit.pagination.prev_page_url || dataLeadsVisit.loadingLeadVisit"
                @click="dataLeadsVisit.goToPage(dataLeadsVisit.pagination.prev_page_url)"
                 >
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                    <span class="badge border text-secondary px-3 py-2">
                      {{ dataLeadsVisit.leadVisitData.length }} data | page {{ dataLeadsVisit.pagination.current_page }}
                    </span>
                    <span class="badge border text-secondary px-3 py-2">Total: {{ dataLeadsVisit.pagination.total }}</span>
                </div>
  
                <button class="btn btn-danger btn-sm"
                :disabled="!dataLeadsVisit.pagination.next_page_url || dataLeadsVisit.loadingLeadVisit"
                @click="dataLeadsVisit.goToPage(dataLeadsVisit.pagination.next_page_url)"
               >
                    Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>
        </div>
      </div>
    </div>


    
    <!-- Modal: Visit Input check  IN -->
    <div class="modal modal-blur fade" id="modal-input-check-in" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
            Check In Lead
            </h5>
           <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="resetVisitState"></button>
          </div>
          <hr>
          <div class="modal-body">
            <div class="row g-3">
              <!-- Left: Camera -->
              <div class="col-12 col-lg-6">
                <div class="card bg-light text-white overflow-hidden" style="height:250px;">
                  <video ref="videoRef" autoplay playsinline @loadedmetadata="isCameraReady = true" class="w-100 h-100" style="object-fit:cover; border-radius:.5rem;"></video>
                </div>
                <button class="btn btn-primary w-100 mt-2" :disabled="!isCameraReady || isProcessingPhoto" @click="takePhoto">
                  {{ isProcessingPhoto ? 'Processing...' : 'Get Photo Proof' }}
                </button>
              </div>

              <!-- Right: Photo Preview & Info -->
              <div class="col-12 col-lg-6">
                <div class="card mb-2" style="height:200px;">
                    <div class="position-relative w-100 h-100" style="height:200px">
                     <template v-if="photoPreview" class="card-body p-0 d-flex align-items-center justify-content-center bg-light" style="height: 200px; overflow: hidden;">
                            <img
                                :src="photoPreview"
                                class="w-100 h-100 rounded img-thumbnail shadow-sm"
                                style="object-fit: contain;"
                            />
                            </template>
                    <div v-else class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted opacity-50">
                      <i class="fa-solid fa-image-portrait fs-1 mb-2 mt-2"></i>
                      <p class="small mb-0">No photo yet</p>
                    </div>
                  </div>
                </div>

                <div v-if="photoPreview" class="p-3 border rounded bg-light shadow-sm">
                  <div class="d-flex align-items-center mb-2">
                    <i class="fa-regular fa-calendar-check text-primary me-2"></i>
                    <div>
                      <div class="fw-bold" style="font-size:.7rem; color:#666;">LEAD VISIT DATE <small class="text-danger">**</small></div>
                      <div class="small fw-semibold text-dark">{{ currentDate }} - {{ currentTime }}</div>
                    </div>
                  </div>
                  <hr class="my-2">
                  <div class="d-flex align-items-start">
                    <i class="fa-solid fa-location-dot text-danger me-2 mt-1"></i>
                    <div style="word-break:break-word;">
                      <div class="fw-bold" style="font-size:.7rem; color:#666;">LEAD LOCATION SNAPSHOT <small class="text-danger">**</small></div>
                      <div class="small text-muted" style="font-size:11px; line-height:1.3;">{{ address || 'Detecting address...' }}</div>
                     <div
                        v-if="photoBlob && locationStatus !== 'Location detected'"
                        class="text-warning small mt-2 d-flex align-items-center"
                      >
                        <i class="fa-solid fa-location-crosshairs me-1"></i>
                        Waiting for location detection...
                      </div>
                    </div>
                    <div
                      v-if="photoBlob && locationStatus === 'Failed to detect location'"
                      class="text-danger small mt-2 d-flex align-items-center"
                    >
                      <i class="fa-solid fa-triangle-exclamation me-1"></i>
                      Location not detected. Please enable GPS and try again.
                    </div>
                  </div>
                  
                </div>
              </div>
              </div>
          </div>
          <hr>
          <div class="modal-footer">
           <button
              type="button"
              class="btn btn-secondary"
              :disabled="dataLeadsVisit.checkingInVisit"
              @click="cancelCheckIn"
            >
              Cancel
            </button>

           <button
            class="btn btn-success ms-auto"
            :disabled="!photoBlob || !latitude || !longitude || dataLeadsVisit.checkingInVisit"
            @click="submitCheckIn"
          >
            <!-- loading -->
            <span
              v-if="dataLeadsVisit.checkingInVisit"
              class="spinner-border spinner-border-sm me-2"
            ></span>

            <!-- icon normal -->
            <i
              v-else
              class="fa-solid fa-cloud-arrow-up me-2"
            ></i>

            <!-- text logic -->
            <span v-if="dataLeadsVisit.checkingInVisit">
              Saving...
            </span>

            <span v-else-if="photoBlob && (!latitude || !longitude)">
              Waiting Location...
            </span>

            <span v-else>
              Submit Check IN
            </span>
          </button>
          </div>
          </div>
          </div>
          </div>





        <!-- Modal: Visit Input check OUT -->
        <div class="modal modal-blur fade" id="modal-input-check-out" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">
                Check In OUT
                </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <hr>
              <div class="modal-body">
                <div class="row g-3">
                

                  
                  <!-- Notes & Status -->
                  <div class="col-12">
                    <div class="mb-3">
                      <label class="form-label fw-bold">Notes on Visit Results <small class="text-danger">**</small></label>
                      <textarea class="form-control"
                      v-model="form.notes" rows="5"
                      placeholder="Write a note here..."
                      :class="{ 'is-invalid': errors?.notes }"
                        ></textarea>
                    <div v-if="errors?.notes" class="invalid-feedback d-block">
                        {{ errors.notes[0] }}
                      </div>
                    </div>
                    </div>


                    <label class="form-label fw-bold">Update Data Status (Response Lead Customer) <small class="text-danger">**</small></label>
                      <div
                        class="row g-2 border rounded p-2"
                        :class="{ 'border-danger': errors?.customer_response }"
                      >
                        <div
                          class="col-12 col-sm-4"
                          v-for="status in statusOptions"
                          :key="status.value"
                        >
                          <div class="input-group">
                            <div class="input-group-text">
                              <input
                                type="radio"
                                class="form-check-input mt-0"
                                v-model="form.status"
                                :value="status.value"
                                :id="'status-' + status.value"
                              >
                            </div>
                            <label
                              class="form-control bg-white cursor-pointer"
                              :for="'status-' + status.value"
                            >
                              {{ status.label }}
                            </label>
                          </div>
                        </div>
                      </div>
                      <!-- ✅ ERROR DI BAWAH GROUP -->
                      <div v-if="errors?.customer_response" class="invalid-feedback d-block mt-1">
                        {{ errors.customer_response[0] }}
                      </div>
                  </div>
              </div>
              <hr>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" >Cancel</button>
                <button
                  class="btn btn-success ms-auto"
                  :disabled="dataLeadsVisit.checkingOutVisit"
                  @click="submitCheckOut"
                >
                  <span
                    v-if="dataLeadsVisit.checkingOutVisit"
                    class="spinner-border spinner-border-sm me-2"
                  ></span>
                  <i class="fa-solid fa-cloud-arrow-up me-2"></i>
                  Save
                </button>

              </div>
              </div>
              </div>
              </div>
          
        

  </backendLayouts>
</template>


<style scoped>


</style>