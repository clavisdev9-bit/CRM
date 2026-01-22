import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from 'sweetalert2'



export const useFollowUpsStore = defineStore("followUpStore", () => {

 const endpoints = {
            leads: "/api/follow-up/get-sales/leads",
            customers: "/api/follow-up/get-sales/customers",
            userSales: "/api/leads/select/user-sales",
    }

        const baseUrlApi = "/api/follow-up-masters"
        const followUpData = ref([]);
        const loadingFollowUp = ref(false)
        const searchFollowUp = ref("");
        let searchTimeoutFollowUp = null;  

        const savingFollowUp = ref(false)
        const errorFollowUp = ref(null)
        

        const updatingFollowUp = ref(false)
        const deletingFollowUp = ref(false)

        const followUpDetail = ref(null)
        const loadingDetail = ref(false)

        const leads = ref([])
        const customers = ref([])
        const userSales = ref([])

        const loadingLeads = ref(false)
        const loadingCustomers = ref(false)
        const loadingUserSales = ref(false)

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

        const allowedSortColumns = ["company_name", "created_at"];

        const getAuthHeader = () => {
            const token = localStorage.getItem("auth_token");
            return { Authorization: `Bearer ${token}` };
        };


         const fetchFollowUp = async (url = "/api/follow-up-masters") => {
                loadingFollowUp.value = true;
                try {
                const response = await axios.get(url, {
                    headers: getAuthHeader(),
                });

                const result = response.data;
                const dataArray = Array.isArray(result.data)
                    ? result.data
                    : result.data?.data ?? [];

                followUpData.value.splice(0, followUpData.value.length, ...dataArray);

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
                loadingFollowUp.value = false;
                }
            };


             const buildUrl = () => {
                    const params = new URLSearchParams()
                            //ini code searching
                            if (searchFollowUp.value) {
                            params.append('search', searchFollowUp.value)
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
                            clearTimeout(searchTimeoutFollowUp)
                            searchFollowUp.value = val

                            // Reset ke halaman 1 saat pencarian
                            pagination.current_page = 1

                            searchTimeoutFollowUp = setTimeout(() => {
                                fetchFollowUp(buildUrl())
                            }, 500)
                            }


                             const changePageSize = () => {
                            pagination.current_page = 1
                            fetchFollowUp(buildUrl())
                            }

                             const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchFollowUp(buildUrl())
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
                                searchFollowUp.value = '' // ← ini yang ga ngefek ke v-model
                                pagination.per_page = 10
                                pagination.current_page = 1
                                sort.column = 'created_at'
                                sort.direction = 'desc'
                                fetchFollowUp(buildUrl())
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

                             
                            const fetchFollowUpDetail = async (id) => {
                                    loadingDetail.value = true
                                    followUpDetail.value = null

                                    try {
                                    const res = await axios.get(`/api/follow-up/show/${id}`, {
                                        headers: getAuthHeader(),
                                    })
                                    followUpDetail.value = res.data.data
                                    } catch (err) {
                                    Swal.fire({
                                        icon: "error",
                                        title: "Failed!",
                                        text: err.response?.data?.message || "Failed to retrieve follow up detail.",
                                    })
                                    } finally {
                                    loadingDetail.value = false
                                    }
                                }


                                const formatDateTime = (datetime) => {
                                if (!datetime) return '-'

                                const date = new Date(datetime)

                                return date.toLocaleString('id-ID', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                })
                                }


            return {

                baseUrlApi,
                followUpData,
                loadingFollowUp,
                searchFollowUp,
                searchTimeoutFollowUp,
                savingFollowUp,
                errorFollowUp,
                updatingFollowUp,
                deletingFollowUp,
                // FollowUpDetail,
                // loading,
                leads,
                customers,
                userSales,
                loadingLeads,
                loadingCustomers,
                loadingUserSales,
                pagination,
                sort,
                allowedSortColumns,
                fetchFollowUp,
                getAuthHeader,
                buildUrl,
                searchWithDelay,
                changePageSize,
                toggleSort,
                changeSorting,
                resetFilters,
                formatDate,
                formatDateTime,
                // detailFollowUp,


                followUpDetail,
                loadingDetail,
                fetchFollowUpDetail
                
            }


})