import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from 'sweetalert2'

export const useLeadByAdminCreate = defineStore('Data-Lead-By-Admin-Create', () => {


        /* ================= ENDPOINT ================= */
        const endpoints = {
            categories: "/api/leads/select/category",
            industries: "/api/leads/select/industry",
            userSales: "/api/leads/select/user-sales",

            store: "/api/leads-store",
            storeBulk: "/api/leads-store-bulk",
        }

        const baseUrlApi = "/api/all-leads-master-created-by-admin"
        const LeadData = ref([]);
        const loadingLead = ref(false)  
        const searchLead = ref("");
        let searchTimeoutLead = null;  
        

        const updatingLead = ref(false)
        const deletingLead = ref(false)
        const leadDetail = ref(null)
        const loading = ref(false) 
        const loadingDetail = ref(false)
        const loadingLeads = ref(false)


        const categories = ref([])
        const industries = ref([])
        const userSales = ref([])

        const loadingCategories = ref(false)
        const loadingIndustries = ref(false)
        const loadingUserSales = ref(false)

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
        });

        const sort = reactive({
            column: "created_at",
            direction: "desc",
        });

        const allowedSortColumns = [
                "company_name",
                "contact_name",
                "created_at",];

        const getAuthHeader = () => {
            const token = localStorage.getItem("auth_token");
            return { Authorization: `Bearer ${token}` };
        };



        const fetchLeadsByAdmin = async (url = "/api/all-leads-master-created-by-admin") => {
                loadingLead.value = true;
                try {
                const response = await axios.get(url, {
                    headers: getAuthHeader(),
                });

                const result = response.data;
                const dataArray = Array.isArray(result.data)
                    ? result.data
                    : result.data?.data ?? [];

                LeadData.value.splice(0, LeadData.value.length, ...dataArray);

                const pag = result.pagination ?? result.data?.pagination
                                if (pag) {
                                    pagination.current_page = pag.current_page
                                    pagination.per_page = pag.per_page
                                    pagination.prev_page_url = pag.prev_page_url
                                    pagination.next_page_url = pag.next_page_url
                                    pagination.last_page = pag.last_page
                                    pagination.total = pag.total
                                }

                } catch (error) {
                console.error("Gagal fetch:", error);
                } finally {
                loadingLead.value = false;
                }
            };


            const buildUrl = () => {
                    const params = new URLSearchParams()
                            //ini code searching
                            if (searchLead.value) {
                            params.append('search', searchLead.value)
                            }

                            if (pagination.current_page) {
                                params.append('page', pagination.current_page)
                              }

                            if (pagination.per_page) {
                                    params.append('per_page', pagination.per_page)
                                }


                                 if (sort.column) {
                                    params.append('sort_by', sort.column)
                                    params.append('sort_dir', sort.direction)
                                }


                             return `${baseUrlApi}?${params.toString()}`
                        }




                         const searchWithDelay = (val) => {
                            clearTimeout(searchTimeoutLead)
                            searchLead.value = val

                            // Reset ke halaman 1 saat pencarian
                            pagination.current_page = 1

                            searchTimeoutLead = setTimeout(() => {
                                fetchLeadsByAdmin(buildUrl())
                            }, 500)
                            }


                             const changePageSize = () => {
                            pagination.current_page = 1
                            fetchLeadsByAdmin(buildUrl())
                            }

                             const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchLeadsByAdmin(buildUrl())
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
                                searchLead.value = '' // ← ini yang ga ngefek ke v-model
                                pagination.per_page = 10
                                pagination.current_page = 1
                                sort.column = 'created_at'
                                sort.direction = 'desc'
                                fetchLeadsByAdmin(buildUrl())
                            }
                            

                             const formatDate = (dateStr) => {
                                    if (!dateStr) return '-'
                                            const date = new Date(dateStr)
                                        if (isNaN(date.getTime())) {
                                            return 'Belum Di Pernah update'
                                        }
                                        const options = {
                                            year: 'numeric',
                                            month: 'long',
                                            day: '2-digit'
                                        }
                               return date.toLocaleDateString('id-ID', options)
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


                    const fetchLeadUserSales = async () => {
                    loadingUserSales.value = true

                    try {
                        const res = await axios.get(endpoints.userSales, {
                        headers: getAuthHeader(),
                        })

                        userSales.value = res.data ?? []
                    } catch (err) {
                        console.error("Fetch user sales failed:", err)
                        userSales.value = []
                    } finally {
                        loadingUserSales.value = false
                    }
                    }


                    const leadSource = ref([
                    { value: 'Cold Call', label: 'Cold Call' },
                    { value: 'Website', label: 'Website' },
                    { value: 'Referral', label: 'Referral' },
                    { value: 'Social Media', label: 'Social Media' },,
                    { value: 'Email Campaign', label: 'Email Campaign' },
                    { value: 'Event', label: 'Event' },
                    { value: 'Partner', label: 'Partner' },
                    { value: 'Ads', label: 'Ads' },
                    { value: 'Other', label: 'Other' },
                    ])


                    const visibilityType = ref([
                    { value: 'PUBLIC', label: 'PUBLIC' },
                    { value: 'PRIVATE', label: 'PRIVATE' },
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
                                    await fetchLeadsByAdmin(buildUrl())

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

                                        await fetchLeadsByAdmin(buildUrl())
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



                                return{
                                    LeadData,
                                    loadingLead,
                                    searchLead,
                                    fetchLeadsByAdmin,
                                    pagination,
                                    sort,
                                    toggleSort,
                                    buildUrl,
                                    searchWithDelay,
                                    changePageSize,
                                    changeSorting,
                                    resetFilters,
                                    formatDate,
                                    leadDetail,
                                    loadingLeads,
                                    loadingDetail,
                                    loading,
                                    fetchLeadDetail,
                                    leadSource,
                                    visibilityType,

                                        categories,
                                        industries,
                                        userSales,
                                        loadingCategories,
                                        loadingIndustries,
                                        loadingUserSales,


                                        fetchLeadCategories,
                                        fetchLeadIndustries,
                                        fetchLeadUserSales,

                                        storeLead,
                                        storeBulkLeads
                                        

                                }
                    }); 