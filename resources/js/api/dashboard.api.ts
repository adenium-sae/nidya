import client from './client'
import { type AxiosResponse } from 'axios'

export const dashboardApi = {
  getStats(period: string = '7d'): Promise<AxiosResponse> {
    return client.get('/admin/dashboard', { params: { period } })
  },
}
