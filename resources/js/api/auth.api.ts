import client from './client'
import axios, { type AxiosResponse } from 'axios'

export const authApi = {
  csrfCookie(): Promise<AxiosResponse> {
    return axios.get('/sanctum/csrf-cookie')
  },

  login(credentials: any): Promise<AxiosResponse> {
    return client.post('/auth/signin', credentials)
  },

  loginOtp(data: any): Promise<AxiosResponse> {
    return client.post('/auth/signin/otp', data)
  },

  generateOtp(data: any): Promise<AxiosResponse> {
    return client.post('/auth/signin/otp/generate', data)
  },

  register(data: any): Promise<AxiosResponse> {
    return client.post('/auth/signup', data)
  },

  logout(): Promise<AxiosResponse> {
    return client.post('/auth/signout')
  },

  me(): Promise<AxiosResponse> {
    return client.get('/user')
  },
}
