<template>
    <backendLayouts>

      <div class="page-header d-print-none">
                <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                    <!-- Page pre-title -->
                    <div class="page-pretitle">
                        Dashboard IT Page
                    </div>
                    <h4 class="page-title">
                         Dashboard IT Page
                    </h4>
                    </div>
                </div>
                </div>
            </div>


            <div class="container-xxl flex-grow-1 container-p-y">
              <div class="row">
                <div class="col-lg-12 mb-4 order-0">
                  <div class="card">
                    <div class="d-flex align-items-end row">
                      <div class="col-sm-7">
                        <div class="card-body">
                          <h5 class="card-title text-primary">Wellcome {{ auth.user?.fullname || 'Loading...' }} 🎉</h5>
                          <p class="mb-4">
                            You have done <span class="fw-bold">72%</span> more sales today. Check your new badge in
                            your profile.
                          </p>

                          <a href="" class="btn btn-sm btn-primary">View Badges</a>
                        </div>
                      </div>
                      <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4">
                          <img
                            :src="bannerImages"
                            height="140"
                            alt="View Badge User"
                            data-app-dark-img="illustrations/man-with-laptop-dark.png"
                            data-app-light-img="illustrations/man-with-laptop-light.png"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                </div>

                 <div class="row mt-4">
                <!-- Bar 1 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card p-3">
                    <h4>Bar: Basic</h4>
                    <div style="height:250px;">
                        <Bar :data="barBasic" :options="barOptions" />
                    </div>
                    </div>
                </div>

                <!-- Bar 2 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card p-3">
                    <h4>Bar: Horizontal</h4>
                    <div style="height:250px;">
                        <Bar :data="barHorizontal" :options="horizontalOptions" />
                    </div>
                    </div>
                </div>

                <!-- Bar 3 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card p-3">
                    <h4>Bar: Grouped</h4>
                    <div style="height:250px;">
                        <Bar :data="barGrouped" :options="barOptions" />
                    </div>
                    </div>
                </div>

                <!-- Bar 4 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card p-3">
                    <h4>Bar: Stacked</h4>
                    <div style="height:250px;">
                        <Bar :data="barStacked" :options="stackedOptions" />
                    </div>
                    </div>
                </div>

                <!-- Bar 5 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card p-3">
                    <h4>Bar: Gradient Bar</h4>
                    <div style="height:250px;">
                        <Bar :data="barGradient" :options="barOptions" />
                    </div>
                    </div>
                </div>

                </div>
            </div>

            
          
            <!-- / Content -->
</backendLayouts>
</template>


<script setup>
import { onMounted } from "vue";
import backendLayouts from "../../../../layouts/backendLayouts.vue";
const bannerImages = '/images/man-with-laptop-light.png'
import { exportsLoginStore } from "@/stores/loginStore";
const auth = exportsLoginStore();

onMounted(() => {
  if (!auth.user) {
    auth.fetchProfile();
  }
});
import { Bar, Doughnut  } from "vue-chartjs";

// versi bar chart
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from "chart.js";

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

// ===================== 1. BASIC BAR ===========================
const barBasic = {
  labels: ["Jan", "Feb", "Mar", "Apr"],
  datasets: [
    {
      label: "Sales",
      backgroundColor: "#42A5F5",
      data: [40, 60, 75, 50]
    }
  ]
};

const barOptions = {
  responsive: true,
  maintainAspectRatio: false
};

// ===================== 2. HORIZONTAL BAR ===========================
const barHorizontal = {
  labels: ["Chrome", "Firefox", "Safari", "Edge"],
  datasets: [
    {
      label: "Users",
      backgroundColor: "#66BB6A",
      data: [70, 40, 50, 30]
    }
  ]
};

const horizontalOptions = {
  responsive: true,
  indexAxis: "y", // swap axis
  maintainAspectRatio: false
};

// ===================== 3. GROUPED BAR ===========================
const barGrouped = {
  labels: ["Q1", "Q2", "Q3", "Q4"],
  datasets: [
    {
      label: "2024",
      backgroundColor: "#42A5F5",
      data: [50, 60, 40, 80]
    },
    {
      label: "2025",
      backgroundColor: "#FFA726",
      data: [40, 70, 60, 90]
    }
  ]
};

// ===================== 4. STACKED BAR ===========================
const barStacked = {
  labels: ["Week 1", "Week 2", "Week 3", "Week 4"],
  datasets: [
    {
      label: "Frontend",
      backgroundColor: "#42A5F5",
      data: [10, 20, 15, 25]
    },
    {
      label: "Backend",
      backgroundColor: "#66BB6A",
      data: [20, 10, 25, 30]
    }
  ]
};

const stackedOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    x: { stacked: true },
    y: { stacked: true }
  }
};

// ===================== 5. GRADIENT BAR ===========================
const barGradient = {
  labels: ["Mon", "Tue", "Wed", "Thu", "Fri"],
  datasets: [
    {
      label: "Visitors",
      data: [45, 60, 50, 80, 65],
      backgroundColor: (ctx) => {
        const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, "#42A5F5");
        gradient.addColorStop(1, "#1E88E5");
        return gradient;
      }
    }
  ]
};
</script>