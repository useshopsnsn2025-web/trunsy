// 本地存储工具

// 存储键名
export const STORAGE_KEYS = {
  TOKEN: 'token',
  REFRESH_TOKEN: 'refreshToken',
  USER_INFO: 'userInfo',
  LOCALE: 'locale',
  REGION: 'region',
  CURRENCY: 'currency',
  SEARCH_HISTORY: 'searchHistory',
  CART: 'cart',
}

// 设置存储
export function setStorage(key: string, value: any): void {
  try {
    const data = typeof value === 'string' ? value : JSON.stringify(value)
    uni.setStorageSync(key, data)
  } catch (e) {
    console.error('Storage set error:', e)
  }
}

// 获取存储
export function getStorage<T = any>(key: string, defaultValue?: T): T | null {
  try {
    const data = uni.getStorageSync(key)
    if (!data) return defaultValue ?? null

    try {
      return JSON.parse(data) as T
    } catch {
      return data as T
    }
  } catch (e) {
    console.error('Storage get error:', e)
    return defaultValue ?? null
  }
}

// 删除存储
export function removeStorage(key: string): void {
  try {
    uni.removeStorageSync(key)
  } catch (e) {
    console.error('Storage remove error:', e)
  }
}

// 清空存储
export function clearStorage(): void {
  try {
    uni.clearStorageSync()
  } catch (e) {
    console.error('Storage clear error:', e)
  }
}

// Token 相关
export function getToken(): string | null {
  return getStorage<string>(STORAGE_KEYS.TOKEN)
}

export function setToken(token: string): void {
  setStorage(STORAGE_KEYS.TOKEN, token)
}

export function removeToken(): void {
  removeStorage(STORAGE_KEYS.TOKEN)
  removeStorage(STORAGE_KEYS.REFRESH_TOKEN)
}

// 用户信息相关
export interface UserInfo {
  id: number
  uuid: string
  nickname: string
  avatar: string
  email?: string
  phone?: string
  language: string
  currency: string
  isSeller: boolean
  createdAt?: string
  lastLoginAt?: string
}

export function getUserInfo(): UserInfo | null {
  return getStorage<UserInfo>(STORAGE_KEYS.USER_INFO)
}

export function setUserInfo(userInfo: UserInfo): void {
  setStorage(STORAGE_KEYS.USER_INFO, userInfo)
}

export function removeUserInfo(): void {
  removeStorage(STORAGE_KEYS.USER_INFO)
}

// 搜索历史
export function getSearchHistory(): string[] {
  return getStorage<string[]>(STORAGE_KEYS.SEARCH_HISTORY) || []
}

export function addSearchHistory(keyword: string): void {
  const history = getSearchHistory()
  // 移除重复项
  const index = history.indexOf(keyword)
  if (index > -1) {
    history.splice(index, 1)
  }
  // 添加到头部
  history.unshift(keyword)
  // 最多保存20条
  if (history.length > 20) {
    history.pop()
  }
  setStorage(STORAGE_KEYS.SEARCH_HISTORY, history)
}

export function clearSearchHistory(): void {
  removeStorage(STORAGE_KEYS.SEARCH_HISTORY)
}
