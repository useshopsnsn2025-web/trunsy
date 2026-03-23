<template>
  <div class="notification-template-edit">
    <!-- 头部 -->
    <div class="page-header">
      <el-button @click="goBack" :icon="ArrowLeft">返回</el-button>
      <span class="page-title">编辑站内信模板</span>
    </div>

    <el-row :gutter="20" v-loading="loading">
      <!-- 左侧：模板信息和编辑 -->
      <el-col :span="16">
        <!-- 基本信息 -->
        <el-card shadow="never" class="info-card">
          <template #header>
            <span>模板信息</span>
          </template>
          <el-descriptions :column="2" border>
            <el-descriptions-item label="模板类型">
              <el-tag type="info">{{ templateDetail?.type }}</el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="模板名称">
              {{ templateDetail?.type_name }}
            </el-descriptions-item>
            <el-descriptions-item label="分类">
              <el-tag :type="getCategoryType(templateDetail?.category)">
                {{ getCategoryName(templateDetail?.category) }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="渠道">
              <el-tag>站内消息</el-tag>
            </el-descriptions-item>
          </el-descriptions>
        </el-card>

        <!-- 多语言编辑 -->
        <el-card shadow="never" class="edit-card">
          <template #header>
            <div class="card-header">
              <span>内容编辑</span>
              <el-button
                type="primary"
                :loading="saving"
                @click="saveTranslation"
                :disabled="!currentTranslation.title"
              >
                保存
              </el-button>
            </div>
          </template>

          <el-tabs v-model="activeLocale" @tab-change="handleTabChange">
            <el-tab-pane
              v-for="locale in availableLocales"
              :key="locale.code"
              :label="locale.name"
              :name="locale.code"
            >
              <el-form :model="currentTranslation" label-position="top">
                <el-form-item label="标题" required>
                  <el-input
                    v-model="currentTranslation.title"
                    placeholder="请输入通知标题"
                    maxlength="100"
                    show-word-limit
                  />
                </el-form-item>
                <el-form-item label="内容" required>
                  <el-input
                    v-model="currentTranslation.content"
                    type="textarea"
                    :rows="8"
                    placeholder="请输入通知内容，可使用变量如 {order_no}"
                    maxlength="500"
                    show-word-limit
                  />
                </el-form-item>
              </el-form>

              <!-- 删除翻译按钮（非英文） -->
              <div class="translation-actions" v-if="activeLocale !== 'en-us'">
                <el-popconfirm
                  title="确定要删除该语言的翻译吗？"
                  @confirm="deleteTranslation"
                >
                  <template #reference>
                    <el-button type="danger" plain size="small">
                      删除此翻译
                    </el-button>
                  </template>
                </el-popconfirm>
              </div>
            </el-tab-pane>
          </el-tabs>
        </el-card>
      </el-col>

      <!-- 右侧：可用变量 -->
      <el-col :span="8">
        <el-card shadow="never" class="variables-card">
          <template #header>
            <span>可用变量</span>
          </template>
          <div class="variables-list">
            <div
              v-for="(desc, key) in templateDetail?.available_variables"
              :key="key"
              class="variable-item"
              @click="insertVariable(key)"
            >
              <code class="variable-code">{'{' + key + '}'}</code>
              <span class="variable-desc">{{ desc }}</span>
            </div>
            <el-empty
              v-if="!templateDetail?.available_variables || Object.keys(templateDetail.available_variables).length === 0"
              description="暂无可用变量"
              :image-size="60"
            />
          </div>
          <div class="variables-tip">
            <el-icon><InfoFilled /></el-icon>
            点击变量可复制到剪贴板
          </div>
        </el-card>

        <!-- 预览卡片 -->
        <el-card shadow="never" class="preview-card">
          <template #header>
            <div class="card-header">
              <span>实时预览</span>
              <el-button type="primary" link @click="loadPreview">
                刷新预览
              </el-button>
            </div>
          </template>
          <div class="preview-content" v-loading="previewLoading">
            <div class="preview-notification" v-if="previewData.title">
              <div class="preview-title">{{ previewData.title }}</div>
              <div class="preview-body">{{ previewData.content }}</div>
            </div>
            <el-empty v-else description="编辑内容后查看预览" :image-size="60" />
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { ArrowLeft, InfoFilled } from '@element-plus/icons-vue'
import {
  getNotificationTemplateDetail,
  getNotificationTranslation,
  saveNotificationTranslation,
  deleteNotificationTranslation,
  previewNotificationTemplate,
  type NotificationTemplateDetail,
  type LocaleInfo
} from '@/api/notificationTemplate'

const route = useRoute()
const router = useRouter()

// 模板类型
const templateType = computed(() => route.params.type as string)

// 数据
const loading = ref(false)
const saving = ref(false)
const templateDetail = ref<NotificationTemplateDetail | null>(null)

// 当前编辑的翻译
const activeLocale = ref('en-us')
const currentTranslation = reactive({
  title: '',
  content: '',
})

// 可用语言
const availableLocales = ref<LocaleInfo[]>([
  { code: 'en-us', name: 'English' },
  { code: 'zh-tw', name: '繁體中文' },
  { code: 'ja-jp', name: '日本語' },
])

// 预览
const previewLoading = ref(false)
const previewData = ref<{ title: string; content: string }>({ title: '', content: '' })

// 返回列表
const goBack = () => {
  router.push('/system/notification-template')
}

// 获取分类名称
const getCategoryName = (category?: string): string => {
  if (!category) return '-'
  const names: Record<string, string> = {
    order: '订单',
    important: '重要',
    account: '账户',
    promotion: '促销',
  }
  return names[category] || category
}

// 获取分类标签类型
const getCategoryType = (category?: string): string => {
  if (!category) return 'info'
  const types: Record<string, string> = {
    order: 'primary',
    important: 'danger',
    account: 'warning',
    promotion: 'success',
  }
  return types[category] || 'info'
}

// 加载模板详情
const loadTemplateDetail = async () => {
  loading.value = true
  try {
    const { data } = await getNotificationTemplateDetail(templateType.value)
    templateDetail.value = data

    // 加载当前语言的翻译
    if (data.translations?.[activeLocale.value]) {
      currentTranslation.title = data.translations[activeLocale.value].title
      currentTranslation.content = data.translations[activeLocale.value].content
    }
  } catch (error) {
    console.error('Failed to load template detail:', error)
    ElMessage.error('加载模板失败')
  } finally {
    loading.value = false
  }
}

// 切换语言标签
const handleTabChange = async (locale: string) => {
  // 从缓存的详情中获取翻译
  if (templateDetail.value?.translations?.[locale]) {
    currentTranslation.title = templateDetail.value.translations[locale].title
    currentTranslation.content = templateDetail.value.translations[locale].content
  } else {
    // 如果没有该语言的翻译，清空表单
    currentTranslation.title = ''
    currentTranslation.content = ''
  }

  // 刷新预览
  loadPreview()
}

// 保存翻译
const saveTranslation = async () => {
  if (!currentTranslation.title) {
    ElMessage.warning('请输入标题')
    return
  }
  if (!currentTranslation.content) {
    ElMessage.warning('请输入内容')
    return
  }

  saving.value = true
  try {
    await saveNotificationTranslation(templateType.value, activeLocale.value, {
      title: currentTranslation.title,
      content: currentTranslation.content,
      category: templateDetail.value?.category || 'order',
      channel: 'message',
      status: 1,
    })
    ElMessage.success('保存成功')

    // 更新本地缓存
    if (templateDetail.value) {
      if (!templateDetail.value.translations) {
        templateDetail.value.translations = {}
      }
      templateDetail.value.translations[activeLocale.value] = {
        id: 0,
        title: currentTranslation.title,
        content: currentTranslation.content,
        status: 1,
      }
    }

    // 刷新预览
    loadPreview()
  } catch (error: any) {
    ElMessage.error(error.message || '保存失败')
  } finally {
    saving.value = false
  }
}

// 删除翻译
const deleteTranslation = async () => {
  try {
    await deleteNotificationTranslation(templateType.value, activeLocale.value)
    ElMessage.success('删除成功')

    // 清空表单
    currentTranslation.title = ''
    currentTranslation.content = ''

    // 从缓存中移除
    if (templateDetail.value?.translations?.[activeLocale.value]) {
      delete templateDetail.value.translations[activeLocale.value]
    }
  } catch (error: any) {
    ElMessage.error(error.message || '删除失败')
  }
}

// 插入变量（复制到剪贴板）
const insertVariable = async (key: string) => {
  const variable = `{${key}}`
  try {
    await navigator.clipboard.writeText(variable)
    ElMessage.success(`已复制 ${variable}`)
  } catch {
    ElMessage.info(`变量: ${variable}`)
  }
}

// 加载预览
const loadPreview = async () => {
  previewLoading.value = true
  previewData.value = { title: '', content: '' }

  try {
    const { data } = await previewNotificationTemplate(templateType.value, activeLocale.value)
    previewData.value = data
  } catch (error) {
    console.error('Failed to load preview:', error)
  } finally {
    previewLoading.value = false
  }
}

onMounted(() => {
  loadTemplateDetail()
  loadPreview()
})
</script>

<style scoped>
.notification-template-edit {
  padding: 20px;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}

.page-title {
  font-size: 18px;
  font-weight: 600;
}

.info-card {
  margin-bottom: 20px;
}

.edit-card {
  margin-bottom: 20px;
}

.edit-card .card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.translation-actions {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px dashed #eee;
}

.variables-card {
  margin-bottom: 20px;
}

.variables-list {
  max-height: 300px;
  overflow-y: auto;
}

.variable-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  margin-bottom: 8px;
  background: #f5f7fa;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.variable-item:hover {
  background: #e6f7ff;
}

.variable-code {
  font-family: monospace;
  font-size: 12px;
  padding: 2px 6px;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  color: #e6a23c;
}

.variable-desc {
  font-size: 13px;
  color: #666;
}

.variables-tip {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #eee;
  font-size: 12px;
  color: #909399;
}

.preview-card .card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.preview-content {
  min-height: 150px;
}

.preview-notification {
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  overflow: hidden;
}

.preview-notification .preview-title {
  padding: 12px 16px;
  background: #f5f7fa;
  font-weight: 600;
  font-size: 14px;
  border-bottom: 1px solid #e4e7ed;
}

.preview-notification .preview-body {
  padding: 16px;
  line-height: 1.6;
  font-size: 13px;
  color: #606266;
  white-space: pre-wrap;
}
</style>
