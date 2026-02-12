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

/* ================= SWITCH MODE (future ready) ================= */
const changeMode = (type) => {
  followUpStore.fetchFollowUps(type)
}

/* ================= OPEN MODAL ================= */
const openAddModal = (type) => {
  followUpStore.mode = type
}


// code desain form leads 

const loading = ref(false);
const dataLeads = ref([]); // Isi dengan data dari API

const form = reactive({
  lead_id: '',
  follow_up_at: '',
  follow_up_type: '',
  status: '',
  done_action: '', // CONVERT or FAILED
  lead_category: '',
  notes: ''
});

const submitFollowUp = async () => {
  loading.value = true;
  try {
    // Di sini kamu panggil API kamu
    console.log("Payload yang dikirim:", form);
    // await axios.post('/api/follow-up', form);
    
    alert('Data Berhasil di-sync!');
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};


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
                    <input type="text" class="form-control" placeholder="Searching....">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" >
                    <option value="company_name">By Company Name</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto" >
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
                   <th :colspan="mode === 'Leads' ? 11 : 11"
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
                <th>Follow Up At</th>
                <th>Status Follow UP</th>
                <th>Status From Visit</th>
                <th>Date Visit / Created</th>
                <th>Estimated Follow Up return</th>
                <th style="width:10%">Actions</th>
              </tr>
            </thead>

          <tbody>
      <!-- LOADING -->
      <tr v-if="followUpStore.loading">
        <td colspan="11" class="text-center">Loading...</td>
      </tr>

      <!-- EMPTY -->
      <tr v-else-if="followUpStore.followUp.length === 0">
        <td colspan="11" class="text-center">No Data</td>
      </tr>

      <!-- DATA -->
      <tr
        v-else
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

        <!-- FOLLOW UP DATE -->
        <td>{{ item.follow_up_at }}</td>

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
        <td>{{ item.lead_status }}</td>

        <!-- CREATED -->
        <td>{{ item.created_at }}</td>

        <!-- ESTIMATED -->
        <td>
          <span :class="item.is_overdue ? 'text-danger fw-bold' : ''">
            {{ item.follow_up_at }}
          </span>

          <div v-if="item.is_overdue" class="small text-danger">
            Overdue — Need Action
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
          >
          <i class="fa-regular fa-trash-can"></i>
          </button>

          <button
            v-if="item.status === 'PENDING'"
            class="btn btn-sm btn-outline-primary me-1 mt-1"
          >
            <i class="fa-regular fa-eye"></i>
          </button>
          
          <span v-else class="badge bg-success">
            {{ item.status }}
          </span>

        
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
               >
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                 <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                    <span class="badge border text-secondary px-3 py-2"> 11 data | on page 11</span>
                    <span class="badge border text-secondary px-3 py-2">Total: 11</span>
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
              <label class="form-label">Lead <small class="text-danger">**</small></label>
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
              <input type="datetime-local" v-model="form.follow_up_at" class="form-control">
            </div>

            <div class="col-lg-3">
              <label class="form-label">Type Follow UP <small class="text-danger">**</small></label>
              <select v-model="form.follow_up_type" class="form-control">
                <option value="CALL">Call</option>
                <option value="WHATSAPP">WhatsApp</option>
                <option value="MEETING">Meeting</option>
              </select>
            </div>

            <div class="col-lg-12">
              <label class="form-label fw-bold">Follow Up Result Status <small class="text-danger">**</small></label>
              <select v-model="form.status" class="form-select form-select-lg border-primary">
                <option value="">-- Choose Status --</option>
                <option value="PENDING">PENDING</option>
                <option value="DONE">DONE</option>
                <option value="CANCELED">CANCELED</option>
              </select>
            </div>

            <div class="col-lg-12">
              <transition name="fade">
                <div v-if="form.status === 'DONE'" class="p-3 border border-success rounded bg-light">
                  <label class="form-label text-success fw-bold">Action after Done:</label>
                  <div class="d-flex gap-4">
                    <label class="form-check">
                      <input type="radio" v-model="form.done_action" value="CONVERT" class="form-check-input">
                      <span class="form-check-label">Convert to Customer</span>
                    </label>
                    <label class="form-check">
                      <input type="radio" v-model="form.done_action" value="FAILED" class="form-check-input">
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

                <div v-else-if="form.status === 'CANCELED'" class="p-3 border border-danger rounded bg-light">
                  <p class="mb-0 text-danger">
                    <strong>Note:</strong> Status Lead akan otomatis berubah menjadi <strong>Failed</strong> di database.
                  </p>
                </div>
              </transition>
            </div>

            <div class="col-lg-12">
              <label class="form-label">Notes <small class="text-success">(*ops*)</small></label>
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