import client from './client'
import { type AxiosResponse } from 'axios'

export const warehousesApi = {
  list(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/warehouses', { params })
  },

  show(id: string | number): Promise<AxiosResponse> {
    return client.get(`/admin/warehouses/${id}`)
  },

  types(): Promise<AxiosResponse> {
    return client.get('/admin/warehouses/types')
  },

  create(data: any): Promise<AxiosResponse> {
    return client.post('/admin/warehouses', data)
  },

  update(id: string | number, data: any): Promise<AxiosResponse> {
    return client.put(`/admin/warehouses/${id}`, data)
  },

  destroy(id: string | number): Promise<AxiosResponse> {
    return client.delete(`/admin/warehouses/${id}`)
  },
}
