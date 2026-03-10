
<template>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

      <Sidebar />

      <div class="layout-page">

        <Navbar @toggle-menu="toggleMenu" @toggle-collapse="toggleCollapse" />

        <div class="content-wrapper">

                    <!-- code khusus Auto logout -->
                   <Transition name="fade">
                        <div v-if="showWarning"
                            class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                            style="backdrop-filter: blur(8px); background: rgba(0,0,0,0.5); z-index: 99999;">

                            <div class="card border-0 shadow-lg overflow-hidden" style="width: 320px;">

                                <!-- Header -->
                                <div class="card-header text-white text-center py-4 border-0"
                                    style="background: linear-gradient(to right, #ef4444, #f97316);">
                                    <div class="fs-2 mb-1">⏱️</div>
                                    <h5 class="fw-bold mb-0">Sesi Akan Berakhir</h5>
                                    <small class="opacity-75">Anda tidak aktif terlalu lama</small>
                                </div>

                                <!-- Body -->
                                <div class="card-body text-center px-4 py-4">
                                    <p class="text-muted small mb-3">Anda akan otomatis logout dalam</p>

                                    <!-- Countdown circle -->
                                    <div class="position-relative d-inline-flex align-items-center justify-content-center mb-4">
                                        <svg style="width: 80px; height: 80px; transform: rotate(-90deg);" viewBox="0 0 100 100">
                                            <circle cx="50" cy="50" r="40" fill="none" stroke="#fee2e2" stroke-width="8"/>
                                            <circle cx="50" cy="50" r="40" fill="none" stroke="#ef4444" stroke-width="8"
                                                stroke-linecap="round"
                                                :stroke-dasharray="`${warningCountdown * 25.13} 251.3`"
                                                style="transition: stroke-dasharray 1s linear"/>
                                        </svg>
                                        <div class="position-absolute text-center">
                                            <span class="fw-bold text-danger" style="font-size: 1.4rem;">{{ warningCountdown }}</span>
                                            <div class="text-muted" style="font-size: 9px;">detik</div>
                                        </div>
                                    </div>

                                    <!-- Button -->
                                    <button @click="stayLoggedIn"
                                        class="btn btn-primary w-100 fw-semibold">
                                        ✋ Tetap Login
                                    </button>

                                    <p class="text-muted mt-3 mb-0" style="font-size: 10px;">
                                        Gerakkan mouse untuk tetap login
                                    </p>
                                </div>

                            </div>
                        </div>
                    </Transition>

          <div class="container-xxl flex-grow-1 container-p-y">
            <slot />
          </div>

          <Footer />

        </div>
      </div>
    </div>

    <div v-if="isMenuExpanded" class="layout-overlay layout-menu-toggle"></div>

    

  </div>
</template>

<script setup>
import { ref, watch, onMounted, provide } from 'vue'; 
import { useMenuStore } from "@/stores/menuStore";

import Navbar from "../components/backend/navbar.vue";
import Sidebar from "../components/backend/sidebar.vue";
import Footer from "../components/backend/footer.vue";


// untuk oauto logout ketika sesi habis 
import { computed } from 'vue'
import { useAutoLogout } from '@/utils/useAutoLogout'
import { exportsLoginStore } from '@/stores/loginStore'

// state khusus logout outomatik ketika sesi habis
const loginStore = exportsLoginStore()
const isAuthenticated = computed(() => !!loginStore.token)
const { showWarning, warningCountdown, stayLoggedIn } = useAutoLogout(60) 


// 1. STATE MANAGEMENT
const isMenuExpanded = ref(false); 
const isMenuCollapsed = ref(false); 
const isDarkMode = ref(false);       
const menuStore = useMenuStore();

onMounted(() => {
  if (!menuStore.menus.length) {
    menuStore.fetchMenus();
  }
});

// 2. FUNGSI
const toggleMenu = () => {
    isMenuExpanded.value = !isMenuExpanded.value;
};

const toggleCollapse = () => {
    isMenuCollapsed.value = !isMenuCollapsed.value;
};

const closeMobileMenuForce = () => {
    if (isMenuExpanded.value === true) {
        isMenuExpanded.value = false;
    }
};

const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
};


// 3. PROVIDE
provide('closeMobileMenu', closeMobileMenuForce); 
provide('toggleTheme', toggleTheme); 


// 4. WATCHERS (DOM Manipulation)

// Watcher A: Mobile Toggle
watch(isMenuExpanded, (newValue) => {
    if (newValue) {
        document.body.classList.add('layout-menu-expanded');
    } else {
        document.body.classList.remove('layout-menu-expanded');
    }
});

// Watcher B: Desktop Toggle
watch(isMenuCollapsed, (newValue) => {
    if (newValue) {
        document.body.classList.add('layout-menu-collapsed');
    } else {
        document.body.classList.remove('layout-menu-collapsed');
    }
});

// Watcher C: DARK MODE - KUNCI PERBAIKAN TEMA (Mengontrol Class dan Atribut)
watch(isDarkMode, (newValue) => {
    const theme = newValue ? 'dark' : 'light';
    
    // 1. Set atribut data-theme (dipertahankan)
    document.documentElement.setAttribute('data-theme', theme);
    
    // 2. KONTROL CLASS PADA <body> (Solusi untuk memaksa perubahan visual)
    if (newValue) {
        document.body.classList.add('dark-style');
        document.body.classList.remove('light-style');
    } else {
        document.body.classList.add('light-style');
        document.body.classList.remove('dark-style');
    }
    
    // Simpan preferensi ke Local Storage
    localStorage.setItem('theme-mode', theme);
    console.log(`Tema diubah menjadi: ${theme}`);
}, { immediate: true }); 


// 5. ON MOUNTED 

onMounted(() => {
    document.addEventListener('click', (event) => {
        if (event.target.classList.contains('layout-overlay')) {
            isMenuExpanded.value = false;
        }
    });

    // MUAT PREFERENSI TEMA
    const savedTheme = localStorage.getItem('theme-mode');
    
    if (savedTheme === 'dark') {
        isDarkMode.value = true; 
    } else {
        isDarkMode.value = false;
    }
    
    // Pastikan Class awal diterapkan (dikerjakan oleh immediate:true di watcher)
    // Cukup pastikan Class default 'light-style' atau 'dark-style' ada di body saat mount.
    const initialThemeClass = isDarkMode.value ? 'dark-style' : 'light-style';
    document.body.classList.add(initialThemeClass);
});
</script>



<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>