

import { defineStore } from "pinia";
import axios from "axios";

export const useAuthStore = defineStore("resetPassword", {
  state: () => ({
    loading: false,
    message: "",
    error: "",
    errors: null, // error field Laravel
  }),

  actions: {
    async resetPassword(payload) {
      this.loading = true;
      this.message = "";
      this.error = "";
      this.errors = null;

      try {
        const res = await axios.post("/api/reset-password", {
          email: payload.email,
          token: payload.token,
          password: payload.password,
          password_confirmation: payload.password_confirmation,
        });

        this.message = res.data.message;
        return true;

      } catch (e) {
        // VALIDATION ERROR (422)
        if (e.response?.status === 422) {
          this.errors = e.response.data.errors || null;
          this.error = e.response.data.message || "Validation failed";
        }

        // TOKEN INVALID / EXPIRED (400 / 401)
        else if (e.response?.status === 400 || e.response?.status === 401) {
          this.error = e.response.data.message || "Invalid or expired token.";
        }

        // OTHER ERRORS
        else {
          this.error = e.response?.data?.message || "Something went wrong.";
        }

        return false;

      } finally {
        this.loading = false;
      }
    }
  }
});
