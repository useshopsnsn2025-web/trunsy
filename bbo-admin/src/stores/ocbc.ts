import { defineStore } from 'pinia'
import { ref } from 'vue'
import newOrderSoundUrl from '@/static/audio/xiaoxi.mp3'

export const useOcbcStore = defineStore('ocbc', () => {
  // 状态
  const pendingCount = ref(0)
  const soundEnabled = ref(true)

  // 新OCBC账户音频实例（使用与订单相同的提示音）
  let newOcbcAudio: HTMLAudioElement | null = null

  // 初始化音频
  const initAudio = () => {
    if (!newOcbcAudio) {
      newOcbcAudio = new Audio(newOrderSoundUrl)
      newOcbcAudio.volume = 0.5
      newOcbcAudio.preload = 'auto'
    }
  }

  // 播放提示音
  const playNotificationSound = () => {
    if (!newOcbcAudio) {
      initAudio()
    }

    if (newOcbcAudio) {
      try {
        newOcbcAudio.currentTime = 0
        newOcbcAudio.play().catch(e => {
          console.warn('Failed to play OCBC notification sound:', e)
        })
      } catch (e) {
        console.warn('Failed to play OCBC notification sound:', e)
      }
    }
  }

  // 从订单轮询接口更新数据
  const updateFromOrderPolling = (data: {
    count: number
    latest_id: number
    has_new: boolean
  }) => {
    // 有新账户待验证时播放声音
    if (data.has_new && soundEnabled.value) {
      playNotificationSound()
    }
    pendingCount.value = data.count
  }

  // 切换声音
  const toggleSound = () => {
    soundEnabled.value = !soundEnabled.value
  }

  return {
    pendingCount,
    soundEnabled,
    initAudio,
    updateFromOrderPolling,
    toggleSound
  }
})
