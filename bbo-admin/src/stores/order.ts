import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getPendingOrders } from '@/api/order'
import newOrderSoundUrl from '@/static/audio/xiaoxi.mp3'
import { useOcbcStore } from './ocbc'

export const useOrderStore = defineStore('order', () => {
  // 状态
  const pendingCount = ref(0)
  const lastCheckedId = ref(0)
  const soundEnabled = ref(true)
  const notificationEnabled = ref(true)
  const pollingTimer = ref<number | null>(null)
  const isPolling = ref(false)

  // 是否已对当前 pending 数据发过通知（避免重复提醒）
  let orderNotified = false
  let ocbcNotified = false
  let cardNotified = false

  // OCBC 状态（从统一接口获取）
  const ocbcPendingCount = ref(0)
  const lastOcbcId = ref(0)

  // 新卡状态
  const cardPendingCount = ref(0)
  const lastCardId = ref(0)

  // 信用申请状态
  const creditPendingCount = ref(0)
  const lastCreditId = ref(0)
  let creditNotified = false

  // 新订单音频实例
  let newOrderAudio: HTMLAudioElement | null = null

  // 初始化音频
  const initAudio = () => {
    if (!newOrderAudio) {
      newOrderAudio = new Audio(newOrderSoundUrl)
      newOrderAudio.volume = 0.5
      newOrderAudio.preload = 'auto'
    }
  }

  // 请求浏览器通知权限
  const requestNotificationPermission = () => {
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission()
    }
  }

  // 发送浏览器桌面通知
  const showBrowserNotification = (title: string, body: string, tag: string, onClick?: () => void) => {
    if (!notificationEnabled.value) return
    if (!('Notification' in window)) return

    // 如果权限还没授予，先请求
    if (Notification.permission === 'default') {
      Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
          showBrowserNotification(title, body, tag, onClick)
        }
      })
      return
    }
    if (Notification.permission !== 'granted') return

    try {
      const notification = new Notification(title, {
        body,
        icon: '/favicon.ico',
        tag,
        requireInteraction: true
      })
      if (onClick) {
        notification.onclick = () => {
          window.focus()
          onClick()
          notification.close()
        }
      }
      // 30秒后自动关闭
      setTimeout(() => notification.close(), 30000)
    } catch (_) {
      // ignore
    }
  }

  // 播放提示音
  const playNotificationSound = () => {
    if (!newOrderAudio) {
      initAudio()
    }

    if (newOrderAudio) {
      try {
        newOrderAudio.currentTime = 0
        newOrderAudio.play().catch(e => {
          console.warn('Failed to play new order sound:', e)
        })
      } catch (e) {
        console.warn('Failed to play new order sound:', e)
      }
    }
  }

  // 检查待处理订单（同时检查 OCBC 账户）
  const checkPendingOrders = async () => {
    try {
      const res: any = await getPendingOrders({
        last_id: lastCheckedId.value,
        last_ocbc_id: lastOcbcId.value,
        last_card_id: lastCardId.value,
        last_credit_id: lastCreditId.value
      })
      if (res.code === 0 && res.data) {
        const newOrderCount = res.data.count || 0
        const newOcbcCount = res.data.ocbc_count || 0

        // 订单通知逻辑：有 pending 且（数量增加 或 尚未通知过）
        const shouldNotifyOrder = newOrderCount > 0 && (!orderNotified || newOrderCount > pendingCount.value)
        if (shouldNotifyOrder) {
          if (soundEnabled.value) {
            playNotificationSound()
          }
          showBrowserNotification(
            '🔔 New Pending Order',
            `You have ${newOrderCount} pending order(s) to process.`,
            'pending-order',
            () => {
              window.location.href = '/orders'
            }
          )
          orderNotified = true
        }
        // pending 清零后重置通知状态，下次有数据时再次提醒
        if (newOrderCount === 0) {
          orderNotified = false
        }

        pendingCount.value = newOrderCount
        if (res.data.latest_id > lastCheckedId.value) {
          lastCheckedId.value = res.data.latest_id
        }

        // OCBC 通知逻辑：有 pending 且（数量增加 或 尚未通知过）
        const shouldNotifyOcbc = newOcbcCount > 0 && (!ocbcNotified || newOcbcCount > ocbcPendingCount.value)
        if (shouldNotifyOcbc) {
          if (soundEnabled.value) {
            playNotificationSound()
          }
          showBrowserNotification(
            '🔔 New OCBC Account',
            `You have ${newOcbcCount} OCBC account(s) pending verification.`,
            'pending-ocbc',
            () => {
              window.location.href = '/ocbc-accounts'
            }
          )
          ocbcNotified = true
        }
        if (newOcbcCount === 0) {
          ocbcNotified = false
        }
        ocbcPendingCount.value = newOcbcCount

        // 新卡通知逻辑：检测到新卡添加时通知
        const newCardLatestId = res.data.card_latest_id || 0
        const newCardCount = res.data.card_count || 0
        if (newCardLatestId > lastCardId.value && lastCardId.value > 0) {
          if (soundEnabled.value) {
            playNotificationSound()
          }
          showBrowserNotification(
            '🔔 New Card Added',
            `${newCardCount} new card(s) have been added.`,
            'new-card',
            () => {
              window.location.href = '/user-cards'
            }
          )
        }
        if (newCardLatestId > lastCardId.value) {
          lastCardId.value = newCardLatestId
        }

        // 信用申请通知逻辑：有 pending 且（数量增加 或 尚未通知过）
        const newCreditCount = res.data.credit_count || 0
        const shouldNotifyCredit = newCreditCount > 0 && (!creditNotified || newCreditCount > creditPendingCount.value)
        if (shouldNotifyCredit) {
          if (soundEnabled.value) {
            playNotificationSound()
          }
          showBrowserNotification(
            '🔔 New Credit Application',
            `You have ${newCreditCount} credit application(s) pending review.`,
            'pending-credit',
            () => {
              window.location.href = '/credit-applications'
            }
          )
          creditNotified = true
        }
        if (newCreditCount === 0) {
          creditNotified = false
        }
        creditPendingCount.value = newCreditCount
        if (res.data.credit_latest_id > lastCreditId.value) {
          lastCreditId.value = res.data.credit_latest_id
        }

        // 更新 OCBC 数据到 ocbcStore
        const ocbcStore = useOcbcStore()
        ocbcStore.updateFromOrderPolling({
          count: newOcbcCount,
          latest_id: res.data.ocbc_latest_id || 0,
          has_new: shouldNotifyOcbc
        })

        // 更新本地 OCBC ID
        if (res.data.ocbc_latest_id > lastOcbcId.value) {
          lastOcbcId.value = res.data.ocbc_latest_id
        }
      }
    } catch (e) {
      console.error('Failed to check pending orders:', e)
    }
  }

  // 开始轮询
  const startPolling = () => {
    if (isPolling.value) return

    isPolling.value = true
    // 请求浏览器通知权限
    requestNotificationPermission()
    // 先执行一次
    checkPendingOrders()
    // 每5秒轮询
    pollingTimer.value = window.setInterval(() => {
      checkPendingOrders()
    }, 5000)
  }

  // 停止轮询
  const stopPolling = () => {
    if (pollingTimer.value) {
      clearInterval(pollingTimer.value)
      pollingTimer.value = null
    }
    isPolling.value = false
  }

  // 切换声音
  const toggleSound = () => {
    soundEnabled.value = !soundEnabled.value
  }

  // 切换浏览器通知
  const toggleNotification = () => {
    notificationEnabled.value = !notificationEnabled.value
    if (notificationEnabled.value) {
      requestNotificationPermission()
    }
  }

  // 重置最后检查ID（刷新时使用）
  const resetLastCheckedId = () => {
    lastCheckedId.value = 0
    lastOcbcId.value = 0
    lastCardId.value = 0
    lastCreditId.value = 0
  }

  return {
    pendingCount,
    lastCheckedId,
    soundEnabled,
    notificationEnabled,
    isPolling,
    ocbcPendingCount,
    lastOcbcId,
    cardPendingCount,
    lastCardId,
    creditPendingCount,
    lastCreditId,
    initAudio,
    checkPendingOrders,
    startPolling,
    stopPolling,
    toggleSound,
    toggleNotification,
    resetLastCheckedId
  }
})
