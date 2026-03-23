<template>
  <div class="analytics-funnel">
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
    <div v-if="steps.length > 0" class="summary-row">
      <div class="summary-card">
        <div class="summary-icon icon-blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ steps[0]?.uv?.toLocaleString() || 0 }}</div>
          <div class="summary-label">总访客数</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ steps[steps.length - 1]?.uv?.toLocaleString() || 0 }}</div>
          <div class="summary-label">最终转化</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-amber">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ overallConversionRate }}%</div>
          <div class="summary-label">整体转化率</div>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-red">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </div>
        <div class="summary-content">
          <div class="summary-value">{{ totalLoss.toLocaleString() }}</div>
          <div class="summary-label">总流失数</div>
        </div>
      </div>
    </div>

    <!-- Funnel Visualization -->
    <el-card class="main-card" shadow="never">
      <template #header>
        <div class="card-title">
          <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
          <span>购买转化漏斗</span>
        </div>
      </template>
      <div v-loading="loading" class="funnel-body">
        <div v-if="steps.length > 0" class="funnel-content">
          <!-- Left: Funnel Bars -->
          <div class="funnel-visual">
            <div
              v-for="(step, index) in steps"
              :key="step.step_order"
              class="funnel-stage"
              :class="{ active: activeStep === index }"
              @mouseenter="activeStep = index"
              @mouseleave="activeStep = -1"
            >
              <div class="stage-bar-track">
                <div
                  class="stage-bar-fill"
                  :style="{
                    width: getBarWidth(index) + '%',
                    background: `linear-gradient(90deg, ${stageGradients[index][0]}, ${stageGradients[index][1]})`,
                  }"
                >
                  <span class="stage-name">{{ step.step_name }}</span>
                  <span class="stage-uv">{{ step.uv.toLocaleString() }}</span>
                </div>
              </div>
              <div v-if="index < steps.length - 1" class="stage-connector">
                <div class="connector-line"></div>
                <span class="connector-rate" :class="getRateClass(steps[index + 1]?.step_rate)">
                  {{ steps[index + 1]?.step_rate }}%
                </span>
                <span class="connector-loss">-{{ (step.uv - steps[index + 1].uv).toLocaleString() }}</span>
              </div>
            </div>
          </div>

          <!-- Right: Step Detail Cards -->
          <div class="step-details">
            <div
              v-for="(step, index) in steps"
              :key="'d-' + step.step_order"
              class="step-card"
              :class="{ active: activeStep === index }"
              @mouseenter="activeStep = index"
              @mouseleave="activeStep = -1"
            >
              <div class="step-card-header">
                <div class="step-indicator" :style="{ background: stageGradients[index][0] }">{{ index + 1 }}</div>
                <span class="step-title">{{ step.step_name }}</span>
              </div>
              <div class="step-card-metrics">
                <div class="step-metric">
                  <span class="step-metric-value">{{ step.uv.toLocaleString() }}</span>
                  <span class="step-metric-label">访客</span>
                </div>
                <div class="step-metric">
                  <span class="step-metric-value">{{ step.overall_rate }}%</span>
                  <span class="step-metric-label">总转化</span>
                </div>
                <div v-if="index > 0" class="step-metric">
                  <span class="step-metric-value" :class="getRateClass(step.step_rate)">{{ step.step_rate }}%</span>
                  <span class="step-metric-label">步骤转化</span>
                </div>
                <div v-if="index > 0" class="step-metric">
                  <span class="step-metric-value loss-val">{{ (steps[index - 1].uv - step.uv).toLocaleString() }}</span>
                  <span class="step-metric-label">流失</span>
                </div>
              </div>
              <div class="step-progress">
                <div
                  class="step-progress-fill"
                  :style="{
                    width: step.overall_rate + '%',
                    background: `linear-gradient(90deg, ${stageGradients[index][0]}, ${stageGradients[index][1]})`,
                  }"
                ></div>
              </div>
            </div>
          </div>
        </div>
        <el-empty v-else description="暂无漏斗数据" />
      </div>
    </el-card>

    <!-- Charts Row -->
    <div class="charts-row">
      <el-card class="main-card" shadow="never">
        <template #header>
          <div class="card-title">
            <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            <span>各步骤转化率</span>
          </div>
        </template>
        <div ref="conversionChartRef" style="height: 340px"></div>
      </el-card>
      <el-card class="main-card" shadow="never">
        <template #header>
          <div class="card-title">
            <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span>流失分析</span>
          </div>
        </template>
        <el-table :data="lossAnalysis" stripe size="small" class="loss-table">
          <el-table-column prop="from" label="来源步骤" min-width="120">
            <template #default="{ row }">
              <span class="tbl-step">{{ row.from }}</span>
            </template>
          </el-table-column>
          <el-table-column label="" width="36" align="center">
            <template #default>
              <svg class="arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </template>
          </el-table-column>
          <el-table-column prop="to" label="目标步骤" min-width="120">
            <template #default="{ row }">
              <span class="tbl-step">{{ row.to }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="loss" label="流失数" width="90" align="right">
            <template #default="{ row }">
              <span class="tbl-loss-num">{{ row.loss }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="loss_rate" label="流失率" width="130" align="right">
            <template #default="{ row }">
              <div class="tbl-rate-cell">
                <div class="tbl-rate-track">
                  <div
                    class="tbl-rate-fill"
                    :style="{ width: Math.min(row.loss_rate, 100) + '%' }"
                    :class="row.loss_rate > 50 ? 'high' : row.loss_rate > 30 ? 'mid' : 'low'"
                  ></div>
                </div>
                <span class="tbl-rate-text" :class="row.loss_rate > 50 ? 'high' : row.loss_rate > 30 ? 'mid' : 'low'">{{ row.loss_rate }}%</span>
              </div>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, onUnmounted } from 'vue'
import * as echarts from 'echarts'
import { getAnalyticsFunnel } from '@/api/analytics'
import dayjs from 'dayjs'

const loading = ref(false)
const conversionChartRef = ref<HTMLDivElement>()
let conversionChart: echarts.ECharts | null = null

const steps = ref<any[]>([])
const activeStep = ref(-1)

// Analytics dashboard gradient palette — blue → indigo → violet → cyan → amber → red → emerald
const stageGradients = [
  ['#3B82F6', '#60A5FA'],
  ['#6366F1', '#818CF8'],
  ['#8B5CF6', '#A78BFA'],
  ['#06B6D4', '#22D3EE'],
  ['#F59E0B', '#FBBF24'],
  ['#EF4444', '#F87171'],
  ['#10B981', '#34D399'],
]

const dateRange = ref<[string, string]>([
  dayjs().subtract(7, 'day').format('YYYY-MM-DD'),
  dayjs().format('YYYY-MM-DD'),
])

const dateShortcuts = [
  { text: '最近7天', value: () => [dayjs().subtract(7, 'day').toDate(), dayjs().toDate()] },
  { text: '最近30天', value: () => [dayjs().subtract(30, 'day').toDate(), dayjs().toDate()] },
  { text: '最近90天', value: () => [dayjs().subtract(90, 'day').toDate(), dayjs().toDate()] },
]

const overallConversionRate = computed(() => {
  if (steps.value.length < 2) return 0
  const first = steps.value[0]?.uv || 0
  const last = steps.value[steps.value.length - 1]?.uv || 0
  return first > 0 ? Math.round((last / first) * 10000) / 100 : 0
})

const totalLoss = computed(() => {
  if (steps.value.length < 2) return 0
  return (steps.value[0]?.uv || 0) - (steps.value[steps.value.length - 1]?.uv || 0)
})

const lossAnalysis = computed(() => {
  const result: any[] = []
  for (let i = 0; i < steps.value.length - 1; i++) {
    const current = steps.value[i]
    const next = steps.value[i + 1]
    const loss = current.uv - next.uv
    const lossRate = current.uv > 0 ? Math.round((loss / current.uv) * 100) : 0
    result.push({
      from: current.step_name,
      to: next.step_name,
      loss: loss.toLocaleString(),
      loss_rate: lossRate,
    })
  }
  return result
})

const getBarWidth = (index: number): number => {
  if (steps.value.length === 0) return 0
  const maxUv = steps.value[0]?.uv || 1
  const uv = steps.value[index]?.uv || 0
  const ratio = maxUv > 0 ? (uv / maxUv) * 100 : 0
  return Math.max(ratio, 18) // minimum 18% width so small bars remain readable
}

const getRateClass = (rate: number): string => {
  if (rate >= 50) return 'rate-good'
  if (rate >= 20) return 'rate-warn'
  return 'rate-bad'
}

const fetchData = async () => {
  if (!dateRange.value || dateRange.value.length !== 2) return
  loading.value = true
  try {
    const res = await getAnalyticsFunnel({
      start_date: dateRange.value[0],
      end_date: dateRange.value[1],
      funnel_type: 'purchase',
    })
    steps.value = res.data.steps || []
    await nextTick()
    renderConversionChart()
  } catch (e: any) {
    console.error('Failed to load funnel:', e)
  } finally {
    loading.value = false
  }
}

const renderConversionChart = () => {
  if (!conversionChartRef.value || steps.value.length === 0) return
  if (!conversionChart) {
    conversionChart = echarts.init(conversionChartRef.value)
  }

  const names = steps.value.map((s: any) => s.step_name)
  const stepRates = steps.value.map((s: any) => s.step_rate)
  const overallRates = steps.value.map((s: any) => s.overall_rate)

  conversionChart.setOption({
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#fff',
      borderColor: '#E2E8F0',
      borderWidth: 1,
      padding: [12, 16],
      textStyle: { color: '#1E293B', fontSize: 13 },
      extraCssText: 'box-shadow: 0 4px 16px rgba(0,0,0,0.08); border-radius: 8px;',
    },
    legend: {
      data: ['步骤转化率', '总转化率'],
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
      data: names,
      axisLabel: { rotate: 20, fontSize: 11, color: '#94A3B8' },
      axisLine: { lineStyle: { color: '#E2E8F0' } },
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      max: 100,
      axisLabel: { formatter: '{value}%', color: '#94A3B8', fontSize: 11 },
      splitLine: { lineStyle: { color: '#F1F5F9', type: 'dashed' } },
      axisLine: { show: false },
      axisTick: { show: false },
    },
    series: [
      {
        name: '步骤转化率',
        type: 'bar',
        data: stepRates.map((v: number, i: number) => ({
          value: v,
          itemStyle: {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
              { offset: 0, color: stageGradients[i]?.[0] || stageGradients[0][0] },
              { offset: 1, color: (stageGradients[i]?.[1] || stageGradients[0][1]) + '66' },
            ]),
            borderRadius: [6, 6, 0, 0],
          },
        })),
        barWidth: '36%',
        label: {
          show: true,
          position: 'top',
          formatter: '{c}%',
          fontSize: 11,
          fontWeight: 600,
          color: '#64748B',
        },
      },
      {
        name: '总转化率',
        type: 'line',
        data: overallRates,
        smooth: true,
        symbol: 'circle',
        symbolSize: 8,
        lineStyle: { width: 2.5, color: '#F59E0B' },
        itemStyle: { color: '#F59E0B', borderWidth: 2, borderColor: '#fff' },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(245,158,11,0.12)' },
            { offset: 1, color: 'rgba(245,158,11,0.01)' },
          ]),
        },
      },
    ],
  })
}

const handleResize = () => {
  conversionChart?.resize()
}

onMounted(() => {
  fetchData()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  conversionChart?.dispose()
})
</script>

<style scoped>
/* ===== Base ===== */
.analytics-funnel { padding: 0; }

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
.icon-blue  { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
.icon-green { background: linear-gradient(135deg, #10B981, #34D399); }
.icon-amber { background: linear-gradient(135deg, #F59E0B, #FBBF24); }
.icon-red   { background: linear-gradient(135deg, #EF4444, #F87171); }
.summary-value { font-size: 22px; font-weight: 700; color: #1E293B; line-height: 1.2; }
.summary-label { font-size: 12px; color: #94A3B8; margin-top: 2px; }

/* ===== Main Card ===== */
.main-card { margin-bottom: 20px; border: 1px solid #E2E8F0; border-radius: 12px; }
.main-card :deep(.el-card__header) { padding: 16px 24px; border-bottom: 1px solid #F1F5F9; }
.card-title { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 600; color: #1E293B; }
.title-icon { width: 18px; height: 18px; color: #64748B; flex-shrink: 0; }

/* ===== Funnel Body ===== */
.funnel-body { min-height: 420px; padding: 8px 0; }
.funnel-content { display: flex; gap: 40px; align-items: flex-start; }

/* -- Funnel Bars -- */
.funnel-visual { flex: 1; min-width: 0; display: flex; flex-direction: column; padding: 8px 0; }
.funnel-stage { display: flex; flex-direction: column; align-items: center; }
.stage-bar-track { width: 100%; display: flex; justify-content: center; }
.stage-bar-fill {
  height: 54px; border-radius: 10px;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 20px; min-width: 120px;
  position: relative; overflow: hidden;
  transition: transform 0.25s, box-shadow 0.25s;
  cursor: default;
}
.stage-bar-fill::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 100%);
  pointer-events: none;
}
.funnel-stage.active .stage-bar-fill {
  transform: scaleY(1.06);
  box-shadow: 0 6px 24px rgba(0,0,0,0.14);
}
.stage-name {
  font-size: 13px; font-weight: 600; color: #fff;
  text-shadow: 0 1px 3px rgba(0,0,0,0.12);
  position: relative; z-index: 1;
}
.stage-uv {
  font-size: 17px; font-weight: 700; color: #fff;
  text-shadow: 0 1px 3px rgba(0,0,0,0.12);
  position: relative; z-index: 1;
}

/* -- Connector -- */
.stage-connector { display: flex; align-items: center; gap: 8px; padding: 3px 0; height: 26px; }
.connector-line { width: 1px; height: 100%; background: #E2E8F0; }
.connector-rate {
  font-size: 11px; font-weight: 700;
  padding: 1px 8px; border-radius: 10px; background: #F1F5F9;
}
.connector-rate.rate-good { color: #10B981; background: #ECFDF5; }
.connector-rate.rate-warn { color: #F59E0B; background: #FFFBEB; }
.connector-rate.rate-bad  { color: #EF4444; background: #FEF2F2; }
.connector-loss { font-size: 11px; color: #94A3B8; font-weight: 500; }

/* -- Step Detail Cards -- */
.step-details { flex: 0 0 380px; display: flex; flex-direction: column; gap: 8px; padding: 8px 0; }
.step-card {
  padding: 14px 16px; border-radius: 10px;
  border: 1px solid transparent; background: #F8FAFC;
  transition: all 0.2s;
}
.step-card.active {
  background: #fff; border-color: #E2E8F0;
  box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}
.step-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.step-indicator {
  width: 24px; height: 24px; border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
}
.step-title { font-size: 13px; font-weight: 600; color: #334155; }
.step-card-metrics { display: flex; gap: 18px; margin-bottom: 10px; padding-left: 34px; }
.step-metric { display: flex; flex-direction: column; gap: 1px; }
.step-metric-value { font-size: 15px; font-weight: 700; color: #1E293B; line-height: 1.3; }
.step-metric-label { font-size: 11px; color: #94A3B8; }
.rate-good { color: #10B981 !important; }
.rate-warn { color: #F59E0B !important; }
.rate-bad  { color: #EF4444 !important; }
.loss-val  { color: #94A3B8 !important; }
.step-progress { height: 3px; background: #F1F5F9; border-radius: 2px; overflow: hidden; margin-left: 34px; }
.step-progress-fill { height: 100%; border-radius: 2px; transition: width 0.5s ease; }

/* ===== Charts Row ===== */
.charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* ===== Loss Table ===== */
.loss-table { --el-table-border-color: #F1F5F9; }
.tbl-step { font-size: 13px; font-weight: 500; color: #334155; }
.arrow-icon { width: 14px; height: 14px; color: #CBD5E1; }
.tbl-loss-num { font-weight: 600; color: #64748B; font-size: 13px; }
.tbl-rate-cell { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
.tbl-rate-track { width: 48px; height: 6px; background: #F1F5F9; border-radius: 3px; overflow: hidden; flex-shrink: 0; }
.tbl-rate-fill { height: 100%; border-radius: 3px; transition: width 0.3s; }
.tbl-rate-fill.low  { background: #10B981; }
.tbl-rate-fill.mid  { background: #F59E0B; }
.tbl-rate-fill.high { background: #EF4444; }
.tbl-rate-text { font-size: 12px; font-weight: 600; min-width: 36px; text-align: right; }
.tbl-rate-text.low  { color: #10B981; }
.tbl-rate-text.mid  { color: #F59E0B; }
.tbl-rate-text.high { color: #EF4444; }
</style>
