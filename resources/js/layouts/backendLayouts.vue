
<template>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

      <Sidebar />

      <div class="layout-page">

        <Navbar @toggle-menu="toggleMenu" @toggle-collapse="toggleCollapse" />

        <div class="content-wrapper">

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
