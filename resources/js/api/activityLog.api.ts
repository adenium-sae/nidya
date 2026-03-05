import client from './client'
import { type AxiosResponse } from 'axios'

export const activityLogApi = {
  list(params: Record<string, any> = {}): Promise<AxiosResponse> {
    return client.get('/admin/activity-logs', { params })
  },
}
