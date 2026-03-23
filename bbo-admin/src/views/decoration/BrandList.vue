<template>
  <div class="brand-list">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>精品品牌管理</span>
          <el-button type="primary" @click="handleAdd">新增品牌</el-button>
        </div>
      </template>

      <!-- 搜索栏 -->
      <div class="search-bar">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索品牌名称"
          clearable
          style="width: 200px"
          @keyup.enter="fetchList"
        />
        <el-select v-model="searchStatus" placeholder="状态" clearable style="width: 120px">
          <el-option label="启用" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
        <el-button type="primary" @click="fetchList">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="logo" label="LOGO" width="100">
          <template #default="{ row }">
            <el-image
              v-if="row.logo"
              :src="row.logo"
              style="width: 60px; height: 60px;"
              fit="contain"
              :preview-src-list="[row.logo]"
            />
            <span v-else-if="row.icon" class="brand-icon">{{ row.icon }}</span>
            <span v-else class="no-image">无</span>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="品牌名称" min-width="150" />
        <el-table-column prop="icon" label="图标" width="80">
          <template #default="{ row }">
            <span v-if="row.icon" class="brand-icon">{{ row.icon }}</span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
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
    <el-dialog v-model="formDialogVisible" :title="isEdit ? '编辑品牌' : '新增品牌'" width="600px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <!-- 多语言输入 -->
        <el-form-item label="品牌名称" required>
          <el-tabs v-model="activeLang" type="card" class="lang-tabs">
            <el-tab-pane label="繁體中文" name="zh-tw">
              <el-form-item label="名稱" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].name" placeholder="請輸入品牌名稱" />
              </el-form-item>
              <el-form-item label="描述" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['zh-tw'].description" type="textarea" :rows="2" placeholder="品牌描述（可選）" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="English" name="en-us">
              <el-form-item label="Name" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].name" placeholder="Enter brand name" />
              </el-form-item>
              <el-form-item label="Desc" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['en-us'].description" type="textarea" :rows="2" placeholder="Description (optional)" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="日本語" name="ja-jp">
              <el-form-item label="名前" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].name" placeholder="ブランド名を入力" />
              </el-form-item>
              <el-form-item label="説明" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['ja-jp'].description" type="textarea" :rows="2" placeholder="説明（任意）" />
              </el-form-item>
            </el-tab-pane>
          </el-tabs>
        </el-form-item>
        <el-form-item label="LOGO" prop="logo">
          <div class="logo-upload">
            <ImagePicker
              v-model="form.logo"
              width="100px"
              height="100px"
            />
            <div class="upload-tip">建议尺寸: 200x200px</div>
          </div>
        </el-form-item>
        <el-form-item label="图标" prop="icon">
          <el-input v-model="form.icon" placeholder="emoji或文字图标，如 🍎" style="width: 200px;" />
          <span class="form-tip">可选，用于无LOGO时显示</span>
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" />
          <span class="form-tip">数值越大越靠前</span>
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
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import ImagePicker from '@/components/ImagePicker.vue'
import { getBrandList, getBrandDetail, createBrand, updateBrand, deleteBrand, type Brand } from '@/api/brand'

const list = ref<Brand[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

// 搜索
const searchKeyword = ref('')
const searchStatus = ref<number | string>('')

const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)
const activeLang = ref('zh-tw')

// 初始翻译结构
const emptyTranslations = () => ({
  'zh-tw': { name: '', description: '' },
  'en-us': { name: '', description: '' },
  'ja-jp': { name: '', description: '' }
})

const form = reactive({
  logo: '',
  icon: '',
  sort: 0,
  status: 1,
  translations: emptyTranslations() as Record<string, { name: string; description: string }>
})

const rules: FormRules = {}

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getBrandList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      keyword: searchKeyword.value || undefined,
      status: searchStatus.value !== '' ? searchStatus.value : undefined
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取列表失败:', error)
  } finally {
    loading.value = false
  }
}

const resetSearch = () => {
  searchKeyword.value = ''
  searchStatus.value = ''
  pagination.page = 1
  fetchList()
}

const handleAdd = () => {
  isEdit.value = false
  formDialogVisible.value = true
  activeLang.value = 'zh-tw'
  Object.assign(form, {
    logo: '',
    icon: '',
    sort: 0,
    status: 1,
    translations: emptyTranslations()
  })
}

const handleEdit = async (row: Brand) => {
  isEdit.value = true
  editId.value = row.id
  activeLang.value = 'zh-tw'
  try {
    const res: any = await getBrandDetail(row.id)
    const data = res.data
    Object.assign(form, {
      logo: data.logo || '',
      icon: data.icon || '',
      sort: data.sort || 0,
      status: data.status,
      translations: data.translations || emptyTranslations()
    })
    formDialogVisible.value = true
  } catch (error) {
    console.error('获取详情失败:', error)
    ElMessage.error('获取详情失败')
  }
}

const handleSubmit = async () => {
  // 验证至少填写一种语言的名称
  if (!form.translations['zh-tw'].name && !form.translations['en-us'].name && !form.translations['ja-jp'].name) {
    ElMessage.warning('请至少填写一种语言的品牌名称')
    return
  }

  submitting.value = true
  try {
    const submitData = {
      name: form.translations['zh-tw'].name || form.translations['en-us'].name || form.translations['ja-jp'].name,
      logo: form.logo,
      icon: form.icon,
      sort: form.sort,
      status: form.status,
      translations: form.translations
    }

    if (isEdit.value) {
      await updateBrand(editId.value, submitData)
      ElMessage.success('更新成功')
    } else {
      await createBrand(submitData)
      ElMessage.success('创建成功')
    }
    formDialogVisible.value = false
    fetchList()
  } catch (error) {
    console.error('提交失败:', error)
  } finally {
    submitting.value = false
  }
}

const handleDelete = async (row: Brand) => {
  try {
    await ElMessageBox.confirm(`确定删除品牌"${row.name}"吗？`, '提示', { type: 'warning' })
    await deleteBrand(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

onMounted(() => fetchList())
</script>

<style scoped>
.brand-list {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.brand-icon {
  font-size: 24px;
}

.no-image {
  color: #999;
  font-size: 12px;
}

.lang-tabs {
  width: 100%;
}

.lang-tabs :deep(.el-tabs__content) {
  padding: 15px;
  background: #f9f9f9;
  border-radius: 4px;
}

.logo-upload {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.upload-tip {
  font-size: 12px;
  color: #909399;
}

.form-tip {
  margin-left: 12px;
  font-size: 12px;
  color: #909399;
}
</style>
