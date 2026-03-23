<template>
  <div class="currency-list">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>货币管理</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增货币
          </el-button>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="code" label="货币代码" width="120">
          <template #default="{ row }">
            <el-tag type="info" size="small">{{ row.code }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="symbol" label="符号" width="100" align="center">
          <template #default="{ row }">
            <span class="currency-symbol">{{ row.symbol }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="名称" min-width="150" />
        <el-table-column prop="decimals" label="小数位" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.decimals === 0 ? 'warning' : 'success'" size="small">
              {{ row.decimals }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column prop="is_active" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              :model-value="row.is_active"
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
    <el-dialog v-model="formDialogVisible" :title="isEdit ? '编辑货币' : '新增货币'" width="500px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="货币代码" prop="code">
          <el-input
            v-model="form.code"
            :disabled="isEdit"
            placeholder="如：KRW, BRL"
            maxlength="3"
            style="width: 120px;"
          />
          <div class="form-tip">3位大写字母，如 USD, EUR, JPY</div>
        </el-form-item>

        <el-form-item label="货币名称" prop="name">
          <el-input v-model="form.name" placeholder="如：Korean Won" />
        </el-form-item>

        <el-form-item label="货币符号" prop="symbol">
          <el-input v-model="form.symbol" placeholder="如：₩, R$" style="width: 120px;" />
          <div class="form-tip">显示在金额前的符号</div>
        </el-form-item>

        <el-form-item label="小数位数" prop="decimals">
          <el-input-number v-model="form.decimals" :min="0" :max="4" />
          <div class="form-tip">日元、韩元等通常为 0</div>
        </el-form-item>

        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>

        <el-form-item label="状态" prop="is_active">
          <el-switch v-model="form.is_active" />
          <span class="switch-label">{{ form.is_active ? '启用' : '禁用' }}</span>
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
import {
  getCurrencyList,
  getCurrencyDetail,
  createCurrency,
  updateCurrency,
  deleteCurrency,
  updateCurrencyStatus,
  type Currency
} from '@/api/currency'

type CurrencyWithStatus = Currency & { toggling?: boolean }

const list = ref<CurrencyWithStatus[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)

const getDefaultForm = () => ({
  code: '',
  name: '',
  symbol: '',
  decimals: 2,
  sort: 0,
  is_active: true
})

const form = reactive(getDefaultForm())

const rules: FormRules = {
  code: [
    { required: true, message: '请输入货币代码', trigger: 'blur' },
    { pattern: /^[A-Za-z]{3}$/, message: '格式不正确，需要3位字母', trigger: 'blur' }
  ],
  name: [
    { required: true, message: '请输入货币名称', trigger: 'blur' }
  ],
  symbol: [
    { required: true, message: '请输入货币符号', trigger: 'blur' }
  ]
}

onMounted(() => {
  fetchList()
})

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getCurrencyList({
      page: pagination.page,
      pageSize: pagination.pageSize
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  isEdit.value = false
  editId.value = 0
  Object.assign(form, getDefaultForm())
  if (list.value.length > 0) {
    form.sort = Math.max(...list.value.map(l => l.sort)) + 1
  }
  formDialogVisible.value = true
}

const handleEdit = async (row: Currency) => {
  isEdit.value = true
  editId.value = row.id
  try {
    const res: any = await getCurrencyDetail(row.id)
    const data = res.data
    Object.assign(form, {
      code: data.code,
      name: data.name,
      symbol: data.symbol,
      decimals: data.decimals,
      sort: data.sort,
      is_active: data.is_active
    })
    formDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取货币详情失败')
  }
}

const handleSubmit = async () => {
  try {
    await formRef.value?.validate()
  } catch (e) {
    return
  }

  submitting.value = true
  try {
    const submitData = {
      code: form.code.toUpperCase(),
      name: form.name,
      symbol: form.symbol,
      decimals: form.decimals,
      sort: form.sort,
      is_active: form.is_active ? 1 : 0
    }

    if (isEdit.value) {
      await updateCurrency(editId.value, submitData)
      ElMessage.success('更新成功')
    } else {
      await createCurrency(submitData)
      ElMessage.success('创建成功')
    }
    formDialogVisible.value = false
    fetchList()
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || e?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleToggleStatus = async (row: CurrencyWithStatus) => {
  row.toggling = true
  try {
    await updateCurrencyStatus(row.id)
    row.is_active = !row.is_active
    ElMessage.success('状态已更新')
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || '操作失败')
  } finally {
    row.toggling = false
  }
}

const handleDelete = async (row: Currency) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除货币"${row.code}"吗？`,
      '删除货币',
      { type: 'warning' }
    )
    await deleteCurrency(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e?.response?.data?.message || '删除失败')
    }
  }
}
</script>

<style scoped>
.currency-list {
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

.currency-symbol {
  font-size: 18px;
  font-weight: 600;
  color: #409eff;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.switch-label {
  margin-left: 10px;
  color: #606266;
}
</style>
