/**
 * 货币工具函数
 * 处理货币转换和格式化
 *
 * 注意：硬编码数据仅作为 fallback，优先使用从 API 获取的动态数据
 * 动态数据通过 app store 的 availableCurrencies 和 availableCountries 获取
 */

// 汇率数据接口
export interface ExchangeRates {
  baseCurrency: string
  rates: Record<string, number>
}

// 动态货币数据缓存（由 app store 设置）
let dynamicCurrencyDecimals: Record<string, number> = {}
let dynamicCurrencySymbols: Record<string, string> = {}
let dynamicRegionCurrencyMap: Record<string, string> = {}

/**
 * 设置动态货币数据（由 app store 调用）
 */
export function setDynamicCurrencyData(
  currencies: Array<{ code: string; symbol: string; decimals: number }>,
  countries: Array<{ code: string; currencyCode: string }>
) {
  dynamicCurrencyDecimals = {}
  dynamicCurrencySymbols = {}
  dynamicRegionCurrencyMap = {}

  currencies.forEach(c => {
    dynamicCurrencyDecimals[c.code] = c.decimals
    dynamicCurrencySymbols[c.code] = c.symbol
  })

  countries.forEach(c => {
    dynamicRegionCurrencyMap[c.code] = c.currencyCode
  })
}

// ============ Fallback 硬编码数据（当 API 数据不可用时使用）============

// 国家/地区对应的默认货币（fallback）
const REGION_CURRENCY_MAP_FALLBACK: Record<string, string> = {
  US: 'USD',
  UK: 'GBP',
  AU: 'AUD',
  CA: 'CAD',
  NZ: 'NZD',
  IE: 'EUR',
  SG: 'SGD',
  ZA: 'USD',
  IN: 'USD',
  PH: 'USD',
  TW: 'TWD',
  HK: 'HKD',
  MO: 'MOP',
  JP: 'JPY',
}

// 货币符号（fallback）
const CURRENCY_SYMBOLS_FALLBACK: Record<string, string> = {
  USD: '$',
  HKD: 'HK$',
  TWD: 'NT$',
  JPY: '¥',
  EUR: '€',
  GBP: '£',
  AUD: 'A$',
  CAD: 'C$',
  SGD: 'S$',
  NZD: 'NZ$',
  MOP: 'MOP$',
}

// 货币小数位数（fallback）
const CURRENCY_DECIMALS_FALLBACK: Record<string, number> = {
  USD: 2,
  HKD: 2,
  TWD: 0,
  JPY: 0,
  EUR: 2,
  GBP: 2,
  AUD: 2,
  CAD: 2,
  SGD: 2,
  NZD: 2,
  MOP: 2,
}

// ============ 导出的常量（兼容旧代码）============
// @deprecated 使用动态数据或 getCurrencyDecimals/getCurrencySymbol 函数
export const REGION_CURRENCY_MAP = REGION_CURRENCY_MAP_FALLBACK
export const CURRENCY_SYMBOLS = CURRENCY_SYMBOLS_FALLBACK
export const CURRENCY_DECIMALS = CURRENCY_DECIMALS_FALLBACK

/**
 * 获取货币小数位数（优先使用动态数据）
 */
export function getCurrencyDecimals(currency: string): number {
  return dynamicCurrencyDecimals[currency] ?? CURRENCY_DECIMALS_FALLBACK[currency] ?? 2
}

/**
 * 转换金额
 * @param amount 原始金额
 * @param fromCurrency 原始货币
 * @param toCurrency 目标货币
 * @param rates 汇率数据
 * @returns 转换后的金额
 */
export function convertAmount(
  amount: number,
  fromCurrency: string,
  toCurrency: string,
  rates: Record<string, number>
): number {
  if (fromCurrency === toCurrency) {
    return amount
  }

  const fromRate = rates[fromCurrency] || 1
  const toRate = rates[toCurrency] || 1

  // 先转换为基准货币(USD)，再转换为目标货币
  const amountInBase = amount / fromRate
  const convertedAmount = amountInBase * toRate

  return convertedAmount
}

/**
 * 格式化数字，添加千分位分隔符
 * 兼容不支持 Intl 的环境（如某些 Android WebView）
 * @param num 数字
 * @returns 格式化后的字符串
 */
function formatNumberWithCommas(num: number): string {
  // 检查是否支持 Intl
  if (typeof Intl !== 'undefined' && Intl.NumberFormat) {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(num)
  }
  // Fallback: 手动添加千分位
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')
}

/**
 * 格式化货币金额
 * 使用货币符号格式（如 $100、NT$100）
 * 所有金额统一取整，不显示小数
 * @param amount 金额
 * @param currency 货币代码
 * @param locale 语言环境
 * @returns 格式化后的货币字符串
 */
export function formatCurrencyAmount(
  amount: number,
  currency: string,
  locale: string = 'en-US'
): string {
  // 所有货币统一取整，不显示小数
  const roundedAmount = Math.round(amount)
  // 获取货币符号
  const symbol = getCurrencySymbol(currency)

  // 格式化数字（带千分位）
  const formattedNumber = formatNumberWithCommas(roundedAmount)

  // 返回符号 + 金额格式
  return `${symbol}${formattedNumber}`
}

/**
 * 根据地区获取默认货币（优先使用动态数据）
 * @param region 地区代码
 * @returns 货币代码
 */
export function getCurrencyByRegion(region: string): string {
  return dynamicRegionCurrencyMap[region] || REGION_CURRENCY_MAP_FALLBACK[region] || 'USD'
}

/**
 * 获取货币符号（优先使用动态数据）
 * @param currency 货币代码
 * @returns 货币符号
 */
export function getCurrencySymbol(currency: string): string {
  return dynamicCurrencySymbols[currency] || CURRENCY_SYMBOLS_FALLBACK[currency] || currency
}
