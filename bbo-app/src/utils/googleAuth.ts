/**
 * Google OAuth 授权登录工具
 * 支持 H5 端和 APP 端
 */

import { fetchOAuthConfig, getCachedOAuthConfig } from '@/config/oauth'

// Google Identity Services SDK URL
const GOOGLE_GIS_SDK_URL = 'https://accounts.google.com/gsi/client'

// SDK 加载状态
let sdkLoaded = false
let sdkLoading = false
let loadPromise: Promise<void> | null = null

/**
 * 加载 Google Identity Services SDK (H5 端)
 */
function loadGoogleSdk(): Promise<void> {
  if (sdkLoaded) {
    return Promise.resolve()
  }

  if (sdkLoading && loadPromise) {
    return loadPromise
  }

  sdkLoading = true
  loadPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = GOOGLE_GIS_SDK_URL
    script.async = true
    script.defer = true

    script.onload = () => {
      sdkLoaded = true
      sdkLoading = false
      resolve()
    }

    script.onerror = () => {
      sdkLoading = false
      loadPromise = null
      reject(new Error('Failed to load Google SDK'))
    }

    document.head.appendChild(script)
  })

  return loadPromise
}

/**
 * 解析 URL 查询字符串（兼容 APP 端，不依赖 URLSearchParams）
 */
function parseQueryString(queryString: string): Record<string, string> {
  const params: Record<string, string> = {}
  if (!queryString) return params

  const pairs = queryString.split('&')
  for (const pair of pairs) {
    const [key, value] = pair.split('=')
    if (key) {
      params[decodeURIComponent(key)] = value ? decodeURIComponent(value) : ''
    }
  }
  return params
}

/**
 * 解析 Google OAuth 回调参数
 * 从 URL hash 中提取 token 并验证 state
 */
function parseGoogleOAuthCallback(): string {
  const hash = window.location.hash

  if (!hash) {
    throw new Error('No callback parameters found')
  }

  // Google OAuth 返回的 hash 格式: #state=xxx&id_token=xxx
  // 但 UniApp 路由会在前面加 /，变成: #/state=xxx&id_token=xxx
  // 需要去掉开头的 #/ 或 #
  let hashParams = hash.substring(1) // 去掉 #

  // 如果以 / 开头（UniApp hash 路由格式），去掉 /
  if (hashParams.startsWith('/')) {
    hashParams = hashParams.substring(1) // 去掉 /
  }

  // 解析 hash 参数（使用兼容方法）
  const params = parseQueryString(hashParams)
  const idToken = params['id_token'] || null
  const returnedState = params['state'] || null
  const error = params['error'] || null
  const errorDescription = params['error_description'] || null

  // 清除 URL 中的 hash
  history.replaceState(null, '', window.location.pathname + window.location.search)

  // 验证 state
  const savedState = sessionStorage.getItem('google_oauth_state')

  // 清除 sessionStorage 中的 OAuth 数据
  sessionStorage.removeItem('google_oauth_state')
  sessionStorage.removeItem('google_oauth_nonce')
  sessionStorage.removeItem('google_oauth_return_url')

  if (error) {
    throw new Error(errorDescription || error)
  } else if (!savedState) {
    // state 未保存，可能是浏览器清除了 sessionStorage（降级处理）
    if (idToken) {
      return idToken
    }
    throw new Error('No ID token received')
  } else if (returnedState !== savedState) {
    throw new Error('OAuth state mismatch')
  } else if (idToken) {
    return idToken
  } else {
    throw new Error('No ID token received')
  }
}

/**
 * H5 端 Google 登录
 * 使用 OAuth 2.0 隐式流程，页面内直接跳转完成授权
 */
async function googleLoginH5(): Promise<string> {
  // 先获取 OAuth 配置
  const config = await fetchOAuthConfig()

  if (!config.google.enabled) {
    throw new Error('Google login is not enabled')
  }

  // H5 端优先使用 Web Client ID
  const clientId = config.google.clientIdWeb || config.google.clientId
  if (!clientId) {
    throw new Error('Google OAuth not configured. Please configure it in admin settings.')
  }

  // 生成随机 state 防止 CSRF
  const state = Math.random().toString(36).substring(2, 15)
  const nonce = Math.random().toString(36).substring(2, 15)

  // 保存 state 到 sessionStorage
  sessionStorage.setItem('google_oauth_state', state)
  sessionStorage.setItem('google_oauth_nonce', nonce)
  // 保存当前页面 URL，以便回调后恢复
  sessionStorage.setItem('google_oauth_return_url', window.location.href)

  // 构建 OAuth 2.0 授权 URL
  const redirectUri = window.location.origin
  const authUrl = new URL('https://accounts.google.com/o/oauth2/v2/auth')
  authUrl.searchParams.set('client_id', clientId)
  authUrl.searchParams.set('redirect_uri', redirectUri)
  authUrl.searchParams.set('response_type', 'id_token')
  authUrl.searchParams.set('scope', 'openid email profile')
  authUrl.searchParams.set('state', state)
  authUrl.searchParams.set('nonce', nonce)
  authUrl.searchParams.set('prompt', 'select_account')

  // 直接跳转到 Google 授权页面
  window.location.href = authUrl.toString()

  // 这个 Promise 不会 resolve，因为页面会跳转
  return new Promise(() => {})
}

/**
 * 检查是否是 Google OAuth 回调
 * 仅检查 URL 中是否有回调参数，不执行解析
 */
export function hasGoogleOAuthCallback(): boolean {
  const hash = window.location.hash
  return !!(hash && (hash.includes('id_token=') || hash.includes('error=')))
}

/**
 * 处理 Google OAuth 回调
 * 解析回调参数并返回 ID Token
 */
export function handleGoogleOAuthCallback(): string {
  return parseGoogleOAuthCallback()
}

/**
 * APP 端 Google 登录
 * 使用 uni-app 的 plus.oauth
 */
async function googleLoginApp(): Promise<string> {
  // 先获取 OAuth 配置
  const config = await fetchOAuthConfig()

  if (!config.google.enabled) {
    throw new Error('Google login is not enabled')
  }

  return new Promise((resolve, reject) => {
    // 获取 OAuth 服务列表
    plus.oauth.getServices(
      (services) => {
        // 查找 Google 服务
        const googleService = services.find((s) => s.id === 'google')

        if (!googleService) {
          reject(new Error('Google login service not available'))
          return
        }

        // 执行登录
        googleService.login(
          (loginResult) => {
            const authResult = loginResult.target?.authResult || loginResult.authResult

            // UniApp plus.oauth 返回 openid/unionid，需要再获取用户信息
            // 或者检查是否有 access_token/id_token
            const idToken = authResult?.id_token || authResult?.idToken
            const accessToken = authResult?.access_token || authResult?.accessToken
            const openid = authResult?.openid

            if (idToken) {
              resolve(idToken)
              return
            }

            if (accessToken) {
              resolve(accessToken)
              return
            }

            // 如果只有 openid，需要调用 getUserInfo 获取更多信息
            if (openid) {
              googleService.getUserInfo(
                (userInfoResult) => {
                  const userInfo = userInfoResult.userInfo || userInfoResult.target?.userInfo

                  // 对于 APP 端，将 openid 和用户信息打包发送给后端
                  const appAuthData = JSON.stringify({
                    type: 'app_oauth',
                    openid: openid,
                    unionid: authResult?.unionid,
                    userInfo: userInfo
                  })
                  resolve(appAuthData)
                },
                () => {
                  // 即使获取用户信息失败，也可以用 openid 登录
                  const appAuthData = JSON.stringify({
                    type: 'app_oauth',
                    openid: openid,
                    unionid: authResult?.unionid
                  })
                  resolve(appAuthData)
                }
              )
              return
            }

            reject(new Error('No token received from Google'))
          },
          (error) => {
            reject(new Error(error.message || 'Google login failed'))
          }
        )
      },
      (error) => {
        reject(new Error('OAuth services not available'))
      }
    )
  })
}

/**
 * 执行 Google 登录
 * 根据平台自动选择登录方式
 * @returns Promise<string> 返回 Google ID Token
 */
export function initGoogleAuth(): Promise<string> {
  // #ifdef H5
  return googleLoginH5()
  // #endif

  // #ifdef APP-PLUS
  return googleLoginApp()
  // #endif

  // 其他平台暂不支持
  // #ifdef MP
  return Promise.reject(new Error('Google login not supported on Mini Program'))
  // #endif
}

/**
 * 检查 Google 登录是否可用
 */
export async function isGoogleAuthAvailable(): Promise<boolean> {
  try {
    const config = await fetchOAuthConfig()
    if (!config.google.enabled || !config.google.clientId) {
      return false
    }

    // #ifdef H5
    return true // H5 端始终可用
    // #endif

    // #ifdef APP-PLUS
    // APP 端需要检查是否配置了 Google 登录插件
    return true // 假设已配置
    // #endif

    // #ifdef MP
    return false // 小程序端不支持
    // #endif
  } catch {
    return false
  }
}

/**
 * 同步检查 Google 登录是否可用（需要先调用过 fetchOAuthConfig）
 */
export function isGoogleAuthAvailableSync(): boolean {
  const config = getCachedOAuthConfig()
  if (!config) {
    return false
  }
  return config.google.enabled && !!config.google.clientId
}
