<template>
  <div class="service-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="名称/邮箱/电话" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="离线" :value="0" />
            <el-option label="在线" :value="1" />
            <el-option label="忙碌" :value="2" />
          </el-select>
        </el-form-item>
        <el-form-item label="启用">
          <el-select v-model="searchForm.is_enabled" placeholder="全部" clearable style="width: 100px">
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

    <!-- 表格区域 -->
    <el-card>
      <template #header>
        <div class="card-header">
          <span>客服列表</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            添加客服
          </el-button>
        </div>
      </template>

      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="头像" width="80">
          <template #default="{ row }">
            <el-avatar :size="40" :src="row.avatar" v-if="row.avatar" />
            <el-avatar :size="40" v-else>
              <el-icon><User /></el-icon>
            </el-avatar>
          </template>
        </el-table-column>
        <el-table-column label="客服名称" width="180">
          <template #default="{ row }">
            <div>{{ getTranslation(row, 'zh-tw') }}</div>
            <div v-if="getTranslation(row, 'en')" class="text-gray">{{ getTranslation(row, 'en') }}</div>
          </template>
        </el-table-column>
        <el-table-column label="关联账号" width="140">
          <template #default="{ row }">
            <span v-if="row.admin">{{ row.admin.nickname || row.admin.username }}</span>
            <el-tag v-else type="warning" size="small">未关联</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="联系方式" width="200">
          <template #default="{ row }">
            <div v-if="row.email">{{ row.email }}</div>
            <div v-if="row.phone">{{ row.phone }}</div>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="接待数" width="120">
          <template #default="{ row }">
            {{ row.current_sessions }} / {{ row.max_sessions }}
          </template>
        </el-table-column>
        <el-table-column label="启用" width="80">
          <template #default="{ row }">
            <el-switch
              v-model="row.is_enabled"
              :active-value="1"
              :inactive-value="0"
              @change="handleToggleEnabled(row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="last_online_at" label="最后在线" width="160" />
        <el-table-column prop="created_at" label="创建时间" width="160" />
        <el-table-column label="操作" width="180" fixed="right">
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
      :title="editingId ? '编辑客服' : '添加客服'"
      width="500px"
    >
      <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
        <el-form-item label="客服头像">
          <ImagePicker v-model="formData.avatar" width="80px" height="80px" />
          <div class="form-tip">建议上传正方形图片，推荐尺寸 200x200</div>
        </el-form-item>
        <el-form-item label="关联管理员" prop="admin_id">
          <el-select
            v-model="formData.admin_id"
            placeholder="选择关联的管理员账号"
            clearable
            filterable
            style="width: 100%"
          >
            <el-option
              v-for="admin in admins"
              :key="admin.id"
              :label="`${admin.nickname || admin.username} (${admin.username})`"
              :value="admin.id"
            />
          </el-select>
          <div class="form-tip">关联管理员后，该管理员登录后台可以接入客服会话</div>
        </el-form-item>
        <el-form-item label="名稱(繁體)" prop="name_zh" :rules="[{ required: true, message: '請輸入繁體中文名稱', trigger: 'blur' }]">
          <el-input v-model="formData.name_zh" placeholder="請輸入客服名稱（繁體中文）" />
        </el-form-item>
        <el-form-item label="名前(日本語)">
          <el-input v-model="formData.name_ja" placeholder="カスタマーサービス名を入力してください" />
        </el-form-item>
        <el-form-item label="名称(英文)">
          <el-input v-model="formData.name_en" placeholder="请输入客服名称（英文）" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="formData.email" placeholder="请输入邮箱" />
        </el-form-item>
        <el-form-item label="电话" prop="phone">
          <el-input v-model="formData.phone" placeholder="请输入电话" />
        </el-form-item>
        <el-form-item label="最大接待数" prop="max_sessions">
          <el-input-number v-model="formData.max_sessions" :min="1" :max="100" />
        </el-form-item>
        <el-form-item label="工作组">
          <el-checkbox-group v-model="formData.group_ids">
            <el-checkbox
              v-for="group in groups"
              :key="group.id"
              :value="group.id"
              :label="group.name"
            />
          </el-checkbox-group>
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="formData.is_enabled" :active-value="1" :inactive-value="0" />
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
import { Search, Plus, User } from '@element-plus/icons-vue'
import ImagePicker from '@/components/ImagePicker.vue'
import {
  getCustomerServiceList,
  createCustomerService,
  updateCustomerService,
  deleteCustomerService,
  getServiceGroups,
  type CustomerService,
  type ServiceGroup,
  type ServiceTranslation
} from '@/api/customerService'
import { getAdminList, type Admin } from '@/api/admin'

const loading = ref(false)
const tableData = ref<CustomerService[]>([])
const groups = ref<ServiceGroup[]>([])
const admins = ref<Admin[]>([])

const searchForm = reactive({
  keyword: '',
  status: '',
  is_enabled: ''
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 对话框
const dialogVisible = ref(false)
const editingId = ref<number | null>(null)
const submitting = ref(false)
const formRef = ref<FormInstance>()

const formData = reactive({
  admin_id: null as number | null,
  avatar: '',
  name_zh: '',
  name_ja: '',
  name_en: '',
  email: '',
  phone: '',
  max_sessions: 10,
  group_ids: [] as number[],
  is_enabled: 1
})

const formRules: FormRules = {}

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const res: any = await getCustomerServiceList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchForm
    })
    tableData.value = res.data.list
    pagination.total = res.data.total
  } catch (error) {
    console.error('加载客服列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 加载工作组
const loadGroups = async () => {
  try {
    const res: any = await getServiceGroups()
    groups.value = res.data
  } catch (error) {
    console.error('加载工作组失败:', error)
  }
}

// 加载管理员列表
const loadAdmins = async () => {
  try {
    const res: any = await getAdminList({ pageSize: 100, status: 1 })
    admins.value = res.data.list
  } catch (error) {
    console.error('加载管理员列表失败:', error)
  }
}

// 获取翻译名称
const getTranslation = (row: CustomerService, locale: string): string => {
  if (row.translations) {
    const translation = row.translations.find((t: ServiceTranslation) => t.locale === locale)
    if (translation) return translation.name
  }
  // 如果是繁体中文且没有翻译，返回name字段
  if (locale === 'zh-tw') {
    return row.name
  }
  return ''
}

// 获取状态类型
const getStatusType = (status: number) => {
  const types: Record<number, string> = {
    0: 'info',
    1: 'success',
    2: 'warning'
  }
  return types[status] || 'info'
}

// 获取状态文本
const getStatusText = (status: number) => {
  const texts: Record<number, string> = {
    0: '离线',
    1: '在线',
    2: '忙碌'
  }
  return texts[status] || '未知'
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  loadData()
}

// 重置
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.status = ''
  searchForm.is_enabled = ''
  handleSearch()
}

// 添加
const handleAdd = () => {
  editingId.value = null
  formData.admin_id = null
  formData.avatar = ''
  formData.name_zh = ''
  formData.name_ja = ''
  formData.name_en = ''
  formData.email = ''
  formData.phone = ''
  formData.max_sessions = 10
  formData.group_ids = []
  formData.is_enabled = 1
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row: CustomerService) => {
  editingId.value = row.id
  formData.admin_id = row.admin_id || null
  formData.avatar = row.avatar || ''
  // 从translations数组获取名称
  let nameZh = ''
  let nameJa = ''
  let nameEn = ''
  if (row.translations) {
    for (const t of row.translations) {
      if (t.locale === 'zh-tw') nameZh = t.name
      if (t.locale === 'ja-jp') nameJa = t.name
      if (t.locale === 'en') nameEn = t.name
    }
  }
  // 如果没有繁体中文翻译数据，使用name字段作为默认值
  formData.name_zh = nameZh || row.name || ''
  formData.name_ja = nameJa
  formData.name_en = nameEn
  formData.email = row.email || ''
  formData.phone = row.phone || ''
  formData.max_sessions = row.max_sessions
  formData.group_ids = row.groups?.map(g => g.id) || []
  formData.is_enabled = row.is_enabled
  dialogVisible.value = true
}

// 删除
const handleDelete = async (row: CustomerService) => {
  try {
    await ElMessageBox.confirm(`确定要删除客服"${row.name}"吗？`, '确认删除', { type: 'warning' })
    await deleteCustomerService(row.id)
    ElMessage.success('删除成功')
    loadData()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

// 切换启用状态
const handleToggleEnabled = async (row: CustomerService) => {
  try {
    await updateCustomerService(row.id, { is_enabled: row.is_enabled })
    ElMessage.success('更新成功')
  } catch (error) {
    row.is_enabled = row.is_enabled === 1 ? 0 : 1
    console.error('更新失败:', error)
  }
}

// 提交
const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return

    submitting.value = true
    try {
      // 构建API需要的数据格式
      const submitData = {
        admin_id: formData.admin_id,
        avatar: formData.avatar,
        email: formData.email,
        phone: formData.phone,
        max_sessions: formData.max_sessions,
        group_ids: formData.group_ids,
        is_enabled: formData.is_enabled,
        translations: {
          'zh-tw': { name: formData.name_zh },
          'ja-jp': { name: formData.name_ja },
          'en': { name: formData.name_en }
        }
      }

      if (editingId.value) {
        await updateCustomerService(editingId.value, submitData)
        ElMessage.success('更新成功')
      } else {
        await createCustomerService(submitData)
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
  loadGroups()
  loadAdmins()
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

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.text-gray {
  font-size: 12px;
  color: #909399;
}
</style>
