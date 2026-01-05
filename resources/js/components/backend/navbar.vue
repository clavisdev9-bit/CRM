<template>
  <nav
    class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar"
  >
    <!-- Hamburger menu -->
    <div class="navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
      <a 
        class="nav-item nav-link px-0 me-xl-4" 
        href="javascript:void(0)"
        @click="$emit('toggle-menu')"
      >
        <i class="bx bx-menu bx-sm"></i>
      </a>
    </div>

    <!-- App Brand -->
    <div class="app-brand demo d-flex d-xl-none me-auto ms-auto"> 
      <a href="" class="app-brand-link">
        <span class="app-brand-logo demo">
          <img :src="logo" width="150" alt="">
        </span>
      </a>
    </div>

    <!-- Navbar Right -->
    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
      <ul class="navbar-nav flex-row align-items-center ms-auto">

        <!-- Globe Icon -->
        <li class="nav-item lh-1 me-1">
          <i class="fa-solid fa-globe"></i>
        </li>

        <!-- User Dropdown -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <div class="avatar avatar-online">
              <img :src="userAvatar" alt class="w-px-40 h-auto rounded-circle" />
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">

            <!-- User Info -->
            <li>
              <a class="dropdown-item" href="#">
                <div class="d-flex">
                  <div class="flex-shrink-0 me-3">
                    <div class="avatar avatar-online">
                      <img :src="userAvatar" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <span class="fw-semibold d-block">{{ userFullname }}</span>
                    <small class="text-muted">{{ userDivision }}</small>
                  </div>
                </div>
              </a>
            </li>

            <li><div class="dropdown-divider"></div></li>

            <!-- My Profile -->
            <li>
              <router-link class="dropdown-item" :to="{ name: 'profile' }">
                <i class="bx bx-user me-2"></i>
                <span class="align-middle">My Profile</span>
              </router-link>
            </li>

            <li><div class="dropdown-divider"></div></li>

            <!-- Logout -->
            <li>
              <a class="dropdown-item" href="#" @click.prevent="doLogout">
                <i class="bx bx-power-off me-2"></i>
                <span class="align-middle">Log Out</span>
              </a>
            </li>

          </ul>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script setup>
import { onMounted, computed, inject } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { exportsLoginStore } from "@/stores/loginStore"
import { useLogoutStore } from "@/stores/logoutStore"

const toast = useToast()
const router = useRouter()

// Auth store
const auth = exportsLoginStore()

// Avatar & Logo
const avatarDefault = '/images/avatar.png'
const logo = '/images/logo.png'

// Fetch profile on mount if not loaded
onMounted(() => {
  if (!auth.user || !auth.user.id_user) {
    auth.fetchProfile()
  }
})

// Logout
const logoutStore = useLogoutStore()
const doLogout = async () => {
  const ok = await logoutStore.logout()
  if (ok) {
    toast.success(logoutStore.message)
    router.push("/login")
  } else {
    toast.error(logoutStore.error)
  }
}

// Computed properties for safe access
const userFullname = computed(() => auth.user?.fullname ?? 'Loading...')
const userDivision = computed(() => auth.user?.division?.name_division ?? '-')
const userGroup = computed(() => auth.user?.groups?.name_group ?? '-')
const userRole = computed(() => auth.user?.role?.role ?? '-')

const userAvatar = computed(() => {
  // jika auth.user belum ada → pakai default
  if (!auth.user) return avatarDefault

  // jika user.image ada → pakai base URL + image
  // misal backend simpan di storage/app/public/users/
  return auth.user.image
    ? `/storage/users/${auth.user.image}`   // sesuaikan path storage
    : avatarDefault
})


// Emits
const emit = defineEmits(['toggle-menu', 'toggle-collapse'])

// Optional: toggle theme from parent
const toggleTheme = inject('toggleTheme')
</script>