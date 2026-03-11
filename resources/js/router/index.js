import { createRouter, createWebHistory } from 'vue-router'
// frontend
import Home from '../pages/frontend/home/files.vue'
import mapsTracking from '../pages/frontend/home/mapsTracking.vue'
import salesTable from '../pages/frontend/home/visitSalesTable.vue'
import dashboardHome from '../pages/frontend/home/dashboard.vue'
import activityFeed from '../pages/frontend/home/activityFeed.vue'
import FrontTable from '../pages/frontend/exampleComponentFronent/table/table.vue'
import FrontForm from '../pages/frontend/exampleComponentFronent/form/form.vue'
import FrontError from '../pages/frontend/exampleComponentFronent/error/error.vue'

// Auth
import Login from '@/pages/auth/login.vue'
import resetPassword from '@/pages/auth/resetPassword.vue'
import forgotPassword from '../pages/auth/forgotPassword.vue'

// backend Administrator
import Dashboard from '../pages/backend/administrator/dashboard/files.vue'
import Menu from '../pages/backend/administrator/menu/menuData.vue'
import Roles from '../pages/backend//administrator/role/roleData.vue'
import Submenu from '../pages/backend/administrator/submenu/submenuData.vue'
import Users from '../pages/backend/administrator/users/userData.vue'
import settingApp from '../pages/backend/administrator/setting/settingApp.vue'

//backend Sales
import salesHome from '../pages/backend/sales/home/home.vue'
import masterEmployee from '../pages/backend/sales/master/employee.vue'
import masterLeads from '../pages/backend/sales/master/leads.vue'

import presensi from '../pages/backend/sales/attendance/presensi.vue'
import leads from '../pages/backend/sales/leads/leads.vue'
import followUp from '../pages/backend/sales/follow_up/followUp.vue'
import costumers from '../pages/backend/sales/customers/customers.vue'
import visitsSales from '../pages/backend/sales/visits/visits.vue'
import visitsSalesLeads from '../pages/backend/sales/visits/visitLeads.vue'
import visitsSalesCustomers from '../pages/backend/sales/visits/visitCustomers.vue'


import reportsForSales from '../pages/backend/sales/reports/reports.vue'




//backend Manager
import managerHome from '../pages/backend/manager/home/home.vue'

import profilePage from '../pages/backend/global/profile/profile.vue'




import Form from '../pages/backend/exampleComponent/form.vue'
import Table from '../pages/backend/exampleComponent/table.vue'
import notFound from '../pages/backend/exampleComponent/Notfound.vue'
// import profilePage from '../pages/backend/exampleComponent/profilePage.vue'



const routes = [
  // Front
  { path: '/', component: Home },
  { path: '/maps/tracking/sales', component: mapsTracking },
  { path: '/history/monitoring/sales', component: salesTable },
  { path: '/dashboard/sales', component: dashboardHome },
  { path: '/dashboard/activity/feed', component: activityFeed },
  { path: '/frontend/table', component: FrontTable },
  { path: '/frontend/form', component: FrontForm },
  { path: '/frontend/error', component: FrontError },

  // Auth
  { path: '/login', name: 'login', component: Login },
  // { path: '/register', name: 'register', component: Register }, fitur ini belum ada
  { path: '/forgot-password', name: 'forgot-password', component: forgotPassword },
  { path: '/reset-password', name: 'reset-password', component: resetPassword },

  // Backend Administrator
  {
  path: '/administrator-dashboard',
  component: Dashboard,
  meta: { requiresAuth: true }   
  },

  {
  path: '/administrator-menu',
  component: Menu,
  meta: { requiresAuth: true }   
  },

  {
  path: '/administrator-role',
  component: Roles,
  meta: { requiresAuth: true }   
  },

  {
  path: '/administrator-submenu',
  component: Submenu,
  meta: { requiresAuth: true }   
  },


  {
  path: '/administrator-users',
  component: Users,
  meta: { requiresAuth: true }   
  },

  {
  path: '/setting-app-global',
  component: settingApp,
  meta: { requiresAuth: true }   
  },



 // Backend Sales
  {
  path: '/sales-home',
  component: salesHome,
  // meta: { requiresAuth: true }   
  },

  {
  path: '/data-master-employee',
  component: masterEmployee,
  // meta: { requiresAuth: true }   
  },

  {
  path: '/data-master-leads-bulk',
  component: masterLeads,
  // meta: { requiresAuth: true }   
  },

  {
  path: '/sales-timesheets-leave-attendance',
  component: presensi,
  // meta: { requiresAuth: true }   
  },
  

  {
  path: '/sales-leads',
  component: leads,
  // meta: { requiresAuth: true }   
  },
 

  {
  path: '/sales-follow-up',
  component: followUp,
  // meta: { requiresAuth: true }   
  },


  {
  path: '/sales-customers',
  component: costumers,
  // meta: { requiresAuth: true }   
  },
  
  {
  path: '/sales-visit',
  component: visitsSales,
  // meta: { requiresAuth: true }   
  },

  {
  path: '/sales-visit-leads',
  component: visitsSalesLeads,
  // meta: { requiresAuth: true }   
  },

  {
  path: '/sales-visit-customers',
  component: visitsSalesCustomers,
  // meta: { requiresAuth: true }   
  },

  {
  path: '/sales-reports-visits-and-follow-up',
  component: reportsForSales,
  // meta: { requiresAuth: true }   
  },


 // Backend Manager
  {
  path: '/manager-home',
  component: managerHome,
  // meta: { requiresAuth: true }   
  },


  // { path: '/form-example-template', component: Form },
  { path: '/administrator-users', component: Form },
  // { path: '/table-example-template', component: Table },
  { path: '/administrator-role', component: Table },
  { path: '/error-example-template', component: notFound },
  { path: '/profile-user', name: 'profile', component: profilePage },

  // *** WILDCARD — 404 handler ***
  { path: '/:pathMatch(.*)*', component: notFound },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})


router.beforeEach((to, from, next) => {
  const isLoggedIn = !!localStorage.getItem("auth_token"); // cek token

  if (to.meta.requiresAuth && !isLoggedIn) {
    next('/login'); // redirect ke login
  } else {
    next();
  }
});


export default router
