<template>
  <div class="installment-plan-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="状态">
          <el-select v-model="searchForm.is_enabled" placeholder="全部" clearable style="width: 120px">
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">查询</el-button>
          <el-button @click="resetSearch">重置</el-button>
          <el-button type="success" @click="handleAdd">新增方案</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card">
      <el-table :data="tableData" v-loading="loading" border stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="方案名稱(繁體)" min-width="150">
          <template #default="{ row }">{{ getTranslation(row, 'zh-tw') || getTranslation(row, 'zh-cn') || row.name || '-' }}</template>
        </el-table-column>
        <el-table-column label="方案名称(英文)" min-width="150">
          <template #default="{ row }">{{ getTranslation(row, 'en') || '-' }}</template>
        </el-table-column>
        <el-table-column label="方案名(日本語)" min-width="150">
          <template #default="{ row }">{{ getTranslation(row, 'ja-jp') || '-' }}</template>
        </el-table-column>
        <el-table-column label="期数" width="80">
          <template #default="{ row }">{{ row.periods }}期</template>
        </el-table-column>
        <el-table-column label="利率" width="100">
          <template #default="{ row }">
            <span v-if="row.interest_rate === 0" class="success">免息</span>
            <span v-else>{{ (row.interest_rate * 100).toFixed(2) }}%</span>
          </template>
        </el-table-column>
        <el-table-column label="手续费率" width="100">
          <template #default="{ row }">{{ (row.fee_rate * 100).toFixed(2) }}%</template>
        </el-table-column>
        <el-table-column label="最低金额" width="120">
          <template #default="{ row }">${{ formatNumber(row.min_amount) }}</template>
        </el-table-column>
        <el-table-column label="最高金额" width="120">
          <template #default="{ row }">
            {{ row.max_amount ? '$' + formatNumber(row.max_amount) : '不限' }}
          </template>
        </el-table-column>
        <el-table-column label="最低信用等级" width="140">
          <template #default="{ row }">
            <el-rate v-model="row.min_credit_level" disabled :max="5" />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-switch
              v-model="row.is_enabled"
              :active-value="1"
              :inactive-value="0"
              @change="handleToggleStatus(row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="160" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadData"
          @current-change="loadData"
        />
      </div>
    </el-card>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="editDialogVisible" :title="editForm.id ? '编辑分期方案' : '新增分期方案'" width="600px">
      <el-form ref="formRef" :model="editForm" :rules="formRules" label-width="120px">
        <el-form-item label="方案名稱(繁體)" prop="name_zh">
          <el-input v-model="editForm.name_zh" placeholder="如：3期免息" />
        </el-form-item>
        <el-form-item label="方案名称(英文)" prop="name_en">
          <el-input v-model="editForm.name_en" placeholder="如：3 Months Interest-Free" />
        </el-form-item>
        <el-form-item label="方案名(日本語)">
          <el-input v-model="editForm.name_ja" placeholder="例：3回払い無利息" />
        </el-form-item>
        <el-form-item label="方案描述(繁體)">
          <el-input v-model="editForm.description_zh" type="textarea" :rows="2" placeholder="方案描述（繁體中文，可選）" />
        </el-form-item>
        <el-form-item label="Description">
          <el-input v-model="editForm.description_en" type="textarea" :rows="2" placeholder="Description (English, optional)" />
        </el-form-item>
        <el-form-item label="説明(日本語)">
          <el-input v-model="editForm.description_ja" type="textarea" :rows="2" placeholder="説明（日本語、任意）" />
        </el-form-item>
        <el-form-item label="分期期数" prop="periods">
          <el-input-number v-model="editForm.periods" :min="1" :max="36" />
          <span class="form-tip">期</span>
        </el-form-item>
        <el-form-item label="月利率" prop="interest_rate">
          <el-input-number v-model="editForm.interest_rate" :min="0" :max="1" :step="0.001" :precision="4" />
          <span class="form-tip">（0表示免息，0.01表示1%）</span>
        </el-form-item>
        <el-form-item label="手续费率" prop="fee_rate">
          <el-input-number v-model="editForm.fee_rate" :min="0" :max="1" :step="0.001" :precision="4" />
          <span class="form-tip">（一次性收取，0.02表示2%）</span>
        </el-form-item>
        <el-form-item label="最低金额" prop="min_amount">
          <el-input-number v-model="editForm.min_amount" :min="0" :step="100" />
          <span class="form-tip">美元</span>
        </el-form-item>
        <el-form-item label="最高金额">
          <el-input-number v-model="editForm.max_amount" :min="0" :step="100" />
          <span class="form-tip">美元（0表示不限）</span>
        </el-form-item>
        <el-form-item label="最低信用等级" prop="min_credit_level">
          <el-rate v-model="editForm.min_credit_level" :max="5" show-text :texts="['1级', '2级', '3级', '4级', '5级']" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="editForm.sort" :min="0" />
          <span class="form-tip">数值越大越靠前</span>
        </el-form-item>
        <el-form-item label="启用状态">
          <el-switch v-model="editForm.is_enabled" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>

      <!-- 试算预览 -->
      <el-divider>试算预览（以$1000为例）</el-divider>
      <el-descriptions :column="2" border size="small">
        <el-descriptions-item label="分期金额">$1,000.00</el-descriptions-item>
        <el-descriptions-item label="期数">{{ editForm.periods }}期</el-descriptions-item>
        <el-descriptions-item label="每期金额">${{ formatNumber(previewPeriodAmount) }}</el-descriptions-item>
        <el-descriptions-item label="总还款额">${{ formatNumber(previewTotalAmount) }}</el-descriptions-item>
        <el-descriptions-item label="总利息">${{ formatNumber(previewTotalInterest) }}</el-descriptions-item>
        <el-descriptions-item label="总手续费">${{ formatNumber(previewTotalFee) }}</el-descriptions-item>
      </el-descriptions>

      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitLoading">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  getInstallmentPlanList,
  createInstallmentPlan,
  updateInstallmentPlan,
  deleteInstallmentPlan,
  toggleInstallmentPlanStatus,
  type InstallmentPlan,
  type InstallmentPlanTranslation
} from '@/api/credit'

const loading = ref(false)
const submitLoading = ref(false)
const editDialogVisible = ref(false)
const formRef = ref<FormInstance>()
const tableData = ref<InstallmentPlan[]>([])

const searchForm = reactive({
  is_enabled: '' as number | string
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const editForm = reactive({
  id: 0,
  name_zh: '',
  name_en: '',
  name_ja: '',
  description_zh: '',
  description_en: '',
  description_ja: '',
  periods: 3,
  interest_rate: 0,
  fee_rate: 0.02,
  min_amount: 100,
  max_amount: 0,
  min_credit_level: 1,
  sort: 0,
  is_enabled: 1
})

const formRules: FormRules = {
  name_zh: [{ required: true, message: '請輸入繁體中文方案名稱', trigger: 'blur' }],
  periods: [{ required: true, message: '请输入期数', trigger: 'blur' }],
  interest_rate: [{ required: true, message: '请输入利率', trigger: 'blur' }],
  fee_rate: [{ required: true, message: '请输入手续费率', trigger: 'blur' }],
  min_amount: [{ required: true, message: '请输入最低金额', trigger: 'blur' }],
  min_credit_level: [{ required: true, message: '请选择最低信用等级', trigger: 'change' }]
}

// 试算预览
const previewTotalFee = computed(() => 1000 * editForm.fee_rate)
const previewPeriodAmount = computed(() => {
  const principal = 1000
  const fee = previewTotalFee.value
  const totalWithFee = principal + fee

  if (editForm.interest_rate === 0) {
    return totalWithFee / editForm.periods
  }

  const monthlyRate = editForm.interest_rate
  const factor = Math.pow(1 + monthlyRate, editForm.periods)
  return (totalWithFee * monthlyRate * factor) / (factor - 1)
})
const previewTotalAmount = computed(() => previewPeriodAmount.value * editForm.periods)
const previewTotalInterest = computed(() => Math.max(0, previewTotalAmount.value - 1000 - previewTotalFee.value))

const formatNumber = (num: number) => {
  return num?.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00'
}

// 从翻译数组中获取指定语言的名称
const getTranslation = (row: InstallmentPlan, locale: string): string => {
  if (row.translations && Array.isArray(row.translations)) {
    const t = row.translations.find((item: InstallmentPlanTranslation) => item.locale === locale)
    return t?.name || ''
  }
  return ''
}

const loadData = async () => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.page,
      pageSize: pagination.pageSize,
      is_enabled: searchForm.is_enabled
    }
    const res: any = await getInstallmentPlanList(params)
    tableData.value = res.data.list || res.data
    pagination.total = res.data.total || tableData.value.length
  } finally {
    loading.value = false
  }
}

const resetSearch = () => {
  searchForm.is_enabled = ''
  pagination.page = 1
  loadData()
}

const handleAdd = () => {
  Object.assign(editForm, {
    id: 0,
    name_zh: '',
    name_en: '',
    name_ja: '',
    description_zh: '',
    description_en: '',
    description_ja: '',
    periods: 3,
    interest_rate: 0,
    fee_rate: 0.02,
    min_amount: 100,
    max_amount: 0,
    min_credit_level: 1,
    sort: 0,
    is_enabled: 1
  })
  editDialogVisible.value = true
}

const handleEdit = (row: InstallmentPlan) => {
  // 从translations数组中解析各语言的名称和描述
  let nameZh = '', nameEn = '', nameJa = ''
  let descZh = '', descEn = '', descJa = ''

  if (row.translations && Array.isArray(row.translations)) {
    for (const t of row.translations) {
      if (t.locale === 'zh-tw' || t.locale === 'zh-cn') {
        nameZh = t.name || ''
        descZh = t.description || ''
      } else if (t.locale === 'en') {
        nameEn = t.name || ''
        descEn = t.description || ''
      } else if (t.locale === 'ja-jp') {
        nameJa = t.name || ''
        descJa = t.description || ''
      }
    }
  }

  // 兼容旧数据格式
  if (!nameZh && row.name) nameZh = row.name
  if (!descZh && row.description) descZh = row.description

  Object.assign(editForm, {
    id: row.id,
    name_zh: nameZh,
    name_en: nameEn,
    name_ja: nameJa,
    description_zh: descZh,
    description_en: descEn,
    description_ja: descJa,
    periods: Number(row.periods) || 3,
    interest_rate: Number(row.interest_rate) || 0,
    fee_rate: Number(row.fee_rate) || 0,
    min_amount: Number(row.min_amount) || 0,
    max_amount: Number(row.max_amount) || 0,
    min_credit_level: Number(row.min_credit_level) || 1,
    sort: Number(row.sort) || 0,
    is_enabled: row.is_enabled
  })
  editDialogVisible.value = true
}

const handleDelete = async (row: InstallmentPlan) => {
  await ElMessageBox.confirm('确定删除该分期方案吗？', '提示', { type: 'warning' })
  await deleteInstallmentPlan(row.id)
  ElMessage.success('删除成功')
  loadData()
}

const handleToggleStatus = async (row: InstallmentPlan) => {
  try {
    await toggleInstallmentPlanStatus(row.id)
    ElMessage.success(row.is_enabled ? '已启用' : '已禁用')
  } catch (e) {
    // 恢复状态
    row.is_enabled = row.is_enabled ? 0 : 1
  }
}

const submitForm = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return

    submitLoading.value = true
    try {
      // 构建翻译数组格式（后端期望的格式）
      const translations = []
      if (editForm.name_zh) {
        translations.push({ locale: 'zh-tw', name: editForm.name_zh, description: editForm.description_zh || '' })
      }
      if (editForm.name_en) {
        translations.push({ locale: 'en', name: editForm.name_en, description: editForm.description_en || '' })
      }
      if (editForm.name_ja) {
        translations.push({ locale: 'ja-jp', name: editForm.name_ja, description: editForm.description_ja || '' })
      }

      const data = {
        translations,
        periods: editForm.periods,
        interest_rate: editForm.interest_rate,
        fee_rate: editForm.fee_rate,
        min_amount: editForm.min_amount,
        max_amount: editForm.max_amount || null,
        min_credit_level: editForm.min_credit_level,
        sort: editForm.sort,
        status: editForm.is_enabled
      }

      if (editForm.id) {
        await updateInstallmentPlan(editForm.id, data)
        ElMessage.success('更新成功')
      } else {
        await createInstallmentPlan(data)
        ElMessage.success('创建成功')
      }
      editDialogVisible.value = false
      loadData()
    } finally {
      submitLoading.value = false
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

.table-card {
  min-height: 400px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.success {
  color: #67C23A;
  font-weight: bold;
}

.form-tip {
  margin-left: 10px;
  color: #999;
  font-size: 12px;
}
</style>
