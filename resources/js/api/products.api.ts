import client from './client'
import { type AxiosResponse } from 'axios'

export const productsApi = {
  list(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/products', { params })
  },

  show(id: string | number): Promise<AxiosResponse> {
    return client.get(`/admin/products/${id}`)
  },

  createSingle(data: any): Promise<AxiosResponse> {
    return client.post('/admin/products/single', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  createMultiple(data: any): Promise<AxiosResponse> {
    return client.post('/admin/products/multiple', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  createAll(data: any): Promise<AxiosResponse> {
    return client.post('/admin/products/all', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  update(id: string | number, data: any): Promise<AxiosResponse> {
    return client.put(`/admin/products/${id}`, data)
  },

  destroy(id: string | number): Promise<AxiosResponse> {
    return client.delete(`/admin/products/${id}`)
  },
}
