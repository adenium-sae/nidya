import client from './client'
import { type AxiosResponse } from 'axios'

export const salesApi = {
  list(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/sales', { params })
  },

  show(id: string | number): Promise<AxiosResponse> {
    return client.get(`/admin/sales/${id}`)
  },

  create(data: any): Promise<AxiosResponse> {
    return client.post('/admin/sales', data)
  },

  cancel(id: string | number): Promise<AxiosResponse> {
    return client.post(`/admin/sales/${id}/cancel`)
  },
}
