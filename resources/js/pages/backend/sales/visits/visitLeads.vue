<script setup>
import { ref, reactive, onMounted, onUnmounted, watch, computed } from 'vue';
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

// ==============================
// Form
// ==============================
const form = reactive({
  notes: '',
  status: 'follow_up'
});

const statusOptions = [
  { value: 'potential_customers', label: 'Potential Customers', desc: 'potential customers' },
  { value: 'consideration_stage', label: 'Consideration Stage', desc: 'consideration stage' },
  { value: 'prospective_customers', label: 'Prospective Customers', desc: 'Prospective Customers' },
  { value: 'failed', label: 'Failed', desc: 'Failed OR Rejected' },
  { value: 'customer', label: 'Covert To Customer', desc: 'Closing' }
];

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

  form.notes = '';
  form.status = 'follow_up';
};

// ==============================
// Submit
// ==============================
const isSubmitDisabled = computed(() => {
  return !photoBlob.value || !latitude.value || !longitude.value || isProcessingPhoto.value;
});

const submitVisit = () => {
  if (!photoBlob.value) return alert('Ambil foto kunjungan terlebih dahulu!');
  
  console.log('Mengirim Data:', {
    photo: photoBlob.value,
    location: { lat: latitude.value, lng: longitude.value },
    notes: form.notes,
    status: form.status
  });
  alert('Data Berhasil Disimpan!');
};

// ==============================
// Lifecycle
// ==============================
onMounted(async () => {
  updateDateTime();
  timer = setInterval(updateDateTime, 1000);

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

  const modalEl = document.getElementById('modal-input-visit');
  if (modalEl) {
    modalEl.addEventListener('shown.bs.modal', startCamera);
    modalEl.addEventListener('hidden.bs.modal', resetVisitState);
  }
});

onUnmounted(() => {
  clearInterval(timer);
  resetVisitState();
});

// ==============================
// Watchers
// ==============================
watch(() => dataLeadsVisit.searchLeadVisit, dataLeadsVisit.searchWithDelay);

document.addEventListener('visibilitychange', () => {
  if (document.hidden) resetVisitState();
});
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
              <h4 class="page-title">{{ PagesTitle }}</h4>
            </div>
            <div class="col-auto ms-auto d-print-none">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                  <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page">{{ PagesTitle }}</li>
                </ol>
              </nav>
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
              <!-- Kiri: Show & Back -->
              <div class="d-flex flex-column gap-3">
                <div class="d-flex align-items-center gap-2">
                  <label class="mb-0 fw-semibold"><i class="fas fa-list-ul me-1"></i> Showing:</label>
                  <select
                    class="form-select w-auto"
                    v-model.number="dataLeadsVisit.pagination.per_page"
                    @change="dataLeadsVisit.changePageSize(dataLeadsVisit.pagination.per_page)"
                  >
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                  </select>

                </div>

                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-add-data">
                  <i class="fa-solid fa-arrow-left"></i> Back
                </button>
              </div>

              <!-- Kanan: Search & Sort -->
              <div class="d-flex flex-column gap-3 align-items-end" style="min-width:300px;">
                <div class="input-group">
                  <input type="text" class="form-control" placeholder="Search..." v-model="dataLeadsVisit.searchLeadVisit">
                  <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <div class="d-flex gap-2 align-items-center">
                  <label class="mb-0 fw-semibold">Sort:</label>
                  <select class="form-select w-auto" 
                 v-model="dataLeadsVisit.sort.column" @change="dataLeadsVisit.changeSorting">
                    <option value="company_name">By Company Name</option>
                    <option value="created_at">By Created Date</option>
                  </select>
                  <select class="form-select w-auto" 
                 v-model="dataLeadsVisit.sort.direction" @change="dataLeadsVisit.changeSorting">
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
                  <td colspan="8" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>
              </tbody>

              <!-- EMPTY DATA -->
              <tbody v-else-if="dataLeadsVisit.leadVisitData.length === 0">
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
                            Lead data not found.
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
                    <td>{{ lvd.address }}</td>
                    <td>{{ lvd.phone }}</td>
                    <td>{{ lvd.lead_status }}</td>
                    <td>
                      <button class="btn btn-outline-primary" 
                      data-bs-toggle="modal" 
                      data-bs-target="#modal-input-visit"
                        @click="selectedLead = lvd">
                        <i class="fa-solid fa-street-view"></i> Visit Now
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!dataLeadsVisit.leadVisitData.length">
                    <td colspan="7" class="text-center text-muted">No leads found</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Card: Pagination -->
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <button class="btn btn-danger btn-sm" 
                :disabled="!dataLeadsVisit.pagination.prev_page_url || dataLeadsVisit.loadingLeadVisit"
                  @click="dataLeadsVisit.fetchLeadsVisitStore(dataLeadsVisit.pagination.prev_page_url)">
                <i class="fa-solid fa-circle-chevron-left"></i> Prev
              </button>

              <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                <span class="badge border text-secondary px-3 py-2"> {{ dataLeadsVisit.leadVisitData.length }} data | on page {{ dataLeadsVisit.pagination.current_page }}</span>
                    <span class="badge border text-secondary px-3 py-2">Total: {{ dataLeadsVisit.pagination.total }}</span>
              </div>

              <button class="btn btn-danger btn-sm" 
               :disabled="!dataLeadsVisit.pagination.next_page_url || dataLeadsVisit.loadingMenus"
                  @click="dataLeadsVisit.fetchLeadsVisitStore(dataLeadsVisit.pagination.next_page_url)">
                Next <i class="fa-solid fa-circle-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Visit Input -->
    <div class="modal modal-blur fade" id="modal-input-visit" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Lead Visit Report   <span v-if="selectedLead"> - {{ selectedLead.company_name }}</span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
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
                  <div class="position-relative w-100 h-100">
                    <template v-if="photoPreview">
                      <img :src="photoPreview" class="w-100 h-100 rounded img-thumbnail shadow-sm" style="object-fit:contain;" />
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
                      <div class="fw-bold" style="font-size:.7rem; color:#666;">VISIT DATE</div>
                      <div class="small fw-semibold text-dark">{{ currentDate }} - {{ currentTime }}</div>
                    </div>
                  </div>
                  <hr class="my-2">
                  <div class="d-flex align-items-start">
                    <i class="fa-solid fa-location-dot text-danger me-2 mt-1"></i>
                    <div style="word-break:break-word;">
                      <div class="fw-bold" style="font-size:.7rem; color:#666;">LOCATION SNAPSHOT</div>
                      <div class="small text-muted" style="font-size:11px; line-height:1.3;">{{ address || 'Detecting address...' }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notes & Status -->
              <div class="col-12">
                <div class="mb-3">
                  <label class="form-label">Notes on Visit Results</label>
                  <textarea class="form-control" v-model="form.notes" rows="2" placeholder="Write a note here..."></textarea>
                </div>

                <label class="form-label fw-bold">Update Data Status:</label>
                <div class="row g-2">
                  <div class="col-12 col-sm-4" v-for="status in statusOptions" :key="status.value">
                    <div class="input-group">
                      <div class="input-group-text">
                        <input type="radio" class="form-check-input mt-0" v-model="form.status" :value="status.value" :id="'status-' + status.value">
                      </div>
                      <label class="form-control bg-white cursor-pointer" :for="'status-' + status.value">{{ status.label }}</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success ms-auto" :disabled="isSubmitDisabled" @click="submitVisit">
              <i class="fa-solid fa-cloud-arrow-up me-2"></i> Save Report Lead
            </button>
          </div>
        </div>
      </div>
    </div>
  </backendLayouts>
</template>
