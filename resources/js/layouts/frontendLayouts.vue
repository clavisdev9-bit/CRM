
<template>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">



      <div class="layout-page">

        <Navbar />

        <div class="content-wrapper">

          <div class="container-xxl flex-grow-1 container-p-y">
            <slot />
          </div>

          <Footer />

        </div>
      </div>
    </div>

    <div  class="layout-overlay layout-menu-toggle"></div>

  </div>
</template>

<script setup>
import Navbar from "../components/frontend/navbar.vue";
import Footer from "../components/frontend/footer.vue";
</script> 

