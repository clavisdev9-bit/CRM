import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from 'sweetalert2'


export const useSubmenuStore = defineStore('Data-Submenu', () => {
        const baseUrlApi = "/api/submenu-management"
        const SubmenuData = ref([]);
        const loadingSubmenu = ref(false)  
        const searchSubmenu = ref("");
        let searchTimeoutSubmenu = null;  

        const savingSubmenu = ref(false)
        const errorSubmenu = ref(null)

        const updatingSubmenu = ref(false)
        const deletingSubmenu = ref(false)

        const submenuDetail = ref(null)
        const loading = ref(false) 

         const submenuSelect = ref([])
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

        const allowedSortColumns = ["title", "created_at"];

        const getAuthHeader = () => {
            const token = localStorage.getItem("auth_token");
            return { Authorization: `Bearer ${token}` };
        };


            const fetchSubmenu = async (url = "/api/submenu-management") => {
                loadingSubmenu.value = true;
                try {
                const response = await axios.get(url, {
                    headers: getAuthHeader(),
                });

                const result = response.data;
                const dataArray = Array.isArray(result.data)
                    ? result.data
                    : result.data?.data ?? [];

                SubmenuData.value.splice(0, SubmenuData.value.length, ...dataArray);

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
                loadingSubmenu.value = false;
                }
            };


            const buildUrl = () => {
                    const params = new URLSearchParams()
                            //ini code searching
                            if (searchSubmenu.value) {
                            params.append('search', searchSubmenu.value)
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
                            clearTimeout(searchTimeoutSubmenu)
                            searchSubmenu.value = val

                            // Reset ke halaman 1 saat pencarian
                            pagination.current_page = 1

                            searchTimeoutSubmenu = setTimeout(() => {
                                fetchSubmenu(buildUrl())
                            }, 500)
                            }


                             const changePageSize = () => {
                            pagination.current_page = 1
                            fetchSubmenu(buildUrl())
                            }

                             const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchSubmenu(buildUrl())
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
                                searchSubmenu.value = '' // ← ini yang ga ngefek ke v-model
                                pagination.per_page = 10
                                pagination.current_page = 1
                                sort.column = 'created_at'
                                sort.direction = 'desc'
                                fetchSubmenu(buildUrl())
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

                             
                            const detailSubmenu = async (submenuId) => {
                                loading.value = true
                                try {
                                    const res = await axios.get(`/api/submenu-management-show/${submenuId}`, {
                                    headers: getAuthHeader(),
                                    })
                                    submenuDetail.value = res.data.data
                                } catch (err) {
                                    // console.error("Gagal ambil detail Submenu:", err)
                                    // alert("Gagal mengambil detail submenu.")
                                     Swal.fire({
                                      icon: "error",
                                      title: "Failed!",
                                      text: err.response?.data?.message || "Failed to fetch submenu details.",
                                      confirmButtonText: "OK",
                                    })
                                } finally {
                                    loading.value = false
                                }
                                }

                                

                                    const storeSubmenu = async (payload) => {
                                    savingSubmenu.value = true
                                    errorSubmenu.value = null

                                    try {
                                        const res = await axios.post(
                                        '/api/store-submenu-management',
                                        payload,
                                        { headers: getAuthHeader() }
                                        )

                                        await fetchSubmenu(buildUrl())
                                        return res.data
                                    } catch (err) {
                                        if (err.response?.status === 422) {
                                        errorSubmenu.value = err.response.data.errors
                                        }
                                        throw err
                                    } finally {
                                        savingSubmenu.value = false
                                    }
                                    }

                                    
                                 

                                  const updateSubmenu = async (id, payload) => {
                                    updatingSubmenu.value = true
                                    errorSubmenu.value = null

                                    try {
                                      await axios.put(`/api/update-submenu-management/${id}`, payload, {
                                        headers: getAuthHeader(),
                                      })

                                      await fetchSubmenu(buildUrl())

                                    } catch (err) {
                                      if (err.response?.status === 422) {
                                        errorSubmenu.value = err.response.data.errors
                                      }
                                      throw err
                                    } finally {
                                      // 🔥 INI YANG SERING LUPA
                                      updatingSubmenu.value = false
                                    }
                                  }


                                  const deleteSubmenu = async (id) => {
                                    deletingSubmenu.value = true

                                    try {
                                      await axios.delete(`/api/delete-submenu-management/${id}`, {
                                        headers: getAuthHeader(),
                                      })

                                      await fetchSubmenu(buildUrl())

                                      Swal.fire({
                                        icon: 'success',
                                        title: 'Succeed',
                                        text: 'Submenu successfully deleted',
                                        timer: 1500,
                                        showConfirmButton: false,
                                      })

                                    } catch (err) {
                                      if (err.response?.status === 403) {
                                        Swal.fire('Access Denied', 'You do not have permission to delete the submenu', 'error')
                                      } else {
                                        Swal.fire('Error', 'Failed to delete submenu', 'error')
                                      }
                                      throw err
                                    } finally {
                                      deletingSubmenu.value = false
                                    }
                                  }


                               const statusStatis = ref([
                                { value: true, label: 'Aktif' },
                                { value: false, label: 'Non-Aktif' }
                                ])

                               


                                const fetchSubmenuSelect = async () => {
                                  loadingSelect.value = true
                                  try {
                                    const { data } = await axios.get('/api/submenu-select', {
                                      headers: {
                                        ...getAuthHeader(),
                                      },
                                    })
                                    submenuSelect.value = data
                                  } finally {
                                    loadingSelect.value = false
                                  }
                                }



                                



            return{
                baseUrlApi,
                SubmenuData,
                loadingSubmenu,
                searchSubmenu,
                searchTimeoutSubmenu,
                pagination,
                sort,
                fetchSubmenu,
                searchWithDelay,
                changePageSize,
                toggleSort,
                changeSorting,
                resetFilters,
                buildUrl,
                formatDate,

                submenuDetail,
                loading,
                detailSubmenu,

                savingSubmenu,
                errorSubmenu,
                storeSubmenu,

                updatingSubmenu,
                updateSubmenu,

                 deleteSubmenu,
                 deletingSubmenu,

                 statusStatis,

                 fetchSubmenuSelect,
                 submenuSelect,
                 loadingSelect,

            }


})