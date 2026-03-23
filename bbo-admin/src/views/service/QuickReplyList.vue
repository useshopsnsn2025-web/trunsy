<template>
  <div class="quick-reply-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="标题/内容" clearable />
        </el-form-item>
        <el-form-item label="分组">
          <el-select v-model="searchForm.group_id" placeholder="全部" clearable style="width: 150px">
            <el-option
              v-for="group in groups"
              :key="group.id"
              :label="group.name"
              :value="group.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
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

    <!-- 表格 -->
    <el-card>
      <template #header>
        <div class="card-header">
          <span>快捷回复列表</span>
          <div>
            <el-button @click="handleManageGroups">管理分组</el-button>
            <el-button type="primary" @click="handleAdd">
              <el-icon><Plus /></el-icon>
              添加回复
            </el-button>
          </div>
        </div>
      </template>

      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="標題" width="200">
          <template #default="{ row }">
            <div>{{ getTranslation(row, 'zh-tw', 'title') }}</div>
            <div v-if="getTranslation(row, 'en', 'title')" class="text-gray">{{ getTranslation(row, 'en', 'title') }}</div>
          </template>
        </el-table-column>
        <el-table-column label="內容" min-width="300" show-overflow-tooltip>
          <template #default="{ row }">
            {{ getTranslation(row, 'zh-tw', 'content') }}
          </template>
        </el-table-column>
        <el-table-column label="分组" width="120">
          <template #default="{ row }">
            {{ row.group?.name || '未分组' }}
          </template>
        </el-table-column>
        <el-table-column prop="shortcut" label="快捷键" width="100" />
        <el-table-column prop="use_count" label="使用次数" width="100" />
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
      :title="editingId ? '编辑快捷回复' : '添加快捷回复'"
      width="700px"
    >
      <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
        <el-form-item label="分组">
          <el-select v-model="formData.group_id" placeholder="选择分组" clearable style="width: 100%">
            <el-option
              v-for="group in groups"
              :key="group.id"
              :label="group.name"
              :value="group.id"
            />
          </el-select>
        </el-form-item>

        <el-tabs v-model="activeLocale" class="locale-tabs">
          <el-tab-pane label="繁體中文" name="zh-tw">
            <el-form-item label="標題" prop="title_zh" :rules="[{ required: true, message: '請輸入繁體中文標題', trigger: 'blur' }]">
              <el-input v-model="formData.title_zh" placeholder="請輸入標題（繁體中文）" />
            </el-form-item>
            <el-form-item label="內容" prop="content_zh" :rules="[{ required: true, message: '請輸入繁體中文內容', trigger: 'blur' }]">
              <el-input
                v-model="formData.content_zh"
                type="textarea"
                :rows="5"
                placeholder="請輸入回覆內容（繁體中文）"
              />
            </el-form-item>
          </el-tab-pane>
          <el-tab-pane label="English" name="en">
            <el-form-item label="Title">
              <el-input v-model="formData.title_en" placeholder="Enter title (English)" />
            </el-form-item>
            <el-form-item label="Content">
              <el-input
                v-model="formData.content_en"
                type="textarea"
                :rows="5"
                placeholder="Enter reply content (English)"
              />
            </el-form-item>
          </el-tab-pane>
          <el-tab-pane label="日本語" name="ja-jp">
            <el-form-item label="タイトル">
              <el-input v-model="formData.title_ja" placeholder="タイトルを入力してください（日本語）" />
            </el-form-item>
            <el-form-item label="内容">
              <el-input
                v-model="formData.content_ja"
                type="textarea"
                :rows="5"
                placeholder="返信内容を入力してください（日本語）"
              />
            </el-form-item>
          </el-tab-pane>
        </el-tabs>

        <el-form-item label="快捷键">
          <el-input v-model="formData.shortcut" placeholder="如: /hello" style="width: 200px" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="formData.sort" :min="0" />
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

    <!-- 分组管理对话框 -->
    <el-dialog v-model="groupDialogVisible" title="管理分组" width="500px">
      <div class="group-list">
        <div v-for="group in groups" :key="group.id" class="group-item">
          <el-input
            v-if="editingGroupId === group.id"
            v-model="editingGroupName"
            size="small"
            style="width: 200px"
            @keyup.enter="saveGroup"
          />
          <span v-else>{{ group.name }}</span>
          <div class="group-actions">
            <el-button
              v-if="editingGroupId === group.id"
              type="primary" link size="small"
              @click="saveGroup"
            >保存</el-button>
            <el-button
              v-else
              type="primary" link size="small"
              @click="startEditGroup(group)"
            >编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDeleteGroup(group)">删除</el-button>
          </div>
        </div>
      </div>
      <el-divider />
      <div class="add-group">
        <el-input
          v-model="newGroupName"
          placeholder="输入新分组名称"
          size="small"
          style="width: 200px; margin-right: 10px"
          @keyup.enter="handleAddGroup"
        />
        <el-button type="primary" size="small" @click="handleAddGroup">添加分组</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Search, Plus } from '@element-plus/icons-vue'
import {
  getQuickReplyList,
  createQuickReply,
  updateQuickReply,
  deleteQuickReply,
  getQuickReplyGroups,
  createQuickReplyGroup,
  updateQuickReplyGroup,
  deleteQuickReplyGroup,
  type QuickReply,
  type QuickReplyGroup,
  type QuickReplyTranslation
} from '@/api/quickReply'

const loading = ref(false)
const tableData = ref<QuickReply[]>([])
const groups = ref<QuickReplyGroup[]>([])

const searchForm = reactive({
  keyword: '',
  group_id: '',
  is_enabled: ''
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
  group_id: null as number | null,
  title_zh: '',
  content_zh: '',
  title_en: '',
  content_en: '',
  title_ja: '',
  content_ja: '',
  shortcut: '',
  sort: 0,
  is_enabled: 1
})

const formRules: FormRules = {}

// 分组管理
const groupDialogVisible = ref(false)
const editingGroupId = ref<number | null>(null)
const editingGroupName = ref('')
const newGroupName = ref('')

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const res: any = await getQuickReplyList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchForm
    })
    tableData.value = res.data.list
    pagination.total = res.data.total
  } catch (error) {
    console.error('加载快捷回复列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 加载分组
const loadGroups = async () => {
  try {
    const res: any = await getQuickReplyGroups()
    groups.value = res.data
  } catch (error) {
    console.error('加载分组失败:', error)
  }
}

// 获取翻译内容
const getTranslation = (row: QuickReply, locale: string, field: 'title' | 'content'): string => {
  if (row.translations) {
    const translation = row.translations.find((t: QuickReplyTranslation) => t.locale === locale)
    if (translation) return translation[field]
  }
  // 如果是繁体中文且没有翻译，返回主表字段
  if (locale === 'zh-tw') {
    return row[field]
  }
  return ''
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  loadData()
}

// 重置
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.group_id = ''
  searchForm.is_enabled = ''
  handleSearch()
}

// 添加
const handleAdd = () => {
  editingId.value = null
  formData.group_id = null
  formData.title_zh = ''
  formData.content_zh = ''
  formData.title_en = ''
  formData.content_en = ''
  formData.title_ja = ''
  formData.content_ja = ''
  formData.shortcut = ''
  formData.sort = 0
  formData.is_enabled = 1
  activeLocale.value = 'zh-tw'
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row: QuickReply) => {
  editingId.value = row.id
  formData.group_id = row.group_id || null
  // 从translations数组获取标题和内容
  let titleZh = ''
  let contentZh = ''
  let titleEn = ''
  let contentEn = ''
  let titleJa = ''
  let contentJa = ''
  if (row.translations) {
    for (const t of row.translations) {
      if (t.locale === 'zh-tw') {
        titleZh = t.title
        contentZh = t.content
      }
      if (t.locale === 'en') {
        titleEn = t.title
        contentEn = t.content
      }
      if (t.locale === 'ja-jp') {
        titleJa = t.title
        contentJa = t.content
      }
    }
  }
  // 如果没有繁体中文翻译数据，使用主表字段作为默认值
  formData.title_zh = titleZh || row.title || ''
  formData.content_zh = contentZh || row.content || ''
  formData.title_en = titleEn
  formData.content_en = contentEn
  formData.title_ja = titleJa
  formData.content_ja = contentJa
  formData.shortcut = row.shortcut || ''
  formData.sort = row.sort
  formData.is_enabled = row.is_enabled
  activeLocale.value = 'zh-tw'
  dialogVisible.value = true
}

// 删除
const handleDelete = async (row: QuickReply) => {
  try {
    await ElMessageBox.confirm(`确定要删除"${row.title}"吗？`, '确认删除', { type: 'warning' })
    await deleteQuickReply(row.id)
    ElMessage.success('删除成功')
    loadData()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

// 切换启用状态
const handleToggleEnabled = async (row: QuickReply) => {
  try {
    await updateQuickReply(row.id, { is_enabled: row.is_enabled })
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
        group_id: formData.group_id,
        shortcut: formData.shortcut,
        sort: formData.sort,
        is_enabled: formData.is_enabled,
        translations: {
          'zh-tw': { title: formData.title_zh, content: formData.content_zh },
          'en': { title: formData.title_en, content: formData.content_en },
          'ja-jp': { title: formData.title_ja, content: formData.content_ja }
        }
      }

      if (editingId.value) {
        await updateQuickReply(editingId.value, submitData)
        ElMessage.success('更新成功')
      } else {
        await createQuickReply(submitData)
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

// 管理分组
const handleManageGroups = () => {
  editingGroupId.value = null
  editingGroupName.value = ''
  newGroupName.value = ''
  groupDialogVisible.value = true
}

// 开始编辑分组
const startEditGroup = (group: QuickReplyGroup) => {
  editingGroupId.value = group.id
  editingGroupName.value = group.name
}

// 保存分组
const saveGroup = async () => {
  if (!editingGroupId.value || !editingGroupName.value.trim()) return
  try {
    await updateQuickReplyGroup(editingGroupId.value, { name: editingGroupName.value })
    ElMessage.success('更新成功')
    editingGroupId.value = null
    loadGroups()
  } catch (error) {
    console.error('更新分组失败:', error)
  }
}

// 添加分组
const handleAddGroup = async () => {
  if (!newGroupName.value.trim()) {
    ElMessage.warning('请输入分组名称')
    return
  }
  try {
    await createQuickReplyGroup({ name: newGroupName.value })
    ElMessage.success('添加成功')
    newGroupName.value = ''
    loadGroups()
  } catch (error) {
    console.error('添加分组失败:', error)
  }
}

// 删除分组
const handleDeleteGroup = async (group: QuickReplyGroup) => {
  try {
    await ElMessageBox.confirm(`确定要删除分组"${group.name}"吗？该分组下的回复将移至未分组。`, '确认删除', { type: 'warning' })
    await deleteQuickReplyGroup(group.id)
    ElMessage.success('删除成功')
    loadGroups()
    loadData()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除分组失败:', error)
    }
  }
}

onMounted(() => {
  loadData()
  loadGroups()
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

.group-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.group-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  background: #f5f7fa;
  border-radius: 4px;
}

.add-group {
  display: flex;
  align-items: center;
}

.text-gray {
  font-size: 12px;
  color: #909399;
}

.locale-tabs {
  margin-bottom: 10px;
}
</style>
