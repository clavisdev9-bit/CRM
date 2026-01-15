<script setup>
import { ref, onMounted, computed  } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue"
import { useLeadsStore } from '../../../../stores/leadsStore'
import { useMenuStore } from "@/stores/menuStore"
import { useRoute, useRouter } from "vue-router"
import Swal from 'sweetalert2'
const PagesTitle = 'Data Leads'

const dataLeads = useLeadsStore()
const menuStore = useMenuStore()
const route = useRoute()
const router = useRouter()

const permission = ref(null)
const loadingPermission = ref(true)

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

                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-add-data">
                       <i class="fa-regular fa-calendar-plus"></i> Add Data Bulk
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
                        class="bg-light fw-bold text-warning">
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
                    <td>{{ index + 1 }}</td>

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
                      <!-- <button class="btn btn-outline-primary btn-sm me-1"  @click="openDetail(lead.id)">
                        <i class="fa fa-eye"></i>
                      </button> -->
                      <button
  class="btn btn-outline-primary btn-sm me-1"
  @click="openDetail(lead.id)"
>
  <i class="fa fa-eye"></i>
</button>

                      <button class="btn btn-outline-warning btn-sm me-1">
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
      
      



        
        




 <div class="modal fade" id="userDetailModal" tabindex="-1">
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
          <p><strong>Company:</strong> {{ dataLeads.leadDetail.company_name }}</p>
          <p><strong>Contact:</strong> {{ dataLeads.leadDetail.contact_name }}</p>
          <p><strong>Email:</strong> {{ dataLeads.leadDetail.email || '-' }}</p>
          <p><strong>Phone:</strong> {{ dataLeads.leadDetail.phone || '-' }}</p>
          <p><strong>Status:</strong>
            <span class="badge bg-warning">
              {{ dataLeads.leadDetail.lead_status }}
            </span>
          </p>
          <p><strong>Source:</strong> {{ dataLeads.leadDetail.lead_source }}</p>
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

  </backendLayouts>
</template>


<style scoped>


</style>