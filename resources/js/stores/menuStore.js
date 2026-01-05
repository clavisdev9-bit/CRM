import { defineStore } from "pinia";
import { ref, watch } from "vue";
import axios from "axios";
import { exportsLoginStore } from "@/stores/loginStore";

export const useMenuStore = defineStore("menu", () => {
  const menus = ref([]);
  const auth = exportsLoginStore();

  // =====================================================
  // 1. LOAD DATA DARI LOCAL STORAGE (biar cepat tampil)
  // =====================================================
  const savedMenus = localStorage.getItem("menus");
  if (savedMenus) {
    menus.value = JSON.parse(savedMenus);
  }

  // =====================================================
  // 2. FETCH MENU + SUBMENU DARI API
  // =====================================================
  const fetchMenus = async () => {
    if (!auth.user) await auth.fetchProfile();

    const role_id = auth.user.role_id;
    const token = auth.token;

    try {
      // 1. Ambil menu
      const menuRes = await axios.get(
        `http://127.0.0.1:8000/api/sidebar-access/${role_id}`,
        { headers: { Authorization: `Bearer ${token}` } }
      );

      const menusList = menuRes.data.data;

      // Ambil semua id_menu
      const menuIds = menusList.map(m => m.id_menu);

      // 2. Ambil submenu berdasarkan user
      const submenuRes = await axios.get(
        `http://127.0.0.1:8000/api/sidebar-access-submenu`,
        {
          params: {
            menu_ids: menuIds,
            id_user: auth.user.id_user,
          },
          headers: { Authorization: `Bearer ${token}` }
        }
      );

      const submenuList = submenuRes.data.data;

      // 3. Gabungkan
      menus.value = menusList.map(menu => ({
        ...menu,
        submenus: submenuList.filter(s => s.id_menu === menu.id_menu)
      }));

    } catch (err) {
      console.error("MENU FETCH ERROR:", err);
      menus.value = [];
    }
  };

  // =====================================================
  // 3. CARI PERMISSION BERDASARKAN URL PAGE SAAT INI
  // =====================================================
  const getPermission = (currentUrl) => {
    for (const menu of menus.value) {
      for (const sub of menu.submenus) {

        // Submenu level 1
        if (sub.url === currentUrl) return sub;

        // Submenu level 2 (child)
        if (sub.children?.length) {
          const foundChild = sub.children.find(c => c.url === currentUrl);
          if (foundChild) return foundChild;
        }
      }
    }
    return null;
  };

  // =====================================================
  // 4. SIMPAN KE LOCAL STORAGE AGAR TIDAK LAMBAT
  // =====================================================
  watch(
    menus,
    (val) => {
      localStorage.setItem("menus", JSON.stringify(val));
    },
    { deep: true }
  );

  return { menus, fetchMenus, getPermission };
});
