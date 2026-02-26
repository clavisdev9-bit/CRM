import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from "sweetalert2";

export const useDataLeadsVisitStore = defineStore("Data-Leads-Visit", () => {
    // ==========================================
    // STATE
    // ==========================================
    const baseUrlApi = "/api/data-leads-visit";

    

    const leadVisitData = ref([]);
    const leadVisitDetail = ref(null);

    const loadingLeadVisit = ref(false);
    const savingLeadVisit = ref(false);
    const updatingLeadVisit = ref(false);
    const deletingLeadVisit = ref(false);
    const errorLeadVisit = ref(null);

    const searchLeadVisit = ref("");
    let searchTimeoutLeadVisit = null;

    const loading = ref(false);
    const errors = ref({});
    


    // untuk state visit start 
    // VISIT STATE
        const activeVisitId = ref(null)
        const activeLeadId = ref(null)
        const activeVisitStatus = ref(null)

    // CHECK IN STATE
    const checkingInVisit = ref(false)

    // CHECK OUT STATE
    const checkingOutVisit = ref(false)



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

    // ==========================================
    // HELPERS
    // ==========================================
    const getAuthHeader = () => {
        const token = localStorage.getItem("auth_token");
        return { Authorization: `Bearer ${token}` };
    };

    const buildUrl = () => {
        const params = new URLSearchParams();

        if (searchLeadVisit.value) params.append("search", searchLeadVisit.value);
        if (pagination.current_page) params.append("page", pagination.current_page);
        if (pagination.per_page) params.append("per_page", pagination.per_page);
        if (sort.column) {
            params.append("sort_by", sort.column);
            params.append("sort_dir", sort.direction);
        }

        return `${baseUrlApi}?${params.toString()}`;
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return "-";
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return "Belum pernah update";

        return date.toLocaleDateString("id-ID", {
            year: "numeric",
            month: "long",
            day: "2-digit",
        });
    };

    // ==========================================
    // ACTIONS
    // ==========================================
    const fetchLeadsVisitStore = async (url = buildUrl()) => {
        loadingLeadVisit.value = true;
        try {
            const response = await axios.get(url, {
                headers: getAuthHeader(),
            });

            const result = response.data;
            const dataArray = Array.isArray(result.data)
                ? result.data
                : result.data?.data ?? [];

            // Replace existing array
            leadVisitData.value.splice(0, leadVisitData.value.length, ...dataArray);

            // Set pagination
            const pag = result.pagination ?? result.data?.pagination;
            if (pag) {
                pagination.current_page = pag.current_page;
                pagination.per_page = pag.per_page;
                pagination.prev_page_url = pag.prev_page_url;
                pagination.next_page_url = pag.next_page_url;
                pagination.last_page = pag.last_page;
                pagination.total = pag.total;
            }
        } catch (error) {
            console.error("Gagal fetch:", error);
        } finally {
            loadingLeadVisit.value = false;
        }
    };

    const searchWithDelay = (val) => {
        clearTimeout(searchTimeoutLeadVisit);
        searchLeadVisit.value = val;
        pagination.current_page = 1; // reset page
        searchTimeoutLeadVisit = setTimeout(() => {
            fetchLeadsVisitStore(buildUrl());
        }, 500);
    };

    const changePageSize = (size) => {
        pagination.per_page = size;
        pagination.current_page = 1;
        fetchLeadsVisitStore(buildUrl());
    };

    const changeSorting = () => {
        pagination.current_page = 1;
        fetchLeadsVisitStore(buildUrl());
    };

    const toggleSort = (col) => {
        if (!allowedSortColumns.includes(col)) return;

        if (sort.column === col) {
            sort.direction = sort.direction === "asc" ? "desc" : "asc";
        } else {
            sort.column = col;
            sort.direction = "asc";
        }

        changeSorting();
    };

    const resetFilters = () => {
        searchLeadVisit.value = "";
        pagination.per_page = 10;
        pagination.current_page = 1;
        sort.column = "created_at";
        sort.direction = "desc";
        fetchLeadsVisitStore(buildUrl());
    };

    const goToPage = (url) => {
        if (!url) return;
        fetchLeadsVisitStore(url);
    };



    const startVisit = async (leadId) => {
        try {
            savingLeadVisit.value = true

            const response = await axios.post(
            `/api/leads/${leadId}/start`,
            {},
            { headers: getAuthHeader() }
            )

            const visit = response.data.data

            // simpan visit aktif
            activeVisitId.value = visit.id
            activeLeadId.value = visit.lead_id
            activeVisitStatus.value = visit.visit_status
           
            Swal.fire({
            icon: "success",
            title: "Visit Start",
            text: "Please go to the location",
            timer: 1500,
            showConfirmButton: false,
            })
             await fetchLeadsVisitStore(buildUrl())
        } catch (err) {
            Swal.fire("Gagal", err.response?.data?.message ?? "Error", "error")
        } finally {
            savingLeadVisit.value = false
        }
    }



    // const checkInVisit = async ({ visitId, latitude, longitude, gps_snapshot, photoBlob }) => {
    //     try {
    //         checkingInVisit.value = true
    //         errors.value = {}

    //         const formData = new FormData()
    //         formData.append('latitude', latitude)
    //         formData.append('longitude', longitude)
    //         formData.append('gps_snapshot', gps_snapshot)
    //         formData.append('photo', photoBlob)

    //         await axios.post(
    //         `/api/visits/${visitId}/check-in`,
    //         formData,
    //         {
    //             headers: {
    //             ...getAuthHeader(),
    //             'Content-Type': 'multipart/form-data'
    //             }
    //         }
    //         )
    //         await fetchLeadsVisitStore(buildUrl())
    //         return true
    //     } catch (err) {
    //         errors.value = err.response?.data?.errors ?? {}
    //         throw err
    //     } finally {
    //         checkingInVisit.value = false
    //     }
    // }
    const checkInVisit = async ({ visitId, latitude, longitude, gps_snapshot, photoBlob }) => {
        try {
            checkingInVisit.value = true
            errors.value = {}

            const formData = new FormData()
            formData.append('latitude', latitude)
            formData.append('longitude', longitude)
            formData.append('gps_snapshot', gps_snapshot)
            formData.append('photo', photoBlob)

            await axios.post(`/api/visits/${visitId}/check-in`, formData, {
            headers: {
                ...getAuthHeader(),
                'Content-Type': 'multipart/form-data'
            }
            })

            await fetchLeadsVisitStore(buildUrl())
            return true

        } catch (err) {
            if (err.response?.status === 422) {
            errors.value = err.response.data.errors   // ✅ penting
            }
            throw err
        } finally {
            checkingInVisit.value = false
        }
        }




    const checkOutVisit = async ({ visitId, notes, customer_response }) => {
        try {
            checkingOutVisit.value = true
            errors.value = {}

            const formData = new FormData()
            formData.append('notes', notes)
            formData.append('customer_response', customer_response)

            await axios.post(
            `/api/visits/${visitId}/check-out`,
            formData,
            {
                headers: {
                ...getAuthHeader(),
                'Content-Type': 'multipart/form-data'
                }
            }
            )
            await fetchLeadsVisitStore(buildUrl())
            return true
        } catch (err) {
            errors.value = err.response?.data?.errors ?? {}
            throw err
        } finally {
            checkingOutVisit.value = false
        }
    }






    return {
        // State
        leadVisitData,
        leadVisitDetail,
        loadingLeadVisit,
        savingLeadVisit,
        updatingLeadVisit,
        deletingLeadVisit,
        errorLeadVisit,
        errors,
        searchLeadVisit,
        pagination,
        sort,
        allowedSortColumns,

        // Actions
        fetchLeadsVisitStore,
        buildUrl,
        searchWithDelay,
        changePageSize,
        changeSorting,
        toggleSort,
        resetFilters,
        formatDate,
        goToPage,

        // state start visit
        activeVisitId,
        activeLeadId,
        activeVisitStatus,
        startVisit,

        // check in
        checkInVisit,
        checkingInVisit,

        //check out
        checkOutVisit,
        checkingOutVisit
    };
});