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
                  <img :src="logo" width="150" alt="Logo" />
                </span>
              </RouterLink>
            </div>

            <h4 class="mb-2 text-center">Reset Your Password 🔒</h4>
            <p class="mb-4 text-center">
              Enter your new password below to complete the reset process.
            </p>

            <!-- SUCCESS -->
            <div v-if="auth.message" class="alert alert-success text-center mt-3">
              {{ auth.message }}
            </div>

            <!-- Global error -->
            <div
              v-if="auth.error"
              class="alert alert-danger text-center mb-4"
            >
              {{ auth.error }}
            </div>

            <!-- FORM -->
            <form @submit.prevent="handleSubmit" class="mb-3">

              <!-- EMAIL -->
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                  type="email"
                  class="form-control"
                  v-model="form.email"
                  readonly
                />
              </div>

              <!-- PASSWORD -->
              <div class="mb-3 form-password-toggle">
                <label class="form-label">New Password</label>

                <div class="input-group input-group-merge">
                  <input
                    :type="showPassword ? 'text' : 'password'"
                    class="form-control"
                    :class="{ 'is-invalid': auth.errors?.password }"
                    v-model="form.password"
                    placeholder="Enter new password"
                  />

                  <span class="input-group-text cursor-pointer" @click="togglePassword">
                    <i v-if="!showPassword" class="bx bx-hide"></i>
                    <span v-else style="font-size: 20px">🐵</span>
                  </span>
                </div>

                <!-- Error Laravel -->
                <small
                  v-if="auth.errors?.password"
                  class="text-danger d-block mt-1"
                >
                  {{ auth.errors.password[0] }}
                </small>
              </div>

              <!-- CONFIRM PASSWORD -->
              <div class="mb-3 form-password-toggle">
                <label class="form-label">Confirm Password</label>

                <div class="input-group input-group-merge">
                  <input
                    :type="showConfirm ? 'text' : 'password'"
                    class="form-control"
                    :class="{ 'is-invalid': auth.errors?.password_confirmation }"
                    v-model="form.password_confirmation"
                    placeholder="Re-enter password"
                  />

                  <span class="input-group-text cursor-pointer" @click="toggleConfirm">
                    <i v-if="!showConfirm" class="bx bx-hide"></i>
                    <span v-else style="font-size: 20px">🐶</span>
                  </span>
                </div>

                <!-- Error Laravel -->
                <small
                  v-if="auth.errors?.password_confirmation"
                  class="text-danger d-block mt-1"
                >
                  {{ auth.errors.password_confirmation[0] }}
                </small>
              </div>

              <!-- SUBMIT BTN -->
               <button
                    class="btn btn-primary d-grid w-100"
                    type="submit"
                    :disabled="auth.loading"
                    :style="{ opacity: auth.loading ? '0.7' : '1' }"
                    >
                    <span v-if="auth.loading">Processing...</span>
                    <span v-else>Update Password</span>
                </button>


            </form>

            

          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/resetPasswordStore";

const auth = useAuthStore();
const router = useRouter();

const logo = "/images/logo.png";

const form = ref({
  email: "",
  token: "",
  password: "",
  password_confirmation: "",
});

// password toggle
const showPassword = ref(false);
const showConfirm = ref(false);
const togglePassword = () => (showPassword.value = !showPassword.value);
const toggleConfirm = () => (showConfirm.value = !showConfirm.value);

// get token & email from URL
onMounted(() => {
  const url = new URL(window.location.href);
  form.value.token = url.searchParams.get("token");
  form.value.email = url.searchParams.get("email");
});

// submit form
const handleSubmit = async () => {
  const ok = await auth.resetPassword(form.value);

  if (ok) {
    form.value.password = "";
    form.value.password_confirmation = "";

    setTimeout(() => router.push("/login"), 2000);
  }
};
</script>

<style scoped>
.is-invalid {
  border-color: #dc3545 !important;
}
</style>
