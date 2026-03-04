import client from './client'
import { type AxiosResponse } from 'axios'

export const storesApi = {
  list(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/stores', { params })
  },

  show(id: string | number): Promise<AxiosResponse> {
    return client.get(`/admin/stores/${id}`)
  },

  create(data: any): Promise<AxiosResponse> {
    return client.post('/admin/stores', data)
  },

  update(id: string | number, data: any): Promise<AxiosResponse> {
    return client.put(`/admin/stores/${id}`, data)
  },

  destroy(id: string | number): Promise<AxiosResponse> {
    return client.delete(`/admin/stores/${id}`)
  },
}
