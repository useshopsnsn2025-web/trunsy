// 用户状态管理
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  getToken,
  setToken,
  removeToken,
  getUserInfo,
  setUserInfo,
  removeUserInfo,
  type UserInfo,
} from '@/utils/storage'
import {
  login as apiLogin,
  register as apiRegister,
  logout as apiLogout,
  socialLogin as apiSocialLogin,
  getUserProfile,
  type LoginParams,
  type RegisterParams,
  type SocialLoginParams,
  type UserProfile,
  type LoginResponse,
} from '@/api/user'

export const useUserStore = defineStore('user', () => {
  // 状态
  const token = ref<string | null>(getToken())
  const userInfo = ref<UserInfo | null>(getUserInfo())

  // 计算属性
  const isLoggedIn = computed(() => !!token.value)
  const isSeller = computed(() => userInfo.value?.isSeller ?? false)

  // 登录
  async function login(params: LoginParams) {
    const res = await apiLogin(params)
    const { token: newToken, refreshToken, user } = res.data

    // 保存 token
    token.value = newToken
    setToken(newToken)

    // 保存用户信息
    const info: UserInfo = {
      id: user.id,
      uuid: user.uuid,
      nickname: user.nickname,
      avatar: user.avatar,
      email: user.email,
      phone: user.phone,
      language: user.language,
      currency: user.currency,
      isSeller: user.isSeller,
      createdAt: user.createdAt,
      lastLoginAt: user.lastLoginAt,
    }
    userInfo.value = info
    setUserInfo(info)

    // 触发登录事件，启动心跳
    uni.$emit('userLogin')

    return res
  }

  // 注册
  async function register(params: RegisterParams) {
    const res = await apiRegister(params)
    const { token: newToken, user } = res.data

    // 保存 token
    token.value = newToken
    setToken(newToken)

    // 保存用户信息
    const info: UserInfo = {
      id: user.id,
      uuid: user.uuid,
      nickname: user.nickname,
      avatar: user.avatar,
      email: user.email,
      phone: user.phone,
      language: user.language,
      currency: user.currency,
      isSeller: user.isSeller,
      createdAt: user.createdAt,
      lastLoginAt: user.lastLoginAt,
    }
    userInfo.value = info
    setUserInfo(info)

    // 触发登录事件，启动心跳
    uni.$emit('userLogin')

    return res
  }

  // 社交登录 (Google, Apple 等)
  async function socialLogin(params: SocialLoginParams) {
    const res = await apiSocialLogin(params)
    const { token: newToken, user } = res.data

    // 保存 token
    token.value = newToken
    setToken(newToken)

    // 保存用户信息
    const info: UserInfo = {
      id: user.id,
      uuid: user.uuid,
      nickname: user.nickname,
      avatar: user.avatar,
      email: user.email,
      phone: user.phone,
      language: user.language,
      currency: user.currency,
      isSeller: user.isSeller,
      createdAt: user.createdAt,
      lastLoginAt: user.lastLoginAt,
    }
    userInfo.value = info
    setUserInfo(info)

    // 触发登录事件，启动心跳
    uni.$emit('userLogin')

    return res
  }

  // 退出登录
  async function logout() {
    // 触发退出事件，标记离线并停止心跳
    uni.$emit('userLogout')

    try {
      await apiLogout()
    } catch (e) {
      // 忽略错误
    } finally {
      // 清除本地状态
      token.value = null
      userInfo.value = null
      removeToken()
      removeUserInfo()
    }
  }

  // 获取用户信息
  async function fetchUserInfo() {
    if (!token.value) return null

    const res = await getUserProfile()
    const user = res.data

    const info: UserInfo = {
      id: user.id,
      uuid: user.uuid,
      nickname: user.nickname,
      avatar: user.avatar,
      email: user.email,
      phone: user.phone,
      language: user.language,
      currency: user.currency,
      isSeller: user.isSeller,
      createdAt: user.createdAt,
      lastLoginAt: user.lastLoginAt,
    }
    userInfo.value = info
    setUserInfo(info)

    return info
  }

  // 更新用户信息
  function updateUserInfoLocal(data: Partial<UserInfo>) {
    if (userInfo.value) {
      userInfo.value = { ...userInfo.value, ...data }
      setUserInfo(userInfo.value)
    }
  }

  // 处理登录响应（用于分步注册等场景）
  function handleLoginResponse(data: LoginResponse) {
    const { token: newToken, user } = data

    // 保存 token
    token.value = newToken
    setToken(newToken)

    // 保存用户信息
    const info: UserInfo = {
      id: user.id,
      uuid: user.uuid,
      nickname: user.nickname,
      avatar: user.avatar,
      email: user.email,
      phone: user.phone,
      language: user.language,
      currency: user.currency,
      isSeller: user.isSeller,
      createdAt: user.createdAt,
      lastLoginAt: user.lastLoginAt,
    }
    userInfo.value = info
    setUserInfo(info)

    // 触发登录事件，启动心跳
    uni.$emit('userLogin')
  }

  return {
    token,
    userInfo,
    isLoggedIn,
    isSeller,
    login,
    register,
    socialLogin,
    logout,
    fetchUserInfo,
    updateUserInfoLocal,
    handleLoginResponse,
  }
})
