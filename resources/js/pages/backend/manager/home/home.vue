<template>
    <backendLayouts>

            <div class="page-header d-print-none">
                <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                    <!-- Page pre-title -->
                    <div class="page-pretitle">
                        Manager Page
                    </div>
                    <h4 class="page-title">
                        Manager Page
                    </h4>
                    </div>
                </div>
                </div>
            </div>

            <div class="container-xxl flex-grow-1 container-p-y">
              <div class="row">
                <div class="col-lg-12 mb-4 order-0">
                  <div class="card">
                    <div class="d-flex align-items-end row">
                      <div class="col-sm-7">
                        <div class="card-body">
                          <h5 class="card-title text-primary">Wellcome {{ auth.user?.fullname || 'Loading...' }} 🎉</h5>
                          <p class="mb-4">
                            You have done <span class="fw-bold">72%</span> more sales today. Check your new badge in
                            your profile.
                          </p>
                        </div>
                      </div>
                      <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4">
                          <img
                            :src="bannerImages"
                            height="140"
                            alt="View Badge User"
                            data-app-dark-img="illustrations/man-with-laptop-dark.png"
                            data-app-light-img="illustrations/man-with-laptop-light.png"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                </div>

           
               

                

               
            </div>

            
          
            <!-- / Content -->
</backendLayouts>
</template>


<script setup>
import { onMounted } from "vue";
import backendLayouts from "../../../../layouts/backendLayouts.vue";

const bannerImages = '/images/man-with-laptop-light.png'
import { exportsLoginStore } from "@/stores/loginStore";
const auth = exportsLoginStore();

onMounted(() => {
  if (!auth.user) {
    auth.fetchProfile();
  }
});




</script>