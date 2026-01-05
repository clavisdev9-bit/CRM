import { ref, reactive, computed } from "vue"
import { defineStore } from "pinia"
import axios from "axios"
import Swal from 'sweetalert2'
import { toasts } from "@/utils/toasts"

export const useAccessMenuStore = defineStore("accessMenuStore", () => {

  /* ================= STATE ================= */
  const roleId = ref(null)
  const accessMenuStoreData = ref([])
  const loadingAccessMenu = ref(false)
  const searchAccessMenu = ref("")
  let searchTimeoutAccessMenu = null

  let autoSaveTimeout = null


  const pagination = reactive({
    current_page: 1,
    per_page: 10,
    prev_page_url: null,
    next_page_url: null,
    last_page: 1,
    total: 0,
  })

  const sort = reactive({
    column: "menu",
    direction: "asc",
  })

  const allowedSortColumns = ["menu", "id_menu"]
  const baseUrlApi = computed(() =>
    roleId.value ? `/api/access-role-to-menu/${roleId.value}` : null
  )

  /* ================= AUTH ================= */
  const getAuthHeader = () => {
    const token = localStorage.getItem("auth_token")
    return { Authorization: `Bearer ${token}` }
  }

  /* ================= FETCH ================= */
  const fetchAccessMenu = async () => {
    if (!baseUrlApi.value) return
    loadingAccessMenu.value = true
    try {
      const response = await axios.get(buildUrl(), {
        headers: getAuthHeader(),
      })
    const result = response.data
      //  ambil ARRAY menu
      accessMenuStoreData.value = result.data.data ?? []
      //  ambil pagination
      const pag = result.data.pagination
      if (pag) {
        pagination.current_page = pag.current_page
        pagination.per_page = pag.per_page
        pagination.prev_page_url = pag.prev_page_url
        pagination.next_page_url = pag.next_page_url
        pagination.last_page = pag.last_page
        pagination.total = pag.total
      }
    } catch (error) {
      console.error("Gagal fetch access menu:", error)
    } finally {
      loadingAccessMenu.value = false
    }
  }

  /* ================= URL BUILDER ================= */
  const buildUrl = () => {
    const params = new URLSearchParams()

    if (searchAccessMenu.value) {
      params.append("search", searchAccessMenu.value)
    }
    if (pagination.current_page) {
                                params.append('page', pagination.current_page)
                              }

                            if (pagination.per_page) {
                                    params.append('per_page', pagination.per_page)
                                }

    if (sort.column) {
      params.append("sort_by", sort.column)
      params.append("sort_dir", sort.direction)
    }

    return `${baseUrlApi.value}?${params.toString()}`
  }

  /* ================= ACTIONS ================= */
  const setRoleId = (id) => {
    roleId.value = id
    pagination.current_page = 1
    fetchAccessMenu()
  }

  const searchWithDelay = (val) => {
    clearTimeout(searchTimeoutAccessMenu)
    searchAccessMenu.value = val
    pagination.current_page = 1

    searchTimeoutAccessMenu = setTimeout(() => {
      fetchAccessMenu()
    }, 500)
  }

  const changePageSize = () => {
    pagination.current_page = 1
    fetchAccessMenu()
  }

  const toggleSort = (col) => {
    if (!allowedSortColumns.includes(col)) return

    if (sort.column === col) {
      sort.direction = sort.direction === "asc" ? "desc" : "asc"
    } else {
      sort.column = col
      sort.direction = "asc"
    }

    pagination.current_page = 1
    fetchAccessMenu()
  }

  const resetFilters = () => {
    searchAccessMenu.value = ""
    pagination.current_page = 1
    pagination.per_page = 10
    sort.column = "menu"
    sort.direction = "asc"
    fetchAccessMenu()
  }



  const autoSaveAccess = (menu) => {
    if (!roleId.value) return Promise.resolve("no-role")

    clearTimeout(autoSaveTimeout)

    return new Promise((resolve) => {
      autoSaveTimeout = setTimeout(async () => {
        try {
          const res = await axios.post(
            `/api/access-menu/change`,
            {
              roleId: roleId.value,
              menuId: menu.id_menu,
            },
            { headers: getAuthHeader() }
          )

          //  Toast berdasarkan aksi
          if (res.data.status === "added") {
            toasts.fire({
              icon: "success",
              title: res.data.message,
            })
          } else if (res.data.status === "removed") {
            toasts.fire({
              icon: "info",
              title: res.data.message,
            })
          }

          resolve(res.data.status)
        } catch (error) {
          console.error("Auto save failed:", error)
          // rollback UI
          menu.has_access = !menu.has_access

          toasts.fire({
            icon: "error",
            title: "Failed to save access changes",
          })

          resolve("error")
        }
      }, 300)
    })
  }





  /* ================= EXPORT ================= */
  return {
    // state
    roleId,
    accessMenuStoreData,
    loadingAccessMenu,
    searchAccessMenu,
    pagination,
    sort,

    // actions
    setRoleId,
    fetchAccessMenu,
    searchWithDelay,
    changePageSize,
    toggleSort,
    resetFilters,
    autoSaveAccess
  }
})
