<script setup>
import { ref, reactive, onMounted , watch, computed } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useSettingAppStore } from '../../../../stores/settingAppStore';
import { useMenuStore } from "@/stores/menuStore";
import { toasts } from "@/utils/toasts"
import { useRoute, useRouter } from "vue-router";
import Swal from 'sweetalert2'
const PagesTitle = 'Setting Application';


const dataSettingApp = useSettingAppStore();
const menuStore = useMenuStore();
const route = useRoute();
const router = useRouter();

const permission = ref(null);
const loadingPermission = ref(true);


const editSettingAppId = ref(null)
const errors = reactive({})


const getLogoImage = (image) => {
  if (!image || image === 'logo.png' || image === 'logo.png') {
    return '/storage/app-setting/logo.png' // sesuaikan lokasi default
  }
  // kalau sudah ada users/
  if (image.startsWith('logo/')) {
    return `/storage/${image}`
  }
  // kalau hanya nama file
  return `/storage/app-setting/${image}`
}


const getLogoSmallImage = (image) => {
  if (!image || image === 'logoSm.png' || image === 'logoSm.png') {
    return '/storage/app-setting/logoSm.png' // sesuaikan lokasi default
  }
  // kalau sudah ada users/
  if (image.startsWith('logo/')) {
    return `/storage/${image}`
  }
  // kalau hanya nama file
  return `/storage/app-setting/${image}`
}


const getPavicon = (image) => {
  if (!image || image === 'favicon.ico' || image === 'favicon.ico') {
    return '/storage/app-setting/favicon.ico' // sesuaikan lokasi default
  }
  // kalau sudah ada users/
  if (image.startsWith('logo/')) {
    return `/storage/${image}`
  }
  // kalau hanya nama file
  return `/storage/app-setting/${image}`
}

onMounted(async () => {
  try {
    if (!localStorage.getItem("auth_token")) {
      alert("Silakan login terlebih dahulu!");
      router.push('/login');
      return;
    }

    await dataSettingApp.fetchSettingApp(dataSettingApp.buildUrl());
    await menuStore.fetchMenus();

    permission.value = menuStore.getPermission(route.path);
  } catch (error) {
    console.error(error);
  } finally {
    loadingPermission.value = false;
  }
});

watch(
  () => dataSettingApp.searchSettingApp,
  dataSettingApp.searchWithDelay
);



const showDetailSettingApp = async (settingAppDataId) => {
  try {
    await dataSettingApp.detailSettingApp(settingAppDataId)
  } catch (e) {
    console.error(e)
  }
}


// start code store

/* =========================
   FORM STATE
========================= */
const form = reactive({
  app_name: '',
  app_short_name: '',
  app_tagline: '',

  app_logo: null,
  app_logo_small: null,
  favicon: null,

  primary_color: '#4f46e5',
  secondary_color: '#22c55e',
  sidebar_color: '#ffffff',
  navbar_color: '#ffffff',

  footer_text: '',
  footer_license_url: '',
  footer_documentation_url: '',
  footer_support_url: '',

  version: '0.0.0',
  environment: 'development',
})


const resetErrors = () => {
  Object.keys(errors).forEach(key => {
    delete errors[key]
  })
}


const openCreateModal = () => {
  editSettingAppId.value = null

  resetForm()
   resetErrors()

  const modal = new bootstrap.Modal(
    document.getElementById('modal-add-data')
  )
  modal.show()
}



const openEditModal = (row) => {
  editSettingAppId.value = row.id

  Object.assign(form, {
    app_name: row.app_name,
    app_short_name: row.app_short_name,
    app_tagline: row.app_tagline,

    primary_color: row.primary_color,
    secondary_color: row.secondary_color,
    sidebar_color: row.sidebar_color,
    navbar_color: row.navbar_color,

    footer_text: row.footer_text,
    footer_license_url: row.footer_license_url,
    footer_documentation_url: row.footer_documentation_url,
    footer_support_url: row.footer_support_url,

    version: row.version,
    environment: row.environment,
  })

  preview.app_logo = getLogoImage(row.app_logo)
  preview.app_logo_small = getLogoSmallImage(row.app_logo_small)
  preview.favicon = getPavicon(row.favicon)

  new bootstrap.Modal(
    document.getElementById('modal-add-data')
  ).show()
}

const clearError = (field) => {
  if (errors[field]) {
    delete errors[field]
  }
}



const resetForm = () => {
  form.app_name = ''
  form.app_short_name = ''
  form.app_tagline = ''
  form.app_logo = null
  form.app_logo_small = null
  form.favicon = null
  form.primary_color = '#4f46e5'
  form.secondary_color = '#22c55e'
  form.sidebar_color = '#ffffff'
  form.navbar_color = '#ffffff'
  form.footer_text = ''
  form.footer_license_url = ''
  form.footer_documentation_url = ''
  form.footer_support_url = ''
  form.version = '0.0.0'
  form.environment = 'development'
}




/* =========================
   IMAGE PREVIEW
========================= */
const preview = reactive({
  app_logo: 'https://placehold.co/150x80?text=Logo',
  app_logo_small: 'https://placehold.co/80x80?text=Small',
  favicon: 'https://placehold.co/40x40?text=Icon',
})

const onFileChange = (event, field) => {
  const file = event.target.files[0]
  if (!file) return

  form[field] = file
  preview[field] = URL.createObjectURL(file)
}



const isLoading = computed(() => {
  return (
    dataSettingApp.savingSettingApp ||
    dataSettingApp.updatingSettingApp
  )
})



const saveSettingApp = async () => {
  console.log('tekan')

  if (
    dataSettingApp.savingSettingApp ||
    dataSettingApp.updatingSettingApp
  ) return

  try {
    resetErrors()

    const payload = {
      ...form,
      app_logo: form.app_logo,
      app_logo_small: form.app_logo_small,
      favicon: form.favicon,
    }

    const isEdit = !!editSettingAppId.value

    if (isEdit) {
      await dataSettingApp.updateSettingApp(
        editSettingAppId.value,
        payload
      )
    } else {
      await dataSettingApp.storeSettingApp(payload)
    }

    bootstrap.Modal.getInstance(
      document.getElementById('modal-add-data')
    )?.hide()

    toasts.fire({
      icon: 'success',
      title: isEdit
        ? 'App Settings successfully updated'
        : 'Settings App added successfully',
    })

  } catch (e) {
    if (e.response?.status === 422) {
      Object.assign(errors, e.response.data.errors)
    } else {
      Swal.fire(
        'Error',
        e.response?.data?.message || 'Terjadi kesalahan server',
        'error'
      )
    }
  }
}


const handleDelete = async (id) => {
  const confirm = await Swal.fire({
    title: 'Delete Setting App?',
    text: 'This action cannot be undone',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
  })

  if (!confirm.isConfirmed) return

  try {
    await dataSettingApp.deleteSettingApp(id)

    Swal.fire({
      icon: 'success',
      title: 'Deleted',
      text: 'Setting App successfully deleted',
      timer: 1500,
      showConfirmButton: false,
    })

  } catch (err) {
    Swal.fire(
      'Error',
      err.response?.data?.message || 'Failed to delete data',
      'error'
    )
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
          <div class="dropdown d-inline-block me-2">
              <button class="btn btn-warning btn-sm d-flex align-items-center ms-auto" @click="dataSettingApp.resetFilters">
                <i class="fas fa-undo"></i> Reset
              </button>
          </div>
        </div>
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
                     v-model.number="dataSettingApp.pagination.per_page" 
                     @change="dataSettingApp.changePageSize"
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
                @click="openCreateModal">
                    <i class="fa fa-plus"></i> Add Data
                    </button>
                </div>


              <!-- Kanan -->
             <div class="d-flex flex-column gap-3 align-items-end" style="min-width:300px;">
                <!-- Pencarian -->
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Searching...." v-model="dataSettingApp.searchSettingApp">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>

                <!-- Urutan -->
                <div class="d-flex gap-2 align-items-center">
                    <label class="mb-0 fw-semibold">Sort:</label>
                    <select class="form-select w-auto" v-model="dataSettingApp.sort.column" @change="dataSettingApp.changeSorting">
                    <option value="fullname">By Name</option>
                    <option value="created_at">By Created Date</option>
                    </select>
                    <select class="form-select w-auto" v-model="dataSettingApp.sort.direction" @change="dataSettingApp.changeSorting">
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
                    <th>App Name</th>
                    <th>App Short Name</th>
                    <th>Tagline</th>
                    <th>App Logo</th>
                    <th>App Logo Small</th>
                    <th>Pavicon</th>
                    <th>Version</th>
                    <th style="width: 8%;">Actions</th>
                  </tr>
                </thead>



                <!-- LOADING DATA -->
              <tbody v-if="dataSettingApp.loadingSettingApp">
                <tr>
                  <td colspan="9" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                  </td>
                </tr>
              </tbody>

              <!-- EMPTY DATA -->
              <tbody v-else-if="dataSettingApp.settingAppData.length === 0">
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
                             data not found.
                          </p>
                        </div>
                      </td>
                    </tr>
            </tbody>

                <tbody v-else>
                <tr
                    v-for="(mn, index) in dataSettingApp.settingAppData"
                    :key="mn.id"
                  >
                    <td>
                      {{
                        index + 1 +
                        dataSettingApp.pagination.per_page *
                          (dataSettingApp.pagination.current_page - 1)
                      }}.
                    </td>
                    <td>{{ mn.app_name }}</td>
                    <td>{{ mn.app_short_name }}</td>
                    <td>{{ mn.app_tagline }}</td>
                    <td><img
                      :src="getLogoImage(mn.app_logo)"
                      alt="logo"
                      width="100"
                     class="rounded mx-auto d-block"
                    />
                    </td>

                    <td><img
                      :src="getLogoSmallImage(mn.app_logo_small)"
                      alt="logo"
                      width="100"
                     class="rounded mx-auto d-block"
                    />
                   </td>

                  <td>
                      <img
                      :src="getPavicon(mn.favicon)"
                      alt="logo"
                      width="25"
                     class="rounded mx-auto d-block"
                    />
                  </td>
                  <td>{{ mn.version }}</td>
                    <td>
                      <!-- UPDATE -->
                      <button
                       v-if="!loadingPermission && permission?.can_update"
                        class="btn btn-outline-warning btn-sm me-1"
                        @click="openEditModal(mn)"
                      >
                      <i class="fa fa-edit"></i>
                      </button>
                      <!-- DELETE -->
                     <button
                        v-if="!loadingPermission && permission?.can_delete"
                        class="btn btn-outline-danger btn-sm me-1"
                        :disabled="dataSettingApp.deletingSettingApp"
                        @click="handleDelete(mn.id)"
                      >
                        <span
                          v-if="dataSettingApp.deletingSettingApp"
                          class="spinner-border spinner-border-sm"
                        ></span>

                        <i v-else class="fa fa-trash"></i>
                      </button>


                      <!-- DETAIL -->
                     <button
                      v-if="!loadingPermission && permission?.can_view"
                        class="btn btn-outline-primary btn-sm me-1"
                        data-bs-toggle="modal"
                        data-bs-target="#settingAppDetailModal"
                        @click="showDetailSettingApp(mn.id)"
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
                  :disabled="!dataSettingApp.pagination.prev_page_url || dataSettingApp.loadingSettingApp"
                  @click="dataSettingApp.fetchSettingApp(dataSettingApp.pagination.prev_page_url)">
                <i class="fa-solid fa-circle-chevron-left"
                ></i> Prev
              </button>
  
                <div class="mx-2 d-flex flex-column flex-sm-row align-items-center gap-1">
                    <span class="badge border text-secondary px-3 py-2"> {{ dataSettingApp.settingAppData.length }} data | on page {{ dataSettingApp.pagination.current_page }}</span>
                    <span class="badge border text-secondary px-3 py-2">Total:  {{ dataSettingApp.pagination.total }} data</span>
                </div>
  
                <button class="btn btn-danger btn-sm"
                :disabled="!dataSettingApp.pagination.next_page_url || dataSettingApp.loadingSettingApp"
                  @click="dataSettingApp.fetchSettingApp(dataSettingApp.pagination.next_page_url)"
               >
                    Next <i class="fa-solid fa-circle-chevron-right"></i>
                </button>
            </div>
          </div>

        </div>
      </div>

     
    </div>





<!-- Modal: Detail Setting App -->
<div
  class="modal fade"
  id="settingAppDetailModal"
  tabindex="-1"
  aria-hidden="true"
>
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title">Detail Setting App</h5>
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
          v-if="dataSettingApp.loading"
          class="d-flex justify-content-center align-items-center"
          style="min-height: 200px"
        >
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <!-- Content -->
        <div v-else-if="dataSettingApp.settingAppDetail" class="row">
          <div class="col-12">
            <ul class="list-group list-group-flush">


              <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between">
                  <span class="text-primary fw-bold">Detail</span>
              
                  <strong>
                    <small class="text-primary fw-bold"> Main App</small>
                  </strong>
                  
                </li>
              </ul>
                <small class="text-primary"><hr class="border border-primary opacity-100">
               </small>


               <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Environment</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.environment }}
                </span>
              </li>

              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">App Name</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.app_name }}
                </span>
              </li>

              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">App short Name</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.app_short_name || '-' }}
                </span>
              </li>

              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">App Tagline</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.app_tagline || '-' }}
                </span>
              </li>

              
              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Created At</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.formatDate(dataSettingApp.settingAppDetail.created_at) }}
                </span>
              </li>

               <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Update At</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.formatDate(dataSettingApp.settingAppDetail.updated_at) }}
                </span>
              </li>

             
              <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between">
                  <span class="text-primary fw-bold">Detail</span>
              
                  <strong>
                    <small class="text-primary fw-bold"> Style App</small>
                  </strong>
                  
                </li>
              </ul>

               <small class="text-primary"><hr class="border border-primary opacity-100">
               </small>
            
              
              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Primary Color</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.primary_color || '-' }}
                </span>
              </li>

              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Secondary Color</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.secondary_color || '-' }}
                </span>
              </li>

              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Sidebar Color</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.sidebar_color || '-' }}
                </span>
              </li>

              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Navbar Color</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.navbar_color || '-' }}
                </span>
              </li>


              <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between">
                  <span class="text-primary fw-bold">Detail</span>
              
                  <strong>
                    <small class="text-primary fw-bold"> Footer App</small>
                  </strong>
                  
                </li>
              </ul>

               <small class="text-primary"><hr class="border border-primary opacity-100">
               </small>
            

               <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Footer Text</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.footer_text || '-' }}
                </span>
              </li>


              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Footer License Url</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.footer_license_url || '-' }}
                </span>
              </li>

              <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Footer License Url</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.footer_documentation_url || '-' }}
                </span>
              </li>

               <li class="list-group-item d-flex justify-content-between">
                <span class="fw-medium text-muted">Footer License Url</span>
                <span class="fw-semibold">
                  {{ dataSettingApp.settingAppDetail.footer_support_url || '-' }}
                </span>
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





<!-- Code Modal: Add Data -->
     <!-- MODAL -->
  <div class="modal fade" id="modal-add-data" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">

        <!-- HEADER -->
        <div class="modal-header">
          <h5 class="modal-title">Tambah App Setting</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body">
          <div class="row g-3">

            <!-- App Name -->
            <div class="col-md-6">
              <label class="form-label">App Name</label>
              <input type="text" class="form-control" 
                v-model="form.app_name"
                @input="clearError('app_name')"
                :class="{ 'is-invalid': errors.app_name }" >
                <div v-if="errors.app_name" class="invalid-feedback">
                {{ errors.app_name[0] }}
              </div>
            </div>

            <!-- App Short Name -->
            <div class="col-md-6">
              <label class="form-label">App Short Name</label>
              <input type="text" class="form-control"
               v-model="form.app_short_name"
                @input="clearError('app_short_name')"
                :class="{ 'is-invalid': errors.app_short_name }">
              <div v-if="errors.app_short_name" class="invalid-feedback">
                {{ errors.app_short_name[0] }}
              </div>
            </div>

            <!-- Tagline -->
            <div class="col-md-12">
              <label class="form-label">App Tagline</label>
              <input type="text" class="form-control"
               v-model="form.app_tagline"
               @input="clearError('app_tagline')"
                :class="{ 'is-invalid': errors.app_tagline }">
                 <div v-if="errors.app_tagline" class="invalid-feedback">
              {{ errors.app_tagline[0] }}
            </div>
            </div>
           
            <div class="row mt-3">
            <!-- LOGO -->
            <div class="col-md-4">
              <label class="form-label">App Logo</label>
              <img :src="preview.app_logo" class="img-thumbnail mb-2" style="max-height:80px">
              <input type="file" class="form-control" accept="image/*"
                @change="onFileChange($event, 'app_logo')"
                :class="{ 'is-invalid': errors.app_logo }">
                <div v-if="errors.app_logo" class="invalid-feedback">
                  {{ errors.app_logo[0] }}
                </div>
            </div>

            <div class="col-md-4 text-center">
              <label class="form-label">App Logo Small</label>
              <img :src="preview.app_logo_small" class="img-thumbnail mb-2" style="max-height:80px">
              <input type="file" class="form-control" accept="image/*"
                @change="onFileChange($event, 'app_logo_small')"
                :class="{ 'is-invalid': errors.app_logo_small }">
                <div v-if="errors.app_logo" class="invalid-feedback">
                  {{ errors.app_logo_small[0] }}
                </div>
            </div>

            <div class="col-md-4 text-center">
              <label class="form-label">Favicon</label>
              <img :src="preview.favicon" class="img-thumbnail mb-2" style="max-height:40px">
              <input type="file" class="form-control" accept="image/*"
                @change="onFileChange($event, 'favicon')"
                :class="{ 'is-invalid': errors.favicon }">
                 <div v-if="errors.favicon" class="invalid-feedback">
                  {{ errors.favicon[0] }}
                </div>
            </div>

            </div>

            <!-- COLORS -->
            <div class="col-md-3">
              <label class="form-label">Primary Color</label>
              <input type="color" class="form-control form-control-color"
                v-model="form.primary_color"
                @input="clearError('primary_color')"
                :class="{ 'is-invalid': errors.primary_color }">
                <div v-if="errors.primary_color" class="invalid-feedback">
                  {{ errors.primary_color[0] }}
                </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Secondary Color</label>
              <input type="color" class="form-control form-control-color"
              :class="{ 'is-invalid': errors.secondary_color }"
                v-model="form.secondary_color">
                <div v-if="errors.secondary_color" class="invalid-feedback">
                  {{ errors.secondary_color[0] }}
                </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Sidebar Color</label>
              <input type="color" class="form-control form-control-color"
                 :class="{ 'is-invalid': errors.sidebar_color }"
                 v-model="form.sidebar_color">
                 <div v-if="errors.sidebar_color" class="invalid-feedback">
                  {{ errors.sidebar_color[0] }}
                </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Navbar Color</label>
              <input type="color" class="form-control form-control-color"
                v-model="form.navbar_color"
                 :class="{ 'is-invalid': errors.navbar_color }">
                 <div v-if="errors.navbar_color" class="invalid-feedback">
                  {{ errors.navbar_color[0] }}
                </div>
            </div>

            <!-- FOOTER -->
            <div class="col-md-12">
              <label class="form-label">Footer Text</label>
              <input type="text" class="form-control" 
              v-model="form.footer_text"
              @input="clearError('footer_text')"
              :class="{ 'is-invalid': errors.footer_text }">
               <div v-if="errors.footer_text" class="invalid-feedback">
                {{ errors.footer_text[0] }}
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">License URL</label>
              <input type="url" class="form-control" 
               v-model="form.footer_license_url"
               @input="clearError('footer_license_url')"
               :class="{ 'is-invalid': errors.footer_license_url }">
              <div v-if="errors.footer_license_url" class="invalid-feedback">
                {{ errors.footer_license_url[0] }}
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Documentation URL</label>
              <input type="url" class="form-control" 
               v-model="form.footer_documentation_url"
               @input="clearError('footer_documentation_url')"
               :class="{ 'is-invalid': errors.footer_documentation_url }">
               <div v-if="errors.footer_documentation_url" class="invalid-feedback">
                {{ errors.footer_documentation_url[0] }}
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Support URL</label>
              <input type="url" class="form-control" 
              v-model="form.footer_support_url"
              @input="clearError('footer_support_url')"
               :class="{ 'is-invalid': errors.footer_support_url }">
              <div v-if="errors.footer_support_url" class="invalid-feedback">
                {{ errors.footer_support_url[0] }}
              </div>
            </div>

            <!-- VERSION -->
            <div class="col-md-6">
              <label class="form-label">Version</label>
              <input type="text" class="form-control" 
              v-model="form.version"
              @input="clearError('version')"
               :class="{ 'is-invalid': errors.version }">
                <div v-if="errors.version" class="invalid-feedback">
                {{ errors.version[0] }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Environment</label>
              <select class="form-select" v-model="form.environment" :class="{ 'is-invalid': errors.environment }">
                <option value="development">Development</option>
                <option value="staging">Staging</option>
                <option value="production">Production</option>
              </select>
                <div class="invalid-feedback" v-if="errors.environment">
              {{ errors.environment[0] }}
            </div>
            </div>

          </div>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer">
          <button class="btn btn-link" data-bs-dismiss="modal">Batal</button>
        <!-- <button
          type="button"
          class="btn btn-primary"
          :disabled="dataSettingApp.savingSettingApp === true"
          @click.prevent="saveSettingApp()"
        >
          <i class="fas fa-save me-1"></i>
          {{ editSettingAppId ? 'Update' : 'Simpan' }}
        </button> -->
        <button
          type="button"
          class="btn btn-primary d-flex align-items-center gap-2"
          :disabled="isLoading"
          @click.prevent="saveSettingApp"
        >
          <!-- LOADING -->
          <span
            v-if="isLoading"
            class="spinner-border spinner-border-sm"
            role="status"
            aria-hidden="true"
          ></span>

          <!-- ICON NORMAL -->
          <i v-else class="fas fa-save"></i>

          <!-- TEXT -->
          <span>
            {{
              isLoading
                ? (editSettingAppId ? 'Updating...' : 'Saving...')
                : (editSettingAppId ? 'Update' : 'Simpan')
            }}
          </span>
        </button>


        </div>

      </div>
    </div>
  </div>



  </backendLayouts>
</template>


<style scoped>


</style>