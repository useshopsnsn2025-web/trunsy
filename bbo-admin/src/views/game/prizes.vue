<template>
  <div class="prize-management">
    <el-page-header @back="goBack" class="mb-4">
      <template #content>
        <span class="text-large font-600">{{ gameName }} - 奖品配置</span>
      </template>
    </el-page-header>

    <el-card>
      <template #header>
        <div class="card-header">
          <div class="left">
            <span>奖品池</span>
            <el-tag
              :type="probabilityValid ? 'success' : 'danger'"
              class="ml-3"
            >
              总计: {{ totalProbability.toFixed(2) }}%
              {{ probabilityValid ? '✓' : '(必须等于100%)' }}
            </el-tag>
          </div>
          <div class="right">
            <el-button type="primary" @click="handleSimulate">
              <el-icon><Aim /></el-icon> 模拟抽奖
            </el-button>
            <el-button type="success" @click="handleAddPrize">
              <el-icon><Plus /></el-icon> 添加奖品
            </el-button>
          </div>
        </div>
      </template>

      <el-table :data="prizeList" v-loading="loading" stripe>
        <el-table-column label="排序" width="70" align="center">
          <template #default="{ row }">
            <span class="sort-handle">{{ row.sort }}</span>
          </template>
        </el-table-column>

        <el-table-column label="奖品" min-width="200">
          <template #default="{ row }">
            <div class="prize-info">
              <div
                class="prize-color"
                :style="{ backgroundColor: row.color }"
              ></div>
              <div class="prize-details">
                <div class="prize-name">{{ getPrizeName(row) }}</div>
                <div class="prize-type">{{ prizeTypeLabels[row.type] || row.type }}</div>
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="价值" width="100" align="right">
          <template #default="{ row }">
            <span v-if="row.type === 'points'">{{ row.value }} 积分</span>
            <span v-else-if="row.type === 'coupon'">{{ row.value }}%</span>
            <span v-else-if="row.type === 'cash'">${{ row.value }}</span>
            <span v-else>{{ row.value }}</span>
          </template>
        </el-table-column>

        <el-table-column label="概率" width="120" align="center">
          <template #default="{ row }">
            <el-tag :type="getProbabilityTagType(row.probability)">
              {{ row.probability.toFixed(2) }}%
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="库存" width="100" align="center">
          <template #default="{ row }">
            <span v-if="row.stock === -1" class="text-muted">无限</span>
            <span v-else :class="{ 'text-danger': row.stock <= 10 }">{{ row.stock }}</span>
          </template>
        </el-table-column>

        <el-table-column label="每日限量" width="100" align="center">
          <template #default="{ row }">
            <span v-if="row.daily_limit === -1" class="text-muted">-</span>
            <span v-else>{{ row.daily_limit }}</span>
          </template>
        </el-table-column>

        <el-table-column label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>

        <el-table-column label="操作" width="140" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEditPrize(row)">
              <el-icon><Edit /></el-icon>
            </el-button>
            <el-button type="danger" link @click="handleDeletePrize(row)">
              <el-icon><Delete /></el-icon>
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 奖品编辑弹窗 -->
    <el-dialog
      v-model="prizeDialogVisible"
      :title="isEdit ? '编辑奖品' : '添加奖品'"
      width="650px"
    >
      <el-form
        ref="prizeFormRef"
        :model="prizeForm"
        :rules="prizeRules"
        label-width="130px"
      >
        <el-form-item label="奖品类型" prop="type">
          <el-radio-group v-model="prizeForm.type">
            <el-radio-button v-for="opt in prizeTypeOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </el-radio-button>
          </el-radio-group>
        </el-form-item>

        <!-- 商品选择 -->
        <el-form-item v-if="prizeForm.type === 'goods'" label="选择商品" prop="goods_id">
          <el-select
            v-model="prizeForm.goods_id"
            filterable
            placeholder="选择商品"
            :loading="goodsLoading"
            style="width: 400px"
          >
            <el-option
              v-for="item in goodsList"
              :key="item.id"
              :label="getGoodsLabel(item)"
              :value="item.id"
            >
              <div class="goods-option">
                <el-image
                  v-if="item.images && item.images.length"
                  :src="item.images[0]"
                  fit="cover"
                  style="width: 40px; height: 40px; margin-right: 10px; border-radius: 4px; flex-shrink: 0;"
                />
                <div style="overflow: hidden;">
                  <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ getGoodsLabel(item) }}</div>
                  <div class="text-muted" style="font-size: 12px;">${{ item.price }} · 库存: {{ item.stock }}</div>
                </div>
              </div>
            </el-option>
          </el-select>
          <el-button type="primary" link @click="fetchGoods" :loading="goodsLoading" class="ml-2">
            刷新列表
          </el-button>
        </el-form-item>

        <!-- 优惠券选择 -->
        <el-form-item v-if="prizeForm.type === 'coupon'" label="选择优惠券" prop="coupon_id">
          <el-select
            v-model="prizeForm.coupon_id"
            filterable
            placeholder="选择优惠券"
            style="width: 350px"
          >
            <el-option
              v-for="item in couponList"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            >
              <div class="coupon-option">
                <span>{{ item.name }}</span>
                <span class="text-muted" style="margin-left: 10px;">
                  {{ item.type === 1 ? `满${item.min_amount}减${item.value}` : `${item.value}折` }}
                </span>
              </div>
            </el-option>
          </el-select>
        </el-form-item>

        <el-form-item v-if="prizeForm.type !== 'goods'" label="奖品值" prop="value">
          <el-input-number
            v-model="prizeForm.value"
            :min="0"
            :precision="prizeForm.type === 'cash' ? 2 : 0"
            style="width: 200px"
          />
          <span class="ml-2">
            <template v-if="prizeForm.type === 'points'">积分</template>
            <template v-else-if="prizeForm.type === 'coupon'">% 折扣（如不选择优惠券）</template>
            <template v-else-if="prizeForm.type === 'cash'">美元</template>
            <template v-else-if="prizeForm.type === 'chance'">次</template>
          </span>
        </el-form-item>

        <el-form-item label="中奖概率" prop="probability">
          <el-input-number
            v-model="prizeForm.probability"
            :min="0.01"
            :max="100"
            :precision="2"
            :step="1"
            style="width: 200px"
          />
          <span class="ml-2">%</span>
          <div class="probability-hint">
            当前总计: {{ currentTotalProbability.toFixed(2) }}%
            <span v-if="!isEdit && currentTotalProbability > 100" class="text-danger">
              (超过100%!)
            </span>
          </div>
        </el-form-item>

        <el-form-item label="显示颜色">
          <el-select v-model="prizeForm.color" placeholder="选择颜色" style="width: 200px">
            <el-option v-for="c in presetColors" :key="c.value" :value="c.value" :label="c.label">
              <div class="color-option">
                <span class="color-dot" :style="{ backgroundColor: c.value }"></span>
                {{ c.label }}
              </div>
            </el-option>
          </el-select>
          <el-color-picker v-model="prizeForm.color" class="ml-2" />
        </el-form-item>

        <el-divider>库存设置</el-divider>

        <el-form-item label="总库存">
          <el-input-number v-model="prizeForm.stock" :min="-1" style="width: 200px" />
          <span class="ml-2 text-muted">-1 = 无限</span>
        </el-form-item>

        <el-form-item label="每日限量">
          <el-input-number v-model="prizeForm.daily_limit" :min="-1" style="width: 200px" />
          <span class="ml-2 text-muted">-1 = 无限</span>
        </el-form-item>

        <el-form-item label="单用户每日限制">
          <el-input-number v-model="prizeForm.user_daily_limit" :min="-1" style="width: 200px" />
          <span class="ml-2 text-muted">-1 = 无限</span>
        </el-form-item>

        <el-divider>多语言配置</el-divider>

        <el-tabs v-model="currentLocale">
          <el-tab-pane v-for="locale in locales" :key="locale" :label="locale" :name="locale">
            <el-form-item label="名称">
              <el-input v-model="prizeForm.translations[locale].name" placeholder="奖品名称" />
            </el-form-item>
            <el-form-item label="描述">
              <el-input v-model="prizeForm.translations[locale].description" placeholder="可选描述" />
            </el-form-item>
          </el-tab-pane>
        </el-tabs>

        <el-form-item label="排序">
          <el-input-number v-model="prizeForm.sort" :min="0" style="width: 200px" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="prizeDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="savePrize" :loading="saving">保存</el-button>
      </template>
    </el-dialog>

    <!-- 模拟抽奖弹窗 -->
    <el-dialog v-model="simulateDialogVisible" title="模拟抽奖" width="700px">
      <div class="simulate-controls mb-4">
        <span>模拟 </span>
        <el-input-number v-model="simulateTimes" :min="100" :max="10000" :step="100" />
        <span> 次</span>
        <el-button type="primary" @click="runSimulate" :loading="simulating" class="ml-3">
          开始模拟
        </el-button>
      </div>

      <el-table v-if="simulateResults.length" :data="simulateResults" stripe>
        <el-table-column prop="name" label="奖品" min-width="150" />
        <el-table-column prop="count" label="次数" width="100" align="right" />
        <el-table-column label="预期概率" width="100" align="right">
          <template #default="{ row }">{{ row.expected_probability }}%</template>
        </el-table-column>
        <el-table-column label="实际概率" width="100" align="right">
          <template #default="{ row }">{{ row.actual_probability }}%</template>
        </el-table-column>
        <el-table-column label="偏差" width="100" align="right">
          <template #default="{ row }">
            <span :class="getDeviationClass(row.deviation)">
              {{ row.deviation > 0 ? '+' : '' }}{{ row.deviation }}%
            </span>
          </template>
        </el-table-column>
      </el-table>

      <div v-if="simulateCost" class="simulate-summary mt-4">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="总成本">${{ simulateCost.total?.toFixed(2) }}</el-descriptions-item>
          <el-descriptions-item label="平均成本/次">${{ simulateCost.avg?.toFixed(4) }}</el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus, Edit, Delete, Aim } from '@element-plus/icons-vue'
import {
  getGameDetail,
  getGamePrizes,
  addPrize,
  updatePrize,
  deletePrize,
  simulateLottery,
  prizeTypeOptions,
  presetColors,
  type Game,
  type Prize,
  type PrizeTranslation
} from '@/api/game'
import { getGoodsList, type Goods } from '@/api/goods'
import { getCouponList, type Coupon } from '@/api/coupon'

const route = useRoute()
const router = useRouter()
const gameId = Number(route.params.id)

const loading = ref(false)
const saving = ref(false)
const simulating = ref(false)
const game = ref<Game | null>(null)
const prizeList = ref<Prize[]>([])
const prizeDialogVisible = ref(false)
const simulateDialogVisible = ref(false)
const isEdit = ref(false)
const currentPrizeId = ref<number | null>(null)
const currentLocale = ref('en-us')

// 商品和优惠券相关
const goodsLoading = ref(false)
const goodsList = ref<Goods[]>([])
const couponList = ref<Coupon[]>([])

const locales = ['en-us', 'zh-tw', 'ja-jp']

const prizeTypeLabels: Record<string, string> = {
  points: '积分',
  coupon: '优惠券',
  cash: '现金券',
  goods: '实物商品',
  chance: '游戏次数',
}

const prizeFormRef = ref<FormInstance>()
const prizeForm = reactive({
  type: 'points' as Prize['type'],
  value: 10,
  coupon_id: null as number | null,
  goods_id: null as number | null,
  probability: 10,
  stock: -1,
  daily_limit: -1,
  user_daily_limit: -1,
  image: '',
  color: '#FFD700',
  sort: 0,
  status: 1,
  translations: {} as Record<string, PrizeTranslation>,
})

const prizeRules: FormRules = {
  type: [{ required: true, message: '请选择奖品类型' }],
  value: [{ required: true, message: '请输入奖品值' }],
  probability: [{ required: true, message: '请输入概率' }],
}

const simulateTimes = ref(1000)
const simulateResults = ref<any[]>([])
const simulateCost = ref<{ total: number; avg: number } | null>(null)

const gameName = computed(() => {
  // 优先显示中文翻译
  if (game.value?.translations?.['zh-tw']?.name) {
    return game.value.translations['zh-tw'].name
  }
  if (game.value?.translations?.['en-us']?.name) {
    return game.value.translations['en-us'].name
  }
  return game.value?.code || '游戏'
})

const totalProbability = computed(() => {
  return prizeList.value
    .filter(p => p.status === 1)
    .reduce((sum, p) => sum + p.probability, 0)
})

const probabilityValid = computed(() => {
  return Math.abs(totalProbability.value - 100) < 0.01
})

const currentTotalProbability = computed(() => {
  let total = prizeList.value
    .filter(p => p.status === 1 && (!isEdit.value || p.id !== currentPrizeId.value))
    .reduce((sum, p) => sum + p.probability, 0)
  return total + prizeForm.probability
})

const fetchData = async () => {
  loading.value = true
  try {
    const [gameRes, prizesRes] = await Promise.all([
      getGameDetail(gameId),
      getGamePrizes(gameId),
    ])
    game.value = gameRes.data
    prizeList.value = prizesRes.data.prizes || []

    // 加载优惠券和商品列表
    await Promise.all([fetchCoupons(), fetchGoods()])
  } catch (error: any) {
    ElMessage.error(error.message || 'Failed to load data')
  } finally {
    loading.value = false
  }
}

// 加载优惠券列表
const fetchCoupons = async () => {
  try {
    const res = await getCouponList({ pageSize: 100, status: 1 })
    couponList.value = res.data?.list || res.data || []
  } catch (error) {
    console.error('Failed to load coupons', error)
  }
}

// 加载商品列表
const fetchGoods = async () => {
  goodsLoading.value = true
  try {
    // status: 1 = 已上架 (ON_SALE)，根据 Goods.php 的 STATUS_MAP
    const res = await getGoodsList({ pageSize: 100, status: 1 })
    goodsList.value = res.data?.list || res.data || []
    console.log('Loaded goods:', goodsList.value.length)
  } catch (error) {
    console.error('Failed to load goods', error)
  } finally {
    goodsLoading.value = false
  }
}

// 获取商品显示名称
const getGoodsLabel = (goods: Goods) => {
  // 优先显示中文翻译
  if (goods.translations && goods.translations['zh-tw']?.title) {
    return goods.translations['zh-tw'].title
  }
  if (goods.translations && goods.translations['en-us']?.title) {
    return goods.translations['en-us'].title
  }
  return goods.goods_no
}

const getPrizeName = (prize: Prize) => {
  // 优先显示中文翻译
  if (prize.translations && prize.translations['zh-tw']) {
    return prize.translations['zh-tw'].name
  }
  if (prize.translations && prize.translations['en-us']) {
    return prize.translations['en-us'].name
  }
  return `${prize.type} - ${prize.value}`
}

const getProbabilityTagType = (probability: number) => {
  if (probability >= 30) return 'success'
  if (probability >= 10) return 'warning'
  if (probability >= 5) return ''
  return 'danger'
}

const getDeviationClass = (deviation: number) => {
  if (Math.abs(deviation) < 2) return 'text-success'
  if (Math.abs(deviation) < 5) return 'text-warning'
  return 'text-danger'
}

const goBack = () => {
  router.push('/game')
}

const handleStatusChange = async (prize: Prize) => {
  try {
    await updatePrize(gameId, prize.id, { status: prize.status })
    ElMessage.success('Status updated')
  } catch (error: any) {
    prize.status = prize.status === 1 ? 0 : 1
    ElMessage.error(error.message || 'Failed to update')
  }
}

const initPrizeForm = () => {
  prizeForm.type = 'points'
  prizeForm.value = 10
  prizeForm.coupon_id = null
  prizeForm.goods_id = null
  prizeForm.probability = 10
  prizeForm.stock = -1
  prizeForm.daily_limit = -1
  prizeForm.user_daily_limit = -1
  prizeForm.image = ''
  prizeForm.color = '#FFD700'
  prizeForm.sort = prizeList.value.length
  prizeForm.status = 1

  prizeForm.translations = {}
  for (const locale of locales) {
    prizeForm.translations[locale] = { name: '', description: '' }
  }
}

const handleAddPrize = () => {
  isEdit.value = false
  currentPrizeId.value = null
  initPrizeForm()
  currentLocale.value = 'en-us'
  prizeDialogVisible.value = true
}

const handleEditPrize = (prize: Prize) => {
  isEdit.value = true
  currentPrizeId.value = prize.id

  prizeForm.type = prize.type
  prizeForm.value = prize.value
  prizeForm.coupon_id = prize.coupon_id
  prizeForm.goods_id = prize.goods_id
  prizeForm.probability = prize.probability
  prizeForm.stock = prize.stock
  prizeForm.daily_limit = prize.daily_limit
  prizeForm.user_daily_limit = prize.user_daily_limit
  prizeForm.image = prize.image || ''
  prizeForm.color = prize.color || '#FFD700'
  prizeForm.sort = prize.sort
  prizeForm.status = prize.status

  prizeForm.translations = {}
  for (const locale of locales) {
    prizeForm.translations[locale] = {
      name: prize.translations?.[locale]?.name || '',
      description: prize.translations?.[locale]?.description || '',
    }
  }

  currentLocale.value = 'en-us'
  prizeDialogVisible.value = true
}

const savePrize = async () => {
  if (!prizeFormRef.value) return

  try {
    await prizeFormRef.value.validate()
  } catch {
    return
  }

  saving.value = true
  try {
    const data = { ...prizeForm }

    if (isEdit.value && currentPrizeId.value) {
      await updatePrize(gameId, currentPrizeId.value, data)
      ElMessage.success('Prize updated')
    } else {
      await addPrize(gameId, data)
      ElMessage.success('Prize added')
    }

    prizeDialogVisible.value = false
    fetchData()
  } catch (error: any) {
    ElMessage.error(error.message || 'Failed to save')
  } finally {
    saving.value = false
  }
}

const handleDeletePrize = async (prize: Prize) => {
  try {
    await ElMessageBox.confirm(
      `Delete prize "${getPrizeName(prize)}"?`,
      'Confirm Delete',
      { type: 'warning' }
    )

    await deletePrize(gameId, prize.id)
    ElMessage.success('Prize deleted')
    fetchData()
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || 'Failed to delete')
    }
  }
}

const handleSimulate = () => {
  simulateResults.value = []
  simulateCost.value = null
  simulateDialogVisible.value = true
}

const runSimulate = async () => {
  simulating.value = true
  try {
    const res = await simulateLottery(gameId, simulateTimes.value)
    simulateResults.value = res.data.results || []
    simulateCost.value = {
      total: res.data.total_cost,
      avg: res.data.avg_cost,
    }
  } catch (error: any) {
    ElMessage.error(error.message || 'Failed to simulate')
  } finally {
    simulating.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-header .left {
  display: flex;
  align-items: center;
  font-weight: 600;
  font-size: 16px;
}

.card-header .right {
  display: flex;
  gap: 10px;
}

.prize-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.prize-color {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.prize-details .prize-name {
  font-weight: 500;
}

.prize-details .prize-type {
  font-size: 12px;
  color: #909399;
}

.sort-handle {
  cursor: grab;
  padding: 4px 8px;
  background: #f5f7fa;
  border-radius: 4px;
}

.text-muted {
  color: #909399;
}

.text-danger {
  color: #f56c6c;
}

.text-success {
  color: #67c23a;
}

.text-warning {
  color: #e6a23c;
}

.probability-hint {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.color-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-dot {
  width: 16px;
  height: 16px;
  border-radius: 4px;
}

.simulate-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.simulate-summary {
  background: #f5f7fa;
  padding: 16px;
  border-radius: 8px;
}

.goods-option {
  display: flex;
  align-items: center;
  padding: 4px 0;
}

.coupon-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}
</style>
