// HTTP 请求封装
import { API_CONFIG } from '@/config/api'
import { getToken, removeToken, removeUserInfo } from './storage'
import { getLocale } from '@/locale'
import { toast } from '@/composables/useToast'
import i18n from '@/locale'

// 获取翻译函数
const t = i18n.global.t

// 响应数据结构
export interface ApiResponse<T = any> {
  code: number
  msg: string
  data: T
}

// 请求配置
export interface RequestOptions {
  url: string
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'
  data?: any
  header?: Record<string, string>
  showLoading?: boolean
  loadingText?: string
  showError?: boolean
}

// 请求错误
export class RequestError extends Error {
  code: number
  data: any

  constructor(code: number, message: string, data?: any) {
    super(message)
    this.code = code
    this.data = data
    this.name = 'RequestError'
  }
}

// 错误码
export const ERROR_CODES = {
  SUCCESS: 0,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  SERVER_ERROR: 500,
  TOKEN_EXPIRED: 10001,
  TOKEN_INVALID: 10002,
}

// 请求函数
export async function request<T = any>(options: RequestOptions): Promise<ApiResponse<T>> {
  const {
    url,
    method = 'GET',
    data,
    header = {},
    showLoading = false,
    loadingText = '',
    showError = true,
  } = options

  // 显示加载
  if (showLoading) {
    uni.showLoading({
      title: loadingText || t('common.loading'),
      mask: true,
    })
  }

  // 获取 token
  const token = getToken()

  // 构建请求头
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept-Language': getLocale(),
    ...header,
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  // 构建完整 URL
  const fullUrl = url.startsWith('http') ? url : `${API_CONFIG.baseUrl}${url}`

  return new Promise((resolve, reject) => {
    uni.request({
      url: fullUrl,
      method,
      data,
      header: headers,
      timeout: API_CONFIG.timeout,
      success: (res) => {
        if (showLoading) {
          uni.hideLoading()
        }

        // 检查 HTTP 状态码（H5 平台 4xx/5xx 也会进入 success 回调）
        const statusCode = res.statusCode
        if (statusCode === 401) {
          // HTTP 401 未授权，跳转登录页
          handleTokenError()
          const response = res.data as ApiResponse<T>
          reject(new RequestError(401, response?.msg || 'Unauthorized', response?.data))
          return
        }

        if (statusCode < 200 || statusCode >= 300) {
          // 其他 HTTP 错误
          const response = res.data as ApiResponse<T>
          const errorMsg = response?.msg || `HTTP Error ${statusCode}`
          if (showError) {
            toast.error(errorMsg)
          }
          reject(new RequestError(statusCode, errorMsg, response?.data))
          return
        }

        const response = res.data as ApiResponse<T>

        // 请求成功
        if (response.code === ERROR_CODES.SUCCESS) {
          resolve(response)
          return
        }

        // Token 过期或无效（业务层面的 code）
        if (
          response.code === ERROR_CODES.TOKEN_EXPIRED ||
          response.code === ERROR_CODES.TOKEN_INVALID ||
          response.code === ERROR_CODES.UNAUTHORIZED
        ) {
          handleTokenError()
          reject(new RequestError(response.code, response.msg, response.data))
          return
        }

        // 其他错误
        if (showError) {
          toast.error(response.msg || t('common.requestFailed'))
        }
        reject(new RequestError(response.code, response.msg, response.data))
      },
      fail: (err) => {
        if (showLoading) {
          uni.hideLoading()
        }

        const errorMsg = err.errMsg || t('common.networkError')
        if (showError) {
          toast.error(errorMsg)
        }
        reject(new RequestError(-1, errorMsg))
      },
    })
  })
}

// 处理 Token 错误
function handleTokenError() {
  removeToken()
  removeUserInfo()

  // 直接跳转到登录页
  uni.reLaunch({
    url: '/pages/auth/login',
  })
}

// 快捷方法
export function get<T = any>(url: string, data?: any, options?: Partial<RequestOptions>) {
  return request<T>({ url, method: 'GET', data, ...options })
}

export function post<T = any>(url: string, data?: any, options?: Partial<RequestOptions>) {
  return request<T>({ url, method: 'POST', data, ...options })
}

export function put<T = any>(url: string, data?: any, options?: Partial<RequestOptions>) {
  return request<T>({ url, method: 'PUT', data, ...options })
}

export function del<T = any>(url: string, data?: any, options?: Partial<RequestOptions>) {
  return request<T>({ url, method: 'DELETE', data, ...options })
}

// 上传文件
export interface UploadOptions {
  url: string
  filePath: string
  name?: string
  formData?: Record<string, any>
  showLoading?: boolean
}

export async function upload<T = any>(options: UploadOptions): Promise<ApiResponse<T>> {
  const { url, filePath, name = 'file', formData = {}, showLoading = true } = options

  if (showLoading) {
    uni.showLoading({
      title: '上传中...',
      mask: true,
    })
  }

  const token = getToken()
  const fullUrl = url.startsWith('http') ? url : `${API_CONFIG.baseUrl}${url}`

  return new Promise((resolve, reject) => {
    uni.uploadFile({
      url: fullUrl,
      filePath,
      name,
      formData,
      header: {
        Authorization: token ? `Bearer ${token}` : '',
        'Accept-Language': getLocale(),
      },
      success: (res) => {
        if (showLoading) {
          uni.hideLoading()
        }

        try {
          const response = JSON.parse(res.data) as ApiResponse<T>
          if (response.code === ERROR_CODES.SUCCESS) {
            resolve(response)
          } else {
            toast.error(response.msg || '上传失败')
            reject(new RequestError(response.code, response.msg))
          }
        } catch (e) {
          reject(new RequestError(-1, '解析响应失败'))
        }
      },
      fail: (err) => {
        if (showLoading) {
          uni.hideLoading()
        }
        toast.error('上传失败')
        reject(new RequestError(-1, err.errMsg || '上传失败'))
      },
    })
  })
}
