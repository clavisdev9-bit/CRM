<template>
  <nav
    class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme"
    id="layout-navbar"
  >
    <div class="container-xxl d-flex align-items-center justify-content-between">

      <!-- LEFT: LOGO -->
      <div class="navbar-brand app-brand demo py-0 me-4 d-none d-xl-flex">
        <a href="#" class="app-brand-link">
        <span class="app-brand-logo demo">
          <img :src="logo" width="150" alt="">
        </span>
        </a>
      </div>

      <!-- HAMBURGER (mobile only) -->
      <button class="navbar-toggler d-xl-none" @click="toggleMenu">
        <i class="bx bx-menu fs-2"></i>
      </button>

      <div class="mobile-logo-center d-xl-none">
        <img :src="logo" alt="Logo" class="mobile-logo-img" />
      </div>

      <!-- ========================================================= -->
      <!-- DESKTOP MENU (muncul di layar besar, hilang di mobile)   -->
      <!-- ========================================================= -->
      <div class="d-none d-xl-flex align-items-center gap-4">

        <ul class="navbar-nav align-items-center modern-nav">

          <li class="nav-item">
            <router-link class="nav-link fw-semibold" to="/">
              <i class="fa-solid fa-house me-2"></i> Home
            </router-link>
          </li>

          <li class="nav-item">
            <router-link class="nav-link fw-semibold" to="/maps/tracking/sales">
              <i class="fa-solid fa-map-location me-2"></i> Realtime Tracking Maps Activity Sales
            </router-link>
          </li>


          <li class="nav-item">
            <router-link class="nav-link fw-semibold" to="/history/monitoring/sales">
             <i class="fa-solid fa-indent me-2"></i> Sales visit history & monitoring
            </router-link>
          </li>

           <li class="nav-item">
            <router-link class="nav-link fw-semibold" to="/dashboard/sales">
             <i class="fa-solid fa-chart-column"></i> Dashboard
            </router-link>
          </li>
     
      </ul>
      </div>
      <!-- END DESKTOP MENU -->

      <!-- RIGHT SIDE (icons, profile) -->
      <div class="d-flex align-items-center gap-3 ms-auto">

       <div class="d-none d-xl-block position-relative searchbox-modern">
        <i class="fas fa-search search-icon"></i>
        <input 
            type="text" 
            class="form-control modern-search-input" 
            placeholder="Search..."
        />
        </div>


       <i class="fa-solid fa-globe"></i>

        <div class="dropdown">
          <!-- <a data-bs-toggle="dropdown">
            <img :src="avatarDefault" class="rounded-circle" width="36" />
          </a> -->
          <!-- <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user-tie me-2"></i> My Profile</a></li>
            <li><hr /></li>
            <li><a class="dropdown-item text-danger" href="#"><i class="fa-solid fa-arrow-up-from-bracket me-2"></i>Logout</a></li>
          </ul> -->
        </div>
      </div>
    </div>
  </nav>

  <!-- =========================== -->
  <!--        MOBILE MENU          -->
  <!-- =========================== -->
  <transition name="slide">
    <div v-if="isOpen" class="mobile-menu bg-white shadow-sm d-xl-none">

      <div class="p-3">

        <!-- Search -->
        <!-- <input type="text" class="form-control mb-3" placeholder="Search..." /> -->
         <div class="position-relative modern-input-wrapper mb-1">
        <i class="fas fa-search modern-input-icon"></i>
        <input 
            type="text" 
            class="form-control modern-input"
            placeholder="Search..."
        />
        </div>


        <!-- Same menu as desktop -->
          <router-link
              class="mobile-link fw-semibold mb-2 d-flex align-items-center"
              to="/"
            >
              <i class="fa-solid fa-house me-2"></i>
              Home
            </router-link>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref } from "vue";
const avatarDefault = '/images/avatar.png'
const logo = '/images/logo.png'

const isOpen = ref(false);
const toggleMenu = () => {
  isOpen.value = !isOpen.value;
};
</script>

<style>


.mobile-menu {
  position: absolute;
  top: 60px;
  right: 0;
  left: 0;

  /* 🔥 Membuat card lebih ramping */
  width: 94%;
  margin: 0 auto;

  background: #fff;
  border-radius: 12px;
  padding: 12px 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.12);

  /* Biar tidak terlalu tinggi */
  max-height: calc(100vh - 80px);
  overflow-y: auto;

  z-index: 9999;
}

/* Animasi tetap */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.25s ease;
}
.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}


.searchbox-modern {
  width: 220px;         /* lebih proporsional */
}

.modern-search-input {
  padding-left: 38px;   /* ruang untuk icon */
  border-radius: 12px;  /* sudut modern */
  background: #f6f6f9;  /* warna lembut */
  border: 1px solid #e4e6eb;
  transition: all .25s ease-in-out;
  height: 38px;
  font-size: 14px;
}

.modern-search-input:focus {
  background: #fff;
  border-color: #b8b9c3;
  box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.1); /* efek modern */
}

.search-icon {
  position: absolute;
  top: 50%;
  left: 12px;
  transform: translateY(-50%);
  font-size: 14px;
  color: #767676;
  pointer-events: none; /* supaya icon tidak ganggu klik */
}

.modern-input-wrapper {
  width: 100%;
}

.modern-input {
  padding-left: 40px;
  height: 40px;
  border-radius: 12px;
  background: #f6f6f9;
  border: 1px solid #e1e3e8;
  font-size: 14px;
  transition: all .25s ease;
}

.modern-input:focus {
  background: #fff;
  border-color: #b5b7c0;
  box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.12);
}

.modern-input-icon {
  position: absolute;
  top: 50%;
  left: 12px;
  transform: translateY(-50%);
  font-size: 14px;
  color: #8c8f96;
  pointer-events: none;
}


.modern-nav .nav-link {
  display: flex;
  align-items: center;
  padding: 0.65rem 0.9rem;
  border-radius: 8px;
  transition: 0.25s ease;
}

.modern-nav .nav-link:hover {
  background: rgba(0, 0, 0, 0.05);
}

.modern-nav .dropdown-menu {
  padding: 0.4rem 0;
}

.modern-nav .dropdown-item {
  padding: 0.55rem 1rem;
  border-radius: 6px;
  transition: 0.2s ease;
}

.modern-nav .dropdown-item:hover {
  background: rgba(0, 123, 255, 0.1);
}

.modern-nav i {
  font-size: 1rem;
}


/* Link utama */
.mobile-link {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  border-radius: 8px;
  transition: background 0.25s ease;
}

.mobile-link:hover {
  background: rgba(0, 0, 0, 0.05);
}

/* Summary (Pages) */
.mobile-details summary {
  cursor: pointer;
  list-style: none;
  display: flex;
  align-items: center;
  padding: 10px 12px;
  border-radius: 8px;
  transition: background 0.25s ease;
}

/* Hilangkan default arrow `<summary>` */
.mobile-details summary::-webkit-details-marker {
  display: none;
}

/* Hover */
.mobile-details summary:hover {
  background: rgba(0, 0, 0, 0.05);
}

/* Isi dropdown */
.details-content a.dropdown-item {
  padding: 8px 12px;
  border-radius: 6px;
  transition: background 0.2s ease;
  display: flex;
  align-items: center;
}

.details-content a.dropdown-item:hover {
  background: rgba(0, 0, 0, 0.08);
}

/* Icon size consistent */
.mobile-link i,
.mobile-details summary i,
.details-content i {
  width: 20px;
  text-align: center;
}


/* Wrapper agar logo berada tepat di tengah */
.mobile-logo-center {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  top: 50%;
  transform: translate(-50%, -50%);
}

/* Ukuran logo */
.mobile-logo-img {
  height: 34px;
  object-fit: contain;
}

/* Pastikan navbar punya posisi relatif */
nav {
  position: relative;
}


</style>
