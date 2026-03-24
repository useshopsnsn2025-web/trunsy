/**
 * Android 前台服务保活模块
 * 使用 leven-call 插件的 startForeground() 实现真正的 Android 前台 Service
 * 真正的前台 Service 能在 APP 进入后台后保持进程存活，防止被系统杀死
 * 仅支持 Android 平台
 */

let isServiceRunning = false

/**
 * 获取前台服务通知的多语言内容
 * 根据安卓系统语言自动匹配
 */
function getNotificationContent(): { title: string; content: string } {
  const systemInfo = uni.getSystemInfoSync()
  const lang = (systemInfo.language || 'en').toLowerCase()

  const i18n: Record<string, string> = {
    'zh': 'TURNSY 全球二手交易平台，为您提供安全可信赖的二手商品买卖服务。',
    'ja': 'TURNSY は安全で信頼できるグローバル中古品取引プラットフォームです。',
    'ko': 'TURNSY는 안전하고 신뢰할 수 있는 글로벌 중고 거래 플랫폼입니다.',
    'id': 'TURNSY, platform jual beli barang bekas global yang aman dan terpercaya.',
    'ms': 'TURNSY, platform jual beli barangan terpakai global yang selamat dan dipercayai.',
    'th': 'TURNSY แพลตฟอร์มซื้อขายสินค้ามือสองระดับโลกที่ปลอดภัยและน่าเชื่อถือ',
    'fr': 'TURNSY, une plateforme mondiale sûre et fiable pour l\'achat et la vente d\'occasion.',
    'de': 'TURNSY, eine sichere und vertrauenswürdige globale Plattform für Gebrauchtwaren.',
    'pt': 'TURNSY, uma plataforma global segura e confiável para compra e venda de usados.',
    'es': 'TURNSY, una plataforma global segura y confiable para compra y venta de segunda mano.',
    'it': 'TURNSY, una piattaforma globale sicura e affidabile per l\'acquisto e la vendita dell\'usato.',
    'ru': 'TURNSY — безопасная и надёжная глобальная платформа для покупки и продажи подержанных товаров.',
    'ar': 'TURNSY، منصة عالمية آمنة وموثوقة لبيع وشراء السلع المستعملة.',
    'hi': 'TURNSY, पुरानी वस्तुओं की खरीद-बिक्री के लिए एक सुरक्षित और विश्वसनीय वैश्विक मंच।',
    'vi': 'TURNSY, nền tảng mua bán đồ cũ toàn cầu an toàn và đáng tin cậy.',
    'tr': 'TURNSY, güvenli ve güvenilir küresel ikinci el alışveriş platformu.',
    'pl': 'TURNSY, bezpieczna i godna zaufania globalna platforma handlu używanymi przedmiotami.',
    'nl': 'TURNSY, een veilig en betrouwbaar wereldwijd platform voor tweedehands handel.',
    'sv': 'TURNSY, en säker och pålitlig global plattform för handel med begagnade varor.',
    'da': 'TURNSY, en sikker og pålidelig global platform for handel med brugte varer.',
    'fi': 'TURNSY, turvallinen ja luotettava maailmanlaajuinen käytettyjen tavaroiden kauppapaikka.',
    'nb': 'TURNSY, en trygg og pålitelig global plattform for kjøp og salg av brukte varer.',
    'no': 'TURNSY, en trygg og pålitelig global plattform for kjøp og salg av brukte varer.',
    'uk': 'TURNSY — безпечна та надійна глобальна платформа для купівлі та продажу вживаних товарів.',
    'el': 'TURNSY, μια ασφαλής και αξιόπιστη παγκόσμια πλατφόρμα αγοραπωλησίας μεταχειρισμένων.',
    'cs': 'TURNSY, bezpečná a důvěryhodná globální platforma pro nákup a prodej použitého zboží.',
    'ro': 'TURNSY, o platformă globală sigură și de încredere pentru cumpărarea și vânzarea de obiecte second-hand.',
    'hu': 'TURNSY, egy biztonságos és megbízható globális platform használt cikkek adásvételéhez.',
    'bn': 'TURNSY, পুরানো পণ্য কেনা-বেচার জন্য একটি নিরাপদ ও বিশ্বস্ত বৈশ্বিক প্ল্যাটফর্ম।',
    'tl': 'TURNSY, isang ligtas at mapagkakatiwalaang global na platform para sa pagbili at pagbebenta ng mga second-hand na gamit.',
    'sw': 'TURNSY, jukwaa salama na la kuaminika la biashara ya bidhaa za mkono wa pili duniani kote.',
    'my': 'TURNSY သည် ကမ္ဘာလုံးဆိုင်ရာ ဒုတိယလက်ကုန်သွယ်မှု ပလက်ဖောင်းဖြစ်ပါသည်။',
    'km': 'TURNSY វេទិកាពាណិជ្ជកម្មទំនិញមួយរដូវជាសកលដែលមានសុវត្ថិភាព និងអាចទុកចិត្តបាន។',
    'en': 'TURNSY, a global secondhand trading platform, offers a safe and trustworthy service.',
  }

  for (const key of Object.keys(i18n)) {
    if (lang.startsWith(key)) return { title: 'TURNSY', content: i18n[key] }
  }
  return { title: 'TURNSY', content: i18n['en'] }
}

/**
 * 获取 leven-call 插件模块
 */
function getCallModule(): any {
  // #ifdef APP-PLUS
  return uni.requireNativePlugin('leven-call-CallModule')
  // #endif
  return null
}

/**
 * 启动真正的 Android 前台服务
 * 使用 leven-call 插件的 startForeground API，在系统级别保活进程
 */
export function startForegroundService(): boolean {
  // #ifdef APP-PLUS
  try {
    const systemInfo = uni.getSystemInfoSync()
    if (systemInfo.platform !== 'android') {
      console.log('[ForegroundService] Only supported on Android')
      return false
    }

    if (isServiceRunning) {
      console.log('[ForegroundService] Already running')
      return true
    }

    const module = getCallModule()
    if (!module) {
      console.error('[ForegroundService] leven-call module not available')
      return false
    }

    const notification = getNotificationContent()
    module.startForeground({
      title: notification.title,
      content: notification.content,
      icon: 'icon',
    }, (res: any) => {
      console.log('[ForegroundService] startForeground result:', JSON.stringify(res))
    })

    // 申请忽略电池优化（加入白名单，进一步防止被杀）
    requestIgnoreBatteryOptimization()

    isServiceRunning = true
    console.log('[ForegroundService] Native foreground service started')
    return true
  } catch (e) {
    console.error('[ForegroundService] Failed to start:', e)
    return false
  }
  // #endif
  return false
}

/**
 * 停止前台服务
 */
export function stopForegroundService(): void {
  // #ifdef APP-PLUS
  try {
    if (!isServiceRunning) return
    isServiceRunning = false
    console.log('[ForegroundService] Stopped')
  } catch (e) {
    console.error('[ForegroundService] Failed to stop:', e)
  }
  // #endif
}

/**
 * 检查前台服务是否在运行
 */
export function isForegroundServiceRunning(): boolean {
  return isServiceRunning
}

/**
 * 请求忽略电池优化（加入电池优化白名单）
 * 这是保活的关键步骤
 */
export function requestIgnoreBatteryOptimization(): void {
  // #ifdef APP-PLUS
  try {
    const main = plus.android.runtimeMainActivity() as any
    const Build = plus.android.importClass('android.os.Build') as any

    if (Build.VERSION.SDK_INT >= 23) {
      const Context = plus.android.importClass('android.content.Context') as any
      const Intent = plus.android.importClass('android.content.Intent') as any
      const Settings = plus.android.importClass('android.provider.Settings') as any
      const Uri = plus.android.importClass('android.net.Uri') as any

      const powerManager = main.getSystemService(Context.POWER_SERVICE)
      const packageName = main.getPackageName()

      if (!powerManager.isIgnoringBatteryOptimizations(packageName)) {
        const intent = new Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS)
        intent.setData(Uri.parse('package:' + packageName))
        main.startActivity(intent)
        console.log('[ForegroundService] Requested battery optimization exemption')
      } else {
        console.log('[ForegroundService] Already ignoring battery optimizations')
      }
    }
  } catch (e) {
    console.warn('[ForegroundService] Battery optimization request failed:', e)
  }
  // #endif
}

/**
 * 检查是否已加入电池优化白名单
 */
export function isIgnoringBatteryOptimizations(): boolean {
  // #ifdef APP-PLUS
  try {
    const main = plus.android.runtimeMainActivity() as any
    const Build = plus.android.importClass('android.os.Build') as any

    if (Build.VERSION.SDK_INT >= 23) {
      const Context = plus.android.importClass('android.content.Context') as any

      const powerManager = main.getSystemService(Context.POWER_SERVICE)
      const packageName = main.getPackageName()
      return powerManager.isIgnoringBatteryOptimizations(packageName)
    }
  } catch (e) {
    console.warn('[ForegroundService] Check battery optimization failed:', e)
  }
  // #endif
  return false
}

/**
 * 检查通知权限是否已开启（同步）
 * 使用 leven-call 插件的 isForegroundPermission 方法
 */
export function checkNotificationPermission(): Promise<boolean> {
  return new Promise((resolve) => {
    // #ifdef APP-PLUS
    try {
      const module = getCallModule()
      if (!module) {
        resolve(false)
        return
      }
      module.isForegroundPermission((res: any) => {
        console.log('[ForegroundService] isForegroundPermission:', JSON.stringify(res))
        resolve(res?.data?.status === true)
      })
    } catch (e) {
      console.warn('[ForegroundService] isForegroundPermission failed:', e)
      resolve(false)
    }
    // #endif
    // #ifndef APP-PLUS
    resolve(false)
    // #endif
  })
}

/**
 * 跳转到系统通知权限设置页面，引导用户手动开启
 * 使用 leven-call 插件的 toForegroundPage 方法
 * 回调返回用户从设置页返回后的最新权限状态
 */
export function requestNotificationPermission(): Promise<boolean> {
  return new Promise((resolve) => {
    // #ifdef APP-PLUS
    try {
      const module = getCallModule()
      if (!module) {
        resolve(false)
        return
      }
      module.toForegroundPage((res: any) => {
        console.log('[ForegroundService] toForegroundPage result:', JSON.stringify(res))
        resolve(res?.data?.status === true)
      })
    } catch (e) {
      console.warn('[ForegroundService] toForegroundPage failed:', e)
      resolve(false)
    }
    // #endif
    // #ifndef APP-PLUS
    resolve(false)
    // #endif
  })
}
