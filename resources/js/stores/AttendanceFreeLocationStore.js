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

        const hasAttendanceToday = ref(false)
        const attendanceStatus = ref(null)
        const loadingTodayStatus = ref(false)

        // =============================================
        // STATE — MY REPORT (laporan bulanan)
        // =============================================
        const reportData     = ref(null)
        const attendanceDays = ref([])
        const loadingReport  = ref(false)
        const selectedMonth  = ref(new Date().getMonth() + 1)
        const selectedYear   = ref(new Date().getFullYear())

        const reportSummary = reactive({
            ONTIME:         0,
            LATE:           0,
            COMPLETED:      0,
            LIBUR:          0,
            TOTAL_HADIR:    0,
            TOTAL_CHECKOUT: 0,
        })

        // =============================================
        // STATE — MY HISTORY (riwayat paginated)
        // =============================================
        const historyData    = ref([])
        const loadingHistory = ref(false)
        const searchHistory  = ref("")
        let   searchTimeoutHistory = null

        const historyPagination = reactive({
            current_page:  1,
            per_page:      15,
            prev_page_url: null,
            next_page_url: null,
            last_page:     1,
            total:         0,
        })

        const historySort = reactive({
            column:    "attendance_date",
            direction: "desc",
        })

        // =============================================
        // STATE — MANAGEMENT (existing)
        // =============================================
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


        // =============================================
        // FETCH — MANAGEMENT (existing)
        // =============================================
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
                                searchAttendance.value = ''
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
                                { headers: { ...getAuthHeader() } }
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


        // =============================================
        // MY REPORT
        // =============================================
        const buildReportUrl = () => {
            const params = new URLSearchParams()
            params.append('month', selectedMonth.value)
            params.append('year',  selectedYear.value)
            return `/api/attendance/my-report?${params.toString()}`
        }

        const fetchMyReport = async () => {
            loadingReport.value = true
            try {
                const res = await axios.get(buildReportUrl(), {
                    headers: getAuthHeader(),
                })
                const data = res.data?.data ?? null
                if (!data) return

                reportData.value     = data
                attendanceDays.value = data.attendance_days ?? []

                const s = data.summary ?? {}
                reportSummary.ONTIME         = s.ONTIME         ?? 0
                reportSummary.LATE           = s.LATE           ?? 0
                reportSummary.COMPLETED      = s.COMPLETED      ?? 0
                reportSummary.LIBUR          = s.LIBUR          ?? 0
                reportSummary.TOTAL_HADIR    = s.TOTAL_HADIR    ?? 0
                reportSummary.TOTAL_CHECKOUT = s.TOTAL_CHECKOUT ?? 0

            } catch (err) {
                if (err.response?.status === 401) {
                    Swal.fire({ icon: 'warning', title: 'Sesi Habis', text: 'Silakan login kembali.' })
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: err.response?.data?.message || 'Gagal memuat laporan.' })
                }
            } finally {
                loadingReport.value = false
            }
        }

        const changeMonth = (val) => {
            selectedMonth.value = val
            fetchMyReport()
        }

        const changeYear = (val) => {
            selectedYear.value = val
            fetchMyReport()
        }

        const getCellLabel = (day) => {
            if (day.is_weekend) return 'L'
            if (!day.check_in)  return ''
            if (day.status === 'LATE') return 'T'
            return 'H'
        }

        const getCellClass = (day) => {
            if (day.is_weekend) return 'table-secondary'
            if (!day.check_in)  return ''
            if (day.status === 'LATE') return 'table-warning'
            return 'table-success'
        }

        const getCellTooltip = (day) => {
            if (day.is_weekend) return 'Libur / Weekend'
            if (!day.check_in)  return 'Tidak ada absensi'
            const out = day.check_out?.time ?? 'Belum checkout'
            return `IN: ${day.check_in.time} | OUT: ${out}`
        }

        const getInitials = (name) => {
            if (!name) return '?'
            return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
        }


        // =============================================
        // MY HISTORY
        // =============================================
        const buildHistoryUrl = () => {
            const params = new URLSearchParams()
            if (searchHistory.value)              params.append('search',   searchHistory.value)
            if (historyPagination.current_page)   params.append('page',     historyPagination.current_page)
            if (historyPagination.per_page)       params.append('per_page', historyPagination.per_page)
            if (historySort.column)               params.append('sort_by',  historySort.column)
            if (historySort.direction)            params.append('sort_dir', historySort.direction)
            return `/api/attendance/my-history?${params.toString()}`
        }

        const fetchMyHistory = async (url = null) => {
            loadingHistory.value = true
            try {
                const res = await axios.get(url ?? buildHistoryUrl(), {
                    headers: getAuthHeader(),
                })

                // sesuai response: data.data = array, data.pagination = object
                const result = res.data?.data ?? {}
                const dataArray = Array.isArray(result.data) ? result.data : []

                historyData.value.splice(0, historyData.value.length, ...dataArray)

                const pag = result.pagination
                if (pag) {
                    historyPagination.current_page  = pag.current_page
                    historyPagination.per_page      = pag.per_page
                    historyPagination.prev_page_url = pag.prev_page_url
                    historyPagination.next_page_url = pag.next_page_url
                    historyPagination.last_page     = pag.last_page
                    historyPagination.total         = pag.total
                }

            } catch (err) {
                if (err.response?.status === 401) {
                    Swal.fire({ icon: 'warning', title: 'Sesi Habis', text: 'Silakan login kembali.' })
                } else {
                    console.error("Gagal fetch history:", err)
                }
            } finally {
                loadingHistory.value = false
            }
        }

        const searchHistoryWithDelay = (val) => {
            clearTimeout(searchTimeoutHistory)
            searchHistory.value = val
            historyPagination.current_page = 1
            searchTimeoutHistory = setTimeout(() => {
                fetchMyHistory()
            }, 500)
        }

        const changeHistoryPageSize = () => {
            historyPagination.current_page = 1
            fetchMyHistory()
        }

        const toggleHistorySort = (col) => {
            if (historySort.column === col) {
                historySort.direction = historySort.direction === 'asc' ? 'desc' : 'asc'
            } else {
                historySort.column    = col
                historySort.direction = 'desc'
            }
            historyPagination.current_page = 1
            fetchMyHistory()
        }

        const resetHistoryFilters = () => {
            searchHistory.value            = ''
            historyPagination.per_page     = 15
            historyPagination.current_page = 1
            historySort.column             = 'attendance_date'
            historySort.direction          = 'desc'
            fetchMyHistory()
        }

        // badge status untuk tabel history
        const getStatusBadgeClass = (status) => {
            const map = {
                ONTIME:    'bg-success',
                LATE:      'bg-warning text-dark',
                COMPLETED: 'bg-info',
                IGNORED:   'bg-secondary',
            }
            return map[status] ?? 'bg-secondary'
        }

        // badge IN / OUT
        const getTypeBadgeClass = (type) => {
            return type === 'IN' ? 'bg-success' : 'bg-danger'
        }

        // foto URL dari photo_path
        const getPhotoUrl = (photoPath) => {
            if (!photoPath) return null
            return `${import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000'}/storage/attendance/photos/${photoPath}`
        }


        return {
                baseUrlApi,
                attendanceData,
                useDataAttendanceStore,
                loadingAttendance,
                searchAttendance,
                searchTimeoutAttendance,
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
                getAuthHeader,
                
                hasAttendanceToday,
                attendanceStatus,
                loadingTodayStatus,
                fetchAttendanceToday,
                storeAttendance,

                // MY REPORT
                reportData,
                attendanceDays,
                loadingReport,
                selectedMonth,
                selectedYear,
                reportSummary,
                fetchMyReport,
                buildReportUrl,
                changeMonth,
                changeYear,
                getCellLabel,
                getCellClass,
                getCellTooltip,
                getInitials,

                // MY HISTORY
                historyData,
                loadingHistory,
                searchHistory,
                historyPagination,
                historySort,
                fetchMyHistory,
                buildHistoryUrl,
                searchHistoryWithDelay,
                changeHistoryPageSize,
                toggleHistorySort,
                resetHistoryFilters,
                getStatusBadgeClass,
                getTypeBadgeClass,
                getPhotoUrl,
        }
    })
