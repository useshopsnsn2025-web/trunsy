// 应用状态管理
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { getLocale, setLocale as setI18nLocale } from '@/locale'
import { getStorage, setStorage, STORAGE_KEYS } from '@/utils/storage'
import { APP_CONFIG } from '@/config/app'
import { get } from '@/utils/request'
import { API_PATHS } from '@/config/api'
import {
  convertAmount,
  formatCurrencyAmount,
  getCurrencyByRegion,
  setDynamicCurrencyData,
  type ExchangeRates,
} from '@/utils/currency'

// 系统配置类型
interface SystemConfig {
  appName: string
  appLogo: string
  appVersion: string
  apkDownloadUrl: string
  contactEmail: string
  contactPhone: string
  contactAddress: string
  supportedLocales: string[]
  defaultLocale: string
  supportedCurrencies: string[]
  defaultCurrency: string
  paymentMethods: string[]
  maxImages: number
  maxVideoSize: number
  maxImageSize: number
}

// 语言选项类型
export interface LanguageOption {
  code: string       // 语言代码，如 zh-tw, en-us
  name: string       // 语言名称（后台显示）
  nativeName: string // 本地名称
  flag: string       // 国旗 emoji
  isDefault: boolean // 是否默认
}

// 货币选项类型
export interface CurrencyOption {
  code: string       // 货币代码，如 USD, TWD
  name: string       // 货币名称
  symbol: string     // 货币符号
  decimals: number   // 小数位数
}

// 国家/地区选项类型
export interface CountryOption {
  code: string       // 国家代码，如 US, TW
  name: string       // 国家名称（根据用户语言翻译）
  flag: string       // 国旗代码
  currencyCode: string // 默认货币
  currencySymbol: string // 货币符号
  currencyDecimals: number // 货币小数位数
  locale: string     // 默认语言
}

// 获取默认地区（优先使用服务器配置）
function getDefaultRegion(): string {
  // 1. 用户已保存的地区
  const stored = getStorage(STORAGE_KEYS.REGION)
  if (stored) return stored

  // 2. 服务器配置的默认国家（由 fetchServerDefaultLocale 缓存）
  const serverDefault = uni.getStorageSync('server_default_country')
  if (serverDefault) return serverDefault

  // 3. 本地默认值
  return APP_CONFIG.defaultRegion
}

// 获取默认货币（优先使用服务器配置）
function getDefaultCurrency(regionCode: string): string {
  // 1. 用户已保存的货币
  const stored = getStorage(STORAGE_KEYS.CURRENCY)
  if (stored) return stored

  // 2. 服务器配置的默认货币（由 fetchServerDefaultLocale 缓存）
  const serverDefault = uni.getStorageSync('server_default_currency')
  if (serverDefault) return serverDefault

  // 3. 根据地区推断
  return getCurrencyByRegion(regionCode)
}

export const useAppStore = defineStore('app', () => {
  // 状态 - 使用服务器配置的默认值
  const defaultRegion = getDefaultRegion()
  const locale = ref<string>(getLocale())
  const region = ref<string>(defaultRegion)
  const currency = ref<string>(getDefaultCurrency(defaultRegion))
  const exchangeRates = ref<Record<string, number>>({})
  const baseCurrency = ref<string>('USD')
  const systemInfo = ref<UniApp.GetSystemInfoResult | null>(null)
  const appName = ref<string>('TURNSY')
  const appLogo = ref<string>('')
  const appVersion = ref<string>('1.0.0')
  const apkDownloadUrl = ref<string>('')
  const contactEmail = ref<string>('')
  const contactPhone = ref<string>('')
  const contactAddress = ref<string>('')
  const availableLanguages = ref<LanguageOption[]>([])
  const availableCurrencies = ref<CurrencyOption[]>([])
  const availableCountries = ref<CountryOption[]>([])

  // 计算属性
  const isIOS = computed(() => systemInfo.value?.platform === 'ios')
  const isAndroid = computed(() => systemInfo.value?.platform === 'android')
  const statusBarHeight = computed(() => systemInfo.value?.statusBarHeight || 0)
  const windowHeight = computed(() => systemInfo.value?.windowHeight || 0)

  // 初始化系统信息
  function initSystemInfo() {
    try {
      systemInfo.value = uni.getSystemInfoSync()
    } catch (e) {
      console.error('Failed to get system info:', e)
    }
  }

  // 切换语言
  async function setLocale(newLocale: string) {
    // 动态支持所有语言，不再限制于本地语言文件
    locale.value = newLocale
    await setI18nLocale(newLocale)
    // 重新获取国家列表（因为国家名称需要根据语言翻译）
    fetchAvailableCountries()
  }

  // 切换地区（同时更新货币）
  function setRegion(newRegion: string) {
    region.value = newRegion
    setStorage(STORAGE_KEYS.REGION, newRegion)
    // 根据地区自动设置货币（优先使用 API 数据）
    const country = availableCountries.value.find(c => c.code === newRegion)
    const newCurrency = country?.currencyCode || getCurrencyByRegion(newRegion)
    setCurrency(newCurrency)
  }

  // 切换货币
  function setCurrency(newCurrency: string) {
    currency.value = newCurrency
    setStorage(STORAGE_KEYS.CURRENCY, newCurrency)
  }

  // 获取汇率
  async function fetchExchangeRates() {
    try {
      const res = await get<ExchangeRates>(API_PATHS.system.exchangeRates)
      if (res.code === 0 && res.data) {
        exchangeRates.value = res.data.rates
        baseCurrency.value = res.data.baseCurrency
      }
    } catch (e) {
      console.error('Failed to fetch exchange rates:', e)
    }
  }

  // 获取系统配置
  async function fetchSystemConfig() {
    try {
      const res = await get<SystemConfig>(API_PATHS.system.config)
      if (res.code === 0 && res.data) {
        appName.value = res.data.appName || 'TURNSY'
        appLogo.value = res.data.appLogo || ''
        appVersion.value = res.data.appVersion || '1.0.0'
        apkDownloadUrl.value = res.data.apkDownloadUrl || ''
        contactEmail.value = res.data.contactEmail || ''
        contactPhone.value = res.data.contactPhone || ''
        contactAddress.value = res.data.contactAddress || ''
      }
    } catch (e) {
      console.error('Failed to fetch system config:', e)
    }
  }

  // 获取可用语言列表
  async function fetchAvailableLanguages() {
    try {
      const res = await get<LanguageOption[]>(API_PATHS.system.languages)
      if (res.code === 0 && res.data) {
        // 将后端返回的语言代码转换为前端格式（zh-tw -> zh-TW）
        availableLanguages.value = res.data.map(lang => ({
          ...lang,
          code: normalizeLocaleCode(lang.code),
        }))
      }
    } catch (e) {
      console.error('Failed to fetch available languages:', e)
    }
  }

  // 获取可用货币列表
  async function fetchAvailableCurrencies() {
    try {
      const res = await get<CurrencyOption[]>(API_PATHS.system.currencies)
      if (res.code === 0 && res.data) {
        availableCurrencies.value = res.data
        // 更新动态货币数据
        updateDynamicCurrencyData()
      }
    } catch (e) {
      console.error('Failed to fetch available currencies:', e)
    }
  }

  // 获取可用国家/地区列表
  async function fetchAvailableCountries() {
    try {
      const res = await get<CountryOption[]>(API_PATHS.system.countries)
      if (res.code === 0 && res.data) {
        availableCountries.value = res.data
        // 更新动态货币数据
        updateDynamicCurrencyData()
      }
    } catch (e) {
      console.error('Failed to fetch available countries:', e)
    }
  }

  // 更新动态货币数据到 currency.ts
  function updateDynamicCurrencyData() {
    if (availableCurrencies.value.length > 0 || availableCountries.value.length > 0) {
      setDynamicCurrencyData(availableCurrencies.value, availableCountries.value)
    }
  }

  // 标准化语言代码格式（后端使用 zh-tw，前端使用 zh-TW）
  function normalizeLocaleCode(code: string): string {
    const parts = code.split('-')
    if (parts.length === 2) {
      return `${parts[0].toLowerCase()}-${parts[1].toUpperCase()}`
    }
    return code
  }

  // 获取支持的语言列表（从 API 获取，支持动态添加的语言）
  const supportedLanguages = computed(() => {
    if (availableLanguages.value.length === 0) {
      // 如果还没有从服务器获取语言列表，返回硬编码的默认语言作为 fallback
      return APP_CONFIG.supportedLocales.map(code => ({
        code,
        name: code,
        nativeName: code,
        flag: '',
        isDefault: code === 'en-US',
      }))
    }
    // 返回所有从 API 获取的语言（不再过滤，因为 UI 翻译可以从 API 动态加载）
    return availableLanguages.value
  })

  // 转换并格式化价格（从商品原始货币转换为用户选择的货币）
  function formatPrice(amount: number, originalCurrency: string = 'USD'): string {
    const targetCurrency = currency.value
    // 如果有汇率数据，进行转换
    if (Object.keys(exchangeRates.value).length > 0) {
      const converted = convertAmount(amount, originalCurrency, targetCurrency, exchangeRates.value)
      return formatCurrencyAmount(converted, targetCurrency, locale.value)
    }
    // 没有汇率数据，直接显示原始货币
    return formatCurrencyAmount(amount, originalCurrency, locale.value)
  }

  // 格式化日期
  function formatDate(date: string | Date, options?: Intl.DateTimeFormatOptions): string {
    const d = typeof date === 'string' ? new Date(date) : date
    return d.toLocaleDateString(locale.value, options)
  }

  // 格式化时间
  function formatDateTime(date: string | Date): string {
    const d = typeof date === 'string' ? new Date(date) : date
    return d.toLocaleString(locale.value)
  }

  // 相对时间
  function formatRelativeTime(date: string | Date): string {
    const { t } = useI18n()
    const d = typeof date === 'string' ? new Date(date) : date
    const now = new Date()
    const diff = now.getTime() - d.getTime()
    const seconds = Math.floor(diff / 1000)
    const minutes = Math.floor(seconds / 60)
    const hours = Math.floor(minutes / 60)
    const days = Math.floor(hours / 24)
    const months = Math.floor(days / 30)

    if (seconds < 60) return t('time.justNow')
    if (minutes < 60) {
      const template = t('time.minutesAgo')
      return template.replace('[N]', String(minutes))
    }
    if (hours < 24) {
      const template = t('time.hoursAgo')
      return template.replace('[N]', String(hours))
    }
    if (days < 30) {
      const template = t('time.daysAgo')
      return template.replace('[N]', String(days))
    }
    if (months < 12) {
      const template = t('time.monthsAgo')
      return template.replace('[N]', String(months))
    }
    return formatDate(d)
  }

  return {
    locale,
    region,
    currency,
    exchangeRates,
    baseCurrency,
    systemInfo,
    appName,
    appLogo,
    appVersion,
    apkDownloadUrl,
    contactEmail,
    contactPhone,
    contactAddress,
    availableLanguages,
    availableCurrencies,
    availableCountries,
    supportedLanguages,
    isIOS,
    isAndroid,
    statusBarHeight,
    windowHeight,
    initSystemInfo,
    setLocale,
    setRegion,
    setCurrency,
    fetchExchangeRates,
    fetchSystemConfig,
    fetchAvailableLanguages,
    fetchAvailableCurrencies,
    fetchAvailableCountries,
    formatPrice,
    formatDate,
    formatDateTime,
    formatRelativeTime,
  }
})
