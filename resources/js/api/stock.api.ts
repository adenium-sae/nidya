import client from './client'
import { type AxiosResponse } from 'axios'

export const stockApi = {
  list(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/inventory/stock', { params })
  },

  updateQuantity(id: string | number, data: any): Promise<AxiosResponse> {
    return client.patch(`/admin/inventory/stock/${id}/quantity`, data)
  },

  adjust(data: any): Promise<AxiosResponse> {
    return client.post('/admin/inventory/stock/adjust', data)
  },

  transfer(data: any): Promise<AxiosResponse> {
    return client.post('/admin/inventory/stock/transfer', data)
  },

  movements(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/inventory/stock/movements', { params })
  },

  adjustments(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/inventory/stock/adjustments', { params })
  },

  locations(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/inventory/locations', { params })
  },

  createLocation(data: any): Promise<AxiosResponse> {
    return client.post('/admin/inventory/locations', data)
  },
}
