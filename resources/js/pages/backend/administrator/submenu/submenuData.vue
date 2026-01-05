<script setup>
import { ref, reactive,computed, onMounted , watch} from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useSubmenuStore } from '../../../../stores/submenuDataStore';
import { useDataMenuStore } from '../../../../stores/menuDataStore';
import { useMenuStore } from "@/stores/menuStore";
import { toasts } from "@/utils/toasts"
import { useRoute, useRouter } from "vue-router";
import Multiselect from '@vueform/multiselect'
import Swal from 'sweetalert2'
import '@vueform/multiselect/themes/default.css'
const PagesTitle = 'Data Submenu Management';

const dataSubmenu = useSubmenuStore();
const menuStore = useMenuStore();
const route = useRoute();
const router = useRouter();

const permission = ref(null);
const loadingPermission = ref(true);
const menuDataStore = useDataMenuStore()

onMounted(async () => {
  try {
    if (!localStorage.getItem("auth_token")) {
      alert("Silakan login terlebih dahulu!");
      router.push('/login');
      return;
    }

    await dataSubmenu.fetchSubmenu(dataSubmenu.buildUrl());
    await menuStore.fetchMenus();
    await dataSubmenu.fetchSubmenuSelect()

    permission.value = menuStore.getPermission(route.path);
  } catch (error) {
    console.error(error);
  } finally {
    loadingPermission.value = false;
  }

  if (menuDataStore.menuData.length === 0) {
    menuDataStore.fetchMenus()
  }
});

watch(
  () => dataSubmenu.searchSubmenu,
  dataSubmenu.searchWithDelay
);

const showDetail = async (submenuId) => {
  try {
    await dataSubmenu.detailSubmenu(submenuId)
  } catch (e) {
    console.error(e)
  }
}



// ===== Add submenu =====
// ===== FORM =====
const formSubmenu = ref({
  id_submenu: null, 
  id_menu: null,
  title: '',
  url: '',
  icon: '',
  parent_id: null,
  is_active: true,
  noted: '',
})

const editSubmenuId = ref(null)
const TitleInput = ref(null)

// ===== OPEN ADD =====
const openAddModal = () => {
  editSubmenuId.value = null
  formSubmenu.value.id_menu = null
  formSubmenu.value.title = ''
  formSubmenu.value.url = ''
  formSubmenu.value.icon = ''
  formSubmenu.value.parent_id = null
  formSubmenu.value.is_active = true
  formSubmenu.value.noted = ''
  dataSubmenu.errorRole = null
}

const openEditModal = (submenu) => {
  editSubmenuId.value = submenu.id_submenu
  formSubmenu.value.id_menu = submenu.id_menu
  formSubmenu.value.title = submenu.title
  formSubmenu.value.url = submenu.url
  formSubmenu.value.icon = submenu.icon
  formSubmenu.value.parent_id = submenu.parent_id
  formSubmenu.value.noted = submenu.noted
  formSubmenu.value.is_active = submenu.is_active

  dataSubmenu.errorRole = null
  dataSubmenu.updatingSubmenu = false
}


onMounted(() => {
  const modal = document.getElementById('modal-add-data')

   modal.addEventListener("shown.bs.modal", () => {
    TitleInput.value?.focus()
  })

  modal.addEventListener('hidden.bs.modal', () => {
    editSubmenuId.value = null
    dataSubmenu.updatingSubmenu = false
    dataSubmenu.savingSubmenu = false
    dataSubmenu.errorRole = null

    formSubmenu.value.id_menu = null
    formSubmenu.value.title = ''
    formSubmenu.value.url = ''
    formSubmenu.value.icon = ''
    formSubmenu.value.parent_id = null
    formSubmenu.value.is_active = null
    formSubmenu.value.noted = ''
  })
})


const saveSubmenu = async () => {
  if (dataSubmenu.savingSubmenu || dataSubmenu.updatingSubmenu) return

  try {
    const isEdit = !!editSubmenuId.value
    if (isEdit) {
      await dataSubmenu.updateSubmenu(editSubmenuId.value, formSubmenu.value)
    } else {
      await dataSubmenu.storeSubmenu(formSubmenu.value)
    }

    // reset form
    editSubmenuId.value = null
    formSubmenu.value.id_menu = ''
    formSubmenu.value.title = ''
    formSubmenu.value.url = ''
    formSubmenu.value.icon = ''
    formSubmenu.value.parent_id = ''
    formSubmenu.value.is_active = ''
    formSubmenu.value.noted = ''

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
            ? "SubMenu berhasil diupdate"
            : "SubMenu berhasil ditambahkan",
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

const parentOptions = computed(() => {
  if (!formSubmenu.value.id_submenu) {
    return dataSubmenu.submenuSelect
  }
  return dataSubmenu.submenuSelect.filter(
    item => item.id_submenu !== formSubmenu.value.id_submenu
  )
})

const handleDeleteSubmenu = async (submenu) => {
  const { isConfirmed } = await Swal.fire({
    title: 'Delete Submenu?',
    html: `Submenu <b>"${submenu.title}"</b> will be permanently deleted.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
    reverseButtons: true,
  })

  if (!isConfirmed) return

  try {
    // optional: loading indicator
    Swal.fire({
      title: 'Deleting...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    })

    await dataSubmenu.deleteSubmenu(submenu.id_submenu)

    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      text: 'Submenu has been deleted successfully.',
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
        'Failed to delete submenu.',
    })
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

       <div v-if="loadingPermission" class="text-center py-5">
              <div class="spinner-border text-primary"></div>
              <p class="text-muted mt-2">Loading access rights......</p>
            </div>

            <!-- Tidak punya akses -->
            <div v-else-if="!permission?.can_view" class="text-center py-5">
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
    <button class="btn btn-warning btn-sm d-flex align-items-center ms-auto"  @click="dataSubmenu.resetFilters">
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
                    v-model.number="dataSubmenu.pagination.per_page" 
                    @change="dataSubmenu.changePageSize"
                    >
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
                    <input type="text" class="form-control" placeholder="Searching...." v-model="dataSubmenu.searchSubmenu">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" v-model="dataSubmenu.sort.column" @change="dataSubmenu.changeSorting">
                    <option value="fullname">By Name</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto"  v-model="dataSubmenu.sort.direction" @change="dataSubmenu.changeSorting">
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
                    <th style="width: 3%;">No.</th>
                    <th>Name Menu</th>
                    <th>Title</th>
                    <th>URL</th>
                    <th>Icon</th>
                    <th>Parent With</th>
                    <th>Status</th>
                    <!-- <th>Created</th> -->
                    <th>Noted</th>
                    <th style="width: 8%;">Actions</th>
                  </tr>
                </thead>

             <tbody>
                <!-- LOADING -->
                <tr v-if="dataSubmenu.loadingSubmenu">
                  <td colspan="9" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>

                <!-- EMPTY -->
                <tr v-else-if="dataSubmenu.SubmenuData.length === 0">
                  <td colspan="9" class="text-center">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                      <img
                        src="https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif"
                        alt="No data found"
                        style="max-width: 250px"
                        class="mb-3"
                      />
                      <p class="text-danger fw-bold fst-italic">
                        <i class="fa fa-exclamation-circle me-1"></i>
                        Submenu data not found.
                      </p>
                    </div>
                  </td>
                </tr>

  <!-- DATA -->
  <template v-else>
    <template
      v-for="(submenu, index) in dataSubmenu.SubmenuData"
      :key="submenu.id_submenu"
    >
      <!-- PARENT -->
      <tr>
        <td>
          {{
            (dataSubmenu.pagination.current_page - 1)
              * dataSubmenu.pagination.per_page
              + index + 1
          }}
        </td>
        <td>{{ submenu.menu?.menu }}</td>
        <td><strong>{{ submenu.title }}</strong></td>
        <td>{{ submenu.url }}</td>
        <td><i :class="submenu.icon"></i></td>
        <td>-</td>
        <td>
          <span
            class="badge"
            :class="submenu.is_active ? 'bg-success' : 'bg-danger'"
          >
            {{ submenu.is_active ? 'Active' : 'Inactive' }}
          </span>
        </td>
        <td>{{ submenu.noted ?? '-' }}</td>
        <td>
          <button 
              v-if="permission?.can_update"
              class="btn btn-outline-warning btn-sm me-1"
              data-bs-toggle="modal"
              data-bs-target="#modal-add-data"
              @click="openEditModal(submenu)">
            <i class="fa fa-edit"></i>
          </button>
          <button 
              v-if="permission?.can_delete"
               class="btn btn-outline-danger btn-sm"
               :disabled="dataSubmenu.deletingSubmenu"
               @click="handleDeleteSubmenu(submenu)">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      </tr>

      <!-- CHILD -->
      <tr
        v-for="child in submenu.children"
        :key="child.id_submenu"
        class="table-light"
      >
        <td></td>
        <td>{{ child.menu?.menu }}</td>
        <td>
          <span class="ms-4">
            <span class="fw-bold text-primary me-1">↳</span>
            {{ child.title }}
          </span>
        </td>
        <td>{{ child.url }}</td>
        <td><i :class="child.icon"></i></td>
        <td class="text-muted">{{ submenu.title }}</td>
        <td>
          <span
            class="badge"
            :class="child.is_active ? 'bg-success' : 'bg-danger'"
          >
            {{ child.is_active ? 'Active' : 'Inactive' }}
          </span>
        </td>
        <td>{{ child.noted ?? '-' }}</td>
        <td>
          <button 
          v-if="permission?.can_update"
          class="btn btn-outline-warning btn-xs me-1"
          data-bs-toggle="modal"
              data-bs-target="#modal-add-data"
              @click="openEditModal(child)">
            <i class="fa fa-edit"></i>
          </button>

          <button 
            v-if="permission?.can_delete"
            class="btn btn-outline-danger btn-xs"
            :disabled="dataSubmenu.deletingSubmenu"
            @click="handleDeleteSubmenu(child)">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      </tr>
    </template>
  </template>
</tbody>

              </table>
            </div>
          </div>

          <!-- Card: Pagination -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
            <button
                class="btn btn-danger btn-sm"
                :disabled="!dataSubmenu.pagination.prev_page_url || dataSubmenu.loadingSubmenu"
                @click="dataSubmenu.fetchSubmenu(dataSubmenu.pagination.prev_page_url)"
              >
                <i class="fa-solid fa-circle-chevron-left"></i> Prev
              </button>

  
                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                    <span class="badge border text-secondary px-3 py-2"> {{ dataSubmenu.SubmenuData.length }} data | on page {{ dataSubmenu.pagination.current_page }}</span>
                    <span class="badge border text-secondary px-3 py-2">Total: {{ dataSubmenu.pagination.total }} data</span>
                </div>
  
                <button
                  class="btn btn-danger btn-sm"
                  :disabled="!dataSubmenu.pagination.next_page_url || dataSubmenu.loadingSubmenu"
                  @click="dataSubmenu.fetchSubmenu(dataSubmenu.pagination.next_page_url)"
                >
                  Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>

            </div>
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
          {{ editSubmenuId ? 'Edit Submenu' : 'Add New Submenu' }}
        </h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close"
        ></button>
      </div>

      <!-- FORM -->
      <form @submit.prevent="saveSubmenu">

        <!-- Body -->
        <div class="modal-body">

          <!-- Role Name -->
         <div class="row">
     
            <div class="col-md-4 mb-3">
              <label class="form-label">Select Menu</label>
              <div
                :class="{
                  'is-invalid': dataSubmenu.errorSubmenu?.id_menu
                }"
                class="multiselect-wrapper"
              >
                <Multiselect
                  v-model="formSubmenu.id_menu"
                  :options="menuDataStore.menuData"
                  label="menu"
                  valueProp="id_menu"
                  placeholder="Select Menu..."
                  :searchable="true"
                  :loading="menuDataStore.loadingMenus"
                  @update:modelValue="dataSubmenu.errorSubmenu = null"
                />
              </div>
              <div
                v-if="dataSubmenu.errorSubmenu?.id_menu"
                class="invalid-feedback d-block"
              >
                {{ dataSubmenu.errorSubmenu.id_menu[0] }}
              </div>
            </div>


        <div class="col-md-4 mb-3">
          <label class="form-label">Title</label>
          <input
            ref="TitleInput"
            v-model="formSubmenu.title"
            :class="{ 'is-invalid': dataSubmenu.errorSubmenu?.title }"
            @input="dataSubmenu.errorSubmenu = null"
            type="text"
            class="form-control"
            placeholder="Title..."
          />
           <div class="invalid-feedback">
              {{ dataSubmenu.errorSubmenu?.title?.[0] }}
            </div>
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label">Icon</label>
          <input
            type="text"
            v-model="formSubmenu.icon"
            :class="{ 'is-invalid': dataSubmenu.errorSubmenu?.icon }"
            @input="dataSubmenu.errorSubmenu = null"
            class="form-control"
            placeholder="Icon..."
          />
            <div class="invalid-feedback">
              {{ dataSubmenu.errorSubmenu?.icon?.[0] }}
            </div>
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label">URL</label>
          <input
            type="text"
            v-model="formSubmenu.url"
            :class="{ 'is-invalid': dataSubmenu.errorSubmenu?.url }"
            @input="dataSubmenu.errorSubmenu = null"
            class="form-control"
            placeholder="URL.. (Example: /dashboard)"
          />
           <div class="invalid-feedback">
              {{ dataSubmenu.errorSubmenu?.url?.[0] }}
            </div>
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label"> Parent</label>
          <Multiselect
            v-model="formSubmenu.parent_id"
            :options="parentOptions"
            label="title"
            valueProp="id_submenu"
            placeholder="Select Parent"
            :searchable="true"
            :loading="dataSubmenu.loadingSelect"
          />
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label">Status</label>
          <Multiselect
            v-model="formSubmenu.is_active"
            :options="dataSubmenu.statusStatis"
            label="label"
            valueProp="value"
            placeholder="Select Status"
          />

        </div>
    </div>

          <!-- noted -->
          <div class="mb-3">
            <label class="form-label">Noted</label>
            <textarea
              class="form-control"
              rows="3"
              v-model="formSubmenu.noted"
              :class="{ 'is-invalid': dataSubmenu.errorSubmenu?.noted }"
              @input="dataSubmenu.errorSubmenu = null"
              placeholder="Enter a Noted..."
            ></textarea>
            <div class="invalid-feedback">
              <div class="invalid-feedback">
              {{ dataSubmenu.errorSubmenu?.noted?.[0] }}
            </div>
            </div>
          </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button
            type="submit"
            class="btn btn-primary ms-auto"
            :disabled="dataSubmenu.savingSubmenu || dataSubmenu.updatingSubmenu"
          >
            <i class="fas fa-save me-1"></i>
            {{
              editSubmenuId
                ? (dataSubmenu.updatingSubmenu ? 'Updating...' : 'Update')
                : (dataSubmenu.savingSubmenu ? 'Saving...' : 'Save')
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

.multiselect-wrapper.is-invalid .multiselect {
  border-color: #dc3545;
}

.multiselect-wrapper.is-invalid .multiselect:focus-within {
  box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}

</style>