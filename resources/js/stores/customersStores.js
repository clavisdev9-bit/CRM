import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from 'sweetalert2'


export const useCustomersStore = defineStore("customersStore", () => {

      const endpoints = {
            categories: "/api/leads/select/category",
            industries: "/api/leads/select/industry",
            userSales: "/api/leads/select/user-sales",
        }
   
        const baseUrlApi = "/api/customers-masters"
        const customersData = ref([]);
        const loadingCustomers = ref(false)
        const searchCustomers = ref("");
        let searchTimeoutCustomers = null;  

        const savingCustomer = ref(false)
        const errorCustomer = ref(null)

        const updatingCustomer = ref(false)
        const deletingCustomer = ref(false)

        const customerDetail = ref(null)
        const loading = ref(false) 

        const categories = ref([])
        const industries = ref([])
        const userSales = ref([])

        const loadingCategories = ref(false)
        const loadingIndustries = ref(false)
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



        const fetchCustomers = async (url = "/api/customers-masters") => {
                loadingCustomers.value = true;
                try {
                const response = await axios.get(url, {
                    headers: getAuthHeader(),
                });

                const result = response.data;
                const dataArray = Array.isArray(result.data)
                    ? result.data
                    : result.data?.data ?? [];

                customersData.value.splice(0, customersData.value.length, ...dataArray);

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
                loadingCustomers.value = false;
                }
            };


             const buildUrl = () => {
                    const params = new URLSearchParams()
                            //ini code searching
                            if (searchCustomers.value) {
                            params.append('search', searchCustomers.value)
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
                            clearTimeout(searchTimeoutCustomers)
                            searchCustomers.value = val

                            // Reset ke halaman 1 saat pencarian
                            pagination.current_page = 1

                            searchTimeoutCustomers = setTimeout(() => {
                                fetchCustomers(buildUrl())
                            }, 500)
                            }


                             const changePageSize = () => {
                            pagination.current_page = 1
                            fetchCustomers(buildUrl())
                            }

                             const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchCustomers(buildUrl())
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
                                searchCustomers.value = '' // ← ini yang ga ngefek ke v-model
                                pagination.per_page = 10
                                pagination.current_page = 1
                                sort.column = 'created_at'
                                sort.direction = 'desc'
                                fetchCustomers(buildUrl())
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

                             
                            const detailCustomers = async (menuId) => {
                                loading.value = true
                                try {
                                    const res = await axios.get(`/api/customers/show/${menuId}`, {
                                    headers: getAuthHeader(),
                                    })
                                    customerDetail.value = res.data.data
                                } catch (err) {
                                  
                                     Swal.fire({
                                      icon: "error",
                                      title: "Failed!",
                                      text: err.response?.data?.message || "Failed to retrieve menu details.",
                                      confirmButtonText: "OK",
                                    })
                                } finally {
                                    loading.value = false
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

                         const storeCustomers = async (payload) => {
                                    savingCustomer.value = true
                                    errorCustomer.value = null

                                    try {
                                        const res = await axios.post(
                                        '/api/customers/store',
                                        payload,
                                        { headers: getAuthHeader() }
                                        )

                                        await fetchCustomers(buildUrl())
                                        return res.data
                                    } catch (err) {
                                        if (err.response?.status === 422) {
                                        errorCustomer.value = err.response.data.errors
                                        }
                                        throw err
                                    } finally {
                                        savingCustomer.value = false
                                    }
                                  }


                                     const updateCustomers = async (id, payload) => {
                                    updatingCustomer.value = true
                                    errorCustomer.value = null

                                    try {
                                      await axios.put(`/api/customers/update/${id}`, payload, {
                                        headers: getAuthHeader(),
                                      })

                                      await fetchCustomers(buildUrl())
                                    } catch (err) {
                                      if (err.response?.status === 422) {
                                        errorCustomer.value = err.response.data.errors
                                      }
                                      throw err
                                    } finally {
                                      //  INI YANG SERING LUPA
                                      updatingCustomer.value = false
                                    }
                                  }



                                  const deleteCustomer = async (id) => {
                                    deletingCustomer.value = true

                                    try {
                                      await axios.delete(`/api/customers/delete/${id}`, {
                                        headers: getAuthHeader(),
                                      })

                                      await fetchCustomers(buildUrl())
                                      Swal.fire({
                                        icon: 'success',
                                        title: 'Succeed',
                                        text: 'Menu successfully deleted',
                                        timer: 1500,
                                        showConfirmButton: false,
                                      })

                                    } catch (err) {
                                      if (err.response?.status === 403) {
                                        Swal.fire('Access Denied', 'You do not have permission to delete the menu', 'error')
                                      } else {
                                        Swal.fire('Error', 'Failed to delete menu', 'error')
                                      }
                                      throw err
                                    } finally {
                                      deletingCustomer.value = false
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





            return {
                baseUrlApi,
                customersData,
                loadingCustomers,
                searchCustomers,
                searchTimeoutCustomers,
                savingCustomer,
                errorCustomer,
                updatingCustomer,
                deletingCustomer,
                customerDetail,
                loading,
                pagination,
                sort,
                allowedSortColumns,
                getAuthHeader,
                fetchCustomers,
                buildUrl,
                searchWithDelay,
                changePageSize,
                changeSorting,
                toggleSort,
                resetFilters,
                formatDate,
                detailCustomers,
                errorCustomer,

                //new
                endpoints,
                categories,
                industries,
                userSales,
                loadingCategories,
                loadingIndustries,
                loadingUserSales,
                fetchLeadCategories,
                fetchLeadIndustries,
                leadSource,

                storeCustomers,
                updateCustomers,
                deleteCustomer

                

            }
})



