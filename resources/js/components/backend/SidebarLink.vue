<!-- <template>
  <RouterLink :to="to" class="menu-link" @click="handleLinkClick">
    <slot></slot>
  </RouterLink>
</template>

<script setup>
import { inject } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
  // Menerima props 'to' yang sama dengan RouterLink
  to: {
    type: [String, Object],
    required: true
  }
});

// Mengambil fungsi penutup yang disediakan oleh Layout Induk
const closeMobileMenu = inject('closeMobileMenu');

const handleLinkClick = () => {
    // Jalankan fungsi penutup jika tersedia
    if (closeMobileMenu) {
        // Cek lebar layar (penting agar Sidebar desktop tidak tertutup)
        if (window.innerWidth < 1200) {
            closeMobileMenu();
            console.log("Penutupan Sidebar dipicu dari SidebarLink.");
        }
    }
    // Navigasi RouterLink akan berjalan otomatis setelah ini
};
</script> -->


<template>
  <router-link :to="to" class="menu-link" @click="handleClick">
    <slot></slot>
  </router-link>
</template>

<script setup>
import { inject } from 'vue';
// Tidak perlu useRoute lagi di sini karena status aktif ditangani di parent (sidebar.vue)

const props = defineProps({
  to: {
    type: [String, Object],
    required: true,
  },
});

const closeMobileMenu = inject('closeMobileMenu');

// Fungsi untuk menutup menu mobile dan menjalankan navigasi
const handleClick = () => {
    // Tutup sidebar mobile setelah klik
    if (closeMobileMenu) {
        closeMobileMenu();
    }
};
</script>