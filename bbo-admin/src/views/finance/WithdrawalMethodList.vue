<template>
  <div class="withdrawal-method-list">
    <!-- 搜索区域 -->
    <el-card class="search-card" shadow="never">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="标识">
          <el-input v-model="searchForm.code" placeholder="提现方式标识" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable>
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格 -->
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>提现方式列表</span>
          <el-button type="primary" @click="handleAdd">新增提现方式</el-button>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="Logo" width="80">
          <template #default="{ row }">
            <el-image
              v-if="row.logo"
              :src="row.logo"
              style="width: 40px; height: 40px"
              fit="contain"
            />
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="code" label="标识" width="120" />
        <el-table-column prop="name" label="名称" min-width="120" />
        <el-table-column prop="route_path" label="APP路由" min-width="180" show-overflow-tooltip />
        <el-table-column label="支持国家" min-width="150">
          <template #default="{ row }">
            <el-tooltip v-if="row.country_names" :content="row.country_names" placement="top">
              <span>{{ row.country_count }}个国家</span>
            </el-tooltip>
            <span v-else class="text-muted">未配置</span>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-switch
              :model-value="row.status === 1"
              @change="handleToggleStatus(row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="170" />
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
          layout="total, sizes, prev, pager, next"
          @size-change="fetchList"
          @current-change="fetchList"
        />
      </div>
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      destroy-on-close
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="标识" prop="code">
          <el-input
            v-model="form.code"
            placeholder="小写字母和下划线，如 bank_card"
            :disabled="!!editingId"
          />
        </el-form-item>
        <el-form-item label="Logo" prop="logo">
          <ImagePicker v-model="form.logo" width="80px" height="80px" />
        </el-form-item>
        <el-form-item label="APP路由" prop="route_path">
          <el-input v-model="form.route_path" placeholder="APP端页面路径，如 /pages/wallet/bindCard" />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>

        <el-divider content-position="left">多语言名称</el-divider>

        <el-form-item label="繁体中文" prop="translations.zh-tw">
          <el-input v-model="form.translations['zh-tw']" placeholder="繁体中文名称" />
        </el-form-item>
        <el-form-item label="英文" prop="translations.en-us">
          <el-input v-model="form.translations['en-us']" placeholder="英文名称" />
        </el-form-item>
        <el-form-item label="日文" prop="translations.ja-jp">
          <el-input v-model="form.translations['ja-jp']" placeholder="日文名称" />
        </el-form-item>

        <el-divider content-position="left">支持国家</el-divider>

        <el-form-item label="国家" prop="country_ids">
          <el-select
            v-model="form.country_ids"
            multiple
            placeholder="选择支持的国家"
            style="width: 100%"
          >
            <el-option
              v-for="country in countryOptions"
              :key="country.id"
              :label="country.name"
              :value="country.id"
            />
          </el-select>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import ImagePicker from '@/components/ImagePicker.vue'
import {
  getWithdrawalMethodList,
  getWithdrawalMethod,
  createWithdrawalMethod,
  updateWithdrawalMethod,
  deleteWithdrawalMethod,
  toggleWithdrawalMethodStatus,
  getCountryOptions,
  type WithdrawalMethod,
  type CountryOption
} from '@/api/withdrawalMethod'

const searchForm = reactive({
  code: '',
  status: '' as number | string
})

const list = ref<WithdrawalMethod[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

const dialogVisible = ref(false)
const editingId = ref<number | null>(null)
const submitting = ref(false)
const formRef = ref<FormInstance>()
const countryOptions = ref<CountryOption[]>([])

const dialogTitle = computed(() => editingId.value ? '编辑提现方式' : '新增提现方式')

const form = reactive({
  code: '',
  logo: '',
  route_path: '',
  sort: 0,
  status: 1,
  translations: {
    'zh-tw': '',
    'en-us': '',
    'ja-jp': ''
  } as Record<string, string>,
  country_ids: [] as number[]
})

const rules: FormRules = {
  code: [
    { required: true, message: '请输入标识', trigger: 'blur' },
    { pattern: /^[a-z_]+$/, message: '只能包含小写字母和下划线', trigger: 'blur' }
  ],
  'translations.zh-tw': [
    { required: true, message: '请输入繁体中文名称', trigger: 'blur' }
  ],
  'translations.en-us': [
    { required: true, message: '请输入英文名称', trigger: 'blur' }
  ]
}

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getWithdrawalMethodList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchForm
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取列表失败:', error)
  } finally {
    loading.value = false
  }
}

const fetchCountryOptions = async () => {
  try {
    const res: any = await getCountryOptions()
    countryOptions.value = res.data || []
  } catch (error) {
    console.error('获取国家列表失败:', error)
  }
}

const handleSearch = () => {
  pagination.page = 1
  fetchList()
}

const handleReset = () => {
  searchForm.code = ''
  searchForm.status = ''
  handleSearch()
}

const resetForm = () => {
  form.code = ''
  form.logo = ''
  form.route_path = ''
  form.sort = 0
  form.status = 1
  form.translations = { 'zh-tw': '', 'en-us': '', 'ja-jp': '' }
  form.country_ids = []
}

const handleAdd = () => {
  editingId.value = null
  resetForm()
  dialogVisible.value = true
}

const handleEdit = async (row: WithdrawalMethod) => {
  editingId.value = row.id
  resetForm()

  try {
    const res: any = await getWithdrawalMethod(row.id)
    const data = res.data

    form.code = data.code
    form.logo = data.logo || ''
    form.route_path = data.route_path || ''
    form.sort = data.sort
    form.status = data.status
    form.translations = data.translations || { 'zh-tw': '', 'en-us': '', 'ja-jp': '' }
    form.country_ids = data.country_ids || []

    dialogVisible.value = true
  } catch (error) {
    console.error('获取详情失败:', error)
    ElMessage.error('获取详情失败')
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return

  try {
    await formRef.value.validate()
  } catch {
    return
  }

  submitting.value = true
  try {
    const data = {
      code: form.code,
      logo: form.logo,
      route_path: form.route_path,
      sort: form.sort,
      status: form.status,
      translations: form.translations,
      country_ids: form.country_ids
    }

    if (editingId.value) {
      await updateWithdrawalMethod(editingId.value, data)
      ElMessage.success('更新成功')
    } else {
      await createWithdrawalMethod(data)
      ElMessage.success('创建成功')
    }

    dialogVisible.value = false
    fetchList()
  } catch (error: any) {
    ElMessage.error(error.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleDelete = async (row: WithdrawalMethod) => {
  try {
    await ElMessageBox.confirm('确定删除该提现方式吗？', '提示', { type: 'warning' })
    await deleteWithdrawalMethod(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

const handleToggleStatus = async (row: WithdrawalMethod) => {
  try {
    await toggleWithdrawalMethodStatus(row.id)
    ElMessage.success(row.status === 1 ? '已禁用' : '已启用')
    fetchList()
  } catch (error) {
    console.error('切换状态失败:', error)
  }
}

onMounted(() => {
  fetchCountryOptions()
  fetchList()
})
</script>

<style scoped>
.withdrawal-method-list {
  padding: 20px;
}

.search-card {
  margin-bottom: 20px;
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

.text-muted {
  color: #909399;
}
</style>
