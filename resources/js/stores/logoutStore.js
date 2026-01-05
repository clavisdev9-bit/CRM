import { defineStore } from "pinia";
import { ref } from "vue";
import axios from "axios";

export const useLogoutStore = defineStore("logout", () => {
  const isLoading = ref(false);
  const message = ref(null);
  const error = ref(null);

  const logout = async () => {
    isLoading.value = true;
    message.value = null;
    error.value = null;

    try {
      // Ambil token yang benar
      const token = localStorage.getItem("auth_token");

      const res = await axios.post(
        "http://127.0.0.1:8000/api/signOut",
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );

      message.value = res.data.message || "Logout successful";

      // Hapus token & user
      localStorage.removeItem("auth_token");
      localStorage.removeItem("auth_user");

      return true;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed logout";
      return false;
    } finally {
      isLoading.value = false;
    }
  };

  return { isLoading, message, error, logout };
});