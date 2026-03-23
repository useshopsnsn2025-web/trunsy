import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'

const request = axios.create({
  baseURL: '/admin',
  timeout: 30000
})

// 请求拦截器
request.interceptors.request.use(
  (config) => {
    // 添加Token
    const token = localStorage.getItem('admin_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// 响应拦截器
request.interceptors.response.use(
  (response) => {
    const res = response.data
    if (res.code !== 0) {
      ElMessage.error(res.msg || '请求失败')
      return Promise.reject(new Error(res.msg || '请求失败'))
    }
    return res
  },
  (error) => {
    if (error.response) {
      const status = error.response.status
      const data = error.response.data

      if (status === 401) {
        // 未登录或Token过期
        localStorage.removeItem('admin_token')
        localStorage.removeItem('admin_info')
        ElMessage.error('登录已过期，请重新登录')
        router.push('/login')
      } else if (data && data.msg) {
        ElMessage.error(data.msg)
      } else {
        ElMessage.error(error.message || '网络错误')
      }
    } else {
      ElMessage.error(error.message || '网络错误')
    }
    return Promise.reject(error)
  }
)

export default request
