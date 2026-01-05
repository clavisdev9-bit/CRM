<template>
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">

        <!-- Register Card -->
        <div class="card">
          <div class="card-body">

            <!-- Logo -->
            <div class="app-brand justify-content-center mb-4">
              <RouterLink to="/" class="app-brand-link gap-2">
                <span class="app-brand-logo demo">
                  <img :src="logo" width="150" alt="Logo" />
                </span>
              </RouterLink>
            </div>
            <!-- /Logo -->

            <h4 class="mb-2">Welcome to CRM App 🚀</h4>
            <p class="mb-4">Manage your customers and business with ease.</p>

            <!-- FORM -->
            <form @submit.prevent="handleRegister" class="mb-3">

              <!-- Username -->
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input
                  type="text"
                  class="form-control"
                  placeholder="Enter your username"
                  v-model="form.username"
                  autofocus
                />
              </div>

              <!-- Email -->
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                  type="email"
                  class="form-control"
                  placeholder="Enter your email"
                  v-model="form.email"
                />
              </div>

              <!-- Password -->
              <div class="mb-3 form-password-toggle">
                <label class="form-label">Password</label>

                <div class="input-group input-group-merge">
                  <input
                    :type="showPassword ? 'text' : 'password'"
                    class="form-control"
                    placeholder="••••••••••••"
                    v-model="form.password"
                  />
                  <span
                    class="input-group-text cursor-pointer"
                    @click="showPassword = !showPassword"
                  >
                    <i :class="showPassword ? 'bx bx-show' : 'bx bx-hide'"></i>
                  </span>
                </div>
              </div>

              <!-- Terms -->
              <div class="mb-3">
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="terms-conditions"
                    v-model="form.terms"
                  />
                  <label class="form-check-label" for="terms-conditions">
                    I agree to
                   <a type="button" class="text-primary" data-bs-toggle="modal" data-bs-target="#privacyPolicyModal">
                    Terms & Privacy Policy
                  </a>
                  </label>
                </div>
              </div>

              <!-- Button -->
              <button class="btn btn-primary d-grid w-100" type="submit">
                Sign up
              </button>
            </form>

            <!-- Login Link -->
            <p class="text-center">
              <span>Already have an account?</span>
              <RouterLink to="/login">
                Sign in instead
              </RouterLink>
            </p>

          </div>
        </div>
        <!-- /Register Card -->

      </div>
    </div>
  </div>


<!-- Modal -->
<div class="modal fade" id="privacyPolicyModal" tabindex="-1" aria-labelledby="privacyPolicyLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="privacyPolicyLabel">Privacy & Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <h6 class="fw-bold">Privacy Policy</h6>
        <p>
          We are committed to protecting your personal information and ensuring that your data
          is handled securely and responsibly. Any information you provide will be used only for
          account verification, service operations, and communication related to our CRM platform.
        </p>

        <h6 class="fw-bold mt-3">Terms & Conditions</h6>
        <p>
          By using our CRM application, you agree to follow the rules and guidelines we have
          established. Please ensure that all information you provide is accurate and that you do
          not misuse the platform in any way that may harm system performance or other users.
        </p>

        <p>
          For full details, please read the complete Privacy Policy and Terms documentation.
        </p>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

</template>

<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

const logo = '/images/logo.png'

const showPassword = ref(false)

const form = ref({
  username: '',
  email: '',
  password: '',
  terms: false
})

const handleRegister = () => {
  console.log('Register data:', form.value)

  // Kirim data ke API Anda nanti:
  // axios.post('/api/register', form.value)
  //   .then(...)
  //   .catch(...)
}
</script>
