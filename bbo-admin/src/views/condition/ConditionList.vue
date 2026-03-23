<template>
  <div class="condition-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="分类">
          <el-select v-model="searchForm.category_id" placeholder="选择分类" clearable style="width: 200px" @change="loadData">
            <el-option
              v-for="item in categoryList"
              :key="item.id"
              :label="item.translations['zh-tw']?.name || item.translations['en-us']?.name || item.id"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px" @change="loadData">
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">查询</el-button>
          <el-button @click="resetSearch">重置</el-button>
          <el-button type="success" @click="handleAddGroup" :disabled="!searchForm.category_id">
            <el-icon><Plus /></el-icon>
            新增状态组
          </el-button>
          <el-button type="warning" @click="showCopyDialog" :disabled="!searchForm.category_id || groupList.length === 0">
            <el-icon><CopyDocument /></el-icon>
            复制到其他分类
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 提示信息 -->
    <el-alert
      v-if="!searchForm.category_id"
      title="请先选择一个分类来管理其商品状态配置"
      type="info"
      show-icon
      :closable="false"
      class="tip-alert"
    />

    <!-- 状态组列表 -->
    <div v-else class="groups-container">
      <el-empty v-if="groupList.length === 0 && !loading" description="该分类暂无状态配置">
        <el-button type="primary" @click="handleAddGroup">添加状态组</el-button>
      </el-empty>

      <div v-else class="group-list" v-loading="loading">
        <el-card v-for="group in groupList" :key="group.id" class="group-card" shadow="hover">
          <template #header>
            <div class="group-header">
              <div class="group-info">
                <i v-if="group.icon" :class="group.icon" class="group-icon"></i>
                <span class="group-name">{{ group.translations['zh-tw']?.name || group.name }}</span>
                <el-tag v-if="group.is_required" type="danger" size="small">必填</el-tag>
                <el-tag :type="group.status === 1 ? 'success' : 'info'" size="small">
                  {{ group.status === 1 ? '启用' : '禁用' }}
                </el-tag>
                <span class="option-count">{{ group.option_count || 0 }} 个选项</span>
              </div>
              <div class="group-actions">
                <el-button type="primary" size="small" @click="handleEditGroup(group)">编辑</el-button>
                <el-button type="success" size="small" @click="handleManageOptions(group)">管理选项</el-button>
                <el-button type="danger" size="small" @click="handleDeleteGroup(group)">删除</el-button>
              </div>
            </div>
          </template>

          <!-- 多语言预览 -->
          <div class="translations-preview">
            <div class="trans-item">
              <span class="trans-label">繁體中文:</span>
              <span class="trans-value">{{ group.translations['zh-tw']?.name || '-' }}</span>
            </div>
            <div class="trans-item">
              <span class="trans-label">English:</span>
              <span class="trans-value">{{ group.translations['en-us']?.name || '-' }}</span>
            </div>
            <div class="trans-item">
              <span class="trans-label">日本語:</span>
              <span class="trans-value">{{ group.translations['ja-jp']?.name || '-' }}</span>
            </div>
          </div>
        </el-card>
      </div>
    </div>

    <!-- 新增/编辑状态组弹窗 -->
    <el-dialog
      v-model="groupDialogVisible"
      :title="isEditGroup ? '编辑状态组' : '新增状态组'"
      width="600px"
    >
      <el-form :model="groupForm" :rules="groupRules" ref="groupFormRef" label-width="100px">
        <el-form-item label="图标">
          <el-input v-model="groupForm.icon" placeholder="Bootstrap Icons 类名，如: bi-battery-full">
            <template #prepend>
              <i :class="groupForm.icon || 'bi-question-circle'"></i>
            </template>
          </el-input>
        </el-form-item>
        <el-form-item label="是否必填">
          <el-radio-group v-model="groupForm.is_required">
            <el-radio :value="1">是</el-radio>
            <el-radio :value="0">否</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="groupForm.sort" :min="0" :max="9999" />
          <span class="form-tip">数字越小越靠前</span>
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="groupForm.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-divider content-position="left">繁體中文</el-divider>
        <el-form-item label="名稱" prop="translations.zh-tw.name">
          <el-input v-model="groupForm.translations['zh-tw'].name" placeholder="輸入繁體中文名稱" />
        </el-form-item>

        <el-divider content-position="left">English</el-divider>
        <el-form-item label="Name" prop="translations.en-us.name">
          <el-input v-model="groupForm.translations['en-us'].name" placeholder="Enter English name" />
        </el-form-item>

        <el-divider content-position="left">日本語</el-divider>
        <el-form-item label="名前" prop="translations.ja-jp.name">
          <el-input v-model="groupForm.translations['ja-jp'].name" placeholder="日本語名を入力" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="groupDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmitGroup" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 管理选项弹窗 -->
    <el-dialog
      v-model="optionDialogVisible"
      :title="`管理选项 - ${currentGroup?.translations['zh-tw']?.name || currentGroup?.name || ''}`"
      width="800px"
    >
      <div class="option-toolbar">
        <el-button type="primary" size="small" @click="handleAddOption">
          <el-icon><Plus /></el-icon>
          添加选项
        </el-button>
      </div>

      <el-table :data="optionList" v-loading="optionLoading" border>
        <el-table-column label="排序" width="70" prop="sort" />
        <el-table-column label="繁體中文" min-width="150">
          <template #default="{ row }">
            {{ row.translations['zh-tw']?.name || row.name }}
          </template>
        </el-table-column>
        <el-table-column label="English" min-width="120">
          <template #default="{ row }">
            {{ row.translations['en-us']?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="日本語" min-width="120">
          <template #default="{ row }">
            {{ row.translations['ja-jp']?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="影响等级" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.impact_level" :type="getImpactLevelType(row.impact_level)" size="small">
              {{ impactLevelMap[row.impact_level] }}
            </el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" link @click="handleEditOption(row)">编辑</el-button>
            <el-button type="danger" size="small" link @click="handleDeleteOption(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>

    <!-- 新增/编辑选项弹窗 -->
    <el-dialog
      v-model="optionFormDialogVisible"
      :title="isEditOption ? '编辑选项' : '新增选项'"
      width="600px"
    >
      <el-form :model="optionForm" :rules="optionRules" ref="optionFormRef" label-width="100px">
        <el-form-item label="影响等级">
          <el-select v-model="optionForm.impact_level" placeholder="选择影响等级（可选）" clearable>
            <el-option label="优秀" :value="1" />
            <el-option label="良好" :value="2" />
            <el-option label="一般" :value="3" />
            <el-option label="较差" :value="4" />
            <el-option label="很差" :value="5" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="optionForm.sort" :min="0" :max="9999" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="optionForm.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-divider content-position="left">繁體中文</el-divider>
        <el-form-item label="名稱" prop="translations.zh-tw.name">
          <el-input v-model="optionForm.translations['zh-tw'].name" placeholder="輸入繁體中文名稱" />
        </el-form-item>

        <el-divider content-position="left">English</el-divider>
        <el-form-item label="Name" prop="translations.en-us.name">
          <el-input v-model="optionForm.translations['en-us'].name" placeholder="Enter English name" />
        </el-form-item>

        <el-divider content-position="left">日本語</el-divider>
        <el-form-item label="名前" prop="translations.ja-jp.name">
          <el-input v-model="optionForm.translations['ja-jp'].name" placeholder="日本語名を入力" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="optionFormDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmitOption" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 复制配置弹窗 -->
    <el-dialog v-model="copyDialogVisible" title="复制状态配置" width="500px">
      <el-alert
        title="将当前分类的所有状态组和选项复制到目标分类"
        type="warning"
        show-icon
        :closable="false"
        style="margin-bottom: 20px;"
      />
      <el-form label-width="100px">
        <el-form-item label="源分类">
          <el-tag>{{ currentCategoryName }}</el-tag>
        </el-form-item>
        <el-form-item label="目标分类">
          <el-select v-model="copyTargetCategoryId" placeholder="选择目标分类" style="width: 100%">
            <el-option
              v-for="item in categoryList.filter(c => c.id !== searchForm.category_id)"
              :key="item.id"
              :label="item.translations['zh-tw']?.name || item.translations['en-us']?.name || item.id"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="copyDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleCopyToCategory" :loading="submitting" :disabled="!copyTargetCategoryId">
          确定复制
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, CopyDocument } from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import {
  getConditionGroups,
  getConditionOptions,
  createConditionGroup,
  updateConditionGroup,
  deleteConditionGroup,
  createConditionOption,
  updateConditionOption,
  deleteConditionOption,
  copyConditionToCategory,
  type ConditionGroup,
  type ConditionOption,
  type ConditionGroupForm,
  type ConditionOptionForm
} from '@/api/categoryCondition'
import { getCategoryList, type Category } from '@/api/category'

const loading = ref(false)
const submitting = ref(false)
const optionLoading = ref(false)

// 分类数据
const categoryList = ref<Category[]>([])

// 搜索表单
const searchForm = reactive({
  category_id: '' as number | string,
  status: '' as number | string
})

// 状态组数据
const groupList = ref<ConditionGroup[]>([])
const groupDialogVisible = ref(false)
const isEditGroup = ref(false)
const editGroupId = ref<number | null>(null)
const groupFormRef = ref<FormInstance>()

const getDefaultGroupForm = (): ConditionGroupForm => ({
  category_id: 0,
  icon: '',
  sort: 0,
  is_required: 1,
  status: 1,
  translations: {
    'zh-tw': { name: '' },
    'en-us': { name: '' },
    'ja-jp': { name: '' }
  }
})

const groupForm = reactive<ConditionGroupForm>(getDefaultGroupForm())

const groupRules: FormRules = {
  'translations.zh-tw.name': [
    { required: true, message: '請輸入繁體中文名稱', trigger: 'blur' }
  ]
}

// 选项数据
const currentGroup = ref<ConditionGroup | null>(null)
const optionList = ref<ConditionOption[]>([])
const optionDialogVisible = ref(false)
const optionFormDialogVisible = ref(false)
const isEditOption = ref(false)
const editOptionId = ref<number | null>(null)
const optionFormRef = ref<FormInstance>()

const getDefaultOptionForm = (): ConditionOptionForm => ({
  group_id: 0,
  sort: 0,
  impact_level: null,
  status: 1,
  translations: {
    'zh-tw': { name: '' },
    'en-us': { name: '' },
    'ja-jp': { name: '' }
  }
})

const optionForm = reactive<ConditionOptionForm>(getDefaultOptionForm())

const optionRules: FormRules = {
  'translations.zh-tw.name': [
    { required: true, message: '請輸入繁體中文名稱', trigger: 'blur' }
  ]
}

// 影响等级映射
const impactLevelMap: Record<number, string> = {
  1: '优秀',
  2: '良好',
  3: '一般',
  4: '较差',
  5: '很差'
}

const getImpactLevelType = (level: number): string => {
  const types: Record<number, string> = {
    1: 'success',
    2: '',
    3: 'warning',
    4: 'danger',
    5: 'danger'
  }
  return types[level] || 'info'
}

// 复制功能
const copyDialogVisible = ref(false)
const copyTargetCategoryId = ref<number | null>(null)

const currentCategoryName = computed(() => {
  const cat = categoryList.value.find(c => c.id === searchForm.category_id)
  return cat?.translations['zh-tw']?.name || cat?.translations['en-us']?.name || ''
})

// 加载分类列表
const loadCategories = async () => {
  const res: any = await getCategoryList({ pageSize: 100 })
  categoryList.value = res.data.list
}

// 加载状态组列表
const loadData = async () => {
  if (!searchForm.category_id) {
    groupList.value = []
    return
  }

  loading.value = true
  try {
    const res: any = await getConditionGroups({
      category_id: searchForm.category_id as number,
      status: searchForm.status
    })
    groupList.value = res.data || []
  } finally {
    loading.value = false
  }
}

// 重置搜索
const resetSearch = () => {
  searchForm.category_id = ''
  searchForm.status = ''
  groupList.value = []
}

// 新增状态组
const handleAddGroup = () => {
  isEditGroup.value = false
  editGroupId.value = null
  Object.assign(groupForm, getDefaultGroupForm())
  groupForm.category_id = searchForm.category_id as number
  groupDialogVisible.value = true
}

// 编辑状态组
const handleEditGroup = (group: ConditionGroup) => {
  isEditGroup.value = true
  editGroupId.value = group.id
  groupForm.category_id = group.category_id
  groupForm.icon = group.icon
  groupForm.sort = group.sort
  groupForm.is_required = group.is_required
  groupForm.status = group.status
  groupForm.translations = {
    'zh-tw': { name: group.translations['zh-tw']?.name || '' },
    'en-us': { name: group.translations['en-us']?.name || '' },
    'ja-jp': { name: group.translations['ja-jp']?.name || '' }
  }
  groupDialogVisible.value = true
}

// 提交状态组
const handleSubmitGroup = async () => {
  await groupFormRef.value?.validate()
  submitting.value = true
  try {
    if (isEditGroup.value && editGroupId.value) {
      await updateConditionGroup(editGroupId.value, groupForm)
      ElMessage.success('更新成功')
    } else {
      await createConditionGroup(groupForm)
      ElMessage.success('创建成功')
    }
    groupDialogVisible.value = false
    loadData()
  } finally {
    submitting.value = false
  }
}

// 删除状态组
const handleDeleteGroup = async (group: ConditionGroup) => {
  await ElMessageBox.confirm(
    `删除状态组"${group.translations['zh-tw']?.name || group.name}"将同时删除其所有选项，确定要删除吗？`,
    '提示',
    { type: 'warning' }
  )
  await deleteConditionGroup(group.id)
  ElMessage.success('删除成功')
  loadData()
}

// 管理选项
const handleManageOptions = async (group: ConditionGroup) => {
  currentGroup.value = group
  optionDialogVisible.value = true
  await loadOptions(group.id)
}

// 加载选项列表
const loadOptions = async (groupId: number) => {
  optionLoading.value = true
  try {
    const res: any = await getConditionOptions(groupId)
    optionList.value = res.data || []
  } finally {
    optionLoading.value = false
  }
}

// 新增选项
const handleAddOption = () => {
  if (!currentGroup.value) return
  isEditOption.value = false
  editOptionId.value = null
  Object.assign(optionForm, getDefaultOptionForm())
  optionForm.group_id = currentGroup.value.id
  optionForm.sort = optionList.value.length
  optionFormDialogVisible.value = true
}

// 编辑选项
const handleEditOption = (option: ConditionOption) => {
  isEditOption.value = true
  editOptionId.value = option.id
  optionForm.group_id = option.group_id
  optionForm.sort = option.sort
  optionForm.impact_level = option.impact_level
  optionForm.status = option.status
  optionForm.translations = {
    'zh-tw': { name: option.translations['zh-tw']?.name || '' },
    'en-us': { name: option.translations['en-us']?.name || '' },
    'ja-jp': { name: option.translations['ja-jp']?.name || '' }
  }
  optionFormDialogVisible.value = true
}

// 提交选项
const handleSubmitOption = async () => {
  await optionFormRef.value?.validate()
  submitting.value = true
  try {
    if (isEditOption.value && editOptionId.value) {
      await updateConditionOption(editOptionId.value, optionForm)
      ElMessage.success('更新成功')
    } else {
      await createConditionOption(optionForm)
      ElMessage.success('创建成功')
    }
    optionFormDialogVisible.value = false
    if (currentGroup.value) {
      await loadOptions(currentGroup.value.id)
    }
    loadData() // 刷新选项数量
  } finally {
    submitting.value = false
  }
}

// 删除选项
const handleDeleteOption = async (option: ConditionOption) => {
  await ElMessageBox.confirm(
    `确定要删除选项"${option.translations['zh-tw']?.name || option.name}"吗？`,
    '提示',
    { type: 'warning' }
  )
  await deleteConditionOption(option.id)
  ElMessage.success('删除成功')
  if (currentGroup.value) {
    await loadOptions(currentGroup.value.id)
  }
  loadData()
}

// 显示复制弹窗
const showCopyDialog = () => {
  copyTargetCategoryId.value = null
  copyDialogVisible.value = true
}

// 复制到其他分类
const handleCopyToCategory = async () => {
  if (!searchForm.category_id || !copyTargetCategoryId.value) return

  await ElMessageBox.confirm(
    '确定要将当前分类的状态配置复制到目标分类吗？',
    '确认复制',
    { type: 'warning' }
  )

  submitting.value = true
  try {
    await copyConditionToCategory(
      searchForm.category_id as number,
      copyTargetCategoryId.value
    )
    ElMessage.success('复制成功')
    copyDialogVisible.value = false
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadCategories()
})
</script>

<style scoped>
.search-card {
  margin-bottom: 20px;
}

.tip-alert {
  margin-bottom: 20px;
}

.groups-container {
  min-height: 300px;
}

.group-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.group-card {
  transition: all 0.3s;
}

.group-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.group-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.group-icon {
  font-size: 20px;
  color: #409eff;
}

.group-name {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.option-count {
  font-size: 13px;
  color: #909399;
}

.group-actions {
  display: flex;
  gap: 8px;
}

.translations-preview {
  display: flex;
  gap: 24px;
  padding-top: 8px;
}

.trans-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.trans-label {
  font-size: 12px;
  color: #909399;
}

.trans-value {
  font-size: 13px;
  color: #606266;
}

.form-tip {
  margin-left: 12px;
  font-size: 12px;
  color: #909399;
}

.option-toolbar {
  margin-bottom: 16px;
}
</style>
