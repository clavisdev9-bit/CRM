<script setup>
import { ref, reactive, onMounted , watch, computed } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
import { useVisitDataStore } from '../../../../stores/visitDataStore';
import { useMenuStore } from "@/stores/menuStore";
import { useRoute, useRouter } from "vue-router";
import Multiselect from "@vueform/multiselect";
import { toasts } from "@/utils/toasts";
import Swal from 'sweetalert2';
const PagesTitle = 'Data Visits Sales';

const dataVisit = useVisitDataStore()
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
    await dataVisit.fetchVisit("leadsVisit")

    await menuStore.fetchMenus()
    permission.value = menuStore.getPermission(route.path)

  } catch (error) {
    console.error(error)
  } finally {
    loadingPermission.value = false
  }
})



const notFoundType = computed(() => {
  if (dataVisit.mode === 'customer') {
    return 'customer'
  }
  if (dataVisit.searchLeads?.length > 0) {
    return 'search'
  }
  return 'empty'
})


const notFoundConfig = {
  assigned: {
    image: 'https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif',
    title: 'Data Not Found',
    message: '!'
  },
  search: {
    image: 'https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif',
    title: 'Data Not Found (Data Tidak Ditemukan)',
    message: 'Try changing your search keywords.)'
  },
  empty: {
    image: 'https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif',
    title: 'Data Not Found',
    message: '!'
  }
}

const formatVisitResult = (value) => {
  if (!value) return '-';

  return value
    .replaceAll('_', ' ')
    .replace(/\b\w/g, char => char.toUpperCase());
};

const visitResultMeta = {
  failed: {
    icon: 'fa-circle-xmark',
    class: 'text-danger',
    label: 'Failed',
  },
  convert_to_customer: {
    icon: 'fa-circle-check',
    class: 'text-success',
    label: 'Converted To Customer',
  },
  potential_customers: {
    icon: 'fa-user-plus',
    class: 'text-info',
    label: 'Potential Customers',
  },
  consideration_stage: {
    icon: 'fa-clock',
    class: 'text-warning',
    label: 'Consideration Stage',
  },
  prospective_customers: {
    icon: 'fa-user-clock',
    class: 'text-primary',
    label: 'Prospective Customers',
  },
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





       <div class="page-body flex-grow-1">
            <div class="container-xl">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="card">
                            <div class="card-body d-flex justify-content-center gap-3">
                               
                                <router-link
                                  to="/sales-visit-leads"
                                  class="btn btn-primary"
                                >
                                   <i class="fa-solid fa-person-walking-dashed-line-arrow-right"></i> Lead Visit
                                </router-link>
                                
                               
                                 <router-link
                                  to="/sales-visit-customers"
                                  class="btn btn-success"
                                >
                                   <i class="fa-solid fa-person-walking-dashed-line-arrow-right"></i> Costumer Visit
                                </router-link>

                               <router-link
                                  to="/sales-follow-up"
                                  class="btn btn-danger"
                                >
                                  <i class="fa-solid fa-mobile-vibrate"></i> Follow Up
                                </router-link>

                                
                            </div>
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
                      v-model="dataVisit.pagination.per_page"
                      @change="dataVisit.changePageSize()"
                    >
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                    </select>
                </div>
                <div class="dropdown">
                    <button 
                      class="btn btn-secondary btn-sm dropdown-toggle" 
                      type="button" 
                      data-bs-toggle="dropdown" 
                      aria-expanded="false"
                    >
                    <i class="fa-solid fa-filter"></i> Filter Data Visits By
                  </button>
                  <ul class="dropdown-menu">

                    <li>
                      <button 
                        class="dropdown-item" 
                        type="button" 
                      
                      >
                        Visit Leads
                      </button>
                    </li>
                    <li>
                      <button 
                        class="dropdown-item" 
                        type="button" 
                        
                      >
                        Visit Customer
                      </button>
                    </li>
                  </ul>
                  </div>
               
                </div>


              <!-- Kanan -->
             <div class="d-flex flex-column gap-3 align-items-end" style="min-width:300px;">
                <!-- Pencarian -->
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Searching...." @input="e => dataVisit.searchWithDelay(e.target.value)">
                    <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                </div>
                <!-- Urutan -->
               <div class="d-flex gap-2 align-items-center">
                  <label class="mb-0 fw-semibold">Sort:</label>
                  <select
                    class="form-select w-auto"
                    v-model="dataVisit.sort.column"
                    @change="dataVisit.changeSorting()"
                  >
                    <option value="company_name">By Company Name</option>
                    <option value="created_at">By Created Date</option>
                  </select>

                  <select
                    class="form-select w-auto"
                    v-model="dataVisit.sort.direction"
                    @change="dataVisit.changeSorting()"
                  >
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
                   <th :colspan="dataVisit.mode === 'customer' ? 11 : 11"
                        class="bg-light fw-bold text-primary">
                      <i class="fa fa-table me-2"></i>
                      {{ dataVisit.mode === 'customer'
                          ? 'Data Visit Customer'
                          : 'Data Visit Leads'
                      }}
                    </th>
                  </tr>
              
                  <tr>
                    <th style="width: 5%;">No.</th>
                    <th>results of the visit</th>
                    <th>visit code</th>
                    <th>company <br>
                      <small class="text-warning">name</small></th>
                    <th>visit <br>
                       <small class="text-warning">time</small>
                    </th>
                    <th>
                      Check in<br>
                       <small class="text-warning">time</small>
                    </th>
                    <th>
                      Check out<br>
                       <small class=" text-warning">time</small>
                    </th>
                    <th>
                      Total time<br>
                      <small class="text-warning">from visit to check in</small>
                    </th>
                    <th>
                      Total time<br>
                      <small class="text-warning">from check in to check out</small>
                    </th>
                    <th>
                      Total<br>
                      <small class="text-warning">your time</small>
                    </th>
                    <th style="width: 8%;">Details</th>
                  </tr>
                </thead>


                  <tbody v-if="dataVisit.loading">
                    <tr>
                      <td 
                          class="text-center" colspan="10">
                        <div class="spinner-border text-primary"></div>
                      </td>
                    </tr>
                  </tbody>


                  <tbody v-else-if="dataVisit.visit.length === 0">
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
                  <tr v-for="(visit, index) in dataVisit.visit" :key="visit.id">
                    <td>{{ (dataVisit.pagination.current_page - 1) * dataVisit.pagination.per_page + index + 1 }}</td>
                    <!-- <td>
                      <i
                        class="fa-solid me-1"
                        :class="visit.visit_type === 'CUSTOMER'
                          ? 'fa-user-check text-success'
                          : 'fa-user-clock text-primary'"
                      ></i>

                      <strong>
                        {{ visit.visit_type === 'CUSTOMER' ? 'Customer' : 'Lead' }}
                      </strong>
                      –
                      <span class="text-muted">
                        {{ formatVisitResult(visit.visit_result) }}
                      </span>
                    </td> -->
                   <td>
  <!-- TYPE -->
  <span
    class="badge me-2"
    :class="visit.visit_type === 'CUSTOMER'
      ? 'bg-success'
      : 'bg-primary'"
  >
    {{ visit.visit_type === 'CUSTOMER' ? 'Customer' : 'Lead' }}
  </span>

  <!-- RESULT -->
  <span
    v-if="visitResultMeta[visit.visit_result]"
    class="fw-semibold"
    :class="visitResultMeta[visit.visit_result].class"
  >
    <i
      class="fa-solid me-1"
      :class="visitResultMeta[visit.visit_result].icon"
    ></i>
    {{ visitResultMeta[visit.visit_result].label }}
  </span>

  <span v-else class="text-muted">
    -
  </span>
</td>


                    <td><strong>{{ visit.visit_code }}</strong></td>
                    <td>{{ visit.company_name }}</td>
                   <td>
                      <span
                        v-if="visit.visit_at"
                        class="badge bg-warning text-dark"
                      >
                        {{ dataVisit.formatDateTime(visit.visit_at) }}
                      </span>

                      <span v-else class="badge bg-secondary">
                        -
                      </span>
                    </td>

                    <td>
                      <span
                        v-if="visit.check_in_at"
                        class="badge bg-success text-dark"
                      >
                        {{ dataVisit.formatTime(visit.check_in_at) }}
                      </span>

                      <span v-else class="badge bg-secondary">
                        Not checked in yet
                      </span>
                    </td>

                    <td>
                      <span
                        v-if="visit.check_out_at"
                        class="badge bg-primary"
                      >
                        {{ dataVisit.formatTime(visit.check_out_at) }}
                      </span>

                      <span v-else class="badge bg-secondary">
                        On going / Not checked in yet
                      </span>
                    </td>

                   <td>
                      <span
                        v-if="visit.time_from_visit_to_check_in && visit.time_from_visit_to_check_in !== '00:00:00'"
                        class="badge bg-primary"
                      >
                        {{ dataVisit.formatDurationToText(visit.time_from_visit_to_check_in) }}
                      </span>

                      <span v-else class="badge bg-secondary">
                       On going
                      </span>
                    </td>

                    <td>
                      <span
                        v-if="visit.time_from_check_in_to_check_out && visit.check_out_at"
                        class="badge bg-primary"
                      >
                        {{ dataVisit.formatDurationToText(visit.time_from_check_in_to_check_out) }}
                      </span>

                      <span v-else class="badge bg-secondary">
                        On going
                      </span>
                    </td>


                   <td>
                      <span
                        v-if="visit.total_time_result && visit.check_out_at"
                        class="badge bg-success"
                      >
                        {{ dataVisit.formatDurationToText(visit.total_time_result) }}
                      </span>

                      <span v-else class="badge bg-secondary">
                        On going
                      </span>
                    </td>


                    <td>
                       <button class="btn btn-outline-primary btn-sm"  data-bs-toggle="modal"
                            data-bs-target="#userDetailModal"
                          >
                            <i class="fa fa-eye"></i> 
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
                    <span class="badge border text-secondary px-3 py-2"> 10 data | on page 19</span>
                    <span class="badge border text-secondary px-3 py-2">Total: 19  data</span>
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




  </backendLayouts>
</template>


<style scoped>


</style>