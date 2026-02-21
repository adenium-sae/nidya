import client from './client'
import { type AxiosResponse } from 'axios'

export const dashboardApi = {
  getStats(): Promise<AxiosResponse> {
    return client.get('/dashboard')
  },
}
