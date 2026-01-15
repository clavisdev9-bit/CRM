
import { ref, reactive } from "vue"
import { defineStore } from "pinia"
import axios from "axios"

export const useLeadsStore = defineStore("leadsStore", () => {

  /* ================= ENDPOINT ================= */
  const endpoints = {
    all: "/api/leads-master",
    assigned: "/api/leads-assigned-to-me",
  }

  /* ================= STATE ================= */
  const leads = ref([])
  const mode = ref("all")
  const loading = ref(false)

  const search = ref("")
  let searchTimeout = null

  const leadDetail = ref(null)
 const loadingDetail = ref(false)


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
    "contact_name",
    "created_at",
  ]

  /* ================= AUTH ================= */
  const getAuthHeader = () => ({
    Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
  })

  /* ================= URL BUILDER ================= */
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
  const fetchLeads = async (newMode = null) => {
    loading.value = true

    if (newMode) {
      mode.value = newMode
      pagination.current_page = 1
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
      console.error("Fetch leads failed:", err)
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
      fetchLeads()
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
    fetchLeads()
  }

  /* ================= PER PAGE ================= */
  const changePageSize = () => {
    pagination.current_page = 1
    fetchLeads()
  }

  /* ================= PAGINATION ================= */
  const goToPage = (page) => {
    if (page < 1 || page > pagination.last_page) return
    pagination.current_page = page
    fetchLeads()
  }

  /* ================= RESET ================= */
  const resetFilters = () => {
    search.value = ""
    pagination.per_page = 10
    pagination.current_page = 1
    sort.column = "created_at"
    sort.direction = "desc"
    fetchLeads()
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
  fetchLeads()
}

const fetchLeadDetail = async (id) => {
  loadingDetail.value = true
  leadDetail.value = null

  try {
    const res = await axios.get(`/api/leads/show/${id}`, {
      headers: getAuthHeader(),
    })

    leadDetail.value = res.data.data
  } catch (err) {
    console.error("Fetch lead detail failed:", err)
  } finally {
    loadingDetail.value = false
  }
}



  /* ================= EXPORT ================= */
  return {
    // state
    leads,
    loading,
    mode,
    search,
    pagination,
    sort,

    // action
    fetchLeads,
    searchWithDelay,
    toggleSort,
    changePageSize,
    goToPage,
    resetFilters,

    // helper
    formatDate,
    changeSorting, 

      leadDetail,
        loadingDetail,
        fetchLeadDetail,
  }
})
