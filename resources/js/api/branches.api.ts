import client from './client'
import { type AxiosResponse } from 'axios'

export const branchesApi = {
  list(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/branches', { params })
  },

  show(id: string | number): Promise<AxiosResponse> {
    return client.get(`/admin/branches/${id}`)
  },

  update(id: string | number, data: any): Promise<AxiosResponse> {
    return client.put(`/admin/branches/${id}`, data)
  },
}
