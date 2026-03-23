<template>
  <div class="payment-method-list">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>支付方式管理</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增支付方式
          </el-button>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="icon" label="图标" width="80">
          <template #default="{ row }">
            <el-image v-if="row.icon" :src="row.icon" style="width: 40px; height: 40px;" fit="contain" />
            <span v-else class="no-icon">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="code" label="编码" width="120" />
        <el-table-column prop="name" label="名称" min-width="150" />
        <el-table-column prop="description" label="描述" min-width="200">
          <template #default="{ row }">
            {{ row.description || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="tag" label="标签" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.tag" size="small" type="warning">{{ row.tag }}</el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
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
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :total="pagination.total"
          layout="total, prev, pager, next"
          @current-change="fetchList"
        />
      </div>
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog v-model="formDialogVisible" :title="isEdit ? '编辑支付方式' : '新增支付方式'" width="650px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="编码" prop="code">
          <el-input v-model="form.code" :disabled="isEdit" placeholder="如：paypal, credit_card" />
        </el-form-item>

        <!-- 多语言输入 -->
        <el-form-item label="名称/描述" required>
          <el-tabs v-model="activeLang" type="card" class="lang-tabs">
            <el-tab-pane label="繁體中文" name="zh-tw">
              <el-form-item label="名稱" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].name" placeholder="支付方式名稱" />
              </el-form-item>
              <el-form-item label="描述" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].description" placeholder="描述（可選）" />
              </el-form-item>
              <el-form-item label="標籤" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].tag" placeholder="如：全新、推薦" />
              </el-form-item>
              <el-form-item label="連結文字" label-width="80px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['zh-tw'].link_text" placeholder="如：查看條款" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="English" name="en-us">
              <el-form-item label="Name" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].name" placeholder="Payment method name" />
              </el-form-item>
              <el-form-item label="Desc" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].description" placeholder="Description (optional)" />
              </el-form-item>
              <el-form-item label="Tag" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].tag" placeholder="e.g. NEW, Recommended" />
              </el-form-item>
              <el-form-item label="Link Text" label-width="80px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['en-us'].link_text" placeholder="e.g. See terms" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="日本語" name="ja-jp">
              <el-form-item label="名前" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].name" placeholder="支払い方法名" />
              </el-form-item>
              <el-form-item label="説明" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].description" placeholder="説明（任意）" />
              </el-form-item>
              <el-form-item label="タグ" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].tag" placeholder="例：新着、おすすめ" />
              </el-form-item>
              <el-form-item label="Link" label-width="80px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['ja-jp'].link_text" placeholder="例：利用規約" />
              </el-form-item>
            </el-tab-pane>
          </el-tabs>
        </el-form-item>

        <el-form-item label="图标">
          <ImagePicker v-model="form.icon" width="60px" height="60px" />
          <span class="image-tip">建议尺寸 60x60，正方形图标</span>
        </el-form-item>

        <el-form-item label="品牌色">
          <el-color-picker v-model="form.brand_color" show-alpha />
          <el-input v-model="form.brand_color" placeholder="#0070BA" style="width: 120px; margin-left: 12px;" />
          <span class="image-tip">用于支付按钮背景色</span>
        </el-form-item>

        <el-form-item label="按钮图标">
          <ImagePicker v-model="form.button_icon" width="60px" height="60px" />
          <span class="image-tip">支付按钮内的图标，建议白色图标</span>
        </el-form-item>

        <el-form-item label="链接地址">
          <el-input v-model="form.link_url" placeholder="如：https://www.paypal.com/credit" />
        </el-form-item>

        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
          <span class="sort-tip">数值越大越靠前</span>
        </el-form-item>

        <el-form-item label="状态" prop="status">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import ImagePicker from '@/components/ImagePicker.vue'
import {
  getPaymentMethodList,
  getPaymentMethodDetail,
  createPaymentMethod,
  updatePaymentMethod,
  deletePaymentMethod,
  togglePaymentMethodStatus,
  type PaymentMethod
} from '@/api/paymentMethod'

const list = ref<(PaymentMethod & { toggling?: boolean })[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)
const activeLang = ref('zh-tw')

// 初始翻译结构
const emptyTranslations = () => ({
  'zh-tw': { name: '', description: '', tag: '', link_text: '' },
  'en-us': { name: '', description: '', tag: '', link_text: '' },
  'ja-jp': { name: '', description: '', tag: '', link_text: '' }
})

// 合并翻译数据
const mergeTranslations = (data: any) => {
  const empty = emptyTranslations()
  if (!data || typeof data !== 'object') return empty
  return {
    'zh-tw': { ...empty['zh-tw'], ...(data['zh-tw'] || {}) },
    'en-us': { ...empty['en-us'], ...(data['en-us'] || {}) },
    'ja-jp': { ...empty['ja-jp'], ...(data['ja-jp'] || {}) }
  }
}

const form = reactive({
  code: '',
  icon: '',
  brand_color: '',
  button_icon: '',
  link_url: '',
  sort: 0,
  status: 1,
  translations: emptyTranslations() as Record<string, { name: string; description: string; tag: string; link_text: string }>
})

const rules: FormRules = {
  code: [
    { required: true, message: '请输入编码', trigger: 'blur' },
    { pattern: /^[a-zA-Z_][a-zA-Z0-9_]*$/, message: '编码只能包含字母、数字和下划线', trigger: 'blur' }
  ]
}

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getPaymentMethodList({
      page: pagination.page,
      pageSize: pagination.pageSize
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取列表失败:', error)
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  isEdit.value = false
  formDialogVisible.value = true
  activeLang.value = 'zh-tw'
  Object.assign(form, {
    code: '',
    icon: '',
    brand_color: '',
    button_icon: '',
    link_url: '',
    sort: 0,
    status: 1,
    translations: emptyTranslations()
  })
}

const handleEdit = async (row: PaymentMethod) => {
  isEdit.value = true
  editId.value = row.id
  activeLang.value = 'zh-tw'
  try {
    const res: any = await getPaymentMethodDetail(row.id)
    const data = res.data
    Object.assign(form, {
      code: data.code,
      icon: data.icon || '',
      brand_color: data.brand_color || '',
      button_icon: data.button_icon || '',
      link_url: data.link_url || '',
      sort: data.sort,
      status: data.status,
      translations: mergeTranslations(data.translations)
    })
    formDialogVisible.value = true
  } catch (error) {
    console.error('获取详情失败:', error)
    ElMessage.error('获取详情失败')
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  // 验证至少填写一种语言的名称
  if (!form.translations['zh-tw'].name && !form.translations['en-us'].name && !form.translations['ja-jp'].name) {
    ElMessage.warning('请至少填写一种语言的支付方式名称')
    return
  }
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      // 设置默认name为英文名称
      const name = form.translations['en-us'].name || form.translations['zh-tw'].name || form.translations['ja-jp'].name
      const submitData = { ...form, name }

      if (isEdit.value) {
        await updatePaymentMethod(editId.value, submitData)
        ElMessage.success('更新成功')
      } else {
        await createPaymentMethod(submitData)
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

const handleDelete = async (row: PaymentMethod) => {
  try {
    await ElMessageBox.confirm(`确定删除"${row.name}"吗？`, '提示', { type: 'warning' })
    await deletePaymentMethod(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') console.error('删除失败:', error)
  }
}

const handleToggleStatus = async (row: PaymentMethod & { toggling?: boolean }) => {
  row.toggling = true
  try {
    const res: any = await togglePaymentMethodStatus(row.id)
    row.status = res.data.status
    ElMessage.success(res.msg || '状态已更新')
  } catch (error) {
    console.error('状态切换失败:', error)
  } finally {
    row.toggling = false
  }
}

onMounted(() => fetchList())
</script>

<style scoped>
.payment-method-list {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.lang-tabs {
  width: 100%;
}

.lang-tabs :deep(.el-tabs__content) {
  padding: 15px;
  background: #f9f9f9;
  border-radius: 4px;
}

.no-icon {
  color: #c0c4cc;
}

.image-tip {
  margin-left: 12px;
  font-size: 12px;
  color: #909399;
}

.sort-tip {
  margin-left: 12px;
  font-size: 12px;
  color: #909399;
}
</style>
