import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from 'sweetalert2'


export const useSettingAppStore = defineStore('Setting-App', () => {

        const baseUrlApi = "/api/setting-app-management"
        const settingAppData = ref([]);
        const loadingSettingApp = ref(false)  
        const searchSettingApp = ref("");
        let searchTimeoutSettingApp = null;  

        const savingSettingApp = ref(false)
        const errorSettingApp = ref(null)

        const updatingSettingApp = ref(false)
        const deletingSettingApp = ref(false)

        const settingAppDetail = ref(null)
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

        const allowedSortColumns = ["nik", "created_at"];

        const getAuthHeader = () => {
            const token = localStorage.getItem("auth_token");
            return { Authorization: `Bearer ${token}` };
        };


         const fetchSettingApp = async (url = "/api/setting-app-management") => {
                loadingSettingApp.value = true;
                try {
                const response = await axios.get(url, {
                    headers: getAuthHeader(),
                });

                const result = response.data;
                const dataArray = Array.isArray(result.data)
                    ? result.data
                    : result.data?.data ?? [];

                settingAppData.value.splice(0, settingAppData.value.length, ...dataArray);

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
                loadingSettingApp.value = false;
                }
            };


              const buildUrl = () => {
                    const params = new URLSearchParams()
                            //ini code searching
                            if (searchSettingApp.value) {
                            params.append('search', searchSettingApp.value)
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
                            clearTimeout(searchTimeoutSettingApp)
                            searchSettingApp.value = val
                            pagination.current_page = 1

                            searchTimeoutSettingApp = setTimeout(() => {
                                fetchSettingApp(buildUrl())
                            }, 500)
                            }



                             const changePageSize = () => {
                            pagination.current_page = 1
                            fetchSettingApp(buildUrl())
                            }

                             const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchSettingApp(buildUrl())
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
                                searchSettingApp.value = '' // ← ini yang ga ngefek ke v-model
                                pagination.per_page = 10
                                pagination.current_page = 1
                                sort.column = 'created_at'
                                sort.direction = 'desc'
                                fetchSettingApp(buildUrl())
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

                             const detailSettingApp = async (settingAppId) => {
                                loading.value = true
                                try {
                                    const res = await axios.get(`/api/setting-app-show/${settingAppId}`, {
                                    headers: getAuthHeader(),
                                    })
                                    settingAppDetail.value = res.data.data
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


                                const storeSettingApp = async (payload) => {
                                    savingSettingApp.value = true
                                    try {
                                      const formData = new FormData()

                                      Object.keys(payload).forEach((key) => {
                                        if (payload[key] !== null && payload[key] !== undefined) {
                                          formData.append(key, payload[key])
                                        }
                                      })
                                      const res = await axios.post(
                                        '/api/setting-app-store-management',
                                        formData,
                                        {
                                          headers: {
                                            ...getAuthHeader(),
                                            'Content-Type': 'multipart/form-data',
                                          },
                                        }
                                      )
                                        await fetchSettingApp(buildUrl())
                                        return res.data
                                      } catch (err) {
                                        throw err 
                                      } finally {
                                        savingSettingApp.value = false
                                      }
                                    }



                          


                            const updateSettingApp = async (id, payload) => {
                                    updatingSettingApp.value = true

                                    try {
                                        const formData = new FormData()

                                        Object.keys(payload).forEach((key) => {
                                        if (payload[key] !== null && payload[key] !== undefined) {
                                            formData.append(key, payload[key])
                                        }
                                        })

                                        // 🔥 WAJIB untuk Laravel PUT
                                        formData.append('_method', 'PUT')

                                        const res = await axios.post(
                                        `/api/update-setting-app-management/${id}`,
                                        formData,
                                        {
                                            headers: {
                                            ...getAuthHeader(),
                                            'Content-Type': 'multipart/form-data',
                                            },
                                        }
                                        )

                                        await fetchSettingApp(buildUrl())
                                        return res.data

                                    } catch (err) {
                                        throw err
                                    } finally {
                                        updatingSettingApp.value = false
                                    }
                            }



                             const deleteSettingApp = async (id) => {
                                deletingSettingApp.value = true

                                try {
                                    await axios.delete(
                                    `/api/delete-setting-app-management/${id}`,
                                    { headers: getAuthHeader() }
                                    )

                                    await fetchSettingApp(buildUrl())
                                    return true

                                } finally {
                                    deletingSettingApp.value = false
                                }
                                }





            return {
                baseUrlApi,
                settingAppData,
                loadingSettingApp,
                searchSettingApp,
                searchTimeoutSettingApp,
                savingSettingApp,
                errorSettingApp,
                updatingSettingApp,
                deletingSettingApp,
                settingAppDetail,
                loading,
                pagination,
                sort,
                allowedSortColumns,
                fetchSettingApp,
                buildUrl,
                searchWithDelay,
                changePageSize,
                changeSorting,
                toggleSort,
                resetFilters,
                formatDate,
                detailSettingApp,
                storeSettingApp,
                updateSettingApp,
                deleteSettingApp
            }

})