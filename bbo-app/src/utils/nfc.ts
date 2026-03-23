/**
 * NFC 银行卡读取工具模块
 * 支持 Android 平台通过 NFC 读取 EMV 银行卡信息
 *
 * 注意：
 * - 仅支持 Android App 环境
 * - iOS 的 Core NFC 对银行卡读取有限制
 * - H5/小程序不支持 NFC
 * - 只能读取卡号和有效期，CVV 无法通过 NFC 读取
 */

// 银行卡信息接口
export interface BankCardInfo {
  cardNumber: string    // 卡号
  expiry: string        // 有效期 (YYMM 格式)
  cardholderName?: string // 持卡人姓名（部分卡支持）
}

// NFC 读取结果
export interface NfcReadResult {
  success: boolean
  data?: BankCardInfo
  error?: string
}

// EMV 应用标识符 (AID)
const EMV_AIDS = {
  VISA: 'A0000000031010',
  MASTERCARD: 'A0000000041010',
  // 银联
  UNIONPAY_DEBIT: 'A000000333010101',
  UNIONPAY_CREDIT: 'A000000333010102',
  UNIONPAY_QUASI_CREDIT: 'A000000333010103',
  UNIONPAY_ELECTRONIC: 'A000000333010106',
  UNIONPAY_INTL: 'A0000003330101',
  // 其他卡组织
  AMEX: 'A000000025010104',
  JCB: 'A0000000651010',
  DISCOVER: 'A0000001523010',
  RUPAY: 'A0000005241010',
}

// EMV Tag 定义
const EMV_TAGS = {
  PAN: '5A',           // Primary Account Number (卡号)
  EXPIRY: '5F24',      // Application Expiration Date (有效期)
  CARDHOLDER: '5F20',  // Cardholder Name (持卡人姓名)
  TRACK2: '57',        // Track 2 Equivalent Data
}

/**
 * 检测设备是否支持 NFC
 */
export function isNfcSupported(): boolean {
  // #ifdef APP-PLUS
  try {
    const systemInfo = uni.getSystemInfoSync()
    if (systemInfo.platform === 'android') {
      const main = plus.android.runtimeMainActivity()
      const NfcAdapter = plus.android.importClass('android.nfc.NfcAdapter')
      const adapter = NfcAdapter.getDefaultAdapter(main)
      return adapter !== null
    }
  } catch {
    // 忽略错误
  }
  // #endif
  return false
}

/**
 * 检测 NFC 是否已开启
 */
export function isNfcEnabled(): boolean {
  // #ifdef APP-PLUS
  try {
    const systemInfo = uni.getSystemInfoSync()
    if (systemInfo.platform === 'android') {
      const main = plus.android.runtimeMainActivity()
      const NfcAdapter = plus.android.importClass('android.nfc.NfcAdapter')
      const adapter = NfcAdapter.getDefaultAdapter(main)
      return adapter?.isEnabled() ?? false
    }
  } catch {
    // 忽略错误
  }
  // #endif
  return false
}

/**
 * 打开 NFC 设置页面
 */
export function openNfcSettings(): void {
  // #ifdef APP-PLUS
  try {
    const systemInfo = uni.getSystemInfoSync()
    if (systemInfo.platform === 'android') {
      const Intent = plus.android.importClass('android.content.Intent')
      const Settings = plus.android.importClass('android.provider.Settings')
      const intent = new Intent(Settings.ACTION_NFC_SETTINGS)
      const main = plus.android.runtimeMainActivity()
      main.startActivity(intent)
    }
  } catch {
    // 忽略错误
  }
  // #endif
}

/**
 * 将十六进制字符串转换为字节数组
 */
function hexToBytes(hex: string): number[] {
  const bytes: number[] = []
  for (let i = 0; i < hex.length; i += 2) {
    bytes.push(parseInt(hex.substr(i, 2), 16))
  }
  return bytes
}

/**
 * 将字节数组转换为十六进制字符串
 */
function bytesToHex(bytes: number[] | any): string {
  let hex = ''
  for (let i = 0; i < bytes.length; i++) {
    const b = bytes[i] & 0xFF
    hex += b.toString(16).padStart(2, '0').toUpperCase()
  }
  return hex
}

/**
 * 解析 TLV 数据
 */
function parseTlv(data: string): Map<string, string> {
  const result = new Map<string, string>()
  let i = 0

  while (i < data.length) {
    // 读取 Tag
    let tag = data.substr(i, 2)
    i += 2

    // 检查是否为两字节 Tag
    if ((parseInt(tag, 16) & 0x1F) === 0x1F) {
      tag += data.substr(i, 2)
      i += 2
    }

    if (i >= data.length) break

    // 读取长度
    let length = parseInt(data.substr(i, 2), 16)
    i += 2

    // 检查是否为多字节长度
    if (length > 127) {
      const numBytes = length & 0x7F
      length = parseInt(data.substr(i, numBytes * 2), 16)
      i += numBytes * 2
    }

    if (i + length * 2 > data.length) break

    // 读取值
    const value = data.substr(i, length * 2)
    i += length * 2

    result.set(tag.toUpperCase(), value)

    // 递归解析嵌套的 TLV（针对构造型 Tag）
    const tagByte = parseInt(tag.substr(0, 2), 16)
    if ((tagByte & 0x20) !== 0) {
      const nested = parseTlv(value)
      nested.forEach((v, k) => result.set(k, v))
    }
  }

  return result
}

/**
 * 从 TLV 数据中提取卡片信息
 */
function extractCardInfo(tlvData: Map<string, string>): BankCardInfo | null {
  let cardNumber = ''
  let expiry = ''
  let cardholderName = ''

  // 尝试从 Tag 5A (PAN) 获取卡号
  const pan = tlvData.get(EMV_TAGS.PAN)
  if (pan) {
    cardNumber = pan.replace(/F+$/i, '')
  }

  // 如果没有 Tag 5A，尝试从 Track 2 获取
  if (!cardNumber) {
    const track2 = tlvData.get(EMV_TAGS.TRACK2)
    if (track2) {
      const separatorIndex = track2.indexOf('D')
      if (separatorIndex > 0) {
        cardNumber = track2.substring(0, separatorIndex).replace(/F+$/i, '')
        expiry = track2.substring(separatorIndex + 1, separatorIndex + 5)
      }
    }
  }

  // 获取有效期 Tag 5F24
  if (!expiry) {
    const expiryTag = tlvData.get(EMV_TAGS.EXPIRY)
    if (expiryTag) {
      expiry = expiryTag.substring(0, 4)
    }
  }

  // 获取持卡人姓名 Tag 5F20
  const name = tlvData.get(EMV_TAGS.CARDHOLDER)
  if (name) {
    let decoded = ''
    for (let i = 0; i < name.length; i += 2) {
      const charCode = parseInt(name.substr(i, 2), 16)
      if (charCode >= 32 && charCode <= 126) {
        decoded += String.fromCharCode(charCode)
      }
    }
    cardholderName = decoded.trim()
  }

  if (cardNumber) {
    return {
      cardNumber,
      expiry,
      cardholderName: cardholderName || undefined
    }
  }

  return null
}

/**
 * 发送 APDU 命令并获取响应
 */
function transceive(isoDep: any, command: number[]): number[] {
  // #ifdef APP-PLUS
  try {
    const byteArray = plus.android.invoke(isoDep, 'transceive', command)
    return Array.from(byteArray as any)
  } catch {
    throw new Error('Transceive failed')
  }
  // #endif
  return []
}

/**
 * 选择 PPSE 应用
 */
function selectPpse(isoDep: any): string | null {
  const selectPpseCmd = [
    0x00, 0xA4, 0x04, 0x00,
    0x0E,
    0x32, 0x50, 0x41, 0x59, 0x2E, 0x53, 0x59, 0x53, // "2PAY.SYS"
    0x2E, 0x44, 0x44, 0x46, 0x30, 0x31, // ".DDF01"
    0x00
  ]

  try {
    const response = transceive(isoDep, selectPpseCmd)
    const hex = bytesToHex(response)
    const sw = hex.length >= 4 ? hex.substring(hex.length - 4) : hex

    // 如果 CLA 不支持，尝试其他 CLA 值
    if (sw === '6E00') {
      const ppseData = [0x32, 0x50, 0x41, 0x59, 0x2E, 0x53, 0x59, 0x53, 0x2E, 0x44, 0x44, 0x46, 0x30, 0x31]
      for (const cla of [0x80, 0x84]) {
        try {
          const cmd = [cla, 0xA4, 0x04, 0x00, ppseData.length, ...ppseData, 0x00]
          const resp = transceive(isoDep, cmd)
          const h = bytesToHex(resp)
          if (h.endsWith('9000') && h.length > 4) {
            return h.substring(0, h.length - 4)
          }
        } catch {
          // 继续尝试
        }
      }
    }

    if (hex.endsWith('9000') && hex.length > 4) {
      return hex.substring(0, hex.length - 4)
    }
  } catch {
    // 忽略错误
  }

  return null
}

/**
 * 选择支付应用 (通过 AID)
 */
function selectApplication(isoDep: any, aid: string, partialMatch: boolean = false): string | null {
  const aidBytes = hexToBytes(aid)
  const p2 = partialMatch ? 0x02 : 0x00

  for (const cla of [0x00, 0x80, 0x84]) {
    try {
      const selectCmd = [
        cla, 0xA4, 0x04, p2,
        aidBytes.length,
        ...aidBytes,
        0x00
      ]

      const response = transceive(isoDep, selectCmd)
      const hex = bytesToHex(response)
      const sw = hex.length >= 4 ? hex.substring(hex.length - 4) : hex

      if (sw === '6E00') continue

      if ((hex.endsWith('9000') || hex.endsWith('6283')) && hex.length > 4) {
        return hex.substring(0, hex.length - 4)
      }

      return null
    } catch {
      // 继续尝试下一个 CLA
    }
  }

  return null
}

/**
 * 获取处理选项 (GPO)
 */
function getProcessingOptions(isoDep: any): string | null {
  const gpoCmd = [
    0x80, 0xA8, 0x00, 0x00,
    0x02,
    0x83, 0x00,
    0x00
  ]

  try {
    const response = transceive(isoDep, gpoCmd)
    const hex = bytesToHex(response)

    if (hex.endsWith('9000') && hex.length > 4) {
      return hex.substring(0, hex.length - 4)
    }
  } catch {
    // 忽略错误
  }

  return null
}

/**
 * 读取记录
 */
function readRecord(isoDep: any, sfi: number, record: number): string | null {
  const p2 = (sfi << 3) | 0x04
  const readCmd = [0x00, 0xB2, record, p2, 0x00]

  try {
    const response = transceive(isoDep, readCmd)
    const hex = bytesToHex(response)

    if (hex.endsWith('9000') && hex.length > 4) {
      return hex.substring(0, hex.length - 4)
    }
  } catch {
    // 忽略错误
  }

  return null
}

/**
 * GET DATA 命令获取特定数据
 */
function getData(isoDep: any, tag: number): string | null {
  const p1 = (tag >> 8) & 0xFF
  const p2 = tag & 0xFF
  const getDataCmd = [0x80, 0xCA, p1, p2, 0x00]

  try {
    const response = transceive(isoDep, getDataCmd)
    const hex = bytesToHex(response)

    if (hex.endsWith('9000') && hex.length > 4) {
      return hex.substring(0, hex.length - 4)
    }
  } catch {
    // 忽略错误
  }

  return null
}

/**
 * NFC 读卡监听器
 */
let nfcCallback: ((result: NfcReadResult) => void) | null = null
let nfcAdapter: any = null
let pendingIntent: any = null

/**
 * 开始 NFC 读卡
 */
export function startNfcReading(callback: (result: NfcReadResult) => void): void {
  // #ifdef APP-PLUS
  try {
    const systemInfo = uni.getSystemInfoSync()
    if (systemInfo.platform !== 'android') {
      callback({ success: false, error: 'NFC only supported on Android' })
      return
    }

    nfcCallback = callback

    const main = plus.android.runtimeMainActivity()
    const NfcAdapter = plus.android.importClass('android.nfc.NfcAdapter')
    const PendingIntent = plus.android.importClass('android.app.PendingIntent')
    const Intent = plus.android.importClass('android.content.Intent')
    const IntentFilter = plus.android.importClass('android.content.IntentFilter')

    nfcAdapter = NfcAdapter.getDefaultAdapter(main)

    if (!nfcAdapter) {
      callback({ success: false, error: 'NFC not supported' })
      return
    }

    if (!nfcAdapter.isEnabled()) {
      callback({ success: false, error: 'NFC is disabled' })
      return
    }

    const intent = new Intent(main, main.getClass())
    intent.addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP)
    pendingIntent = PendingIntent.getActivity(main, 0, intent, PendingIntent.FLAG_MUTABLE)

    const techFilter = new IntentFilter(NfcAdapter.ACTION_TECH_DISCOVERED)
    const techList = [['android.nfc.tech.IsoDep']]

    nfcAdapter.enableForegroundDispatch(main, pendingIntent, [techFilter], techList)
    plus.globalEvent.addEventListener('newintent', handleNfcIntent)

  } catch (e) {
    callback({ success: false, error: String(e) })
  }
  // #endif
}

/**
 * 停止 NFC 读卡
 */
export function stopNfcReading(): void {
  // #ifdef APP-PLUS
  try {
    if (nfcAdapter) {
      const main = plus.android.runtimeMainActivity()
      nfcAdapter.disableForegroundDispatch(main)
    }

    plus.globalEvent.removeEventListener('newintent', handleNfcIntent)

    nfcCallback = null
    nfcAdapter = null
    pendingIntent = null
  } catch {
    // 忽略错误
  }
  // #endif
}

/**
 * 处理 NFC Intent
 */
function handleNfcIntent(): void {
  // #ifdef APP-PLUS
  try {
    const main = plus.android.runtimeMainActivity()
    const intent = main.getIntent()
    const NfcAdapter = plus.android.importClass('android.nfc.NfcAdapter')

    const action = intent.getAction()

    if (action === NfcAdapter.ACTION_TECH_DISCOVERED ||
        action === NfcAdapter.ACTION_TAG_DISCOVERED ||
        action === NfcAdapter.ACTION_NDEF_DISCOVERED) {

      const tag = intent.getParcelableExtra(NfcAdapter.EXTRA_TAG)

      if (tag) {
        processNfcTag(tag)
      }
    }
  } catch (e) {
    if (nfcCallback) {
      nfcCallback({ success: false, error: String(e) })
    }
  }
  // #endif
}

/**
 * 处理 NFC Tag
 */
function processNfcTag(tag: any): void {
  // #ifdef APP-PLUS
  try {
    const IsoDep = plus.android.importClass('android.nfc.tech.IsoDep')
    const isoDep = IsoDep.get(tag)

    if (!isoDep) {
      if (nfcCallback) {
        nfcCallback({ success: false, error: 'Not a payment card' })
      }
      return
    }

    isoDep.connect()
    isoDep.setTimeout(5000)

    let cardInfo: BankCardInfo | null = null
    const allTlvData = new Map<string, string>()

    // 1. 选择 PPSE
    const ppseResponse = selectPpse(isoDep)
    if (ppseResponse) {
      const ppseTlv = parseTlv(ppseResponse)
      ppseTlv.forEach((v, k) => allTlvData.set(k, v))
    }

    // 2. 尝试选择各种支付应用
    const aids = [
      { name: 'UNIONPAY_CREDIT', aid: EMV_AIDS.UNIONPAY_CREDIT },
      { name: 'UNIONPAY_DEBIT', aid: EMV_AIDS.UNIONPAY_DEBIT },
      { name: 'UNIONPAY_INTL', aid: EMV_AIDS.UNIONPAY_INTL },
      { name: 'UNIONPAY_QUASI', aid: EMV_AIDS.UNIONPAY_QUASI_CREDIT },
      { name: 'UNIONPAY_ECASH', aid: EMV_AIDS.UNIONPAY_ELECTRONIC },
      { name: 'VISA', aid: EMV_AIDS.VISA },
      { name: 'MASTERCARD', aid: EMV_AIDS.MASTERCARD },
      { name: 'AMEX', aid: EMV_AIDS.AMEX },
      { name: 'JCB', aid: EMV_AIDS.JCB },
      { name: 'DISCOVER', aid: EMV_AIDS.DISCOVER },
      { name: 'RUPAY', aid: EMV_AIDS.RUPAY }
    ]

    for (const { aid } of aids) {
      try {
        let appResponse = selectApplication(isoDep, aid, false)
        if (!appResponse) {
          appResponse = selectApplication(isoDep, aid, true)
        }

        if (appResponse) {
          const appTlv = parseTlv(appResponse)
          appTlv.forEach((v, k) => allTlvData.set(k, v))

          // 获取处理选项
          const gpoResponse = getProcessingOptions(isoDep)
          if (gpoResponse) {
            const gpoTlv = parseTlv(gpoResponse)
            gpoTlv.forEach((v, k) => allTlvData.set(k, v))
          }

          // 读取记录
          for (let sfi = 1; sfi <= 4; sfi++) {
            for (let rec = 1; rec <= 10; rec++) {
              try {
                const recordData = readRecord(isoDep, sfi, rec)
                if (recordData) {
                  const recordTlv = parseTlv(recordData)
                  recordTlv.forEach((v, k) => allTlvData.set(k, v))
                }
              } catch {
                // 忽略
              }
            }
          }

          // GET DATA 获取额外数据
          const getDataTags = [0x9F36, 0x9F17, 0x9F13, 0x5A, 0x57]
          for (const t of getDataTags) {
            try {
              const tagData = getData(isoDep, t)
              if (tagData) {
                const tagHex = t.toString(16).toUpperCase().padStart(4, '0')
                if (tagHex.startsWith('00')) {
                  allTlvData.set(tagHex.substring(2), tagData)
                } else {
                  allTlvData.set(tagHex, tagData)
                }
                try {
                  const tlv = parseTlv(tagData)
                  tlv.forEach((v, k) => allTlvData.set(k, v))
                } catch {
                  // 忽略
                }
              }
            } catch {
              // 忽略
            }
          }

          cardInfo = extractCardInfo(allTlvData)
          if (cardInfo?.cardNumber) {
            break
          }
        }
      } catch {
        // 继续尝试下一个 AID
      }
    }

    isoDep.close()

    if (cardInfo) {
      if (nfcCallback) {
        nfcCallback({ success: true, data: cardInfo })
      }
    } else {
      if (nfcCallback) {
        nfcCallback({ success: false, error: 'Could not read card data' })
      }
    }

  } catch (e) {
    if (nfcCallback) {
      nfcCallback({ success: false, error: String(e) })
    }
  }
  // #endif
}

/**
 * 一次性读取银行卡 (Promise 方式)
 * @param timeout 超时时间（毫秒），默认 30000，传入 0 表示无超时（持续监听）
 */
export function readBankCard(timeout: number = 30000): Promise<NfcReadResult> {
  return new Promise((resolve) => {
    let timeoutId: ReturnType<typeof setTimeout> | null = null

    const cleanup = () => {
      if (timeoutId) {
        clearTimeout(timeoutId)
        timeoutId = null
      }
      stopNfcReading()
    }

    if (timeout > 0) {
      timeoutId = setTimeout(() => {
        cleanup()
        resolve({ success: false, error: 'Timeout' })
      }, timeout)
    }

    startNfcReading((result) => {
      cleanup()
      resolve(result)
    })
  })
}

/**
 * 格式化卡号 (每4位一组)
 */
export function formatCardNumber(cardNumber: string): string {
  const clean = cardNumber.replace(/\D/g, '')
  const groups = []
  for (let i = 0; i < clean.length; i += 4) {
    groups.push(clean.substr(i, 4))
  }
  return groups.join(' ')
}

/**
 * 格式化有效期 (YYMM -> MM/YY)
 */
export function formatExpiry(expiry: string): string {
  if (expiry.length >= 4) {
    const yy = expiry.substring(0, 2)
    const mm = expiry.substring(2, 4)
    return `${mm}/${yy}`
  }
  return expiry
}
