import { ref, reactive, computed } from "vue"
import { defineStore } from "pinia"
import axios from "axios"
import { toasts } from "@/utils/toasts"
import Swal from 'sweetalert2'

export const useAccessSubMenuStore = defineStore("accessSubMenuStore", () => {

  /* ================= STATE ================= */
  const userId = ref(null)
  const accessSubMenuData = ref([])
  const loadingAccessSubMenu = ref(false)
  const searchAccessSubMenu = ref("")
  let searchTimeout = null
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
  column: "submenu.title",
  direction: "asc",
})

const allowedSortColumns = [
  "submenu.title",
  "submenu.created_at",
]



  const baseUrlApi = computed(() =>
    userId.value ? `/api/users/${userId.value}/submenu-access` : null
  )

  /* ================= AUTH ================= */
  const getAuthHeader = () => {
    const token = localStorage.getItem("auth_token")
    return { Authorization: `Bearer ${token}` }
  }



  const fetchAccessSubMenu = async (url = null) => {
  if (!baseUrlApi.value) return
  loadingAccessSubMenu.value = true

  try {
    const requestUrl = url ?? buildUrl()

    const response = await axios.get(requestUrl, {
      headers: getAuthHeader(),
    })

    const result = response.data
    accessSubMenuData.value = result.data.data ?? []

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
    console.error("Gagal fetch access submenu:", error)
  } finally {
    loadingAccessSubMenu.value = false
  }
}


  /* ================= URL BUILDER ================= */
  const buildUrl = () => {
    const params = new URLSearchParams()

    if (searchAccessSubMenu.value) {
      params.append("search", searchAccessSubMenu.value)
    }

    params.append("page", pagination.current_page)
    params.append("per_page", pagination.per_page)

    if (sort.column) {
      params.append("sort_by", sort.column)
      params.append("sort_dir", sort.direction)
    }

    return `${baseUrlApi.value}?${params.toString()}`
  }

  /* ================= ACTIONS ================= */
  const setUserId = (id) => {
    userId.value = id
    pagination.current_page = 1
    fetchAccessSubMenu()
  }

  const searchWithDelay = (val) => {
    clearTimeout(searchTimeout)
    searchAccessSubMenu.value = val
    pagination.current_page = 1

    searchTimeout = setTimeout(fetchAccessSubMenu, 500)
  }

  const changePageSize = () => {
    pagination.current_page = 1
    fetchAccessSubMenu()
  }

   const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchAccessSubMenu(buildUrl())
                                }


                                  const toggleSort = (col) => {
                                    if (!allowedSortColumns.includes(col)) return  

                                    if (sort.column === col) {
                                    sort.direction = sort.direction === 'asc' ? 'desc' : 'asc'
                                    } else {
                                    sort.column = col
                                    sort.direction = 'asc'
                                    }
                                    changeSorting()
                                }



              const resetFilters = () => {
                searchAccessSubMenu.value = ""
                pagination.current_page = 1
                pagination.per_page = 10
                // sort.column = "title"
                // sort.direction = "asc"
                fetchAccessSubMenu()
              }









  /* ================= AUTO SAVE (PUT) ================= */
  // const autoSavePermission = (submenu) => {
  //   if (!userId.value) return Promise.resolve("no-user")

  //   clearTimeout(autoSaveTimeout)

  //   const payload = {
  //     can_view: submenu.can_view,
  //     can_create: submenu.can_create,
  //     can_update: submenu.can_update,
  //     can_delete: submenu.can_delete,
  //   }

  //   return new Promise((resolve) => {
  //     autoSaveTimeout = setTimeout(async () => {
  //       try {
  //         await axios.put(
  //           `/api/users/${userId.value}/submenu-access/${submenu.id_submenu}`,
  //           payload,
  //           { headers: getAuthHeader() }
  //         )

  //         // toasts.fire({
  //         //   icon: "success",
  //         //   title: "Permission updated",
  //         // })

  //         Swal.fire({
  //               title: "Permission updated!",
  //               icon: "success",
  //               draggable: true
  //             });

  //         resolve("success")
  //       } catch (error) {
  //         console.error("Auto save permission failed:", error)

  //         toasts.fire({
  //           icon: "error",
  //           title: "Failed to update permission",
  //         })

  //         resolve("error")
  //       }
  //     }, 300)
  //   })
  // }



const autoSavePermission = (submenu) => {
  if (!userId.value) return Promise.resolve("no-user")

  clearTimeout(autoSaveTimeout)

  const payload = {
    can_view: submenu.can_view,
    can_create: submenu.can_create,
    can_update: submenu.can_update,
    can_delete: submenu.can_delete,
  }

  return new Promise((resolve) => {
    autoSaveTimeout = setTimeout(async () => {
      try {
        await axios.put(
          `/api/users/${userId.value}/submenu-access/${submenu.id_submenu}`,
          payload,
          { headers: getAuthHeader() }
        )

          Swal.fire({
            icon: "success",
            title: "Permission updated",
            toast: true,
            position: "top-end",
            timer: 1000,
            showConfirmButton: false,
          })

        resolve("success")
      } catch (error) {
        console.error("Auto save permission failed:", error)

        toasts.fire({
            icon: "error",
            title: "Failed to update permission",
          })

        resolve("error")
      }
    }, 300)
  })
}


  /* ================= EXPORT ================= */
  return {
    // state
    userId,
    accessSubMenuData,
    loadingAccessSubMenu,
    searchAccessSubMenu,
    pagination,
    sort,
    changeSorting,

    // actions
    setUserId,
    fetchAccessSubMenu,
    searchWithDelay,
    changePageSize,
    toggleSort,
    resetFilters,
    autoSavePermission,
  }
})
