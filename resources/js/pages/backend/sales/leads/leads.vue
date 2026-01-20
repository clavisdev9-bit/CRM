<script setup>
import { ref, onMounted, computed  } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue"
import { useLeadsStore } from '../../../../stores/leadsStore'
import { useMenuStore } from "@/stores/menuStore"
import { useRoute, useRouter } from "vue-router"
import Multiselect from "@vueform/multiselect"
import { toasts } from "@/utils/toasts"
import Swal from 'sweetalert2'
const PagesTitle = 'Data Leads'

const dataLeads = useLeadsStore()
const menuStore = useMenuStore()
const route = useRoute()
const router = useRouter()

const permission = ref(null)
const loadingPermission = ref(true)

const editLeadId = ref(null)


onMounted(async () => {
  try {
  if (!localStorage.getItem("auth_token")) {
  // router.push('/login')
  const result = await Swal.fire({
    icon: 'warning',
    title: 'Not logged in yet',
    text: 'You must be logged in to access this page.',
    showCancelButton: true,
    confirmButtonText: 'Login',
    cancelButtonText: 'Cancel'
  })
  if (result.isConfirmed) {
    router.push('/login')
  } else {
    router.push('/')
  }
  return
}
    //  DEFAULT LOAD MASTER LEADS
    await dataLeads.fetchLeads("all")

    await menuStore.fetchMenus()
    permission.value = menuStore.getPermission(route.path)

     await dataLeads.fetchLeadCategories()
     await dataLeads.fetchLeadIndustries()
  

  } catch (error) {
    console.error(error)
  } finally {
    loadingPermission.value = false
  }
})


const notFoundType = computed(() => {
  if (dataLeads.mode === 'assigned') {
    return 'assigned'
  }

  if (dataLeads.searchLeads?.length > 0) {
    return 'search'
  }

  return 'empty'
})

const notFoundConfig = {
  assigned: {
    image: 'https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif',
    title: 'No Leads Assigned Yet (Belum Ada Assigned Leads)',
    message: 'There are currently no leads assigned to you (Saat ini belum ada leads yang di-assign ke Anda)'
  },
  search: {
    image: 'https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif',
    title: 'Data Not Found (Data Tidak Ditemukan)',
    message: 'Try changing your search keywords. (Coba ubah kata kunci pencarian Anda.)'
  },
  empty: {
    image: 'https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif',
    title: 'No Leads Data Yet (Belum Ada Data Leads)',
    message: 'Please add new leads data. (Silakan tambahkan data leads baru.)'
  }
}

const formatDate = (value) => {
  if (!value) return '-'

  const date = new Date(value)

  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}


const openDetail = async (id) => {
  await dataLeads.fetchLeadDetail(id)

  const modal = new bootstrap.Modal(
    document.getElementById("userDetailModal")
  )
  modal.show()
}




// Single lead
const singleLead = ref({
  company_name: '',
  contact_name: '',
  email: '',
  phone: '',
  lead_source: '',
  lead_category_id: null,
  industry_id: null,
  notes: '',
  address: '',
 
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
    industry_id: null,
    notes: '',
    address: '',
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
    industry_id: null,
    notes: '',
    address: '',
  })
}

const removeRow = (index) => {
  if (bulkLeads.value.length > 1) {
    bulkLeads.value.splice(index, 1)
  }
}


const openEditLead = (lead) => {
  editLeadId.value = lead.id
  dataLeads.errorLead = null

  singleLead.value = {
    company_name: lead.company_name,
    contact_name: lead.contact_name,
    email: lead.email,
    phone: lead.phone,
    lead_source: lead.lead_source,
    industry_id: lead.industry_id,
    lead_category_id: lead.lead_category_id,
    notes: lead.notes,
    address: lead.address,
  }

  const modal = new bootstrap.Modal(
    document.getElementById('modal-add-data')
  )
  modal.show()
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
          lead_status: 'New',
          visibility_type: 'PRIVATE',
        })
      }
    } else {
      await dataLeads.storeBulkLeads(
        bulkLeads.value.map(lead => ({
          ...lead,
          lead_status: 'New',
          visibility_type: 'PRIVATE',
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
        notes: '',
        address: '',
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
          notes: '',
          address: '',

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


const confirmDelete = async (id) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'Are you sure?',
    text: 'Leads will be permanently deleted!',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete!',
    cancelButtonText: 'Cancelled',
  })

  if (result.isConfirmed) {
    try {
      await dataLeads.deleteLead(id)
      toasts.fire({
        icon: 'success',
        title: 'Lead successfully removed',
      })
    } catch (err) {
      toasts.fire({
        icon: 'error',
        title: err.response?.data?.message || 'Failed to delete lead',
      })
    }
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
     

      <!-- Card: Filter & Sort -->
      <div v-else class="card mb-4">
         
            <div class="card-header d-flex justify-content-between flex-wrap gap-3">
              <!-- Kiri -->
             <div class="d-flex flex-column gap-3">
                <!-- Dropdown Tampilkan -->
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 fw-semibold">
                    <i class="fas fa-list-ul me-1"></i> Showing:
                    </label>
                    <select class="form-select w-auto"
                     v-model="dataLeads.pagination.per_page"
                      @change="dataLeads.changePageSize()"
                    >
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                    </select>
                </div>

                <!-- Tombol add -->
               <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-add-data">
                        <i class="fa fa-plus"></i> Add Data 
                    </button>
                  
                </div>
                <div class="dropdown">
                    <button 
                      class="btn btn-success btn-sm dropdown-toggle" 
                      type="button" 
                      data-bs-toggle="dropdown" 
                      aria-expanded="false"
                    >
                    <i class="fa-solid fa-filter"></i> Filter Data Leads By
                  </button>
                  <ul class="dropdown-menu">
                    <li>
                      <button 
                        class="dropdown-item" 
                        type="button" 
                        @click="dataLeads.fetchLeads('all')"
                      >
                        Master Leads you Created
                      </button>
                    </li>
                    <li>
                      <button 
                        class="dropdown-item" 
                        type="button" 
                        @click="dataLeads.fetchLeads('assigned')"
                      >
                        My Assigned Leads from Admin/Manager
                      </button>
                    </li>
                  </ul>
                  </div>
                </div>

                  


              <!-- Kanan -->
             <div class="d-flex flex-column gap-3 align-items-end" style="min-width:300px;">
                <!-- Pencarian -->
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Searching...." 
                     @input="e => dataLeads.searchWithDelay(e.target.value)">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <!-- SORT COLUMN -->
                    <select
                      class="form-select w-auto"
                      v-model="dataLeads.sort.column"
                      @change="dataLeads.changeSorting()"
                    >
                      <option value="company_name">By Company</option>
                      <option value="created_at">By Created Date</option>
                    </select>

                    <!-- SORT DIRECTION -->
                    <select
                      class="form-select w-auto"
                      v-model="dataLeads.sort.direction"
                      @change="dataLeads.changeSorting()"
                    >
                      <option value="asc">Ascending</option>
                      <option value="desc">Descending</option>
                    </select>
                  </div>
                </div>
            </div>

            

            <div class="table-responsive">
              <table class="table card-table table-vcenter text-nowrap">
               
                <thead>
                <tr>
                   <th :colspan="dataLeads.mode === 'assigned' ? 11 : 9"
                        class="bg-light fw-bold text-primary">
                      <i class="fa fa-table me-2"></i>
                      {{ dataLeads.mode === 'assigned'
                          ? 'My Assigned Leads from Admin/Manager'
                          : 'Master Leads your Created'
                      }}
                    </th>
                  </tr>
                <tr>
                <th>No</th>
                <th @click="dataLeads.toggleSort('company_name')">Company</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Source</th>
                <th>Status</th>
               <th v-if="dataLeads.mode === 'assigned'">Assigned To</th>
               <th v-if="dataLeads.mode === 'assigned'">Visibility</th>
                <th>Last Contact</th>
                <th>Actions</th>
                </tr>
                </thead>

              <tbody v-if="dataLeads.loading">
                <tr>
                  <td 
                      class="text-center" colspan="11">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>
              </tbody>
              <tbody v-else-if="dataLeads.leads.length === 0">
                <tr>
                  <td
                  
                    class="text-center text-muted" colspan="11"
                   >
                    <div class="d-flex flex-column align-items-center justify-content-center">
                      <img
                        :src="notFoundConfig[notFoundType].image"
                        alt="No data"
                         style="max-width: 250px; height: auto;"
                        class="mb-3"
                      />

                      <h6 class="fw-bold text-dark">
                        {{ notFoundConfig[notFoundType].title }}
                      </h6>
                      

                      <p class="fst-italic text-secondary mb-0">
                        {{ notFoundConfig[notFoundType].message }}
                      </p>
                    </div>
                  </td>
                </tr>
              </tbody>


                <tbody v-else>
                  <tr v-for="(lead, index) in dataLeads.leads" :key="lead.id">
                    <td>{{ (dataLeads.pagination.current_page - 1) * dataLeads.pagination.per_page + index + 1 }}</td>


                    <td>
                      <strong>{{ lead.company_name }}</strong><br>
                      <small class="text-muted">{{ lead.industry_name }}</small>
                    </td>

                    <td>{{ lead.contact_name }}</td>
                    <td>{{ lead.email }}</td>
                    <td>{{ lead.phone }}</td>

                    <td>
                      <span class="badge bg-info">{{ lead.lead_source }}</span>
                    </td>

                    <td>
                      <span class="badge bg-warning">{{ lead.lead_status }}</span>
                    </td>

                    <!-- ASSIGNED ONLY -->
                    <td v-if="dataLeads.mode === 'assigned'">
                      <i class="fa fa-user me-1 text-primary"></i>
                      {{ lead.assigned_name ?? '-' }}
                    </td>

                    <td v-if="dataLeads.mode === 'assigned'">
                      <span class="badge bg-primary">
                        {{ lead.visibility_type }}
                      </span>
                    </td>

                  <td>
                  <div class="fw-semibold">
                    {{ formatDate(lead.last_contacted_at) }}
                  </div>
                  
                </td>


                    <td>
                     
                        <button
                          class="btn btn-outline-primary btn-sm me-1"
                          data-bs-toggle="modal"
                          data-bs-target="#leadDetailModal"
                          @click="openDetail(lead.id)"
                          >
                          <i class="fa fa-circle-info"></i>
                        </button>

                      <button
                        v-if="!loadingPermission && permission?.can_delete && dataLeads.mode === 'all'"
                        class="btn btn-outline-danger btn-sm me-1"
                        @click="confirmDelete(lead.id)"
                      >
                        <i class="fa fa-trash"></i>
                      </button>

                      

                      <button
                         
                           v-if="!loadingPermission && permission?.can_update && dataLeads.mode === 'all'"
                          class="btn btn-outline-warning btn-sm me-1"
                          @click="openEditLead(lead)"
                        >
                          <i class="fa fa-edit"></i>
                      </button>


                    </td>
                  </tr>
                </tbody>

                  </table>
            </div>

             <div class="card-header d-flex justify-content-between align-items-center">
                 <button
                  class="btn btn-danger btn-sm"
                  :disabled="dataLeads.pagination.current_page === 1 || dataLeads.loading"
                  @click="dataLeads.prevPage()"
                >
                  <i class="fa-solid fa-circle-chevron-left"></i> Prev
                </button>

                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                  <span class="badge border text-secondary px-3 py-2">
                    {{ dataLeads.leads.length }} data |
                    page {{ dataLeads.pagination.current_page }}
                  </span>
                  <span class="badge border text-secondary px-3 py-2">
                    Total: {{ dataLeads.pagination.total }}
                  </span>
                </div>

                <button
                  class="btn btn-danger btn-sm"
                  :disabled="dataLeads.pagination.current_page === dataLeads.pagination.last_page || dataLeads.loading"
                  @click="dataLeads.nextPage()"
                >
                  Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>
      
      



        
        




          <div class="modal fade" id="leadDetailModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">

                <div class="modal-header">
                  <h5 class="modal-title">Detail Lead</h5>
                  <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

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
                    <p><strong>Company or Store Name:</strong> {{ dataLeads.leadDetail.company_name }}</p>
                    <p><strong>Contact Name:</strong> {{ dataLeads.leadDetail.contact_name }}</p>
                    <p><strong>Email:</strong> {{ dataLeads.leadDetail.email || '-' }}</p>
                    <p><strong>Phone:</strong> {{ dataLeads.leadDetail.phone || '-' }}</p>
                    <p><strong>Industry:</strong> {{ dataLeads.leadDetail.industry_name || '-' }}</p>
                    <p><strong>Category:</strong> {{ dataLeads.leadDetail.category_name || '-' }}</p>
                    <p><strong>Created By:</strong> {{ dataLeads.leadDetail.owner_name || '-' }}</p>
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

                <div class="modal-footer">
                  <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                  </button>
                </div>

              </div>
            </div>
          </div>




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


                         <div class="col-lg-12 mb-3">
                                <label class="form-label">Address</label>

                                <textarea
                                  class="form-control"
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