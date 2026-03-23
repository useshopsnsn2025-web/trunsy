<template>
  <div class="image-picker-wrapper">
    <!-- 触发器插槽 -->
    <div class="picker-trigger" @click="openDialog">
      <slot>
        <div class="default-trigger">
          <el-image
            v-if="modelValue"
            :src="getDisplayUrl(modelValue)"
            fit="cover"
            class="preview-image"
          />
          <div v-else class="upload-placeholder">
            <el-icon :size="28"><Plus /></el-icon>
            <span class="placeholder-text">选择图片</span>
          </div>
        </div>
      </slot>
    </div>

    <!-- 删除按钮 -->
    <el-button
      v-if="modelValue && showDelete"
      type="danger"
      size="small"
      :icon="Delete"
      circle
      class="delete-btn"
      @click.stop="handleDelete"
    />

    <!-- 图片选择弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      title="选择图片"
      width="900px"
      :append-to-body="true"
      destroy-on-close
    >
      <div class="picker-dialog">
        <!-- 顶部工具栏 -->
        <div class="picker-toolbar">
          <div class="toolbar-left">
            <el-upload
              action="/admin/attachments/upload"
              :headers="uploadHeaders"
              :data="{ group_id: currentGroupId }"
              :show-file-list="false"
              :on-success="handleUploadSuccess"
              :on-error="handleUploadError"
              :before-upload="beforeUpload"
              accept="image/*"
            >
              <el-button type="primary" :icon="Upload" size="small">上传图片</el-button>
            </el-upload>
            <el-button :icon="Link" size="small" @click="showExternalInput = !showExternalInput">
              外部链接
            </el-button>
          </div>
          <div class="toolbar-right">
            <el-input
              v-model="searchKeyword"
              placeholder="搜索"
              clearable
              size="small"
              style="width: 160px"
              @keyup.enter="loadData"
            />
          </div>
        </div>

        <!-- 外部链接输入 -->
        <div class="external-input" v-if="showExternalInput">
          <el-input
            v-model="externalUrl"
            placeholder="请输入图片URL地址"
            size="small"
          >
            <template #append>
              <el-button @click="confirmExternalUrl">确定</el-button>
            </template>
          </el-input>
        </div>

        <div class="picker-content">
          <!-- 左侧分组 -->
          <div class="picker-groups">
            <div
              v-for="group in groups"
              :key="group.id"
              :class="['group-item', { active: currentGroupId === group.id }]"
              @click="selectGroup(group.id)"
            >
              <span class="group-name">{{ group.name }}</span>
              <span class="group-count">{{ group.count }}</span>
            </div>
          </div>

          <!-- 右侧图片列表 -->
          <div class="picker-images" v-loading="loading">
            <div
              v-for="file in fileList"
              :key="file.id"
              :class="['image-item', { selected: selectedUrl === file.url }]"
              @click="selectImage(file)"
            >
              <el-image :src="file.full_url || file.url" fit="cover" />
              <div class="selected-mask" v-if="selectedUrl === file.url">
                <el-icon :size="24"><Check /></el-icon>
              </div>
              <div class="external-tag" v-if="file.is_external">
                <el-tag size="small" type="warning">外链</el-tag>
              </div>
            </div>
            <el-empty v-if="!loading && fileList.length === 0" description="暂无图片" />
          </div>
        </div>

        <!-- 分页 -->
        <div class="picker-pagination">
          <el-pagination
            v-model:current-page="pagination.page"
            v-model:page-size="pagination.pageSize"
            :page-sizes="[20, 40, 60]"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next"
            size="small"
            @size-change="loadData"
            @current-change="loadData"
          />
        </div>
      </div>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :disabled="!selectedUrl" @click="confirmSelect">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Delete, Upload, Link, Check } from '@element-plus/icons-vue'
import type { UploadRawFile } from 'element-plus'
import {
  getAttachmentList,
  getAttachmentGroups,
  addExternalAttachment,
  type Attachment,
  type AttachmentGroup
} from '@/api/attachment'

const props = withDefaults(defineProps<{
  modelValue?: string
  showDelete?: boolean
  width?: string
  height?: string
}>(), {
  modelValue: '',
  showDelete: true,
  width: '100px',
  height: '100px'
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'change', value: string): void
}>()

const dialogVisible = ref(false)
const loading = ref(false)
const fileList = ref<Attachment[]>([])
const groups = ref<AttachmentGroup[]>([])
const currentGroupId = ref<number>(0)
const selectedUrl = ref('')
const searchKeyword = ref('')
const showExternalInput = ref(false)
const externalUrl = ref('')

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 上传请求头
const uploadHeaders = {
  Authorization: `Bearer ${localStorage.getItem('admin_token') || ''}`
}

// 将相对路径转为可显示的 URL（同域下直接使用，跨域时拼接后端域名）
const getDisplayUrl = (url: string) => {
  if (!url) return ''
  // 已经是完整 URL（http/https 或 data:），直接返回
  if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:')) return url
  // 相对路径（如 /storage/xxx），在同域下直接可用
  return url
}

// 打开弹窗
const openDialog = async () => {
  dialogVisible.value = true
  selectedUrl.value = props.modelValue || ''
  await loadGroups()
  await loadData()
}

// 加载分组
const loadGroups = async () => {
  const res: any = await getAttachmentGroups()
  groups.value = res.data
}

// 加载图片列表
const loadData = async () => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.page,
      pageSize: pagination.pageSize,
      type: 'image'
    }
    if (currentGroupId.value > 0) {
      params.group_id = currentGroupId.value
    }
    if (searchKeyword.value) {
      params.keyword = searchKeyword.value
    }
    const res: any = await getAttachmentList(params)
    fileList.value = res.data.list
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

// 选择分组
const selectGroup = (groupId: number) => {
  currentGroupId.value = groupId
  pagination.page = 1
  loadData()
}

// 选择图片
const selectImage = (file: Attachment) => {
  selectedUrl.value = file.url
}

// 确认选择
const confirmSelect = () => {
  emit('update:modelValue', selectedUrl.value)
  emit('change', selectedUrl.value)
  dialogVisible.value = false
}

// 删除图片
const handleDelete = () => {
  emit('update:modelValue', '')
  emit('change', '')
}

// 上传成功
const handleUploadSuccess = (response: any) => {
  if (response.code === 0) {
    ElMessage.success('上传成功')
    // 自动选中刚上传的图片
    selectedUrl.value = response.data.url
    loadData()
    loadGroups()
  } else {
    ElMessage.error(response.msg || '上传失败')
  }
}

// 上传失败
const handleUploadError = () => {
  ElMessage.error('上传失败')
}

// 上传前验证
const beforeUpload = (file: UploadRawFile) => {
  const isImage = file.type.startsWith('image/')
  if (!isImage) {
    ElMessage.error('只能上传图片文件')
    return false
  }
  const isLt5M = file.size / 1024 / 1024 < 5
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB')
    return false
  }
  return true
}

// 确认外部链接
const confirmExternalUrl = async () => {
  if (!externalUrl.value) {
    ElMessage.warning('请输入图片URL')
    return
  }
  try {
    const res: any = await addExternalAttachment({
      url: externalUrl.value,
      group_id: currentGroupId.value > 0 ? currentGroupId.value : undefined
    })
    if (res.code === 0) {
      ElMessage.success('添加成功')
      selectedUrl.value = res.data.url
      externalUrl.value = ''
      showExternalInput.value = false
      loadData()
      loadGroups()
    }
  } catch (e: any) {
    ElMessage.error(e.message || '添加失败')
  }
}
</script>

<style scoped>
.image-picker-wrapper {
  display: inline-block;
  position: relative;
}

.picker-trigger {
  cursor: pointer;
}

.default-trigger {
  width: v-bind(width);
  height: v-bind(height);
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  transition: border-color 0.3s;
  overflow: hidden;
}

.default-trigger:hover {
  border-color: #409eff;
}

.preview-image {
  width: 100%;
  height: 100%;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  color: #8c939d;
}

.placeholder-text {
  font-size: 12px;
}

.delete-btn {
  position: absolute;
  top: -8px;
  right: -8px;
}

.picker-dialog {
  display: flex;
  flex-direction: column;
  height: 500px;
}

.picker-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.toolbar-left {
  display: flex;
  gap: 10px;
}

.external-input {
  margin-bottom: 15px;
}

.picker-content {
  display: flex;
  flex: 1;
  min-height: 0;
  gap: 15px;
}

.picker-groups {
  width: 160px;
  flex-shrink: 0;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  overflow-y: auto;
}

.picker-groups .group-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s;
}

.picker-groups .group-item:last-child {
  border-bottom: none;
}

.picker-groups .group-item:hover {
  background-color: #f5f7fa;
}

.picker-groups .group-item.active {
  background-color: #ecf5ff;
  color: #409eff;
}

.group-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 13px;
}

.group-count {
  color: #909399;
  font-size: 12px;
}

.picker-images {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 10px;
  overflow-y: auto;
  padding: 5px;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  align-content: start;
}

.image-item {
  position: relative;
  aspect-ratio: 1;
  border: 2px solid transparent;
  border-radius: 4px;
  overflow: hidden;
  cursor: pointer;
  transition: border-color 0.2s;
}

.image-item:hover {
  border-color: #409eff;
}

.image-item.selected {
  border-color: #409eff;
}

.image-item :deep(.el-image) {
  width: 100%;
  height: 100%;
}

.selected-mask {
  position: absolute;
  inset: 0;
  background-color: rgba(64, 158, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.external-tag {
  position: absolute;
  top: 2px;
  right: 2px;
}

.picker-pagination {
  margin-top: 15px;
  display: flex;
  justify-content: flex-end;
}
</style>
