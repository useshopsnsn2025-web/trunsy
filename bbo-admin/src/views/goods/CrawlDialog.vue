<template>
  <el-dialog
    v-model="visible"
    title="eBay 商品采集"
    width="900px"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <!-- 步骤条 -->
    <el-steps :active="currentStep" finish-status="success" style="margin-bottom: 20px">
      <el-step title="配置参数" />
      <el-step title="预览结果" />
      <el-step title="确认导入" />
    </el-steps>

    <!-- 步骤1：配置参数 -->
    <div v-show="currentStep === 0">
      <el-form :model="crawlForm" label-width="100px">
        <el-form-item label="eBay URL" required>
          <el-input
            v-model="crawlForm.url"
            placeholder="输入 eBay 列表页 URL（搜索结果页或分类页）"
            clearable
          />
          <div class="form-tip">支持 eBay 搜索结果页和 Browse 分类页 URL</div>
        </el-form-item>
        <el-form-item label="采集页数">
          <el-select v-model="crawlForm.pages" style="width: 120px">
            <el-option :value="1" label="1 页" />
            <el-option :value="2" label="2 页" />
            <el-option :value="3" label="3 页" />
            <el-option :value="5" label="5 页" />
          </el-select>
          <span class="form-tip" style="margin-left: 10px">每页约 60 个商品</span>
        </el-form-item>
        <el-form-item label="目标分类" required>
          <el-select v-model="crawlForm.category_id" placeholder="选择分类" clearable>
            <el-option
              v-for="item in categoryList"
              :key="item.id"
              :label="item.translations['zh-tw']?.name || item.translations['en-us']?.name || item.id"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="商品类型">
          <el-select v-model="crawlForm.type" style="width: 120px">
            <el-option :value="1" label="C2C (个人)" />
            <el-option :value="2" label="B2C (商家)" />
          </el-select>
        </el-form-item>
        <el-form-item label="默认状态">
          <el-select v-model="crawlForm.status" style="width: 120px">
            <el-option :value="0" label="待审核" />
            <el-option :value="1" label="直接上架" />
          </el-select>
        </el-form-item>
        <el-form-item label="默认包邮">
          <el-switch v-model="crawlForm.free_shipping" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="价格调整">
          <div style="display: flex; align-items: center; gap: 8px;">
            <el-input-number
              v-model="crawlForm.price_adjust"
              :precision="2"
              :step="10"
              style="width: 200px"
              controls-position="right"
            />
            <span class="form-tip">
              {{ crawlForm.price_adjust > 0 ? `每个商品价格 +${crawlForm.price_adjust}` : crawlForm.price_adjust < 0 ? `每个商品价格 ${crawlForm.price_adjust}` : '不调整价格' }}
            </span>
          </div>
          <div class="form-tip">正数加价，负数减价，0 不调整（单位与商品原始货币一致）</div>
        </el-form-item>
      </el-form>

      <!-- 动态分类属性 -->
      <template v-if="categoryAttributes.length > 0">
        <el-divider content-position="left">商品属性（统一配置）</el-divider>
        <el-alert
          title="以下属性将统一应用到所有采集的商品"
          type="info"
          show-icon
          :closable="false"
          style="margin-bottom: 16px;"
        />
        <el-form :model="specsForm" label-width="100px">
          <el-row :gutter="20" v-loading="loadingAttributes">
            <el-col :span="12" v-for="attr in categoryAttributes" :key="attr.key">
              <el-form-item :label="attr.name">
                <!-- 单选下拉 -->
                <el-select
                  v-if="attr.input_type === 'select'"
                  v-model="specsForm[attr.key]"
                  :placeholder="attr.parent_key && !specsForm[attr.parent_key] ? `请先选择${categoryAttributes.find(a => a.key === attr.parent_key)?.name || ''}` : (attr.placeholder || `选择${attr.name}`)"
                  :disabled="!!attr.parent_key && !specsForm[attr.parent_key]"
                  filterable
                  allow-create
                  default-first-option
                  style="width: 100%"
                  clearable
                >
                  <el-option
                    v-for="opt in getFilteredOptions(attr)"
                    :key="opt.value"
                    :label="opt.label"
                    :value="opt.value"
                  />
                </el-select>
                <!-- 多选下拉 -->
                <el-select
                  v-else-if="attr.input_type === 'multi_select'"
                  v-model="specsForm[attr.key]"
                  :placeholder="attr.placeholder || `选择${attr.name}`"
                  filterable
                  allow-create
                  default-first-option
                  style="width: 100%"
                  multiple
                  clearable
                >
                  <el-option
                    v-for="opt in getFilteredOptions(attr)"
                    :key="opt.value"
                    :label="opt.label"
                    :value="opt.value"
                  />
                </el-select>
                <!-- 文本输入 -->
                <el-input
                  v-else
                  v-model="specsForm[attr.key]"
                  :placeholder="attr.placeholder || `输入${attr.name}`"
                />
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>
      </template>

      <!-- 商品状态配置 -->
      <template v-if="conditionGroups.length > 0">
        <el-divider content-position="left">商品状态配置（统一配置）</el-divider>
        <el-alert
          title="请为二手商品选择各项状态，帮助买家了解商品真实情况"
          type="info"
          show-icon
          :closable="false"
          style="margin-bottom: 16px;"
        />
        <el-form label-width="100px">
          <el-row :gutter="20" v-loading="loadingConditions">
            <el-col :span="12" v-for="group in conditionGroups" :key="group.id">
              <el-form-item
                :label="group.translations['zh-tw']?.name || group.name"
                :required="group.is_required === 1"
              >
                <el-select
                  v-model="conditionValues[group.id]"
                  :placeholder="`请选择${group.translations['zh-tw']?.name || group.name}`"
                  style="width: 100%"
                  clearable
                >
                  <el-option
                    v-for="option in group.options"
                    :key="option.id"
                    :label="option.translations['zh-tw']?.name || option.name"
                    :value="option.id"
                  >
                    <div class="condition-option">
                      <span>{{ option.translations['zh-tw']?.name || option.name }}</span>
                      <el-tag
                        v-if="option.impact_level"
                        :type="getImpactLevelType(option.impact_level)"
                        size="small"
                        style="margin-left: 8px;"
                      >
                        {{ impactLevelMap[option.impact_level] }}
                      </el-tag>
                    </div>
                  </el-option>
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>
      </template>
    </div>

    <!-- 步骤2：预览结果 -->
    <div v-show="currentStep === 1">
      <div v-if="crawlLoading" style="text-align: center; padding: 40px">
        <el-icon class="is-loading" :size="32"><Loading /></el-icon>
        <div style="margin-top: 10px; color: #909399">正在采集数据，请稍候...</div>
      </div>

      <div v-else-if="crawlItems.length > 0">
        <div style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center">
          <span>共采集到 <b>{{ crawlItems.length }}</b> 个商品</span>
          <div>
            <el-button size="small" @click="toggleSelectAll">
              {{ isAllSelected ? '取消全选' : '全选' }}
            </el-button>
            <el-button
              size="small"
              type="warning"
              :disabled="selectedCrawlItems.length === 0"
              :loading="detailLoading"
              @click="handleDeepCrawl"
            >
              深度采集 ({{ selectedCrawlItems.length }})
            </el-button>
          </div>
        </div>

        <el-table
          :data="crawlItems"
          max-height="400"
          border
          size="small"
          @selection-change="handleSelectionChange"
          ref="crawlTableRef"
        >
          <el-table-column type="selection" width="40" />
          <el-table-column label="图片" width="70">
            <template #default="{ row }">
              <el-image
                v-if="row.image_url"
                :src="row.image_url"
                fit="cover"
                style="width: 50px; height: 50px; border-radius: 4px"
                :preview-src-list="row.image_urls"
              />
            </template>
          </el-table-column>
          <el-table-column label="标题" min-width="200" show-overflow-tooltip>
            <template #default="{ row }">
              <a :href="row.listing_url" target="_blank" style="color: #409eff; text-decoration: none">
                {{ row.title }}
              </a>
            </template>
          </el-table-column>
          <el-table-column label="价格" width="100">
            <template #default="{ row }">
              <div>{{ row.currency }} {{ row.price }}</div>
              <div v-if="row.original_price > 0 && row.original_price !== row.price" style="color: #999; text-decoration: line-through; font-size: 12px">
                {{ row.currency }} {{ row.original_price }}
              </div>
            </template>
          </el-table-column>
          <el-table-column label="成色" width="100">
            <template #default="{ row }">
              <el-tag :type="conditionTagType(row.condition_mapped)" size="small">
                {{ row.condition_text || conditionText(row.condition_mapped) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="图片数" width="60">
            <template #default="{ row }">{{ row.image_urls?.length || 0 }}</template>
          </el-table-column>
        </el-table>

        <div v-if="crawlErrors.length > 0" style="margin-top: 10px">
          <el-alert type="warning" :closable="false">
            采集过程中有 {{ crawlErrors.length }} 个错误：{{ crawlErrors.join('; ') }}
          </el-alert>
        </div>
      </div>

      <div v-else style="text-align: center; padding: 40px; color: #909399">
        未采集到任何商品数据
      </div>
    </div>

    <!-- 步骤3：确认导入 -->
    <div v-show="currentStep === 2">
      <el-descriptions :column="2" border style="margin-bottom: 20px">
        <el-descriptions-item label="导入数量">{{ selectedCrawlItems.length }} 个商品</el-descriptions-item>
        <el-descriptions-item label="目标分类">{{ selectedCategoryName }}</el-descriptions-item>
        <el-descriptions-item label="商品类型">{{ crawlForm.type === 1 ? 'C2C (个人)' : 'B2C (商家)' }}</el-descriptions-item>
        <el-descriptions-item label="默认状态">{{ crawlForm.status === 0 ? '待审核' : '直接上架' }}</el-descriptions-item>
        <el-descriptions-item label="发布用户">随机分配</el-descriptions-item>
      </el-descriptions>

      <el-form label-width="100px">
        <el-form-item label="自动翻译">
          <el-switch v-model="importConfig.auto_translate" active-text="是" inactive-text="否" />
          <span class="form-tip" style="margin-left: 10px">翻译标题为繁體中文和日本語</span>
        </el-form-item>
      </el-form>

      <div v-if="importLoading || importResult" style="margin-top: 20px">
        <el-progress
          :percentage="importProgress"
          :status="importResult?.status === 'completed' ? 'success' : ''"
          :format="() => importResult ? `${importResult.processed}/${importResult.total}` : '提交中...'"
          style="margin-bottom: 12px"
        />
        <div v-if="importResult && importResult.status === 'running' && importResult.current_title" style="font-size: 12px; color: #909399; margin-bottom: 8px">
          正在处理：{{ importResult.current_title }}
        </div>
        <div v-if="importResult && importResult.processed > 0" style="font-size: 13px; color: #606266; margin-bottom: 8px">
          已成功 <span style="color: #67c23a; font-weight: bold">{{ importResult.success_count }}</span> 个，
          失败 <span style="color: #f56c6c; font-weight: bold">{{ importResult.fail_count }}</span> 个
        </div>
        <el-alert
          v-if="importResult?.status === 'completed'"
          :type="importResult.fail_count === 0 ? 'success' : 'warning'"
          :closable="false"
          style="margin-top: 8px"
        >
          导入完成！成功 {{ importResult.success_count }} 个，失败 {{ importResult.fail_count }} 个
        </el-alert>
        <div v-if="importResult?.errors && importResult.errors.length > 0" style="margin-top: 10px; max-height: 150px; overflow-y: auto">
          <div v-for="(err, i) in importResult.errors" :key="i" style="color: #f56c6c; font-size: 12px">
            {{ err.title }}: {{ err.reason }}
          </div>
        </div>
      </div>
    </div>

    <!-- 底部按钮 -->
    <template #footer>
      <div style="display: flex; justify-content: space-between">
        <div>
          <el-button v-if="currentStep > 0 && !importTaskId" @click="prevStep">上一步</el-button>
        </div>
        <div>
          <el-button @click="handleClose">{{ importResult?.status === 'completed' ? '关闭' : '取消' }}</el-button>
          <el-button
            v-if="currentStep === 0"
            type="primary"
            :disabled="!canStartCrawl"
            @click="startCrawl"
          >
            开始采集
          </el-button>
          <el-button
            v-if="currentStep === 1"
            type="primary"
            :disabled="selectedCrawlItems.length === 0"
            @click="nextStep"
          >
            下一步 ({{ selectedCrawlItems.length }})
          </el-button>
          <el-button
            v-if="currentStep === 2 && !importTaskId"
            type="primary"
            :loading="importLoading"
            :disabled="selectedCrawlItems.length === 0"
            @click="handleImport"
          >
            确认导入
          </el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, reactive } from 'vue'
import { Loading } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { crawlPreview, crawlDetail, crawlImport, crawlImportStatus } from '@/api/goodsCrawl'
import type { CrawlItem, CrawlImportProgress } from '@/api/goodsCrawl'
import { getCategoryAttributes, type CategoryAttribute } from '@/api/category'
import { getCategoryConditionGroupsWithOptions, type ConditionGroup } from '@/api/categoryCondition'

const props = defineProps<{
  modelValue: boolean
  categoryList: any[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'imported': []
}>()

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

const currentStep = ref(0)
const crawlTableRef = ref()

// 步骤1 - 采集配置
const crawlForm = ref({
  url: '',
  pages: 1,
  category_id: 0 as number,
  type: 2,
  status: 0,
  price_adjust: 0,
  free_shipping: 1,
})

// 分类属性
const categoryAttributes = ref<CategoryAttribute[]>([])
const loadingAttributes = ref(false)
const specsForm = reactive<Record<string, string | string[]>>({})

// 商品状态配置
const conditionGroups = ref<ConditionGroup[]>([])
const loadingConditions = ref(false)
const conditionValues = reactive<Record<number, number>>({})

// 影响等级映射
const impactLevelMap: Record<number, string> = {
  1: '优秀', 2: '良好', 3: '一般', 4: '较差', 5: '很差'
}
const getImpactLevelType = (level: number): string => {
  const types: Record<number, string> = { 1: 'success', 2: '', 3: 'warning', 4: 'danger', 5: 'danger' }
  return types[level] || ''
}

// 获取属性的过滤后选项（用于联动）
const getFilteredOptions = (attr: CategoryAttribute) => {
  if (!attr.parent_key) return attr.options
  const parentValue = specsForm[attr.parent_key]
  if (!parentValue) return []
  return attr.options.filter(opt => opt.parent_value === parentValue)
}

// 监听 specs 变化，当父属性变化时清空子属性
watch(() => ({ ...specsForm }), (newSpecs, oldSpecs) => {
  if (!oldSpecs) return
  categoryAttributes.value.forEach(attr => {
    if (attr.parent_key) {
      const parentValue = newSpecs[attr.parent_key]
      const oldParentValue = oldSpecs[attr.parent_key]
      if (parentValue !== oldParentValue) {
        specsForm[attr.key] = attr.input_type === 'multi_select' ? [] : ''
      }
    }
  })
})

// 加载分类属性
const loadCategoryAttributes = async (categoryId: number) => {
  if (!categoryId) {
    categoryAttributes.value = []
    return
  }
  loadingAttributes.value = true
  try {
    const res: any = await getCategoryAttributes(categoryId)
    categoryAttributes.value = res.data || []
    // 初始化 specs 默认值
    categoryAttributes.value.forEach(attr => {
      if (specsForm[attr.key] === undefined) {
        specsForm[attr.key] = attr.input_type === 'multi_select' ? [] : ''
      }
    })
  } catch (e) {
    categoryAttributes.value = []
  } finally {
    loadingAttributes.value = false
  }
}

// 加载分类状态配置
const loadConditionGroups = async (categoryId: number) => {
  if (!categoryId) {
    conditionGroups.value = []
    return
  }
  loadingConditions.value = true
  try {
    const res: any = await getCategoryConditionGroupsWithOptions(categoryId)
    conditionGroups.value = res.data || []
    // 默认选中每个状态组的第一个选项
    conditionGroups.value.forEach(group => {
      if (group.options && group.options.length > 0 && !conditionValues[group.id]) {
        conditionValues[group.id] = group.options[0].id
      }
    })
  } catch (e) {
    conditionGroups.value = []
  } finally {
    loadingConditions.value = false
  }
}

// 监听分类变化
watch(() => crawlForm.value.category_id, (newVal) => {
  // 清空旧属性
  Object.keys(specsForm).forEach(key => delete specsForm[key])
  Object.keys(conditionValues).forEach(key => delete conditionValues[Number(key)])

  if (newVal) {
    loadCategoryAttributes(newVal)
    loadConditionGroups(newVal)
  } else {
    categoryAttributes.value = []
    conditionGroups.value = []
  }
})

const canStartCrawl = computed(() => {
  return crawlForm.value.url.includes('ebay.com') && crawlForm.value.category_id > 0
})

const selectedCategoryName = computed(() => {
  const cat = props.categoryList.find((c: any) => c.id === crawlForm.value.category_id)
  return cat?.translations['zh-tw']?.name || cat?.translations['en-us']?.name || '-'
})

// 步骤2 - 预览
const crawlLoading = ref(false)
const crawlItems = ref<CrawlItem[]>([])
const crawlErrors = ref<string[]>([])
const selectedCrawlItems = ref<CrawlItem[]>([])
const detailLoading = ref(false)

const isAllSelected = computed(() => {
  return crawlItems.value.length > 0 && selectedCrawlItems.value.length === crawlItems.value.length
})

// 步骤3 - 导入
const importConfig = ref({
  auto_translate: true,
})
const importLoading = ref(false)
const importProgress = ref(0)
const importTaskId = ref('')
const importResult = ref<CrawlImportProgress | null>(null)
let pollTimer: ReturnType<typeof setInterval> | null = null

// 条件标签
function conditionTagType(condition: number) {
  const map: Record<number, string> = { 1: 'success', 2: '', 3: 'warning', 4: 'warning', 5: 'danger' }
  return map[condition] || ''
}

function conditionText(condition: number) {
  const map: Record<number, string> = { 1: '全新', 2: '几乎全新', 3: '轻微使用', 4: '明显使用', 5: '有缺陷' }
  return map[condition] || '未知'
}

// 开始采集
async function startCrawl() {
  currentStep.value = 1
  crawlLoading.value = true
  crawlItems.value = []
  crawlErrors.value = []

  try {
    const res = await crawlPreview({
      url: crawlForm.value.url,
      pages: crawlForm.value.pages,
    }) as any
    crawlItems.value = res.data?.items || []
    crawlErrors.value = res.data?.errors || []

    if (crawlItems.value.length === 0) {
      ElMessage.warning('未采集到任何商品，请检查 URL 是否正确')
    } else {
      ElMessage.success(`成功采集 ${crawlItems.value.length} 个商品`)
    }
  } catch (e: any) {
    ElMessage.error('采集请求失败: ' + (e.message || '未知错误'))
    currentStep.value = 0
  } finally {
    crawlLoading.value = false
  }
}

// 选择变化
function handleSelectionChange(selection: CrawlItem[]) {
  selectedCrawlItems.value = selection
}

// 全选/取消全选
function toggleSelectAll() {
  if (isAllSelected.value) {
    crawlTableRef.value?.clearSelection()
  } else {
    crawlTableRef.value?.toggleAllSelection()
  }
}

// 深度采集
async function handleDeepCrawl() {
  if (selectedCrawlItems.value.length === 0) return
  if (selectedCrawlItems.value.length > 20) {
    ElMessage.warning('单次深度采集最多 20 个商品')
    return
  }

  detailLoading.value = true
  try {
    const itemsToFetch = selectedCrawlItems.value.map((item) => ({
      ebay_item_id: item.ebay_item_id,
      listing_url: item.listing_url,
    }))

    const res = await crawlDetail({ items: itemsToFetch }) as any
    const detailItems = res.data?.items || []
    for (const detail of detailItems) {
      const idx = crawlItems.value.findIndex((i) => i.ebay_item_id === detail.ebay_item_id)
      if (idx !== -1) {
        crawlItems.value[idx] = { ...crawlItems.value[idx], ...detail }
      }
    }
    ElMessage.success('深度采集完成')
  } catch (e: any) {
    ElMessage.error('深度采集失败: ' + (e.message || ''))
  } finally {
    detailLoading.value = false
  }
}

// 下一步
function nextStep() {
  currentStep.value++
}

// 上一步
function prevStep() {
  currentStep.value--
}

// 导入
async function handleImport() {
  await ElMessageBox.confirm(
    `确定要导入 ${selectedCrawlItems.value.length} 个商品吗？`,
    '确认导入',
    { type: 'warning' }
  )

  importLoading.value = true
  importProgress.value = 0

  try {
    // 构建 conditionValues 数组格式
    const conditionValuesArray = Object.entries(conditionValues)
      .filter(([, optionId]) => optionId)
      .map(([groupId, optionId]) => ({ group_id: Number(groupId), option_id: optionId }))

    // 过滤掉空值的 specs
    const specs: Record<string, any> = {}
    for (const [key, value] of Object.entries(specsForm)) {
      if (value !== '' && value !== undefined && value !== null) {
        if (Array.isArray(value) && value.length === 0) continue
        specs[key] = value
      }
    }

    // 提交任务（立即返回）
    const res = await crawlImport({
      items: selectedCrawlItems.value,
      config: {
        category_id: crawlForm.value.category_id,
        type: crawlForm.value.type,
        status: crawlForm.value.status,
        auto_translate: importConfig.value.auto_translate,
        price_adjust: crawlForm.value.price_adjust || 0,
        free_shipping: crawlForm.value.free_shipping,
        specs,
        condition_values: conditionValuesArray,
      } as any,
    })

    const resAny = res as any
    importTaskId.value = resAny.data?.task_id || ''
    ElMessage.success('导入任务已提交，正在后台处理...')

    // 开始轮询进度
    startPolling()
  } catch (e: any) {
    ElMessage.error('导入请求失败: ' + (e.message || ''))
    importLoading.value = false
  }
}

function startPolling() {
  if (pollTimer) clearInterval(pollTimer)
  pollTimer = setInterval(async () => {
    if (!importTaskId.value) return
    try {
      const res = await crawlImportStatus(importTaskId.value) as any
      const progress = res.data as CrawlImportProgress
      if (!progress) return

      importResult.value = progress
      importProgress.value = progress.total > 0
        ? Math.round((progress.processed / progress.total) * 100)
        : 0

      if (progress.status === 'completed' || progress.status === 'failed') {
        stopPolling()
        importLoading.value = false
        if (progress.success_count > 0) {
          ElMessage.success(`导入完成！成功 ${progress.success_count} 个`)
          emit('imported')
        }
      }
    } catch (e) {
      // 轮询失败不中断
    }
  }, 2000)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

// 关闭
function handleClose() {
  stopPolling()
  visible.value = false
  setTimeout(() => {
    currentStep.value = 0
    crawlItems.value = []
    crawlErrors.value = []
    selectedCrawlItems.value = []
    importResult.value = null
    importProgress.value = 0
    importTaskId.value = ''
    importLoading.value = false
    crawlForm.value = { url: '', pages: 1, category_id: 0, type: 2, status: 0, price_adjust: 0, free_shipping: 1 }
    importConfig.value = { auto_translate: true }
    Object.keys(specsForm).forEach(key => delete specsForm[key])
    Object.keys(conditionValues).forEach(key => delete conditionValues[Number(key)])
    categoryAttributes.value = []
    conditionGroups.value = []
  }, 300)
}
</script>

<style scoped>
.form-tip {
  color: #909399;
  font-size: 12px;
}
.condition-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
