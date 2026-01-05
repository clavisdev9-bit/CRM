import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from 'sweetalert2'


export const useRoleStore = defineStore('Data-Role', () => {
        const baseUrlApi = "/api/role-management"
        const rolesData = ref([]);
        const loadingRoles = ref(false)  
        const searchRoles = ref("");
        let searchTimeoutRoles = null;  
        
        const savingRole = ref(false)
        const errorRole = ref(null)

        const updatingRole = ref(false)
        const deletingRole = ref(false)

        const roleDetail = ref(null)
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

        const allowedSortColumns = ["role", "created_at"];

        const getAuthHeader = () => {
            const token = localStorage.getItem("auth_token");
            return { Authorization: `Bearer ${token}` };
        };


            const fetchRoles = async (url = "/api/role-management") => {
                loadingRoles.value = true;
                try {
                const response = await axios.get(url, {
                    headers: getAuthHeader(),
                });

                const result = response.data;
                const dataArray = Array.isArray(result.data)
                    ? result.data
                    : result.data?.data ?? [];

                rolesData.value.splice(0, rolesData.value.length, ...dataArray);

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
                loadingRoles.value = false;
                }
            };


            const buildUrl = () => {
                    const params = new URLSearchParams()
                            //ini code searching
                            if (searchRoles.value) {
                            params.append('search', searchRoles.value)
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
                            clearTimeout(searchTimeoutRoles)
                            searchRoles.value = val

                            // Reset ke halaman 1 saat pencarian
                            pagination.current_page = 1

                            searchTimeoutRoles = setTimeout(() => {
                                fetchRoles(buildUrl())
                            }, 500)
                            }


                             const changePageSize = () => {
                            pagination.current_page = 1
                            fetchRoles(buildUrl())
                            }

                             const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchRoles(buildUrl())
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
                                searchRoles.value = '' // ← ini yang ga ngefek ke v-model
                                pagination.per_page = 10
                                pagination.current_page = 1
                                sort.column = 'created_at'
                                sort.direction = 'desc'
                                fetchRoles(buildUrl())
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

                             
                            const detailRoles = async (roleId) => {
                                loading.value = true
                                try {
                                    const res = await axios.get(`/api/role-management-show/${roleId}`, {
                                    headers: getAuthHeader(),
                                    })
                                    roleDetail.value = res.data.data
                                } catch (err) {
                                    // console.error("Gagal ambil detail Role:", err)
                                    // alert("Gagal mengambil detail role.")
                                     Swal.fire({
                                      icon: "error",
                                      title: "Failed!",
                                      text: err.response?.data?.message || "Failed to retrieve role details.",
                                      confirmButtonText: "OK",
                                    })
                                } finally {
                                    loading.value = false
                                }
                                }

                                

                                    const storeRole = async (payload) => {
                                    savingRole.value = true
                                    errorRole.value = null

                                    try {
                                        const res = await axios.post(
                                        '/api/store-role-management',
                                        payload,
                                        { headers: getAuthHeader() }
                                        )

                                        await fetchRoles(buildUrl())
                                        return res.data
                                    } catch (err) {
                                        if (err.response?.status === 422) {
                                        errorRole.value = err.response.data.errors
                                        }
                                        throw err
                                    } finally {
                                        savingRole.value = false
                                    }
                                    }

                                    
                                 

                                  const updateRole = async (id, payload) => {
                                    updatingRole.value = true
                                    errorRole.value = null

                                    try {
                                      await axios.put(`/api/update-role-management/${id}`, payload, {
                                        headers: getAuthHeader(),
                                      })

                                      await fetchRoles(buildUrl())

                                    } catch (err) {
                                      if (err.response?.status === 422) {
                                        errorRole.value = err.response.data.errors
                                      }
                                      throw err
                                    } finally {
                                      // 🔥 INI YANG SERING LUPA
                                      updatingRole.value = false
                                    }
                                  }


                                  const deleteRole = async (id) => {
                                    deletingRole.value = true

                                    try {
                                      await axios.delete(`/api/delete-role-management/${id}`, {
                                        headers: getAuthHeader(),
                                      })

                                      await fetchRoles(buildUrl())

                                      Swal.fire({
                                        icon: 'success',
                                        title: 'Succeed',
                                        text: 'Role successfully deleted',
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
                                      deletingRole.value = false
                                    }
                                  }




            return{
                baseUrlApi,
                rolesData,
                loadingRoles,
                searchRoles,
                searchTimeoutRoles,
                pagination,
                sort,
                fetchRoles,
                searchWithDelay,
                changePageSize,
                toggleSort,
                changeSorting,
                resetFilters,
                buildUrl,
                formatDate,

                roleDetail,
                loading,
                detailRoles,

                savingRole,
                errorRole,
                storeRole,

                updatingRole,
                updateRole,

                 deleteRole,
                 deletingRole

            }


})