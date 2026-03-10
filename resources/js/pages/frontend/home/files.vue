<template>
  <FrontendLayouts>
    <div class="container-xxl flex-grow-1 container-p-y">

      <!-- WELCOME CARD -->
      <div class="row">
        <div class="col-lg-12 mb-4 order-0">
          <div class="card">
            <div class="d-flex align-items-end row">
              <div class="col-sm-7">
                <div class="card-body">
                  <h4 class="card-title text-primary mb-2">Welcome to Clavis CRM! 🎉</h4>
                  <p class="mb-4">
                    Manage customers, monitor sales activity, and increase your team's productivity with ease.
                    Start your CRM journey now!
                  </p>
                  <RouterLink to="/login" class="btn btn-primary btn-sm">
                    Login Now
                  </RouterLink>
                </div>
              </div>
              <div class="col-sm-5 text-center text-sm-left">
                <div class="card-body pb-0 px-0 px-md-4">
                  <img
                    :src="bannerImages"
                    height="160"
                    alt="CRM Dashboard Illustration"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- COUNTER STATS -->
      <div class="row mb-4">
        <div
          v-for="(stat, index) in stats"
          :key="index"
          class="col-sm-6 col-lg-3 mb-4"
        >
          <div class="card stat-card h-100" :style="{ animationDelay: index * 0.1 + 's' }">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="stat-icon-wrap" :style="{ background: stat.bg }">
                <i :class="stat.icon" :style="{ color: stat.color }"></i>
              </div>
              <div>
                <p class="mb-0 text-muted small">{{ stat.label }}</p>
                <h3 class="mb-0 fw-bold counter" :data-target="stat.value">0</h3>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FEATURE CARDS -->
      <div class="row">
        <div class="col-12 mb-3">
          <h5 class="fw-semibold text-muted">What You Can Do</h5>
        </div>
        <div
          v-for="(feature, index) in features"
          :key="index"
          class="col-sm-6 col-lg-4 mb-4"
        >
          <div
            class="card feature-card h-100"
            :style="{ animationDelay: index * 0.15 + 's' }"
          >
            <div class="card-body">
              <div class="feature-icon mb-3" :style="{ background: feature.bg }">
                <i :class="feature.icon" :style="{ color: feature.color }"></i>
              </div>
              <h6 class="fw-bold mb-1">{{ feature.title }}</h6>
              <p class="text-muted small mb-0">{{ feature.desc }}</p>
            </div>
            <div class="feature-bar" :style="{ background: feature.color }"></div>
          </div>
        </div>
      </div>

    </div>
  </FrontendLayouts>
</template>

<script setup>
import { onMounted, ref } from "vue";
import axios from "axios";
import FrontendLayouts from "../../../layouts/frontendLayouts.vue";

const bannerImages = '/images/man-with-laptop-light.png';



const features = ref([
  {
    title: 'Lead Management',
    desc: 'Track and manage all your potential leads in one place efficiently.',
    icon: 'bx bx-user-plus bx-md',
    color: '#696cff',
    bg: '#eeedff',
  },
  {
    title: 'Customer Management',
    desc: 'Keep all customer data organized and accessible at any time.',
    icon: 'bx bx-group bx-md',
    color: '#03c3ec',
    bg: '#e0f7fc',
  },
  {
    title: 'Visit Tracking',
    desc: 'Monitor your sales team field visits with real-time GPS tracking.',
    icon: 'bx bx-map-pin bx-md',
    color: '#71dd37',
    bg: '#eafbdf',
  },
  {
    title: 'Sales Analytics',
    desc: 'Visualize your sales performance with interactive charts and reports.',
    icon: 'bx bx-bar-chart-alt-2 bx-md',
    color: '#ff3e1d',
    bg: '#ffe8e5',
  },
  {
    title: 'Team Collaboration',
    desc: 'Assign tasks and collaborate seamlessly across your sales team.',
    icon: 'bx bx-conversation bx-md',
    color: '#ffab00',
    bg: '#fff8e1',
  },
  {
    title: 'Deal Pipeline',
    desc: 'Manage your deals through every stage of the sales pipeline.',
    icon: 'bx bx-trending-up bx-md',
    color: '#20c997',
    bg: '#e0faf3',
  },
]);



const stats = ref([
  { label: 'Total Leads',     value: 0, icon: 'bx bx-user-plus', color: '#696cff', bg: '#eeedff' },
  { label: 'Total Customers', value: 0, icon: 'bx bx-group',      color: '#03c3ec', bg: '#e0f7fc' },
  { label: 'Visits Today',    value: 0, icon: 'bx bx-map-pin',    color: '#71dd37', bg: '#eafbdf' },
  { label: 'Deals Closed',    value: 0, icon: 'bx bx-trophy',     color: '#ff3e1d', bg: '#ffe8e5' },
]);

// Animate counter
const animateCounters = () => {
  const counters = document.querySelectorAll('.counter')
  counters.forEach(counter => {
    const target = +counter.getAttribute('data-target')
    const duration = 1500
    const step = target / (duration / 16)
    let current = 0

    const update = () => {
      current += step
      if (current < target) {
        counter.textContent = Math.floor(current).toLocaleString()
        requestAnimationFrame(update)
      } else {
        counter.textContent = target.toLocaleString()
      }
    }
    update()
  })
}

onMounted(async () => {
  if (window.Helpers?.initNavbarDropdowns) {
    window.Helpers.initNavbarDropdowns()
  }

  try {
    const res = await axios.get('/api/dashboard/home-stats')
    const data = res.data.data ?? {}

    stats.value = [
      { label: 'Total Leads',     value: data.total_leads     ?? 0, icon: 'bx bx-user-plus', color: '#696cff', bg: '#eeedff' },
      { label: 'Total Customers', value: data.total_customers ?? 0, icon: 'bx bx-group',      color: '#03c3ec', bg: '#e0f7fc' },
      { label: 'Visits Today',    value: data.visits_today    ?? 0, icon: 'bx bx-map-pin',    color: '#71dd37', bg: '#eafbdf' },
      { label: 'Deals Closed',    value: data.deals_closed    ?? 0, icon: 'bx bx-trophy',     color: '#ff3e1d', bg: '#ffe8e5' },
    ]
  } catch (e) {
    console.error('Failed to fetch home stats:', e)
  }

  // Delay agar DOM update dulu setelah data masuk
  setTimeout(animateCounters, 300)
})
</script>

<style scoped>
/* STAT CARD */
.stat-card {
  border: none;
  border-radius: 12px;
  animation: fadeSlideUp 0.5s ease both;
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.stat-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-icon-wrap i {
  font-size: 24px;
}

/* FEATURE CARD */
.feature-card {
  border: none;
  border-radius: 12px;
  animation: fadeSlideUp 0.5s ease both;
  transition: transform 0.25s, box-shadow 0.25s;
  position: relative;
  overflow: hidden;
}
.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 32px rgba(0,0,0,0.12);
}
.feature-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.feature-bar {
  height: 4px;
  width: 100%;
  position: absolute;
  bottom: 0;
  left: 0;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.3s ease;
}
.feature-card:hover .feature-bar {
  transform: scaleX(1);
}

/* ANIMATION */
@keyframes fadeSlideUp {
  from {
    opacity: 0;
    transform: translateY(24px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>