<template>
  <FrontendLayouts>
    <div class="container-xxl flex-grow-1 container-p-y">

      <!-- WELCOME CARD -->
      <div class="row">
        <div class="col-lg-12 mb-4 order-0">
          <div class="card">
            <div class="d-flex align-items-end row">

              <!-- LEFT TEXT -->
              <div class="col-sm-7">
                <div class="card-body">
                  <h4 class="card-title text-primary mb-2">Welcome to Clavis CRM! 🎉</h4>

                  <p class="mb-4">
                    Manage customers, monitor sales activity, and increase your team's productivity with ease.
                    Start your CRM journey now!
                  </p>

                  <!-- BUTTON LOGIN -->
                  <RouterLink to="/login" class="btn btn-primary btn-sm">
                   Login Now
                  </RouterLink>
                </div>
              </div>

              <!-- RIGHT IMAGE -->
              <div class="col-sm-5 text-center text-sm-left">
                <div class="card-body pb-0 px-0 px-md-4">
                  <img
                    :src="bannerImages"
                    height="160"
                    alt="CRM Dashboard Illustration"
                    data-app-dark-img="illustrations/man-with-laptop-dark.png"
                    data-app-light-img="illustrations/man-with-laptop-light.png"
                  />
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- KONTEN TAMBAHAN BISA DITARUH DI SINI -->
      <div class="row mt-4">
        <!-- kamu bisa isi statistik, chart, menu, dsb -->
      </div>

    </div>
  </FrontendLayouts>
</template>



<script setup>
import { onMounted } from "vue";
import FrontendLayouts from "../../../layouts/frontendLayouts.vue";
const bannerImages = '/images/man-with-laptop-light.png'
onMounted(() => {
  if (window.Helpers?.initNavbarDropdowns) {
    window.Helpers.initNavbarDropdowns();
  }
});
</script>



<style scoped>
.navbar {
  background: #fff;
  border-bottom: 1px solid #e5e5e5;
}
</style>
