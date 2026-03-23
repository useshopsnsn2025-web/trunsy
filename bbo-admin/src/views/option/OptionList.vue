<template>
  <div class="option-list">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="分类">
          <el-select v-model="searchForm.category_id" placeholder="选择分类" clearable filterable @change="handleSearchCategoryChange" style="width: 180px">
            <el-option label="全局属性" :value="0" />
            <el-option
              v-for="item in categoryList"
              :key="item.id"
              :label="item.translations['zh-tw']?.name || item.translations['zh-cn']?.name || item.id"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="属性">
          <el-select v-model="searchForm.attribute_id" placeholder="选择属性" clearable filterable @change="handleSearchAttributeChange" style="width: 180px">
            <el-option
              v-for="item in filteredAttributes"
              :key="item.id"
              :label="item.translations['zh-tw']?.name || item.translations['zh-cn']?.name || item.attr_key"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="品牌">
          <el-select v-model="searchForm.parent_value" placeholder="全部" clearable filterable style="width: 180px">
            <el-option
              v-for="opt in brandOptions"
              :key="opt.value"
              :label="opt.label"
              :value="opt.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="搜索选项值或标签" clearable style="width: 180px" />
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
          <el-button type="success" @click="handleAdd">新增选项</el-button>
          <el-button type="warning" @click="handleBatchAdd">批量新增</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card">
      <div class="table-toolbar" v-if="selectedRows.length > 0">
        <span class="selected-count">已选择 {{ selectedRows.length }} 项</span>
        <el-button type="danger" size="small" @click="handleBatchDelete">批量删除</el-button>
      </div>
      <el-table :data="tableData" v-loading="loading" border stripe @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="attribute_id" label="属性ID" width="80" />
        <el-table-column label="图片" width="80">
          <template #default="{ row }">
            <el-image
              v-if="row.image"
              :src="row.image"
              fit="cover"
              style="width: 50px; height: 50px; border-radius: 4px"
              :preview-src-list="[row.image]"
            />
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="option_value" label="选项值" width="120" />
        <el-table-column prop="parent_value" label="父属性值" width="120">
          <template #default="{ row }">
            <span v-if="row.parent_value">{{ row.parent_value }}</span>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column label="繁體中文" min-width="120">
          <template #default="{ row }">
            {{ row.translations['zh-tw']?.label || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="英文标签" min-width="120">
          <template #default="{ row }">
            {{ row.translations['en-us']?.label || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="日本語" min-width="120">
          <template #default="{ row }">
            {{ row.translations['ja-jp']?.label || '-' }}
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

    <!-- 单个编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑选项' : '新增选项'"
      width="600px"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="所属属性" prop="attribute_id">
          <el-select v-model="form.attribute_id" placeholder="选择属性" :disabled="isEdit" style="width: 100%">
            <el-option-group label="全局属性" v-if="getAttributesByCategory(0).length > 0">
              <el-option
                v-for="attr in getAttributesByCategory(0)"
                :key="attr.id"
                :label="attr.translations['zh-tw']?.name || attr.translations['zh-cn']?.name || attr.attr_key"
                :value="attr.id"
              />
            </el-option-group>
            <el-option-group
              v-for="cat in categoryList"
              :key="cat.id"
              :label="cat.translations['zh-tw']?.name || cat.translations['zh-cn']?.name || String(cat.id)"
            >
              <el-option
                v-for="attr in getAttributesByCategory(cat.id)"
                :key="attr.id"
                :label="attr.translations['zh-tw']?.name || attr.translations['zh-cn']?.name || attr.attr_key"
                :value="attr.id"
              />
            </el-option-group>
          </el-select>
        </el-form-item>
        <el-form-item v-if="selectedAttribute?.parent_key" :label="getParentAttribute(selectedAttribute.category_id, selectedAttribute.parent_key)?.translations['zh-tw']?.name || getParentAttribute(selectedAttribute.category_id, selectedAttribute.parent_key)?.translations['zh-cn']?.name || '父属性'" prop="parent_value">
          <el-select v-model="form.parent_value" placeholder="请选择" clearable filterable style="width: 100%">
            <el-option
              v-for="opt in parentAttributeOptions"
              :key="opt.value"
              :label="opt.label"
              :value="opt.value"
            />
          </el-select>
          <div class="form-tip">如需添加新的选项，请先在对应的父属性下添加</div>
        </el-form-item>
        <el-form-item label="选项值" prop="option_value">
          <el-input v-model="form.option_value" placeholder="如: apple, samsung, 128GB" />
        </el-form-item>
        <el-form-item label="选项图片">
          <ImagePicker v-model="form.image" width="100px" height="100px" />
          <div class="form-tip">可选，用于展示品牌logo、颜色色块等</div>
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
        <el-form-item label="繁體標籤">
          <el-input v-model="form.translations['zh-tw'].label" placeholder="輸入繁體中文標籤" />
        </el-form-item>
        <el-divider content-position="left">英文翻译</el-divider>
        <el-form-item label="英文标签">
          <el-input v-model="form.translations['en-us'].label" placeholder="Enter English label" />
        </el-form-item>
        <el-divider content-position="left">日本語</el-divider>
        <el-form-item label="日語標籤">
          <el-input v-model="form.translations['ja-jp'].label" placeholder="日本語ラベルを入力" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 批量新增弹窗 -->
    <el-dialog
      v-model="batchDialogVisible"
      title="批量新增选项"
      width="1100px"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
    >
      <el-form :model="batchForm" ref="batchFormRef" label-width="100px">
        <el-form-item label="所属属性" prop="attribute_id" :rules="[{ required: true, message: '请选择属性' }]">
          <el-select v-model="batchForm.attribute_id" placeholder="选择属性" style="width: 100%">
            <el-option-group label="全局属性" v-if="getAttributesByCategory(0).length > 0">
              <el-option
                v-for="attr in getAttributesByCategory(0)"
                :key="attr.id"
                :label="attr.translations['zh-tw']?.name || attr.translations['zh-cn']?.name || attr.attr_key"
                :value="attr.id"
              />
            </el-option-group>
            <el-option-group
              v-for="cat in categoryList"
              :key="cat.id"
              :label="cat.translations['zh-tw']?.name || cat.translations['zh-cn']?.name || String(cat.id)"
            >
              <el-option
                v-for="attr in getAttributesByCategory(cat.id)"
                :key="attr.id"
                :label="attr.translations['zh-tw']?.name || attr.translations['zh-cn']?.name || attr.attr_key"
                :value="attr.id"
              />
            </el-option-group>
          </el-select>
        </el-form-item>
        <el-form-item v-if="batchSelectedAttribute?.parent_key" :label="getBatchParentAttrName()">
          <el-select v-model="batchForm.parent_value" placeholder="请选择（批量添加的选项都属于同一品牌）" clearable filterable style="width: 400px">
            <el-option
              v-for="opt in batchParentAttributeOptions"
              :key="opt.value"
              :label="opt.label"
              :value="opt.value"
            />
          </el-select>
          <div class="form-tip">批量添加的所有选项将归属于此{{ getBatchParentAttrName() }}</div>
        </el-form-item>
        <el-form-item label="选项列表">
          <div class="batch-options">
            <!-- 表头 -->
            <div class="batch-option-header" v-if="batchForm.options.length > 0">
              <span style="width: 120px">选项值</span>
              <span style="width: 150px">繁體標籤</span>
              <span style="width: 150px">English</span>
              <span style="width: 150px">日本語</span>
              <span style="width: 220px">图片地址</span>
              <span style="width: 80px">排序</span>
              <span style="width: 32px"></span>
            </div>
            <div v-for="(opt, index) in batchForm.options" :key="index" class="batch-option-row">
              <el-input v-model="opt.option_value" placeholder="选项值" style="width: 120px" />
              <el-input v-model="opt.label_zh" placeholder="繁體標籤" style="width: 150px" />
              <el-input v-model="opt.label_en" placeholder="English" style="width: 150px" />
              <el-input v-model="opt.label_ja" placeholder="日本語" style="width: 150px" />
              <el-input v-model="opt.image" placeholder="图片URL" style="width: 220px" />
              <el-input-number v-model="opt.sort" :min="0" placeholder="排序" style="width: 80px" controls-position="right" />
              <el-button type="danger" :icon="Delete" circle @click="removeBatchOption(index)" />
            </div>
            <el-button type="primary" @click="addBatchOption">添加一行</el-button>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="batchDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleBatchSubmit" :loading="submitting">批量创建</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete } from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import {
  getOptionList,
  createOption,
  updateOption,
  deleteOption,
  batchCreateOptions,
  batchDeleteOptions,
  type AttributeOption,
  type OptionForm
} from '@/api/option'
import { getCategoryList, type Category } from '@/api/category'
import { getAttributeList, type CategoryAttribute } from '@/api/attribute'
import ImagePicker from '@/components/ImagePicker.vue'

const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const batchDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const batchFormRef = ref<FormInstance>()

const tableData = ref<AttributeOption[]>([])
const categoryList = ref<Category[]>([])
const attributeList = ref<CategoryAttribute[]>([])
const parentOptionsCache = ref<Record<number, { value: string, label: string }[]>>({})  // 缓存父属性选项
const selectedRows = ref<AttributeOption[]>([])  // 选中的行

const searchForm = reactive({
  keyword: '',
  category_id: '' as number | string,
  attribute_id: '' as number | string,
  parent_value: '' as string,  // 父属性值筛选（如品牌）
  status: '' as number | string
})

// 搜索表单选中的属性
const searchSelectedAttribute = computed(() => {
  if (!searchForm.attribute_id) return null
  return attributeList.value.find(attr => attr.id === searchForm.attribute_id)
})

// 搜索表单的父属性选项
const searchParentOptions = ref<{ value: string, label: string }[]>([])

// 品牌选项（全局品牌属性的选项）
const brandOptions = ref<{ value: string, label: string }[]>([])

// 获取搜索父属性名称
const getSearchParentAttributeName = () => {
  const attr = searchSelectedAttribute.value
  if (!attr?.parent_key) return '父属性'
  const parentAttr = getParentAttribute(attr.category_id, attr.parent_key)
  return parentAttr?.translations['zh-tw']?.name || parentAttr?.translations['zh-cn']?.name || attr.parent_key
}

// 加载品牌选项
// 当选中了属性时，根据该属性下选项的 parent_value 过滤品牌
// 否则加载全局品牌属性的全部选项
const loadBrandOptions = async () => {
  const attr = searchSelectedAttribute.value

  // 如果选中了属性，尝试根据该属性下选项的 parent_value 过滤品牌
  if (attr) {
    await loadFilteredBrandOptions(attr)
    return
  }

  // 否则加载全局品牌属性的全部选项
  const brandAttr = attributeList.value.find(
    a => a.category_id == 0 && a.attr_key === 'brand'
  )

  if (brandAttr) {
    await loadBrandOptionsFromAttr(brandAttr.id)
  } else {
    brandOptions.value = []
  }
}

// 加载过滤后的品牌选项
// 根据指定属性下选项的 parent_value 过滤品牌
const loadFilteredBrandOptions = async (attr: CategoryAttribute) => {
  try {
    // 1. 先获取该属性下的所有选项
    const res: any = await getOptionList({
      attribute_id: attr.id,
      status: 1,
      pageSize: 5000
    })

    // 2. 提取所有选项的 parent_value（品牌代码）
    const parentValues = new Set<string>()
    for (const opt of res.data.list) {
      if (opt.parent_value) {
        parentValues.add(opt.parent_value)
      }
    }

    // 3. 获取全局品牌属性
    const brandAttr = attributeList.value.find(
      a => a.category_id == 0 && a.attr_key === 'brand'
    )

    if (!brandAttr) {
      brandOptions.value = []
      return
    }

    // 确保品牌选项已加载到缓存
    if (!parentOptionsCache.value[brandAttr.id]) {
      const brandRes: any = await getOptionList({
        attribute_id: brandAttr.id,
        status: 1,
        pageSize: 3000
      })
      parentOptionsCache.value[brandAttr.id] = brandRes.data.list.map((opt: AttributeOption) => ({
        value: opt.option_value,
        label: opt.translations['zh-tw']?.label || opt.translations['zh-cn']?.label || opt.option_value
      }))
    }

    const allBrands = parentOptionsCache.value[brandAttr.id] || []

    // 4. 如果该属性的选项有 parent_value，则过滤品牌；否则显示全部品牌
    if (parentValues.size > 0) {
      brandOptions.value = allBrands.filter(brand => parentValues.has(brand.value))
    } else {
      // 该属性的选项没有 parent_value，显示全部品牌
      brandOptions.value = allBrands
    }
  } catch (e) {
    brandOptions.value = []
  }
}

// 从指定属性加载选项到品牌列表
const loadBrandOptionsFromAttr = async (attrId: number) => {
  // 使用缓存或加载
  if (parentOptionsCache.value[attrId]) {
    brandOptions.value = parentOptionsCache.value[attrId]
    return
  }

  try {
    const res: any = await getOptionList({
      attribute_id: attrId,
      status: 1,
      pageSize: 3000
    })
    const options = res.data.list.map((opt: AttributeOption) => ({
      value: opt.option_value,
      label: opt.translations['zh-tw']?.label || opt.translations['zh-cn']?.label || opt.option_value
    }))
    parentOptionsCache.value[attrId] = options
    brandOptions.value = options
  } catch (e) {
    brandOptions.value = []
  }
}

// 加载搜索父属性选项
const loadSearchParentOptions = async () => {
  const attr = searchSelectedAttribute.value
  if (!attr?.parent_key) {
    searchParentOptions.value = []
    return
  }

  const parentAttr = getParentAttribute(attr.category_id, attr.parent_key)
  if (!parentAttr) {
    searchParentOptions.value = []
    return
  }

  // 使用缓存或加载
  if (parentOptionsCache.value[parentAttr.id]) {
    searchParentOptions.value = parentOptionsCache.value[parentAttr.id]
    return
  }

  try {
    const res: any = await getOptionList({
      attribute_id: parentAttr.id,
      status: 1,
      pageSize: 500
    })
    const options = res.data.list.map((opt: AttributeOption) => ({
      value: opt.option_value,
      label: opt.translations['zh-tw']?.label || opt.translations['zh-cn']?.label || opt.option_value
    }))
    parentOptionsCache.value[parentAttr.id] = options
    searchParentOptions.value = options
  } catch (e) {
    searchParentOptions.value = []
  }
}

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const filteredAttributes = computed(() => {
  if (searchForm.category_id === '' || searchForm.category_id === null || searchForm.category_id === undefined) {
    return attributeList.value
  }
  // 支持全局属性 (category_id = 0)，使用 == 进行宽松比较以处理类型差异
  return attributeList.value.filter(attr => attr.category_id == searchForm.category_id)
})

const getAttributesByCategory = (categoryId: number) => {
  // 使用 == 进行宽松比较以处理类型差异（后端可能返回字符串或数字）
  return attributeList.value.filter(attr => attr.category_id == categoryId)
}

const getDefaultForm = (): OptionForm => ({
  attribute_id: 0,
  option_value: '',
  image: '',
  parent_value: null,
  sort: 0,
  status: 1,
  translations: {
    'zh-tw': { label: '' },
    'en-us': { label: '' },
    'ja-jp': { label: '' }
  }
})

// 获取当前选中属性的信息
const selectedAttribute = computed(() => {
  return attributeList.value.find(attr => attr.id === form.attribute_id)
})

// 获取批量表单当前选中属性的信息
const batchSelectedAttribute = computed(() => {
  return attributeList.value.find(attr => attr.id === batchForm.attribute_id)
})

// 获取父属性（如品牌）
// 先在同分类下查找，找不到则查找全局属性（category_id=0）
const getParentAttribute = (categoryId: number, parentKey: string) => {
  // 先在同分类下查找
  let parentAttr = attributeList.value.find(
    attr => attr.category_id == categoryId && attr.attr_key === parentKey
  )
  // 如果找不到，查找全局属性
  if (!parentAttr) {
    parentAttr = attributeList.value.find(
      attr => attr.category_id == 0 && attr.attr_key === parentKey
    )
  }
  return parentAttr
}

// 加载父属性的选项
const loadParentOptions = async (parentAttrId: number) => {
  if (parentOptionsCache.value[parentAttrId]) {
    return parentOptionsCache.value[parentAttrId]
  }

  try {
    const res: any = await getOptionList({
      attribute_id: parentAttrId,
      status: 1,
      pageSize: 3000
    })
    const options = res.data.list.map((opt: AttributeOption) => ({
      value: opt.option_value,
      label: opt.translations['zh-tw']?.label || opt.translations['zh-cn']?.label || opt.option_value
    }))
    parentOptionsCache.value[parentAttrId] = options
    return options
  } catch (e) {
    return []
  }
}

// 获取父属性的选项列表
const parentAttributeOptions = computed(() => {
  const attr = selectedAttribute.value
  if (!attr?.parent_key) return []

  const parentAttr = getParentAttribute(attr.category_id, attr.parent_key)
  if (!parentAttr) return []

  return parentOptionsCache.value[parentAttr.id] || []
})

// 批量表单的父属性选项
const batchParentAttributeOptions = computed(() => {
  const attr = batchSelectedAttribute.value
  if (!attr?.parent_key) return []

  const parentAttr = getParentAttribute(attr.category_id, attr.parent_key)
  if (!parentAttr) return []

  return parentOptionsCache.value[parentAttr.id] || []
})

const form = reactive<OptionForm>(getDefaultForm())

interface BatchOption {
  option_value: string
  image: string
  label_zh: string
  label_en: string
  label_ja: string
  sort: number
}

const batchForm = reactive({
  attribute_id: 0,
  parent_value: null as string | null,
  options: [] as BatchOption[]
})

// 获取批量表单父属性名称
const getBatchParentAttrName = () => {
  const attr = batchSelectedAttribute.value
  if (!attr?.parent_key) return '父属性'
  const parentAttr = getParentAttribute(attr.category_id, attr.parent_key)
  return parentAttr?.translations['zh-tw']?.name || parentAttr?.translations['zh-cn']?.name || attr.parent_key
}

// 监听选中属性变化，加载父属性选项
watch(() => form.attribute_id, async (newAttrId) => {
  if (!newAttrId) return
  const attr = attributeList.value.find(a => a.id === newAttrId)
  if (attr?.parent_key) {
    const parentAttr = getParentAttribute(attr.category_id, attr.parent_key)
    if (parentAttr) {
      await loadParentOptions(parentAttr.id)
    }
  }
})

// 监听批量表单属性变化
watch(() => batchForm.attribute_id, async (newAttrId) => {
  if (!newAttrId) return
  const attr = attributeList.value.find(a => a.id === newAttrId)
  if (attr?.parent_key) {
    const parentAttr = getParentAttribute(attr.category_id, attr.parent_key)
    if (parentAttr) {
      await loadParentOptions(parentAttr.id)
    }
  }
})

const rules: FormRules = {
  attribute_id: [
    { required: true, message: '请选择属性', trigger: 'change' }
  ],
  option_value: [
    { required: true, message: '请输入选项值', trigger: 'blur' }
  ]
}

const loadData = async () => {
  loading.value = true
  try {
    const res: any = await getOptionList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      keyword: searchForm.keyword,
      attribute_id: searchForm.attribute_id,
      parent_value: searchForm.parent_value,
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

const loadAttributes = async () => {
  const res: any = await getAttributeList({ pageSize: 500 })
  attributeList.value = res.data.list
  // 加载品牌选项
  await loadBrandOptions()
}

const handleSearchCategoryChange = async () => {
  searchForm.attribute_id = ''
  searchForm.parent_value = ''
  searchParentOptions.value = []
  // 重新加载品牌选项（回到全局品牌）
  await loadBrandOptions()
}

const handleSearchAttributeChange = async () => {
  searchForm.parent_value = ''
  // 根据选中属性的 parent_key 加载对应的品牌选项
  await loadBrandOptions()
  await loadSearchParentOptions()
}

const resetSearch = () => {
  searchForm.keyword = ''
  searchForm.category_id = ''
  searchForm.attribute_id = ''
  searchForm.parent_value = ''
  searchForm.status = ''
  searchParentOptions.value = []
  pagination.page = 1
  loadData()
}

const handleAdd = () => {
  isEdit.value = false
  editId.value = null
  Object.assign(form, getDefaultForm())
  dialogVisible.value = true
}

const handleEdit = (row: AttributeOption) => {
  isEdit.value = true
  editId.value = row.id
  form.attribute_id = row.attribute_id
  form.option_value = row.option_value
  form.image = row.image || ''
  form.parent_value = row.parent_value || null
  form.sort = row.sort
  form.status = row.status
  form.translations = {
    'zh-tw': { label: row.translations['zh-tw']?.label || '' },
    'en-us': { label: row.translations['en-us']?.label || '' },
    'ja-jp': { label: row.translations['ja-jp']?.label || '' }
  }
  dialogVisible.value = true
}

const handleSubmit = async () => {
  await formRef.value?.validate()
  submitting.value = true
  try {
    if (isEdit.value && editId.value) {
      await updateOption(editId.value, form)
      ElMessage.success('更新成功')
    } else {
      await createOption(form)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    loadData()
  } finally {
    submitting.value = false
  }
}

const handleDelete = async (row: AttributeOption) => {
  await ElMessageBox.confirm('确定要删除该选项吗？', '提示', {
    type: 'warning'
  })
  await deleteOption(row.id)
  ElMessage.success('删除成功')
  loadData()
}

// 表格选择变化
const handleSelectionChange = (rows: AttributeOption[]) => {
  selectedRows.value = rows
}

// 批量删除
const handleBatchDelete = async () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择要删除的选项')
    return
  }

  await ElMessageBox.confirm(
    `确定要删除选中的 ${selectedRows.value.length} 个选项吗？此操作不可恢复！`,
    '批量删除确认',
    {
      type: 'warning',
      confirmButtonText: '确定删除',
      cancelButtonText: '取消'
    }
  )

  const ids = selectedRows.value.map(row => row.id)
  const res: any = await batchDeleteOptions(ids)
  ElMessage.success(`成功删除 ${res.data.count} 个选项`)
  selectedRows.value = []
  loadData()
}

// 批量新增相关
const getDefaultBatchOption = (): BatchOption => ({
  option_value: '',
  image: '',
  label_zh: '',
  label_en: '',
  label_ja: '',
  sort: 0
})

const handleBatchAdd = () => {
  batchForm.attribute_id = 0
  batchForm.parent_value = null
  batchForm.options = [getDefaultBatchOption()]
  batchDialogVisible.value = true
}

const addBatchOption = () => {
  batchForm.options.push(getDefaultBatchOption())
}

const removeBatchOption = (index: number) => {
  batchForm.options.splice(index, 1)
}

const handleBatchSubmit = async () => {
  await batchFormRef.value?.validate()

  if (!batchForm.attribute_id) {
    ElMessage.error('请选择属性')
    return
  }

  const validOptions = batchForm.options.filter(opt => opt.option_value.trim())
  if (validOptions.length === 0) {
    ElMessage.error('请至少填写一个选项')
    return
  }

  submitting.value = true
  try {
    const options = validOptions.map(opt => ({
      option_value: opt.option_value,
      parent_value: batchForm.parent_value,
      image: opt.image || null,
      sort: opt.sort,
      status: 1,
      translations: {
        'zh-tw': { label: opt.label_zh || opt.option_value },
        'en-us': { label: opt.label_en || opt.option_value },
        'ja-jp': { label: opt.label_ja || opt.option_value }
      }
    }))

    const res: any = await batchCreateOptions(batchForm.attribute_id, options)
    ElMessage.success(`成功创建 ${res.data.count} 个选项`)
    batchDialogVisible.value = false
    loadData()
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadData()
  loadCategories()
  loadAttributes()
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

.batch-options {
  width: 100%;
}

.batch-option-header {
  display: flex;
  gap: 10px;
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid #eee;
  font-size: 12px;
  color: #909399;
}

.batch-option-row {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
  align-items: center;
}

.text-muted {
  color: #999;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.table-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  padding: 8px 12px;
  background: #f0f9eb;
  border-radius: 4px;
}

.selected-count {
  color: #67c23a;
  font-weight: 500;
}
</style>
