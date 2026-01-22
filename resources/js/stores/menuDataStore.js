import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from 'sweetalert2'


export const useDataMenuStore = defineStore('Data-Menu', () => {
        const baseUrlApi = "/api/menu-management"
        const menuData = ref([]);
        const loadingMenus = ref(false)  
        const searchMenus = ref("");
        let searchTimeoutMenus = null;  

        const savingMenu = ref(false)
        const errorMenu = ref(null)

        const updatingMenu = ref(false)
        const deletingMenu = ref(false)

        const menuDetail = ref(null)
        const loading = ref(false) 


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

        const allowedSortColumns = ["menu", "created_at"];

        const getAuthHeader = () => {
            const token = localStorage.getItem("auth_token");
            return { Authorization: `Bearer ${token}` };
        };


            const fetchMenus = async (url = "/api/menu-management") => {
                loadingMenus.value = true;
                try {
                const response = await axios.get(url, {
                    headers: getAuthHeader(),
                });

                const result = response.data;
                const dataArray = Array.isArray(result.data)
                    ? result.data
                    : result.data?.data ?? [];

                menuData.value.splice(0, menuData.value.length, ...dataArray);

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
                loadingMenus.value = false;
                }
            };


            const buildUrl = () => {
                    const params = new URLSearchParams()
                            //ini code searching
                            if (searchMenus.value) {
                            params.append('search', searchMenus.value)
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
                            clearTimeout(searchTimeoutMenus)
                            searchMenus.value = val

                            // Reset ke halaman 1 saat pencarian
                            pagination.current_page = 1

                            searchTimeoutMenus = setTimeout(() => {
                                fetchMenus(buildUrl())
                            }, 500)
                            }


                             const changePageSize = () => {
                            pagination.current_page = 1
                            fetchMenus(buildUrl())
                            }

                             const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchMenus(buildUrl())
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
                                searchMenus.value = '' // ← ini yang ga ngefek ke v-model
                                pagination.per_page = 10
                                pagination.current_page = 1
                                sort.column = 'created_at'
                                sort.direction = 'desc'
                                fetchMenus(buildUrl())
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

                             
                            const detailMenus = async (menuId) => {
                                loading.value = true
                                try {
                                    const res = await axios.get(`/api/menu-management-show/${menuId}`, {
                                    headers: getAuthHeader(),
                                    })
                                    menuDetail.value = res.data.data
                                } catch (err) {
                                    // console.error("Gagal ambil detail Role:", err)
                                    // alert("Gagal mengambil detail role.")
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

                                

                                  const storeMenu = async (payload) => {
                                    savingMenu.value = true
                                    errorMenu.value = null

                                    try {
                                        const res = await axios.post(
                                        '/api/store-menu-management',
                                        payload,
                                        { headers: getAuthHeader() }
                                        )

                                        await fetchMenus(buildUrl())
                                        return res.data
                                    } catch (err) {
                                        if (err.response?.status === 422) {
                                        errorMenu.value = err.response.data.errors
                                        }
                                        throw err
                                    } finally {
                                        savingMenu.value = false
                                    }
                                  }

                                    
                                 

                                  const updateMenu = async (id, payload) => {
                                    updatingMenu.value = true
                                    errorMenu.value = null

                                    try {
                                      await axios.put(`/api/update-menu-management/${id}`, payload, {
                                        headers: getAuthHeader(),
                                      })

                                      await fetchMenus(buildUrl())
                                    } catch (err) {
                                      if (err.response?.status === 422) {
                                        errorRole.value = err.response.data.errors
                                      }
                                      throw err
                                    } finally {
                                      //  INI YANG SERING LUPA
                                      updatingMenu.value = false
                                    }
                                  }


                                  const deleteMenu = async (id) => {
                                    deletingMenu.value = true

                                    try {
                                      await axios.delete(`/api/delete-menu-management/${id}`, {
                                        headers: getAuthHeader(),
                                      })

                                      await fetchMenus(buildUrl())
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
                                      deletingMenu.value = false
                                    }
                                  }




            return{
                baseUrlApi,
                menuData,
                loadingMenus,
                searchMenus,
                searchTimeoutMenus,
                pagination,
                sort,
                fetchMenus,
                searchWithDelay,
                changePageSize,
                toggleSort,
                changeSorting,
                resetFilters,
                buildUrl,
                formatDate,

                menuDetail,
                loading,
                detailMenus,

                savingMenu,
                errorMenu,
                storeMenu,

                updatingMenu,
                updateMenu,
                 deleteMenu,
                 deletingMenu

            }


})