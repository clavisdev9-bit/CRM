
import { defineStore } from "pinia";
import { ref } from "vue";
import axios from "axios";

export const usePermissionStore = defineStore("permissions", () => {
  const permissions = ref({});
  const isLoaded = ref(false);

  // Fetch permission hanya sekali
  const fetchPermissions = async (id_user, token) => {
    if (isLoaded.value) return;

    try {
      const res = await axios.get(
        "http://127.0.0.1:8000/api/permission-button",
        { id_user },
        { headers: { Authorization: `Bearer ${token}` } }
      );

      permissions.value = res.data.permissions;
      isLoaded.value = true;

    } catch (err) {
      console.error("Failed to load permissions:", err);
    }
  };

  return {
    permissions,
    isLoaded,
    fetchPermissions,
  };
});

