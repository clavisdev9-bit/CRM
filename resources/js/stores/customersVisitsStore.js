import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from "sweetalert2";

export const useDataCustomerVisitStore = defineStore("Data-Customers-Visit", () => {

 // ==============================
    // State
    // ==============================
    const baseUrlApi = "/api/data-customers-visit";

    const customersVisitData = ref([]);
    const customersVisitDetail = ref(null);

    const loadingCustomersVisit = ref(false);
    const savingCustomersVisit = ref(false);
    const updatingCustomersVisit = ref(false);
    const deletingCustomersVisit = ref(false);
    const errorCustomersVisit = ref(null);

    const searchCustomersVisit = ref("");
    let searchTimeoutCustomersVisit = null;

    const loading = ref(false);
    const errors = ref({});
    



     // untuk state visit start 
    // VISIT STATE
        const activeVisitId = ref(null)
        const activeCustomerId = ref(null)
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

    // ==============================
    // Helpers
    // ==============================
    const getAuthHeader = () => {
        const token = localStorage.getItem("auth_token");
        return { Authorization: `Bearer ${token}` };
    };


     const buildUrl = () => {
        const params = new URLSearchParams();

        if (searchCustomersVisit.value) params.append("search", searchCustomersVisit.value);
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


    const fetchCustomersVisitStore = async (url = buildUrl()) => {

        loadingCustomersVisit.value = true;
        try {
            const response = await axios.get(url, {
                headers: getAuthHeader(),
            });

            const result = response.data;
            const dataArray = Array.isArray(result.data)
                ? result.data
                : result.data?.data ?? [];

            // Replace existing array
            customersVisitData.value.splice(0, customersVisitData.value.length, ...dataArray);

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
            loadingCustomersVisit.value = false;
        }
    };


      const searchWithDelay = (val) => {
        clearTimeout(searchTimeoutCustomersVisit);
        searchCustomersVisit.value = val;
        pagination.current_page = 1; // reset page
        searchTimeoutCustomersVisit = setTimeout(() => {
            fetchCustomersVisitStore(buildUrl());
        }, 500);
    };

    const changePageSize = (size) => {
        pagination.per_page = size;
        pagination.current_page = 1;
        fetchCustomersVisitStore(buildUrl());
    };

    const changeSorting = () => {
        pagination.current_page = 1;
        fetchCustomersVisitStore(buildUrl());
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
        searchCustomersVisit.value = "";
        pagination.per_page = 10;
        pagination.current_page = 1;
        sort.column = "created_at";
        sort.direction = "desc";
        fetchCustomersVisitStore(buildUrl());
    };

    const goToPage = (url) => {
        if (!url) return;
        fetchCustomersVisitStore(url);
    };



      const startVisit = async (customerId) => {
        try {
            savingCustomersVisit.value = true

            const response = await axios.post(
            `/api/customers/${customerId}/start`,
            {},
            { headers: getAuthHeader() }
            )

            const visit = response.data.data

            // simpan visit aktif
            activeVisitId.value = visit.id
            activeCustomerId.value = visit.customer_id
            activeVisitStatus.value = visit.visit_status
           
            Swal.fire({
            icon: "success",
            title: "Visit Start",
            text: "Please go to the location",
            timer: 1500,
            showConfirmButton: false,
            })
             await fetchCustomersVisitStore(buildUrl())
        } catch (err) {
            Swal.fire("Gagal", err.response?.data?.message ?? "Error", "error")
        } finally {
            savingCustomersVisit.value = false
        }
    }


     const checkInVisit = async ({ visitId, latitude, longitude, gps_snapshot, photoBlob }) => {
        try {
            checkingInVisit.value = true
            errors.value = {}

            const formData = new FormData()
            formData.append('latitude', latitude)
            formData.append('longitude', longitude)
            formData.append('gps_snapshot', gps_snapshot)
            formData.append('photo', photoBlob)

            // await axios.post(`/api/visits/${visitId}/check-in`, formData, {
            await axios.post(`/api/visits/${visitId}/check-in`, formData, {
            headers: {
                ...getAuthHeader(),
                'Content-Type': 'multipart/form-data'
            }
            })

            await fetchCustomersVisitStore(buildUrl())
            return true

        } catch (err) {
            if (err.response?.status === 422) {
            errors.value = err.response.data.errors   
            }
            throw err
        } finally {
            checkingInVisit.value = false
        }
        }


        const checkOutVisit = async (payload) => {
    try {
        checkingOutVisit.value = true
        errors.value = {}

        const formData = new FormData()

        // formData.append('notes', payload.notes)
        // formData.append('customer_response', payload.customer_response)
        // formData.append('has_complaint', payload.has_complaint ? 1 : 0)
        // // formData.append('complaint_detail', payload.complaint_detail ?? '')
        // if (payload.has_complaint && payload.complaint_detail) {
        //         formData.append('complaint_detail', payload.complaint_detail)
        //     }
        // formData.append('has_potential_order', payload.has_potential_order ? 1 : 0)
        // // formData.append('potential_order_detail', payload.potential_order_detail ?? '')
        // if (payload.has_potential_order && payload.potential_order_detail) {
        //         formData.append('potential_order_detail', payload.potential_order_detail)
        //     }
        // formData.append('follow_up_at', payload.follow_up_at)
        // formData.append('follow_up_type', payload.follow_up_type ?? 'CALL')
        // formData.append('follow_up_notes', payload.follow_up_notes ?? '')

        formData.append('notes', payload.notes)
            formData.append('customer_response', payload.customer_response)
            formData.append('has_complaint', payload.has_complaint ? 1 : 0)
            // Hanya append kalau has_complaint true DAN ada isinya
            if (payload.has_complaint && payload.complaint_detail) {
                formData.append('complaint_detail', payload.complaint_detail)
            }
            formData.append('has_potential_order', payload.has_potential_order ? 1 : 0)
            if (payload.has_potential_order && payload.potential_order_detail) {
                formData.append('potential_order_detail', payload.potential_order_detail)
            }
            formData.append('follow_up_at', payload.follow_up_at)
            formData.append('follow_up_type', payload.follow_up_type ?? 'CALL')
            formData.append('follow_up_notes', payload.follow_up_notes ?? '')

        await axios.post(
            `/api/visits/customers/${payload.visitId}/check-out`,
            formData,
            {
                headers: {
                    ...getAuthHeader(),
                    'Content-Type': 'multipart/form-data'
                }
            }
        )

        await fetchCustomersVisitStore(buildUrl())
        return true

    } catch (err) {
        errors.value = err.response?.data?.errors ?? {}
        throw err
    } finally {
        checkingOutVisit.value = false
    }
}


    return {
        baseUrlApi,
        customersVisitData,
        customersVisitDetail,
        loadingCustomersVisit,
        savingCustomersVisit,
        updatingCustomersVisit,
        deletingCustomersVisit,
        errorCustomersVisit,
        searchCustomersVisit,
        searchTimeoutCustomersVisit,
        loading,
        pagination,
        sort,
        allowedSortColumns,
        getAuthHeader,
        buildUrl,
        formatDate,
        fetchCustomersVisitStore,
        searchWithDelay,
        changePageSize,
        changeSorting,
        toggleSort,
        resetFilters,
        goToPage,

        activeVisitId,
        activeCustomerId,
        activeVisitStatus,
        startVisit,
        checkingInVisit,
        checkInVisit,

        checkingOutVisit,
        checkOutVisit
    }

})