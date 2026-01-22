<script setup>
import { ref, reactive, onMounted , watch} from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useFollowUpsStore } from '../../../../stores/followUpStore';
import { useMenuStore } from "@/stores/menuStore";
import { toasts } from "@/utils/toasts"
import { useRoute, useRouter } from "vue-router";
import Swal from 'sweetalert2'
const PagesTitle = 'Data Follow Up';

const dataFollowUps = useFollowUpsStore();
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

    await dataFollowUps.fetchFollowUp(dataFollowUps.buildUrl());
    await menuStore.fetchMenus();

    permission.value = menuStore.getPermission(route.path);
  } catch (error) {
    console.error(error);
  } finally {
    loadingPermission.value = false;
  }
});

watch(
  () => dataFollowUps.searchFollowUp,
  dataFollowUps.searchWithDelay
);



// untuk detail
const openDetailModal = async (id) => {
  await dataFollowUps.fetchFollowUpDetail(id)

  const modal = new bootstrap.Modal(
    document.getElementById('followUpDetailModal')
  )
  modal.show()
}
const getFollowUpTypeBadge = (type) => {
  switch (type) {
    case 'CALL':
      return 'bg-info'
    case 'EMAIL':
      return 'bg-primary'
    case 'MEETING':
      return 'bg-success'
    default:
      return 'bg-secondary'
  }
}

const getFollowUpStatusBadge = (status) => {
  return status === 'DONE'
    ? 'bg-success'
    : 'bg-warning text-dark'
}

const formatDateTime = (value) => {
  if (!value || value === '-') return '-'
  return new Date(value).toLocaleString('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short'
  })
}




// untuk table
const getTypeBadge = (type) => {
  switch (type) {
    case 'CALL':
      return 'bg-info'
    case 'EMAIL':
      return 'bg-primary'
    case 'MEETING':
      return 'bg-success'
    default:
      return 'bg-secondary'
  }
}

const getStatusBadge = (status) => {
  return status === 'DONE'
    ? 'bg-success'
    : 'bg-warning text-dark'
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

      <!-- Page Body -->
      <div class="page-body flex-grow-1">
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
    <button class="btn btn-warning btn-sm d-flex align-items-center ms-auto" @click="dataFollowUps.resetFilters">
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
                    v-model.number="dataFollowUps.pagination.per_page" 
                     @change="dataFollowUps.changePageSize">
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
                    <input type="text" class="form-control" placeholder="Searching...."  v-model="dataFollowUps.searchFollowUp">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" v-model="dataFollowUps.sort.column" @change="dataFollowUps.changeSorting">
                    <option value="company_name">By Company Name</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto" v-model="dataFollowUps.sort.direction" @change="dataFollowUps.changeSorting">
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
                <th style="width:5%">No.</th>
                <th>Type</th>
                <th>Subject</th>
                <th>
                    <div>FollowUp</div>
                    <div>Lead / Customer</div>
                  </th>
                <th>Notes</th>
                <th>Follow Up At</th>
                <th>Status</th>
                <th>Created</th>
                <th style="width:10%">Actions</th>
              </tr>
            </thead>


                <!-- LOADING DATA -->
              <tbody v-if="dataFollowUps.loadingFollowUp">
                <tr>
                  <td colspan="9" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>
              </tbody>

              <!-- EMPTY DATA -->
              <tbody v-else-if="dataFollowUps.followUpData.length === 0">
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
                            Follow UP data not found.
                          </p>
                        </div>
                      </td>
                    </tr>
            </tbody>

            <tbody v-else>
        <tr
          v-for="(fu, index) in dataFollowUps.followUpData"
          :key="fu.id"
        >
          <!-- NO -->
          <td>
            {{
              index + 1 +
              dataFollowUps.pagination.per_page *
                (dataFollowUps.pagination.current_page - 1)
            }}.
          </td>

          <!-- TYPE -->
          <td>
            <span class="badge" :class="getTypeBadge(fu.follow_up_type)">
              {{ fu.follow_up_type }}
            </span>
          </td>

          <td>
            <span class="text-truncate d-inline-block" style="max-width: 220px">
              {{ fu.subject }}
            </span>
          </td>

          <td>
              <div class="fw-semibold">
                {{ fu.target_name }}
              </div>

              <span
                class="badge badge-sm mt-1"
                :class="fu.target_source === 'LEAD'
                  ? 'bg-secondary'
                  : 'bg-primary'"
              >
                {{ fu.target_source }}
              </span>
          </td>


          <td>
            <span class="text-truncate d-inline-block" style="max-width: 250px">
              {{ fu.notes?.substring(0, 10) }}{{ fu.notes?.length > 10 ? '...' : '' }}
            </span>
          </td>



          <!-- FOLLOW UP AT -->
          <td>{{ dataFollowUps.formatDateTime(fu.follow_up_at) }}</td>

          <!-- STATUS -->
          <td>
          
          <span class="badge" :class="getStatusBadge(fu.status)">
            {{ fu.status }}
          </span>
          </td>

          <!-- CREATED -->
          <td>{{ dataFollowUps.formatDate(fu.created_at) }}</td>

          <!-- ACTIONS -->
          <td>
           <button
  class="btn btn-outline-primary btn-sm"
  @click="openDetailModal(fu.id)"
>
  <i class="fa fa-circle-info"></i>
</button>




            <!-- DELETE -->
            <button
              v-if="!loadingPermission && permission?.can_delete"
              class="btn btn-outline-danger btn-sm me-1"
              :disabled="dataFollowUps.deletingFollowUp"
              @click="handleDeleteFollowUp(fu.id)"
            >
              <i class="fa fa-trash"></i>
            </button>

            <!-- DETAIL -->
            <!-- <button
              class="btn btn-outline-primary btn-sm"
              data-bs-toggle="modal"
              data-bs-target="#followUpDetailModal"
              @click="showFollowUpDetail(fu)"
            >
              <i class="fa fa-eye"></i>
            </button> -->
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
                 :disabled="!dataFollowUps.pagination.prev_page_url || dataFollowUps.loadingFollowUp"
                  @click="dataFollowUps.fetchFollowUp(dataFollowUps.pagination.prev_page_url)">
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                       <span class="badge border text-secondary px-3 py-2"> {{ dataFollowUps.followUpData.length }} data | on page {{ dataFollowUps.pagination.current_page }}</span>
                    <span class="badge border text-secondary px-3 py-2">Total: {{ dataFollowUps.pagination.total }}</span>
                </div>
  
                <button class="btn btn-danger btn-sm"
                  :disabled="!dataFollowUps.pagination.next_page_url || dataFollowUps.loadingFollowUp"
                  @click="dataFollowUps.fetchFollowUp(dataMenu.pagination.next_page_url)" >
                    Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>
        </div>
      </div>
    </div>


        <!-- Modal: Detail Follow Up -->
       <!-- Modal: Detail Follow Up -->
<div class="modal fade" id="followUpDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa fa-circle-info me-1"></i> Detail Follow Up
        </h5>
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
        <div v-if="dataFollowUps.loadingDetail" class="text-center py-5">
          <div class="spinner-border text-primary"></div>
          <p class="mt-2 text-muted">Memuat detail follow up...</p>
        </div>

        <!-- DETAIL -->
        <div v-else-if="dataFollowUps.followUpDetail" class="row g-3">

          <!-- TYPE -->
          <div class="col-md-6">
            <label class="form-label">Follow Up Type</label>
            <span
              class="badge w-100 text-center py-2"
              :class="getFollowUpTypeBadge(dataFollowUps.followUpDetail.follow_up_type)"
            >
              {{ dataFollowUps.followUpDetail.follow_up_type }}
            </span>
          </div>

          <!-- STATUS -->
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <span
              class="badge w-100 text-center py-2"
              :class="getFollowUpStatusBadge(dataFollowUps.followUpDetail.status)"
            >
              {{ dataFollowUps.followUpDetail.status }}
            </span>
          </div>

          <!-- SUBJECT -->
          <div class="col-12">
            <label class="form-label">Subject</label>
            <input
              type="text"
              class="form-control"
              :value="dataFollowUps.followUpDetail.subject"
              readonly
            />
          </div>

          <!-- NOTES -->
          <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea
              class="form-control"
              rows="4"
              readonly
            >{{ dataFollowUps.followUpDetail.notes }}</textarea>
          </div>

          <!-- FOLLOW UP DATE -->
          <div class="col-md-6">
            <label class="form-label">Follow Up At</label>
            <input
              type="text"
              class="form-control"
              :value="formatDateTime(dataFollowUps.followUpDetail.follow_up_at)"
              readonly
            />
          </div>

          <!-- CREATED AT -->
          <div class="col-md-6">
            <label class="form-label">Created At</label>
            <input
              type="text"
              class="form-control"
              :value="formatDateTime(dataFollowUps.followUpDetail.created_at)"
              readonly
            />
          </div>

          <!-- LEAD -->
          <div class="col-md-6">
            <label class="form-label">Lead</label>
            <input
              type="text"
              class="form-control"
              :value="dataFollowUps.followUpDetail.lead_company_name ?? '-'"
              readonly
            />
          </div>

          <!-- CUSTOMER -->
          <div class="col-md-6">
            <label class="form-label">Customer</label>
            <input
              type="text"
              class="form-control"
              :value="dataFollowUps.followUpDetail.customer_company_name ?? '-'"
              readonly
            />
          </div>

          <!-- SALES / CREATED BY -->
          <div class="col-12">
            <label class="form-label">Created By (Sales)</label>
            <input
              type="text"
              class="form-control"
              :value="dataFollowUps.followUpDetail.sales_name ?? '-'"
              readonly
            />
          </div>

        </div>

        <!-- EMPTY -->
        <div v-else class="text-muted text-center py-5">
          Data tidak ditemukan
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
<div class="modal modal-blur fade" id="modal-add-data" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      
      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">Tambah Data Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
         <div class="modal-body">
                                <div class="mb-3">
                                  <label class="form-label">Name</label>
                                  <input type="text" class="form-control" name="example-text-input" placeholder="Your report name" />
                                </div>
                                <label class="form-label">Report type</label>
                                <div class="form-selectgroup-boxes row mb-3">
                                  <div class="col-lg-6">
                                    <label class="form-selectgroup-item">
                                      <input type="radio" name="report-type" value="1" class="form-selectgroup-input" checked />
                                      <span class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="me-3">
                                          <span class="form-selectgroup-check"></span>
                                        </span>
                                        <span class="form-selectgroup-label-content">
                                          <span class="form-selectgroup-title strong mb-1">Simple</span>
                                          <span class="d-block text-secondary">Provide only basic data needed for the report</span>
                                        </span>
                                      </span>
                                    </label>
                                  </div>
                                  <div class="col-lg-6">
                                    <label class="form-selectgroup-item">
                                      <input type="radio" name="report-type" value="1" class="form-selectgroup-input" />
                                      <span class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="me-3">
                                          <span class="form-selectgroup-check"></span>
                                        </span>
                                        <span class="form-selectgroup-label-content">
                                          <span class="form-selectgroup-title strong mb-1">Advanced</span>
                                          <span class="d-block text-secondary"
                                            >Insert charts and additional advanced analyses to be inserted in the report</span
                                          >
                                        </span>
                                      </span>
                                    </label>
                                  </div>
                                </div>
                                <div class="row">
                                  <div class="col-lg-8">
                                    <div class="mb-3">
                                      <label class="form-label">Report url</label>
                                      <div class="input-group input-group-flat">
                                        <span class="input-group-text"> https://tabler.io/reports/ </span>
                                        <input type="text" class="form-control ps-0" value="report-01" autocomplete="off" />
                                      </div>
                                    </div>
                                  </div>
                                  <div class="col-lg-4">
                                    <div class="mb-3">
                                      <label class="form-label">Visibility</label>
                                      <select class="form-select">
                                        <option value="1" selected>Private</option>
                                        <option value="2">Public</option>
                                        <option value="3">Hidden</option>
                                      </select>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div class="modal-body">
                                <div class="row">
                                  <div class="col-lg-6">
                                    <div class="mb-3">
                                      <label class="form-label">Client name</label>
                                      <input type="text" class="form-control" />
                                    </div>
                                  </div>
                                  <div class="col-lg-6">
                                    <div class="mb-3">
                                      <label class="form-label">Reporting period</label>
                                      <input type="date" class="form-control" />
                                    </div>
                                  </div>
                                  <div class="col-lg-12">
                                    <div>
                                      <label class="form-label">Additional information</label>
                                      <textarea class="form-control" rows="3"></textarea>
                                    </div>
                                  </div>
                                </div>
                              </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
          Batal
        </button>
        <button type="button" class="btn btn-primary ms-auto">
          <i class="fas fa-save me-1"></i> Simpan
        </button>
      </div>

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