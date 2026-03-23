<template>
  <div class="promotion-list">
    <!-- 搜索区域 -->
    <el-card class="search-card" shadow="never">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="活动名称" clearable />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="searchForm.type" placeholder="全部" clearable>
            <el-option label="限时折扣" :value="1" />
            <el-option label="满减活动" :value="2" />
            <el-option label="秒杀活动" :value="3" />
            <el-option label="拼团活动" :value="4" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable>
            <el-option label="未开始" :value="0" />
            <el-option label="进行中" :value="1" />
            <el-option label="已结束" :value="2" />
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
          <span>活动列表</span>
          <el-button type="primary" @click="handleAdd">新增活动</el-button>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="活动名称" min-width="150" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="typeColors[row.type]" size="small">{{ typeNames[row.type] }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="banner" label="Banner" width="120">
          <template #default="{ row }">
            <el-image v-if="row.banner" :src="row.banner" :preview-src-list="[row.banner]" fit="cover" style="width: 80px; height: 40px;" />
            <span v-else class="text-muted">无</span>
          </template>
        </el-table-column>
        <el-table-column prop="start_time" label="开始时间" width="170" />
        <el-table-column prop="end_time" label="结束时间" width="170" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="statusTypes[row.status]" size="small">{{ statusNames[row.status] }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="success" link @click="handleManageGoods(row)">商品</el-button>
            <el-button v-if="row.status === 0" type="warning" link @click="handleStart(row)">开始</el-button>
            <el-button v-if="row.status === 1" type="info" link @click="handleStop(row)">结束</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrapper">
        <el-pagination v-model:current-page="pagination.page" v-model:page-size="pagination.pageSize" :total="pagination.total" layout="total, sizes, prev, pager, next" @size-change="fetchList" @current-change="fetchList" />
      </div>
    </el-card>

    <!-- 活动商品管理对话框 -->
    <el-dialog v-model="goodsDialogVisible" :title="`活动商品管理 - ${currentPromotion?.name || ''}`" width="1200px" top="2vh" class="goods-manage-dialog">
      <div class="goods-dialog-content">
        <!-- 添加商品区域 -->
        <el-card shadow="never" class="add-goods-card">
          <template #header>
            <div class="card-header">
              <span>添加商品到活动</span>
              <div class="batch-actions" v-if="selectedGoods.length > 0">
                <el-tag type="info" size="small">已选 {{ selectedGoods.length }} 件</el-tag>
                <el-input-number v-model="batchDiscountRate" :min="0.1" :max="1" :step="0.1" :precision="2" size="small" style="width: 100px; margin: 0 8px;" />
                <span class="discount-hint">折扣率</span>
                <el-button type="primary" size="small" @click="handleBatchAdd" :loading="batchAdding">批量添加</el-button>
                <el-button size="small" @click="clearSelection">清空选择</el-button>
              </div>
            </div>
          </template>

          <!-- 筛选条件 -->
          <div class="filter-wrapper">
            <el-form :inline="true" size="small">
              <el-form-item label="关键词">
                <el-input v-model="goodsSearchForm.keyword" placeholder="商品名称/编号" clearable style="width: 150px;" @keyup.enter="searchAvailableGoods" />
              </el-form-item>
              <el-form-item label="分类">
                <el-cascader v-model="goodsSearchForm.category_id" :options="categoryTree" :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }" placeholder="选择分类" clearable style="width: 180px;" />
              </el-form-item>
              <el-form-item label="品牌">
                <el-select v-model="goodsSearchForm.brand_id" placeholder="选择品牌" clearable style="width: 120px;">
                  <el-option v-for="brand in brandList" :key="brand.id" :label="brand.name" :value="brand.id" />
                </el-select>
              </el-form-item>
              <el-form-item label="类型">
                <el-select v-model="goodsSearchForm.goods_type" placeholder="全部" clearable style="width: 100px;">
                  <el-option label="C2C商品" :value="1" />
                  <el-option label="B2C商品" :value="2" />
                </el-select>
              </el-form-item>
              <el-form-item>
                <el-button type="primary" @click="searchAvailableGoods">搜索</el-button>
                <el-button @click="resetGoodsSearch">重置</el-button>
              </el-form-item>
            </el-form>
          </div>

          <!-- 商品表格 -->
          <el-table
            ref="availableGoodsTableRef"
            :data="availableGoods"
            v-loading="searchingGoods"
            size="small"
            max-height="300"
            @selection-change="handleSelectionChange"
          >
            <el-table-column type="selection" width="45" />
            <el-table-column prop="goods_no" label="编号" width="100" />
            <el-table-column label="图片" width="60">
              <template #default="{ row }">
                <el-image v-if="row.images && row.images.length" :src="row.images[0]" style="width: 40px; height: 40px;" fit="cover" />
              </template>
            </el-table-column>
            <el-table-column prop="title" label="商品名称" min-width="180" show-overflow-tooltip />
            <el-table-column prop="price" label="原价" width="90">
              <template #default="{ row }">¥{{ row.price }}</template>
            </el-table-column>
            <el-table-column prop="stock" label="库存" width="70" />
            <el-table-column prop="goods_type" label="类型" width="80">
              <template #default="{ row }">
                <el-tag :type="row.goods_type === 2 ? 'success' : 'info'" size="small">
                  {{ row.goods_type === 2 ? 'B2C' : 'C2C' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="70" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link size="small" @click="openAddGoodsForm(row)">添加</el-button>
              </template>
            </el-table-column>
          </el-table>

          <!-- 分页 -->
          <div class="pagination-wrapper" v-if="availableGoodsPagination.total > 0">
            <el-pagination
              :current-page="availableGoodsPagination.page"
              :page-size="availableGoodsPagination.pageSize"
              :total="availableGoodsPagination.total"
              :page-sizes="[20, 50, 100]"
              layout="total, sizes, prev, pager, next"
              small
              @size-change="handleAvailableGoodsSizeChange"
              @current-change="handleAvailableGoodsPageChange"
            />
          </div>
        </el-card>

        <!-- 已添加商品列表 -->
        <el-card shadow="never" class="promotion-goods-card">
          <template #header>
            <div class="card-header">
              <span>已添加商品 ({{ promotionGoodsPagination.total }})</span>
              <div class="batch-actions" v-if="selectedPromotionGoods.length > 0">
                <el-tag type="info" size="small">已选 {{ selectedPromotionGoods.length }} 件</el-tag>
                <el-button type="warning" size="small" @click="openBatchEditDialog">批量编辑折扣</el-button>
                <el-button size="small" @click="clearPromotionGoodsSelection">清空选择</el-button>
              </div>
            </div>
          </template>
          <el-table ref="promotionGoodsTableRef" :data="promotionGoodsList" v-loading="loadingPromotionGoods" size="small" max-height="280" @selection-change="handlePromotionGoodsSelectionChange">
            <el-table-column type="selection" width="45" />
            <el-table-column prop="goods.goods_no" label="编号" width="100" />
            <el-table-column label="图片" width="60">
              <template #default="{ row }">
                <el-image v-if="row.goods?.images && row.goods.images.length" :src="row.goods.images[0]" style="width: 40px; height: 40px;" fit="cover" />
              </template>
            </el-table-column>
            <el-table-column prop="goods.title" label="商品名称" min-width="150" show-overflow-tooltip />
            <el-table-column prop="goods.price" label="原价" width="80">
              <template #default="{ row }">¥{{ row.goods?.price }}</template>
            </el-table-column>
            <el-table-column prop="promotion_price" label="活动价" width="90">
              <template #default="{ row }">
                <span class="promotion-price">¥{{ row.promotion_price }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="discount" label="折扣" width="70">
              <template #default="{ row }">
                <el-tag type="danger" size="small">{{ (row.discount * 10).toFixed(1) }}折</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="stock" label="活动库存" width="80" />
            <el-table-column prop="sold_count" label="已售" width="60" />
            <el-table-column prop="limit_per_user" label="限购" width="60">
              <template #default="{ row }">{{ row.limit_per_user || '不限' }}</template>
            </el-table-column>
            <el-table-column label="操作" width="100" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link size="small" @click="openEditGoodsForm(row)">编辑</el-button>
                <el-button type="danger" link size="small" @click="handleRemoveGoods(row)">移除</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div class="pagination-wrapper" v-if="promotionGoodsPagination.total > promotionGoodsPagination.pageSize">
            <el-pagination :current-page="promotionGoodsPagination.page" :page-size="promotionGoodsPagination.pageSize" :total="promotionGoodsPagination.total" layout="total, prev, pager, next" size="small" @current-change="handlePromotionGoodsPageChange" />
          </div>
        </el-card>
      </div>
    </el-dialog>

    <!-- 添加/编辑活动商品表单对话框 -->
    <el-dialog v-model="goodsFormVisible" :title="isEditGoods ? '编辑活动商品' : '添加活动商品'" width="450px" append-to-body>
      <el-form :model="goodsForm" label-width="100px">
        <el-form-item label="商品">
          <span>{{ goodsForm.goodsTitle }}</span>
        </el-form-item>
        <el-form-item label="原价">
          <span>¥{{ goodsForm.originalPrice }}</span>
        </el-form-item>
        <el-form-item label="活动价" required>
          <el-input-number v-model="goodsForm.promotion_price" :min="0.01" :precision="2" :step="1" style="width: 100%;" />
        </el-form-item>
        <el-form-item label="活动库存">
          <el-input-number v-model="goodsForm.stock" :min="0" style="width: 100%;" />
        </el-form-item>
        <el-form-item label="每人限购">
          <el-input-number v-model="goodsForm.limit_per_user" :min="0" style="width: 100%;" />
          <div class="form-tip">0表示不限购</div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="goodsFormVisible = false">取消</el-button>
        <el-button type="primary" @click="handleGoodsFormSubmit" :loading="submittingGoods">确定</el-button>
      </template>
    </el-dialog>

    <!-- 批量编辑折扣对话框 -->
    <el-dialog v-model="batchEditDialogVisible" title="批量编辑折扣" width="500px" append-to-body>
      <div class="batch-edit-content">
        <div class="batch-edit-info">
          <el-tag type="info">已选择 {{ selectedPromotionGoods.length }} 件商品</el-tag>
        </div>
        <div class="batch-edit-slider">
          <div class="slider-label">
            <span>设置折扣：</span>
            <span class="discount-value">{{ batchEditDiscount.toFixed(1) }} 折</span>
          </div>
          <el-slider
            v-model="batchEditDiscount"
            :min="1"
            :max="10"
            :step="0.1"
            :marks="discountMarks"
            show-stops
          />
          <div class="slider-tips">
            <span>1折 (10%)</span>
            <span>10折 (原价)</span>
          </div>
        </div>
        <div class="batch-edit-preview">
          <div class="preview-title">预览（部分商品）</div>
          <el-table :data="batchEditPreviewList" size="small" max-height="200">
            <el-table-column prop="goods.title" label="商品名称" min-width="150" show-overflow-tooltip />
            <el-table-column prop="goods.price" label="原价" width="80">
              <template #default="{ row }">¥{{ row.goods?.price }}</template>
            </el-table-column>
            <el-table-column label="新活动价" width="100">
              <template #default="{ row }">
                <span class="promotion-price">¥{{ calculateNewPrice(row.goods?.price || 0) }}</span>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
      <template #footer>
        <el-button @click="batchEditDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleBatchEditSubmit" :loading="batchEditing">确定修改</el-button>
      </template>
    </el-dialog>

    <!-- 编辑对话框 -->
    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑活动' : '新增活动'" width="700px">
      <el-form :model="form" label-width="100px">
        <!-- 多语言输入 -->
        <el-form-item label="名称/描述" required>
          <el-tabs v-model="activeLang" type="card" class="lang-tabs">
            <el-tab-pane label="繁體中文" name="zh-tw">
              <el-form-item label="名稱" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].name" placeholder="請輸入繁體中文名稱" />
              </el-form-item>
              <el-form-item label="描述" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['zh-tw'].description" type="textarea" :rows="2" placeholder="活動描述（繁體中文）" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="English" name="en-us">
              <el-form-item label="Name" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].name" placeholder="Enter promotion name" />
              </el-form-item>
              <el-form-item label="Desc" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['en-us'].description" type="textarea" :rows="2" placeholder="Description (English)" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="日本語" name="ja-jp">
              <el-form-item label="名前" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].name" placeholder="プロモーション名を入力してください" />
              </el-form-item>
              <el-form-item label="説明" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['ja-jp'].description" type="textarea" :rows="2" placeholder="キャンペーンの説明（日本語）" />
              </el-form-item>
            </el-tab-pane>
          </el-tabs>
        </el-form-item>
        <el-form-item label="活动类型" required>
          <el-select v-model="form.type" placeholder="请选择类型" style="width: 100%;">
            <el-option label="限时折扣" :value="1" />
            <el-option label="满减活动" :value="2" />
            <el-option label="秒杀活动" :value="3" />
            <el-option label="拼团活动" :value="4" />
          </el-select>
        </el-form-item>
        <el-form-item label="活动时间" required>
          <el-date-picker v-model="dateRange" type="datetimerange" range-separator="至" start-placeholder="开始时间" end-placeholder="结束时间" value-format="YYYY-MM-DD HH:mm:ss" style="width: 100%;" />
        </el-form-item>
        <el-form-item label="Banner">
          <ImagePicker v-model="form.banner" width="200px" height="100px" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type TableInstance } from 'element-plus'
import {
  getPromotionList, getPromotionDetail, createPromotion, updatePromotion, deletePromotion, startPromotion, stopPromotion,
  getPromotionGoodsList, searchPromotionGoods, addPromotionGoods, updatePromotionGoods, removePromotionGoods, batchAddPromotionGoods, batchUpdatePromotionGoods,
  type Promotion, type PromotionGoods
} from '@/api/promotion'
import { getCategoryTree } from '@/api/category'
import { getAllBrands } from '@/api/brand'
import ImagePicker from '@/components/ImagePicker.vue'

const typeNames: Record<number, string> = { 1: '限时折扣', 2: '满减活动', 3: '秒杀活动', 4: '拼团活动' }
const typeColors: Record<number, string> = { 1: 'primary', 2: 'success', 3: 'danger', 4: 'warning' }
const statusNames: Record<number, string> = { 0: '未开始', 1: '进行中', 2: '已结束' }
const statusTypes: Record<number, string> = { 0: 'info', 1: 'success', 2: 'danger' }

const searchForm = reactive({ keyword: '', type: '' as number | string, status: '' as number | string })
const list = ref<Promotion[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

const dialogVisible = ref(false)
const isEdit = ref(false)
const dateRange = ref<[string, string] | null>(null)
const activeLang = ref('zh-tw')

// 初始翻译结构
const emptyTranslations = () => ({
  'zh-tw': { name: '', description: '' },
  'en-us': { name: '', description: '' },
  'ja-jp': { name: '', description: '' }
})

// 合并翻译数据，确保所有语言键都存在
const mergeTranslations = (data: any) => {
  const empty = emptyTranslations()
  if (!data || typeof data !== 'object') return empty
  return {
    'zh-tw': { ...empty['zh-tw'], ...(data['zh-tw'] || {}) },
    'en-us': { ...empty['en-us'], ...(data['en-us'] || {}) },
    'ja-jp': { ...empty['ja-jp'], ...(data['ja-jp'] || {}) }
  }
}

const form = reactive({
  id: undefined as number | undefined,
  type: 1,
  banner: '',
  sort: 0,
  translations: emptyTranslations() as Record<string, { name: string; description: string }>
})

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getPromotionList({ page: pagination.page, pageSize: pagination.pageSize, ...searchForm })
    list.value = res.data.list || []; pagination.total = res.data.total || 0
  } catch (error) { console.error('获取列表失败:', error) }
  finally { loading.value = false }
}

const handleSearch = () => { pagination.page = 1; fetchList() }
const handleReset = () => { searchForm.keyword = ''; searchForm.type = ''; searchForm.status = ''; handleSearch() }

const handleAdd = () => {
  isEdit.value = false
  activeLang.value = 'zh-tw'
  Object.assign(form, { id: undefined, type: 1, banner: '', sort: 0, translations: emptyTranslations() })
  dateRange.value = null
  dialogVisible.value = true
}

const handleEdit = async (row: Promotion) => {
  isEdit.value = true
  activeLang.value = 'zh-tw'
  try {
    const res: any = await getPromotionDetail(row.id)
    const data = res.data
    dateRange.value = data.start_time && data.end_time ? [data.start_time, data.end_time] : null
    Object.assign(form, {
      id: data.id,
      type: data.type,
      banner: data.banner || '',
      sort: data.sort || 0,
      translations: mergeTranslations(data.translations)
    })
    dialogVisible.value = true
  } catch (error) {
    console.error('获取详情失败:', error)
    ElMessage.error('获取详情失败')
  }
}

const handleSubmit = async () => {
  // 验证至少填写一种语言的名称
  if (!form.translations['zh-tw'].name && !form.translations['en-us'].name && !form.translations['ja-jp'].name) {
    ElMessage.warning('請至少填寫一種語言的名稱')
    return
  }
  if (!dateRange.value) { ElMessage.warning('请选择活动时间'); return }

  const data = { ...form, start_time: dateRange.value[0], end_time: dateRange.value[1] }
  try {
    if (isEdit.value && form.id) {
      await updatePromotion(form.id, data)
      ElMessage.success('更新成功')
    } else {
      await createPromotion(data)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchList()
  } catch (error) { console.error(error) }
}

const handleDelete = async (row: Promotion) => {
  try {
    await ElMessageBox.confirm('确定删除该活动吗？', '提示', { type: 'warning' })
    await deletePromotion(row.id); ElMessage.success('删除成功'); fetchList()
  } catch (error) { if (error !== 'cancel') console.error(error) }
}

const handleStart = async (row: Promotion) => {
  try {
    await ElMessageBox.confirm('确定开始该活动吗？', '提示', { type: 'warning' })
    await startPromotion(row.id); ElMessage.success('活动已开始'); fetchList()
  } catch (error) { if (error !== 'cancel') console.error(error) }
}

const handleStop = async (row: Promotion) => {
  try {
    await ElMessageBox.confirm('确定结束该活动吗？', '提示', { type: 'warning' })
    await stopPromotion(row.id); ElMessage.success('活动已结束'); fetchList()
  } catch (error) { if (error !== 'cancel') console.error(error) }
}

// ===================== 活动商品管理 =====================

const goodsDialogVisible = ref(false)
const currentPromotion = ref<Promotion | null>(null)
const promotionGoodsList = ref<PromotionGoods[]>([])
const loadingPromotionGoods = ref(false)
const promotionGoodsPagination = reactive({ page: 1, pageSize: 10, total: 0 })

// 筛选条件
const categoryTree = ref<any[]>([])
const brandList = ref<any[]>([])
const goodsSearchForm = reactive({
  keyword: '',
  category_id: '' as number | string,
  brand_id: '' as number | string,
  goods_type: '' as number | string
})

// 搜索可添加的商品
const availableGoods = ref<any[]>([])
const searchingGoods = ref(false)
const availableGoodsPagination = reactive({ page: 1, pageSize: 20, total: 0 })
const availableGoodsTableRef = ref<TableInstance>()

// 多选功能
const selectedGoods = ref<any[]>([])
const batchDiscountRate = ref(0.8)
const batchAdding = ref(false)

// 添加/编辑商品表单
const goodsFormVisible = ref(false)
const isEditGoods = ref(false)
const submittingGoods = ref(false)
const goodsForm = reactive({
  id: 0,
  goods_id: 0,
  goodsTitle: '',
  originalPrice: 0,
  promotion_price: 0,
  stock: 0,
  limit_per_user: 0
})

// 已添加商品多选
const promotionGoodsTableRef = ref<TableInstance>()
const selectedPromotionGoods = ref<PromotionGoods[]>([])

// 批量编辑折扣
const batchEditDialogVisible = ref(false)
const batchEditDiscount = ref(8) // 默认8折
const batchEditing = ref(false)
const discountMarks = {
  1: '1折',
  5: '5折',
  10: '10折'
}

// 批量编辑预览列表（最多显示5条）
const batchEditPreviewList = computed(() => selectedPromotionGoods.value.slice(0, 5))

// 计算新价格
const calculateNewPrice = (originalPrice: number) => {
  return (originalPrice * batchEditDiscount.value / 10).toFixed(2)
}

// 已添加商品多选处理
const handlePromotionGoodsSelectionChange = (selection: PromotionGoods[]) => {
  selectedPromotionGoods.value = selection
}

const clearPromotionGoodsSelection = () => {
  selectedPromotionGoods.value = []
  promotionGoodsTableRef.value?.clearSelection()
}

// 打开批量编辑弹窗
const openBatchEditDialog = () => {
  if (selectedPromotionGoods.value.length === 0) {
    ElMessage.warning('请先选择要编辑的商品')
    return
  }
  batchEditDiscount.value = 8 // 重置为8折
  batchEditDialogVisible.value = true
}

// 批量编辑提交
const handleBatchEditSubmit = async () => {
  if (!currentPromotion.value || selectedPromotionGoods.value.length === 0) return

  batchEditing.value = true
  try {
    const goodsIds = selectedPromotionGoods.value.map(g => g.id)
    const res: any = await batchUpdatePromotionGoods(currentPromotion.value.id, {
      goods_ids: goodsIds,
      discount: batchEditDiscount.value
    })
    ElMessage.success(res.message || '批量更新成功')
    batchEditDialogVisible.value = false
    clearPromotionGoodsSelection()
    fetchPromotionGoods()
  } catch (error) {
    console.error('批量更新失败:', error)
  } finally {
    batchEditing.value = false
  }
}

// 处理分类树，从translations中提取name
const processCategoryTree = (categories: any[], locale: string = 'zh-tw'): any[] => {
  return categories.map(category => {
    const processed: any = {
      ...category,
      name: category.translations?.[locale]?.name || category.name || `分类${category.id}`
    }
    if (category.children && category.children.length > 0) {
      processed.children = processCategoryTree(category.children, locale)
    }
    return processed
  })
}

// 加载分类和品牌数据
const loadFilterOptions = async () => {
  try {
    const [categoryRes, brandRes]: any[] = await Promise.all([
      getCategoryTree(),
      getAllBrands()
    ])
    // 处理分类树数据，提取name字段
    categoryTree.value = processCategoryTree(categoryRes.data || [])
    brandList.value = brandRes.data || []
  } catch (error) {
    console.error('加载筛选数据失败:', error)
  }
}

// 打开活动商品管理
const handleManageGoods = async (row: Promotion) => {
  currentPromotion.value = row
  goodsDialogVisible.value = true
  // 重置状态
  Object.assign(goodsSearchForm, { keyword: '', category_id: '', brand_id: '', goods_type: '' })
  availableGoods.value = []
  selectedGoods.value = []
  availableGoodsPagination.page = 1
  availableGoodsPagination.total = 0
  promotionGoodsPagination.page = 1
  // 加载数据
  await Promise.all([
    loadFilterOptions(),
    fetchPromotionGoods()
  ])
}

// 获取活动商品列表
const fetchPromotionGoods = async () => {
  if (!currentPromotion.value) return
  loadingPromotionGoods.value = true
  try {
    const res: any = await getPromotionGoodsList(currentPromotion.value.id, {
      page: promotionGoodsPagination.page,
      pageSize: promotionGoodsPagination.pageSize
    })
    promotionGoodsList.value = res.data.list || []
    promotionGoodsPagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取活动商品失败:', error)
  } finally {
    loadingPromotionGoods.value = false
  }
}

// 活动商品分页
const handlePromotionGoodsPageChange = (page: number) => {
  promotionGoodsPagination.page = page
  fetchPromotionGoods()
}

// 搜索可添加的商品
const searchAvailableGoods = async () => {
  if (!currentPromotion.value) return
  searchingGoods.value = true
  selectedGoods.value = []
  availableGoodsPagination.page = 1
  try {
    const res: any = await searchPromotionGoods(currentPromotion.value.id, {
      keyword: goodsSearchForm.keyword,
      category_id: goodsSearchForm.category_id,
      brand_id: goodsSearchForm.brand_id,
      goods_type: goodsSearchForm.goods_type,
      page: availableGoodsPagination.page,
      pageSize: availableGoodsPagination.pageSize
    })
    availableGoods.value = res.data.list || []
    availableGoodsPagination.total = res.data.total || 0
  } catch (error) {
    console.error('搜索商品失败:', error)
  } finally {
    searchingGoods.value = false
  }
}

// 重置搜索条件
const resetGoodsSearch = () => {
  Object.assign(goodsSearchForm, { keyword: '', category_id: '', brand_id: '', goods_type: '' })
  availableGoods.value = []
  selectedGoods.value = []
  availableGoodsPagination.page = 1
  availableGoodsPagination.total = 0
}

// 可添加商品分页
const handleAvailableGoodsPageChange = async (page: number) => {
  if (!currentPromotion.value) return
  availableGoodsPagination.page = page
  searchingGoods.value = true
  try {
    const res: any = await searchPromotionGoods(currentPromotion.value.id, {
      keyword: goodsSearchForm.keyword,
      category_id: goodsSearchForm.category_id,
      brand_id: goodsSearchForm.brand_id,
      goods_type: goodsSearchForm.goods_type,
      page: availableGoodsPagination.page,
      pageSize: availableGoodsPagination.pageSize
    })
    availableGoods.value = res.data.list || []
    availableGoodsPagination.total = res.data.total || 0
  } catch (error) {
    console.error('搜索商品失败:', error)
  } finally {
    searchingGoods.value = false
  }
}

const handleAvailableGoodsSizeChange = (size: number) => {
  availableGoodsPagination.pageSize = size
  availableGoodsPagination.page = 1
  searchAvailableGoods()
}

// 多选处理
const handleSelectionChange = (selection: any[]) => {
  selectedGoods.value = selection
}

const clearSelection = () => {
  selectedGoods.value = []
  availableGoodsTableRef.value?.clearSelection()
}

// 批量添加
const handleBatchAdd = async () => {
  if (!currentPromotion.value || selectedGoods.value.length === 0) return
  if (batchDiscountRate.value <= 0 || batchDiscountRate.value > 1) {
    ElMessage.warning('折扣率必须在 0.1 到 1 之间')
    return
  }
  batchAdding.value = true
  try {
    const goodsIds = selectedGoods.value.map(g => g.id)
    const res: any = await batchAddPromotionGoods(currentPromotion.value.id, {
      goods_ids: goodsIds,
      discount_rate: batchDiscountRate.value
    })
    ElMessage.success(res.message || '批量添加成功')
    clearSelection()
    searchAvailableGoods()
    fetchPromotionGoods()
  } catch (error) {
    console.error('批量添加失败:', error)
  } finally {
    batchAdding.value = false
  }
}

// 打开添加商品表单
const openAddGoodsForm = (goods: any) => {
  isEditGoods.value = false
  Object.assign(goodsForm, {
    id: 0,
    goods_id: goods.id,
    goodsTitle: goods.title,
    originalPrice: goods.price,
    promotion_price: goods.price,
    stock: goods.stock,
    limit_per_user: 0
  })
  goodsFormVisible.value = true
}

// 打开编辑商品表单
const openEditGoodsForm = (item: PromotionGoods) => {
  isEditGoods.value = true
  Object.assign(goodsForm, {
    id: item.id,
    goods_id: item.goods_id,
    goodsTitle: item.goods?.title || '',
    originalPrice: item.goods?.price || 0,
    promotion_price: item.promotion_price,
    stock: item.stock,
    limit_per_user: item.limit_per_user
  })
  goodsFormVisible.value = true
}

// 提交商品表单
const handleGoodsFormSubmit = async () => {
  if (!currentPromotion.value) return
  if (goodsForm.promotion_price <= 0) {
    ElMessage.warning('请输入活动价')
    return
  }
  submittingGoods.value = true
  try {
    if (isEditGoods.value) {
      await updatePromotionGoods(currentPromotion.value.id, goodsForm.id, {
        promotion_price: goodsForm.promotion_price,
        stock: goodsForm.stock,
        limit_per_user: goodsForm.limit_per_user
      })
      ElMessage.success('更新成功')
    } else {
      await addPromotionGoods(currentPromotion.value.id, {
        goods_id: goodsForm.goods_id,
        promotion_price: goodsForm.promotion_price,
        stock: goodsForm.stock,
        limit_per_user: goodsForm.limit_per_user
      })
      ElMessage.success('添加成功')
      // 刷新搜索列表
      if (availableGoods.value.length > 0) {
        searchAvailableGoods()
      }
    }
    goodsFormVisible.value = false
    fetchPromotionGoods()
  } catch (error) {
    console.error('提交失败:', error)
  } finally {
    submittingGoods.value = false
  }
}

// 移除活动商品
const handleRemoveGoods = async (item: PromotionGoods) => {
  if (!currentPromotion.value) return
  try {
    await ElMessageBox.confirm('确定从活动中移除该商品吗？', '提示', { type: 'warning' })
    await removePromotionGoods(currentPromotion.value.id, item.id)
    ElMessage.success('移除成功')
    fetchPromotionGoods()
    // 刷新搜索列表
    if (availableGoods.value.length > 0) {
      searchAvailableGoods()
    }
  } catch (error) {
    if (error !== 'cancel') console.error('移除失败:', error)
  }
}

onMounted(() => { fetchList() })
</script>

<style scoped>
.promotion-list { padding: 20px; }
.search-card { margin-bottom: 20px; }
.card-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.pagination-wrapper { margin-top: 15px; display: flex; justify-content: flex-end; }
.text-muted { color: #c0c4cc; }
.lang-tabs { width: 100%; }
.lang-tabs :deep(.el-tabs__content) { padding: 15px; background: #f9f9f9; border-radius: 4px; }

/* 活动商品管理对话框样式 */
.goods-manage-dialog :deep(.el-dialog__body) { padding: 15px 20px; max-height: calc(100vh - 150px); overflow-y: auto; }
.goods-dialog-content { display: flex; flex-direction: column; gap: 15px; }
.add-goods-card { margin-bottom: 0; }
.add-goods-card :deep(.el-card__header) { padding: 12px 15px; background: #f5f7fa; }
.add-goods-card :deep(.el-card__body) { padding: 15px; }
.promotion-goods-card :deep(.el-card__header) { padding: 12px 15px; background: #e6f7ff; }
.promotion-goods-card :deep(.el-card__body) { padding: 15px; }

/* 筛选区域 */
.filter-wrapper { margin-bottom: 10px; }
.filter-wrapper :deep(.el-form-item) { margin-bottom: 10px; margin-right: 10px; }

/* 批量操作区域 */
.batch-actions { display: flex; align-items: center; gap: 8px; }
.discount-hint { font-size: 12px; color: #909399; margin-right: 8px; }

/* 商品价格样式 */
.promotion-price { color: #f56c6c; font-weight: 600; }
.form-tip { font-size: 12px; color: #909399; margin-top: 4px; }

/* 批量编辑折扣弹窗 */
.batch-edit-content { padding: 10px 0; }
.batch-edit-info { margin-bottom: 20px; text-align: center; }
.batch-edit-slider { margin-bottom: 25px; padding: 0 10px; }
.slider-label { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-size: 14px; }
.discount-value { font-size: 24px; font-weight: 600; color: #f56c6c; }
.slider-tips { display: flex; justify-content: space-between; margin-top: 10px; font-size: 12px; color: #909399; }
.batch-edit-preview { border-top: 1px solid #ebeef5; padding-top: 15px; }
.preview-title { font-size: 13px; color: #606266; margin-bottom: 10px; }
</style>
