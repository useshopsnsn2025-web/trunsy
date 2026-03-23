<template>
  <div class="email-template-edit">
    <el-card shadow="never" v-loading="loading">
      <template #header>
        <div class="card-header">
          <el-button link @click="goBack">
            <el-icon><ArrowLeft /></el-icon>
            返回
          </el-button>
          <span class="title">编辑邮件模板</span>
        </div>
      </template>

      <div class="template-info" v-if="template">
        <el-descriptions :column="3" border>
          <el-descriptions-item label="模板类型">
            <el-tag>{{ template.type }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="模板名称">{{ template.name }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-switch v-model="template.is_active" @change="handleUpdateTemplate" />
          </el-descriptions-item>
        </el-descriptions>
      </div>

      <!-- 语言标签页 -->
      <el-tabs v-model="activeLocale" class="template-tabs" @tab-change="handleTabChange">
        <el-tab-pane
          v-for="locale in availableLocales"
          :key="locale.code"
          :label="locale.name"
          :name="locale.code"
        >
          <div class="locale-content">
            <el-form :model="translationForm" label-width="100px">
              <el-form-item label="邮件主题">
                <el-input
                  v-model="translationForm.subject"
                  placeholder="请输入邮件主题"
                  maxlength="255"
                  show-word-limit
                />
              </el-form-item>

              <el-form-item label="邮件内容">
                <div class="editor-toolbar">
                  <el-button-group>
                    <el-button size="small" @click="insertVariable">
                      <el-icon><Plus /></el-icon>
                      插入变量
                    </el-button>
                    <el-button size="small" @click="formatCode">
                      <el-icon><Document /></el-icon>
                      格式化
                    </el-button>
                  </el-button-group>
                  <el-button size="small" type="success" @click="previewContent">
                    <el-icon><View /></el-icon>
                    预览
                  </el-button>
                </div>
                <el-input
                  v-model="translationForm.content"
                  type="textarea"
                  :rows="20"
                  placeholder="请输入邮件HTML内容"
                  class="code-editor"
                />
              </el-form-item>

              <el-form-item>
                <el-button type="primary" @click="saveTranslation" :loading="saving">
                  保存翻译
                </el-button>
                <el-button @click="resetTranslation">重置</el-button>
                <el-button
                  type="danger"
                  @click="handleDeleteTranslation"
                  v-if="translationForm.id"
                >
                  删除翻译
                </el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>
      </el-tabs>

      <!-- 模板图片配置 -->
      <el-card shadow="never" class="images-card">
        <template #header>
          <div class="images-card-header">
            <span>模板图片配置</span>
            <el-button type="primary" size="small" @click="saveImages" :loading="savingImages">
              保存图片配置
            </el-button>
          </div>
        </template>
        <el-form :model="imagesForm" label-width="120px">
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Header 背景图">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.header_image" :show-delete="true" />
                  <div class="image-info">
                    <div class="image-variable-tip">
                      模板变量: <code>{template_header_image}</code>
                    </div>
                  </div>
                </div>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Logo">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.logo" :show-delete="true" />
                  <div class="image-info">
                    <div class="image-variable-tip">
                      模板变量: <code>{template_logo}</code>
                    </div>
                    <div class="image-hint">为空则使用系统LOGO</div>
                  </div>
                </div>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Banner 图">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.banner" :show-delete="true" />
                  <div class="image-info">
                    <div class="image-variable-tip">
                      模板变量: <code>{template_banner}</code>
                    </div>
                  </div>
                </div>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Footer 图片">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.footer_image" :show-delete="true" />
                  <div class="image-info">
                    <div class="image-variable-tip">
                      模板变量: <code>{template_footer_image}</code>
                    </div>
                  </div>
                </div>
              </el-form-item>
            </el-col>
          </el-row>

          <!-- 分类图片 -->
          <el-divider content-position="left">分类图片</el-divider>
          <el-row :gutter="20">
            <el-col :span="6">
              <el-form-item label="分类1">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.category_image_1" :show-delete="true" />
                </div>
              </el-form-item>
            </el-col>
            <el-col :span="6">
              <el-form-item label="分类2">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.category_image_2" :show-delete="true" />
                </div>
              </el-form-item>
            </el-col>
            <el-col :span="6">
              <el-form-item label="分类3">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.category_image_3" :show-delete="true" />
                </div>
              </el-form-item>
            </el-col>
            <el-col :span="6">
              <el-form-item label="分类4">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.category_image_4" :show-delete="true" />
                </div>
              </el-form-item>
            </el-col>
          </el-row>

          <!-- 功能区域图片 -->
          <el-divider content-position="left">功能区域图片</el-divider>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="保护区域图">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.protection_image" :show-delete="true" />
                  <div class="image-info">
                    <div class="image-variable-tip">
                      模板变量: <code>{protection_image}</code>
                    </div>
                  </div>
                </div>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="实时发现图">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.live_image" :show-delete="true" />
                  <div class="image-info">
                    <div class="image-variable-tip">
                      模板变量: <code>{live_image}</code>
                    </div>
                  </div>
                </div>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="APP二维码">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.app_qr_code" :show-delete="true" />
                  <div class="image-info">
                    <div class="image-variable-tip">
                      模板变量: <code>{app_qr_code}</code>
                    </div>
                  </div>
                </div>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="销售区域图">
                <div class="image-picker-row">
                  <ImagePicker v-model="imagesForm.sell_image" :show-delete="true" />
                  <div class="image-info">
                    <div class="image-variable-tip">
                      模板变量: <code>{sell_image}</code>
                    </div>
                  </div>
                </div>
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>
      </el-card>

      <!-- 可用变量说明 -->
      <el-card shadow="never" class="variables-card">
        <template #header>
          <span>可用变量</span>
        </template>
        <div class="variables-list">
          <el-tooltip
            v-for="variable in availableVariables"
            :key="variable"
            :content="getVariableDescription(variable)"
            placement="top"
          >
            <el-tag
              class="variable-tag"
              @click="copyVariable(variable)"
            >
              {{ formatVariableDisplay(variable) }}
            </el-tag>
          </el-tooltip>
        </div>
        <div class="variables-tip">
          点击变量可复制到剪贴板，鼠标悬停查看说明
        </div>
      </el-card>
    </el-card>

    <!-- 预览对话框 -->
    <el-dialog v-model="previewDialogVisible" title="内容预览" width="800px" top="5vh">
      <div class="preview-content">
        <iframe
          v-if="previewHtml"
          :srcdoc="previewHtml"
          frameborder="0"
          class="preview-iframe"
        ></iframe>
      </div>
    </el-dialog>

    <!-- 插入变量对话框 -->
    <el-dialog v-model="variableDialogVisible" title="选择变量" width="700px">
      <div class="variable-picker">
        <div
          v-for="variable in availableVariables"
          :key="variable"
          class="variable-item"
          @click="insertSelectedVariable(variable)"
        >
          <el-tag>{{ formatVariableDisplay(variable) }}</el-tag>
          <span class="variable-desc">{{ getVariableDescription(variable) }}</span>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ArrowLeft, Plus, Document, View } from '@element-plus/icons-vue'
import {
  getEmailTemplateDetail,
  updateEmailTemplate,
  getTemplateTranslation,
  saveTemplateTranslation,
  deleteTemplateTranslation,
  previewEmailTemplate,
  getTemplateImages,
  updateTemplateImages,
  type EmailTemplate,
  type LocaleInfo,
  type EmailTemplateImages
} from '@/api/emailTemplate'
import ImagePicker from '@/components/ImagePicker.vue'

const route = useRoute()
const router = useRouter()

const templateId = computed(() => Number(route.params.id))

// 数据
const loading = ref(false)
const saving = ref(false)
const template = ref<EmailTemplate | null>(null)
const activeLocale = ref('en-us')

// 可用语言
const availableLocales = ref<LocaleInfo[]>([])

// 翻译表单
const translationForm = ref({
  id: null as number | null,
  subject: '',
  content: '',
})

// 原始翻译数据（用于重置）
const originalTranslation = ref({
  subject: '',
  content: '',
})

// 图片配置
const savingImages = ref(false)
const imagesForm = ref<EmailTemplateImages>({
  header_image: '',
  logo: '',
  banner: '',
  footer_image: '',
  category_image_1: '',
  category_image_2: '',
  category_image_3: '',
  category_image_4: '',
  protection_image: '',
  live_image: '',
  app_qr_code: '',
  sell_image: '',
})

// 可用变量及其中文说明
const variableDescriptions: Record<string, string> = {
  'buyer_name': '买家姓名',
  'user_name': '用户名',
  'order_no': '订单号',
  'order_date': '订单日期',
  'total_amount': '订单总额',
  'paid_amount': '实付金额',
  'goods_title': '商品标题',
  'goods_image': '商品图片',
  'quantity': '商品数量',
  'price': '商品价格',
  'currency': '货币符号',
  'payment_method': '支付方式',
  'recipient_name': '收件人姓名',
  'address': '收货地址',
  'city': '城市',
  'state': '省/州',
  'postal_code': '邮政编码',
  'country': '国家',
  'platform_name': '平台名称',
  'platform_url': '平台网址',
  'platform_logo': '平台LOGO图片',
  'current_year': '当前年份',
  'shipped_date': '发货日期',
  'carrier_name': '物流公司',
  'tracking_no': '物流单号',
  'tracking_url': '物流追踪链接',
  'estimated_delivery': '预计送达时间',
  'fail_message': '失败信息',
  'fail_reason': '失败原因',
  // 模板图片变量
  'template_header_image': '模板Header背景图',
  'template_logo': '模板LOGO（为空则使用系统LOGO）',
  'template_banner': '模板Banner图',
  'template_footer_image': '模板Footer图片',
  'category_image_1': '分类图片1',
  'category_image_2': '分类图片2',
  'category_image_3': '分类图片3',
  'category_image_4': '分类图片4',
  'protection_image': '保护区域图片',
  'live_image': '实时发现图片',
  'app_qr_code': 'APP下载二维码',
  'sell_image': '销售区域图片',
}

// 可用变量
const availableVariables = computed(() => {
  if (template.value?.variables) {
    return template.value.variables
  }
  return Object.keys(variableDescriptions)
})

// 格式化变量显示
const formatVariableDisplay = (variable: string) => {
  return '{{$' + variable + '}}'
}

// 获取变量的中文说明
const getVariableDescription = (variable: string) => {
  return variableDescriptions[variable] || variable
}

// 预览
const previewDialogVisible = ref(false)
const previewHtml = ref('')

// 变量选择
const variableDialogVisible = ref(false)

// 返回
const goBack = () => {
  router.push('/system/email-template')
}

// 获取模板详情
const fetchTemplate = async () => {
  loading.value = true
  try {
    const { data } = await getEmailTemplateDetail(templateId.value)
    template.value = data
    availableLocales.value = data.available_locales || [
      { code: 'en-us', name: 'English' },
      { code: 'zh-tw', name: '繁體中文' },
      { code: 'ja-jp', name: '日本語' },
    ]

    // 检查 activeLocale 是否在可用语言列表中，如果不在则使用第一个
    const localeExists = availableLocales.value.some(l => l.code === activeLocale.value)
    if (!localeExists && availableLocales.value.length > 0) {
      // 优先选择 en-us，否则选第一个
      const enLocale = availableLocales.value.find(l => l.code === 'en-us')
      activeLocale.value = enLocale ? enLocale.code : availableLocales.value[0].code
    }

    // 加载当前语言的翻译
    await loadTranslation(activeLocale.value)
  } catch (error) {
    console.error('Failed to fetch template:', error)
    ElMessage.error('加载模板失败')
  } finally {
    loading.value = false
  }
}

// 加载翻译
const loadTranslation = async (locale: string) => {
  try {
    const { data } = await getTemplateTranslation(templateId.value, locale)
    translationForm.value = {
      id: data.id || null,
      subject: data.subject || '',
      content: data.content || '',
    }
    originalTranslation.value = {
      subject: data.subject || '',
      content: data.content || '',
    }
  } catch (error) {
    console.error('Failed to load translation:', error)
    // 如果加载失败，重置表单
    translationForm.value = {
      id: null,
      subject: '',
      content: '',
    }
    originalTranslation.value = {
      subject: '',
      content: '',
    }
  }
}

// 切换语言标签
const handleTabChange = (locale: string) => {
  loadTranslation(locale)
}

// 更新模板基本信息
const handleUpdateTemplate = async () => {
  if (!template.value) return
  try {
    await updateEmailTemplate(templateId.value, {
      is_active: template.value.is_active,
    })
    ElMessage.success('更新成功')
  } catch (error) {
    console.error('Failed to update template:', error)
  }
}

// 保存翻译
const saveTranslation = async () => {
  if (!translationForm.value.subject || !translationForm.value.content) {
    ElMessage.warning('请填写主题和内容')
    return
  }

  saving.value = true
  try {
    await saveTemplateTranslation(templateId.value, activeLocale.value, {
      subject: translationForm.value.subject,
      content: translationForm.value.content,
    })
    ElMessage.success('保存成功')
    // 重新加载以获取ID
    loadTranslation(activeLocale.value)
  } catch (error: any) {
    ElMessage.error(error.message || '保存失败')
  } finally {
    saving.value = false
  }
}

// 重置翻译
const resetTranslation = () => {
  translationForm.value.subject = originalTranslation.value.subject
  translationForm.value.content = originalTranslation.value.content
}

// 删除翻译
const handleDeleteTranslation = async () => {
  try {
    await ElMessageBox.confirm('确定要删除该语言的翻译吗？', '提示', {
      type: 'warning',
    })

    await deleteTemplateTranslation(templateId.value, activeLocale.value)
    ElMessage.success('删除成功')
    translationForm.value = {
      id: null,
      subject: '',
      content: '',
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '删除失败')
    }
  }
}

// 预览内容（调用后端 API 进行变量替换）
const previewContent = async () => {
  try {
    const { data } = await previewEmailTemplate(templateId.value, activeLocale.value)
    previewHtml.value = data.content
    previewDialogVisible.value = true
  } catch (error: any) {
    // 如果 API 调用失败，降级为直接显示原始内容
    console.error('Preview API failed, fallback to raw content:', error)
    previewHtml.value = translationForm.value.content
    previewDialogVisible.value = true
  }
}

// 插入变量
const insertVariable = () => {
  variableDialogVisible.value = true
}

// 插入选中的变量
const insertSelectedVariable = (variable: string) => {
  const varText = '{{$' + variable + '}}'
  translationForm.value.content += varText
  variableDialogVisible.value = false
  ElMessage.success('已插入: ' + varText)
}

// 复制变量
const copyVariable = async (variable: string) => {
  const varText = '{{$' + variable + '}}'
  try {
    await navigator.clipboard.writeText(varText)
    ElMessage.success('已复制: ' + varText)
  } catch (error) {
    ElMessage.error('复制失败')
  }
}

// 格式化代码
const formatCode = () => {
  // 简单的HTML格式化
  let html = translationForm.value.content
  // 在标签后添加换行
  html = html.replace(/></g, '>\n<')
  translationForm.value.content = html
  ElMessage.success('格式化完成')
}

// 加载图片配置
const loadImages = async () => {
  try {
    const { data } = await getTemplateImages(templateId.value)
    imagesForm.value = {
      header_image: data.images?.header_image || '',
      logo: data.images?.logo || '',
      banner: data.images?.banner || '',
      footer_image: data.images?.footer_image || '',
      category_image_1: data.images?.category_image_1 || '',
      category_image_2: data.images?.category_image_2 || '',
      category_image_3: data.images?.category_image_3 || '',
      category_image_4: data.images?.category_image_4 || '',
      protection_image: data.images?.protection_image || '',
      live_image: data.images?.live_image || '',
      app_qr_code: data.images?.app_qr_code || '',
      sell_image: data.images?.sell_image || '',
    }
  } catch (error) {
    console.error('Failed to load images:', error)
  }
}

// 保存图片配置
const saveImages = async () => {
  savingImages.value = true
  try {
    await updateTemplateImages(templateId.value, imagesForm.value)
    ElMessage.success('图片配置已保存')
  } catch (error: any) {
    ElMessage.error(error.message || '保存失败')
  } finally {
    savingImages.value = false
  }
}

onMounted(() => {
  fetchTemplate()
  loadImages()
})
</script>

<style scoped>
.email-template-edit .card-header {
  display: flex;
  align-items: center;
  gap: 15px;
}

.email-template-edit .card-header .title {
  font-size: 16px;
  font-weight: 500;
}

.email-template-edit .template-info {
  margin-bottom: 20px;
}

.email-template-edit .template-tabs {
  margin-top: 20px;
}

.email-template-edit .locale-content {
  padding: 20px 0;
}

.email-template-edit .editor-toolbar {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.email-template-edit .code-editor :deep(textarea) {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
  font-size: 13px;
  line-height: 1.5;
}

.email-template-edit .variables-card {
  margin-top: 20px;
}

.email-template-edit .variables-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.email-template-edit .variable-tag {
  cursor: pointer;
}

.email-template-edit .variable-tag:hover {
  opacity: 0.8;
}

.email-template-edit .variables-tip {
  margin-top: 10px;
  color: #909399;
  font-size: 12px;
}

.email-template-edit .preview-iframe {
  width: 100%;
  height: 500px;
  border: 1px solid #eee;
  border-radius: 4px;
}

.email-template-edit .variable-picker {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  max-height: 400px;
  overflow-y: auto;
}

.email-template-edit .variable-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border: 1px solid #eee;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.email-template-edit .variable-item:hover {
  background: #f0f7ff;
  border-color: #409eff;
}

.email-template-edit .variable-desc {
  color: #909399;
  font-size: 13px;
}

/* 图片配置样式 */
.email-template-edit .images-card {
  margin-top: 20px;
}

.email-template-edit .images-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.email-template-edit .image-picker-row {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.email-template-edit .image-info {
  flex: 1;
  min-width: 0;
}

.email-template-edit .image-variable-tip {
  font-size: 12px;
  color: #909399;
}

.email-template-edit .image-variable-tip code {
  background: #f5f7fa;
  padding: 2px 6px;
  border-radius: 3px;
  font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
  color: #409eff;
}

.email-template-edit .image-hint {
  margin-top: 4px;
  font-size: 12px;
  color: #c0c4cc;
}
</style>
