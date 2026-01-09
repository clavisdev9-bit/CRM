<script setup>
import { ref, reactive, onMounted , watch} from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useDataMenuStore } from '../../../../stores/menuDataStore';
import { useMenuStore } from "@/stores/menuStore";
import { toasts } from "@/utils/toasts"
import { useRoute, useRouter } from "vue-router";
import Swal from 'sweetalert2'
const PagesTitle = 'Data Menu Management';


const dataMenu = useDataMenuStore();
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

    await dataMenu.fetchMenus(dataMenu.buildUrl());
    await menuStore.fetchMenus();

    permission.value = menuStore.getPermission(route.path);
  } catch (error) {
    console.error(error);
  } finally {
    loadingPermission.value = false;
  }
});

watch(
  () => dataMenu.searchMenus,
  dataMenu.searchWithDelay
);

const showDetail = async (menuId) => {
  try {
    await dataMenu.detailMenus(menuId)
  } catch (e) {
    console.error(e)
  }
}

// ===== Add Role =====
// ===== FORM =====
const formMenu = ref({
  menu: '',
})

const editMenuId = ref(null)
const MenuInput = ref(null)

// ===== OPEN ADD =====
const openAddModal = () => {
  editMenuId.value = null
  formMenu.value.menu = ''
  dataMenu.errorMenu = null
}

const openEditModal = (menu) => {
  editMenuId.value = menu.id_menu
  formMenu.value.menu = menu.menu

  dataMenu.errorMenu = null
  dataMenu.updatingMenu = false // 
}



onMounted(() => {
  const modal = document.getElementById('modal-add-data')

   modal.addEventListener("shown.bs.modal", () => {
    MenuInput.value?.focus()
  })

  modal.addEventListener('hidden.bs.modal', () => {
    editMenuId.value = null
    dataMenu.updatingMenu = false
    dataMenu.savingMenu = false
    dataMenu.errorMenu = null

    formMenu.value.menu = ''
  })
})




const saveMenu = async () => {
  if (dataMenu.savingMenu || dataMenu.updatingMenu) return

  try {
    const isEdit = !!editMenuId.value
    if (isEdit) {
      await dataMenu.updateMenu(editMenuId.value, formMenu.value)
    } else {
      await dataMenu.storeMenu(formMenu.value)
    }

    // reset form
    editMenuId.value = null
    formMenu.value.menu = ""

    // close modal first
    const modal = document.getElementById("modal-add-data")
    const instance =
      bootstrap.Modal.getInstance(modal) ||
      new bootstrap.Modal(modal)

    instance.hide()

    // toast setelah modal tertutup
    modal.addEventListener(
      "hidden.bs.modal",
      () => {
        toasts.fire({
          icon: "success",
          title: isEdit
            ? "Menu berhasil diupdate"
            : "Menu berhasil ditambahkan",
        })
      },
      { once: true }
    )

  } 
  
  catch (err) {
  console.error(err)

  toasts.fire({
    icon: "error",
    title: err.response?.data?.message || "Gagal menyimpan data",
  })
}

}




const handleDeleteMenu = async (menu) => {
  const confirm = await Swal.fire({
    title: 'Sure delete the menu?',
    text: `Menu "${menu.menu}" will be permanently deleted`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancelled',
  })

  if (!confirm.isConfirmed) return

  try {
    await dataMenu.deleteMenu(menu.id_menu)
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

      <!-- Page Body -->
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

      <div v-else class="page-body flex-grow-1">
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
    <button class="btn btn-warning btn-sm d-flex align-items-center ms-auto"  @click="dataMenu.resetFilters">
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
                     v-model.number="dataMenu.pagination.per_page" 
                     @change="dataMenu.changePageSize">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                    </select>
                </div>

                <!-- Tombol add -->
                 <button
                  v-if="!loadingPermission && permission?.can_create"
                  class="btn btn-primary btn-sm"
                  data-bs-toggle="modal"
                  data-bs-target="#modal-add-data"
                  @click="openAddModal">
                    <i class="fa fa-plus"></i> Add Data
                    </button>
                </div>


              <!-- Kanan -->
             <div class="d-flex flex-column gap-3 align-items-end" style="min-width:300px;">
                <!-- Pencarian -->
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Searching...." 
                    v-model="dataMenu.searchMenus">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" v-model="dataMenu.sort.column" @change="dataMenu.changeSorting">
                    <option value="menu">By Name</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto" v-model="dataMenu.sort.direction" @change="dataMenu.changeSorting">
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
                    <th>Menu Name</th>
                    <th style="width: 10%;">Created</th>
                    <th style="width: 8%;">Actions</th>
                  </tr>
                </thead>

                 <!-- LOADING DATA -->
              <tbody v-if="dataMenu.loadingMenus">
                <tr>
                  <td colspan="8" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>
              </tbody>

              <!-- EMPTY DATA -->
              <tbody v-else-if="dataMenu.menuData.length === 0">
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
                            Role data not found.
                          </p>
                        </div>
                      </td>
                    </tr>
            </tbody>

                <tbody v-else>
                <tr
                    v-for="(mn, index) in dataMenu.menuData"
                    :key="mn.id_menu"
                  >
                    <td>
                      {{
                        index + 1 +
                        dataMenu.pagination.per_page *
                          (dataMenu.pagination.current_page - 1)
                      }}.
                    </td>
                    <td>{{ mn.menu }}</td>
                    <td>{{ dataMenu.formatDate(mn.created_at) }}</td>
                    <td>
                      <!-- UPDATE -->
                      <button
                        v-if="!loadingPermission && permission?.can_update"
                        class="btn btn-outline-warning btn-sm me-1"
                        data-bs-toggle="modal"
                        data-bs-target="#modal-add-data"
                          @click="openEditModal(mn)"
                      >
                        <i class="fa fa-edit"></i>
                      </button>

                      <!-- DELETE -->
                      <button
                     v-if="!loadingPermission && permission?.can_delete"
                        class="btn btn-outline-danger btn-sm me-1"
                         :disabled="dataMenu.deletingMenu"
                         @click="handleDeleteMenu(mn)"
                      >
                        <i class="fa fa-trash"></i>
                      </button>

                      <!-- DETAIL -->
                      <button
                        class="btn btn-outline-primary btn-sm me-1"
                        data-bs-toggle="modal"
                        data-bs-target="#userDetailModal"
                         @click="showDetail(mn.id_menu)"
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
                :disabled="!dataMenu.pagination.prev_page_url || dataMenu.loadingMenus"
                  @click="dataMenu.fetchMenus(dataMenu.pagination.prev_page_url)"
                 >
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                    <span class="badge border text-secondary px-3 py-2"> {{ dataMenu.menuData.length }} data | on page {{ dataMenu.pagination.current_page }}</span>
                    <span class="badge border text-secondary px-3 py-2">Total: {{ dataMenu.pagination.total }}</span>
                </div>
  
                <button class="btn btn-danger btn-sm"
                 :disabled="!dataMenu.pagination.next_page_url || dataMenu.loadingMenus"
                  @click="dataMenu.fetchMenus(dataMenu.pagination.next_page_url)"
               >
                    Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>

        </div>
      </div>

     
    </div>


  <!-- Code Modal: Detail Data -->
   <div class="modal fade" id="userDetailModal" tabindex="-1" aria-hidden="true">
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

        <!-- Loading -->
        <div
          v-if="dataMenu.loading"
          class="d-flex justify-content-center align-items-center"
          style="min-height: 180px;"
        >
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <!-- Content -->
        <div v-else-if="dataMenu.showDetail" class="row">
          <div class="col-12">
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Menu</span>
                <span class="fw-semibold">{{ dataMenu.menuDetail.menu }}</span>
              </li>

            </ul>
          </div>
        </div>

        <!-- Empty -->
        <div v-else class="text-center text-muted py-5">
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



<!-- Modal Add / Edit Role -->
<div
  class="modal modal-blur fade"
  id="modal-add-data"
  tabindex="-1"
  aria-hidden="true"
>
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">
          {{ editMenuId ? 'Edit Menu' : 'Add New Menu' }}
        </h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"
        ></button>
      </div>

      <!-- FORM -->
      <form @submit.prevent="saveMenu">
        <!-- Body -->
        <div class="modal-body">

          <!-- Role Name -->
          <div class="mb-3">
            <label class="form-label">Name menu</label>
            <input
              ref="menuInput"
              type="text"
              class="form-control"
              v-model="formMenu.menu"
              :class="{ 'is-invalid': dataMenu.errorMenu?.menu }"
              @input="dataMenu.errorMenu = null"
              placeholder="Enter a menu name"
            />
            <div class="invalid-feedback">
              {{ dataMenu.errorMenu?.menu?.[0] }}
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button
            type="submit"
            class="btn btn-primary ms-auto"
            :disabled="dataMenu.savingMenu || dataMenu.updatingMenu"
          >
            <i class="fas fa-save me-1"></i>
            {{
              editMenuId
                ? (dataMenu.updatingMenu ? 'Updating...' : 'Update')
                : (dataMenu.savingMenu ? 'Saving...' : 'Save')
            }}
          </button>
        </div>

      </form>

    </div>
  </div>
</div>




<!-- ## Modal Export Laporan --> 

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