<script setup>
import { ref, reactive, onMounted , watch} from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useLeadByAdminCreate } from '../../../../stores/leadsByAdminStore';
import { useMenuStore } from "@/stores/menuStore";
import { useAccessMenuStore } from "@/stores/accessMenuStore";
import Multiselect from "@vueform/multiselect"
import { toasts } from "@/utils/toasts"
import Swal from 'sweetalert2'
import { useRoute, useRouter } from "vue-router";

const PagesTitle = 'Data Master Leads';

const dataLeads = useLeadByAdminCreate();
const menuStore = useMenuStore();
const accessMenuStore = useAccessMenuStore();
const route = useRoute();
const router = useRouter();

const permission = ref(null);
const loadingPermission = ref(true);

const editLeadId = ref(null)

onMounted(async () => {
  try {
    if (!localStorage.getItem("auth_token")) {
      alert("Silakan login terlebih dahulu!");
      router.push('/login');
      return;
    }

    await dataLeads.fetchLeadsByAdmin(dataLeads.buildUrl());
    await menuStore.fetchMenus();

     await dataLeads.fetchLeadCategories()
     await dataLeads.fetchLeadIndustries()
     await dataLeads.fetchLeadUserSales()


    permission.value = menuStore.getPermission(route.path);
  } catch (error) {
    console.error(error);
  } finally {
    loadingPermission.value = false;
  }
});


watch(
  () => dataLeads.searchLead,
  dataLeads.searchWithDelay
);



const openDetail = async (id) => {
  try {
    await dataLeads.fetchLeadDetail(id)
  } catch (e) {
    console.error(e)
  }
}


// Single lead
const singleLead = ref({
  company_name: '',
  contact_name: '',
  email: '',
  phone: '',
  lead_source: '',
  lead_category_id: null,
  assigned_to: null,
  industry_id: null,
  visibility_type: null,
  address: '',
  notes: '',
})

// Bulk leads
const bulkLeads = ref([
  {
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    lead_source: '',
    lead_category_id: null,
    assigned_to: null,
    industry_id: null,
    visibility_type: null,
    address: '',
    notes:'',
  },
])

const addRow = () => {
  bulkLeads.value.push({
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    lead_source: '',
    lead_category_id: null,
    visibility_type: null,
    assigned_to: null,
    industry_id: null,
    address: '',
  })
}

const removeRow = (index) => {
  if (bulkLeads.value.length > 1) {
    bulkLeads.value.splice(index, 1)
  }
}



const openEditLead = async (lead) => {
  editLeadId.value = lead.id
  dataLeads.errorLead = null

  // 🔥 ambil detail lengkap dari API
  await dataLeads.fetchLeadDetail(lead.id)

  const detail = dataLeads.leadDetail

  singleLead.value = {
    company_name: detail.company_name,
    contact_name: detail.contact_name,
    email: detail.email,
    phone: detail.phone,
    assigned_to: detail.assigned_to,
    lead_source: detail.lead_source,
    industry_id: detail.industry_id,
    lead_category_id: detail.lead_category_id,
    visibility_type: detail.visibility_type,
    address: detail.address,  
    notes: detail.notes,     
  }

  
}


onMounted(() => {
  const modal = document.getElementById('modal-add-data')
  modal.addEventListener('hidden.bs.modal', () => {
    editLeadId.value = null
    dataLeads.errorLead = null
  })
})



const saveLead = async () => {
  if (dataLeads.savingLead) return

  try {
    const isEdit = !!editLeadId.value
    const isSingle = document.querySelector('#tab-single').classList.contains('active')

    if (isSingle) {
      if (isEdit) {
        await dataLeads.updateLead(editLeadId.value, singleLead.value)
      } else {
        await dataLeads.storeLead({
          ...singleLead.value,
        })
      }
    } else {
      await dataLeads.storeBulkLeads(
        bulkLeads.value.map(lead => ({
          ...lead,
        }))
      )
    }

    editLeadId.value = null
    dataLeads.errorLead = null

    const modalEl = document.getElementById('modal-add-data')
    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)

    // Close modal
    modalInstance.hide()

    // Reset form AFTER modal benar-benar tertutup
    modalEl.addEventListener('hidden.bs.modal', () => {
      // reset singleLead
      Object.assign(singleLead.value, {
        company_name: '',
        contact_name: '',
        email: '',
        phone: '',
        lead_source: '',
        lead_category_id: null,
        industry_id: null,
        visibility_type: null,
        assigned_to: null,
        address:'',
        notes: '',
      })

      // reset bulkLeads
      bulkLeads.value = [
        {
          __key: crypto.randomUUID(),
          company_name: '',
          contact_name: '',
          email: '',
          phone: '',
          lead_source: '',
          lead_category_id: null,
          industry_id: null,
          visibility_type: null,
          assigned_to: null,
          address:'',
          notes: '',
        }
      ]
    }, { once: true }) // event hanya sekali

    toasts.fire({
      icon: 'success',
      title: isEdit
        ? 'Lead successfully updated'
        : 'Lead successfully added',
    })

  } catch (err) {
    console.error(err)
     toasts.fire({
      icon: 'error',
      title: err.response?.data?.message || 'Failed to save leads',
    })
  }
}


const handleDeleteLead = async (lead) => {
  const confirm = await Swal.fire({
    title: 'Sure delete the Lead?',
    text: `Lead  will be permanently deleted`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancelled',
  })

  if (!confirm.isConfirmed) return

  try {
    await dataLeads.deleteLeads(lead.id)
  } catch (e) {
    console.error(e)
  }
}





// code export excel
const exportModalOpen = ref(false)
const exportType = ref('month') // 'month', 'date', 'year'
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const startDate = ref('')
const endDate = ref('')

const years = ref([])
const generateYears = () => {
  const currentYear = new Date().getFullYear();
  for (let i = currentYear; i >= 2000; i--) {
    years.value.push(i);
  }
}

const openExportModal = () => {
     generateYears();
    exportModalOpen.value = true
}

// // code export pdf
const exportModalOpenPdf = ref(false)
const exportTypePdf = ref('month') // 'month', 'date', 'year'
const selectedMonthPdf = ref(new Date().getMonth() + 1)
const selectedYearPdf = ref(new Date().getFullYear())
const startDatePdf = ref('')
const endDatePdf = ref('')

const yearsPdf = ref([])
const generateYearsPdf = () => {
  yearsPdf.value = []
  const currentYear = new Date().getFullYear()
  for (let i = currentYear; i >= 2000; i--) {
    yearsPdf.value.push(i)
  }
}

const openExportModalPdf = () => {
  generateYearsPdf()
  exportModalOpenPdf.value = true
}

// import csv
const importCsvModalOpen = ref(false)
const selectedCsvFile = ref(null)

const openImportCsvModal = () => {
  importCsvModalOpen.value = true
}

// Event ketika file dipilih
const handleCsvFile = (event) => {
  selectedCsvFile.value = event.target.files[0]
}

// Tombol upload (sementara hanya alert)
const handleImportCsv = () => {
  if (!selectedCsvFile.value) {
    alert("Silakan pilih file CSV terlebih dahulu")
    return
  }
  alert(`Mengupload file: ${selectedCsvFile.value.name}`)
  importCsvModalOpen.value = false
}


// import excel
const importExcelModalOpen = ref(false)
const selectedExcelFile = ref(null)

const openImportExcelModal = () => {
  importExcelModalOpen.value = true
}

// Event ketika file dipilih
const handleExcelFile = (event) => {
  selectedExcelFile.value = event.target.files[0]
}

// Tombol upload (sementara hanya alert)
const handleImportExcel = () => {
  if (!selectedExcelFile.value) {
    alert("Silakan pilih file Excel terlebih dahulu")
    return
  }
  alert(`Mengupload file: ${selectedExcelFile.value.name}`)
  importExcelModalOpen.value = false
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


       <!-- LOADING PERMISSION -->
        <div v-if="loadingPermission" class="text-center py-5">
          <div class="spinner-border text-primary"></div>
          <p class="text-muted mt-2">Loading access rights...</p>
        </div>

        <!-- NO ACCESS -->
        <div
          v-else-if="!permission?.can_view"
          class="text-center py-5"
        >
          <i class="fa fa-lock fa-2x text-muted mb-2"></i>
          <p class="fw-semibold text-muted">
            You don't have access to view the data
          </p>
        </div>



      <!-- Page Body -->
      <div  v-else class="page-body flex-grow-1">
        <div class="container-xl">

          <!-- Card: Export/Import -->
         <div class="card mb-4">
          <div class="card-header d-flex gap-2 flex-wrap align-items-center">
            
            <!-- Tombol kiri -->
            <div class="d-flex gap-2 flex-wrap">
        
            <div class="dropdown d-inline-block me-2">
                    <button
                        class="btn btn-primary btn-sm dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <i class="fa-solid fa-upload"></i> Export
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                        <button class="dropdown-item" @click="openExportModalPdf">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        </li>
                        <li>
                        <button class="dropdown-item" @click="openExportModal">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        </li>
                      
                    </ul>
            </div>

            <div class="dropdown d-inline-block">
                <button
                    class="btn btn-primary btn-sm dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <i class="fa fa-download"></i> Import
                </button>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                    <button class="dropdown-item" @click="openImportCsvModal">
                        <i class="fas fa-file-import"></i> Import CSV
                    </button>
                    </li>
                     <li>
                    <button class="dropdown-item" @click="openImportExcelModal">
                        <i class="fas fa-file-import"></i> Import Excel
                    </button>
                    </li>
                </ul>
            </div>

    </div>

        <!-- Tombol Reset paling kanan -->
        <button class="btn btn-warning btn-sm d-flex align-items-center ms-auto" @click="dataLeads.resetFilters">
          <i class="fas fa-undo"></i> Reset
        </button>

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
                     v-model.number="dataLeads.pagination.per_page" 
                     @change="dataLeads.changePageSize">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                    </select>
                </div>

                <!-- Tombol add -->
                 <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-add-data">
                    <i class="fa fa-plus"></i> Add Data
                    </button>
                </div>


              <!-- Kanan -->
             <div class="d-flex flex-column gap-3 align-items-end" style="min-width:300px;">
                <!-- Pencarian -->
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Searching...."  v-model="dataLeads.searchLead">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" v-model="dataLeads.sort.column" @change="dataLeads.changeSorting">
                    <option value="fullname">By Name</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto" v-model="dataLeads.sort.direction" @change="dataLeads.changeSorting">
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
                    <th>Company Name</th>
                    <th>Contact Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Created By</th>
                    <th>Assign To Sales</th>
                    <th style="width: 8%;">Actions</th>
                  </tr>
                </thead>

                 <tbody v-if="dataLeads.loadingLead">
                <tr>
                  <td colspan="8" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>
              </tbody>

              <!-- EMPTY DATA -->
              <tbody v-else-if="dataLeads.LeadData.length === 0">
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
                    <tr
                      v-for="(led, index) in dataLeads.LeadData"
                      :key="led.id"
                    >
                      <td>
                        {{
                          index + 1 +
                          dataLeads.pagination.per_page *
                            (dataLeads.pagination.current_page - 1)
                        }}.
                      </td>
                      <td>{{ led.company_name }}</td>
                      <td>{{ led.contact_name }}</td>
                      <td>{{ led.phone }}</td>
                      <td>{{ led.email }}</td>
                      <td>{{ led.owner_name }}</td>
                      <td>{{ led.assigned_name }}</td>

                      <td>

                         <!-- DETAIL -->
                       <button
                          class="btn btn-outline-primary btn-sm me-1"
                          data-bs-toggle="modal"
                          data-bs-target="#leadDetailModal"
                          @click="openDetail(led.id)"
                          >
                          <i class="fa fa-circle-info"></i>
                        </button>

                        <!-- UPDATE -->
                        <button
                         v-if="!loadingPermission && permission?.can_update"
                          class="btn btn-outline-warning btn-sm me-1"
                          data-bs-toggle="modal"
                          data-bs-target="#modal-add-data"
                            @click="openEditLead(led)"
                        >
                          <i class="fa fa-edit"></i>
                        </button>

                        <!-- DELETE -->
                        <button
                           v-if="!loadingPermission && permission?.can_delete"
                          class="btn btn-outline-danger btn-sm me-1"
                          @click="handleDeleteLead(led)"
                        >
                          <i class="fa fa-trash"></i>
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
               :disabled="!dataLeads.pagination.prev_page_url || dataLeads.loadingLeads"
                  @click="dataLeads.fetchLeadsByAdmin(dataLeads.pagination.prev_page_url)"
                 >
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                      <span class="badge border text-secondary px-3 py-2"> {{ dataLeads.LeadData.length }} data | on page {{ dataLeads.pagination.current_page }}</span>
                    <span class="badge border text-secondary px-3 py-2">Total: {{ dataLeads.pagination.total }}</span>
                </div>
  
                <button class="btn btn-danger btn-sm"
                :disabled="!dataLeads.pagination.next_page_url || dataLeads.loadingLeads"
                  @click="dataLeads.fetchLeadsByAdmin(dataLeads.pagination.next_page_url)"
               >
                    Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>
        </div>
      </div>
    </div>


    <!-- Code Modal: Detail Data -->
   <div class="modal fade" id="leadDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">Detail Menu</h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"
        ></button>
      </div>

      <!-- Body -->
      <div class="modal-body">


           <!-- LOADING -->
                  <div
                    v-if="dataLeads.loadingDetail"
                    class="d-flex justify-content-center align-items-center"
                    style="min-height:150px"
                  >
                    <div class="spinner-border text-secondary"></div>
                  </div>

                  <!-- DATA -->
                  <div v-else-if="dataLeads.leadDetail">
                    <p><strong>Company:</strong> {{ dataLeads.leadDetail.company_name }}</p>
                    <p><strong>Contact:</strong> {{ dataLeads.leadDetail.contact_name }}</p>
                    <p><strong>Email:</strong> {{ dataLeads.leadDetail.email || '-' }}</p>
                    <p><strong>Phone:</strong> {{ dataLeads.leadDetail.phone || '-' }}</p>
                    <p><strong>Industry:</strong> {{ dataLeads.leadDetail.industry_name || '-' }}</p>
                    <p><strong>Category:</strong> {{ dataLeads.leadDetail.category_name || '-' }}</p>
                    <p><strong>Status:</strong>
                      <span class="badge bg-warning">
                        {{ dataLeads.leadDetail.lead_status }}
                      </span>
                    </p>
                    <p><strong>Source:</strong> {{ dataLeads.leadDetail.lead_source }}</p>

                      <div class="mb-3">
                      <label class="form-label fw-semibold">Notes</label>
                      <textarea
                        class="form-control"
                        rows="3"
                        readonly
                      >{{ dataLeads.leadDetail.notes || '-' }}</textarea>
                    </div>

                    <div class="mb-3">
                      <label class="form-label fw-semibold">Address</label>
                      <textarea
                        class="form-control"
                        rows="3"
                        readonly
                      >{{ dataLeads.leadDetail.address || '-' }}</textarea>
                    </div>
                  </div>

                  <!-- EMPTY -->
                  <div v-else class="text-center text-muted">
                    Data tidak tersedia
                  </div>

                </div>

       
      <!-- Footer -->
      <div class="modal-footer">
        <button
          type="button"
          class="btn btn-outline-secondary"
          data-bs-dismiss="modal"
        >
          Close
        </button>
      </div>

    </div>
  </div>
</div>





    <!-- Code Modal: Add Data -->
 <!-- Modal: Add Lead (Single & Bulk) -->
          <div class="modal modal-blur fade" id="modal-add-data" tabindex="-1">
              <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">

                  <!-- Header -->
                  <div class="modal-header mb-2">
                    <h5 class="modal-title">  {{ editLeadId ? 'Edit Lead' : 'Add Lead data master' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>

                
                  <div class="border-bottom" v-if="!editLeadId">
                    <ul class="nav nav-tabs nav-tabs-bordered px-3" role="tablist">
                      <li class="nav-item">
                        <button
                          class="nav-link active text-dark"
                          data-bs-toggle="tab"
                          data-bs-target="#tab-single"
                          type="button"
                          @click="activeTab = 'single'"
                        >
                          Add Data Single
                        </button>
                      </li>
                      <li class="nav-item">
                        <button
                          class="nav-link"
                          data-bs-toggle="tab"
                          data-bs-target="#tab-bulk"
                          type="button"
                          @click="activeTab = 'bulk'"
                        >
                          Add Data Bulk
                        </button>
                      </li>
                    </ul>
                  </div>


                  <!-- Body -->
                  <div class="modal-body">

                    <div class="tab-content">

                      <!-- ================= SINGLE INPUT ================= -->
                      <div class="tab-pane fade show active" id="tab-single">
                        <form id="form-add-lead-single">
                          <div class="row">
                            <div class="col-lg-6 mb-3">
                              <label class="form-label">Company or Store Name <small class="text-danger">**</small></label>

                              <input
                                type="text"
                                class="form-control"
                                placeholder="Enter company or store name"
                                v-model="singleLead.company_name"
                                :class="{ 'is-invalid': dataLeads.errorLead?.company_name }"
                                @input="() => {
                                  if (dataLeads.errorLead?.company_name) {
                                    dataLeads.errorLead.company_name = null
                                  }
                                }"
                              />

                              <div
                                v-if="dataLeads.errorLead?.company_name"
                                class="invalid-feedback"
                              >
                                {{ dataLeads.errorLead.company_name[0] }}
                              </div>
                            </div>


                            <div class="col-lg-6 mb-3">
                            <label class="form-label">Contact Name <small class="text-danger">**</small></label>

                            <input
                              type="text"
                              class="form-control"
                              placeholder="Enter contact name"
                              v-model="singleLead.contact_name"
                              :class="{ 'is-invalid': dataLeads.errorLead?.contact_name }"
                              @input="() => {
                                if (dataLeads.errorLead?.contact_name) {
                                  dataLeads.errorLead.contact_name = null
                                }
                              }"
                            />

                            <div
                              v-if="dataLeads.errorLead?.contact_name"
                              class="invalid-feedback"
                            >
                              {{ dataLeads.errorLead.contact_name[0] }}
                            </div>
                          </div>


                          <div class="col-lg-6 mb-3">
                            <label class="form-label">Email <small class="text-danger">**</small></label>
                            <input
                              type="email"
                              class="form-control"
                              placeholder="Enter email"
                              v-model="singleLead.email"
                              :class="{ 'is-invalid': dataLeads.errorLead?.email }"
                              @input="() => {
                                if (dataLeads.errorLead?.email) {
                                  dataLeads.errorLead.email = null
                                }
                              }"
                            />

                            <div
                              v-if="dataLeads.errorLead?.email"
                              class="invalid-feedback"
                            >
                              {{ dataLeads.errorLead.email[0] }}
                            </div>
                          </div>


                          <div class="col-lg-6 mb-3">
                          <label class="form-label">Phone <small class="text-danger">**</small></label>
                          <input
                            type="text"
                            class="form-control"
                            placeholder="Enter phone number"
                            v-model="singleLead.phone"
                            :class="{ 'is-invalid': dataLeads.errorLead?.phone }"
                            @input="() => {
                              if (dataLeads.errorLead?.phone) {
                                dataLeads.errorLead.phone = null
                              }
                            }"
                          />

                          <div
                            v-if="dataLeads.errorLead?.phone"
                            class="invalid-feedback"
                          >
                            {{ dataLeads.errorLead.phone[0] }}
                          </div>
                        </div>


                          <div class="col-lg-6 mb-3">
                            <label class="form-label">Lead Source <small class="text-danger">**</small></label>
                            <div
                              class="multiselect-wrapper"
                              :class="{ 'is-invalid': dataLeads.errorLead?.lead_source }"
                            >
                              <Multiselect
                                v-model="singleLead.lead_source"
                                :options="dataLeads.leadSource"
                                label="label"
                                valueProp="value"
                                placeholder="Select Lead Source"
                                @update:modelValue="() => {
                                  if (dataLeads.errorLead?.lead_source) {
                                    dataLeads.errorLead.lead_source = null
                                  }
                                }"
                              />
                            </div>
                            <div
                              v-if="dataLeads.errorLead?.lead_source"
                              class="invalid-feedback d-block"
                            >
                              {{ dataLeads.errorLead.lead_source[0] }}
                            </div>
                          </div>


                            <div class="col-lg-6 mb-3">
                            <label class="form-label">Industry <small class="text-danger">**</small></label>

                            <div
                              class="multiselect-wrapper"
                              :class="{ 'is-invalid': dataLeads.errorLead?.industry_id }"
                            >
                              <Multiselect
                                v-model="singleLead.industry_id"
                                :options="dataLeads.industries"
                                label="name"
                                valueProp="id"
                                placeholder="Select Industry"
                                :searchable="true"
                                :loading="dataLeads.loadingIndustries"
                                @update:modelValue="() => {
                                  if (dataLeads.errorLead?.industry_id) {
                                    dataLeads.errorLead.industry_id = null
                                  }
                                }"
                              />
                            </div>
                            <div
                              v-if="dataLeads.errorLead?.industry_id"
                              class="invalid-feedback d-block"
                            >
                              {{ dataLeads.errorLead.industry_id[0] }}
                            </div>
                          </div>



                            <div class="col-lg-6 mb-3">
                            <label class="form-label">Category <small class="text-danger">**</small></label>
                            <div
                              class="multiselect-wrapper"
                              :class="{ 'is-invalid': dataLeads.errorLead?.lead_category_id }"
                            >
                              <Multiselect
                                v-model="singleLead.lead_category_id"
                                :options="dataLeads.categories"
                                label="name"
                                valueProp="id"
                                placeholder="Select Category"
                                :searchable="true"
                                :loading="dataLeads.loadingCategories"
                                :close-on-select="true"
                                @update:modelValue="() => {
                                  if (dataLeads.errorLead?.lead_category_id) {
                                    dataLeads.errorLead.lead_category_id = null
                                  }
                                }"
                              />
                            </div>
                            <div
                              v-if="dataLeads.errorLead?.lead_category_id"
                              class="invalid-feedback d-block"
                            >
                              {{ dataLeads.errorLead.lead_category_id[0] }}
                            </div>
                          </div>


                          <div class="col-lg-6 mb-3">
                            <label class="form-label">Assign To Sales <small class="text-danger">**</small></label>
                            <div
                              class="multiselect-wrapper"
                              :class="{ 'is-invalid': dataLeads.errorLead?.assigned_to}"
                            >
                              <Multiselect
                                v-model="singleLead.assigned_to"
                                :options="dataLeads.userSales"
                                label="name"
                                valueProp="id_user"
                                placeholder="Select Assign To Sales"
                                :searchable="true"
                                :loading="dataLeads.loadingUserSales"
                                @update:modelValue="() => {
                                  if (dataLeads.errorLead?.assigned_to) {
                                    dataLeads.errorLead.assigned_to = null
                                  }
                                }"
                              />
                            </div>
                            <div
                              v-if="dataLeads.errorLead?.assigned_to"
                              class="invalid-feedback d-block"
                            >
                              {{ dataLeads.errorLead.assigned_to[0] }}
                            </div>
                          </div>


                           <div class="col-lg-6 mb-3">
                            <label class="form-label">Visibility Type <small class="text-danger">**</small></label>
                            <div
                              class="multiselect-wrapper"
                              :class="{ 'is-invalid': dataLeads.errorLead?.visibility_type }"
                            >
                              <Multiselect
                                v-model="singleLead.visibility_type"
                                :options="dataLeads.visibilityType"
                                label="label"
                                valueProp="value"
                                placeholder="Select Visibility Type"
                                @update:modelValue="() => {
                                  if (dataLeads.errorLead?.visibility_type) {
                                    dataLeads.errorLead.visibility_type = null
                                  }
                                }"
                              />
                            </div>
                            <div
                              v-if="dataLeads.errorLead?.visibility_type"
                              class="invalid-feedback d-block"
                            >
                              {{ dataLeads.errorLead.visibility_type[0] }}
                            </div>
                          </div>


                          <div class="col-lg-12 mb-3">
                              <label class="form-label">Address</label>

                              <textarea
                                class="form-control"
                                placeholder="Enter address"
                                v-model="singleLead.address"
                                rows="3"
                                :class="{ 'is-invalid': dataLeads.errorLead?.address }"
                                @input="() => {
                                  if (dataLeads.errorLead?.address) {
                                    dataLeads.errorLead.address = null
                                  }
                                }"
                              ></textarea>
                              <div
                                v-if="dataLeads.errorLead?.address"
                                class="invalid-feedback"
                              >
                                {{ dataLeads.errorLead.address[0] }}
                              </div>
                              </div>
                          


                            <div class="col-lg-12 mb-3">
                              <label class="form-label">Notes</label>

                              <textarea
                                class="form-control"
                                placeholder="Enter notes"
                                v-model="singleLead.notes"
                                rows="3"
                                :class="{ 'is-invalid': dataLeads.errorLead?.notes }"
                                @input="() => {
                                  if (dataLeads.errorLead?.notes) {
                                    dataLeads.errorLead.notes = null
                                  }
                                }"
                              ></textarea>
                              <div
                                v-if="dataLeads.errorLead?.notes"
                                class="invalid-feedback"
                              >
                                {{ dataLeads.errorLead.notes[0] }}
                              </div>
                            </div>
                          </div>
                        </form>
                      </div>

                      <!-- ================= BULK INPUT ================= -->
                      <div class="tab-pane fade" id="tab-bulk">

                        <!-- Toolbar -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <button class="btn btn-primary" @click="addRow">
                            <i class="fas fa-plus me-1"></i> Add Row
                          </button>
                          <small class="text-muted">Input multiple leads at once</small>
                        </div>

                        <!-- Scroll Area -->
                        <div style="max-height: 55vh; overflow-y: auto;">

                          <div
                            v-for="(lead, index) in bulkLeads"
                            :key="lead.__key"
                            class="card shadow-sm mb-3"
                          >

                            <!-- Card Header -->
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                              <strong class="text-primary text-decoration-underline"">Lead #{{ index + 1 }}</strong>
                              <button
                                class="btn btn-sm btn-outline-danger"
                                @click="removeRow(index)"
                              >
                                <i class="fas fa-trash"></i>
                              </button>
                            </div>

                            <!-- Card Body -->
                            <div class="card-body">
                              <div class="row g-3">

                                <!-- Company -->
                                <div class="col-md-4">
                                  <label class="form-label">Company Or Store Name <small class="text-danger">**</small></label>
                                  <input
                                    type="text"
                                    class="form-control"
                                    v-model="lead.company_name"
                                    :class="{ 'is-invalid': dataLeads.errorLead?.[`leads.${index}.company_name`] }"
                                    @input="delete dataLeads.errorLead?.[`leads.${index}.company_name`]"
                                  />
                                  <div class="invalid-feedback">
                                    {{ dataLeads.errorLead?.[`leads.${index}.company_name`]?.[0] }}
                                  </div>
                                </div>

                                <!-- Contact -->
                                <div class="col-md-4">
                                  <label class="form-label">Contact Name</label>
                                  <input
                                    type="text"
                                    class="form-control"
                                    v-model="lead.contact_name"
                                    :class="{ 'is-invalid': dataLeads.errorLead?.[`leads.${index}.contact_name`] }"
                                    @input="delete dataLeads.errorLead?.[`leads.${index}.contact_name`]"
                                  />
                                  <div class="invalid-feedback">
                                    {{ dataLeads.errorLead?.[`leads.${index}.contact_name`]?.[0] }}
                                  </div>
                                </div>

                                <!-- Email -->
                                <div class="col-md-4">
                                  <label class="form-label">Email <small class="text-danger">**</small></label>
                                  <input
                                    type="email"
                                    class="form-control"
                                    v-model="lead.email"
                                    :class="{ 'is-invalid': dataLeads.errorLead?.[`leads.${index}.email`] }"
                                    @input="delete dataLeads.errorLead?.[`leads.${index}.email`]"
                                  />
                                  <div class="invalid-feedback">
                                    {{ dataLeads.errorLead?.[`leads.${index}.email`]?.[0] }}
                                  </div>
                                </div>

                                <!-- Phone -->
                                <div class="col-md-3">
                                  <label class="form-label">Phone <small class="text-danger">**</small></label>
                                  <input
                                    type="text"
                                    class="form-control"
                                    v-model="lead.phone"
                                    :class="{ 'is-invalid': dataLeads.errorLead?.[`leads.${index}.phone`] }"
                                    @input="delete dataLeads.errorLead?.[`leads.${index}.phone`]"
                                  />
                                  <div class="invalid-feedback">
                                    {{ dataLeads.errorLead?.[`leads.${index}.phone`]?.[0] }}
                                  </div>
                                </div>

                                <!-- Lead Source -->
                                <div class="col-md-3">
                                  <label class="form-label">Lead Source <small class="text-danger">**</small></label>
                                  <div
                                    class="multiselect-wrapper"
                                    :class="{ 'is-invalid': dataLeads.errorLead?.[`leads.${index}.lead_source`] }"
                                  >
                                    <Multiselect
                                      v-model="lead.lead_source"
                                      :options="dataLeads.leadSource"
                                      label="label"
                                      valueProp="value"
                                      placeholder="Select Lead Source"
                                      @update:modelValue="delete dataLeads.errorLead?.[`leads.${index}.lead_source`]"
                                    />
                                  </div>
                                  <small class="text-danger">
                                    {{ dataLeads.errorLead?.[`leads.${index}.lead_source`]?.[0] }}
                                  </small>
                                </div>

                                <!-- Industry -->
                                <div class="col-md-3">
                                  <label class="form-label">Industry <small class="text-danger">**</small></label>
                                  <div
                                    class="multiselect-wrapper"
                                    :class="{ 'is-invalid': dataLeads.errorLead?.[`leads.${index}.industry_id`] }"
                                  >
                                    <Multiselect
                                      v-model="lead.industry_id"
                                      :options="dataLeads.industries"
                                      label="name"
                                      valueProp="id"
                                      searchable
                                      :loading="dataLeads.loadingIndustries"
                                      placeholder="Select Industry"
                                      @update:modelValue="delete dataLeads.errorLead?.[`leads.${index}.industry_id`]"
                                    />
                                  </div>
                                  <small class="text-danger">
                                    {{ dataLeads.errorLead?.[`leads.${index}.industry_id`]?.[0] }}
                                  </small>
                                </div>

                                <!-- Category -->
                                <div class="col-md-3">
                                  <label class="form-label">Category <small class="text-danger">**</small></label>
                                  <div
                                    class="multiselect-wrapper"
                                    :class="{ 'is-invalid': dataLeads.errorLead?.[`leads.${index}.lead_category_id`] }"
                                  >
                                    <Multiselect
                                      v-model="lead.lead_category_id"
                                      :options="dataLeads.categories"
                                      label="name"
                                      valueProp="id"
                                      searchable
                                      :loading="dataLeads.loadingCategories"
                                      placeholder="Select Category"
                                      @update:modelValue="delete dataLeads.errorLead?.[`leads.${index}.lead_category_id`]"
                                    />
                                  </div>
                                  <small class="text-danger">
                                    {{ dataLeads.errorLead?.[`leads.${index}.lead_category_id`]?.[0] }}
                                  </small>
                                </div>




                            <div class="col-lg-6 mb-3">
                            <label class="form-label">Visibility Type <small class="text-danger">**</small></label>
                            <div
                              class="multiselect-wrapper"
                              :class="{ 'is-invalid': dataLeads.errorLead?.visibility_type }"
                            >
                              <Multiselect
                                v-model="lead.visibility_type"
                                :options="dataLeads.visibilityType"
                                label="label"
                                valueProp="value"
                                placeholder="Select Visibility Type"
                                @update:modelValue="() => {
                                  if (dataLeads.errorLead?.visibility_type) {
                                    dataLeads.errorLead.visibility_type = null
                                  }
                                }"
                              />
                            </div>
                            <div
                              v-if="dataLeads.errorLead?.visibility_type"
                              class="invalid-feedback d-block"
                            >
                              {{ dataLeads.errorLead.visibility_type[0] }}
                            </div>
                          </div>


                          <div class="col-lg-6 mb-3">
                            <label class="form-label">Assign To Sales <small class="text-danger">**</small></label>
                            <div
                              class="multiselect-wrapper"
                              :class="{ 'is-invalid': dataLeads.errorLead?.assigned_to}"
                            >
                              <Multiselect
                                v-model="lead.assigned_to"
                                :options="dataLeads.userSales"
                                label="name"
                                valueProp="id_user"
                                placeholder="Select Assign To Sales"
                                :searchable="true"
                                :loading="dataLeads.loadingUserSales"
                                @update:modelValue="() => {
                                  if (dataLeads.errorLead?.assigned_to) {
                                    dataLeads.errorLead.assigned_to = null
                                  }
                                }"
                              />
                            </div>
                            <div
                              v-if="dataLeads.errorLead?.assigned_to"
                              class="invalid-feedback d-block"
                            >
                              {{ dataLeads.errorLead.assigned_to[0] }}
                            </div>
                          </div>

                            <!-- Notes -->
                                <div class="col-12">
                                  <label class="form-label">Address</label>
                                  <textarea
                                    class="form-control"
                                    rows="2"
                                    v-model="lead.address"
                                  ></textarea>
                            </div>

                                <!-- Notes -->
                            <div class="col-12">
                                  <label class="form-label">Notes</label>
                                  <textarea
                                    class="form-control"
                                    rows="2"
                                    v-model="lead.notes"
                                  ></textarea>
                            </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                
                  <div class="modal-footer">
                    <button
                      class="btn btn-link"
                      data-bs-dismiss="modal"
                      :disabled="dataLeads.savingLead"
                    >
                      Cancel
                    </button>

                    <button
                      class="btn btn-primary"
                      :disabled="dataLeads.savingLead"
                      @click="saveLead"
                    >
                      <span v-if="dataLeads.savingLead">
                        <i class="fas fa-spinner fa-spin me-1"></i>
                        Saving...
                      </span>
                      <span v-else>
                        <i class="fas fa-save me-1"></i>
                        Save
                      </span>
                    </button>
                  </div>
                </div>
              </div>
            </div>




    <!-- ### Modal Export Laporan --> 
    <div class="modal fade" id="exportExcelModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Ekspor Laporan Invoice (excel)</h5>
            <button type="button" class="btn-close" @click="exportModalOpen=false"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Pilih Tipe Ekspor</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" v-model="exportType" value="month" id="exportByMonth">
                  <label class="form-check-label" for="exportByMonth">Bulan</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" v-model="exportType" value="date" id="exportByDate">
                  <label class="form-check-label" for="exportByDate">Tanggal</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" v-model="exportType" value="year" id="exportByYear">
                  <label class="form-check-label" for="exportByYear">Tahun</label>
                </div>
              </div>
            </div>

            <div v-if="exportType === 'month'" class="row g-2 mb-3">
              <div class="col">
                <label class="form-label">Bulan</label>
                <select v-model="selectedMonth" class="form-select">
                  <option value="1">Januari</option>
                  <option value="2">Februari</option>
                  <option value="3">Maret</option>
                  <option value="4">April</option>
                  <option value="5">Mei</option>
                  <option value="6">Juni</option>
                  <option value="7">Juli</option>
                  <option value="8">Agustus</option>
                  <option value="9">September</option>
                  <option value="10">Oktober</option>
                  <option value="11">November</option>
                  <option value="12">Desember</option>
                </select>
              </div>
              <div class="col">
                <label class="form-label">Tahun</label>
                <select v-model="selectedYear" class="form-select">
                  <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
            </div>

            <div v-if="exportType === 'date'" class="row g-2 mb-3">
              <div class="col">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" v-model="startDate" class="form-control" />
              </div>
              <div class="col">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" v-model="endDate" class="form-control" />
              </div>
            </div>
            
            <div v-if="exportType === 'year'" class="mb-3">
              <label class="form-label">Pilih Tahun</label>
              <select v-model="selectedYear" class="form-select">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>

          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="exportModalOpen=false">Batal</button>
            <button class="btn btn-primary" @click="handleExport">Ekspor</button>
          </div>
        </div>
      </div>
    </div>





    <!-- ### Modal Export Laporan PDF --> 
    <div class="modal fade" id="leadDetailModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Ekspor Laporan Invoice (PDF)</h5>
            <button type="button" class="btn-close" @click="exportModalOpenPdf=false"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Pilih Tipe Ekspor</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" v-model="exportTypePdf" value="month" id="exportByMonthPdf">
                  <label class="form-check-label" for="exportByMonthPdf">Bulan</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" v-model="exportTypePdf" value="date" id="exportByDatePdf">
                  <label class="form-check-label" for="exportByDatePdf">Tanggal</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" v-model="exportTypePdf" value="year" id="exportByYearPdf">
                  <label class="form-check-label" for="exportByYearPdf">Tahun</label>
                </div>
              </div>
            </div>

            <!-- Filter Bulan -->
            <div v-if="exportTypePdf === 'month'" class="row g-2 mb-3">
              <div class="col">
                <label class="form-label">Bulan</label>
                <select v-model="selectedMonthPdf" class="form-select">
                  <option value="1">Januari</option>
                  <option value="2">Februari</option>
                  <option value="3">Maret</option>
                  <option value="4">April</option>
                  <option value="5">Mei</option>
                  <option value="6">Juni</option>
                  <option value="7">Juli</option>
                  <option value="8">Agustus</option>
                  <option value="9">September</option>
                  <option value="10">Oktober</option>
                  <option value="11">November</option>
                  <option value="12">Desember</option>
                </select>
              </div>
              <div class="col">
                <label class="form-label">Tahun</label>
                <select v-model="selectedYearPdf" class="form-select">
                  <option v-for="y in yearsPdf" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
            </div>

            <!-- Filter Tanggal -->
            <div v-if="exportTypePdf === 'date'" class="row g-2 mb-3">
              <div class="col">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" v-model="startDatePdf" class="form-control" />
              </div>
              <div class="col">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" v-model="endDatePdf" class="form-control" />
              </div>
            </div>

            <!-- Filter Tahun -->
            <div v-if="exportTypePdf === 'year'" class="mb-3">
              <label class="form-label">Pilih Tahun</label>
              <select v-model="selectedYearPdf" class="form-select">
                <option v-for="y in yearsPdf" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>

            <div class="alert alert-info">
              Klik tombol "Ekspor" untuk mendownload laporan dalam format PDF.
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" @click="exportModalOpenPdf=false">Batal</button>
            <button class="btn btn-danger" @click="handleExportPdf">Ekspor PDF</button>
          </div>
        </div>
      </div>
    </div>


<!-- ### Modal Import CSV --> 

 <div class="modal fade" id="leadDetailModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Data CSV</h5>
        <button type="button" class="btn-close" @click="importCsvModalOpen=false"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Pilih File CSV</label>
          <input type="file" class="form-control" accept=".csv" @change="handleCsvFile" />
        </div>

        <div class="alert alert-info">
          Pastikan format CSV sesuai template.
          <a href="/template.csv" target="_blank">Download Template CSV</a>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" @click="importCsvModalOpen=false">Batal</button>
        <button class="btn btn-primary" @click="handleImportCsv">Upload CSV</button>
      </div>
    </div>
  </div>
</div>



<!-- ### Modal Import Excel --> 

 <div class="modal fade" id="leadDetailModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Data Excel</h5>
        <button type="button" class="btn-close" @click="importExcelModalOpen=false"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Pilih File Excel</label>
          <input type="file" class="form-control" accept=".xlsx,.xls" @change="handleExcelFile" />
        </div>

        <div class="alert alert-info">
          Pastikan format kolom sesuai template.
          <a href="/template.xlsx" target="_blank">Download Template Excel</a>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" @click="importExcelModalOpen=false">Batal</button>
        <button class="btn btn-primary" @click="handleImportExcel">Upload Excel</button>
      </div>
    </div>
  </div>
</div>

  </backendLayouts>
</template>




<style scoped>
/* Tab aktif "nyala" */
.nav-tabs .nav-link.active {
    color: #fff; /* teks putih */
    background-color: var(--bs-primary); /* warna primary Bootstrap */
    border-color: var(--bs-primary) var(--bs-primary) #dee2e6; /* border atas & samping */
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2); /* efek nyala */
    transition: all 0.2s ease-in-out;
}

/* Tab nonaktif tetap netral */
.nav-tabs .nav-link {
    color: var(--bs-body-color);
    background-color: transparent;
    border-color: transparent;
}


.multiselect-wrapper.is-invalid .multiselect {
  border-color: #dc3545;
}

.multiselect-wrapper.is-invalid .multiselect:focus-within {
  box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}


</style>