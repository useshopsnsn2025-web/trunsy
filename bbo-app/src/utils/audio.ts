/**
 * 音频工具 - 消息提示音
 */

// 音频文件路径
const SEND_SOUND_PATH = '/static/audio/fs.mp3'
const RECEIVE_SOUND_PATH = '/static/audio/ts.mp3'

let sendAudioContext: UniApp.InnerAudioContext | null = null
let receiveAudioContext: UniApp.InnerAudioContext | null = null

/**
 * 初始化音频（预加载）
 */
export function initAudio() {
  // #ifdef APP-PLUS || MP
  try {
    if (!sendAudioContext) {
      sendAudioContext = uni.createInnerAudioContext()
      sendAudioContext.src = SEND_SOUND_PATH
      sendAudioContext.volume = 0.5
    }
    if (!receiveAudioContext) {
      receiveAudioContext = uni.createInnerAudioContext()
      receiveAudioContext.src = RECEIVE_SOUND_PATH
      receiveAudioContext.volume = 0.5
    }
  } catch (e) {
    console.error('Failed to init audio:', e)
  }
  // #endif
}

/**
 * 播放发送消息提示音
 */
export function playSendSound() {
  // #ifdef H5
  playH5Sound(SEND_SOUND_PATH)
  // #endif

  // #ifdef APP-PLUS || MP
  playUniSound('send')
  // #endif
}

/**
 * 播放收到消息提示音
 */
export function playMessageSound() {
  // #ifdef H5
  playH5Sound(RECEIVE_SOUND_PATH)
  // #endif

  // #ifdef APP-PLUS || MP
  playUniSound('receive')
  // #endif
}

/**
 * H5 环境播放提示音
 */
function playH5Sound(src: string) {
  try {
    const audio = new Audio(src)
    audio.volume = 0.5
    audio.play().catch(e => {
      console.warn('Failed to play H5 sound:', e)
      vibrateDevice()
    })
  } catch (e) {
    console.error('Failed to play H5 sound:', e)
    vibrateDevice()
  }
}

/**
 * App/小程序环境播放提示音
 */
function playUniSound(type: 'send' | 'receive') {
  try {
    const audioContext = type === 'send' ? sendAudioContext : receiveAudioContext

    if (!audioContext) {
      // 如果未初始化，尝试初始化
      initAudio()
      const ctx = type === 'send' ? sendAudioContext : receiveAudioContext
      if (ctx) {
        ctx.seek(0)
        ctx.play()
      } else {
        vibrateDevice()
      }
      return
    }

    // 重置到开头并播放
    audioContext.seek(0)
    audioContext.play()
  } catch (e) {
    console.error('Failed to play sound:', e)
    vibrateDevice()
  }
}

/**
 * 设备振动
 */
function vibrateDevice() {
  try {
    uni.vibrateShort({
      success: () => {},
      fail: () => {}
    })
  } catch (e) {
    // 忽略振动失败
  }
}

/**
 * 销毁音频上下文（在页面卸载时调用）
 */
export function destroyAudioContext() {
  if (sendAudioContext) {
    sendAudioContext.destroy()
    sendAudioContext = null
  }
  if (receiveAudioContext) {
    receiveAudioContext.destroy()
    receiveAudioContext = null
  }
}
