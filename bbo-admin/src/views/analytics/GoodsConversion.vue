<template>
  <div class="goods-conversion">
    <!-- Date Filter -->
    <el-card class="filter-card" shadow="never">
      <div class="date-filter">
        <span class="filter-label">日期范围</span>
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          value-format="YYYY-MM-DD"
          :shortcuts="dateShortcuts"
          @change="fetchData"
        />
      </div>
    </el-card>

    <!-- Summary Stats -->
    <div v-if="goodsList.length > 0" class="summary-row">
      <div class="summary-card">
        <div class="summary-icon icon-blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ goodsList.length }}</div>
          <div class="summary-label">商品数</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-indigo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ totalViews.toLocaleString() }}</div>
          <div class="summary-label">总浏览量</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-amber">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ avgCartRate }}%</div>
          <div class="summary-label">平均加购率</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ avgBuyRate }}%</div>
          <div class="summary-label">平均购买率</div>
        </div>
      </div>
    </div>

    <!-- Goods Conversion Table -->
    <el-card class="main-card" shadow="never">
      <template #header>
        <div class="card-title">
          <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          <span>商品转化排行</span>
          <span class="card-count">共 {{ goodsList.length }} 件</span>
        </div>
      </template>
      <el-table :data="goodsList" v-loading="loading" size="small" class="goods-table">
        <el-table-column width="50" label="#" align="center">
          <template #default="{ $index }">
            <span class="row-rank" :class="{ 'top-3': $index < 3 }">{{ $index + 1 }}</span>
          </template>
        </el-table-column>
        <el-table-column label="商品" min-width="280">
          <template #default="{ row }">
            <div class="goods-cell">
              <el-image
                v-if="row.goods_image"
                :src="row.goods_image"
                fit="cover"
                class="goods-thumb"
              >
                <template #error>
                  <div class="thumb-fallback">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  </div>
                </template>
              </el-image>
              <div v-else class="thumb-fallback">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
              <div class="goods-info">
                <div class="goods-title">{{ row.goods_title }}</div>
                <div class="goods-price">
                  <span class="price-currency">{{ row.goods_currency }}</span>
                  {{ Number(row.goods_price).toFixed(2) }}
                </div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="浏览" width="110" align="right" sortable sort-by="view_count">
          <template #default="{ row }">
            <div class="view-cell">
              <span class="view-pv">{{ row.view_count?.toLocaleString() }}</span>
              <span class="view-uv">{{ row.view_uv?.toLocaleString() }} UV</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="加购" width="140" align="right" sortable sort-by="cart_rate">
          <template #default="{ row }">
            <div class="rate-cell">
              <span class="rate-uv">{{ row.cart_uv }}</span>
              <div class="rate-bar-group">
                <div class="rate-bar-track">
                  <div class="rate-bar-fill cart" :style="{ width: Math.min(row.cart_rate * 2, 100) + '%' }"></div>
                </div>
                <span class="rate-text" :class="rateClass(row.cart_rate)">{{ row.cart_rate }}%</span>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="购买" width="140" align="right" sortable sort-by="buy_rate">
          <template #default="{ row }">
            <div class="rate-cell">
              <span class="rate-uv">{{ row.buy_uv }}</span>
              <div class="rate-bar-group">
                <div class="rate-bar-track">
                  <div class="rate-bar-fill buy" :style="{ width: Math.min(row.buy_rate * 2, 100) + '%' }"></div>
                </div>
                <span class="rate-text" :class="rateClass(row.buy_rate)">{{ row.buy_rate }}%</span>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="收藏" width="140" align="right" sortable sort-by="fav_rate">
          <template #default="{ row }">
            <div class="rate-cell">
              <span class="rate-uv">{{ row.fav_uv }}</span>
              <div class="rate-bar-group">
                <div class="rate-bar-track">
                  <div class="rate-bar-fill fav" :style="{ width: Math.min(row.fav_rate * 2, 100) + '%' }"></div>
                </div>
                <span class="rate-text fav-text">{{ row.fav_rate }}%</span>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="转化概览" width="160" align="center">
          <template #default="{ row }">
            <div class="overview-bars">
              <div class="ov-row">
                <span class="ov-label">加购</span>
                <div class="ov-track">
                  <div class="ov-fill cart" :style="{ width: Math.min(row.cart_rate * 2, 100) + '%' }"></div>
                </div>
              </div>
              <div class="ov-row">
                <span class="ov-label">购买</span>
                <div class="ov-track">
                  <div class="ov-fill buy" :style="{ width: Math.min(row.buy_rate * 2, 100) + '%' }"></div>
                </div>
              </div>
              <div class="ov-row">
                <span class="ov-label">收藏</span>
                <div class="ov-track">
                  <div class="ov-fill fav" :style="{ width: Math.min(row.fav_rate * 2, 100) + '%' }"></div>
                </div>
              </div>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { getAnalyticsGoodsConversion } from '@/api/analytics'
import dayjs from 'dayjs'

const loading = ref(false)
const goodsList = ref<any[]>([])

const dateRange = ref<[string, string]>([
  dayjs().subtract(7, 'day').format('YYYY-MM-DD'),
  dayjs().format('YYYY-MM-DD'),
])

const dateShortcuts = [
  { text: '最近7天', value: () => [dayjs().subtract(7, 'day').toDate(), dayjs().toDate()] },
  { text: '最近30天', value: () => [dayjs().subtract(30, 'day').toDate(), dayjs().toDate()] },
  { text: '最近90天', value: () => [dayjs().subtract(90, 'day').toDate(), dayjs().toDate()] },
]

const totalViews = computed(() => goodsList.value.reduce((s, r) => s + (r.view_count || 0), 0))
const avgCartRate = computed(() => {
  if (goodsList.value.length === 0) return 0
  const avg = goodsList.value.reduce((s, r) => s + (r.cart_rate || 0), 0) / goodsList.value.length
  return Math.round(avg * 100) / 100
})
const avgBuyRate = computed(() => {
  if (goodsList.value.length === 0) return 0
  const avg = goodsList.value.reduce((s, r) => s + (r.buy_rate || 0), 0) / goodsList.value.length
  return Math.round(avg * 100) / 100
})

const rateClass = (rate: number): string => {
  if (rate >= 10) return 'rate-good'
  if (rate >= 5) return 'rate-ok'
  return 'rate-low'
}

const fetchData = async () => {
  if (!dateRange.value || dateRange.value.length !== 2) return
  loading.value = true
  try {
    const res = await getAnalyticsGoodsConversion({
      start_date: dateRange.value[0],
      end_date: dateRange.value[1],
      limit: 30,
    })
    goodsList.value = res.data.list || []
  } catch (e: any) {
    console.error('Failed to load goods conversion:', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
/* ===== Base ===== */
.goods-conversion { padding: 0; }

/* ===== Filter ===== */
.filter-card { margin-bottom: 20px; border: 1px solid #E2E8F0; border-radius: 12px; }
.filter-card :deep(.el-card__body) { padding: 16px 20px; }
.date-filter { display: flex; align-items: center; gap: 12px; }
.filter-label { font-size: 13px; font-weight: 500; color: #64748B; white-space: nowrap; }

/* ===== Summary Row ===== */
.summary-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}
.summary-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 20px;
  background: #fff;
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  transition: box-shadow 0.2s, transform 0.2s;
}
.summary-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  transform: translateY(-2px);
}
.summary-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.summary-icon svg { width: 22px; height: 22px; color: #fff; }
.icon-blue   { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
.icon-indigo { background: linear-gradient(135deg, #6366F1, #818CF8); }
.icon-amber  { background: linear-gradient(135deg, #F59E0B, #FBBF24); }
.icon-green  { background: linear-gradient(135deg, #10B981, #34D399); }
.summary-value { font-size: 22px; font-weight: 700; color: #1E293B; line-height: 1.2; }
.summary-label { font-size: 12px; color: #94A3B8; margin-top: 2px; }

/* ===== Main Card ===== */
.main-card { margin-bottom: 20px; border: 1px solid #E2E8F0; border-radius: 12px; }
.main-card :deep(.el-card__header) { padding: 16px 24px; border-bottom: 1px solid #F1F5F9; }
.card-title {
  display: flex; align-items: center; gap: 8px;
  font-size: 15px; font-weight: 600; color: #1E293B;
}
.title-icon { width: 18px; height: 18px; color: #64748B; flex-shrink: 0; }
.card-count {
  font-size: 12px; font-weight: 500; color: #94A3B8;
  background: #F1F5F9; padding: 2px 10px; border-radius: 10px;
  margin-left: 4px;
}

/* ===== Table ===== */
.goods-table {
  --el-table-border-color: #F1F5F9;
  --el-table-row-hover-bg-color: #F8FAFC;
}
.goods-table :deep(.el-table__header th) {
  font-weight: 600;
  color: #64748B;
  font-size: 12px;
  background: #FAFBFC;
}

/* Rank */
.row-rank {
  font-size: 12px; font-weight: 600; color: #94A3B8;
  display: inline-flex; align-items: center; justify-content: center;
  width: 24px; height: 24px; border-radius: 6px;
}
.row-rank.top-3 {
  background: linear-gradient(135deg, #3B82F6, #60A5FA);
  color: #fff;
}

/* Goods Cell */
.goods-cell { display: flex; align-items: center; gap: 12px; }
.goods-thumb {
  width: 48px; height: 48px; border-radius: 8px; flex-shrink: 0;
  border: 1px solid #F1F5F9;
}
.goods-thumb :deep(.el-image__inner) { border-radius: 8px; }
.thumb-fallback {
  width: 48px; height: 48px; border-radius: 8px;
  background: #F8FAFC; border: 1px solid #E2E8F0;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.thumb-fallback svg { width: 24px; height: 24px; color: #CBD5E1; }
.goods-info { overflow: hidden; min-width: 0; }
.goods-title {
  font-size: 13px; font-weight: 600; color: #1E293B;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  max-width: 220px; line-height: 1.4;
}
.goods-price {
  font-size: 12px; color: #64748B; margin-top: 2px;
}
.price-currency {
  font-size: 11px; color: #94A3B8;
}

/* View Cell */
.view-cell { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
.view-pv { font-size: 14px; font-weight: 700; color: #334155; }
.view-uv { font-size: 11px; color: #94A3B8; font-weight: 500; }

/* Rate Cell */
.rate-cell { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.rate-uv { font-size: 13px; font-weight: 600; color: #334155; }
.rate-bar-group { display: flex; align-items: center; gap: 6px; }
.rate-bar-track {
  width: 48px; height: 5px; background: #F1F5F9;
  border-radius: 3px; overflow: hidden; flex-shrink: 0;
}
.rate-bar-fill {
  height: 100%; border-radius: 3px; transition: width 0.3s;
}
.rate-bar-fill.cart { background: linear-gradient(90deg, #F59E0B, #FBBF24); }
.rate-bar-fill.buy  { background: linear-gradient(90deg, #10B981, #34D399); }
.rate-bar-fill.fav  { background: linear-gradient(90deg, #8B5CF6, #A78BFA); }
.rate-text {
  font-size: 12px; font-weight: 600; min-width: 36px; text-align: right;
}
.rate-good { color: #10B981; }
.rate-ok   { color: #F59E0B; }
.rate-low  { color: #EF4444; }
.fav-text  { color: #8B5CF6; }

/* Overview Bars */
.overview-bars {
  display: flex; flex-direction: column; gap: 5px;
  padding: 4px 0;
}
.ov-row { display: flex; align-items: center; gap: 6px; }
.ov-label { font-size: 10px; color: #94A3B8; width: 24px; text-align: right; flex-shrink: 0; }
.ov-track {
  flex: 1; height: 6px; background: #F1F5F9;
  border-radius: 3px; overflow: hidden;
}
.ov-fill {
  height: 100%; border-radius: 3px; transition: width 0.3s;
}
.ov-fill.cart { background: linear-gradient(90deg, #F59E0B, #FBBF24); }
.ov-fill.buy  { background: linear-gradient(90deg, #10B981, #34D399); }
.ov-fill.fav  { background: linear-gradient(90deg, #8B5CF6, #A78BFA); }
</style>
