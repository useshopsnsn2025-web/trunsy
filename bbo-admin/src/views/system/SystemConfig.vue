<template>
  <div class="system-config">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>系统配置</span>
          <div class="header-actions">
            <el-button
              v-if="activeGroup === 'currency'"
              type="success"
              @click="handleUpdateExchangeRate"
              :loading="updatingRate"
            >
              <el-icon><Refresh /></el-icon>
              更新汇率
            </el-button>
            <el-button
              v-if="activeGroup === 'mail'"
              type="success"
              @click="showTestMailDialog"
            >
              <el-icon><Message /></el-icon>
              测试邮件
            </el-button>
            <el-button type="primary" @click="handleSave" :loading="saving">
              <el-icon><Check /></el-icon>
              保存配置
            </el-button>
            <el-button @click="handleAdd">
              <el-icon><Plus /></el-icon>
              新增配置
            </el-button>
          </div>
        </div>
      </template>

      <!-- 分组标签页 -->
      <el-tabs v-model="activeGroup" @tab-change="handleTabChange">
        <el-tab-pane
          v-for="group in groups"
          :key="group.value"
          :label="group.label"
          :name="group.value"
        >
          <el-table :data="configList" v-loading="loading" stripe>
            <el-table-column prop="name" label="配置名称" width="200" />
            <el-table-column prop="key" label="配置键" width="200" />
            <el-table-column prop="value" label="配置值" min-width="300">
              <template #default="{ row }">
                <template v-if="row.type === 'boolean'">
                  <el-switch
                    v-model="row.value"
                    :active-value="'1'"
                    :inactive-value="'0'"
                    @change="handleValueChange(row)"
                  />
                </template>
                <template v-else-if="row.type === 'number'">
                  <el-input-number
                    v-model.number="row.value"
                    :min="0"
                    @change="handleValueChange(row)"
                  />
                </template>
                <template v-else-if="row.type === 'json' || row.type === 'textarea'">
                  <el-input
                    v-model="row.value"
                    type="textarea"
                    :rows="3"
                    :placeholder="row.type === 'textarea' ? '每行一个' : ''"
                    @change="handleValueChange(row)"
                  />
                </template>
                <template v-else-if="row.type === 'image'">
                  <div class="image-value">
                    <el-image
                      v-if="row.value"
                      :src="row.value"
                      :preview-src-list="[row.value]"
                      fit="contain"
                      style="width: 80px; height: 80px;"
                    />
                    <span v-else class="no-image">未设置</span>
                  </div>
                </template>
                <template v-else-if="row.type === 'file'">
                  <div class="file-value">
                    <el-link v-if="row.value" :href="row.value" target="_blank" type="primary">
                      {{ row.value.split('/').pop() }}
                    </el-link>
                    <span v-else class="no-image">未上传</span>
                  </div>
                </template>
                <!-- 密码字段 -->
                <template v-else-if="row.key === 'mail_password'">
                  <el-input
                    v-model="row.value"
                    type="password"
                    show-password
                    @change="handleValueChange(row)"
                  />
                </template>
                <template v-else>
                  <el-input
                    v-model="row.value"
                    @change="handleValueChange(row)"
                  />
                </template>
              </template>
            </el-table-column>
            <el-table-column prop="type" label="类型" width="100">
              <template #default="{ row }">
                <el-tag size="small">{{ typeLabels[row.type] || row.type }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="description" label="说明" min-width="200" />
            <el-table-column label="操作" width="150" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
                <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="formDialogVisible"
      :title="isEdit ? '编辑配置' : '新增配置'"
      width="500px"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="分组" prop="group">
          <el-select v-model="form.group" placeholder="请选择分组" style="width: 100%">
            <el-option
              v-for="g in groups"
              :key="g.value"
              :label="g.label"
              :value="g.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="配置键" prop="key">
          <el-input v-model="form.key" :disabled="isEdit" placeholder="请输入配置键（英文）" />
        </el-form-item>
        <el-form-item label="名称/说明" required>
          <el-tabs v-model="activeLang" type="card" class="lang-tabs">
            <el-tab-pane label="繁體中文" name="zh-tw">
              <el-form-item label="名稱" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].name" placeholder="請輸入繁體中文名稱" />
              </el-form-item>
              <el-form-item label="說明" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['zh-tw'].description" type="textarea" :rows="2" placeholder="配置說明（繁體中文）" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="English" name="en-us">
              <el-form-item label="Name" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].name" placeholder="Enter config name" />
              </el-form-item>
              <el-form-item label="Desc" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['en-us'].description" type="textarea" :rows="2" placeholder="Description (English)" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="日本語" name="ja-jp">
              <el-form-item label="Name" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].name" placeholder="設定名を入力してください" />
              </el-form-item>
              <el-form-item label="Desc" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['ja-jp'].description" type="textarea" :rows="2" placeholder="設定の説明（日本語）" />
              </el-form-item>
            </el-tab-pane>
          </el-tabs>
        </el-form-item>
        <el-form-item label="值类型" prop="type">
          <el-select v-model="form.type" placeholder="请选择类型" style="width: 100%">
            <el-option label="字符串" value="string" />
            <el-option label="数字" value="number" />
            <el-option label="布尔值" value="boolean" />
            <el-option label="多行文本" value="textarea" />
            <el-option label="图片" value="image" />
            <el-option label="JSON" value="json" />
          </el-select>
        </el-form-item>
        <el-form-item label="配置值" prop="value">
          <template v-if="form.type === 'boolean'">
            <el-switch v-model="form.value" active-value="1" inactive-value="0" />
          </template>
          <template v-else-if="form.type === 'number'">
            <el-input-number v-model.number="form.value" :min="0" style="width: 100%;" />
          </template>
          <template v-else-if="form.type === 'json'">
            <el-input v-model="form.value" type="textarea" :rows="4" placeholder="请输入JSON格式" />
          </template>
          <template v-else-if="form.type === 'textarea'">
            <el-input v-model="form.value" type="textarea" :rows="4" placeholder="每行一个，支持多个值轮换" />
          </template>
          <template v-else-if="form.type === 'image'">
            <ImagePicker v-model="form.value" width="120px" height="120px" />
            <div class="image-tip">点击选择或上传图片</div>
          </template>
          <template v-else-if="form.type === 'file'">
            <el-upload
              :action="uploadUrl"
              :headers="uploadHeaders"
              :show-file-list="false"
              :on-success="handleFileUploadSuccess"
              :before-upload="beforeFileUpload"
            >
              <el-button type="primary" :loading="fileUploading">
                {{ fileUploading ? '上传中...' : '选择文件' }}
              </el-button>
            </el-upload>
            <div v-if="form.value" style="margin-top: 8px;">
              <el-link :href="form.value" target="_blank" type="primary">{{ form.value.split('/').pop() }}</el-link>
              <el-button type="danger" size="small" text @click="form.value = ''" style="margin-left: 8px;">删除</el-button>
            </div>
          </template>
          <template v-else>
            <!-- 字符串类型支持多语言 -->
            <el-tabs v-model="activeLang" type="card" class="lang-tabs">
              <el-tab-pane label="繁體中文" name="zh-tw">
                <el-input v-model="form.translations['zh-tw'].value" type="textarea" :rows="2" placeholder="請輸入配置值（繁體中文）" />
              </el-tab-pane>
              <el-tab-pane label="English" name="en-us">
                <el-input v-model="form.translations['en-us'].value" type="textarea" :rows="2" placeholder="Enter value (English)" />
              </el-tab-pane>
              <el-tab-pane label="日本語" name="ja-jp">
                <el-input v-model="form.translations['ja-jp'].value" type="textarea" :rows="2" placeholder="値を入力してください（日本語）" />
              </el-tab-pane>
            </el-tabs>
          </template>
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 测试邮件对话框 -->
    <el-dialog
      v-model="testMailDialogVisible"
      title="发送测试邮件"
      width="450px"
    >
      <el-form :model="testMailForm" label-width="100px">
        <el-form-item label="收件人邮箱" required>
          <el-input v-model="testMailForm.to" placeholder="请输入收件人邮箱地址" />
        </el-form-item>
        <el-alert
          v-if="testMailResult"
          :title="testMailResult.success ? '发送成功' : '发送失败'"
          :type="testMailResult.success ? 'success' : 'error'"
          :description="testMailResult.message"
          show-icon
          :closable="false"
          style="margin-top: 10px;"
        />
      </el-form>
      <template #footer>
        <el-button @click="testMailDialogVisible = false">关闭</el-button>
        <el-button type="primary" @click="handleSendTestMail" :loading="sendingTestMail">
          发送测试邮件
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus, Check, Refresh, Message } from '@element-plus/icons-vue'
import ImagePicker from '@/components/ImagePicker.vue'
import {
  getConfigList,
  getConfigGroups,
  getConfigDetail,
  createConfig,
  updateConfig,
  deleteConfig,
  batchUpdateConfigs,
  updateExchangeRate,
  sendTestMail,
  type SystemConfig
} from '@/api/systemConfig'

// 分组列表
const groups = ref([
  { value: 'basic', label: '基础配置' },
  { value: 'contact', label: '联系方式' },
  { value: 'trade', label: '交易设置' },
  { value: 'security', label: '安全设置' }
])

const activeGroup = ref('basic')

// 类型标签
const typeLabels: Record<string, string> = {
  string: '字符串',
  number: '数字',
  boolean: '布尔值',
  image: '图片',
  file: '文件',
  json: 'JSON'
}

// 配置列表
const configList = ref<SystemConfig[]>([])
const loading = ref(false)
const saving = ref(false)
const updatingRate = ref(false)

// 修改过的配置
const changedConfigs = ref<Map<string, string>>(new Map())

// 表单相关
const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)
const activeLang = ref('zh-tw')

// 初始翻译结构
const emptyTranslations = () => ({
  'zh-tw': { name: '', description: '', value: '' },
  'en-us': { name: '', description: '', value: '' },
  'ja-jp': { name: '', description: '', value: '' }
})

const form = reactive({
  group: 'basic',
  key: '',
  type: 'string',
  value: '',
  sort: 0,
  translations: emptyTranslations() as Record<string, { name: string; description: string; value: string }>
})

const rules: FormRules = {
  group: [{ required: true, message: '请选择分组', trigger: 'change' }],
  key: [
    { required: true, message: '请输入配置键', trigger: 'blur' },
    { pattern: /^[a-zA-Z_][a-zA-Z0-9_]*$/, message: '配置键只能包含字母、数字和下划线', trigger: 'blur' }
  ],
  type: [{ required: true, message: '请选择值类型', trigger: 'change' }]
}

// 获取分组列表
const fetchGroups = async () => {
  try {
    const res: any = await getConfigGroups()
    if (res.data && res.data.length > 0) {
      // 转换格式 {key, name} -> {value, label}
      groups.value = res.data.map((item: any) => ({
        value: item.key || item.value,
        label: item.name || item.label
      }))
    }
  } catch (error) {
    console.error('获取分组列表失败:', error)
  }
}

// 获取配置列表
const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getConfigList({
      group: activeGroup.value,
      pageSize: 100
    })
    configList.value = res.data.list || []
    changedConfigs.value.clear()
  } catch (error) {
    console.error('获取配置列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 切换标签页
const handleTabChange = () => {
  fetchList()
}

// 值变化
const handleValueChange = (row: SystemConfig) => {
  changedConfigs.value.set(row.key, String(row.value))
}

// 文件上传相关
const fileUploading = ref(false)
const uploadUrl = '/admin/attachments/upload'
const uploadHeaders = computed(() => ({
  Authorization: `Bearer ${localStorage.getItem('admin_token')}`
}))

function beforeFileUpload() {
  fileUploading.value = true
  return true
}

function handleFileUploadSuccess(response: any) {
  fileUploading.value = false
  if (response.code === 0 && response.data?.url) {
    form.value = response.data.url
    ElMessage.success('文件上传成功')
  } else {
    ElMessage.error(response.msg || '上传失败')
  }
}


// 保存配置
const handleSave = async () => {
  if (changedConfigs.value.size === 0) {
    ElMessage.info('没有修改的配置')
    return
  }

  saving.value = true
  try {
    const configs = Array.from(changedConfigs.value.entries()).map(([key, value]) => ({
      key,
      value
    }))
    await batchUpdateConfigs(configs)
    ElMessage.success('保存成功')
    changedConfigs.value.clear()
    // 重新获取列表以确保数据同步
    await fetchList()
  } catch (error) {
    console.error('保存失败:', error)
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 新增
const handleAdd = () => {
  isEdit.value = false
  formDialogVisible.value = true
  activeLang.value = 'zh-tw'
  Object.assign(form, {
    group: activeGroup.value,
    key: '',
    type: 'string',
    value: '',
    sort: 0,
    translations: emptyTranslations()
  })
}

// 编辑
const handleEdit = async (row: SystemConfig) => {
  isEdit.value = true
  editId.value = row.id
  activeLang.value = 'zh-tw'
  try {
    const res: any = await getConfigDetail(row.id)
    const data = res.data
    // 合并翻译数据，确保所有语言键都存在
    const mergedTranslations = emptyTranslations()
    if (data.translations) {
      for (const locale of ['zh-tw', 'en-us', 'ja-jp']) {
        if (data.translations[locale]) {
          mergedTranslations[locale] = {
            name: data.translations[locale].name || '',
            description: data.translations[locale].description || '',
            value: data.translations[locale].value || ''
          }
        }
      }
    }
    // 对于字符串类型，如果翻译中没有 value，用主表的 value 填充到繁体中文
    if (data.type === 'string' && data.value && !mergedTranslations['zh-tw'].value) {
      mergedTranslations['zh-tw'].value = data.value
    }
    Object.assign(form, {
      group: data.group,
      key: data.key,
      type: data.type,
      value: data.value || '',
      sort: data.sort,
      translations: mergedTranslations
    })
    formDialogVisible.value = true
  } catch (error) {
    console.error('获取详情失败:', error)
    ElMessage.error('获取详情失败')
  }
}

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return
  // 验证至少填写一种语言的名称
  if (!form.translations['zh-tw'].name && !form.translations['en-us'].name && !form.translations['ja-jp'].name) {
    ElMessage.warning('请至少填写一种语言的配置名称')
    return
  }
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      if (isEdit.value) {
        const { key, ...updateData } = form
        await updateConfig(editId.value, updateData)
        ElMessage.success('更新成功')
      } else {
        await createConfig(form)
        ElMessage.success('创建成功')
      }
      formDialogVisible.value = false
      fetchList()
    } catch (error) {
      console.error('提交失败:', error)
    } finally {
      submitting.value = false
    }
  })
}

// 删除
const handleDelete = async (row: SystemConfig) => {
  try {
    await ElMessageBox.confirm(`确定要删除配置"${row.name}"吗？`, '提示', {
      type: 'warning'
    })
    await deleteConfig(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

// 更新汇率
const handleUpdateExchangeRate = async () => {
  updatingRate.value = true
  try {
    const res: any = await updateExchangeRate()
    if (res.code === 0) {
      ElMessage.success(res.msg || '汇率更新成功')
      // 刷新列表以显示最新汇率
      fetchList()
    } else {
      ElMessage.error(res.msg || '汇率更新失败')
    }
  } catch (error: any) {
    console.error('更新汇率失败:', error)
    ElMessage.error(error.message || '更新汇率失败')
  } finally {
    updatingRate.value = false
  }
}

// 测试邮件相关
const testMailDialogVisible = ref(false)
const sendingTestMail = ref(false)
const testMailForm = reactive({
  to: ''
})
const testMailResult = ref<{ success: boolean; message: string } | null>(null)

// 显示测试邮件对话框
const showTestMailDialog = () => {
  testMailForm.to = ''
  testMailResult.value = null
  testMailDialogVisible.value = true
}

// 发送测试邮件
const handleSendTestMail = async () => {
  if (!testMailForm.to) {
    ElMessage.warning('请输入收件人邮箱')
    return
  }
  // 简单的邮箱格式验证
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(testMailForm.to)) {
    ElMessage.warning('请输入有效的邮箱地址')
    return
  }

  sendingTestMail.value = true
  testMailResult.value = null
  try {
    const res: any = await sendTestMail(testMailForm.to)
    if (res.code === 0) {
      testMailResult.value = {
        success: true,
        message: res.msg || '测试邮件发送成功'
      }
    } else {
      testMailResult.value = {
        success: false,
        message: res.msg || '测试邮件发送失败'
      }
    }
  } catch (error: any) {
    console.error('发送测试邮件失败:', error)
    testMailResult.value = {
      success: false,
      message: error.message || '发送测试邮件失败'
    }
  } finally {
    sendingTestMail.value = false
  }
}

onMounted(() => {
  fetchGroups()
  fetchList()
})
</script>

<style scoped>
.system-config {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.lang-tabs {
  width: 100%;
}

.lang-tabs :deep(.el-tabs__content) {
  padding: 15px;
  background: #f9f9f9;
  border-radius: 4px;
}

.image-value {
  display: inline-block;
}

.image-value .no-image {
  color: #c0c4cc;
  font-size: 12px;
}

.image-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 8px;
}
</style>
