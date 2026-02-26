
<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue"
import { useDataCustomerVisitStore } from '../../../../stores/customersVisitsStore'
import { useRoute, useRouter } from "vue-router"
import { toasts } from "@/utils/toasts"
import Swal from 'sweetalert2';


// ======================================================
// BASIC
// ======================================================
const PagesTitle = 'Data Customers Ready To Visit'
const router = useRouter()
const route = useRoute()
const dataCustomerVisit = useDataCustomerVisitStore()


// ======================================================
// HELPER DATE & TIME
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
// HELPER FOR DATA IN TABLE
// ======================================================
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


// ======================================================
// LIFECYCLE
// ======================================================
onMounted(async () => {
  updateDateTime()
  timer = setInterval(updateDateTime, 1000)
  await dataCustomerVisit.fetchCustomersVisitStore(
    dataCustomerVisit.buildUrl()
  )
})



// code for see more address in modal
const selectedAddress = ref('')
const openAddressModal = (cvd) => {
  selectedAddress.value = cvd.address

  const modalEl = document.getElementById('modal-address')
  const instance =
    bootstrap.Modal.getInstance(modalEl) ||
    new bootstrap.Modal(modalEl)

  instance.show()
}

const openGoogleMaps = (address) => {
  const url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`
  window.open(url, '_blank')
}


// code for start visit
const confirmStartVisit = (customer) => {
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
      await dataCustomerVisit.startVisit(customer.id)
    } catch (err) {
      Swal.fire(
        'Failed',
        err.response?.data?.message ?? 'Cannot start visit',
        'error'
      )
    }
  })
}



const selectedCustomersCheckIn = ref(null)
const openVisitModalCheckIn = (customer) => {
  selectedCustomersCheckIn.value = customer
  startCamera();

  console.log('Target Visit ID:', customer.active_visit_id); // Cek di console
  
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
const errors = ref({});

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


const isLocationReady = computed(() => {
  return (
    latitude.value !== null &&
    longitude.value !== null &&
    locationStatus.value === 'Location detected' &&
    address.value &&
    address.value !== 'Detecting address...'
  )
})


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



const submitCheckIn = async () => {
  if (!selectedCustomersCheckIn.value) return
  errors.value = {}

  if (!photoBlob.value) {
    errors.value.photo = ['Photo evidence is required']
    return
  }

  if (!latitude.value || !longitude.value) {
    errors.value.latitude = ['Location not detected']
    return
  }

  if (!address.value) {
    errors.value.gps_snapshot = ['GPS snapshot is required']
    return
  }
  

  try {
     await dataCustomerVisit.checkInVisit({
      visitId: selectedCustomersCheckIn.value.active_visit_id,
      latitude: latitude.value,
      longitude: longitude.value,
      gps_snapshot: address.value,
      photoBlob: photoBlob.value
    })

    resetVisitState()

    const modal = document.getElementById("modal-input-check-in")
    const instance =
      bootstrap.Modal.getInstance(modal) ||
      new bootstrap.Modal(modal)

    instance.hide()

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


// ==============================
// CHECK OUT STATE
// ==============================
const selectedCustomerCheckOut = ref(null)
const form = reactive({
  notes: '',
  status: '',
  has_complaint: false,
  complaint_detail: '',
  has_potential_order: false,
  potential_order_detail: '',
  follow_up_date: '',
  follow_up_type: '',
  follow_up_notes: '',
})

const statusOptions = [
  {
    value: 'maintained',
    label: 'Relationship Maintained (Routine Visit / Engagement)'
  },
  {
    value: 'improved',
    label: 'Relationship Improved / Positive Development'
  },
  {
    value: 'upsell_identified',
    label: 'Upsell / Additional Opportunity Identified'
  },
  {
    value: 'renewal_discussed',
    label: 'Contract Renewal / Continuation Discussed'
  },
  {
    value: 'complaint_handled',
    label: 'Complaint Addressed / Issue Resolved'
  },
  {
    value: 'at_risk',
    label: 'Customer At Risk (Low Usage / Negative Signal)'
  },
  {
    value: 'no_progress',
    label: 'No Significant Progress'
  }
]

// ==============================
// OPEN MODAL
// ==============================
const openVisitModalCheckOut = (customers) => {
  selectedCustomerCheckOut.value = customers

  form.notes = ''
  form.status = ''
  form.has_complaint = false
  form.complaint_detail = ''
  form.has_potential_order = false
  form.potential_order_detail = ''
  form.follow_up_date = ''
  form.follow_up_type = ''
  form.follow_up_notes = ''

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
  if (!selectedCustomerCheckOut.value) return

  try {
    errors.value = {} //  reset error dulu

    await dataCustomerVisit.checkOutVisit({
      visitId: selectedCustomerCheckOut.value.active_visit_id,
      notes: form.notes,
      customer_response: form.status,
      has_complaint: form.has_complaint,
      complaint_detail: form.complaint_detail,
      has_potential_order: form.has_potential_order,
      potential_order_detail: form.potential_order_detail,
      follow_up_at: form.follow_up_date,
      follow_up_type: form.follow_up_type,
      follow_up_notes: form.follow_up_notes
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
                  <tr v-for="(cvd, index) in dataCustomerVisit.customersVisitData" :key="cvd.id">
                    <td>{{ index + 1 + dataCustomerVisit.pagination.per_page * (dataCustomerVisit.pagination.current_page - 1) }}.</td>
                    <td>{{ cvd.company_name }}</td>
                    <td>{{ cvd.contact_name }}</td>
                    <td>
                        <span 
                          class="text-primary fw-bold fs-6" 
                          style="cursor: pointer; font-size: 11px;"
                          @click="openAddressModal(cvd)"
                        >
                          <i class="fa-solid fa-arrow-right"></i> See More Address
                        </span>
                    </td>
                    <td>{{ cvd.phone }}</td>
                   <td>
                       <span
                          class="badge rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1"
                          :class="getStatusBadgeClass(cvd.customer_status)"
                        >
                          <i
                            class="fa-solid"
                            :class="{
                              'fa-circle-check': cvd.customer_status === 'Active',
                              'fa-clock': cvd.customer_status === 'Dormant',
                              'fa-pause': cvd.customer_status === 'Inactive',
                              'fa-xmark': cvd.customer_status === 'Lost',
                              'fa-ban': cvd.customer_status === 'Blacklist'
                            }"
                          ></i>
                          {{ cvd.customer_status }}
                        </span>
                    </td>
                    <td>
                     <td>
                      <td>
                    <!-- VISIT NOW -->
                    <button
                       v-if="!cvd.active_visit_id"
                      class="btn btn-primary btn-sm me-1"
                       @click="confirmStartVisit(cvd)"
                    >
                      <i class="fa-solid fa-street-view"></i> Visit Now
                    </button>

                    <!-- ONGOING (BELUM CHECK IN) -->
                    <button
                      v-else-if="cvd.visit_status === 'ONGOING'"
                      class="btn btn-secondary btn-sm me-1"
                      disabled
                    >
                      <i class="fa-solid fa-car-on"></i> Visits On The Way
                    </button>

                    <!-- SEDANG CHECK IN -->
                    <button
                      v-else-if="cvd.visit_status === 'CHECKED_IN'"
                      class="btn btn-warning btn-sm me-1"
                      disabled
                    >
                      <i class="fa-solid fa-location-dot"></i> Currently Checking In
                    </button>

                    <!-- CHECK IN -->
                    <button
                      class="btn btn-sm btn-primary me-1"
                      :disabled="cvd.visit_status !== 'ONGOING'"
                      data-bs-toggle="modal"
                      data-bs-target="#modal-input-check-in"
                      @click="openVisitModalCheckIn(cvd)"
                      >
                      <i class="fa-solid fa-building-circle-arrow-right"></i> Check In
                    </button>

                    

                    <!-- CHECK OUT -->
                    <button
                      class="btn btn-sm btn-success"
                      :disabled="cvd.visit_status !== 'CHECKED_IN'"
                      data-bs-toggle="modal"
                      data-bs-target="#modal-input-check-out"
                      @click="openVisitModalCheckOut(cvd)"
                    >
                      <i class="fa-solid fa-building-circle-check"></i> Check Out
                    </button>
                  </td>
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



            <!-- Modal  untuk alamat-->
             <!-- Modal -->
          <div class="modal fade" id="modal-address" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h6 class="modal-title">Complete address</h6>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <p class="mb-0">{{ selectedAddress }}</p>
                </div>
                <div class="modal-footer">
                  <button 
                    type="button" 
                    class="btn btn-secondary btn-sm" 
                    data-bs-dismiss="modal"
                  >
                    Close
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-primary btn-sm"
                    @click="openGoogleMaps(selectedAddress)"
                  >
                    <i class="fa-solid fa-map-location-dot"></i> Open in Google Maps
                  </button>
                </div>
              </div>
            </div>
          </div>




           <div class="modal modal-blur fade" id="modal-input-check-in" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
            Check In Customer
            </h5>
           <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="resetVisitState"></button>
          </div>
          <hr>
          <div class="modal-body">

            <!-- ERROR VALIDATION -->
            <div v-if="Object.keys(errors).length" class="alert alert-danger mt-2">
              <ul class="mb-0 ps-3">
                <li v-if="errors.latitude">{{ errors.latitude[0] }}</li>
                <li v-if="errors.longitude">{{ errors.longitude[0] }}</li>
                <li v-if="errors.gps_snapshot">{{ errors.gps_snapshot[0] }}</li>
                <li v-if="errors.photo">{{ errors.photo[0] }}</li>
              </ul>
            </div>

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
              class="btn btn-success ms-auto"
              :disabled="
                !photoBlob ||
                !isLocationReady ||
                isProcessingPhoto ||
                dataCustomerVisit.checkingInVisit
              "
              @click="submitCheckIn"
            >
              <!-- loading -->
              <span
                v-if="dataCustomerVisit.checkingInVisit"
                class="spinner-border spinner-border-sm me-2"
              ></span>

              <!-- icon normal -->
              <i
                v-else
                class="fa-solid fa-cloud-arrow-up me-2"
              ></i>

              <!-- text logic -->
              <span v-if="dataCustomerVisit.checkingInVisit">
                Saving...
              </span>

              <!--  ganti pakai isLocationReady -->
              <span v-else-if="photoBlob && !isLocationReady">
                Detecting location...
              </span>

              <span v-else>
                Submit Check-In
              </span>
            </button>
          </div>
          </div>
          </div>
          </div>





           <!-- Modal: Visit Input check OUT -->
   <!-- Modal: Visit Input check OUT -->
<div class="modal modal-blur fade" id="modal-input-check-out" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa-solid fa-building-circle-check me-2 text-success"></i>
          Check OUT Customer
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <hr>

      <div class="modal-body">
        <div class="row g-3">

          <!-- 1. Notes Visit -->
          <div class="col-12">
            <label class="form-label fw-bold">
              <i class="fa-solid fa-note-sticky text-primary me-1"></i>
              Notes on Visit Results <small class="text-danger">**</small>
            </label>
            <textarea
              class="form-control"
              v-model="form.notes"
              rows="4"
              placeholder="Write a note here..."
              :class="{ 'is-invalid': errors?.notes }"
            ></textarea>
           <div v-if="errors?.notes" class="invalid-feedback d-block">
                        {{ errors.notes[0] }}
                      </div>
          </div>

          <!-- 2. Status Response -->
          <div class="col-12">
            <label class="form-label fw-bold">
              <i class="fa-solid fa-clipboard-check text-primary me-1"></i>
              Update Data Status (Response Customer) <small class="text-danger">**</small>
            </label>
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
            <div v-if="errors?.customer_response" class="invalid-feedback d-block mt-1">
              {{ errors.customer_response[0] }}
            </div>
          </div>

          <!-- 3. Complaint -->
          <div class="col-12">
            <div class="border rounded p-3">
              <div class="form-check form-switch mb-2">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="hasComplaint"
                  v-model="form.has_complaint"
                >
                <label class="form-check-label fw-bold" for="hasComplaint">
                  <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Any Complaint?
                </label>
              </div>
              <div v-if="form.has_complaint">
                <textarea
                  class="form-control"
                  v-model="form.complaint_detail"
                  rows="2"
                  placeholder="Describe the complaint..."
                  :class="{ 'is-invalid': errors?.complaint_detail }"
                ></textarea>
                <div v-if="errors?.complaint_detail" class="invalid-feedback d-block">
                  {{ errors.complaint_detail[0] }}
                </div>
              </div>
            </div>
          </div>

          <!-- 4. Potential Order -->
          <div class="col-12">
            <div class="border rounded p-3">
              <div class="form-check form-switch mb-2">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="hasPotentialOrder"
                  v-model="form.has_potential_order"
                >
                <label class="form-check-label fw-bold" for="hasPotentialOrder">
                  <i class="fa-solid fa-sack-dollar text-success me-1"></i> Potential Order / Upsell?
                </label>
              </div>
              <div v-if="form.has_potential_order">
                <textarea
                  class="form-control"
                  v-model="form.potential_order_detail"
                  rows="2"
                  placeholder="Describe the potential order..."
                  :class="{ 'is-invalid': errors?.potential_order_detail }"
                ></textarea>
                <div v-if="errors?.potential_order_detail" class="invalid-feedback d-block">
                  {{ errors.potential_order_detail[0] }}
                </div>
              </div>
            </div>
          </div>

          <!-- 5. Next Follow Up -->
          <div class="col-12">
            <label class="form-label fw-bold">
              <i class="fa-solid fa-calendar-check text-primary me-1"></i>
              Next Follow Up <small class="text-danger">**</small>
            </label>
            <div class="row g-2">

              <!-- Tanggal -->
              <div class="col-12 col-sm-6">
                <label class="form-label small text-muted">Date</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="form.follow_up_date"
                  :min="new Date().toISOString().split('T')[0]"
                  :class="{ 'is-invalid': errors?.follow_up_at }"
                >
                <div v-if="errors?.follow_up_at" class="invalid-feedback d-block">
                  {{ errors.follow_up_at[0] }}
                </div>
              </div>

              <!-- Type -->
              <div class="col-12 col-sm-6">
                <label class="form-label small text-muted">Type</label>
                <select
                  class="form-select"
                  v-model="form.follow_up_type"
                  :class="{ 'is-invalid': errors?.follow_up_type }"
                >
                  <option value="">-- Select Type --</option>
                  <option value="CALL">📞 Call</option>
                  <option value="VISIT">🏢 Visit</option>
                  <option value="WHATSAPP">💬 WhatsApp</option>
                  <option value="EMAIL">📧 Email</option>
                  <option value="MEETING">🏢 Meeting</option>
                  <option value="OTHER">📦 Other</option>
                </select>
                <div v-if="errors?.follow_up_type" class="invalid-feedback d-block">
                  {{ errors.follow_up_type[0] }}
                </div>
              </div>

              <!-- Notes Follow Up -->
              <div class="col-12">
                <label class="form-label small text-muted">
                  Follow Up Notes <span class="text-muted">(optional)</span>
                </label>
                <textarea
                  class="form-control"
                  v-model="form.follow_up_notes"
                  rows="2"
                  placeholder="e.g. Discuss new product offer..."
                ></textarea>
              </div>

            </div>
          </div>

        </div>
      </div>

      <hr>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button
          class="btn btn-success"
          @click="submitCheckOut"
          :disabled="dataCustomerVisit.checkingOutVisit"
        >
          <span
            v-if="dataCustomerVisit.checkingOutVisit"
            class="spinner-border spinner-border-sm me-2"
          ></span>
          <i v-else class="fa-solid fa-cloud-arrow-up me-2"></i>
          {{ dataCustomerVisit.checkingOutVisit ? 'Saving...' : 'Save' }}
        </button>
      </div>

    </div>
  </div>
</div>


  </backendLayouts>
</template>


<style scoped>


</style>