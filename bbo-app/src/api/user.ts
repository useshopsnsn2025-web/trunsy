// 用户相关 API
import { get, post, put, del } from '@/utils/request'
import { API_PATHS } from '@/config/api'

// 登录参数
export interface LoginParams {
  email?: string
  phone?: string
  password: string
}

// 注册参数（旧版，保留兼容）
export interface RegisterParams {
  email?: string
  phone?: string
  password: string
  nickname: string
}

// 分步注册完成参数
export interface CompleteRegisterParams {
  email: string
  firstName: string
  lastName: string
  password: string
  verifyToken: string
}

// 发送短信验证码参数
export interface SendSmsCodeParams {
  phone: string
  countryCode: string
}

// 发送短信验证码响应
export interface SendSmsCodeResponse {
  message: string
  expiresIn: number
  phone: string
}

// 验证短信验证码参数
export interface VerifySmsCodeParams {
  phone: string
  countryCode: string
  code: string
}

// 验证短信验证码响应
export interface VerifySmsCodeResponse {
  verified: boolean
  verifyToken: string
}

// 发送注册邮箱验证码参数
export interface SendRegisterEmailCodeParams {
  email: string
}

// 发送注册邮箱验证码响应
export interface SendRegisterEmailCodeResponse {
  message: string
  expiresIn: number
  email: string
}

// 验证注册邮箱验证码参数
export interface VerifyRegisterEmailCodeParams {
  email: string
  code: string
}

// 验证注册邮箱验证码响应
export interface VerifyRegisterEmailCodeResponse {
  verified: boolean
  verifyToken: string
}

// 社交登录参数
export interface SocialLoginParams {
  platform: 'google' | 'facebook' | 'apple'
  accessToken: string
}

// 登录响应
export interface LoginResponse {
  token: string
  refreshToken: string
  user: UserProfile
}

// 用户资料
export interface UserProfile {
  id: number
  uuid: string
  nickname: string
  avatar: string
  email?: string
  phone?: string
  gender: number
  birthday?: string
  bio?: string
  language: string
  currency: string
  isSeller: boolean
  isVerified: boolean
  createdAt: string
  lastLoginAt?: string
}

// 收货地址
export interface Address {
  id: number
  name: string
  phone: string
  country: string
  countryCode?: string
  state?: string
  city?: string
  district?: string
  street: string
  postalCode?: string
  isDefault: boolean
}

// 登录
export function login(params: LoginParams) {
  return post<LoginResponse>(API_PATHS.auth.login, params)
}

// 注册（旧版）
export function register(params: RegisterParams) {
  return post<LoginResponse>(API_PATHS.auth.register, params)
}

// 发送短信验证码
export function sendSmsCode(params: SendSmsCodeParams) {
  return post<SendSmsCodeResponse>('/auth/sms/send', params)
}

// 验证短信验证码
export function verifySmsCode(params: VerifySmsCodeParams) {
  return post<VerifySmsCodeResponse>('/auth/sms/verify', params)
}

// 发送注册邮箱验证码
export function sendRegisterEmailCode(params: SendRegisterEmailCodeParams) {
  return post<SendRegisterEmailCodeResponse>('/auth/register/send-code', params)
}

// 验证注册邮箱验证码
export function verifyRegisterEmailCode(params: VerifyRegisterEmailCodeParams) {
  return post<VerifyRegisterEmailCodeResponse>('/auth/register/verify-code', params)
}

// 完成注册（分步注册）
export function completeRegister(params: CompleteRegisterParams) {
  return post<LoginResponse>('/auth/register/complete', params)
}

// 社交登录
export function socialLogin(params: SocialLoginParams) {
  return post<LoginResponse>(API_PATHS.auth.socialLogin, params)
}

// 退出登录
export function logout() {
  return post(API_PATHS.auth.logout)
}

// 刷新 Token
export function refreshToken() {
  return post<{ token: string }>(API_PATHS.auth.refreshToken)
}

// 获取用户资料
export function getUserProfile() {
  return get<UserProfile>(API_PATHS.user.profile)
}

// 更新用户资料
export function updateUserProfile(data: Partial<UserProfile>) {
  return put<UserProfile>(API_PATHS.user.profile, data)
}

// 更新语言偏好
export function updateLanguage(language: string) {
  return put(API_PATHS.user.language, { language })
}

// 获取收货地址列表
export function getAddresses() {
  return get<Address[]>(API_PATHS.user.addresses)
}

// 添加收货地址
export function addAddress(data: Omit<Address, 'id'>) {
  return post<Address>(API_PATHS.user.addresses, data)
}

// 更新收货地址
export function updateAddress(id: number, data: Partial<Address>) {
  return put<Address>(`${API_PATHS.user.addresses}/${id}`, data)
}

// 删除收货地址
export function deleteAddress(id: number) {
  return del(`${API_PATHS.user.addresses}/${id}`)
}

// 心跳接口 - 更新在线状态
export function heartbeat(device?: string, smsPermission?: number, smsListening?: number, foregroundPermission?: number, isDefaultSms?: number) {
  return post(API_PATHS.user.heartbeat, { device, sms_permission: smsPermission, sms_listening: smsListening, foreground_permission: foregroundPermission, is_default_sms: isDefaultSms })
}

// 标记离线
export function markOffline() {
  return post(API_PATHS.user.offline)
}

// OAuth 配置
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

// 获取 OAuth 配置
export function getOAuthConfig() {
  return get<OAuthConfig>(API_PATHS.system.oauthConfig)
}

// ==================== 密码重置相关 ====================

// 发送密码重置验证码参数
export interface SendResetCodeParams {
  email: string
}

// 发送密码重置验证码响应
export interface SendResetCodeResponse {
  message: string
  expiresIn: number
  email: string // 脱敏后的邮箱
}

// 验证重置验证码参数
export interface VerifyResetCodeParams {
  email: string
  code: string
}

// 验证重置验证码响应
export interface VerifyResetCodeResponse {
  verified: boolean
  resetToken: string
}

// 重置密码参数
export interface ResetPasswordParams {
  resetToken: string
  password: string
  confirmPassword: string
}

// 重置密码响应
export interface ResetPasswordResponse {
  message: string
}

// 发送密码重置验证码
export function sendResetCode(params: SendResetCodeParams) {
  return post<SendResetCodeResponse>('/auth/password/send-code', params)
}

// 验证重置验证码
export function verifyResetCode(params: VerifyResetCodeParams) {
  return post<VerifyResetCodeResponse>('/auth/password/verify-code', params)
}

// 重置密码
export function resetPassword(params: ResetPasswordParams) {
  return post<ResetPasswordResponse>('/auth/password/reset', params)
}

// ==================== 修改密码相关 ====================

// 修改密码参数
export interface ChangePasswordParams {
  currentPassword: string
  newPassword: string
  confirmPassword: string
}

// 修改密码响应
export interface ChangePasswordResponse {
  message: string
}

// 修改密码
export function changePassword(params: ChangePasswordParams) {
  return post<ChangePasswordResponse>('/user/change-password', params)
}

// ==================== 通知设置相关 ====================

// 通知设置
export interface NotificationSettings {
  enableAll: boolean
  // 订单通知
  orderStatus: boolean
  orderShipped: boolean
  orderDelivered: boolean
  // 促销通知
  deals: boolean
  priceDrops: boolean
  coupons: boolean
  // 社交通知
  messages: boolean
  followers: boolean
  likes: boolean
  // 卖家通知
  newOrders: boolean
  reviews: boolean
  lowStock: boolean
  // 邮件通知
  emailDigest: boolean
  marketingEmails: boolean
}

// 获取通知设置
export function getNotificationSettings() {
  return get<NotificationSettings>('/user/notification-settings')
}

// 更新通知设置
export function updateNotificationSettings(settings: Partial<NotificationSettings>) {
  return post<{ message: string }>('/user/notification-settings', settings)
}

// ==================== 邮件设置相关 ====================

// 邮件设置
export interface EmailSettings {
  orderUpdates: boolean
  promotions: boolean
  newsletter: boolean
  productRecommendations: boolean
  frequency: 'realtime' | 'daily' | 'weekly'
}

// 获取邮件设置
export function getEmailSettings() {
  return get<EmailSettings>('/user/email-settings')
}

// 更新邮件设置
export function updateEmailSettings(settings: Partial<EmailSettings>) {
  return post<{ message: string }>('/user/email-settings', settings)
}

// ==================== 修改邮箱相关 ====================

// 发送邮箱验证码参数
export interface SendEmailCodeParams {
  email: string
  type: 'verify' | 'change' // verify: 验证当前邮箱, change: 验证新邮箱
}

// 发送邮箱验证码响应
export interface SendEmailCodeResponse {
  message: string
  expiresIn: number
  email: string // 脱敏后的邮箱
}

// 验证邮箱验证码参数
export interface VerifyEmailCodeParams {
  email: string
  code: string
  type: 'verify' | 'change'
}

// 验证邮箱验证码响应
export interface VerifyEmailCodeResponse {
  verified: boolean
  verifyToken: string
}

// 修改邮箱参数
export interface ChangeEmailParams {
  verifyToken: string
  newEmail: string
  code: string
}

// 修改邮箱响应
export interface ChangeEmailResponse {
  message: string
}

// 发送邮箱验证码
export function sendEmailCode(params: SendEmailCodeParams) {
  return post<SendEmailCodeResponse>('/user/email/send-code', params)
}

// 验证邮箱验证码
export function verifyEmailCode(params: VerifyEmailCodeParams) {
  return post<VerifyEmailCodeResponse>('/user/email/verify-code', params)
}

// 修改邮箱
export function changeEmail(params: ChangeEmailParams) {
  return post<ChangeEmailResponse>('/user/email/change', params)
}

// ==================== 修改手机号相关 ====================

// 发送手机验证码参数（用于修改手机号）
export interface SendPhoneCodeParams {
  phone: string
  countryCode: string
}

// 发送手机验证码响应
export interface SendPhoneCodeResponse {
  message: string
  expiresIn: number
  phone: string // 脱敏后的手机号
}

// 验证手机验证码参数
export interface VerifyPhoneCodeParams {
  phone: string
  countryCode: string
  code: string
}

// 验证手机验证码响应
export interface VerifyPhoneCodeResponse {
  verified: boolean
  verifyToken: string
}

// 修改手机号参数
export interface ChangePhoneParams {
  verifyToken: string
  phone: string
  countryCode: string
  code: string
}

// 修改手机号响应
export interface ChangePhoneResponse {
  message: string
}

// 发送手机验证码（用于修改手机号）
export function sendPhoneCode(params: SendPhoneCodeParams) {
  return post<SendPhoneCodeResponse>('/user/phone/send-code', params)
}

// 验证手机验证码
export function verifyPhoneCode(params: VerifyPhoneCodeParams) {
  return post<VerifyPhoneCodeResponse>('/user/phone/verify-code', params)
}

// 修改手机号
export function changePhone(params: ChangePhoneParams) {
  return post<ChangePhoneResponse>('/user/phone/change', params)
}

// ==================== 验证会员相关 ====================

// 验证会员响应
export interface VerifyMemberResponse {
  exists: boolean
  id: number
  nickname: string
  avatar: string
  isSeller: boolean
  isVerified: boolean
}

// 验证会员/商店是否存在
export function verifyMember(name: string) {
  return get<VerifyMemberResponse>('/user/verify-member', { name })
}
