
import { ref, reactive } from "vue"
import { defineStore } from "pinia"
import axios from "axios"

export const useVisitDataStore = defineStore("visitStore", () => {

  /* ================= ENDPOINT ================= */
  const endpoints = {
    leadsVisit: "/api/data-visits-leads",
    // customers: "/api/your endpoint",
  }

  /* ================= STATE ================= */
      const visit = ref([])
      const mode = ref("leadsVisit")
      const loading = ref(false)

      const search = ref("")
      let searchTimeout = null

      const visitDetail = ref(null)
      const loadingDetailVisit = ref(false)
      const errorVisit = ref(null)





  const pagination = reactive({
    current_page: 1,
    per_page: 10,
    prev_page_url: null,
    next_page_url: null,
    last_page: 1,
    total: 0,
  })

  const sort = reactive({
    column: "created_at",
    direction: "desc",
  })

  const allowedSortColumns = [
    "company_name",
    "vvisit_codes",
    "created_at",
  ]

  /* ================= AUTH ================= */
  const getAuthHeader = () => ({
    Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
  })



   const buildUrl = () => {
    const params = new URLSearchParams()

    params.append("page", pagination.current_page)
    params.append("per_page", pagination.per_page)
    params.append("sort_by", sort.column)
    params.append("sort_dir", sort.direction)

    if (search.value) {
      params.append("search", search.value)
    }

    return `${endpoints[mode.value]}?${params.toString()}`
  }



   /* ================= FETCH ================= */

  const fetchLeadsVisit = async (newMode = null, page = null) => {
  loading.value = true

  if (newMode) {
    mode.value = newMode
    pagination.current_page = 1
  }

  if (page !== null) {
    pagination.current_page = page
  }

  try {
    const res = await axios.get(buildUrl(), {
      headers: getAuthHeader(),
    })

    const result = res.data
    leads.value = result.data?.data ?? []

    const pag = result.data?.pagination
    if (pag) {
      pagination.current_page = pag.current_page
      pagination.per_page = pag.per_page
      pagination.prev_page_url = pag.prev_page_url
      pagination.next_page_url = pag.next_page_url
      pagination.last_page = pag.last_page
      pagination.total = pag.total
    }

  } catch (err) {
    console.error("Fetch visit leads failed:", err)
    leads.value = []
  } finally {
    loading.value = false
  }
}




  /* ================= SEARCH ================= */
  const searchWithDelay = (val) => {
    clearTimeout(searchTimeout)
    search.value = val
    pagination.current_page = 1

    searchTimeout = setTimeout(() => {
      fetchLeadsVisit()
    }, 500)
  }


   /* ================= SORT ================= */
  const toggleSort = (col) => {
    if (!allowedSortColumns.includes(col)) return

    if (sort.column === col) {
      sort.direction = sort.direction === "asc" ? "desc" : "asc"
    } else {
      sort.column = col
      sort.direction = "asc"
    }

    pagination.current_page = 1
    fetchLeadsVisit()
  }

  /* ================= PER PAGE ================= */
  const changePageSize = () => {
    pagination.current_page = 1
    fetchLeadsVisit()
  }



    const goToPage = (page) => {
  if (page < 1 || page > pagination.last_page) return
  fetchLeadsVisit(null, page)
}

const nextPage = () => {
  if (pagination.current_page < pagination.last_page) {
    fetchLeadsVisit(null, pagination.current_page + 1)
  }
}


const prevPage = () => {
  if (pagination.current_page > 1) {
    fetchLeadsVisit(null, pagination.current_page - 1)
  }
}



  /* ================= RESET ================= */
  const resetFilters = () => {
    search.value = ""
    pagination.per_page = 10
    pagination.current_page = 1
    sort.column = "created_at"
    sort.direction = "desc"
    fetchLeadsVisit()
  }

  /* ================= HELPER ================= */
  const formatDate = (value) => {
    if (!value) return "-"
    const date = new Date(value)
    return date.toLocaleDateString("id-ID", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    })
  }

  const changeSorting = () => {
  pagination.current_page = 1
  fetchLeadsVisit()
}



  return {
    endpoints,
    visit,
    mode,
    loading,
    search,
    searchTimeout,
    visitDetail,
    loadingDetailVisit,
    errorVisit,
    pagination,
    sort,
    allowedSortColumns,
    getAuthHeader,
    buildUrl,
    fetchLeadsVisit,
    searchWithDelay,
    toggleSort,
    changePageSize,
    goToPage,
    nextPage,
    prevPage,
    resetFilters,
    formatDate,
    changeSorting
  }


})