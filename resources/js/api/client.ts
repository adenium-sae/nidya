import axios, { type InternalAxiosRequestConfig, type AxiosResponse } from 'axios'

const client = axios.create({
  baseURL: '/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

// Request interceptor: attach auth token
client.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor: handle global errors
client.interceptors.response.use(
  (response: AxiosResponse) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      if (typeof window !== 'undefined' && window.location.pathname !== '/sign-in') {
        window.location.href = '/sign-in'
      }
    }
    return Promise.reject(error)
  }
)

export default client
