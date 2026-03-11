<template>
  <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    
    <div class="app-brand demo"> 
      <a href="" class="app-brand-link">
        <span class="app-brand-logo demo">
           <img :src="logoUrl"  width="150" alt="">
        </span>
      </a>
    </div>

    <div class="menu-inner-shadow"></div>

      <ul class="menu-inner py-1">

  <template v-for="menu in menuStore.menus" :key="menu.id_menu">

    <!-- HEADER -->
    <li class="menu-header small text-uppercase">
      <span class="menu-header-text">{{ menu.menu }}</span>
    </li>

    <!-- SUBMENUS -->
    <template v-for="sub in menu.submenus" :key="sub.id_submenu">

      <!-- NO CHILD -->
      <li 
        class="menu-item"
        :class="{ 'active': isLinkActive(sub.url) }"
        v-if="!sub.children || sub.children.length === 0"
      >
        <SidebarLink :to="sub.url">
          <i :class="'menu-icon tf-icons ' + sub.icon"></i>
          <div>{{ sub.title }}</div>
        </SidebarLink>
      </li>

      <!-- DROPDOWN -->
      <li 
        class="menu-item"
        v-else
        :class="{ 'open': isDropdownOpen(sub.children.map(c => c.url)) }"
      >
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i :class="'menu-icon tf-icons ' + sub.icon"></i>
          <div>{{ sub.title }}</div>
        </a>

        <ul class="menu-sub">
          <li 
            v-for="child in sub.children"
            :key="child.id_submenu"
            class="menu-item"
            :class="{ 'active': isLinkActive(child.url) }"
          >
            <SidebarLink :to="child.url">
              <div>{{ child.title }}</div>
            </SidebarLink>
          </li>
        </ul>
      </li>

    </template>

  </template>



</ul>

  
  </aside>
</template>

<script setup>
import {ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router'; 
import SidebarLink from './SidebarLink.vue'; 
import { useLogoutStore } from "@/stores/logoutStore"
import { useToast } from "vue-toastification";
import { exportsLoginStore } from "@/stores/loginStore";
import { useMenuStore } from "@/stores/menuStore";




const menuStore = useMenuStore();

// onMounted(async () => {
//   await auth.fetchProfile();  // WAJIB ditunggu

//   console.log("USER INFO:", {
//     id_user: auth.user?.id_user,
//     role_id: auth.user?.role_id,
//     token: auth.token
//   });
// });

const logoUrl = ref('/images/logo.png')

onMounted(async () => {
    try {
        const res = await axios.get('/api/asset-version')
        logoUrl.value = `/images/logo.png?v=${res.data.v}`
    } catch {
        logoUrl.value = `/images/logo.png?v=${Date.now()}`
    }
})

onMounted(async () => {
  if (auth.user && auth.token) {
    await menuStore.fetchMenus(auth.user.id_role, auth.token);
  } else {
    console.warn("User or token not available");
  }
});

const auth = exportsLoginStore();
const toast = useToast();
const logo = '/images/logo.png'
const router = useRouter();
const logoutStore = useLogoutStore();
const doLogout = async () => {
const ok = await logoutStore.logout();

  if (ok) {
    toast.success(logoutStore.message);
    router.push("/login");
  } else {
    toast.error(logoutStore.error);
  }
};

onMounted(() => {
  if (!auth.user) {
    auth.fetchProfile();
  }
});


let menuInstance = null;

const route = useRoute();

// FUNGSI 1: Mendeteksi apakah link individual aktif
const isLinkActive = (path) => {
    return route.path === path;
}

// FUNGSI 2: Mendeteksi apakah dropdown harus dibuka
const isDropdownOpen = (paths) => {
    // Cek apakah path saat ini ada di dalam array path anak
    return paths.includes(route.path);
}


onMounted(() => {
  // Inisialisasi Menu Engine Sneat
  setTimeout(() => {
    const menuEl = document.querySelector('#layout-menu');
    if (menuEl && window.Menu && !menuInstance) {
      try {
        menuInstance = new window.Menu(menuEl); 
        console.log('Sneat Menu Engine berhasil diinisialisasi.');
      } catch (error) {
        console.error('Gagal menginisialisasi window.Menu:', error);
      }
    }
  }, 100); 
});

onUnmounted(() => {
    // Hancurkan instance Menu saat komponen dibongkar
    if (menuInstance && menuInstance.destroy) {
      try {
        menuInstance.destroy();
        menuInstance = null;
      } catch (error) {
        // ...
      }
    }
});
</script>