
import './bootstrap'
import '../css/app.css'
import '../scss/app.scss'

import { createApp } from 'vue'
import App from './App.vue'
import { createPinia } from 'pinia'
import router from './router'
import axios from "axios"
// ===== Vendor helpers (WAJIB)
import '../vendor/assets/vendor/js/helpers.js'
import '../vendor/assets/js/config.js'

// untuk PWA
// import { registerSW } from "virtual:pwa-register";
// registerSW({ immediate: true });


// jQuery + Popper
import jQuery from 'jquery'
window.$ = window.jQuery = jQuery
import * as Popper from '@popperjs/core'
window.Popper = Popper

// ===== Bootstrap JS (gunakan file vendor Sneat)
import '../vendor/assets/vendor/js/bootstrap.js'

// ===== Vendor libs lainnya
import '../vendor/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js'
import '../vendor/assets/vendor/js/menu.js'

// ===== Main JS Sneat
import '../vendor/assets/js/main.js'


import '@fortawesome/fontawesome-free/css/all.min.css'

// Import Vue Toastification
import Toast, { POSITION } from 'vue-toastification'
import 'vue-toastification/dist/index.css'

import VueSweetalert2 from 'vue-sweetalert2'
import Swal from 'sweetalert2'

// optional css
import 'sweetalert2/dist/sweetalert2.min.css'



// ===== Vue mount
const app = createApp(App)
// Konfigurasi global toast
app.use(Toast, {
 position: "top-right",
  timeout: 5000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true,
  draggable: true,
  draggablePercent: 0.6,
  showCloseButtonOnHover: false,
  hideProgressBar: true,
  closeButton: "button",
  icon: "fas fa-rocket",
  rtl: false
})
const pinia = createPinia()   
app.use(pinia)  
app.use(router)
app.use(VueSweetalert2)
app.mount('#app')


