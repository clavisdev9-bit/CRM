import { ref, reactive } from "vue";
import { defineStore } from "pinia";
import axios from "axios";
import Swal from "sweetalert2";


export const useFollowUpsStore = defineStore("followUpStore", () => {

  const endpoints = {
    customers: "/api/follow-up-customers",
    leads: "/api/follow-up-leads",
    timeline: (id) => `/api/follow-up/${id}/timeline`,
      leadsSelect: "/api/follow-up/get-sales/leads",

  };

  /* ================= STATE ================= */
  const followUp = ref([]); // ← singular (dipakai di UI)
  const mode = ref("customers");
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

// state untuk delete dan detail follow up
 const deletingFollowUp = ref(false)
 const followUpDetail = ref(null)
 const loadingDetail = ref(false);

// code untuk select leads direct subject follow up
const leadsOptionsDirect = ref([]);
const loadingLeadsOptionsDirect = ref(false);

// state untuk direct follow up (tanpa visit)
const savingLeadDirectToFollowUp = ref(false)
const errorLeadDirectToFollowUp = ref(null)

// store untuk type follow up
const submittingResult = ref(false)
const errorSubmitResult = ref(null)

//state update follow up data
const updatingFollowUp = ref(false)
const errorFollowUp = ref(null)


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

     console.log('MODE:', mode.value)           // cek mode
  console.log('ENDPOINT:', endpoints[mode.value]) 
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

  const changeSorting = () => {
  pagination.current_page = 1
  fetchFollowUps()
}


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


                  //  code serch leads dengan delay 400ms
                  const searchLeadsOptions = (val) => {
                    clearTimeout(leadsSearchTimeout);

                    leadsSearchTimeout = setTimeout(() => {
                      fetchLeadsOptions(val);
                    }, 400);
                  };

                        const typeFollowUp = ref([
                            { value: 'CALL', label: 'CALL' },
                            { value: 'EMAIL', label: 'EMAIL' },
                            { value: 'WHATSAPP', label: 'WHATSAPP'},
                            { value: 'MEETING', label: 'MEETING'},
                            { value: 'VISIT', label: 'VISIT LOCATION' },
                            ])

                            const typeSubjectDirect = ref([
                            { value: 'Perkenalan Produk', label: 'Perkenalan Produk' },
                            { value: 'Penawaran Harga', label: 'Penawaran Harga' },
                            { value: 'Negosiasi', label: 'Negosiasi' },
                            { value: 'Follow Up', label: 'Follow Up' },
                            { value: 'Kirim Proposal', label: 'Kirim Proposal' },
                            { value: 'Presentasi Produk', label: 'Presentasi Produk' },
                            { value: 'Demo Produk', label: 'Demo Produk' },
                            { value: 'Klarifikasi Kebutuhan', label: 'Klarifikasi Kebutuhan' },
                            { value: 'Pembahasan Kontrak', label: 'Pembahasan Kontrak' },
                            { value: 'After Sales', label: 'After Sales' },
                            { value: 'Menunggu Keputusan', label: 'Menunggu Keputusan' },
                            { value: 'Lainnya', label: 'Lainnya' },
                          ])


                          const resultSubmit = ref([
                            { value: 'success', label: 'Follow Up Ditutup (Tidak Ada Respons dari Customer)' },
                            { value: 'need_followup', label: 'Perlu Follow Up Lagi' },
                            { value: 'reschedule', label: 'Jadwal Ulang'},
                            { value: 'no_meet', label: 'Tidak Berhasil Follow UP / Tidak Bertemu Customer(PIC)' },
                            { value: 'dealing', label: 'Sedang Proses Deal / Negotiation Stage ' },
                            { value: 'closed', label: 'Selesai / Closed' },
                            { value: 'cancelled', label: 'Dibatalkan' },
                            ])


                          //  format tanggal untuk tabel
                          const formatDate = (value) => {
                            if (!value) return "-"

                            // ubah "2026-02-15 13:55:41" → "2026-02-15T13:55:41"
                            const isoString = value.replace(" ", "T")

                            const date = new Date(isoString)

                            return date.toLocaleDateString("id-ID", {
                              day: "2-digit",
                              month: "short",
                              year: "numeric",
                              timeZone: "Asia/Jakarta", // penting!
                            })
                          }


                           
                          // format tanggal untuk timeline
                          const formatDates = (value) => {
                              if (!value) return "-"
                              const date = new Date(value)
                              return date.toLocaleDateString("id-ID", {
                                day: "2-digit",
                                month: "short",
                                year: "numeric",
                              })
                            }

                            //kode untuk delete follow up
                            const deleteFollowUp = async (id) => {
                                  deletingFollowUp.value = true

                                  try {
                                    await axios.delete(`/api/follow-up/delete/${id}`, {
                                      headers: getAuthHeader(),
                                    })

                                    await fetchFollowUps()

                                    Swal.fire({
                                      icon: 'success',
                                      title: 'Succeed',
                                      text: 'Follow Up successfully deleted',
                                      timer: 1500,
                                      showConfirmButton: false,
                                    })

                                  } catch (err) {
                                    if (err.response?.status === 403) {
                                      Swal.fire(
                                        'Access Denied',
                                        'You do not have permission to delete the follow up',
                                        'error'
                                      )
                                    } else {
                                      Swal.fire(
                                        'Error',
                                        err.response?.data?.message || 'Failed to delete follow up',
                                        'error'
                                      )
                                    }
                                    throw err
                                  } finally {
                                    deletingFollowUp.value = false
                                  }
                                }

                              //  kode untuk detail follow up
                              const detailFollowUpData = async (followUpId) => {
                                loadingDetail.value = true
                                try {
                                    const res = await axios.get(`/api/follow-up/show/${followUpId}`, {
                                    headers: getAuthHeader(),
                                    })
                                    followUpDetail.value = res.data.data
                                } catch (err) {
                                     Swal.fire({
                                      icon: "error",
                                      title: "Failed!",
                                      text: err.response?.data?.message || "Failed to fetch follow up details.",
                                      confirmButtonText: "OK",
                                    })
                                } finally {
                                    loadingDetail.value = false
                                }
                                }



                                // code untuk fecth leads
                               const fetchLeadsSelectDirectSubject = async () => {
                                  loadingLeadsOptionsDirect.value = true
                                  try {
                                    const res = await axios.get('/api/follow-up/get-sales/leads/direct', {
                                      headers: { ...getAuthHeader() },
                                    })

                                    console.log('DIRECT LEADS RESPONSE:', res)

                                    //  cek dulu sebelum map
                                    if (!res.data || !res.data.data) {
                                      console.error('FORMAT RESPONSE TIDAK SESUAI', res.data)
                                      leadsOptionsDirect.value = []
                                      return
                                    }

                                  leadsOptionsDirect.value = res.data.data.map(item => ({
                                  lead_id: Number(item.id), // ⬅️ paksa number
                                  company_name: item.company_name,
                                  contact_name: item.contact_name,
                                }))


                                  } catch (err) {
                                    console.error('FETCH DIRECT LEADS ERROR', err)
                                    leadsOptionsDirect.value = []
                                  } finally {
                                    loadingLeadsOptionsDirect.value = false
                                  }
                             }


                          // store untuk direct langsung follow up (tanpa visit)
                           const storeLeadDirectForFollowUp = async (leadId, payload) => {
                              savingLeadDirectToFollowUp.value = true
                              errorLeadDirectToFollowUp.value = null

                              try {
                                const res = await axios.post(
                                  `/api/follow-ups/${leadId}/direct-follow-up`,
                                  payload,
                                  { headers: getAuthHeader() }
                                )

                                // refresh data follow up list
                                await fetchFollowUps()

                                return res.data
                              } catch (err) {
                                if (err.response?.status === 422) {
                                  errorLeadDirectToFollowUp.value = err.response.data.errors
                                }
                                throw err
                              } finally {
                                savingLeadDirectToFollowUp.value = false
                              }
                            }


                            const submitFollowUpResult = async (followUpId, payload) => {
                              submittingResult.value = true
                              errorSubmitResult.value = null

                              try {
                                const res = await axios.post(
                                  `/api/follow-ups/${followUpId}/submit-result`,
                                  payload,
                                  { headers: getAuthHeader() }
                                )

                                // refresh list setelah submit
                                await fetchFollowUps()

                                return res.data
                              } catch (err) {
                                if (err.response?.status === 422) {
                                  errorSubmitResult.value = err.response.data.errors
                                }
                                throw err
                              } finally {
                                submittingResult.value = false
                              }
                            }


                              const updateFollowUp = async (id, form) => {
                                updatingFollowUp.value = true
                                errorFollowUp.value = null

                                try {
                                  const payload = {
                                    follow_up_at: form.follow_up_at,
                                    notes: form.notes ?? null,
                                    subject: form.subject ?? null,
                                  }

                                  const res = await axios.put(
                                    `/api/follow-up/update/${id}`,
                                    payload,
                                    { headers: getAuthHeader() }  
                                  )

                                  // optional kalau mau auto refresh table juga
                                  await fetchFollowUps()

                                  return res.data
                                } catch (err) {
                                  errorFollowUp.value =
                                    err.response?.data?.message || 'Failed to update follow up'

                                  throw err
                                } finally {
                                  updatingFollowUp.value = false
                                }
                      }


                      // ===============================
                      // SUBMIT RESULT FOLLOW UP CUSTOMER
                      // ===============================
                      const submitFollowUpResultCustomer = async (followUpId, payload) => {
                        submittingResult.value = true
                        errorSubmitResult.value = null

                        try {
                          const res = await axios.post(
                            `/api/follow-ups/${followUpId}/submit-result/customer`,
                            payload,
                            { headers: getAuthHeader() }
                          )

                          // refresh data setelah submit supaya timeline & status langsung update
                          await fetchFollowUps()

                          return res.data
                        } catch (err) {
                          console.error('Submit Result Customer Error:', err)
                          errorSubmitResult.value =
                            err.response?.data?.message || 'Gagal submit result customer'
                          throw err
                        } finally {
                          submittingResult.value = false
                        }
                      }



  return {
    followUp,
    loading,
    loadingDetail,
    search,
    pagination,
    sort,
    mode,

    fetchFollowUps,
    searchWithDelay,
    changePageSize,
    changeSorting,
    nextPage,
    prevPage,

    fetchTimeline,
    timeline,
    loadingTimeline,
    selectedFollowUpCode,

    fetchTimeline,
    clearTimeline,
    typeFollowUp,
    formatDates,


    //  untuk select 
    leadsOptions,
    loadingLeadsOptions,
    fetchLeadsOptions,
    searchLeadsOptions,
    resultSubmit,

    formatDate,

    deletingFollowUp,
    deleteFollowUp,

    followUpDetail,
    detailFollowUpData,
    typeSubjectDirect,

    leadsOptionsDirect,
    fetchLeadsSelectDirectSubject,
    loadingLeadsOptionsDirect,

    savingLeadDirectToFollowUp,
    errorLeadDirectToFollowUp,
    storeLeadDirectForFollowUp,

     submitFollowUpResult,
     submittingResult,
     errorSubmitResult,

    updatingFollowUp,
    errorFollowUp,
    updateFollowUp,

    submitFollowUpResultCustomer

  };
});
