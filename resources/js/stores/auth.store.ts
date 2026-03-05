import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth.api'
import type { User } from '@/types/models'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoadingUser = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  const fullName = computed(() => {
    if (!user.value) return ''
    const profile = user.value.profile
    if (!profile) return user.value.email || ''
    const parts = [profile.first_name]
    if (profile.middle_name) parts.push(profile.middle_name)
    if (profile.last_name) parts.push(profile.last_name)
    if (profile.second_last_name) parts.push(profile.second_last_name)
    return parts.join(' ')
  })

  const email = computed(() => user.value?.email || '')
  const avatarUrl = computed(() => user.value?.profile?.avatar_url || '')

  async function login(credentials: any) {
    await authApi.csrfCookie()
    const response = await authApi.login(credentials)
    const newToken = response.data.data.token
    token.value = newToken
    localStorage.setItem('auth_token', newToken)
    await fetchUser()
    return response
  }

  async function fetchUser() {
    if (!token.value) return
    isLoadingUser.value = true
    try {
      const response = await authApi.me()
      user.value = response.data
    } catch (error: any) {
      console.error('Error fetching user:', error)
      if (error.response?.status === 401) {
        logout()
      }
    } finally {
      isLoadingUser.value = false
    }
  }

  function logout() {
    user.value = null
    token.value = null
    localStorage.removeItem('auth_token')
  }

  function setToken(newToken: string) {
    token.value = newToken
    localStorage.setItem('auth_token', newToken)
  }

  return {
    user,
    token,
    isLoadingUser,
    isAuthenticated,
    fullName,
    email,
    avatarUrl,
    login,
    fetchUser,
    logout,
    setToken,
  }
})
