import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import api from '@/services/api'

interface User {
  id: number
  name: string
  email: string
}

interface AuthResponse {
  message: string
  user: User
}

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const loading = ref(false)
  const initialized = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const isAuthenticated = computed(() => user.value !== null)

  // Retrieve the currently authenticated user
  async function fetchUser() {
    loading.value = true
    error.value = null

    try {
      const response = await api.get<AuthResponse>('/api/user')
      user.value = response.data.user
    } catch (err) {
      user.value = null

      if (axios.isAxiosError(err) && err.response?.status !== 401) {
        error.value = 'Unable to retrieve your account information.'
      }
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  // Register a new user
  async function register(
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
  ) {
    loading.value = true
    error.value = null

    try {
      await api.get('/sanctum/csrf-cookie')

      const response = await api.post<AuthResponse>('/api/register', {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })

      user.value = response.data.user
      initialized.value = true

      return true
    } catch (err) {
      if (axios.isAxiosError(err)) {
        error.value =
          err.response?.data?.message || 'Registration failed. Please try again.'
      } else {
        error.value = 'An unexpected error occurred.'
      }

      return false
    } finally {
      loading.value = false
    }
  }

  // Log in an existing user
  async function login(email: string, password: string) {
    loading.value = true
    error.value = null

    try {
      await api.get('/sanctum/csrf-cookie')

      const response = await api.post<AuthResponse>('/api/login', {
        email,
        password,
      })

      user.value = response.data.user
      initialized.value = true

      return true
    } catch (err) {
      if (axios.isAxiosError(err)) {
        error.value =
          err.response?.data?.message || 'Login failed. Please try again.'
      } else {
        error.value = 'An unexpected error occurred.'
      }

      return false
    } finally {
      loading.value = false
    }
  }

  // Log out the current user
  async function logout() {
    loading.value = true
    error.value = null

    try {
      await api.post('/api/logout')

      user.value = null
      initialized.value = true

      return true
    } catch (err) {
      error.value = 'Logout failed. Please try again.'
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    loading,
    initialized,
    error,
    isAuthenticated,
    fetchUser,
    register,
    login,
    logout,
  }
})