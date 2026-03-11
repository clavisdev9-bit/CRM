<template>
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner py-4">

        <div class="card">
          <div class="card-body">

            <!-- Logo -->
            <div class="app-brand justify-content-center mb-4">
              <RouterLink to="/" class="app-brand-link gap-2">
                <span class="app-brand-logo demo">
                  <img :src="logoUrl" width="150" alt="Logo" />
                </span>
              </RouterLink>
            </div>

            <h4 class="mb-2 text-center">Forgot Password? 🔒</h4>
            <p class="mb-4 text-center">
              Enter your email and we will send you instructions to reset your password.
            </p>

            <!-- ALERT -->
            <div
              v-if="auth.message"
              class="alert"
              :class="auth.status === 'success' ? 'alert-success' : 'alert-danger'"
            >
              {{ auth.message }}
            </div>

            <!-- FORM -->
            <form @submit.prevent="handleForgotPassword" class="mb-3">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                  type="email"
                  class="form-control"
                  placeholder="Enter your email"
                  v-model="email"
                  autofocus
                />
              </div>

              <button
                class="btn btn-primary d-grid w-100"
                type="submit"
                :disabled="auth.loading"
              >
                <span v-if="auth.loading" class="loader me-2"></span>
                <span v-if="auth.loading">Processing...</span>
                <span v-else>Send Reset Link</span>
              </button>
            </form>

            <div class="text-center">
              <RouterLink
                to="/login"
                class="d-flex align-items-center justify-content-center"
              >
                <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
                Back to login
              </RouterLink>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/forgotPasswordStore'

const logo = '/images/logo.png'

const logoUrl = ref('/images/logo.png')

onMounted(async () => {
    try {
        const res = await axios.get('/api/asset-version')
        logoUrl.value = `/images/logo.png?v=${res.data.v}`
    } catch {
        logoUrl.value = `/images/logo.png?v=${Date.now()}`
    }
})

const email = ref('')
const auth = useAuthStore()

const handleForgotPassword = async () => {
  if (!email.value) {
    auth.status = "error"
    auth.message = "Email is required."
    return
  }

  const ok = await auth.forgotPassword(email.value)

  // Jika sukses → kosongkan input
  if (ok) {
    email.value = ''
  }
}
</script>
