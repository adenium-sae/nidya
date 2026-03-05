import client from './client'
import { type AxiosResponse } from 'axios'

export const categoriesApi = {
  list(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/categories', { params })
  },

  show(id: string | number): Promise<AxiosResponse> {
    return client.get(`/admin/categories/${id}`)
  },

  create(data: any): Promise<AxiosResponse> {
    return client.post('/admin/categories', data)
  },

  update(id: string | number, data: any): Promise<AxiosResponse> {
    return client.put(`/admin/categories/${id}`, data)
  },

  destroy(id: string | number): Promise<AxiosResponse> {
    return client.delete(`/admin/categories/${id}`)
  },
}
