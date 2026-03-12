<script setup>
import { ref, reactive, onMounted , watch, computed } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useCustomersStore } from '../../../../stores/customersStores';
import { useMenuStore } from "@/stores/menuStore";
import { toasts } from "@/utils/toasts"
import { useRoute, useRouter } from "vue-router";
import Multiselect from "@vueform/multiselect"
import Swal from 'sweetalert2'
const PagesTitle = 'Data Customers';

const dataCustomers = useCustomersStore();
const menuStore = useMenuStore();
const route = useRoute();
const router = useRouter();
const permission = ref(null);
const loadingPermission = ref(true);

onMounted(async () => {
  try {
    if (!localStorage.getItem("auth_token")) {
      alert("Silakan login terlebih dahulu!");
      router.push('/login');
      return;
    }

    await dataCustomers.fetchCustomers(dataCustomers.buildUrl());
    await menuStore.fetchMenus();

     await dataCustomers.fetchLeadCategories()
     await dataCustomers.fetchLeadIndustries()

    permission.value = menuStore.getPermission(route.path);
  } catch (error) {
    console.error(error);
  } finally {
    loadingPermission.value = false;
  }
});

watch(
  () => dataCustomers.searchCustomers,
  dataCustomers.searchWithDelay
);

const showDetail = async (customerId) => {
  try {
    await dataCustomers.detailCustomers(customerId)
  } catch (e) {
    console.error(e)
  }
}


const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'New':
      return 'bg-secondary';

    case 'Blacklist':
      return 'bg-info text-dark';

    case 'Dormant':
      return 'bg-primary';

    case 'Inactive':
      return 'bg-warning text-dark';

        case 'Active':
      return 'bg-success';

    case 'Lost':
      return 'bg-danger';

    default:
      return 'bg-light text-dark';
  }
};




//start code for form customers
const isProcessing = computed(() => {
  return dataCustomers.savingCustomer || dataCustomers.updatingCustomer
})


const form = reactive({
  company_name: '',
  contact_name: '',
  email: '',
  phone: '',
  industry_id: '',
  lead_category_id: '',
  lead_source: '',
  visibility_type: 'PRIVATE',
  notes: '',
  customer_status: '',
  address: '',
})

// const form = reactive({
//   company_name: '',
//   contact_name: '',
//   email: '',
//   phone: '',
//   industry_id: '',
//   lead_category_id: '',
//   lead_source: '',        // ← tambahkan ini
//   visibility_type: 'PRIVATE',
//   notes: '',
//   customer_status: '',
//   address: '',
// })

// Tambahkan ini sebelum `const form = reactive({...})`
const defaultForm = {
  company_name: '',
  contact_name: '',
  email: '',
  phone: '',
  industry_id: '',
  lead_category_id: '',
  lead_source: '',        // ← fix bug sebelumnya
  visibility_type: 'PRIVATE',
  notes: '',
  customer_status: '',
  address: '',
}

const resetForm = () => {
  Object.assign(form, defaultForm)
  editCustomerId.value = null
  dataCustomer.updating = false
  dataCustomer.error = null
  dataCustomers.errorCustomer = null
}


const editCustomerId = ref(null)
const customersInput = ref(null)

const dataCustomer = reactive({
  error: null,
  updating: false,
})


const openAddModal = () => {
  resetForm() 
  editCustomerId.value = null

  Object.assign(form, {
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    industry_id: '',
    lead_category_id: '',
    visibility_type: 'PRIVATE',
    notes: '',
    customer_status: '',
    address: '',
  })

  dataCustomer.error = null
}


const openEditModal = (customer) => {
   console.log('customer data:', customer)
   resetForm() 
  editCustomerId.value = customer.id
  dataCustomer.updating = true

  Object.assign(form, {
    company_name: customer.company_name,
    contact_name: customer.contact_name,
    email: customer.email,
    phone: customer.phone,
    industry_id: customer.industry_id,
    lead_category_id: customer.lead_category_id,
    lead_source: customer.lead_source ?? '',  // ← INI YANG KURANG
    visibility_type: customer.visibility_type,
    notes: customer.notes,
    address: customer.address,
    customer_status: customer.customer_status,
  })

  dataCustomer.error = null
  dataCustomer.updating = true
}



const saveCustomers = async () => {
  if (dataCustomers.savingCustomer || dataCustomers.updatingCustomer) return

  try {
    const isEdit = !!editCustomerId.value

    if (isEdit) {
      await dataCustomers.updateCustomers(editCustomerId.value, form)
    } else {
      await dataCustomers.storeCustomers(form)
    }

    const modal = document.getElementById("modal-add-data")
    const instance = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal)
    instance.hide()
    // resetForm() akan otomatis dipanggil oleh event 'hidden.bs.modal' ↑

    modal.addEventListener("hidden.bs.modal", () => {
      toasts.fire({
        icon: "success",
        title: isEdit ? "Customer updated successfully" : "Customer added successfully",
      })
    }, { once: true })

  } catch (err) {
    toasts.fire({
      icon: "error",
      title: err.response?.data?.message || "Failed to save customer",
    })
  }
}



const handleDeleteCustomers = async (customer) => {
  const confirm = await Swal.fire({
    title: 'Sure delete the menu?',
    text: `Customer "${customer.customer_code}" will be permanently deleted`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancelled',
  })

  if (!confirm.isConfirmed) return

  try {
    await dataCustomers.deleteCustomer(customer.id)
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
      <div v-else class="page-body flex-grow-1">
        <div class="container-xl">

          <!-- Card: Export/Import -->
         <div class="card mb-4">
      <div class="card-header d-flex gap-2 flex-wrap align-items-center">
    
         <!-- Tombol kiri -->
          <div class="d-flex gap-2 flex-wrap">
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
        <button class="btn btn-warning btn-sm d-flex align-items-center ms-auto"  @click="dataCustomers.resetFilters">
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
                      v-model.number="dataCustomers.pagination.per_page" 
                     @change="dataCustomers.changePageSize"
                    >
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                    </select>
                </div>

                <!-- Tombol add -->
                 <button 
                 class="btn btn-primary btn-sm" 
                 v-if="!loadingPermission && permission?.can_create"
                 data-bs-toggle="modal" 
                 data-bs-target="#modal-add-data" 
                 @click="openAddModal">
                    <i class="fa fa-plus"></i> Add Data
                    </button>
                </div>


              <!-- Kanan -->
             <div class="d-flex flex-column gap-3 align-items-end" style="min-width:200px;">
                <!-- Pencarian -->
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Searching...." v-model="dataCustomers.searchCustomers">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" v-model="dataCustomers.sort.column" @change="dataCustomers.changeSorting">
                    <option value="company_name">Name</option>
                    <option value="created_at">Created</option>
                    </select>
                    <select class="form-select w-auto" v-model="dataCustomers.sort.direction" @change="dataCustomers.changeSorting">
                    <option value="asc">Asc</option>
                    <option value="desc">Desc</option>
                    </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Card: Table -->
          <div class="card mb-4">
            <div class="card-header">
              <!-- <h3 class="card-title">{{ PagesTitle }}</h3> -->
            </div>
            <div class="table-responsive">
              <table class="table card-table table-vcenter text-nowrap">
                <thead>
                  <tr>
                    <th style="width:5%;">No.</th>
                    <th>Customer Code</th>
                    <th>Company Name</th>
                    <th>Contact Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status Now</th>
                    <th>Converted</th>
                    <th style="width:10%;">Actions</th>
                  </tr>
                </thead>

                  <!-- LOADING DATA -->
              <tbody v-if="dataCustomers.loadingCustomers">
                <tr>
                  <td colspan="9" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>
              </tbody>

              <!-- EMPTY DATA -->
              <tbody v-else-if="dataCustomers.customersData.length === 0">
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
                            Customers data not found.
                          </p>
                        </div>
                      </td>
                    </tr>
            </tbody>


                <tbody v-else>
                <tr
                    v-for="(cs, index) in dataCustomers.customersData"
                    :key="cs.id"
                  >
                    <td>
                      {{
                        index + 1 +
                        dataCustomers.pagination.per_page *
                          (dataCustomers.pagination.current_page - 1)
                      }}.
                    </td>
                    <td>{{ cs.customer_code }}</td>
                    <td>{{ cs.company_name }}</td>
                    <td>{{ cs.contact_name }}</td>
                    <td>{{ cs.email }}</td>
                    <td>{{ cs.phone }}</td>
                    <td>
                      <span
                        class="badge px-2 py-1"
                        :class="getStatusBadgeClass(cs.customer_status)"
                      >
                        {{ cs.customer_status }}
                      </span>
                    </td>
                    <td>{{ dataCustomers.formatDate(cs.converted_at) }}</td>
                    <td>
                      <!-- UPDATE -->
                      <button
                          v-if="!loadingPermission && permission?.can_update"
                        class="btn btn-outline-warning btn-sm me-1"
                        data-bs-toggle="modal"
                        data-bs-target="#modal-add-data"
                           @click="openEditModal(cs)"
                      >
                        <i class="fa fa-edit"></i>
                      </button>

                      <!-- DELETE -->
                      <button
                        v-if="!loadingPermission && permission?.can_delete"
                        class="btn btn-outline-danger btn-sm me-1"
                         :disabled="dataCustomers.deletingCustomer"
                         @click="handleDeleteCustomers(cs)"
                      >
                        <i class="fa fa-trash"></i>
                      </button>

                      <!-- DETAIL -->
                      <button
                       v-if="!loadingPermission && permission?.can_view"
                        class="btn btn-outline-primary btn-sm me-1"
                        data-bs-toggle="modal"
                        data-bs-target="#customersDetailModal"
                        @click="showDetail(cs.id)"
                        >
                        <i class="fa fa-circle-info"></i>
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
                 :disabled="!dataCustomers.pagination.prev_page_url || dataCustomers.loadingCustomers"
                  @click="dataCustomers.fetchCustomers(dataCustomers.pagination.prev_page_url)">
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                 <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                    <span class="badge border text-secondary px-3 py-2"> {{ dataCustomers.customersData.length }} data | on page {{ dataCustomers.pagination.current_page }}</span>
                    <span class="badge border text-secondary px-3 py-2">Total: {{ dataCustomers.pagination.total }}</span>
                </div>
  
                <button class="btn btn-danger btn-sm"
                :disabled="!dataCustomers.pagination.next_page_url || dataCustomers.loadingCustomers"
                  @click="dataCustomers.fetchCustomers(dataCustomers.pagination.next_page_url)"
               >
                    Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>

        </div>
      </div>

     
    </div>





  <!-- Code Modal: Detail Data -->
  <!-- Code Modal: Detail Data -->
<div class="modal fade" id="customersDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">Detail Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <!-- Loading -->
        <div v-if="dataCustomers.loading" class="d-flex justify-content-center align-items-center" style="min-height: 180px;">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <!-- Content -->
        <div v-else-if="dataCustomers.customerDetail" class="row g-3">

          <!-- BASIC INFO -->
          <div class="col-12">
            <h6 class="text-muted mb-2">
              <i class="fa fa-user me-1"></i> Informasi Customer
            </h6>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Customer Code</span>
                <span class="fw-semibold badge bg-secondary">
                  {{ dataCustomers.customerDetail.customer_code }}
                </span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Company Name</span>
                <span class="fw-semibold">{{ dataCustomers.customerDetail.company_name }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Contact Name</span>
                <span>{{ dataCustomers.customerDetail.contact_name || '-' }}</span>
              </li>
            </ul>
          </div>

          <!-- CONTACT -->
          <div class="col-12">
            <h6 class="text-muted mt-2 mb-2">
              <i class="fa fa-phone me-1"></i> Kontak
            </h6>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Email</span>
                <span>{{ dataCustomers.customerDetail.email || '-' }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Phone</span>
                <span>{{ dataCustomers.customerDetail.phone || '-' }}</span>
              </li>
            </ul>
          </div>

          <!-- CLASSIFICATION -->
          <div class="col-12">
            <h6 class="text-muted mt-2 mb-2">
              <i class="fa fa-tag me-1"></i> Klasifikasi
            </h6>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Category</span>
                <span>{{ dataCustomers.customerDetail.category_name || '-' }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Industry</span>
                <span>{{ dataCustomers.customerDetail.industry_name || '-' }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Lead Source</span>
                <span class="badge bg-info text-dark">
                  {{ dataCustomers.customerDetail.lead_source || '-' }}
                </span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Status</span>
                <span class="badge" :class="getStatusBadgeClass(dataCustomers.customerDetail.customer_status)">
                  {{ dataCustomers.customerDetail.customer_status }}
                </span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Visibility</span>
                <span class="badge" :class="dataCustomers.customerDetail.visibility_type === 'PUBLIC' ? 'bg-success' : 'bg-warning text-dark'">
                  {{ dataCustomers.customerDetail.visibility_type }}
                </span>
              </li>
            </ul>
          </div>

          <!-- OWNERSHIP -->
          <div class="col-12">
            <h6 class="text-muted mt-2 mb-2">
              <i class="fa fa-users me-1"></i> Ownership
            </h6>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Owner</span>
                <span>{{ dataCustomers.customerDetail.owner_name || '-' }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Assigned Sales</span>
                <span>{{ dataCustomers.customerDetail.assigned_name || '-' }}</span>
              </li>
            </ul>
          </div>

          <!-- ADDRESS -->
          <div class="col-12" v-if="dataCustomers.customerDetail.address">
            <h6 class="text-muted mt-2 mb-2">
              <i class="fa fa-map-marker-alt me-1"></i> Address
            </h6>
            <div class="border rounded p-2 bg-light">
              {{ dataCustomers.customerDetail.address }}
            </div>
          </div>

          <!-- NOTES -->
          <div class="col-12" v-if="dataCustomers.customerDetail.notes">
            <h6 class="text-muted mt-2 mb-2">
              <i class="fa fa-sticky-note me-1"></i> Notes
            </h6>
            <div class="border rounded p-2 bg-light">
              {{ dataCustomers.customerDetail.notes }}
            </div>
          </div>

          <!-- META -->
          <div class="col-12">
            <h6 class="text-muted mt-2 mb-2">
              <i class="fa fa-clock me-1"></i> Informasi Sistem
            </h6>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Converted At</span>
                <span>{{ dataCustomers.formatDate(dataCustomers.customerDetail.converted_at) }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Created At</span>
                <span>{{ dataCustomers.formatDate(dataCustomers.customerDetail.created_at) }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Last Updated</span>
                <span>{{ dataCustomers.formatDate(dataCustomers.customerDetail.updated_at) }}</span>
              </li>
            </ul>
          </div>

        </div>

        <!-- Empty -->
        <div v-else class="text-center text-muted py-5">
          <i class="fa fa-inbox fa-2x mb-2"></i>
          <p>Data tidak tersedia</p>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="fa fa-times me-1"></i> Close
        </button>
      </div>

    </div>
  </div>
</div>

<!-- Code Modal: Add Customer -->
<div
  class="modal modal-blur fade"
  id="modal-add-data"
  tabindex="-1"
  role="dialog"
  aria-hidden="true"
>
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">Form Customer</h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"
        ></button>
      </div>

      <!-- Body -->
        <form @submit.prevent="saveCustomers">
      <div class="modal-body">
        <div class="row">

          <!-- Company Name -->
          <div class="col-lg-6 mb-3">
             <label class="form-label">Company or Store Name <small class="text-danger">**</small></label>
            
            <input
              type="text"
              class="form-control"
              placeholder="Example : PT. Clavis Indonesia"
              v-model="form.company_name"
              :class="{ 'is-invalid': dataCustomers.errorCustomer?.company_name }"
              @input="() => {
                if (dataCustomers.errorCustomer?.company_name) {
                  dataCustomers.errorCustomer.company_name = null
                }
              }"
            />
            <div
              v-if="dataCustomers.errorCustomer?.company_name"
              class="invalid-feedback"
            >
              {{ dataCustomers.errorCustomer.company_name[0] }}
            </div>
          </div>

          <!-- Contact Name -->
          <div class="col-lg-6 mb-3">
            <label class="form-label">Contact Name <small class="text-danger">**</small></label>
            <input
              type="text"
              class="form-control"
              placeholder="John Doe"
              v-model="form.contact_name"
                :class="{ 'is-invalid': dataCustomers.errorCustomer?.contact_name }"
                              @input="() => {
                                if (dataCustomers.errorCustomer?.contact_name) {
                                  dataCustomers.errorCustomer.contact_name = null
                                }
                              }"
            />
             <div
              v-if="dataCustomers.errorCustomer?.contact_name"
              class="invalid-feedback"
               >
              {{ dataCustomers.errorCustomer.contact_name[0] }}
            </div>
          </div>


          

              <!-- Email -->
              <div class="col-lg-6 mb-3">
              <label class="form-label">Email <small class="text-danger">**</small></label>
                <input
                  type="email"
                  class="form-control"
                  placeholder="email@company.com"
                  v-model="form.email"
                    :class="{ 'is-invalid': dataCustomers.errorCustomer?.email }"
                                  @input="() => {
                                    if (dataCustomers.errorCustomer?.email) {
                                      dataCustomers.errorCustomer.email = null
                                    }
                                  }"
                />
                <div
                  v-if="dataCustomers.errorCustomer?.email"
                  class="invalid-feedback"
                  >
                  {{ dataCustomers.errorCustomer.email[0] }}
                </div>
              </div>

              <!-- Phone -->
              <div class="col-lg-6 mb-3">
                  <label class="form-label">Phone <small class="text-danger">**</small></label>
                <input
                  type="text"
                  class="form-control"
                  placeholder="08xxxxxxxxxx"
                  v-model="form.phone"
                  :class="{ 'is-invalid': dataCustomers.errorCustomer?.phone }"
                                @input="() => {
                                  if (dataCustomers.errorCustomer?.phone) {
                                    dataCustomers.errorCustomer.phone = null
                                  }
                                }"
                />
                <div
                  v-if="dataCustomers.errorCustomer?.phone"
                  class="invalid-feedback"
                  >
                  {{ dataCustomers.errorCustomer.phone[0] }}
                </div>
              </div>

            <!-- Industry -->
            <div class="col-lg-6 mb-3">
              <label class="form-label">Industry Customer<small class="text-danger">**</small></label>
                <div
                                class="multiselect-wrapper"
                                :class="{ 'is-invalid': dataCustomers.errorCustomer?.industry_id }"
                              >
                                <Multiselect
                                  v-model="form.industry_id"
                                  :options="dataCustomers.industries"
                                  label="name"
                                  valueProp="id"
                                  placeholder="Select Industry"
                                  :searchable="true"
                                  :loading="dataCustomers.loadingIndustries"
                                  @update:modelValue="() => {
                                    if (dataCustomers.errorCustomer?.industry_id) {
                                      dataCustomers.errorCustomer.industry_id = null
                                    }
                                  }"
                                />
                            </div>
                            <div
                                v-if="dataCustomers.errorCustomer?.industry_id"
                                class="invalid-feedback d-block"
                              >
                                {{ dataCustomers.errorCustomer.industry_id[0] }}
                              </div>
                        </div>

                      <!-- Lead Category -->
                        <div class="col-lg-6 mb-3">
                          <label class="form-label">Category Customer<small class="text-danger">**</small></label>
                          <div
                                  class="multiselect-wrapper"
                                  :class="{ 'is-invalid': dataCustomers.errorCustomer?.lead_category_id }"
                                >
                                  <Multiselect
                                    v-model="form.lead_category_id"
                                    :options="dataCustomers.categories"
                                    label="name"
                                    valueProp="id"
                                    placeholder="Select Category"
                                    :searchable="true"
                                    :loading="dataCustomers.loadingCategories"
                                    :close-on-select="true"
                                    @update:modelValue="() => {
                                      if (dataCustomers.errorCustomer?.lead_category_id) {
                                        dataCustomers.errorCustomer.lead_category_id = null
                                      }
                                    }"
                                  />
                                </div>
                            <div
                                v-if="dataCustomers.errorCustomer?.lead_category_id"
                                class="invalid-feedback d-block"
                              >
                                {{ dataCustomers.errorCustomer.lead_category_id[0] }}
                            </div>
                    </div>



                    <div class="col-lg-6 mb-3">
                            <label class="form-label">Lead Source <small class="text-danger">**</small></label>
                            <div
                              class="multiselect-wrapper"
                              :class="{ 'is-invalid': dataCustomers.errorCustomer?.lead_source }"
                            >
                              <Multiselect
                               :key="`lead-source-${editCustomerId}`"
                               v-model="form.lead_source"   
                                :options="dataCustomers.leadSource"
                                label="label"
                                valueProp="value"
                                placeholder="Select Lead Source"
                                @update:modelValue="() => {
                                  if (dataCustomers.errorCustomer?.lead_source) {
                                    dataCustomers.errorCustomer.lead_source = null
                                  }
                                }"
                              />
                            </div>
                            <div
                              v-if="dataCustomers.errorCustomer?.lead_source"
                              class="invalid-feedback d-block"
                            >
                              {{ dataCustomers.errorCustomer.lead_source[0] }}
                            </div>
                    </div>



                      <div class="col-lg-6 mb-3">
                            <label class="form-label">Status Customer <small class="text-danger">**</small></label>
                            <div
                              class="multiselect-wrapper"
                              :class="{ 'is-invalid': dataCustomers.errorCustomer?.customer_status }"
                            >
                              <Multiselect
                                v-model="form.customer_status"
                                :options="dataCustomers.statusCustomer"
                                label="label"
                                valueProp="value"
                                placeholder="Select Customer Status"
                                @update:modelValue="() => {
                                  if (dataCustomers.errorCustomer?.customer_status) {
                                    dataCustomers.errorCustomer.customer_status = null
                                  }
                                }"
                              />
                            </div>
                            <div
                              v-if="dataCustomers.errorCustomer?.customer_status"
                              class="invalid-feedback d-block"
                            >
                              {{ dataCustomers.errorCustomer.customer_status[0] }}
                            </div>
                      </div>



                <!-- Address -->
                <div class="col-lg-12 mb-3">
                  <label class="form-label">Address</label>
                  <textarea
                    class="form-control"
                    rows="2"
                    placeholder="address Customers"
                    v-model="form.address"
                    :class="{ 'is-invalid': dataCustomers.errorCustomer?.address }"
                                        @input="() => {
                                          if (dataCustomers.errorCustomer?.address) {
                                            dataCustomers.errorCustomer.address = null
                                          }
                                        }"
                  ></textarea>
                  <div
                    v-if="dataCustomers.errorCustomer?.address"
                    class="invalid-feedback"
                    >
                    {{ dataCustomers.errorCustomer.address[0] }}
                    </div>
                </div>

                <!-- Notes -->
                <div class="col-lg-12">
                  <label class="form-label">Notes</label>
                  <textarea
                    class="form-control"
                    rows="3"
                    placeholder="Additional notes for customer"
                    v-model="form.notes"
                      :class="{ 'is-invalid': dataCustomers.errorCustomer?.notes }"
                                        @input="() => {
                                          if (dataCustomers.errorCustomer?.notes) {
                                            dataCustomers.errorCustomer.notes = null
                                          }
                                        }"
                  ></textarea>
                  <div
                        v-if="dataCustomers.errorCustomer?.notes"
                        class="invalid-feedback"
                      >
                      {{ dataCustomers.errorCustomer.notes[0] }}
                      </div>
                </div>

        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button
        type="button"
        class="btn btn-outline-secondary"
        data-bs-dismiss="modal"
        :disabled="isProcessing"
      >
        Cancel
      </button>
       
       <button
  type="submit"
  class="btn btn-primary ms-auto"
  :disabled="dataCustomers.savingCustomer || dataCustomers.updatingCustomer"
>
  <i
    v-if="dataCustomers.savingCustomer || dataCustomers.updatingCustomer"
    class="fas fa-spinner fa-spin me-1"
  ></i>
  <i
    v-else
    class="fas fa-save me-1"
  ></i>

  {{
    editCustomerId
      ? (dataCustomers.updatingCustomer ? 'Updating...' : 'Update')
      : (dataCustomers.savingCustomer ? 'Saving...' : 'Save')
  }}
</button>

      </div>

      </form>

    </div>
  </div>
</div>





<!-- ### Modal Export Laporan --> 

<div v-if="exportModalOpen" class="modal-backdrop fade show"></div>
<div v-if="exportModalOpen" class="modal d-block" tabindex="-1">
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

<div v-if="exportModalOpenPdf" class="modal-backdrop fade show"></div>
<div v-if="exportModalOpenPdf" class="modal d-block" tabindex="-1">
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

<div v-if="importCsvModalOpen" class="modal-backdrop fade show"></div>
<div v-if="importCsvModalOpen" class="modal d-block" tabindex="-1">
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

<div v-if="importExcelModalOpen" class="modal-backdrop fade show"></div>
<div v-if="importExcelModalOpen" class="modal d-block" tabindex="-1">
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


</style>