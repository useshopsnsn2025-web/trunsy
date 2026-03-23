<template>
  <div class="sell-faq-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>
            搜索
          </el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格 -->
    <el-card>
      <template #header>
        <div class="card-header">
          <span>出售常见问题列表</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            添加问题
          </el-button>
        </div>
      </template>

      <el-table :data="tableData" v-loading="loading" stripe row-key="id">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="问题（繁体中文）" min-width="250">
          <template #default="{ row }">
            <div class="question-text">{{ getTranslation(row, 'zh-tw', 'question') || '-' }}</div>
          </template>
        </el-table-column>
        <el-table-column label="答案（繁体中文）" min-width="300">
          <template #default="{ row }">
            <div class="answer-text" :title="getTranslation(row, 'zh-tw', 'answer')">
              {{ truncate(getTranslation(row, 'zh-tw', 'answer'), 80) || '-' }}
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="100">
          <template #default="{ row }">
            <el-input-number
              v-model="row.sort_order"
              :min="0"
              :max="9999"
              size="small"
              controls-position="right"
              @change="handleSortChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="pagination.total"
        layout="total, sizes, prev, pager, next, jumper"
        class="pagination"
        @size-change="loadData"
        @current-change="loadData"
      />
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="editingId ? '编辑常见问题' : '添加常见问题'"
      width="800px"
      :close-on-click-modal="false"
    >
      <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
        <el-tabs v-model="activeLocale" class="locale-tabs">
          <el-tab-pane label="繁體中文" name="zh-tw">
            <el-form-item label="問題" prop="question_zh" :rules="[{ required: true, message: '請輸入問題', trigger: 'blur' }]">
              <el-input v-model="formData.question_zh" placeholder="請輸入問題（繁體中文）" />
            </el-form-item>
            <el-form-item label="答案" prop="answer_zh" :rules="[{ required: true, message: '請輸入答案', trigger: 'blur' }]">
              <el-input
                v-model="formData.answer_zh"
                type="textarea"
                :rows="6"
                placeholder="請輸入答案（繁體中文）"
              />
            </el-form-item>
          </el-tab-pane>
          <el-tab-pane label="English" name="en-us">
            <el-form-item label="Question">
              <el-input v-model="formData.question_en" placeholder="Enter question (English)" />
            </el-form-item>
            <el-form-item label="Answer">
              <el-input
                v-model="formData.answer_en"
                type="textarea"
                :rows="6"
                placeholder="Enter answer (English)"
              />
            </el-form-item>
          </el-tab-pane>
          <el-tab-pane label="日本語" name="ja-jp">
            <el-form-item label="質問">
              <el-input v-model="formData.question_ja" placeholder="質問を入力してください（日本語）" />
            </el-form-item>
            <el-form-item label="回答">
              <el-input
                v-model="formData.answer_ja"
                type="textarea"
                :rows="6"
                placeholder="回答を入力してください（日本語）"
              />
            </el-form-item>
          </el-tab-pane>
        </el-tabs>

        <el-divider />

        <el-form-item label="排序">
          <el-input-number v-model="formData.sort_order" :min="0" :max="9999" />
          <span class="form-tip">数值越大排序越靠前</span>
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Search, Plus } from '@element-plus/icons-vue'
import {
  getSellFaqList,
  createSellFaq,
  updateSellFaq,
  deleteSellFaq,
  updateSellFaqStatus,
  type SellFaq
} from '@/api/sellFaq'

const loading = ref(false)
const tableData = ref<SellFaq[]>([])

const searchForm = reactive({
  status: '' as number | string
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 编辑对话框
const dialogVisible = ref(false)
const editingId = ref<number | null>(null)
const submitting = ref(false)
const formRef = ref<FormInstance>()
const activeLocale = ref('zh-tw')

const formData = reactive({
  question_zh: '',
  answer_zh: '',
  question_en: '',
  answer_en: '',
  question_ja: '',
  answer_ja: '',
  sort_order: 0,
  status: 1
})

const formRules: FormRules = {}

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const res: any = await getSellFaqList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchForm
    })
    tableData.value = res.data.list
    pagination.total = res.data.total
  } catch (error) {
    console.error('加载FAQ列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 获取翻译内容
const getTranslation = (row: SellFaq, locale: string, field: 'question' | 'answer'): string => {
  if (row.translations && row.translations[locale]) {
    return row.translations[locale][field] || ''
  }
  return ''
}

// 截断文本
const truncate = (text: string, maxLength: number): string => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return text.slice(0, maxLength) + '...'
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  loadData()
}

// 重置
const handleReset = () => {
  searchForm.status = ''
  handleSearch()
}

// 添加
const handleAdd = () => {
  editingId.value = null
  formData.question_zh = ''
  formData.answer_zh = ''
  formData.question_en = ''
  formData.answer_en = ''
  formData.question_ja = ''
  formData.answer_ja = ''
  formData.sort_order = 0
  formData.status = 1
  activeLocale.value = 'zh-tw'
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row: SellFaq) => {
  editingId.value = row.id
  formData.question_zh = getTranslation(row, 'zh-tw', 'question')
  formData.answer_zh = getTranslation(row, 'zh-tw', 'answer')
  formData.question_en = getTranslation(row, 'en-us', 'question')
  formData.answer_en = getTranslation(row, 'en-us', 'answer')
  formData.question_ja = getTranslation(row, 'ja-jp', 'question')
  formData.answer_ja = getTranslation(row, 'ja-jp', 'answer')
  formData.sort_order = row.sort_order
  formData.status = row.status
  activeLocale.value = 'zh-tw'
  dialogVisible.value = true
}

// 删除
const handleDelete = async (row: SellFaq) => {
  const question = getTranslation(row, 'zh-tw', 'question') || `ID: ${row.id}`
  try {
    await ElMessageBox.confirm(`确定要删除"${truncate(question, 30)}"吗？`, '确认删除', { type: 'warning' })
    await deleteSellFaq(row.id)
    ElMessage.success('删除成功')
    loadData()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

// 切换状态
const handleStatusChange = async (row: SellFaq) => {
  try {
    await updateSellFaqStatus(row.id, row.status)
    ElMessage.success('状态更新成功')
  } catch (error) {
    row.status = row.status === 1 ? 0 : 1
    console.error('更新状态失败:', error)
  }
}

// 排序变更
const handleSortChange = async (row: SellFaq) => {
  try {
    await updateSellFaq({
      id: row.id,
      sort_order: row.sort_order
    })
    ElMessage.success('排序更新成功')
  } catch (error) {
    console.error('更新排序失败:', error)
    loadData() // 刷新数据
  }
}

// 提交
const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return

    // 验证至少有一个语言的翻译
    const hasZh = formData.question_zh && formData.answer_zh
    const hasEn = formData.question_en && formData.answer_en
    const hasJa = formData.question_ja && formData.answer_ja

    if (!hasZh && !hasEn && !hasJa) {
      ElMessage.warning('请至少填写一种语言的问题和答案')
      return
    }

    submitting.value = true
    try {
      // 构建API需要的数据格式
      const translations: Record<string, { question: string; answer: string }> = {}

      if (formData.question_zh || formData.answer_zh) {
        translations['zh-tw'] = {
          question: formData.question_zh,
          answer: formData.answer_zh
        }
      }
      if (formData.question_en || formData.answer_en) {
        translations['en-us'] = {
          question: formData.question_en,
          answer: formData.answer_en
        }
      }
      if (formData.question_ja || formData.answer_ja) {
        translations['ja-jp'] = {
          question: formData.question_ja,
          answer: formData.answer_ja
        }
      }

      const submitData = {
        id: editingId.value || undefined,
        sort_order: formData.sort_order,
        status: formData.status,
        translations
      }

      if (editingId.value) {
        await updateSellFaq(submitData)
        ElMessage.success('更新成功')
      } else {
        await createSellFaq(submitData)
        ElMessage.success('创建成功')
      }
      dialogVisible.value = false
      loadData()
    } catch (error) {
      console.error('保存失败:', error)
    } finally {
      submitting.value = false
    }
  })
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.search-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pagination {
  margin-top: 20px;
  justify-content: flex-end;
}

.question-text {
  font-weight: 500;
  color: #303133;
}

.answer-text {
  color: #606266;
  font-size: 13px;
  line-height: 1.5;
}

.locale-tabs {
  margin-bottom: 10px;
}

.form-tip {
  margin-left: 12px;
  color: #909399;
  font-size: 12px;
}
</style>
