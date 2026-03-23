<template>
  <div class="notification-template-list">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>站内信模板管理</span>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="type" label="模板类型" width="200">
          <template #default="{ row }">
            <el-tag type="info">{{ row.type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="type_name" label="模板名称" min-width="150" />
        <el-table-column prop="category" label="分类" width="120">
          <template #default="{ row }">
            <el-tag :type="getCategoryType(row.category)" size="small">
              {{ getCategoryName(row.category) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="translation_count" label="语言数量" width="100" align="center">
          <template #default="{ row }">
            <el-tag type="success" size="small">{{ row.translation_count }} / 3</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="英文标题" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.locales?.['en-us']?.title || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              :model-value="row.status === 1"
              @change="handleToggleStatus(row)"
              :loading="row.toggling"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="success" link @click="handlePreview(row)">预览</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 预览对话框 -->
    <el-dialog v-model="previewDialogVisible" title="站内信预览" width="600px">
      <div class="preview-toolbar">
        <span>预览语言：</span>
        <el-select v-model="previewLocale" placeholder="选择语言" @change="loadPreview">
          <el-option
            v-for="locale in availableLocales"
            :key="locale.code"
            :label="locale.name"
            :value="locale.code"
          />
        </el-select>
      </div>
      <div class="preview-content" v-loading="previewLoading">
        <div class="preview-card" v-if="previewData.title">
          <div class="preview-title">{{ previewData.title }}</div>
          <div class="preview-body">{{ previewData.content }}</div>
        </div>
        <el-empty v-else description="暂无预览内容" />
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  getNotificationTemplateList,
  toggleNotificationTemplateStatus,
  previewNotificationTemplate,
  type NotificationTemplate,
  type LocaleInfo
} from '@/api/notificationTemplate'

const router = useRouter()

// 列表数据
const list = ref<NotificationTemplate[]>([])
const loading = ref(false)

// 可用语言
const availableLocales = ref<LocaleInfo[]>([
  { code: 'en-us', name: 'English' },
  { code: 'zh-tw', name: '繁體中文' },
  { code: 'ja-jp', name: '日本語' },
])

// 预览相关
const previewDialogVisible = ref(false)
const previewLoading = ref(false)
const previewLocale = ref('en-us')
const previewData = ref<{ title: string; content: string }>({ title: '', content: '' })
const currentTemplateType = ref<string | null>(null)

// 获取列表
const fetchList = async () => {
  loading.value = true
  try {
    const { data } = await getNotificationTemplateList()
    list.value = data || []
  } catch (error) {
    console.error('Failed to fetch notification templates:', error)
  } finally {
    loading.value = false
  }
}

// 获取分类名称
const getCategoryName = (category: string): string => {
  const names: Record<string, string> = {
    order: '订单',
    important: '重要',
    account: '账户',
    promotion: '促销',
  }
  return names[category] || category
}

// 获取分类标签类型
const getCategoryType = (category: string): string => {
  const types: Record<string, string> = {
    order: 'primary',
    important: 'danger',
    account: 'warning',
    promotion: 'success',
  }
  return types[category] || 'info'
}

// 编辑模板
const handleEdit = (row: NotificationTemplate) => {
  router.push(`/system/notification-template/${row.type}`)
}

// 预览
const handlePreview = (row: NotificationTemplate) => {
  currentTemplateType.value = row.type
  previewLocale.value = 'en-us'
  previewDialogVisible.value = true
  loadPreview()
}

// 加载预览内容
const loadPreview = async () => {
  if (!currentTemplateType.value) return

  previewLoading.value = true
  previewData.value = { title: '', content: '' }

  try {
    const { data } = await previewNotificationTemplate(currentTemplateType.value, previewLocale.value)
    previewData.value = data
  } catch (error) {
    console.error('Failed to load preview:', error)
    ElMessage.error('加载预览失败')
  } finally {
    previewLoading.value = false
  }
}

// 切换状态
const handleToggleStatus = async (row: any) => {
  row.toggling = true
  try {
    const newStatus = row.status === 1 ? 0 : 1
    await toggleNotificationTemplateStatus(row.type, newStatus)
    row.status = newStatus
    ElMessage.success('状态更新成功')
  } catch (error) {
    console.error('Failed to toggle status:', error)
  } finally {
    row.toggling = false
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style scoped>
.notification-template-list .card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.notification-template-list .preview-toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.notification-template-list .preview-content {
  min-height: 200px;
}

.notification-template-list .preview-card {
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  overflow: hidden;
}

.notification-template-list .preview-title {
  padding: 12px 16px;
  background: #f5f7fa;
  font-weight: 600;
  font-size: 15px;
  border-bottom: 1px solid #e4e7ed;
}

.notification-template-list .preview-body {
  padding: 16px;
  line-height: 1.6;
  color: #606266;
  white-space: pre-wrap;
}
</style>
