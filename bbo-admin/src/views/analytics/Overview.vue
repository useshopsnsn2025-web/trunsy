<template>
  <div class="analytics-overview">
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

    <!-- KPI Cards -->
    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-header">
          <div class="kpi-icon icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <div v-if="uvChange !== null" class="kpi-change" :class="uvChange >= 0 ? 'up' : 'down'">
            <svg v-if="uvChange >= 0" class="change-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
            <svg v-else class="change-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            <span>{{ Math.abs(uvChange) }}%</span>
          </div>
        </div>
        <div class="kpi-value">{{ (overview.today?.uv || 0).toLocaleString() }}</div>
        <div class="kpi-label">今日 UV</div>
        <div class="kpi-sub">较昨日 {{ overview.yesterday?.uv?.toLocaleString() || 0 }}</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-header">
          <div class="kpi-icon icon-indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </div>
          <div v-if="pvChange !== null" class="kpi-change" :class="pvChange >= 0 ? 'up' : 'down'">
            <svg v-if="pvChange >= 0" class="change-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
            <svg v-else class="change-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            <span>{{ Math.abs(pvChange) }}%</span>
          </div>
        </div>
        <div class="kpi-value">{{ (overview.today?.pv || 0).toLocaleString() }}</div>
        <div class="kpi-label">今日 PV</div>
        <div class="kpi-sub">较昨日 {{ overview.yesterday?.pv?.toLocaleString() || 0 }}</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-header">
          <div class="kpi-icon icon-amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          </div>
        </div>
        <div class="kpi-value">{{ overview.conversion_rate || 0 }}<span class="kpi-unit">%</span></div>
        <div class="kpi-label">转化率</div>
        <div class="kpi-progress-track">
          <div class="kpi-progress-fill amber" :style="{ width: Math.min(overview.conversion_rate || 0, 100) + '%' }"></div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-header">
          <div class="kpi-icon icon-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
        </div>
        <div class="kpi-value">{{ (overview.today?.conversion_count || 0).toLocaleString() }}</div>
        <div class="kpi-label">今日转化数</div>
        <div class="kpi-sub">累计转化</div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
      <el-card class="main-card chart-trend" shadow="never">
        <template #header>
          <div class="card-title">
            <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <span>UV / PV 趋势</span>
          </div>
        </template>
        <div ref="trendChartRef" style="height: 360px"></div>
      </el-card>
      <el-card class="main-card chart-device" shadow="never">
        <template #header>
          <div class="card-title">
            <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            <span>设备分布</span>
          </div>
        </template>
        <div ref="deviceChartRef" style="height: 360px"></div>
      </el-card>
    </div>

    <!-- Top Pages -->
    <el-card class="main-card" shadow="never">
      <template #header>
        <div class="card-title">
          <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <span>热门页面</span>
          <span v-if="(overview.top_pages || []).length > 0" class="card-count">Top {{ (overview.top_pages || []).length }}</span>
        </div>
      </template>
      <el-table :data="overview.top_pages || []" v-loading="loading" size="small" class="page-table">
        <el-table-column width="50" label="#" align="center">
          <template #default="{ $index }">
            <span class="row-rank" :class="{ 'top-3': $index < 3 }">{{ $index + 1 }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="page" label="页面路径" min-width="220">
          <template #default="{ row }">
            <div class="page-cell">
              <svg class="page-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <span class="page-path">{{ row.page }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="页面名称" width="120">
          <template #default="{ row }">
            <span class="page-name-tag">{{ pageNameMap[row.page] || '其他页面' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="PV" width="120" align="right" sortable sort-by="pv">
          <template #default="{ row }">
            <span class="metric-num">{{ row.pv?.toLocaleString() }}</span>
          </template>
        </el-table-column>
        <el-table-column label="UV" width="120" align="right" sortable sort-by="uv">
          <template #default="{ row }">
            <span class="metric-num">{{ row.uv?.toLocaleString() }}</span>
          </template>
        </el-table-column>
        <el-table-column label="占比" width="160" align="right">
          <template #default="{ row }">
            <div class="share-cell">
              <div class="share-track">
                <div class="share-fill" :style="{ width: getPageShare(row.pv) + '%' }"></div>
              </div>
              <span class="share-text">{{ getPageShare(row.pv) }}%</span>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, nextTick, onUnmounted } from 'vue'
import * as echarts from 'echarts'
import { getAnalyticsOverview } from '@/api/analytics'
import dayjs from 'dayjs'

const loading = ref(false)
const trendChartRef = ref<HTMLDivElement>()
const deviceChartRef = ref<HTMLDivElement>()
let trendChart: echarts.ECharts | null = null
let deviceChart: echarts.ECharts | null = null

const overview = reactive<any>({})

const dateRange = ref<[string, string]>([
  dayjs().subtract(7, 'day').format('YYYY-MM-DD'),
  dayjs().format('YYYY-MM-DD'),
])

const pageNameMap: Record<string, string> = {
  '/pages/index/index': '首页',
  '/pages/goods/list': '商品列表',
  '/pages/goods/detail': '商品详情',
  '/pages/search/index': '搜索页',
  '/pages/category/index': '分类页',
  '/pages/cart/index': '购物车',
  '/pages/order/checkout': '结算页',
  '/pages/order/payment-success': '支付成功',
  '/pages/auth/login': '登录页',
  '/pages/auth/register': '注册页',
  '/pages/user/index': '个人中心',
  '/pages/order/list': '订单列表',
  '/pages/order/detail': '订单详情',
  '/pages/user/address': '地址管理',
  '/pages/user/favorites': '我的收藏',
}

const dateShortcuts = [
  { text: '最近7天', value: () => [dayjs().subtract(7, 'day').toDate(), dayjs().toDate()] },
  { text: '最近30天', value: () => [dayjs().subtract(30, 'day').toDate(), dayjs().toDate()] },
  { text: '最近90天', value: () => [dayjs().subtract(90, 'day').toDate(), dayjs().toDate()] },
]

const deviceColors = ['#3B82F6', '#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']

const uvChange = computed(() => {
  const today = overview.today?.uv || 0
  const yesterday = overview.yesterday?.uv || 0
  if (yesterday === 0) return today > 0 ? 100 : 0
  return Math.round(((today - yesterday) / yesterday) * 100)
})

const pvChange = computed(() => {
  const today = overview.today?.pv || 0
  const yesterday = overview.yesterday?.pv || 0
  if (yesterday === 0) return today > 0 ? 100 : 0
  return Math.round(((today - yesterday) / yesterday) * 100)
})

const totalPagePV = computed(() => {
  return (overview.top_pages || []).reduce((s: number, p: any) => s + (p.pv || 0), 0)
})

const getPageShare = (pv: number): number => {
  if (!totalPagePV.value || !pv) return 0
  return Math.round((pv / totalPagePV.value) * 1000) / 10
}

const fetchData = async () => {
  if (!dateRange.value || dateRange.value.length !== 2) return
  loading.value = true
  try {
    const res = await getAnalyticsOverview({
      start_date: dateRange.value[0],
      end_date: dateRange.value[1],
    })
    Object.assign(overview, res.data)
    await nextTick()
    renderCharts()
  } catch (e: any) {
    console.error('Failed to load overview:', e)
  } finally {
    loading.value = false
  }
}

const renderCharts = () => {
  renderTrendChart()
  renderDeviceChart()
}

const renderTrendChart = () => {
  if (!trendChartRef.value) return
  if (!trendChart) {
    trendChart = echarts.init(trendChartRef.value)
  }

  const trend = overview.trend || []
  const dates = trend.map((d: any) => d.date)
  const pvData = trend.map((d: any) => d.pv || 0)
  const uvData = trend.map((d: any) => d.uv || 0)

  trendChart.setOption({
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#fff',
      borderColor: '#E2E8F0',
      borderWidth: 1,
      padding: [12, 16],
      textStyle: { color: '#1E293B', fontSize: 13 },
      extraCssText: 'box-shadow: 0 4px 16px rgba(0,0,0,0.08); border-radius: 8px;',
      axisPointer: { type: 'cross', crossStyle: { color: '#CBD5E1' } },
    },
    legend: {
      data: ['PV', 'UV'],
      top: 0,
      right: 0,
      itemWidth: 16,
      itemHeight: 8,
      itemGap: 24,
      textStyle: { fontSize: 12, color: '#64748B' },
    },
    grid: { left: '3%', right: '4%', top: 44, bottom: '3%', containLabel: true },
    xAxis: {
      type: 'category',
      data: dates,
      boundaryGap: false,
      axisLine: { lineStyle: { color: '#E2E8F0' } },
      axisLabel: { color: '#94A3B8', fontSize: 11 },
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      minInterval: 1,
      splitLine: { lineStyle: { color: '#F1F5F9', type: 'dashed' } },
      axisLabel: { color: '#94A3B8', fontSize: 11 },
      axisLine: { show: false },
      axisTick: { show: false },
    },
    series: [
      {
        name: 'PV',
        type: 'line',
        data: pvData,
        smooth: true,
        symbol: 'circle',
        symbolSize: 8,
        showSymbol: true,
        lineStyle: { width: 2.5, color: '#3B82F6' },
        itemStyle: { color: '#3B82F6', borderWidth: 2, borderColor: '#fff' },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(59,130,246,0.18)' },
            { offset: 1, color: 'rgba(59,130,246,0.01)' },
          ]),
        },
      },
      {
        name: 'UV',
        type: 'line',
        data: uvData,
        smooth: true,
        symbol: 'circle',
        symbolSize: 8,
        showSymbol: true,
        lineStyle: { width: 2.5, color: '#10B981' },
        itemStyle: { color: '#10B981', borderWidth: 2, borderColor: '#fff' },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(16,185,129,0.18)' },
            { offset: 1, color: 'rgba(16,185,129,0.01)' },
          ]),
        },
      },
    ],
  })
}

const renderDeviceChart = () => {
  if (!deviceChartRef.value) return
  if (!deviceChart) {
    deviceChart = echarts.init(deviceChartRef.value)
  }

  const dist = overview.device_distribution || []
  const data = dist.map((d: any, i: number) => ({
    name: d.device_type || 'unknown',
    value: d.uv || 0,
    itemStyle: { color: deviceColors[i] || deviceColors[0] },
  }))

  deviceChart.setOption({
    tooltip: {
      trigger: 'item',
      backgroundColor: '#fff',
      borderColor: '#E2E8F0',
      borderWidth: 1,
      padding: [12, 16],
      textStyle: { color: '#1E293B', fontSize: 13 },
      extraCssText: 'box-shadow: 0 4px 16px rgba(0,0,0,0.08); border-radius: 8px;',
      formatter: (params: any) => {
        return `<div style="display:flex;align-items:center;gap:8px">
          <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:${params.color}"></span>
          <span style="font-weight:600">${params.name}</span>
        </div>
        <div style="margin-top:6px;padding-left:18px">
          <span style="font-size:18px;font-weight:700;color:#1E293B">${params.value.toLocaleString()}</span>
          <span style="color:#94A3B8;margin-left:6px">(${params.percent}%)</span>
        </div>`
      },
    },
    legend: {
      orient: 'vertical',
      right: 16,
      top: 'center',
      itemWidth: 12,
      itemHeight: 12,
      itemGap: 14,
      textStyle: { fontSize: 12, color: '#64748B' },
      formatter: (name: string) => {
        const item = data.find((d: any) => d.name === name)
        return item ? `${name}  ${item.value}` : name
      },
    },
    series: [{
      type: 'pie',
      radius: ['48%', '72%'],
      center: ['35%', '50%'],
      avoidLabelOverlap: false,
      itemStyle: {
        borderRadius: 8,
        borderColor: '#fff',
        borderWidth: 3,
      },
      label: {
        show: true,
        position: 'center',
        formatter: () => {
          const total = data.reduce((s: number, d: any) => s + d.value, 0)
          return `{total|${total.toLocaleString()}}\n{label|总设备数}`
        },
        rich: {
          total: { fontSize: 24, fontWeight: 700, color: '#1E293B', lineHeight: 32 },
          label: { fontSize: 12, color: '#94A3B8', lineHeight: 20 },
        },
      },
      emphasis: {
        itemStyle: { shadowBlur: 16, shadowColor: 'rgba(0,0,0,0.12)' },
        label: { show: true },
      },
      data,
    }],
  })
}

const handleResize = () => {
  trendChart?.resize()
  deviceChart?.resize()
}

onMounted(() => {
  fetchData()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  trendChart?.dispose()
  deviceChart?.dispose()
})
</script>

<style scoped>
/* ===== Base ===== */
.analytics-overview { padding: 0; }

/* ===== Filter ===== */
.filter-card { margin-bottom: 20px; border: 1px solid #E2E8F0; border-radius: 12px; }
.filter-card :deep(.el-card__body) { padding: 16px 20px; }
.date-filter { display: flex; align-items: center; gap: 12px; }
.filter-label { font-size: 13px; font-weight: 500; color: #64748B; white-space: nowrap; }

/* ===== KPI Row ===== */
.kpi-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}
.kpi-card {
  background: #fff;
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  padding: 20px;
  transition: box-shadow 0.2s, transform 0.2s;
}
.kpi-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  transform: translateY(-2px);
}
.kpi-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.kpi-icon {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
}
.kpi-icon svg { width: 20px; height: 20px; color: #fff; }
.icon-blue   { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
.icon-indigo { background: linear-gradient(135deg, #6366F1, #818CF8); }
.icon-amber  { background: linear-gradient(135deg, #F59E0B, #FBBF24); }
.icon-green  { background: linear-gradient(135deg, #10B981, #34D399); }

.kpi-change {
  display: flex; align-items: center; gap: 3px;
  font-size: 12px; font-weight: 700;
  padding: 3px 8px; border-radius: 8px;
}
.kpi-change.up {
  color: #10B981; background: #ECFDF5;
}
.kpi-change.down {
  color: #EF4444; background: #FEF2F2;
}
.change-arrow { width: 14px; height: 14px; }

.kpi-value {
  font-size: 28px; font-weight: 700; color: #1E293B;
  line-height: 1.1; margin-bottom: 4px;
}
.kpi-unit {
  font-size: 16px; font-weight: 600; color: #64748B; margin-left: 2px;
}
.kpi-label {
  font-size: 13px; color: #64748B; font-weight: 500;
}
.kpi-sub {
  font-size: 11px; color: #94A3B8; margin-top: 6px;
}
.kpi-progress-track {
  height: 4px; background: #F1F5F9; border-radius: 2px;
  overflow: hidden; margin-top: 10px;
}
.kpi-progress-fill {
  height: 100%; border-radius: 2px; transition: width 0.5s;
}
.kpi-progress-fill.amber {
  background: linear-gradient(90deg, #F59E0B, #FBBF24);
}

/* ===== Charts Row ===== */
.charts-row {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 20px;
  margin-bottom: 20px;
}

/* ===== Main Card ===== */
.main-card { margin-bottom: 20px; border: 1px solid #E2E8F0; border-radius: 12px; }
.main-card :deep(.el-card__header) { padding: 16px 24px; border-bottom: 1px solid #F1F5F9; }
.charts-row .main-card { margin-bottom: 0; }
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

/* ===== Page Table ===== */
.page-table {
  --el-table-border-color: #F1F5F9;
  --el-table-row-hover-bg-color: #F8FAFC;
}
.page-table :deep(.el-table__header th) {
  font-weight: 600; color: #64748B; font-size: 12px; background: #FAFBFC;
}

.row-rank {
  font-size: 12px; font-weight: 600; color: #94A3B8;
  display: inline-flex; align-items: center; justify-content: center;
  width: 24px; height: 24px; border-radius: 6px;
}
.row-rank.top-3 {
  background: linear-gradient(135deg, #3B82F6, #60A5FA);
  color: #fff;
}

.page-cell { display: flex; align-items: center; gap: 8px; }
.page-icon { width: 16px; height: 16px; color: #CBD5E1; flex-shrink: 0; }
.page-path {
  font-family: 'SF Mono', 'Cascadia Code', Consolas, monospace;
  font-size: 13px; color: #3B82F6; font-weight: 500;
}

.page-name-tag {
  font-size: 12px; font-weight: 500; color: #334155;
  background: #F1F5F9; padding: 2px 8px; border-radius: 6px;
}

.metric-num { font-size: 13px; font-weight: 600; color: #334155; }

/* Share Bar */
.share-cell { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
.share-track {
  width: 64px; height: 6px; background: #F1F5F9;
  border-radius: 3px; overflow: hidden; flex-shrink: 0;
}
.share-fill {
  height: 100%; border-radius: 3px;
  background: linear-gradient(90deg, #3B82F6, #60A5FA);
  transition: width 0.3s;
}
.share-text {
  font-size: 12px; font-weight: 600; color: #3B82F6;
  min-width: 40px; text-align: right;
}
</style>
