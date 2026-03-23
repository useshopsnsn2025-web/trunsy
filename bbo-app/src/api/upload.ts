// 统一上传 API
import { upload } from '@/utils/request'

// 上传类型
export type UploadType = 'goods' | 'avatar' | 'chat' | 'credit' | 'common'

/**
 * 上传单张图片
 * @param filePath 本地文件路径
 * @param type 上传类型
 */
export function uploadImage(filePath: string, type: UploadType = 'common') {
  return upload<{ url: string; path: string }>({
    url: '/upload/image',
    filePath,
    formData: { type },
  })
}

/**
 * 上传视频
 * @param filePath 本地文件路径
 * @param type 上传类型
 */
export function uploadVideo(filePath: string, type: UploadType = 'common') {
  return upload<{ url: string; path: string }>({
    url: '/upload/video',
    filePath,
    formData: { type },
  })
}

/**
 * 上传头像
 * @param filePath 本地文件路径
 */
export function uploadAvatar(filePath: string) {
  return uploadImage(filePath, 'avatar')
}

/**
 * 上传商品图片
 * @param filePath 本地文件路径
 */
export function uploadGoodsImage(filePath: string) {
  return uploadImage(filePath, 'goods')
}

/**
 * 上传商品视频
 * @param filePath 本地文件路径
 */
export function uploadGoodsVideo(filePath: string) {
  return uploadVideo(filePath, 'goods')
}

/**
 * 上传聊天图片
 * @param filePath 本地文件路径
 */
export function uploadChatImage(filePath: string) {
  return uploadImage(filePath, 'chat')
}

/**
 * 上传信用申请相关图片（身份证等）
 * @param filePath 本地文件路径
 */
export function uploadCreditImage(filePath: string) {
  return uploadImage(filePath, 'credit')
}
