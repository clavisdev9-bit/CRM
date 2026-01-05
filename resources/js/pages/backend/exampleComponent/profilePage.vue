<template>
  <backendLayouts>
  <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
          
  <!-- Header -->
  <div class="row">
    <div class="col-12">
      <div class="card mb-6">
        <!-- <div class="user-profile-header-banner">
          <img :src="imageBanner" alt="Banner image" class="rounded-top" />
        </div> -->
        <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center mb-8">
          <div class="flex-shrink-0 mt-1 mx-sm-0 mx-auto">
            <img :src="profile" alt="user image" class="d-block h-auto ms-0 ms-sm-6 rounded-3 user-profile-img" />
          </div>
          <div class="flex-grow-1 mt-3 mt-lg-5">
            <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
              <div class="user-profile-info">
                <h4 class="mb-2 mt-lg-7">John Doe</h4>
                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 mt-4">
                  <li class="list-inline-item"><i class="icon-base bx bx-palette me-2 align-top"></i><span class="fw-medium">UX Designer</span></li>
                  <li class="list-inline-item"><i class="icon-base bx bx-map me-2 align-top"></i><span class="fw-medium">Vatican City</span></li>
                  <li class="list-inline-item"><i class="icon-base bx bx-calendar me-2 align-top"></i><span class="fw-medium"> Joined April 2021</span></li>
                </ul>
              </div>
              <a href="javascript:void(0)" class="btn btn-success mb-1"> <i class="icon-base bx bx-user-check icon-sm me-2"></i>aktif </a>
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
    <div class="col-xl-4 col-lg-5 col-md-5">
      <!-- About User -->
      <div class="card mb-6">
        <div class="card-body">
          <small class="card-text text-uppercase text-body-secondary small">About</small>
          <ul class="list-unstyled my-3 py-1">
            <li class="d-flex align-items-center mb-4"><i class="icon-base bx bx-user"></i><span class="fw-medium mx-2">Full Name:</span> <span>John Doe</span></li>
            <li class="d-flex align-items-center mb-4"><i class="icon-base bx bx-check"></i><span class="fw-medium mx-2">Status:</span> <span>Active</span></li>
            <li class="d-flex align-items-center mb-4"><i class="icon-base bx bx-crown"></i><span class="fw-medium mx-2">Role:</span> <span>Developer</span></li>
            <li class="d-flex align-items-center mb-4"><i class="icon-base bx bx-flag"></i><span class="fw-medium mx-2">Country:</span> <span>USA</span></li>
            <li class="d-flex align-items-center mb-2"><i class="icon-base bx bx-detail"></i><span class="fw-medium mx-2">Languages:</span> <span>English</span></li>
          </ul>
          <small class="card-text text-uppercase text-body-secondary small">Contacts</small>
          <ul class="list-unstyled my-3 py-1">
            <li class="d-flex align-items-center mb-4"><i class="icon-base bx bx-phone"></i><span class="fw-medium mx-2">Contact:</span> <span>(123) 456-7890</span></li>
            <li class="d-flex align-items-center mb-4"><i class="icon-base bx bx-chat"></i><span class="fw-medium mx-2">Skype:</span> <span>john.doe</span></li>
            <li class="d-flex align-items-center mb-4"><i class="icon-base bx bx-envelope"></i><span class="fw-medium mx-2">Email:</span> <span>john.doe@example.com</span></li>
          </ul>
          <small class="card-text text-uppercase text-body-secondary small">Teams</small>
          <ul class="list-unstyled mb-0 mt-3 pt-1">
            <li class="d-flex flex-wrap mb-4"><span class="fw-medium me-2">Backend Developer</span><span>(126 Members)</span></li>
            <li class="d-flex flex-wrap"><span class="fw-medium me-2">React Developer</span><span>(98 Members)</span></li>
          </ul>
        </div>
      </div>
      <!--/ About User -->
    </div>




          <div class="col-xl-8 col-lg-7 col-md-7 mt-2">
            <!-- Change Password -->
            <div class="card card-action mb-6">
              <div class="card-header align-items-center">
                  <small class="card-text text-uppercase text-body-secondary small"><i class="icon-base bx bx-key icon-lg text-body me-4"></i>Change Profile</small>
              </div>
              <div class="card-body pt-3">
                  
              
                <div class="row align-items-center">
                   <div class="col-auto">
                    <img src="" id="imgPreview" 
                    width=""
                    height=""
                    class="img-thumbnail avatar"
                    alt="profile"
                    loading="lazy"
                    >
                  </div>
                  <div class="col-auto">
                    <input class="form-control" type="file" name="image"  accept="image/png, image/jpeg, image/jpg, image/gif">
                    <input type="hidden" name="imageold" value="" class="form-control mt-2" id="coversold">
                  </div>
                 
                </div>
                <h5 class="card-title mt-4">Your Data Profile</h5>
                <div class="row g-3">
                  <div class="col-md">
                    <div class="form-label">FullName</div>
                    <input type="text" class="form-control" name="fullname" value=""/>
                    <div class="invalid-feedback d-block"></div>

                  </div>
                  <div class="col-md">
                    <div class="form-label">UserName</div>
                    <input type="text" class="form-control" name="username" value=""/>
                    <div class="invalid-feedback d-block"></div>
                  </div>
                  <div class="col-md">
                    <div class="form-label">Division</div>
                    <input type="text" class="form-control" value="" disabled/>
                  </div>

                  <div class="col-md">
                    <div class="form-label">Status</div>
                    <input type="text" class="form-control" value="" disabled/>
                  </div>
                </div>
              
              </div>
              

              <div class="card-footer text-end">
                <div class="d-flex">
                  <button type="submit" class="btn btn-primary ms-auto">Change Profile</button>
                </div>
              </div>
            </div>




            <!-- card profile -->
            <div class="card card-action mt-2">
              <div class="card-header align-items-center">
                <small class="card-text text-uppercase text-body-secondary small"><i class="icon-base bx bx-key icon-lg text-body me-4"></i>Change Password</small>
              </div>
              <div class="card-body pt-3">
                  

               
        <h5 class="card-header">Change Password</h5>
        <div class="card-body pt-1">
          <form id="formAccountSettings" method="GET" onsubmit="return false">
            <div class="row">
              <div class="mb-6 col-md-6 form-password-toggle form-control-validation">
                <label class="form-label" for="currentPassword">Current Password</label>
                <div class="input-group input-group-merge">
                  <input class="form-control" type="password" name="currentPassword" id="currentPassword" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                  <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="mb-6 col-md-6 form-password-toggle form-control-validation">
                <label class="form-label" for="newPassword">New Password</label>
                <div class="input-group input-group-merge">
                  <input class="form-control" type="password" id="newPassword" name="newPassword" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                  <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                </div>
              </div>

              <div class="mb-6 col-md-6 form-password-toggle form-control-validation">
                <label class="form-label" for="confirmPassword">Confirm New Password</label>
                <div class="input-group input-group-merge">
                  <input class="form-control" type="password" name="confirmPassword" id="confirmPassword" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                  <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                </div>
              </div>
            </div>
            <h6 class="text-body">Password Requirements:</h6>
            <ul class="ps-4 mb-0">
              <li class="mb-4">Minimum 8 characters long - the more, the better</li>
              <li class="mb-4">At least one lowercase character</li>
              <li>At least one number, symbol, or whitespace character</li>
            </ul>
            <div class="mt-6">
              <button type="submit" class="btn btn-primary me-3">Save changes</button>
              <button type="reset" class="btn btn-secondary">Reset</button>
            </div>
          </form>
        </div>
      </div>
                
              </div>
            </div>
          </div>



         
  </div>
   

   
</backendLayouts>
</template>


<script setup>
import backendLayouts from "../../../layouts/backendLayouts.vue";
const imageBanner = '/images/profile-banner.png'
const profile = '/images/avatar.png'
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