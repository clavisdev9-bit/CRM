import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from 'sweetalert2'

export const useMasterSalesStore = defineStore('Data-Master-Sales', () => {
        const baseUrlApi = "/api/employee-management"
        const MasterSalesData = ref([]);
        const loadingMasterSalesData = ref(false)  
        const searchMasterSalesData = ref("");
        let searchTimeoutMasterSalesData = null;  

        const savingMasterSalesData = ref(false)
        const errorMasterSalesData = ref(null)

        const updatingMasterSalesData = ref(false)
        const deletingMasterSalesData = ref(false)

        const MasterSalesDataDetail = ref(null)
        const loading = ref(false) 

        const userSelect = ref([])
        const loadingSelect = ref(false)

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

        const allowedSortColumns = ["nik", "created_at"];

        const getAuthHeader = () => {
            const token = localStorage.getItem("auth_token");
            return { Authorization: `Bearer ${token}` };
        };


        const fetchMasterSalesData = async (url = "/api/employee-management") => {
                loadingMasterSalesData.value = true;
                try {
                const response = await axios.get(url, {
                    headers: getAuthHeader(),
                });

                const result = response.data;
                const dataArray = Array.isArray(result.data)
                    ? result.data
                    : result.data?.data ?? [];

                MasterSalesData.value.splice(0, MasterSalesData.value.length, ...dataArray);

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
                loadingMasterSalesData.value = false;
                }
            };


             const buildUrl = () => {
                    const params = new URLSearchParams()
                            //ini code searching
                            if (searchMasterSalesData.value) {
                            params.append('search', searchMasterSalesData.value)
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
                            clearTimeout(searchTimeoutMasterSalesData)
                            searchMasterSalesData.value = val

                            // Reset ke halaman 1 saat pencarian
                            pagination.current_page = 1

                            searchTimeoutMasterSalesData = setTimeout(() => {
                                fetchMasterSalesData(buildUrl())
                            }, 500)
                            }


                             const changePageSize = () => {
                            pagination.current_page = 1
                            fetchMasterSalesData(buildUrl())
                            }

                             const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchMasterSalesData(buildUrl())
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
                                searchMasterSalesData.value = '' // ← ini yang ga ngefek ke v-model
                                pagination.per_page = 10
                                pagination.current_page = 1
                                sort.column = 'created_at'
                                sort.direction = 'desc'
                                fetchMasterSalesData(buildUrl())
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

                             
                            const fetchDetailMasterSalesData = async (masterSalesId) => {
                                loading.value = true
                                try {
                                    const res = await axios.get(`/api/employee-management-show/${masterSalesId}`, {
                                    headers: getAuthHeader(),
                                    })
                                    MasterSalesDataDetail.value = res.data.data
                                } catch (err) {
                                     Swal.fire({
                                      icon: "error",
                                      title: "Failed!",
                                      text: err.response?.data?.message || "Failed to fetch Master sales details.",
                                      confirmButtonText: "OK",
                                    })
                                } finally {
                                    loading.value = false
                                }
                                }

                                const statusGender = ref([
                                { value: 'L', label: 'Male' },
                                { value: 'P', label: 'Female' }
                                ])

                                const statusJobs = ref([
                                { value: 'Tetap', label: 'permanent' },
                                { value: 'Kontrak', label: 'Contract' }
                                ])

                               


                                const fetchUserSelect = async (employeeId = null) => {
                                    loadingSelect.value = true
                                    try {
                                        const { data } = await axios.get('/api/employee-available-users', {
                                        params: {
                                            employee_id: employeeId
                                        },
                                        headers: getAuthHeader(),
                                        })
                                        userSelect.value = data.data
                                    } finally {
                                        loadingSelect.value = false
                                    }
                                    }


                                    const storeMasterSales = async (payload) => {
                                    savingMasterSalesData.value = true
                                    errorMasterSalesData.value = null

                                    try {
                                        const res = await axios.post(
                                        '/api/employee-store-management',
                                        payload,
                                        { headers: getAuthHeader() }
                                        )

                                        await fetchMasterSalesData(buildUrl())
                                        return res.data
                                    } catch (err) {
                                        if (err.response?.status === 422) {
                                        errorMasterSalesData.value = err.response.data.errors
                                        }
                                        throw err
                                    } finally {
                                        savingMasterSalesData.value = false
                                    }
                                    }



                                const updateMasterSales = async (id, payload) => {
                                    updatingMasterSalesData.value = true
                                    errorMasterSalesData.value = null

                                    try {
                                      await axios.put(`/api/employee-update-management/${id}`, payload, {
                                        headers: getAuthHeader(),
                                      })

                                      await fetchMasterSalesData(buildUrl())

                                    } catch (err) {
                                      if (err.response?.status === 422) {
                                        errorMasterSalesData.value = err.response.data.errors
                                      }
                                      throw err
                                    } finally {
                                      // INI YANG SERING LUPA
                                      updatingMasterSalesData.value = false
                                    }
                                  }


                                  const deleteMasterSales = async (id) => {
                                    deletingMasterSalesData.value = true

                                    try {
                                      await axios.delete(`/api/employee-delete-management/${id}`, {
                                        headers: getAuthHeader(),
                                      })

                                      await fetchMasterSalesData(buildUrl())

                                      Swal.fire({
                                        icon: 'success',
                                        title: 'Succeed',
                                        text: 'Delete successfully deleted',
                                        timer: 1500,
                                        showConfirmButton: false,
                                      })

                                    } catch (err) {
                                      if (err.response?.status === 403) {
                                        Swal.fire('Access Denied', 'You do not have permission to delete the role', 'error')
                                      } else {
                                        Swal.fire('Error', 'Failed to delete role', 'error')
                                      }
                                      throw err
                                    } finally {
                                      deletingMasterSalesData.value = false
                                    }
                                  }





                            return{
                                baseUrlApi,
                                MasterSalesData,
                                loadingMasterSalesData,
                                searchMasterSalesData,
                                searchTimeoutMasterSalesData,
                                pagination,
                                sort,
                                fetchMasterSalesData,
                                searchWithDelay,
                                changePageSize,
                                toggleSort,
                                changeSorting,
                                resetFilters,
                                buildUrl,
                                formatDate,

                                fetchDetailMasterSalesData,
                                loading,
                                MasterSalesDataDetail,
                                statusGender,
                                statusJobs,

                                errorMasterSalesData,
                                savingMasterSalesData,
                                updatingMasterSalesData,

                                userSelect,
                                loadingSelect,
                                fetchUserSelect,

                                storeMasterSales,
                                updateMasterSales,
                                deletingMasterSalesData,
                                deleteMasterSales


                            }


})