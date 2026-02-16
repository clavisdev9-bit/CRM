<script setup>
import { ref, onMounted, watch, reactive  } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useFollowUpsStore } from '../../../../stores/followUpStore';
import { useMenuStore } from "@/stores/menuStore";
import { useRoute, useRouter } from "vue-router";
import Swal from 'sweetalert2'
import Multiselect from "@vueform/multiselect"
import Flatpickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'

const PagesTitle = 'Data Follow Up';

/* ================= STORE ================= */
const followUpStore = useFollowUpsStore()
const menuStore = useMenuStore()

const route = useRoute()
const router = useRouter()

/* ================= LOAD DATA FIRST TIME ================= */
onMounted(() => {
  // hard lock ke LEADS dulu
  followUpStore.fetchFollowUps("leads")
})

/* ================= SEARCH WATCH ================= */
watch(
  () => followUpStore.search,
  (val) => {
    followUpStore.searchWithDelay(val)
  }
)

const normalizeStatus = (status) => {
  return status?.toUpperCase().replaceAll(' ', '_')
}
const StatusConfigFromLeads = {
  PROSPECTIVE_CUSTOMERS: {
    class: 'bg-info',
    icon: 'fa-solid fa-user-plus',
  },
  CONSIDERATION_STAGE: {
    class: 'bg-warning text-dark',
    icon: 'fa-solid fa-clock',
  },
  POTENTIAL_CUSTOMERS: {
    class: 'bg-primary',
    icon: 'fa-solid fa-star',
  },
  OTHER: {
    class: 'bg-dark',
    icon: 'fa-solid fa-tag',
  },
}

/* ================= SWITCH MODE (future ready) ================= */
const changeMode = (type) => {
  followUpStore.fetchFollowUps(type)
}

/* ================= OPEN MODAL ================= */
// start
const loading = ref(false);
const dataLeads = ref([]); // Isi dengan data dari API
const form = reactive({
  lead_id: '',
  follow_up_at: '',
  follow_up_type: '',
  status: '',
  done_action: '', // CONVERT or FAILED
  lead_category: '',
  notes: '',
  subject: '',
  subject_template: null,
});




const subjectTemplates = ref([
  // Initial Engagement
  { label: 'Terima Kasih atas Waktunya – Tindak Lanjut Diskusi', value: 'Terima Kasih atas Waktunya – Tindak Lanjut Diskusi' },
  { label: 'Menindaklanjuti Pembahasan Solusi untuk Kebutuhan Anda', value: 'Menindaklanjuti Pembahasan Solusi untuk Kebutuhan Anda' },

  // Needs Exploration
  { label: 'Pendalaman Kebutuhan & Potensi Kolaborasi', value: 'Pendalaman Kebutuhan & Potensi Kolaborasi' },
  { label: 'Diskusi Lanjutan Terkait Kebutuhan Bisnis Anda', value: 'Diskusi Lanjutan Terkait Kebutuhan Bisnis Anda' },

  // Solution Alignment
  { label: 'Penyesuaian Solusi Berdasarkan Diskusi Sebelumnya', value: 'Penyesuaian Solusi Berdasarkan Diskusi Sebelumnya' },
  { label: 'Sharing Insight & Rekomendasi untuk Kebutuhan Anda', value: 'Sharing Insight & Rekomendasi untuk Kebutuhan Anda' },

  // Proposal Soft Follow-up (belum closing)
  { label: 'Tindak Lanjut Proposal yang Telah Dibagikan', value: 'Tindak Lanjut Proposal yang Telah Dibagikan' },
  { label: 'Apakah Ada Hal yang Bisa Kami Sesuaikan?', value: 'Apakah Ada Hal yang Bisa Kami Sesuaikan?' },

  // Relationship Building
  { label: 'Menjaga Komunikasi & Update Perkembangan', value: 'Menjaga Komunikasi & Update Perkembangan' },
  { label: 'Terbuka untuk Diskusi Lanjutan Kapan Saja', value: 'Terbuka untuk Diskusi Lanjutan Kapan Saja' },

  // Re-engagement (kalau lead mulai dingin)
  { label: 'Follow Up Kembali – Siap Melanjutkan Diskusi', value: 'Follow Up Kembali – Siap Melanjutkan Diskusi' },
  { label: 'Apakah Masih Relevan untuk Kita Lanjutkan?', value: 'Apakah Masih Relevan untuk Kita Lanjutkan?' }
])


watch(() => form.subject_template, (val) => {
  if (val) {
    form.subject = val   // <-- LANGSUNG ISI
  }
})


const openAddModal = (type) => {
  followUpStore.mode = type
  resetForm()
}

const resetForm = () => {
  form.lead_id = ''
  form.follow_up_at = ''
  form.follow_up_type = ''
  form.status = ''
  form.done_action = ''
  form.lead_category = ''
  form.notes = ''
  form.subject = ''
  form.subject_template = ''
}

watch(() => form.status, (val) => {
  if (val === 'DONE') {
    form.lead_category = ''
  }

  if (val === 'PENDING') {
    form.done_action = ''
  }

  if (val === 'CANCELED') {
    form.done_action = ''
    form.lead_category = ''
  }
})


const errors = reactive({
  lead_id: null,
  follow_up_type: null,
  status: null,
})

// const validateForm = () => {
//   errors.lead_id = !form.lead_id ? 'Lead wajib dipilih' : null
//   errors.follow_up_type = !form.follow_up_type ? 'Type wajib dipilih' : null
//   errors.status = !form.status ? 'Status wajib dipilih' : null

//   if (form.status === 'DONE' && !form.done_action) {
//     Swal.fire('Action wajib dipilih untuk DONE')
//     return false
//   }

//   return !errors.lead_id && !errors.follow_up_type && !errors.status
// }
const validateForm = () => {
  // reset error dulu
  errors.lead_id = null
  errors.follow_up_type = null
  errors.status = null

  // validasi required
  if (!form.lead_id) errors.lead_id = 'Lead wajib dipilih'
  if (!form.follow_up_type) errors.follow_up_type = 'Type wajib dipilih'
  if (!form.status) errors.status = 'Status wajib dipilih'

  // validasi khusus DONE
  if (form.status === 'DONE' && !form.done_action) {
    Swal.fire({
      icon: 'warning',
      title: 'Validasi',
      text: 'Action wajib dipilih untuk DONE',
    })
    return false
  }

  return !errors.lead_id && !errors.follow_up_type && !errors.status
}


const submitFollowUp = async () => {
  if (!validateForm()) return

  loading.value = true

  try {
    console.log('VALID PAYLOAD:', form)

    Swal.fire({
      icon: 'success',
      title: 'Form Valid ✔',
      text: 'Ready to send API',
    })

    // nanti tinggal:
    // await followUpStore.storeFollowUp(form)

  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}


// const submitFollowUp = async () => {
//   if (!validateForm()) return

//   loading.value = true

//   try {
//     console.log('VALID PAYLOAD:', form)

//     Swal.fire({
//       icon: 'success',
//       title: 'Form Valid ✔',
//       text: 'Ready to send API',
//     })

//   } finally {
//     loading.value = false
//   }
// }

// end



// code desain form leads 



const fpConfig = {
  enableTime: true,
  time_24hr: true,
  dateFormat: 'Y-m-d H:i',
  minuteIncrement: 5,
  allowInput: true
}


const followUpStatusConfig = {
  PENDING: {
    class: 'bg-warning text-dark',
    label: 'PENDING',
  },
  DONE: {
    class: 'bg-success',
    label: 'DONE',
  },
  CANCELED: {
    class: 'bg-danger',
    label: 'CANCELED',
  },
}

const normalizeFollowUpStatus = (status) => {
  if (!status) return ''

  // mapping typo lama dari DB
  if (status === 'CANCELLED') return 'CANCELED'

  return status.toUpperCase()
}

const getFollowUpStatus = (status) => {
  const normalized = normalizeFollowUpStatus(status)

  return followUpStatusConfig[normalized] || {
    class: 'bg-secondary',
    label: normalized || '-',
  }
}


// const submitFollowUp = async () => {
//   loading.value = true;
//   try {
//     // Di sini kamu panggil API kamu
//     console.log("Payload yang dikirim:", form);
//     // await axios.post('/api/follow-up', form);
    
//     alert('Data Berhasil di-sync!');
//   } catch (error) {
//     console.error(error);
//   } finally {
//     loading.value = false;
//   }
// };



const handleDeleteFollowUp = async (followUp) => {
  const { isConfirmed } = await Swal.fire({
    title: 'Delete Follow Up?',
    html: `Follow Up <b>"${followUp.follow_up_code}"</b> will be permanently deleted.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
    reverseButtons: true,
  })

  if (!isConfirmed) return

  try {
    Swal.fire({
      title: 'Deleting...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    })

    //  ID USER YANG BENAR
    await followUpStore.deleteFollowUp(followUp.id)

    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      text: 'Follow Up has been deleted successfully.',
      timer: 1500,
      showConfirmButton: false,
    })
  } catch (e) {
    console.error(e)

    Swal.fire({
      icon: 'error',
      title: 'Failed',
      text:
        e.response?.data?.message ||
        'Failed to delete follow up.',
    })
  }
}



const openDetailFollowUp = async (followUp) => {
  try {
    followUpStore.followUpDetail = null // reset dulu
    await followUpStore.detailFollowUpData(followUp.id)

    const modalEl = document.getElementById('followUpDetailModal')
    const modal =
      bootstrap.Modal.getInstance(modalEl) ||
      new bootstrap.Modal(modalEl)

    modal.show()
  } catch (err) {
    console.error(err)
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
      <div  class="page-body flex-grow-1">
        <div class="container-xl">
          <!-- Card: Export/Import -->
         



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
                    v-model="followUpStore.pagination.per_page"
                      @change="followUpStore.changePageSize()"
                  >
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                    </select>
                </div>

                <!-- Tombol add -->
                 <div class="d-flex justify-content-between align-items-center mb-2"> 
                <!-- Kiri --> 
                <button class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" 
                   data-bs-target="#form-leads" @click="openAddModal('lead')" >
                <i class="fa fa-plus"></i> Add FL Follow Up </button>

                     <!-- Kanan --> 
                <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                       data-bs-target="#modal-add-data" @click="openAddModal('customer')" >
                        <i class="fa fa-plus"></i> Add FL Customer </button> 
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Filter Follow UP By:</label>
                  <select class="form-select w-auto"
                          @change="changeMode($event.target.value)">
                    <option value="leads">Leads</option>
                    <option value="customers">Customer</option>
                  </select>
                </div>
                </div>


              <!-- Kanan -->
             <div class="d-flex flex-column gap-3 align-items-end" style="min-width:300px;">
                <!-- Pencarian -->
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Searching.." 
                      @input="e => followUpStore.searchWithDelay(e.target.value)">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" 
                     v-model="followUpStore.sort.column"
                      @change="followUpStore.changeSorting()">
                    <option value="company_name">By Company Name</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto" 
                      v-model="followUpStore.sort.direction"
                      @change="followUpStore.changeSorting()">
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
              <table class="table card-table table-vcenter">
            <thead>
              <tr>
                   <th :colspan="followUpStore.mode === 'Leads' ? 11 : 11"
                        class="bg-light fw-bold text-primary">
                      <i class="fa fa-table me-2"></i>
                      {{ followUpStore.mode === 'customers'
                          ? 'Data Follow Up Customer'
                          : 'Data Follow Up Leads'
                      }}
                    </th>
                  </tr>
              <tr>
                <th style="width:5%">No.</th>
                <th>Code Follow Up</th>
                <th>Type</th>
                <th>Subject</th>
                <th>
                    <div>FollowUp</div>
                    <div>Lead / Customer</div>
                  </th>
                <th>Status Follow UP</th>
                <th>Status From Visit</th>
                <th>Date Visit / Created</th>
                <th>Estimated Follow Up return</th>
                <th style="width:10%">Actions</th>
              </tr>
            </thead>

            <tbody v-if="followUpStore.loading">
                <tr>
                  <td colspan="11" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>
            </tbody>

            <tbody v-else-if="followUpStore.followUp.length === 0">
                   <tr>
                      <td colspan="11" class="text-center">
                        <div class="d-flex flex-column align-items-center justify-content-center">
                          <img
                            src="https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif"
                            alt="No data found"
                            style="max-width: 250px; height: auto;"
                            class="mb-3"
                          />
                          <p class="text-danger fw-bold fst-italic">
                            <i class="fa fa-exclamation-circle me-1"></i>
                            Follow UP Data Not Found.
                          </p>
                        </div>
                      </td>
                    </tr>
          </tbody>

          <tbody v-else>
          <tr
            v-for="(item, index) in followUpStore.followUp"
            :key="item.id"
            :class="{ 'table-danger': item.is_overdue }"
          >
            <!-- NO -->
            <td>{{ index + 1 }}</td>
            <!-- CODE -->
            <td>{{ item.follow_up_code }}</td>
            <!-- TYPE -->
            <td>{{ item.follow_up_type }}</td>
            <!-- SUBJECT -->
            <td>{{ item.subject }}</td>
            <!-- TARGET -->
            <td>
              <div class="fw-bold">{{ item.target_name }}</div>
              <small class="text-muted">{{ item.target_source }}</small>
            </td>

            <!-- STATUS -->
            <td>
              <span
                class="badge"
                :class="
                  item.computed_status === 'OVERDUE'
                    ? 'bg-danger'
                    : item.status === 'PENDING'
                    ? 'bg-warning'
                    : 'bg-success'
                "
              >
                {{ item.computed_status }}
              </span>
            </td>

            <!-- LEAD STATUS -->
            <td>
                      <span
                        class="badge d-inline-flex align-items-center gap-1 px-2 py-1"
                        :class="StatusConfigFromLeads[normalizeStatus(item.lead_status)]?.class || 'bg-light text-dark'"
                      >
                        <i
                          :class="StatusConfigFromLeads[normalizeStatus(item.lead_status)]?.icon || 'fa-solid fa-circle-info'"
                        ></i>
                        {{ item.lead_status }}
                      </span>
            </td>


            <!-- CREATED -->
            <td class="fw-bold">{{  followUpStore.formatDate(item.created_at) }}</td>

            <!-- ESTIMATED -->
            <td class="fw-bold">
              <span :class="item.is_overdue ? 'text-danger fw-bold' : ''">
                {{ followUpStore.formatDate(item.follow_up_at) }}
              </span>

              <div v-if="item.is_overdue" class="small text-danger">
                <i class="fa-regular fa-bell"></i> Overdue — Need Action 
              </div>
            </td>

            <!-- ACTION -->
            <td>
              <button
                v-if="item.status === 'PENDING'"
                class="btn btn-sm btn-outline-primary me-1"
              >
                <i class="fa-regular fa-pen-to-square"></i>
              </button>

              <button
                v-if="item.status === 'PENDING'"
                class="btn btn-sm btn-outline-primary me-1"
                @click="handleDeleteFollowUp(item)"
              >
              <i class="fa-regular fa-trash-can"></i>
              </button>

            

              
              <span v-else class="badge bg-success">
                {{ item.status }}
              </span>


               <button
                
                type="button"
                data-bs-toggle="modal"
                    data-bs-target="#followUpDetailModal"
                class="btn btn-sm btn-outline-primary me-1 mt-1"
                @click="openDetailFollowUp(item)"
              >
                <i class="fa-regular fa-eye"></i>
            </button>

            
                <button
                    class="btn btn-outline-primary btn-sm mt-1"
                    data-bs-toggle="modal"
                    data-bs-target="#timeLineModal"
                    @click="followUpStore.fetchTimeline(item.id)"
                  >
                  <i class="fa-solid fa-timeline"></i> 
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
                  :disabled="followUpStore.pagination.current_page === 1 || followUpStore.loading"
                  @click="followUpStore.prevPage()" >
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                 <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                    <span class="badge border text-secondary px-3 py-2">
                    {{ followUpStore.followUp.length }} data |
                    page {{ followUpStore.pagination.current_page }}</span>
                    <span class="badge border text-secondary px-3 py-2">Total: {{ followUpStore.pagination.total }}</span>
                </div>
  
                <button class="btn btn-danger btn-sm"
                :disabled="followUpStore.pagination.current_page === followUpStore.pagination.last_page || followUpStore.loading"
                  @click="followUpStore.nextPage()"
               >
                    Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>
        </div>
      </div>
    </div>




<!-- code modal detail -->
    <div class="modal fade" id="followUpDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- HEADER -->
      <div class="modal-header">
        <h5 class="modal-title">
          Detail Follow Up
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- BODY -->
      <div class="modal-body">

        <!-- LOADING -->
        <div v-if="followUpStore.loadingDetail" class="text-center py-5">
          <div class="spinner-border text-primary"></div>
        </div>

        <!-- DATA -->
        <div v-else-if="followUpStore.followUpDetail">

          <!-- CODE -->
          <div class="alert alert-primary d-flex justify-content-between">
            <div>
              <strong>{{ followUpStore.followUpDetail.follow_up_code }}</strong><br>
              {{ followUpStore.followUpDetail.target_name }}
            </div>

           <span
              class="badge badge-pill"
              :class="getFollowUpStatus(followUpStore.followUpDetail.status).class"
            >
              {{ getFollowUpStatus(followUpStore.followUpDetail.status).label }}
            </span>
          </div>

          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label">Follow Up Type</label>
              <input class="form-control"
                :value="followUpStore.followUpDetail.follow_up_type"
                readonly>
            </div>

            <div class="col-md-6">
              <label class="form-label">Sales</label>
              <input class="form-control"
                :value="followUpStore.followUpDetail.sales_name"
                readonly>
            </div>

            <div class="col-md-12">
              <label class="form-label">Subject</label>
              <input class="form-control"
                :value="followUpStore.followUpDetail.subject"
                readonly>
            </div>

            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" rows="4" readonly>
                {{ followUpStore.followUpDetail.notes }}
              </textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">Follow Up Retun Estimate</label>
              <input class="form-control"
                :value="followUpStore.formatDates(followUpStore.followUpDetail.follow_up_at)"
                readonly>
            </div>

            <div class="col-md-6">
              <label class="form-label">Created Date</label>
              <input class="form-control"
                :value="followUpStore.formatDates(followUpStore.followUpDetail.created_at)"
                readonly>
            </div>

            <div class="col-md-6">
              <label class="form-label">Lead Company</label>
              <input class="form-control"
                :value="followUpStore.followUpDetail.lead_company_name"
                readonly>
            </div>

            <div class="col-md-6">
              <label class="form-label">Lead Status</label>
              <input class="form-control"
                :value="followUpStore.followUpDetail.lead_status"
                readonly>
            </div>

          </div>
        </div>

        <!-- EMPTY -->
        <div v-else class="text-center text-muted py-5">
          Data tidak ditemukan
        </div>

      </div>

      <!-- FOOTER -->
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">
          Close
        </button>
      </div>

    </div>
  </div>
</div>


<!-- form add dan edit -->
   <div class="modal modal-blur fade" id="form-leads" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Form Follow Up</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-lg-6">
              <label class="form-label">Lead <small class="text-danger">**</small></label><br>
                <small class="text-danger">{{ errors.lead_id }}</small>
              <Multiselect
                v-model="form.lead_id"
                :options="followUpStore.leadsOptions"
                :loading="followUpStore.loadingLeadsOptions"
                valueProp="lead_id"
                label="company_name"
                placeholder="Pilih Leads..."
                
                @open="followUpStore.fetchLeadsOptions()"
                @search-change="followUpStore.searchLeadsOptions" 
              >
              
              <!-- Dropdown List -->
              <template #option="{ option }">
                <div class="d-flex flex-column">
                  <strong>{{ option.company_name }}</strong>
                  <small class="text-muted">
                    {{ option.contact_name }}
                  </small>
                  <small
                    :class="{
                      'text-danger': option.urgency_status === 'OVERDUE',
                      'text-warning': option.urgency_status === 'DUE_SOON',
                      'text-success': option.urgency_status === 'SCHEDULED'
                    }"
                  >
                    ⏱ {{ option.time_remaining_text }}
                  </small>
                  </div>
                </template>

                      <!-- Selected Value -->
                      <template #singlelabel="{ value }">
                        <div>
                          {{ value.company_name }}
                          <small class="ms-2 text-muted">
                            ({{ value.time_remaining_text }})
                          </small>
                        </div>
                      </template>
                    </Multiselect>
                </div>



            <div class="col-lg-3">
              <label class="form-label">Follow-up Date Estimate Return <small class="text-success">(*ops*)</small></label>
              <Flatpickr
                 v-model="form.follow_up_at"
                 :config="fpConfig"   
                 class="form-control"
                 placeholder="Select date & time"
              />
            </div>

            <div class="col-lg-3">
              <label class="form-label">Type Follow UP <small class="text-danger">**</small></label>
              <Multiselect
                                v-model="form.follow_up_type"
                                :options="followUpStore.typeFollowUp"
                                label="label"
                                valueProp="value"
                                placeholder="Select Follow Up"
                                @update:modelValue="() => {
                                  if (followUpStore.error?.follow_up_type) {
                                    followUpStore.error.follow_up_type = null
                                  }
                                }"
                              />
            </div>

            <div class="col-lg-12">
              <label class="form-label fw-bold">Follow Up Result Status <small class="text-danger">**</small></label>
              <select v-model="form.status" class="form-select form-select-lg border-primary">
                <option value="">-- Choose Status --</option>
                <option value="PENDING">PENDING</option>
                <option value="DONE">DONE OR FAILED</option>
                <!-- <option value="CANCELED">CANCELED</option> -->
              </select>
            </div>

            <div class="col-lg-12">
              <transition name="fade">
                <div v-if="form.status === 'DONE'" class="p-3 border border-success rounded bg-light">
                  <label class="form-label text-success fw-bold">Action after Done:</label>
                  <div class="d-flex gap-4">
                    <label class="form-check">
                      <input type="radio" v-model="form.done_action" value="convert" class="form-check-input">
                      <span class="form-check-label">Convert to Customer</span>
                    </label>
                    <label class="form-check">
                      <input type="radio" v-model="form.done_action" value="failed" class="form-check-input">
                      <span class="form-check-label">Mark Lead as Failed</span>
                    </label>
                  </div>
                  <small class="text-muted text-italic">*Semua data follow-up Lead ini akan ditandai DONE.</small>
                </div>

                <div v-else-if="form.status === 'PENDING'" class="p-3 border border-warning rounded bg-light">
                  <label class="form-label text-warning fw-bold">Update Lead Category (Optional):</label>
                  <select v-model="form.lead_category" class="form-control">
                    <option value="">-- Keep Current Data --</option>
                    <option value="potential_customers">Potential Customers</option>
                    <option value="consideration_stage">Consideration Stage</option>
                    <option value="prospective_customers">Prospective Customers</option>
                  </select>
                </div>

                <!-- <div v-else-if="form.status === 'CANCELED'" class="p-3 border border-danger rounded bg-light">
                  <p class="mb-0 text-danger">
                    <strong>Note:</strong> Status Lead akan otomatis berubah menjadi <strong>Failed</strong> di database.
                  </p>
                </div> -->
              </transition>
            </div>


         <div class="col-lg-6">
  <label class="form-label">
    Template Subject <small class="text-success">(opsional)</small>
  </label>

 <Multiselect
  v-model="form.subject_template"
  :options="subjectTemplates"
  label="label"
  valueProp="value"
  trackBy="value"
  placeholder="Pilih Template Subject"
  :searchable="true"
/>

</div>


<div class="col-lg-6">
  <label class="form-label">
    Subject <small class="text-danger">**</small>
  </label>

  <input
    v-model="form.subject"
    type="text"
    class="form-control"
    placeholder="Tulis subject atau pilih template"
  />
</div>


<div class="col-lg-12">
  <label class="form-label">
    Notes <small class="text-success">(*opsional)</small>
  </label>

  <textarea v-model="form.notes" class="form-control" rows="3"></textarea>
</div>


           
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Close</button>
          <button @click="submitFollowUp" class="btn btn-primary ms-auto" :disabled="loading">
            {{ loading ? 'Processing...' : 'Save & Sync Tables' }}
          </button>
        </div>
      </div>
    </div>
  </div>




   <!-- Code Modal: Timeline Data -->
    <div class="modal modal-blur fade" id="timeLineModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          
          <!-- Header -->
          <div class="modal-header">
          <h5 class="modal-title">
            Timeline - {{ followUpStore.selectedFollowUpCode }}
          </h5>

            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <!-- Body -->
        <div class="modal-body">

            <!-- LOADING -->
            <div v-if="followUpStore.loadingTimeline"
                class="d-flex justify-content-center py-5">
              <div class="spinner-border text-primary"></div>
            </div>

            <!-- EMPTY -->
            <div v-else-if="followUpStore.timeline.length === 0"
                class="text-center text-muted py-4">
              No Activity Found
            </div>

            <!-- TIMELINE -->
            <div v-else class="timeline-wrapper">

              <div
                v-for="(item, index) in followUpStore.timeline"
                :key="index"
                class="timeline-step"
              >
                <div class="circle" :class="{ active: index === 0 }"></div>

                <div class="label fw-bold">
                  {{ item.activity }}
                </div>

                <small class="text-muted d-block">
                  {{ item.activity_at }}
                </small>

                <div class="small mt-2">
                  {{ item.description }}
                </div>
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

  </backendLayouts>
</template>


<style scoped>
/* BACKDROP */
.custom-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

/* MODAL BOX */
.custom-modal-content {
  background: #fff;
  padding: 30px;
  border-radius: 12px;
  width: 700px;
}

/* TIMELINE */
.timeline-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  margin-top: 40px;
}

/* LINE */
.timeline-wrapper::before {
  content: "";
  position: absolute;
  top: 20px;
  left: 0;
  right: 0;
  height: 3px;
  background: #dc3545;
  z-index: 0;
}

/* STEP */
.timeline-step {
  position: relative;
  text-align: center;
  z-index: 1;
  width: 100%;
}

/* CIRCLE */
.circle {
  width: 40px;
  height: 40px;
  border: 3px solid #dc3545;
  border-radius: 50%;
  background: white;
  margin: auto;
}

/* ACTIVE STEP */
.circle.active {
  background: #dc3545;
}

/* LABEL */
.label {
  margin-top: 10px;
  font-size: 14px;
  color: #dc3545;
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>