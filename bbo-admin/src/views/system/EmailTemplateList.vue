<template>
  <div class="email-template-list">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>邮件模板管理</span>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="type" label="模板类型" width="180">
          <template #default="{ row }">
            <el-tag type="info">{{ row.type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="模板名称" min-width="150" />
        <el-table-column prop="subject" label="邮件主题" min-width="200" show-overflow-tooltip />
        <el-table-column prop="translation_count" label="翻译数量" width="100" align="center">
          <template #default="{ row }">
            <el-tag type="success" size="small">{{ row.translation_count }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="is_active" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              :model-value="row.is_active"
              @change="handleToggleStatus(row)"
              :loading="row.toggling"
            />
          </template>
        </el-table-column>
        <el-table-column prop="updated_at" label="更新时间" width="180" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="success" link @click="handlePreview(row)">预览</el-button>
            <el-button type="warning" link @click="handleSendTest(row)">测试</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 预览对话框 -->
    <el-dialog v-model="previewDialogVisible" title="邮件预览" width="800px" top="5vh">
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
      <div class="preview-subject" v-if="previewData.subject">
        <strong>主题：</strong>{{ previewData.subject }}
      </div>
      <div class="preview-content" v-loading="previewLoading">
        <iframe
          v-if="previewData.content"
          :srcdoc="previewData.content"
          frameborder="0"
          class="preview-iframe"
        ></iframe>
        <el-empty v-else description="暂无预览内容" />
      </div>
    </el-dialog>

    <!-- 发送测试对话框 -->
    <el-dialog v-model="testDialogVisible" title="发送测试邮件" width="500px">
      <el-form :model="testForm" label-width="100px">
        <el-form-item label="收件邮箱" required>
          <el-input v-model="testForm.email" placeholder="请输入收件邮箱" />
        </el-form-item>
        <el-form-item label="语言">
          <el-select v-model="testForm.locale" placeholder="选择语言">
            <el-option
              v-for="locale in availableLocales"
              :key="locale.code"
              :label="locale.name"
              :value="locale.code"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="testDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmSendTest" :loading="sendingTest">
          发送测试
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  getEmailTemplateList,
  updateEmailTemplate,
  previewEmailTemplate,
  sendTestEmail,
  type EmailTemplate,
  type LocaleInfo
} from '@/api/emailTemplate'

const router = useRouter()

// 列表数据
const list = ref<EmailTemplate[]>([])
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
const previewData = ref<{ subject: string; content: string }>({ subject: '', content: '' })
const currentTemplateId = ref<number | null>(null)

// 测试邮件相关
const testDialogVisible = ref(false)
const sendingTest = ref(false)
const testForm = ref({
  email: '',
  locale: 'en-us',
})

// 获取列表
const fetchList = async () => {
  loading.value = true
  try {
    const { data } = await getEmailTemplateList()
    list.value = data || []
  } catch (error) {
    console.error('Failed to fetch email templates:', error)
  } finally {
    loading.value = false
  }
}

// 编辑模板
const handleEdit = (row: EmailTemplate) => {
  router.push(`/system/email-template/${row.id}`)
}

// 预览
const handlePreview = (row: EmailTemplate) => {
  currentTemplateId.value = row.id
  previewLocale.value = 'en-us'
  previewDialogVisible.value = true
  loadPreview()
}

// 加载预览内容
const loadPreview = async () => {
  if (!currentTemplateId.value) return

  previewLoading.value = true
  previewData.value = { subject: '', content: '' }

  try {
    const { data } = await previewEmailTemplate(currentTemplateId.value, previewLocale.value)
    previewData.value = data
  } catch (error) {
    console.error('Failed to load preview:', error)
    ElMessage.error('加载预览失败')
  } finally {
    previewLoading.value = false
  }
}

// 发送测试邮件
const handleSendTest = (row: EmailTemplate) => {
  currentTemplateId.value = row.id
  testForm.value = {
    email: '',
    locale: 'en-us',
  }
  testDialogVisible.value = true
}

// 确认发送测试
const confirmSendTest = async () => {
  if (!testForm.value.email) {
    ElMessage.warning('请输入收件邮箱')
    return
  }

  sendingTest.value = true
  try {
    await sendTestEmail(currentTemplateId.value!, testForm.value)
    ElMessage.success('测试邮件已发送')
    testDialogVisible.value = false
  } catch (error: any) {
    ElMessage.error(error.message || '发送失败')
  } finally {
    sendingTest.value = false
  }
}

// 切换状态
const handleToggleStatus = async (row: any) => {
  row.toggling = true
  try {
    await updateEmailTemplate(row.id, { is_active: !row.is_active })
    row.is_active = !row.is_active
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
.email-template-list .card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.email-template-list .preview-toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.email-template-list .preview-subject {
  padding: 10px 15px;
  background: #f5f7fa;
  border-radius: 4px;
  margin-bottom: 15px;
}

.email-template-list .preview-content {
  min-height: 400px;
}

.email-template-list .preview-iframe {
  width: 100%;
  height: 500px;
  border: 1px solid #eee;
  border-radius: 4px;
}
</style>
