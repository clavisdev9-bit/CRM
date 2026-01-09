import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from 'sweetalert2'

export const useDataAttendanceStore = defineStore('Data-Menu', () => {
        const baseUrlApi = "/api/attendance-management"
        const attendanceData = ref([]);
        const loadingAttendance = ref(false)  
        const searchAttendance = ref("");
        let searchTimeoutAttendance = null;  

        const savingAttendance = ref(false)
        const errorAttendance = ref(null)

        const updatingAttendance = ref(false)
        const deletingAttendance = ref(false)

        const attendanceDetail = ref(null)
        const loading = ref(false) 
        const loadingDetail = ref(false) 

        // state untuk tombol in-out
        const hasAttendanceToday = ref(false)
        const attendanceStatus = ref(null) // COMPLETE, IN, OUT, INVALID
        const loadingTodayStatus = ref(false)

         

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

        const allowedSortColumns = ["attendance_date", "created_at"];

        const getAuthHeader = () => {
            const token = localStorage.getItem("auth_token");
            return { Authorization: `Bearer ${token}` };
        };


         const fetchAttendanceData = async (url = "/api/attendance-management") => {
                loadingAttendance.value = true;
                try {
                const response = await axios.get(url, {
                    headers: getAuthHeader(),
                });

                const result = response.data;
                const dataArray = Array.isArray(result.data)
                    ? result.data
                    : result.data?.data ?? [];

                attendanceData.value.splice(0, attendanceData.value.length, ...dataArray);

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
                loadingAttendance.value = false;
                }
            };


                const buildUrl = () => {
                    const params = new URLSearchParams()
                            //ini code searching
                            if (searchAttendance.value) {
                            params.append('search', searchAttendance.value)
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
                            clearTimeout(searchTimeoutAttendance)
                            searchAttendance.value = val

                            // Reset ke halaman 1 saat pencarian
                            pagination.current_page = 1

                            searchTimeoutAttendance = setTimeout(() => {
                                fetchAttendanceData(buildUrl())
                            }, 500)
                            }


                             const changePageSize = () => {
                            pagination.current_page = 1
                            fetchAttendanceData(buildUrl())
                            }

                             const changeSorting = () => {
                                    pagination.current_page = 1
                                    fetchAttendanceData(buildUrl())
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
                                searchAttendance.value = '' // ← ini yang ga ngefek ke v-model
                                pagination.per_page = 10
                                pagination.current_page = 1
                                sort.column = 'created_at'
                                sort.direction = 'desc'
                                fetchAttendanceData(buildUrl())
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




                    const fetchAttendanceDetail = async (id) => {
                        loadingDetail.value = true
                        attendanceDetail.value = null

                        try {
                        const res = await axios.get(`/api/attendance/show/${id}`, {
                            headers: getAuthHeader(),
                        })
                        attendanceDetail.value = res.data.data
                        } catch (err) {
                        Swal.fire({
                            icon: "error",
                            title: "Failed",
                            text: err.response?.data?.message || "Failed load attendance detail",
                        })
                        } finally {
                        loadingDetail.value = false
                        }
                    }



                    // Method untuk fetch status attendance hari ini
                        const fetchAttendanceToday = async () => {
                        loadingTodayStatus.value = true
                        try {
                            const res = await axios.get("/api/attendance/check-today", {
                            headers: getAuthHeader(),
                            })
                            const data = res.data.data
                            hasAttendanceToday.value = data.has_attendance_today
                            attendanceStatus.value = data.status
                        } catch (err) {
                            console.error("Gagal ambil status attendance hari ini:", err)
                        } finally {
                            loadingTodayStatus.value = false
                        }
                        }


                         const storeAttendance = async (payload) => {
                            savingAttendance.value = true

                            try {
                                const formData = new FormData()

                                for (const key in payload) {
                                const value = payload[key]

                                if (value === null || value === undefined) continue

                                // 🔹 Jika foto base64 → convert ke File
                                if (key === 'photo_path' && typeof value === 'string') {
                                    const blob = await (await fetch(value)).blob()
                                    formData.append('photo_path', blob, 'attendance.png')
                                } else {
                                    formData.append(key, value)
                                }
                                }

                                const res = await axios.post(
                                '/api/attendance/process-free-location',
                                formData,
                                {
                                    headers: {
                                    ...getAuthHeader()
                                    // ❌ jangan set Content-Type manual
                                    }
                                }
                                )

                                await fetchAttendanceData(buildUrl())

                                return res.data
                            } catch (err) {
                                console.error('Store attendance error:', err)
                                throw err
                            } finally {
                                savingAttendance.value = false
                            }
                            }








            return{
                baseUrlApi,
                attendanceData,
                useDataAttendanceStore,
                loadingAttendance,
                searchAttendance,
                searchTimeoutAttendance,
                savingAttendance,
                savingAttendance,
                errorAttendance,
                updatingAttendance,
                deletingAttendance,
                attendanceDetail,
                loading,
                pagination,
                sort,
                allowedSortColumns,
                fetchAttendanceData,
                buildUrl,
                fetchAttendanceDetail,
                loadingDetail,
                searchWithDelay,
                resetFilters,
                toggleSort,
                changePageSize,
                 changeSorting,
                formatDate,
                
                hasAttendanceToday,
                attendanceStatus,
                loadingTodayStatus,
                fetchAttendanceToday,

                storeAttendance




        }


    })