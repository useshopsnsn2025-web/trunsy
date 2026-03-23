/**
 * OAuth 第三方登录配置
 *
 * 注意：OAuth 配置现在从后台 API 动态获取
 * 在管理后台 -> 系统设置 -> OAuth 设置 中配置
 */

import { getOAuthConfig } from '@/api/user'

// OAuth 配置类型
export interface OAuthConfig {
  google: {
    enabled: boolean
    clientId: string // 通用（向后兼容，优先使用 Web）
    clientIdWeb?: string
    clientIdAndroid?: string
    clientIdIos?: string
  }
  apple: {
    enabled: boolean
    clientId: string
  }
}

// 缓存的 OAuth 配置
let cachedConfig: OAuthConfig | null = null
let configLoading: Promise<OAuthConfig> | null = null

/**
 * 获取 OAuth 配置（带缓存）
 */
export async function fetchOAuthConfig(): Promise<OAuthConfig> {
  // 如果已有缓存，直接返回
  if (cachedConfig) {
    return cachedConfig
  }

  // 如果正在加载，等待加载完成
  if (configLoading) {
    return configLoading
  }

  // 开始加载
  configLoading = (async () => {
    try {
      const res = await getOAuthConfig()
      cachedConfig = res.data
      return cachedConfig
    } catch (error) {
      // 返回默认配置
      return {
        google: { enabled: false, clientId: '' },
        apple: { enabled: false, clientId: '' },
      }
    } finally {
      configLoading = null
    }
  })()

  return configLoading
}

/**
 * 获取缓存的 OAuth 配置（同步）
 * 注意：首次调用前需要先调用 fetchOAuthConfig()
 */
export function getCachedOAuthConfig(): OAuthConfig | null {
  return cachedConfig
}

/**
 * 清除 OAuth 配置缓存
 */
export function clearOAuthConfigCache(): void {
  cachedConfig = null
  configLoading = null
}

// ============ 兼容旧代码的配置（将逐步废弃） ============

// Google OAuth 配置（用于兼容，实际值从 API 获取）
export const GOOGLE_OAUTH_CONFIG = {
  clientId: '',
  scopes: ['email', 'profile'],
}

// Apple OAuth 配置（用于兼容，实际值从 API 获取）
export const APPLE_OAUTH_CONFIG = {
  clientId: '',
  redirectUri: '',
}

/**
 * 检查 Google OAuth 是否已配置
 * @deprecated 请使用 fetchOAuthConfig() 获取配置
 */
export function isGoogleOAuthConfigured(): boolean {
  if (cachedConfig) {
    return cachedConfig.google.enabled && !!cachedConfig.google.clientId
  }
  return false
}

/**
 * 检查 Apple OAuth 是否已配置
 * @deprecated 请使用 fetchOAuthConfig() 获取配置
 */
export function isAppleOAuthConfigured(): boolean {
  if (cachedConfig) {
    return cachedConfig.apple.enabled && !!cachedConfig.apple.clientId
  }
  return false
}
