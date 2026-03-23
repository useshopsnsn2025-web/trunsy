<template>
  <div class="attribute-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="分类">
          <el-select v-model="searchForm.category_id" placeholder="选择分类" clearable style="width: 180px">
            <el-option label="全局属性" :value="0" />
            <el-option
              v-for="item in categoryList"
              :key="item.id"
              :label="item.translations['zh-tw']?.name || item.translations['zh-cn']?.name || item.id"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="搜索属性名称或键名" clearable style="width: 200px" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">查询</el-button>
          <el-button @click="resetSearch">重置</el-button>
          <el-button type="success" @click="handleAdd">新增属性</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card">
      <el-table :data="tableData" v-loading="loading" border stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="category_id" label="分类" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.category_id == 0" type="warning">全局</el-tag>
            <span v-else>{{ row.category_id }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="attr_key" label="属性键名" width="120" />
        <el-table-column label="繁體中文" min-width="120">
          <template #default="{ row }">
            {{ row.translations['zh-tw']?.name || row.translations['zh-cn']?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="日本語" min-width="120">
          <template #default="{ row }">
            {{ row.translations['ja-jp']?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="英文名称" min-width="120">
          <template #default="{ row }">
            {{ row.translations['en-us']?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="input_type" label="输入类型" width="100">
          <template #default="{ row }">
            <el-tag>{{ inputTypeMap[row.input_type] || row.input_type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="必填" width="70">
          <template #default="{ row }">
            <el-tag :type="row.is_required === 1 ? 'danger' : 'info'" size="small">
              {{ row.is_required === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="70" />
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
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
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑属性' : '新增属性'"
      width="600px"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="所属分类" prop="category_id">
          <el-select v-model="form.category_id" placeholder="选择分类" :disabled="isEdit">
            <el-option label="全局属性（所有分类通用）" :value="0" />
            <el-option
              v-for="item in categoryList"
              :key="item.id"
              :label="item.translations['zh-tw']?.name || item.translations['zh-cn']?.name || item.id"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="属性键名" prop="attr_key">
          <el-input v-model="form.attr_key" placeholder="如: brand, model, storage" />
        </el-form-item>
        <el-form-item label="输入类型" prop="input_type">
          <el-select v-model="form.input_type" placeholder="选择输入类型">
            <el-option label="下拉选择" value="select" />
            <el-option label="多选" value="multi_select" />
            <el-option label="文本输入" value="input" />
          </el-select>
        </el-form-item>
        <el-form-item label="父属性">
          <el-select v-model="form.parent_key" placeholder="无（独立属性）" clearable>
            <el-option
              v-for="attr in parentAttributeOptions"
              :key="attr.attr_key"
              :label="attr.translations['zh-tw']?.name || attr.translations['zh-cn']?.name || attr.attr_key"
              :value="attr.attr_key"
            />
          </el-select>
          <div class="form-tip">选择父属性后，添加选项时需要先选择父属性的值（如：系列属性选择品牌作为父属性）</div>
        </el-form-item>
        <el-form-item label="是否必填">
          <el-radio-group v-model="form.is_required">
            <el-radio :value="1">是</el-radio>
            <el-radio :value="0">否</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-divider content-position="left">繁體中文</el-divider>
        <el-form-item label="繁體名稱" prop="translations.zh-tw.name">
          <el-input v-model="form.translations['zh-tw'].name" placeholder="輸入繁體中文名稱" />
        </el-form-item>
        <el-form-item label="繁體佔位符">
          <el-input v-model="form.translations['zh-tw'].placeholder" placeholder="輸入框佔位提示" />
        </el-form-item>
        <el-divider content-position="left">日本語</el-divider>
        <el-form-item label="日本語名" prop="translations.ja-jp.name">
          <el-input v-model="form.translations['ja-jp'].name" placeholder="日本語名を入力" />
        </el-form-item>
        <el-form-item label="プレースホルダー">
          <el-input v-model="form.translations['ja-jp'].placeholder" placeholder="入力欄のプレースホルダー" />
        </el-form-item>
        <el-divider content-position="left">英文翻译</el-divider>
        <el-form-item label="英文名称" prop="translations.en-us.name">
          <el-input v-model="form.translations['en-us'].name" placeholder="Enter English name" />
        </el-form-item>
        <el-form-item label="英文占位符">
          <el-input v-model="form.translations['en-us'].placeholder" placeholder="Input placeholder text" />
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
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import {
  getAttributeList,
  createAttribute,
  updateAttribute,
  deleteAttribute,
  type CategoryAttribute,
  type AttributeForm
} from '@/api/attribute'
import { getCategoryList, type Category } from '@/api/category'

const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const formRef = ref<FormInstance>()

const tableData = ref<CategoryAttribute[]>([])
const categoryList = ref<Category[]>([])

const inputTypeMap: Record<string, string> = {
  select: '下拉选择',
  multi_select: '多选',
  input: '文本输入'
}

const searchForm = reactive({
  keyword: '',
  category_id: '' as number | string,
  status: '' as number | string
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const getDefaultForm = (): AttributeForm => ({
  category_id: 0,
  attr_key: '',
  input_type: 'select',
  is_required: 0,
  parent_key: null,
  sort: 0,
  status: 1,
  translations: {
    'zh-tw': { name: '', placeholder: '' },
    'ja-jp': { name: '', placeholder: '' },
    'en-us': { name: '', placeholder: '' }
  }
})

// 可作为父属性的选项（全局属性，排除自身）
const parentAttributeOptions = computed(() => {
  return tableData.value.filter(attr =>
    attr.category_id === 0 && // 只有全局属性可以作为父属性
    attr.attr_key !== form.attr_key // 排除自身
  )
})

const form = reactive<AttributeForm>(getDefaultForm())

const rules: FormRules = {
  category_id: [
    { required: true, message: '请选择分类', trigger: 'change', type: 'number', validator: (_rule, value, callback) => {
      if (value === undefined || value === null || value === '') {
        callback(new Error('请选择分类'))
      } else {
        callback()
      }
    }}
  ],
  attr_key: [
    { required: true, message: '请输入属性键名', trigger: 'blur' }
  ],
  input_type: [
    { required: true, message: '请选择输入类型', trigger: 'change' }
  ],
  'translations.zh-tw.name': [
    { required: true, message: '請輸入繁體中文名稱', trigger: 'blur' }
  ],
  'translations.ja-jp.name': [
    { required: true, message: '日本語名を入力してください', trigger: 'blur' }
  ],
  'translations.en-us.name': [
    { required: true, message: '请输入英文名称', trigger: 'blur' }
  ]
}

const loadData = async () => {
  loading.value = true
  try {
    const res: any = await getAttributeList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      keyword: searchForm.keyword,
      category_id: searchForm.category_id,
      status: searchForm.status
    })
    tableData.value = res.data.list
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  const res: any = await getCategoryList({ pageSize: 100 })
  categoryList.value = res.data.list
}

const resetSearch = () => {
  searchForm.keyword = ''
  searchForm.category_id = ''
  searchForm.status = ''
  pagination.page = 1
  loadData()
}

const handleAdd = () => {
  isEdit.value = false
  editId.value = null
  Object.assign(form, getDefaultForm())
  dialogVisible.value = true
}

const handleEdit = (row: CategoryAttribute) => {
  isEdit.value = true
  editId.value = row.id
  form.category_id = row.category_id
  form.attr_key = row.attr_key
  form.input_type = row.input_type
  form.is_required = row.is_required
  form.parent_key = row.parent_key || null
  form.sort = row.sort
  form.status = row.status
  form.translations = {
    'zh-tw': {
      name: row.translations['zh-tw']?.name || row.translations['zh-cn']?.name || '',
      placeholder: row.translations['zh-tw']?.placeholder || row.translations['zh-cn']?.placeholder || ''
    },
    'ja-jp': {
      name: row.translations['ja-jp']?.name || '',
      placeholder: row.translations['ja-jp']?.placeholder || ''
    },
    'en-us': {
      name: row.translations['en-us']?.name || '',
      placeholder: row.translations['en-us']?.placeholder || ''
    }
  }
  dialogVisible.value = true
}

const handleSubmit = async () => {
  await formRef.value?.validate()
  submitting.value = true
  try {
    if (isEdit.value && editId.value) {
      await updateAttribute(editId.value, form)
      ElMessage.success('更新成功')
    } else {
      await createAttribute(form)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadData()
  } finally {
    submitting.value = false
  }
}

const handleDelete = async (row: CategoryAttribute) => {
  await ElMessageBox.confirm('删除属性将同时删除其所有选项，确定要删除吗？', '提示', {
    type: 'warning'
  })
  await deleteAttribute(row.id)
  ElMessage.success('删除成功')
  loadData()
}

onMounted(() => {
  loadData()
  loadCategories()
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

.form-tip {
  font-size: 12px;
  color: #909399;
  line-height: 1.4;
  margin-top: 4px;
}
</style>
