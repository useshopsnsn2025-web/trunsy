<template>
  <div class="app-previews">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>应用预览图管理</span>
          <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
        </div>
      </template>

      <el-alert
        type="info"
        :closable="false"
        style="margin-bottom: 20px;"
      >
        <template #title>
          上传5张应用预览截图，将展示在APP下载页面。建议使用手机竖屏截图，尺寸 <strong>1080 x 1920</strong>（9:16比例），支持 JPG / PNG 格式。
        </template>
      </el-alert>

      <div v-loading="loading" class="previews-grid">
        <div v-for="item in previews" :key="item.index" class="preview-item">
          <div class="preview-header">
            <span class="preview-index">{{ item.index }}</span>
            <span class="preview-name">{{ item.name }}</span>
          </div>

          <div class="preview-upload">
            <div v-if="item.image" class="preview-image-wrapper">
              <el-image
                :src="item.image"
                fit="cover"
                class="preview-image"
                :preview-src-list="[item.image]"
              />
              <div class="preview-actions">
                <el-button type="danger" size="small" circle @click="removeImage(item)">
                  <el-icon><Delete /></el-icon>
                </el-button>
              </div>
            </div>
            <el-upload
              v-else
              class="preview-uploader"
              :action="uploadUrl"
              :headers="uploadHeaders"
              :show-file-list="false"
              accept="image/jpeg,image/png,image/webp"
              :on-success="(res: any) => handleUploadSuccess(res, item)"
              :before-upload="beforeUpload"
            >
              <div class="upload-placeholder">
                <el-icon class="upload-icon"><Plus /></el-icon>
                <span class="upload-text">点击上传</span>
                <span class="upload-hint">1080 x 1920</span>
              </div>
            </el-upload>
          </div>

          <div class="preview-desc">{{ item.description }}</div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Delete, Plus } from '@element-plus/icons-vue'
import request from '@/api/request'

interface PreviewItem {
  index: number
  key: string
  image: string
  name: string
  description: string
}

const loading = ref(false)
const saving = ref(false)
const previews = ref<PreviewItem[]>([])

const uploadUrl = '/admin/attachments/upload'
const uploadHeaders = computed(() => ({
  Authorization: `Bearer ${localStorage.getItem('admin_token')}`
}))

async function loadPreviews() {
  loading.value = true
  try {
    const res: any = await request.get('/configs/app-previews')
    previews.value = res.data || []
  } catch (e) {
    ElMessage.error('加载预览图失败')
  } finally {
    loading.value = false
  }
}

function beforeUpload(file: File) {
  const isImage = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
  if (!isImage) {
    ElMessage.error('只支持 JPG/PNG/WebP 格式')
    return false
  }
  const isLt5M = file.size / 1024 / 1024 < 5
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB')
    return false
  }
  return true
}

function handleUploadSuccess(response: any, item: PreviewItem) {
  if (response.code === 0 && response.data?.url) {
    item.image = response.data.url
    ElMessage.success('上传成功')
  } else {
    ElMessage.error(response.msg || '上传失败')
  }
}

function removeImage(item: PreviewItem) {
  item.image = ''
}

async function handleSave() {
  saving.value = true
  try {
    await request.post('/configs/app-previews', {
      previews: previews.value.map(p => ({
        key: p.key,
        image: p.image,
      }))
    })
    ElMessage.success('保存成功')
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadPreviews()
})
</script>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.previews-grid {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.preview-item {
  width: 180px;
  text-align: center;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}

.preview-index {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #FF6B35;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.preview-name {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.preview-upload {
  width: 180px;
  height: 320px;
  border-radius: 12px;
  overflow: hidden;
  background: #fafafa;
  border: 1px solid #eee;
}

.preview-image-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
}

.preview-image {
  width: 100%;
  height: 100%;
}

.preview-actions {
  position: absolute;
  top: 8px;
  right: 8px;
  opacity: 0;
  transition: opacity 0.2s;
}

.preview-image-wrapper:hover .preview-actions {
  opacity: 1;
}

.preview-uploader {
  width: 100%;
  height: 100%;
}

.preview-uploader :deep(.el-upload) {
  width: 100%;
  height: 100%;
}

.upload-placeholder {
  width: 100%;
  height: 320px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.upload-placeholder:hover {
  background: #f0f0f0;
}

.upload-icon {
  font-size: 32px;
  color: #ccc;
  margin-bottom: 8px;
}

.upload-text {
  font-size: 14px;
  color: #999;
}

.upload-hint {
  font-size: 12px;
  color: #ccc;
  margin-top: 4px;
}

.preview-desc {
  margin-top: 8px;
  font-size: 12px;
  color: #999;
}
</style>
