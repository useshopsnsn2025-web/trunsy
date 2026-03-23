<template>
  <div class="category-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="搜索分类名称" clearable />
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
          <el-button type="success" @click="handleAdd">新增分类</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card">
      <el-table :data="tableData" v-loading="loading" border stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="繁體中文" min-width="150">
          <template #default="{ row }">
            {{ row.translations['zh-tw']?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="英文名称" min-width="150">
          <template #default="{ row }">
            {{ row.translations['en-us']?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="日本語" min-width="150">
          <template #default="{ row }">
            {{ row.translations['ja-jp']?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="parent_id" label="父级ID" width="80" />
        <el-table-column label="图标" width="100">
          <template #default="{ row }">
            <el-image
              v-if="row.icon"
              :src="row.icon"
              fit="cover"
              style="width: 50px; height: 50px; border-radius: 4px"
            />
            <span v-else class="text-gray">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="热门" width="80">
          <template #default="{ row }">
            <el-tag :type="row.is_hot === 1 ? 'warning' : 'info'">
              {{ row.is_hot === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
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
      :title="isEdit ? '编辑分类' : '新增分类'"
      width="600px"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="父级分类">
          <el-select v-model="form.parent_id" placeholder="选择父级分类" clearable>
            <el-option label="顶级分类" :value="0" />
            <el-option
              v-for="item in categoryTree"
              :key="item.id"
              :label="item.translations['zh-tw']?.name || item.id"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="分类图片">
          <ImagePicker v-model="form.icon" width="100px" height="100px" />
          <div class="upload-tip">建议尺寸: 200x200，支持 jpg/png/gif</div>
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
        <el-form-item label="热门分类">
          <el-switch
            v-model="form.is_hot"
            :active-value="1"
            :inactive-value="0"
            active-text="是"
            inactive-text="否"
          />
          <div class="form-tip">开启后将在APP首页热门分类区域显示</div>
        </el-form-item>
        <el-divider content-position="left">繁體中文</el-divider>
        <el-form-item label="繁體名稱" prop="translations.zh-tw.name">
          <el-input v-model="form.translations['zh-tw'].name" placeholder="輸入繁體中文名稱" />
        </el-form-item>
        <el-form-item label="繁體描述">
          <el-input v-model="form.translations['zh-tw'].description" type="textarea" rows="2" placeholder="輸入繁體中文描述" />
        </el-form-item>
        <el-divider content-position="left">英文翻译</el-divider>
        <el-form-item label="英文名称" prop="translations.en-us.name">
          <el-input v-model="form.translations['en-us'].name" placeholder="Enter English name" />
        </el-form-item>
        <el-form-item label="英文描述">
          <el-input v-model="form.translations['en-us'].description" type="textarea" rows="2" placeholder="Enter English description" />
        </el-form-item>
        <el-divider content-position="left">日本語</el-divider>
        <el-form-item label="日本語名" prop="translations.ja-jp.name">
          <el-input v-model="form.translations['ja-jp'].name" placeholder="日本語名を入力" />
        </el-form-item>
        <el-form-item label="日本語説明">
          <el-input v-model="form.translations['ja-jp'].description" type="textarea" rows="2" placeholder="日本語の説明を入力" />
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
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import ImagePicker from '@/components/ImagePicker.vue'
import {
  getCategoryList,
  getCategoryTree,
  createCategory,
  updateCategory,
  deleteCategory,
  type Category,
  type CategoryForm
} from '@/api/category'

const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const formRef = ref<FormInstance>()

const tableData = ref<Category[]>([])
const categoryTree = ref<Category[]>([])

const searchForm = reactive({
  keyword: '',
  status: '' as number | string
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const getDefaultForm = (): CategoryForm => ({
  parent_id: 0,
  icon: '',
  sort: 0,
  status: 1,
  is_hot: 0,
  translations: {
    'zh-tw': { name: '', description: '' },
    'en-us': { name: '', description: '' },
    'ja-jp': { name: '', description: '' }
  }
})

const form = reactive<CategoryForm>(getDefaultForm())

const rules: FormRules = {
  'translations.zh-tw.name': [
    { required: true, message: '請輸入繁體中文名稱', trigger: 'blur' }
  ],
  'translations.en-us.name': [
    { required: true, message: '请输入英文名称', trigger: 'blur' }
  ],
  'translations.ja-jp.name': [
    { required: true, message: '日本語名を入力してください', trigger: 'blur' }
  ]
}

const loadData = async () => {
  loading.value = true
  try {
    const res: any = await getCategoryList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      keyword: searchForm.keyword,
      status: searchForm.status
    })
    tableData.value = res.data.list
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

const loadCategoryTree = async () => {
  const res: any = await getCategoryTree()
  categoryTree.value = res.data
}

const resetSearch = () => {
  searchForm.keyword = ''
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

const handleEdit = (row: Category) => {
  isEdit.value = true
  editId.value = row.id
  form.parent_id = row.parent_id
  form.icon = row.icon
  form.sort = row.sort
  form.status = row.status
  form.is_hot = row.is_hot
  form.translations = {
    'zh-tw': {
      name: row.translations['zh-tw']?.name || '',
      description: row.translations['zh-tw']?.description || ''
    },
    'en-us': {
      name: row.translations['en-us']?.name || '',
      description: row.translations['en-us']?.description || ''
    },
    'ja-jp': {
      name: row.translations['ja-jp']?.name || '',
      description: row.translations['ja-jp']?.description || ''
    }
  }
  dialogVisible.value = true
}

const handleSubmit = async () => {
  await formRef.value?.validate()
  submitting.value = true
  try {
    if (isEdit.value && editId.value) {
      await updateCategory(editId.value, form)
      ElMessage.success('更新成功')
    } else {
      await createCategory(form)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadData()
    loadCategoryTree()
  } finally {
    submitting.value = false
  }
}

const handleDelete = async (row: Category) => {
  await ElMessageBox.confirm('确定要删除该分类吗？', '提示', {
    type: 'warning'
  })
  await deleteCategory(row.id)
  ElMessage.success('删除成功')
  loadData()
  loadCategoryTree()
}

onMounted(() => {
  loadData()
  loadCategoryTree()
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

.text-gray {
  color: #999;
}

.upload-tip {
  font-size: 12px;
  color: #999;
  margin-top: 8px;
}

.form-tip {
  font-size: 12px;
  color: #999;
  margin-left: 12px;
}
</style>
