
<template>
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">
        <div class="card">
          <div class="card-body">

            <!-- Logo -->
            <div class="app-brand justify-content-center mb-4">
              <a class="app-brand-link gap-2">
                <span class="app-brand-logo demo">
                  <img :src="logo" width="150" alt="Logo">
                </span>
              </a>
            </div>

            <h4 class="mb-2">Welcome to CRM App 👋</h4>
            <p class="mb-4">Please login to manage your CRM data.</p>

            <!-- FORM -->
            <form @submit.prevent="handleLogin" class="mb-3">

              <!-- Email -->
              <div class="mb-3">
                <label class="form-label">Email / Username</label>
                <input 
                  type="text" 
                  class="form-control"
                  v-model="loginStore.email"
                  placeholder="Enter your email or username"
                  autofocus
                />
              </div>

              <!-- Password -->
              <div class="mb-3 form-password-toggle">
                <div class="d-flex justify-content-between">
                  <label class="form-label">Password</label>
                  <RouterLink to="/forgot-password">
                    <small>Forgot Password?</small>
                  </RouterLink>
                </div>

                <div class="input-group input-group-merge">
                  <input
                    :type="showPassword ? 'text' : 'password'"
                    class="form-control"
                    v-model="loginStore.password"
                    placeholder="••••••••••••"
                  />
                  <span 
                    class="input-group-text cursor-pointer"
                    @click="showPassword = !showPassword"
                  >
                    <i :class="showPassword ? 'bx bx-show' : 'bx bx-hide'"></i>
                  </span>
                </div>
              </div>

              <!-- Remember Me -->
              <div class="mb-3">
                <div class="form-check">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    v-model="remember"
                    id="remember-me"
                  />
                  <label class="form-check-label" for="remember-me">
                    Remember Me
                  </label>
                </div>
              </div>

              <!-- Submit -->
              <!-- <div class="mb-3">
                <button 
                  class="btn btn-primary d-grid w-100"
                  type="submit"
                  :disabled="loginStore.isLoading"
                >
                  <span v-if="loginStore.isLoading">Processing...</span>
                  <span v-else>Sign in</span>
                </button>

                 <button 
                  class="btn btn-primary d-grid w-100 mt-1"
                  type="submit"
                  :disabled="loginStore.isLoading"
                >
                  <span v-if="loginStore.isLoading">Processing...</span>
                  <span v-else>Sign in</span>
                </button>
              </div> -->

              <div class="mb-3 d-flex gap-2">
                  <a 
                  :href="homeUrl"
                  class="btn btn-secondary w-100"
                >
                  <i class="fa-regular fa-circle-left"></i> Back To Home
                </a>

                  <button 
                    class="btn btn-primary w-100"
                    type="submit"
                    :disabled="loginStore.isLoading"
                  >
                    <span v-if="loginStore.isLoading">Processing...</span>
                    <span v-else> Sign in <i class="fa-regular fa-circle-right"></i></span>
                  </button>
                </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"
import { exportsLoginStore } from "@/stores/loginStore"

const router = useRouter()
const loginStore = exportsLoginStore()
const homeUrl = window.location.origin

const logo = "/images/logo.png"
const showPassword = ref(false)
const remember = ref(
  !!localStorage.getItem("remember_email") // auto centang jika email tersimpan
)

const handleLogin = async () => {
  const result = await loginStore.LoginLogic({
    remember: remember.value
  })

  if (result && result.redirect_url) {
    router.push(result.redirect_url)
  } else {
    router.push("/administrator-dashboard") // fallback
  }
}
</script>
