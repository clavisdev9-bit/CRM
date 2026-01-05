import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from 'sweetalert2'


export const useUsersStore = defineStore('Data-Users', () => {
        const baseUrlApi = "/api/users-management"
        const usersData = ref([]);
        const loadingUsers = ref(false)  
        const searchUsers = ref("");
        let searchTimeoutUsers = null;  

        const savingUsers = ref(false)
        const errorUsers = ref(null)

        const updatingUsers = ref(false)
        const deletingUsers = ref(false)

        const usersDetail = ref(null)
        const loading = ref(false) 

        const roleSelect = ref([])
        const divisionSelect = ref([])
        const groupSelect = ref([])
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


//         let abortController = null
// const lastFetchUrl = ref(null)

// const fetchUsers = async (url = "/api/users-management") => {
//   // ⛔ jangan fetch ulang kalau URL sama
//   if (lastFetchUrl.value === url) return

//   lastFetchUrl.value = url

//   // ⛔ batalkan request sebelumnya
//   if (abortController) {
//     abortController.abort()
//   }

//   abortController = new AbortController()
//   loadingUsers.value = true

//   try {
//     const response = await axios.get(url, {
//       headers: getAuthHeader(),
//       signal: abortController.signal,
//     })

//     const result = response.data

//     const dataArray = Array.isArray(result.data)
//       ? result.data
//       : result.data?.data ?? []

//     // update array TANPA ganti reference
//     usersData.value.splice(0, usersData.value.length, ...dataArray)

//     const pag = result.pagination ?? result.data?.pagination
//     if (pag) {
//       pagination.current_page = pag.current_page
//       pagination.per_page = pag.per_page
//       pagination.prev_page_url = pag.prev_page_url
//       pagination.next_page_url = pag.next_page_url
//       pagination.last_page = pag.last_page
//       pagination.total = pag.total
//     }

//   } catch (error) {
//     if (error.name !== "CanceledError") {
//       console.error("Gagal fetch users:", error)
//     }
//   } finally {
//     loadingUsers.value = false
//   }
// }


             //kode lama kurang cepat
            const fetchUsers = async (url = "/api/users-management") => {
                loadingUsers.value = true;
                try {
                const response = await axios.get(url, {
                    headers: getAuthHeader(),
                });

                const result = response.data;
                const dataArray = Array.isArray(result.data)
                    ? result.data
                    : result.data?.data ?? [];

                usersData.value.splice(0, usersData.value.length, ...dataArray);

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
                loadingUsers.value = false;
                }
            };


            const buildUrl = () => {
                    const params = new URLSearchParams()
                            //ini code searching
                            if (searchUsers.value) {
                            params.append('search', searchUsers.value)
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
                            clearTimeout(searchTimeoutUsers)
                            searchUsers.value = val

                            // Reset ke halaman 1 saat pencarian
                            pagination.current_page = 1

                            searchTimeoutUsers = setTimeout(() => {
                                fetchUsers(buildUrl())
                            }, 500)
                            }


                             const changePageSize = () => {
                            pagination.current_page = 1
                            fetchUsers(buildUrl())
                            }

                             const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchUsers(buildUrl())
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
                                searchUsers.value = '' // ← ini yang ga ngefek ke v-model
                                pagination.per_page = 10
                                pagination.current_page = 1
                                sort.column = 'created_at'
                                sort.direction = 'desc'
                                fetchUsers(buildUrl())
                            }
                            

                             const formatDate = (dateStr) => {
                                    if (!dateStr) return '-'
                                            const date = new Date(dateStr)
                                        if (isNaN(date.getTime())) {
                                            return 'Not Updated Yet'
                                        }
                                        const options = {
                                            year: 'numeric',
                                            month: 'long',
                                            day: '2-digit'
                                        }
                            return date.toLocaleDateString('id-ID', options)
                            }

                             
                            const detailUser = async (userId) => {
                                loading.value = true
                                try {
                                    const res = await axios.get(`/api/users-management/show/${userId}`, {
                                    headers: getAuthHeader(),
                                    })
                                    usersDetail.value = res.data.data
                                } catch (err) {
                                     Swal.fire({
                                      icon: "error",
                                      title: "Failed!",
                                      text: err.response?.data?.message || "Failed to retrieve user details.",
                                      confirmButtonText: "OK",
                                    })
                                } finally {
                                    loading.value = false
                                }
                                }

                                
                                const storeUser = async (payload) => {
                                    savingUsers.value = true
                                    try {
                                      const formData = new FormData()

                                      Object.keys(payload).forEach((key) => {
                                        if (payload[key] !== null && payload[key] !== undefined) {
                                          formData.append(key, payload[key])
                                        }
                                      })
                                      const res = await axios.post(
                                        '/api/store-users-management',
                                        formData,
                                        {
                                          headers: {
                                            ...getAuthHeader(),
                                            'Content-Type': 'multipart/form-data',
                                          },
                                        }
                                      )
                                        await fetchUsers(buildUrl())
                                        return res.data
                                      } catch (err) {
                                        throw err 
                                      } finally {
                                        savingUsers.value = false
                                      }
                                    }

                                 

                              

                           const updateUser = async (id, payload) => {
                                  updatingUsers.value = true

                                  try {
                                    const formData = new FormData()

                                    Object.keys(payload).forEach((key) => {
                                      if (payload[key] !== null && payload[key] !== undefined) {
                                        formData.append(key, payload[key])
                                      }
                                    })

                                    // WAJIB → trick Laravel agar PUT terbaca
                                    formData.append('_method', 'PUT')

                                    const res = await axios.post(
                                      `/api/update-users-management/${id}`,
                                      formData,
                                      {
                                        headers: {
                                          ...getAuthHeader(),
                                          'Content-Type': 'multipart/form-data',
                                        },
                                      }
                                    )

                                    await fetchUsers(buildUrl())
                                    return res.data

                                  } catch (err) {
                                    throw err
                                  } finally {
                                    updatingUsers.value = false
                                  }
                                }

                                const deleteUser = async (id) => {
                                  deletingUsers.value = true

                                  try {
                                    await axios.delete(`/api/delete-users-management/${id}`, {
                                      headers: getAuthHeader(),
                                    })

                                    await fetchUsers(buildUrl())

                                    Swal.fire({
                                      icon: 'success',
                                      title: 'Succeed',
                                      text: 'User successfully deleted',
                                      timer: 1500,
                                      showConfirmButton: false,
                                    })

                                  } catch (err) {
                                    if (err.response?.status === 403) {
                                      Swal.fire(
                                        'Access Denied',
                                        'You do not have permission to delete the user',
                                        'error'
                                      )
                                    } else {
                                      Swal.fire(
                                        'Error',
                                        err.response?.data?.message || 'Failed to delete user',
                                        'error'
                                      )
                                    }
                                    throw err
                                  } finally {
                                    deletingUsers.value = false
                                  }
                                }



                                


                                const fetchRoleSelect = async (force = false) => {
                                  if (roleSelect.value.length && !force) return

                                  loadingSelect.value = true
                                  try {
                                    const { data } = await axios.get('/api/role-select', {
                                      headers: {
                                        ...getAuthHeader(),
                                      },
                                    })
                                    roleSelect.value = data
                                  } finally {
                                    loadingSelect.value = false
                                  }
                                }




                                const fetchDivisionSelect = async (force = false) => {
                                  if (divisionSelect.value.length && !force) return divisionSelect.value
                                  loadingSelect.value = true
                                  try {
                                      const res = await axios.get('/api/division-select', { headers: getAuthHeader() })
                                      // Pastikan menyimpan array hasil response
                                      const data = Array.isArray(res.data) ? res.data : (res.data.data ?? [])
                                      divisionSelect.value = data
                                      // console.log("Store: Division Loaded", data.length, "items")
                                      return data
                                  } catch (e) {
                                      console.error('fetchDivisionSelect error', e)
                                      divisionSelect.value = []
                                      return []
                                  } finally {
                                      loadingSelect.value = false
                                  }
                              }

                              const fetchGroupSelect = async (force = false) => {
                                  if (groupSelect.value.length && !force) return groupSelect.value
                                  loadingSelect.value = true
                                  try {
                                      const res = await axios.get('/api/group-select', { headers: getAuthHeader() })
                                      const data = Array.isArray(res.data) ? res.data : (res.data.data ?? [])
                                      groupSelect.value = data
                                      // console.log("Store: Group Loaded", data.length, "items")
                                      return data
                                  } finally {
                                      loadingSelect.value = false
                                  }
                                }

                                const statusStatis = ref([
                                { value: 1, label: 'Aktif' },
                                { value: 0, label: 'Non-Aktif' }
                                ])






            return{
                baseUrlApi,
                usersData,
                loadingUsers,
                searchUsers,
                searchTimeoutUsers,
                pagination,
                sort,
                fetchUsers,
                searchWithDelay,
                changePageSize,
                toggleSort,
                changeSorting,
                resetFilters,
                buildUrl,
                formatDate,

                usersDetail,
                loading,
                detailUser,

                savingUsers,
                errorUsers,
                storeUser,

                updatingUsers,
                updateUser,

                deleteUser,
                deletingUsers,

                fetchRoleSelect,
                roleSelect,
                loadingSelect,

                fetchDivisionSelect,
                divisionSelect,
                fetchGroupSelect,
                groupSelect,
                statusStatis
                
            

            }


})