<template>
  <div class="page-analysis">
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
    <div v-if="pageStats.length > 0" class="summary-row">
      <div class="summary-card">
        <div class="summary-icon icon-blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ pageStats.length }}</div>
          <div class="summary-label">页面总数</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-indigo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ totalPV.toLocaleString() }}</div>
          <div class="summary-label">总 PV</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ totalUV.toLocaleString() }}</div>
          <div class="summary-label">总 UV</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-amber">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ avgDuration }}</div>
          <div class="summary-label">平均停留</div>
        </div>
      </div>
    </div>

    <!-- Page Stats Table -->
    <el-card class="main-card" shadow="never">
      <template #header>
        <div class="card-title">
          <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
          <span>页面表现</span>
          <span v-if="pageStats.length > 0" class="card-subtitle">点击行查看停留时长分布</span>
        </div>
      </template>
      <el-table
        :data="pageStats"
        v-loading="loading"
        size="small"
        @row-click="selectPage"
        highlight-current-row
        class="page-table"
        :row-class-name="tableRowClassName"
      >
        <el-table-column type="index" width="50" label="#">
          <template #default="{ $index }">
            <span class="row-index" :class="{ 'top-3': $index < 3 }">{{ $index + 1 }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="page" label="页面" min-width="240">
          <template #default="{ row }">
            <div class="page-cell">
              <svg class="page-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <span class="page-path">{{ row.page }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="PV" width="110" align="right" sortable sort-by="pv">
          <template #default="{ row }">
            <span class="metric-num">{{ row.pv?.toLocaleString() }}</span>
          </template>
        </el-table-column>
        <el-table-column label="UV" width="110" align="right" sortable sort-by="uv">
          <template #default="{ row }">
            <span class="metric-num">{{ row.uv?.toLocaleString() }}</span>
          </template>
        </el-table-column>
        <el-table-column label="平均停留" width="120" align="right" sortable :sort-by="(row: any) => row.avg_duration_ms">
          <template #default="{ row }">
            <span class="duration-text" :class="getDurationClass(row.avg_duration_ms)">
              {{ formatDuration(row.avg_duration_ms) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="跳出率" width="180" align="right" sortable :sort-by="(row: any) => row.bounce_rate">
          <template #default="{ row }">
            <div class="bounce-cell">
              <div class="bounce-track">
                <div
                  class="bounce-fill"
                  :style="{ width: Math.min(row.bounce_rate, 100) + '%' }"
                  :class="bounceClass(row.bounce_rate)"
                ></div>
              </div>
              <span class="bounce-text" :class="bounceClass(row.bounce_rate)">{{ row.bounce_rate }}%</span>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- Duration Distribution -->
    <transition name="slide-fade">
      <el-card v-if="selectedPage" class="main-card" shadow="never">
        <template #header>
          <div class="card-title">
            <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>停留时长分布</span>
            <span class="selected-page-tag">{{ selectedPage }}</span>
          </div>
        </template>
        <div ref="distributionChartRef" style="height: 320px"></div>
      </el-card>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, onUnmounted } from 'vue'
import * as echarts from 'echarts'
import { getAnalyticsPageStats } from '@/api/analytics'
import dayjs from 'dayjs'

const loading = ref(false)
const distributionChartRef = ref<HTMLDivElement>()
let distributionChart: echarts.ECharts | null = null

const pageStats = ref<any[]>([])
const selectedPage = ref('')
const distribution = ref<any[]>([])

const dateRange = ref<[string, string]>([
  dayjs().subtract(7, 'day').format('YYYY-MM-DD'),
  dayjs().format('YYYY-MM-DD'),
])

const dateShortcuts = [
  { text: '最近7天', value: () => [dayjs().subtract(7, 'day').toDate(), dayjs().toDate()] },
  { text: '最近30天', value: () => [dayjs().subtract(30, 'day').toDate(), dayjs().toDate()] },
  { text: '最近90天', value: () => [dayjs().subtract(90, 'day').toDate(), dayjs().toDate()] },
]

const barGradients = [
  ['#10B981', '#34D399'],
  ['#3B82F6', '#60A5FA'],
  ['#F59E0B', '#FBBF24'],
  ['#EF4444', '#F87171'],
  ['#8B5CF6', '#A78BFA'],
]

const totalPV = computed(() => pageStats.value.reduce((s, r) => s + (r.pv || 0), 0))
const totalUV = computed(() => pageStats.value.reduce((s, r) => s + (r.uv || 0), 0))
const avgDuration = computed(() => {
  if (pageStats.value.length === 0) return '0s'
  const avg = pageStats.value.reduce((s, r) => s + (r.avg_duration_ms || 0), 0) / pageStats.value.length
  return formatDuration(avg)
})

const formatDuration = (ms: number): string => {
  if (!ms || ms <= 0) return '0s'
  const seconds = Math.round(ms / 1000)
  if (seconds < 60) return seconds + 's'
  const minutes = Math.floor(seconds / 60)
  const remainSeconds = seconds % 60
  return minutes + 'm ' + remainSeconds + 's'
}

const bounceClass = (rate: number): string => {
  if (rate > 50) return 'high'
  if (rate > 30) return 'mid'
  return 'low'
}

const getDurationClass = (ms: number): string => {
  if (!ms || ms <= 0) return ''
  const sec = ms / 1000
  if (sec >= 60) return 'dur-good'
  if (sec >= 15) return 'dur-ok'
  return 'dur-low'
}

const tableRowClassName = ({ rowIndex }: { row: any; rowIndex: number }): string => {
  return rowIndex < 3 ? 'top-row' : ''
}

const fetchData = async () => {
  if (!dateRange.value || dateRange.value.length !== 2) return
  loading.value = true
  try {
    const res = await getAnalyticsPageStats({
      start_date: dateRange.value[0],
      end_date: dateRange.value[1],
    })
    pageStats.value = res.data.list || []
  } catch (e: any) {
    console.error('Failed to load page stats:', e)
  } finally {
    loading.value = false
  }
}

const selectPage = async (row: any) => {
  selectedPage.value = row.page
  try {
    const res = await getAnalyticsPageStats({
      start_date: dateRange.value[0],
      end_date: dateRange.value[1],
      page: row.page,
    })
    distribution.value = res.data.distribution || []
    await nextTick()
    renderDistributionChart()
  } catch (e: any) {
    console.error('Failed to load distribution:', e)
  }
}

const renderDistributionChart = () => {
  if (!distributionChartRef.value) return
  if (!distributionChart) {
    distributionChart = echarts.init(distributionChartRef.value)
  }

  const labels = distribution.value.map(d => d.label)
  const counts = distribution.value.map(d => d.count)
  const total = counts.reduce((a: number, b: number) => a + b, 0)

  distributionChart.setOption({
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#fff',
      borderColor: '#E2E8F0',
      borderWidth: 1,
      padding: [12, 16],
      textStyle: { color: '#1E293B', fontSize: 13 },
      extraCssText: 'box-shadow: 0 4px 16px rgba(0,0,0,0.08); border-radius: 8px;',
      formatter: (params: any) => {
        const p = params[0]
        const pct = total > 0 ? ((p.value / total) * 100).toFixed(1) : '0'
        return `<div style="font-weight:600;margin-bottom:4px">${p.name}</div>
          <div style="display:flex;align-items:center;gap:6px">
            <span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:${p.color}"></span>
            <span>${p.value} 次</span>
            <span style="color:#94A3B8;margin-left:4px">(${pct}%)</span>
          </div>`
      },
    },
    grid: { left: '3%', right: '4%', top: 24, bottom: '3%', containLabel: true },
    xAxis: {
      type: 'category',
      data: labels,
      axisLabel: { fontSize: 12, color: '#64748B' },
      axisLine: { lineStyle: { color: '#E2E8F0' } },
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      axisLabel: { color: '#94A3B8', fontSize: 11 },
      splitLine: { lineStyle: { color: '#F1F5F9', type: 'dashed' } },
      axisLine: { show: false },
      axisTick: { show: false },
    },
    series: [{
      type: 'bar',
      data: counts.map((v: number, i: number) => ({
        value: v,
        itemStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: barGradients[i]?.[0] || barGradients[1][0] },
            { offset: 1, color: (barGradients[i]?.[1] || barGradients[1][1]) + '66' },
          ]),
          borderRadius: [6, 6, 0, 0],
        },
      })),
      barWidth: '48%',
      label: {
        show: true,
        position: 'top',
        formatter: (params: any) => {
          const pct = total > 0 ? ((params.value / total) * 100).toFixed(0) : '0'
          return `{val|${params.value}}{pct|  ${pct}%}`
        },
        rich: {
          val: { fontSize: 13, fontWeight: 700, color: '#1E293B' },
          pct: { fontSize: 11, color: '#94A3B8' },
        },
      },
    }],
  })
}

const handleResize = () => {
  distributionChart?.resize()
}

onMounted(() => {
  fetchData()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  distributionChart?.dispose()
})
</script>

<style scoped>
/* ===== Base ===== */
.page-analysis { padding: 0; }

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
.icon-green  { background: linear-gradient(135deg, #10B981, #34D399); }
.icon-amber  { background: linear-gradient(135deg, #F59E0B, #FBBF24); }
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
.card-subtitle {
  font-size: 12px; font-weight: 400; color: #94A3B8; margin-left: 8px;
}
.selected-page-tag {
  font-size: 12px; font-weight: 500; color: #3B82F6;
  background: #EFF6FF; padding: 2px 10px; border-radius: 6px;
  font-family: 'SF Mono', 'Cascadia Code', Consolas, monospace;
  margin-left: 4px;
}

/* ===== Table ===== */
.page-table {
  --el-table-border-color: #F1F5F9;
  --el-table-row-hover-bg-color: #F8FAFC;
  cursor: pointer;
}
.page-table :deep(.el-table__row) {
  transition: background 0.15s;
}
.page-table :deep(.top-row td) {
  background: #FAFBFE !important;
}
.page-table :deep(.el-table__row.current-row td) {
  background: #EFF6FF !important;
}

.row-index {
  font-size: 12px; font-weight: 600; color: #94A3B8;
  display: inline-flex; align-items: center; justify-content: center;
  width: 24px; height: 24px; border-radius: 6px;
}
.row-index.top-3 {
  background: linear-gradient(135deg, #3B82F6, #60A5FA);
  color: #fff;
}

.page-cell {
  display: flex; align-items: center; gap: 8px;
}
.page-icon {
  width: 16px; height: 16px; color: #CBD5E1; flex-shrink: 0;
}
.page-path {
  font-family: 'SF Mono', 'Cascadia Code', Consolas, monospace;
  font-size: 13px; color: #3B82F6; font-weight: 500;
}

.metric-num {
  font-size: 13px; font-weight: 600; color: #334155;
}

.duration-text {
  font-size: 13px; font-weight: 600; color: #64748B;
}
.dur-good { color: #10B981; }
.dur-ok   { color: #F59E0B; }
.dur-low  { color: #94A3B8; }

/* Bounce Rate Cell */
.bounce-cell {
  display: flex; align-items: center; gap: 10px; justify-content: flex-end;
}
.bounce-track {
  width: 64px; height: 6px; background: #F1F5F9;
  border-radius: 3px; overflow: hidden; flex-shrink: 0;
}
.bounce-fill {
  height: 100%; border-radius: 3px; transition: width 0.3s;
}
.bounce-fill.low  { background: linear-gradient(90deg, #10B981, #34D399); }
.bounce-fill.mid  { background: linear-gradient(90deg, #F59E0B, #FBBF24); }
.bounce-fill.high { background: linear-gradient(90deg, #EF4444, #F87171); }
.bounce-text {
  font-size: 12px; font-weight: 600; min-width: 40px; text-align: right;
}
.bounce-text.low  { color: #10B981; }
.bounce-text.mid  { color: #F59E0B; }
.bounce-text.high { color: #EF4444; }

/* ===== Transition ===== */
.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s ease-in; }
.slide-fade-enter-from { opacity: 0; transform: translateY(12px); }
.slide-fade-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
