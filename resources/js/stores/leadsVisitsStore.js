import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from "sweetalert2";

export const useDataLeadsVisitStore = defineStore("Data-Leads-Visit", () => {
    // ==============================
    // State
    // ==============================
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

    // ==============================
    // Actions
    // ==============================
    const fetchLeadsVisitStore = async (url = baseUrlApi) => {
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

    // ==============================
    // Return
    // ==============================
    // return {
    //     leadVisitData,
    //     leadVisitDetail,
    //     loadingLeadVisit,
    //     savingLeadVisit,
    //     updatingLeadVisit,
    //     deletingLeadVisit,
    //     errorLeadVisit,
    //     searchLeadVisit,
    //     pagination,
    //     sort,
    //     allowedSortColumns,
    //     fetchLeadsVisitStore,
    //     buildUrl,
    //     searchWithDelay,
    //     changePageSize,
    //     toggleSort,
    //     resetFilters,
    //     formatDate,
    //     changePageSize,
       
    // };
    return {
    leadVisitData,
    leadVisitDetail,
    loadingLeadVisit,
    savingLeadVisit,
    updatingLeadVisit,
    deletingLeadVisit,
    errorLeadVisit,

    searchLeadVisit,
    pagination,
    sort,
    allowedSortColumns,

    fetchLeadsVisitStore,
    buildUrl,
    searchWithDelay,

    changePageSize,
    changeSorting, // ✅ INI YANG HILANG
    toggleSort,
    resetFilters,
    formatDate,
};

});
