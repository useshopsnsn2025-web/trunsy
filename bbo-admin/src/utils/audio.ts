/**
 * 消息提示音工具
 * 使用 HTML5 Audio 播放音频文件
 */

// 音频文件路径
import sendSoundUrl from '@/static/audio/fs.mp3'
import receiveSoundUrl from '@/static/audio/ts.mp3'

let sendAudio: HTMLAudioElement | null = null
let receiveAudio: HTMLAudioElement | null = null
let isInitialized = false

/**
 * 初始化音频（需要用户交互后调用）
 * 用于解决浏览器自动播放限制
 */
export function initAudio(): void {
  if (isInitialized) return

  try {
    // 预加载音频
    sendAudio = new Audio(sendSoundUrl)
    sendAudio.volume = 0.5
    sendAudio.preload = 'auto'

    receiveAudio = new Audio(receiveSoundUrl)
    receiveAudio.volume = 0.5
    receiveAudio.preload = 'auto'

    isInitialized = true
  } catch (e) {
    console.warn('Failed to initialize audio:', e)
  }
}

/**
 * 播放发送消息提示音
 */
export function playSendSound(): void {
  if (!isInitialized || !sendAudio) {
    console.warn('Audio not initialized')
    return
  }

  try {
    // 重置到开头以便重复播放
    sendAudio.currentTime = 0
    sendAudio.play().catch(e => {
      console.warn('Failed to play send sound:', e)
    })
  } catch (e) {
    console.warn('Failed to play send sound:', e)
  }
}

/**
 * 播放收到消息提示音
 */
export function playMessageSound(): void {
  if (!isInitialized || !receiveAudio) {
    console.warn('Audio not initialized')
    return
  }

  try {
    // 重置到开头以便重复播放
    receiveAudio.currentTime = 0
    receiveAudio.play().catch(e => {
      console.warn('Failed to play receive sound:', e)
    })
  } catch (e) {
    console.warn('Failed to play receive sound:', e)
  }
}
