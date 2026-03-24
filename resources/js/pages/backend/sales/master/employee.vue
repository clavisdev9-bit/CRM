<script setup>
import { ref, onMounted, watch, computed, nextTick  } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useMasterSalesStore } from '../../../../stores/masterSalesStore';
import { useMenuStore } from "@/stores/menuStore";
import { useAccessMenuStore } from "@/stores/accessMenuStore";
import { toasts } from "@/utils/toasts"
import { useRoute, useRouter } from "vue-router";
import Multiselect from '@vueform/multiselect'
import Swal from 'sweetalert2'
import '@vueform/multiselect/themes/default.css'


const PagesTitle = 'Data Sales Management';

const dataMastersales = useMasterSalesStore();
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

    await dataMastersales.fetchMasterSalesData(dataMastersales.buildUrl());
    await menuStore.fetchMenus();
    await dataMastersales.fetchUserSelect();
    await dataMastersales.fetchOfficeSelect();

    permission.value = menuStore.getPermission(route.path);
  } catch (error) {
    console.error(error);
  } finally {
    loadingPermission.value = false;
  }
});



watch(
  () => dataMastersales.searchMasterSalesData,
  dataMastersales.searchWithDelay
);





const showDetail = async (idMasterSales) => {
  try {
     await dataMastersales.fetchDetailMasterSalesData(idMasterSales)
  } catch (e) {
    console.error(e)
  }
}


// code store and edit
const form = ref({
  user_id: '',
  office_id: '',
  nik: '',
  tempat_lahir: '',
  tanggal_lahir: '',
  jenis_kelamin: '',
  alamat: '',
  no_hp: '',
  tanggal_masuk: '',
  status_karyawan: '',
  attendance_mode: '',
})


const editMasterSalesId = ref(null) // null = add, ada value = edit
const MasterSalesInput = ref(null)  // optional (ref ke modal / form)


// ===== OPEN ADD =====
const openAddModal = () => {
  editMasterSalesId.value = null
 dataMastersales.fetchUserSelect() // tanpa employee_id
 
 
  form.value = {
    user_id: '',
    office_id: '',
    nik: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    jenis_kelamin: '',
    alamat: '',
    no_hp: '',
    tanggal_masuk: '',
    status_karyawan: '',
    attendance_mode: '',
  }


  dataMastersales.errorMasterSalesData = null
}






const statusBadgeLabel = computed(() => {
  const status = dataMastersales.MasterSalesDataDetail?.status_karyawan

  switch (status) {
    case 'PERMANENT':
      return 'Permanent'
    case 'CONTRACT':
      return 'Contract'
    case 'INTERNSHIP':
      return 'Internship'
    default:
      return '-'
  }
})


const statusBadgeClass = computed(() => {
  const status = dataMastersales.MasterSalesDataDetail?.status_karyawan

  switch (status) {
    case 'PERMANENT':
      return 'bg-success'
    case 'CONTRACT':
      return 'bg-warning text-dark'
    case 'INTERNSHIP':
      return 'bg-info'
    default:
      return 'bg-secondary'
  }
})

const openEditModal = async (employee) => {
  editMasterSalesId.value = employee.id_employee
  console.log('FULL employee:', JSON.parse(JSON.stringify(employee)))

  // Reset form dulu
  form.value = {
    user_id: null, office_id: null, nik: '',
    tempat_lahir: '', tanggal_lahir: '', jenis_kelamin: '',
    alamat: '', no_hp: '', tanggal_masuk: '',
    status_karyawan: '', attendance_mode: '',
  }

  // Fetch options — tanpa parameter
  await dataMastersales.fetchUserSelect()
  await dataMastersales.fetchOfficeSelect()  // ← tanpa argument

  await nextTick()

  // Debug cek dulu struktur office
  console.log('office nested:', employee.office)

  form.value = {
  user_id: employee.user_id,
office_id: employee.office_id ?? null,   // ← coba dari user
  nik: employee.nik,
  tempat_lahir: employee.tempat_lahir,
  tanggal_lahir: employee.tanggal_lahir,
  jenis_kelamin: employee.jenis_kelamin,
  alamat: employee.alamat,
  no_hp: employee.no_hp,
  tanggal_masuk: employee.tanggal_masuk,
  status_karyawan: employee.status_karyawan,
  attendance_mode: employee.attendance_mode,
}
}


watch(
  () => dataMastersales.userSelect,
  (users) => {
    if (!users.length || !editMasterSalesId.value) return

    const found = users.find(
      u => u.id_user === form.value.user_id
    )

    if (found) {
      form.value.user_id = found.id_user
    }
  },
  { immediate: true }
)


const saveMasterSales = async () => {
  if (
    dataMastersales.savingMasterSalesData ||
    dataMastersales.updatingMasterSalesData
  ) return

  try {
    const isEdit = !!editMasterSalesId.value

    if (isEdit) {
      await dataMastersales.updateMasterSales(
        editMasterSalesId.value,
        form.value
      )
    } else {
      await dataMastersales.storeMasterSales(form.value)
    }

    // reset state
    editMasterSalesId.value = null
    form.value = {
      user_id: '',
      office_id: '',
      nik: '',
      tempat_lahir: '',
      tanggal_lahir: '',
      jenis_kelamin: '',
      alamat: '',
      no_hp: '',
      tanggal_masuk: '',
      status_karyawan: '',
      attendance_mode: '',
    }

    // tutup modal
    const modal = document.getElementById("modal-add-data")
    const instance =
      bootstrap.Modal.getInstance(modal) ||
      new bootstrap.Modal(modal)

    instance.hide()

    modal.addEventListener(
      "hidden.bs.modal",
      () => {
        toasts.fire({
          icon: "success",
          title: isEdit
            ? "Employee updated successfully"
            : "Employee successfully added",
        })
      },
      { once: true }
    )

    // reload table
    dataMastersales.fetchMasterSalesData(
      dataMastersales.buildUrl()
    )

  } catch (err) {
    console.error(err)

    toasts.fire({
      icon: "error",
      title: err.response?.data?.message || "Failed to save data",
    })
  }
}




const handleDeleteMasterSales = async (sales) => {
  const { isConfirmed } = await Swal.fire({
    title: 'Delete Employee?',
    html: `Employee <b>"${sales.nik}"</b> will be permanently deleted.`,
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

    //  ID yang benar
    await dataMastersales.deleteMasterSales(sales.id_employee)

    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      text: 'Employee has been deleted successfully.',
      timer: 1500,
      showConfirmButton: false,
    })

    //  Refresh table
    dataMastersales.fetchMasterSalesData(
      dataMastersales.buildUrl()
    )

  } catch (e) {
    console.error(e)

    Swal.fire({
      icon: 'error',
      title: 'Failed',
      text:
        e.response?.data?.message ||
        'Failed to delete employee.',
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

      
          <!-- Loading permission -->
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

      <!-- Page Body -->
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
    <button class="btn btn-warning btn-sm d-flex align-items-center ms-auto" @click="dataMastersales.resetFilters">
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
                    v-model.number="dataMastersales.pagination.per_page" 
                     @change="dataMastersales.changePageSize"
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
                  class="btn btn-primary btn-sm" data-bs-toggle="modal"
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
                    v-model="dataMastersales.searchMasterSalesData">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" v-model="dataMastersales.sort.column" @change="dataMastersales.changeSorting">
                    <option value="nik">By Name</option>
                    <option value="created_at">By Created Date</option>
                    </select> 
                    <select class="form-select w-auto" v-model="dataMastersales.sort.direction" @change="dataMastersales.changeSorting">
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
                    <th style="width:2%">No</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Username / NIK</th>
                    <th>Email</th>
                    <th>Group Company & Photo</th>
                    <th>Division</th>
                    <th style="width:10%">Actions</th>
                </tr>
                </thead>

                 <tbody v-if="dataMastersales.loadingMasterSalesData">
                <tr>
                  <td colspan="8" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>
              </tbody>

              <!-- EMPTY DATA -->
              <tbody v-else-if="dataMastersales.MasterSalesData.length === 0">
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
                            sales master data not found.
                          </p>
                        </div>
                      </td>
                    </tr>
            </tbody>

              <tbody v-else>
                      <template
                        v-for="(sales, index) in dataMastersales.MasterSalesData"
                        :key="sales.id_employee"
                      >
                        <!-- ================= SALES / EMPLOYEE ================= -->
                        <tr class="fw-bold bg-light">
                          <td>{{ index + 1 }}</td>

                          <td>
                            <span class="badge bg-primary">Employee</span>
                          </td>

                          <td>{{ sales.user?.fullname }}</td>

                          <td>{{ sales.nik }}</td>

                          <td>{{ sales.user?.email }}</td>

                          <td>{{ sales.user?.group?.name_group }}</td>
                          <td>{{ sales.user?.division?.name_division }}</td>

                          <td>
                                            <button
                                             v-if="permission?.can_update"
                                              class="btn btn-outline-warning btn-sm me-1"
                                              data-bs-toggle="modal"
                                              data-bs-target="#modal-add-data"
                                              @click="openEditModal(sales)"
                                            >
                                              <i class="fa fa-edit"></i>
                                            </button>


                                           <button
                                                 v-if="permission?.can_delete"
                                                class="btn btn-outline-danger btn-sm me-1"
                                                @click="handleDeleteMasterSales(sales)"
                                                :disabled="dataMastersales.deletingMasterSalesData"
                                                title="Delete Employee"
                                              >
                                                <i class="fa fa-trash"></i>
                                              </button>


                                            <button class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#employeeDetailModal"
                                                  @click="showDetail(sales.id_employee)"
                                              >
                                                <i class="fa fa-eye"></i> 
                                            </button>
                          </td>
                        </tr>

                        <!-- ================= USER (CHILD) ================= -->
                        <tr class="text-muted">
                          <td></td>
                          <td>
                            ↳ <span class="badge bg-secondary">User</span>
                          </td>

                          <td class="ps-4">{{ sales.user?.fullname }}</td>

                          <td>{{ sales.user?.username }}</td>

                          <td>{{ sales.user?.email }}</td>

                          <td>
                           
                             <td>
                              <img
                                :src="`/storage/users/${sales.user?.image}`"
                                alt="User Image"
                                width="40"
                                height="40"
                                class="rounded float-start"
                              />
                            </td>
                          </td>
                        </tr>
                      </template>
              </tbody>



              </table>
            </div>
          </div>

          <!-- Card: Pagination -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <button class="btn btn-danger btn-sm" 
                 :disabled="!dataMastersales.pagination.prev_page_url || dataMastersales.loadingMenus"
                  @click="dataMastersales.fetchMasterSalesData(dataMastersales.pagination.prev_page_url)">
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                    <span class="badge border text-secondary px-3 py-2"> {{ dataMastersales.MasterSalesData.length }} data | on page {{ dataMastersales.pagination.current_page }}</span>
                    <span class="badge border text-secondary px-3 py-2">Total: {{ dataMastersales.pagination.total }} data</span>
                </div>
  
                <button class="btn btn-danger btn-sm"
               :disabled="!dataMastersales.pagination.next_page_url || dataMastersales.loadingMasterSalesData"
                  @click="dataMastersales.fetchMasterSalesData(dataMastersales.pagination.next_page_url)">
                    Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>

        </div>
      </div>

     
    </div>




  <!-- Code Modal: Detail Data -->
<!-- ================= MODAL: DETAIL EMPLOYEE ================= -->
<div
  class="modal modal-blur fade"
  id="employeeDetailModal"
  tabindex="-1"
  aria-hidden="true"
>
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">Detail Employee</h5>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="modal"
        ></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <!-- LOADING -->
        <div
          v-if="dataMastersales.loading"
          class="d-flex justify-content-center align-items-center"
          style="min-height:150px;"
        >
          <div class="spinner-border text-secondary"></div>
        </div>

        <!-- CONTENT -->
        <div v-else-if="dataMastersales.MasterSalesDataDetail">

          <!-- USER INFO -->
          <div class="d-flex align-items-center mb-4">
            <img
              :src="dataMastersales.MasterSalesDataDetail.user?.image
                ? `/storage/users/${dataMastersales.MasterSalesDataDetail.user.image}`
                : '/images/default-user.png'"
              class="rounded-circle me-3"
              width="70"
              height="70"
              style="object-fit:cover"
            />

            <div>
              <h5 class="mb-1">
                {{ dataMastersales.MasterSalesDataDetail.user?.fullname }}
              </h5>
              <div class="text-muted">
                {{ dataMastersales.MasterSalesDataDetail.user?.email }}
              </div>

              <span
                class="badge mt-1"
                :class="dataMastersales.MasterSalesDataDetail.user?.is_active
                  ? 'bg-success'
                  : 'bg-danger'"
              >
                {{ dataMastersales.MasterSalesDataDetail.user?.is_active
                  ? 'ACTIVE'
                  : 'INACTIVE' }}
              </span>
            </div>
          </div>

          <hr />

          <!-- EMPLOYEE DATA -->
          <div class="row g-3">
            <div class="col-md-6">
              <strong>NIK</strong>
              <div>{{ dataMastersales.MasterSalesDataDetail.nik }}</div>
            </div>

            <div class="col-md-6">
              <strong>Status Employee</strong>
              <div>
                <span class="badge" :class="statusBadgeClass">
                  {{ statusBadgeLabel }}
                </span>
              </div>
            </div>


            <div class="col-md-6">
              <strong>Place of birth</strong>
              <div>{{ dataMastersales.MasterSalesDataDetail.tempat_lahir }}</div>
            </div>

            <div class="col-md-6">
              <strong>Date Of Birth</strong>
              <div>{{ dataMastersales.formatDate(
                dataMastersales.MasterSalesDataDetail.tanggal_lahir
              ) }}</div>
            </div>

            <div class="col-md-6">
              <strong>Gender</strong>
              <div>
                {{ dataMastersales.MasterSalesDataDetail.jenis_kelamin === 'L'
                  ? 'Male'
                  : 'Female' }}
              </div>
            </div>

            <div class="col-md-6">
              <strong>No. HP</strong>
              <div>{{ dataMastersales.MasterSalesDataDetail.no_hp }}</div>
            </div>

            <div class="col-md-6">
              <strong>Date Join</strong>
              <div>{{ dataMastersales.formatDate(
                dataMastersales.MasterSalesDataDetail.tanggal_masuk
              ) }}</div>
            </div>

            <div class="col-md-6">
              <strong>Division</strong>
              <div>
                {{ dataMastersales.MasterSalesDataDetail.user?.division?.name_division ?? '-' }}
              </div>
            </div>

            <div class="col-md-6">
              <strong>Office</strong>
              <div>
                {{ dataMastersales.MasterSalesDataDetail.user?.office?.office_name ?? '-' }}


              </div>
            </div>

            <div class="col-md-6">
              <strong>attendance mode</strong>
              <div>
                {{ dataMastersales.MasterSalesDataDetail.attendance_mode }}
              </div>
            </div>


            <div class="col-md-6">
              <strong>Group</strong>
              <div>
                {{ dataMastersales.MasterSalesDataDetail.user?.group?.name_group ?? '-' }}
              </div>
            </div>

            <div class="col-12">
              <strong>Address</strong>
              <div>{{ dataMastersales.MasterSalesDataDetail.alamat }}</div>
            </div>
          </div>
        </div>

        <!-- EMPTY -->
        <div v-else class="text-center text-muted">
          Employee data is not available
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button
          type="button"
          class="btn btn-secondary"
          data-bs-dismiss="modal"
        >
          Close
        </button>
      </div>

    </div>
  </div>
</div>





    <!-- Code Modal: Add Data -->
<div class="modal modal-blur fade" id="modal-add-data" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">
          {{ editMasterSalesId ? 'Edit Employee' : 'Add Employee' }}
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- FORM -->
      <form @submit.prevent="saveMasterSales">

        <!-- Body -->
        <div class="modal-body">
          <div class="row g-3">

            <!-- USER -->
            <div class="col-md-6">
              <label class="form-label">User</label>
              <div
                class="multiselect-wrapper"
                :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.user_id }"
              >
                <Multiselect
                  v-model="form.user_id"
                  :options="dataMastersales.userSelect"
                  label="fullname"
                  valueProp="id_user"
                  placeholder="Select User..."
                  :searchable="true"
                  :loading="dataMastersales.loadingSelect"
                />
              </div>
              <div
              v-if="dataMastersales.errorMasterSalesData?.user_id"
              class="invalid-feedback d-block"
            >
              {{ dataMastersales.errorMasterSalesData.user_id[0] }}
          </div>
            </div>

            <!-- NIK -->
            <div class="col-md-6">
              <label class="form-label">NIK <small class="text-warning">(NIK cannot be the same for every employee)</small></label>
              <input
                type="text"
                class="form-control"
                v-model="form.nik"
                placeholder="EMP2025xxxx"
                 :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.nik }"
              />
              <div
                  v-if="dataMastersales.errorMasterSalesData?.nik"
                  class="invalid-feedback"
                >
                  {{ dataMastersales.errorMasterSalesData.nik[0] }}
                </div>
            </div>

            <!-- TEMPAT LAHIR -->
            <div class="col-md-6">
              <label class="form-label">Place of birth</label>
              <input type="text" class="form-control" 
                v-model="form.tempat_lahir"
               :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.tempat_lahir }" />
                 <div
                  v-if="dataMastersales.errorMasterSalesData?.tempat_lahir"
                  class="invalid-feedback"
                >
                  {{ dataMastersales.errorMasterSalesData.tempat_lahir[0] }}
                </div>
            </div>

            <!-- TANGGAL LAHIR -->
            <div class="col-md-6">
              <label class="form-label">Date of birth</label>
              <input type="date" class="form-control" v-model="form.tanggal_lahir" 
              :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.tanggal_lahir }" />
               <div
                  v-if="dataMastersales.errorMasterSalesData?.tanggal_lahir"
                  class="invalid-feedback"
                >
                  {{ dataMastersales.errorMasterSalesData.tanggal_lahir[0] }}
                </div>
            </div>

            <!-- JENIS KELAMIN -->
          <div class="col-md-6">
              <label class="form-label">Gender</label>
            <div
                class="multiselect-wrapper"
                :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.jenis_kelamin }"
              >
               <Multiselect
                v-model="form.jenis_kelamin"
                :options="dataMastersales.statusGender"
                label="label"
                valueProp="value"
                placeholder="Select Gender"
             />
           </div>
          <div
              v-if="dataMastersales.errorMasterSalesData?.jenis_kelamin"
              class="invalid-feedback d-block"
            >
              {{ dataMastersales.errorMasterSalesData.jenis_kelamin[0] }}
            </div>
            </div>

            <!-- NO HP -->
            <div class="col-md-6">
              <label class="form-label">No. Mobile Phone</label>
              <input type="text" class="form-control" v-model="form.no_hp"
               :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.no_hp }"  />
              <div
                  v-if="dataMastersales.errorMasterSalesData?.no_hp"
                  class="invalid-feedback"
                >
                  {{ dataMastersales.errorMasterSalesData.no_hp[0] }}
                </div>
            </div>

            <!-- TANGGAL MASUK -->
            <div class="col-md-6">
              <label class="form-label">Date Join</label>
              <input type="date" class="form-control" v-model="form.tanggal_masuk"
               :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.tanggal_masuk }"  />
               <div
                  v-if="dataMastersales.errorMasterSalesData?.tanggal_masuk"
                  class="invalid-feedback"
                >
                  {{ dataMastersales.errorMasterSalesData.tanggal_masuk[0] }}
                </div>
            </div>

            <!-- STATUS -->
            <div class="col-md-6">
              <label class="form-label">Employee Status</label>
             <div
                class="multiselect-wrapper"
                :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.status_karyawan }"
              >
               <Multiselect
                v-model="form.status_karyawan"
                :options="dataMastersales.statusJobs"
                label="label"
                valueProp="value"
                placeholder="Select Status Jobs"
          />
            </div>
            <div
              v-if="dataMastersales.errorMasterSalesData?.status_karyawan"
              class="invalid-feedback d-block"
            >
              {{ dataMastersales.errorMasterSalesData.status_karyawan[0] }}
            </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Office Employee</label>
            <div
                class="multiselect-wrapper"
                :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.office_id }"
              >
              <Multiselect
                  :key="form.office_id"
                  v-model="form.office_id"
                  :options="dataMastersales.officeSelect"
                  label="office_name"
                  valueProp="id"
                  placeholder="Select Office..."
                  :searchable="true"
                />
           </div>
          <div
              v-if="dataMastersales.errorMasterSalesData?.office_id"
              class="invalid-feedback d-block"
            >
              {{ dataMastersales.errorMasterSalesData.office_id[0] }}
            </div>
            </div>


            <div class="col-md-6">
              <label class="form-label">Attendance Mode</label>
            <div
                class="multiselect-wrapper"
                :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.attendance_mode }"
              >
               <Multiselect
                v-model="form.attendance_mode"
                :options="dataMastersales.AttendanceMode"
                label="label"
                valueProp="value"
                placeholder="Select Attendance Mode"
             />
           </div>
              <div
                  v-if="dataMastersales.errorMasterSalesData?.attendance_mode"
                  class="invalid-feedback d-block"
                >
                  {{ dataMastersales.errorMasterSalesData.attendance_mode[0] }}
              </div>
            </div>

            <!-- ALAMAT -->
            <div class="col-12">
              <label class="form-label">Address</label>
              <textarea class="form-control" rows="3" v-model="form.alamat" :class="{ 'is-invalid': dataMastersales.errorMasterSalesData?.alamat }" ></textarea>
               <div
                  v-if="dataMastersales.errorMasterSalesData?.alamat"
                  class="invalid-feedback"
                >
                  {{ dataMastersales.errorMasterSalesData.alamat[0] }}
                </div>
            </div>

          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
        <button
            type="button"
            class="btn btn-link"
            data-bs-dismiss="modal"
            :disabled="
              dataMastersales.savingMasterSalesData ||
              dataMastersales.updatingMasterSalesData
            "
          >
            Cancel
        </button>


          <!--  SUBMIT BUTTON -->
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="
              dataMastersales.savingMasterSalesData ||
              dataMastersales.updatingMasterSalesData
            "
          >
            <span
              v-if="
                dataMastersales.savingMasterSalesData ||
                dataMastersales.updatingMasterSalesData
              "
            >
              <span class="spinner-border spinner-border-sm me-1"></span>
              Processing...
            </span>

            <span v-else>
              <i class="fas fa-save me-1"></i>
              {{ editMasterSalesId ? 'Edit Employee' : 'Save Employee' }}
            </span>
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