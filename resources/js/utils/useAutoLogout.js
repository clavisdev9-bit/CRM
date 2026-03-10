import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useLogoutStore } from '@/stores/logoutStore'

export function useAutoLogout(timeoutMinutes = 60) {
  const router      = useRouter()
  const logoutStore = useLogoutStore()
  const showWarning      = ref(false)
  const warningCountdown = ref(60)

  let logoutTimer       = null
  let warningTimer      = null
  let countdownInterval = null

  const resetTimer = () => {
    clearTimeout(logoutTimer)
    clearTimeout(warningTimer)
    clearInterval(countdownInterval)
    showWarning.value      = false
    warningCountdown.value = 60

    // Warning 1 menit sebelum logout
    warningTimer = setTimeout(() => {
      showWarning.value = true
      countdownInterval = setInterval(() => {
        warningCountdown.value--
      }, 1000)
    }, (timeoutMinutes * 60 - 60) * 1000)

    // Auto logout
    logoutTimer = setTimeout(async () => {
      await logoutStore.logout()
      router.push('/login?reason=timeout')
    }, timeoutMinutes * 60 * 1000)
  }

  const stayLoggedIn = () => {
    showWarning.value = false
    resetTimer()
  }

  const events = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart']

  onMounted(() => {
    events.forEach(e => window.addEventListener(e, resetTimer))
    resetTimer()
  })

  onUnmounted(() => {
    events.forEach(e => window.removeEventListener(e, resetTimer))
    clearTimeout(logoutTimer)
    clearTimeout(warningTimer)
    clearInterval(countdownInterval)
  })

  return { showWarning, warningCountdown, stayLoggedIn }
}





// buat testing
// import { ref, onMounted, onUnmounted } from 'vue'
// import { useRouter } from 'vue-router'
// import { useLogoutStore } from '@/stores/logoutStore'

// export function useAutoLogout(timeoutMinutes = 1) { // ⬅️ default 1 menit
//   const router      = useRouter()
//   const logoutStore = useLogoutStore()
//   const showWarning      = ref(false)
//   const warningCountdown = ref(10) // ⬅️ warning 10 detik

//   let logoutTimer       = null
//   let warningTimer      = null
//   let countdownInterval = null

//   const resetTimer = () => {
//     clearTimeout(logoutTimer)
//     clearTimeout(warningTimer)
//     clearInterval(countdownInterval)
//     showWarning.value      = false
//     warningCountdown.value = 10 // ⬅️ reset ke 10 detik

//     // Warning 10 detik sebelum logout
//     warningTimer = setTimeout(() => {
//       showWarning.value = true
//       countdownInterval = setInterval(() => {
//         warningCountdown.value--
//       }, 1000)
//     }, (timeoutMinutes * 60 - 10) * 1000) // ⬅️ muncul 10 detik sebelum logout

//     // Auto logout setelah 1 menit
//     logoutTimer = setTimeout(async () => {
//       await logoutStore.logout()
//       router.push('/login?reason=timeout')
//     }, timeoutMinutes * 60 * 1000)
//   }

//   const stayLoggedIn = () => {
//     showWarning.value = false
//     resetTimer()
//   }

//   const events = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart']

//   onMounted(() => {
//     events.forEach(e => window.addEventListener(e, resetTimer))
//     resetTimer()
//   })

//   onUnmounted(() => {
//     events.forEach(e => window.removeEventListener(e, resetTimer))
//     clearTimeout(logoutTimer)
//     clearTimeout(warningTimer)
//     clearInterval(countdownInterval)
//   })

//   return { showWarning, warningCountdown, stayLoggedIn }
// }