<template>
  <div class="role-list">
    <!-- 搜索区域 -->
    <el-card class="search-card" shadow="never">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="角色名称/标识"
            clearable
            @keyup.enter="handleSearch"
          />
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

    <!-- 工具栏 -->
    <el-card class="table-card" shadow="never">
      <template #header>
        <div class="card-header">
          <span>角色列表</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增角色
          </el-button>
        </div>
      </template>

      <!-- 表格 -->
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="角色名称" width="150" />
        <el-table-column prop="code" label="角色标识" width="150" />
        <el-table-column prop="description" label="描述" min-width="200" />
        <el-table-column prop="permissions" label="权限" min-width="200">
          <template #default="{ row }">
            <div class="permissions-tags">
              <template v-if="row.permissions && row.permissions.length">
                <el-tag
                  v-for="(perm, index) in row.permissions.slice(0, 3)"
                  :key="index"
                  size="small"
                  class="permission-tag"
                >
                  {{ perm === '*' ? '全部权限' : perm }}
                </el-tag>
                <el-tag v-if="row.permissions.length > 3" size="small" type="info">
                  +{{ row.permissions.length - 3 }}
                </el-tag>
              </template>
              <span v-else class="text-muted">无权限</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
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

      <!-- 分页 -->
      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="formDialogVisible"
      :title="isEdit ? '编辑角色' : '新增角色'"
      width="600px"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="角色名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入角色名称" />
        </el-form-item>
        <el-form-item label="角色标识" prop="code">
          <el-input v-model="form.code" :disabled="isEdit" placeholder="请输入角色标识（英文）" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input v-model="form.description" type="textarea" :rows="2" placeholder="请输入描述" />
        </el-form-item>
        <el-form-item label="权限" prop="permissions">
          <div class="permissions-select">
            <el-checkbox
              v-model="allPermissionsChecked"
              :indeterminate="isIndeterminate"
              @change="handleCheckAllChange"
            >
              全选
            </el-checkbox>
            <el-divider />
            <el-checkbox-group v-model="form.permissions" @change="handlePermissionChange">
              <div v-for="group in permissionGroups" :key="group.key" class="permission-group">
                <div class="group-title">{{ group.name }}</div>
                <div class="group-items">
                  <el-checkbox
                    v-for="perm in group.children"
                    :key="perm.key"
                    :label="perm.key"
                  >
                    {{ perm.name }}
                  </el-checkbox>
                </div>
              </div>
            </el-checkbox-group>
          </div>
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
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
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getRoleList,
  createRole,
  updateRole,
  deleteRole,
  getPermissions,
  type Role,
  type Permission
} from '@/api/role'

// 搜索表单
const searchForm = reactive({
  keyword: '',
  status: '' as number | string
})

// 列表数据
const list = ref<Role[]>([])
const loading = ref(false)
const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 权限列表
const permissionGroups = ref<Permission[]>([])
const allPermissions = computed(() => {
  const perms: string[] = []
  permissionGroups.value.forEach(group => {
    if (group.children) {
      group.children.forEach(child => {
        perms.push(child.key)
      })
    }
  })
  return perms
})

// 表单相关
const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)

const form = reactive({
  name: '',
  code: '',
  description: '',
  permissions: [] as string[],
  sort: 0,
  status: 1
})

const rules: FormRules = {
  name: [
    { required: true, message: '请输入角色名称', trigger: 'blur' },
    { max: 50, message: '角色名称不超过50个字符', trigger: 'blur' }
  ],
  code: [
    { required: true, message: '请输入角色标识', trigger: 'blur' },
    { pattern: /^[a-zA-Z_][a-zA-Z0-9_]*$/, message: '角色标识只能包含字母、数字和下划线', trigger: 'blur' }
  ]
}

// 全选状态
const allPermissionsChecked = computed({
  get: () => form.permissions.length === allPermissions.value.length && allPermissions.value.length > 0,
  set: () => {}
})

const isIndeterminate = computed(() => {
  return form.permissions.length > 0 && form.permissions.length < allPermissions.value.length
})

// 获取权限列表
const fetchPermissions = async () => {
  try {
    const res: any = await getPermissions()
    permissionGroups.value = res.data || []
  } catch (error) {
    console.error('获取权限列表失败:', error)
  }
}

// 获取列表
const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getRoleList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchForm
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取角色列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  fetchList()
}

// 重置
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.status = ''
  handleSearch()
}

// 分页
const handleSizeChange = (size: number) => {
  pagination.pageSize = size
  fetchList()
}

const handleCurrentChange = (page: number) => {
  pagination.page = page
  fetchList()
}

// 全选切换
const handleCheckAllChange = (val: boolean) => {
  form.permissions = val ? [...allPermissions.value] : []
}

// 权限选择变化
const handlePermissionChange = () => {
  // 触发响应式更新
}

// 新增
const handleAdd = () => {
  isEdit.value = false
  formDialogVisible.value = true
  Object.assign(form, {
    name: '',
    code: '',
    description: '',
    permissions: [],
    sort: 0,
    status: 1
  })
}

// 编辑
const handleEdit = (row: Role) => {
  isEdit.value = true
  editId.value = row.id
  formDialogVisible.value = true
  Object.assign(form, {
    name: row.name,
    code: row.code,
    description: row.description || '',
    permissions: row.permissions || [],
    sort: row.sort,
    status: row.status
  })
}

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      if (isEdit.value) {
        const { code, ...updateData } = form
        await updateRole(editId.value, updateData)
        ElMessage.success('更新成功')
      } else {
        await createRole(form)
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
const handleDelete = async (row: Role) => {
  try {
    await ElMessageBox.confirm(`确定要删除角色"${row.name}"吗？`, '提示', {
      type: 'warning'
    })
    await deleteRole(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

onMounted(() => {
  fetchPermissions()
  fetchList()
})
</script>

<style scoped>
.role-list {
  padding: 20px;
}

.search-card {
  margin-bottom: 20px;
}

.search-form {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
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

.permissions-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.permission-tag {
  margin-right: 4px;
}

.text-muted {
  color: #999;
  font-size: 12px;
}

.permissions-select {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  padding: 10px;
  max-height: 300px;
  overflow-y: auto;
}

.permission-group {
  margin-bottom: 15px;
}

.group-title {
  font-weight: bold;
  margin-bottom: 8px;
  color: #409eff;
}

.group-items {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  padding-left: 10px;
}
</style>
