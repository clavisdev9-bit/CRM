
import { ref, reactive } from "vue"
import { defineStore } from "pinia"
import axios from "axios"

export const useLeadsStore = defineStore("leadsStore", () => {

  /* ================= ENDPOINT ================= */
  const endpoints = {
    all: "/api/leads-master",
    assigned: "/api/leads-assigned-to-me",

    
    categories: "/api/leads/select/category",
    industries: "/api/leads/select/industry",

    store: "/api/leads-store",
    storeBulk: "/api/leads-store-bulk",
  }

  /* ================= STATE ================= */
  const leads = ref([])
  const mode = ref("all")
  const loading = ref(false)

  const search = ref("")
  let searchTimeout = null

  const leadDetail = ref(null)
 const loadingDetail = ref(false)

 const categories = ref([])
const industries = ref([])

const loadingCategories = ref(false)
const loadingIndustries = ref(false)

const savingLead = ref(false)
const updatesLead = ref(false)
const errorLead = ref(null)




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
  // const fetchLeads = async (newMode = null) => {
  //   loading.value = true

  //   if (newMode) {
  //     mode.value = newMode
  //     pagination.current_page = 1
  //   }

  //   try {
  //     const res = await axios.get(buildUrl(), {
  //       headers: getAuthHeader(),
  //     })

  //     const result = res.data
  //     leads.value = result.data?.data ?? []

  //     const pag = result.data?.pagination
  //     if (pag) {
  //       pagination.current_page = pag.current_page
  //       pagination.per_page = pag.per_page
  //       pagination.prev_page_url = pag.prev_page_url
  //       pagination.next_page_url = pag.next_page_url
  //       pagination.last_page = pag.last_page
  //       pagination.total = pag.total
  //     }

  //   } catch (err) {
  //     console.error("Fetch leads failed:", err)
  //     leads.value = []
  //   } finally {
  //     loading.value = false
  //   }
  // }

  const fetchLeads = async (newMode = null, page = null) => {
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
  // const goToPage = (page) => {
  //   if (page < 1 || page > pagination.last_page) return
  //   pagination.current_page = page
  //   fetchLeads()
  // }

  const goToPage = (page) => {
  if (page < 1 || page > pagination.last_page) return
  fetchLeads(null, page)
}

const nextPage = () => {
  if (pagination.current_page < pagination.last_page) {
    fetchLeads(null, pagination.current_page + 1)
  }
}


const prevPage = () => {
  if (pagination.current_page > 1) {
    fetchLeads(null, pagination.current_page - 1)
  }
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


const fetchLeadCategories = async () => {
  loadingCategories.value = true

  try {
    const res = await axios.get(endpoints.categories, {
      headers: getAuthHeader(),
    })

    categories.value = res.data ?? []
  } catch (err) {
    console.error("Fetch categories failed:", err)
    categories.value = []
  } finally {
    loadingCategories.value = false
  }
}



const fetchLeadIndustries = async () => {
  loadingIndustries.value = true

  try {
    const res = await axios.get(endpoints.industries, {
      headers: getAuthHeader(),
    })

    industries.value = res.data ?? []
  } catch (err) {
    console.error("Fetch industries failed:", err)
    industries.value = []
  } finally {
    loadingIndustries.value = false
  }
}


const leadSource = ref([
  { value: 'Cold Call', label: 'Cold Call' },
  { value: 'Website', label: 'Website' },
  { value: 'Referral', label: 'Referral' },,
  { value: 'Social Media', label: 'Social Media' },,
  { value: 'Email Campaign', label: 'Email Campaign' },
  { value: 'Event', label: 'Event' },
  { value: 'Partner', label: 'Partner' },
  { value: 'Ads', label: 'Ads' },
  { value: 'Other', label: 'Other' },
])


const storeLead = async (payload) => {
  savingLead.value = true
  errorLead.value = null

  try {
    const res = await axios.post(
      '/api/leads-store',
      payload,
      { headers: getAuthHeader() }
    )

    // refresh list
    await fetchLeads(mode.value)

    return res.data

  } catch (err) {
    if (err.response?.status === 422) {
      errorLead.value = err.response.data.errors
    }
    throw err

  } finally {
    savingLead.value = false
  }
}

const storeBulkLeads = async (leads) => {
  savingLead.value = true
  errorLead.value = null

  try {
    const res = await axios.post(
      '/api/leads-store-bulk',
      { leads },
      { headers: getAuthHeader() }
    )

    await fetchLeads(mode.value)

    return res.data

  } catch (err) {
   
    if (err.response?.status === 422) {
  errorLead.value = { ...err.response.data.errors }
}

    throw err

  } finally {
    savingLead.value = false
  }
}


const updateLead = async (id, payload) => {
  updatesLead.value = true
  errorLead.value = null

  try {
    const res = await axios.put(
      `/api/leads-update/${id}`,
      payload,
      { headers: getAuthHeader() }
    )

    await fetchLeads(mode.value)

    return res.data

  } catch (err) {
    if (err.response?.status === 422) {
      errorLead.value = err.response.data.errors
    }
    throw err

  } finally {
    updatesLead.value = false
  }
}


const deleteLead = async (id) => {
  savingLead.value = true // optional, bisa pakai updatesLead juga
  errorLead.value = null

  try {
    const res = await axios.delete(
      `/api/leads-delete/${id}`, // sesuaikan endpoint API-mu
      { headers: getAuthHeader() }
    )

    // refresh list setelah delete
    await fetchLeads(mode.value, pagination.current_page)

    return res.data

  } catch (err) {
    if (err.response?.status === 422) {
      errorLead.value = err.response.data.errors
    }
    throw err
  } finally {
    savingLead.value = false
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
     nextPage,
      prevPage,
    resetFilters,

    // helper
    formatDate,
    changeSorting, 

    leadDetail,
    loadingDetail,
    fetchLeadDetail,
    leadSource,

    categories,
    industries,
    loadingCategories,
    loadingIndustries,

    fetchLeadCategories,
    fetchLeadIndustries,

     // state
  savingLead,
  errorLead,

  // action
  storeLead,
  storeBulkLeads,

  updateLead,
  updatesLead,

  deleteLead,

  }
})
