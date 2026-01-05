import { defineStore } from 'pinia'
import axios from 'axios'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  
  const loading = ref(false)
  const message = ref('')
  const status = ref('') // success | error

  const forgotPassword = async (email) => {
    loading.value = true
    message.value = ''
    status.value = ''

    try {
      const res = await axios.post(
        'http://127.0.0.1:8000/api/forgot-password-request',
        { email }
      )

      message.value = res.data.message
      status.value = 'success'

      loading.value = false
      return true // <-- indikator sukses

    } catch (err) {
      status.value = 'error'

      if (err.response) {
        message.value = err.response.data.message
      } else {
        message.value = 'Server error.'
      }

      loading.value = false
      return false
    }
  }

  return {
    loading,
    message,
    status,
    forgotPassword,
  }
})
