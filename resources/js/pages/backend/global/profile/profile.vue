<template>
  <backendLayouts>
  <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
          
  <!-- Header -->
  <div class="row">
    <div class="col-12">
      <div class="card mb-6">
      
        <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center mb-8">
          <div class="flex-shrink-0 ml-1 mr-1 mt-1 mb-1  mx-sm-0 mx-auto">
         <img
          :src="userAvatar"
          alt="user image"
          class="rounded-2 object-fit-cover"
          style="width:120px; height:120px"
        />
          </div>
          <div class="flex-grow-1 mt-2">
            <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
              <div class="user-profile-info">
                <h4 class="mb-2 mt-lg-7">{{ userFullname }}</h4>
                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 mt-4">
                  <li class="list-inline-item"><i class="fa-solid fa-person-circle-check"> </i> <span class="fw-medium">{{ userDivision }}</span></li>
                  <li class="list-inline-item"><i class="fa-solid fa-building-flag"></i> <span class="fw-medium">{{ userGroup }}</span></li>
                  <li class="list-inline-item"><i class="fa-solid fa-envelope"></i> <span class="fw-medium"> {{ userMail }} | {{ userPhone }}</span></li>
                </ul>
              </div>
              <span class="mb-2" :class="userStatus.class"> {{ userStatus.text }} </span>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </div>
  <!--/ Header -->

  <!-- Navbar pills -->
  <div class="row mt-2 mb-2">
    <div class="col-md-12">
      <div class="nav-align-top">
       
      </div>
    </div>
  </div>
  <!--/ Navbar pills -->

  <!-- User Profile Content -->
<div class="row">
  <div class="col-xl-12 col-lg-12 col-md-12">
    <div class="card mb-6">
      <div class="card-body">
        <!-- ABOUT -->
        <small class="card-text text-uppercase text-body-secondary">About</small>  
<ul class="list-unstyled mt-3 mb-4">
        <li class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
            <i class="icon-base bx bx-user"></i>
            <span class="fw-medium">Full Name</span>
          </div>
          <span>{{ userFullname }}</span>
        </li>
        <li class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-mars-and-venus"></i>
            <span class="fw-medium">Gender</span>
          </div>
          <span>{{ userGender }}</span>
        </li>
        <li class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-regular fa-envelope"></i>
            <span class="fw-medium">Email</span>
          </div>
          <span>{{ userMail }}</span>
        </li>
        <li class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-mobile-screen-button"></i>
            <span class="fw-medium">Number Handphone</span>
          </div>
        <span>{{ userPhone }}</span>

        </li>
        
        <li class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
            <i class="icon-base bx bx-check"></i>
            <span class="fw-medium">Status</span>
          </div>
         <span>
            {{ userStatus.text }}
          </span>

        </li>
        <li class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-braille"></i>
            <span class="fw-medium">division</span>
          </div>
          <span>{{ userDivision }}</span>
        </li>
        <li class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-hotel"></i>
            <span class="fw-medium">group</span>
          </div>
          <span>{{ userGroup }}</span>
        </li>

        <li class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-map-location-dot"></i>
            <span class="fw-medium">Address</span>
          </div>
          <span>{{ userAddres }}</span>
        </li>
      </ul>   
      </div>
    </div>
  </div>
</div>

</div>

</backendLayouts>
</template>


<script setup>
import { onMounted, computed, inject } from 'vue'
import backendLayouts from "../../../../layouts/backendLayouts.vue";
const imageBanner = '/images/profile-banner.png'
const profile = '/images/avatar.png'
import { exportsLoginStore } from "@/stores/loginStore"

const auth = exportsLoginStore()

const avatarDefault = '/images/avatar.png'
const logo = '/images/logo.png'
onMounted(() => {
  if (!auth.user || !auth.user.id_user) {
    auth.fetchProfile()
  }
})

// Computed properties for safe access
const userFullname = computed(() => auth.user?.fullname ?? 'Loading...')
const userDivision = computed(() => auth.user?.division?.name_division ?? '-')
const userGroup = computed(() => auth.user?.groups?.name_group ?? '-')
const userMail = computed(() => auth.user?.email ?? '-')
const userPhone = computed(() => auth.user?.employee?.no_hp ?? '-')
const userAddres = computed(() => auth.user?.employee?.alamat ?? '-')
const userStatus = computed(() => {
  if (!auth.user) {
    return { text: 'Loading...', class: 'text-gray-400' }
  }

  return auth.user.is_active
    ? { text: 'Active', class: 'btn btn-success' }
    : { text: 'Non Active', class: 'btn btn-secondary' }
})

const userGender = computed(() =>
  auth.user?.employee?.jenis_kelamin === 'L'
    ? 'Laki-laki'
    : auth.user?.employee?.jenis_kelamin === 'P'
      ? 'Perempuan'
      : '-'
)



const userAvatar = computed(() => {
  // jika auth.user belum ada → pakai default
  if (!auth.user) return avatarDefault

  // jika user.image ada → pakai base URL + image
  // misal backend simpan di storage/app/public/users/
  return auth.user.image
    ? `/storage/users/${auth.user.image}`   // sesuaikan path storage
    : avatarDefault
})


</script>





<style scoped>
.user-profile-header-banner {
  width: 100%;
  overflow: hidden; /* cegah overflow */
}

.user-profile-header-banner .banner-image {
  display: block;
  width: 100%;
  height: auto;
  max-width: 100%;
  object-fit: cover; /* biar rapi tanpa distorsi */
}

</style>