import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from "sweetalert2";


export const useFollowUpsStore = defineStore("followUpStore", () => {

  const endpoints = {
    leads: "/api/follow-up-leads",
    timeline: (id) => `/api/follow-up/${id}/timeline`,
      leadsSelect: "/api/follow-up/get-sales/leads",

  };

  /* ================= STATE ================= */
  const followUp = ref([]); // ← singular (dipakai di UI)
  const mode = ref("leads");
  const loading = ref(false);

  const search = ref("");
  let searchTimeout = null;

  /* ================= TIMELINE ================= */
const timeline = ref([]);
const loadingTimeline = ref(false);
const selectedFollowUpCode = ref(null);

/* ================= LEADS SELECT ================= */
const leadsOptions = ref([]);
const loadingLeadsOptions = ref(false);
let leadsSearchTimeout = null;




  const pagination = reactive({
    current_page: 1,
    per_page: 10,
    last_page: 1,
    total: 0,
  });

  const sort = reactive({
    column: "created_at",
    direction: "desc",
  });

  const getAuthHeader = () => ({
    Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
  });

  const buildUrl = () => {
    const params = new URLSearchParams();

    params.append("page", pagination.current_page);
    params.append("per_page", pagination.per_page);
    params.append("sort_by", sort.column);
    params.append("sort_dir", sort.direction);

    if (search.value) params.append("search", search.value);

    return `${endpoints[mode.value]}?${params.toString()}`;
  };

  /* ================= FETCH ================= */
  const fetchFollowUps = async (newMode = null, page = null) => {
    loading.value = true;

    if (newMode) {
      mode.value = newMode;
      pagination.current_page = 1;
    }

    if (page !== null) pagination.current_page = page;

    try {
      const res = await axios.get(buildUrl(), {
        headers: getAuthHeader(),
      });

      const result = res.data?.data;

      followUp.value = result?.data ?? [];

      if (result?.pagination) {
        Object.assign(pagination, result.pagination);
      }

    } catch (err) {
      console.error("Fetch Follow Ups Failed:", err);
      followUp.value = [];
    } finally {
      loading.value = false;
    }
  };

  /* ================= SEARCH ================= */
  const searchWithDelay = (val) => {
    clearTimeout(searchTimeout);
    search.value = val;
    pagination.current_page = 1;

    searchTimeout = setTimeout(fetchFollowUps, 500);
  };

  const changePageSize = () => {
    pagination.current_page = 1;
    fetchFollowUps();
  };

  const nextPage = () => {
    if (pagination.current_page < pagination.last_page) {
      fetchFollowUps(null, pagination.current_page + 1);
    }
  };

  const prevPage = () => {
    if (pagination.current_page > 1) {
      fetchFollowUps(null, pagination.current_page - 1);
    }
  };



    /* ================= FETCH TIMELINE ================= */
    const fetchTimeline = async (id) => {
      loadingTimeline.value = true;
      timeline.value = [];

      try {
        const res = await axios.get(`/api/follow-ups/${id}/timeline`, {
          headers: getAuthHeader(),
        });

        selectedFollowUpCode.value = res.data.data.follow_up_code;
        timeline.value = res.data.data.histories ?? [];

      } catch (err) {
        console.error("Fetch Timeline Failed:", err);
        timeline.value = [];
      } finally {
        loadingTimeline.value = false;
      }
    };


      const clearTimeline = () => {
      timeline.value = [];
      selectedFollowUpCode.value = null;
    };

/* ================= FETCH LEADS FOR SELECT ================= */
const fetchLeadsOptions = async (keyword = "") => {
  loadingLeadsOptions.value = true;

  try {
    const params = new URLSearchParams();
    if (keyword) params.append("search", keyword);

    const res = await axios.get(`${endpoints.leadsSelect}?${params.toString()}`, {
      headers: getAuthHeader(),
    });

    leadsOptions.value = res.data.data ?? [];

  } catch (err) {
    console.error("Fetch Leads Options Failed:", err);
    leadsOptions.value = [];
  } finally {
    loadingLeadsOptions.value = false;
  }
};



const searchLeadsOptions = (val) => {
  clearTimeout(leadsSearchTimeout);

  leadsSearchTimeout = setTimeout(() => {
    fetchLeadsOptions(val);
  }, 400);
};


  return {
    followUp,
    loading,
    search,
    pagination,
    sort,

    fetchFollowUps,
    searchWithDelay,
    changePageSize,
    nextPage,
    prevPage,

    fetchTimeline,

    timeline,
        loadingTimeline,
        // selectedFollowUp,
        selectedFollowUpCode,

        fetchTimeline,
        clearTimeline,


      //  untuk select 
        leadsOptions,
        loadingLeadsOptions,
        fetchLeadsOptions,
        searchLeadsOptions,


  };
});
