<template>
  <div class="game-stats">
    <el-page-header @back="goBack" class="mb-4">
      <template #content>
        <span class="text-large font-600">{{ gameName }} - 统计数据</span>
      </template>
    </el-page-header>

    <!-- 日期选择 -->
    <el-card class="mb-4">
      <div class="date-filter">
        <span class="mr-2">日期范围:</span>
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          value-format="YYYY-MM-DD"
          :shortcuts="dateShortcuts"
          @change="fetchStats"
        />
      </div>
    </el-card>

    <!-- 总览卡片 -->
    <el-row :gutter="20" class="mb-4">
      <el-col :span="8">
        <el-card shadow="hover">
          <el-statistic title="总抽奖次数" :value="stats.total_plays" />
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <el-statistic title="总奖品价值" :value="stats.total_value" prefix="$" :precision="2" />
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <el-statistic
            title="平均每次成本"
            :value="stats.total_plays > 0 ? stats.total_value / stats.total_plays : 0"
            prefix="$"
            :precision="4"
          />
        </el-card>
      </el-col>
    </el-row>

    <!-- 图表 -->
    <el-row :gutter="20" class="mb-4">
      <el-col :span="14">
        <el-card>
          <template #header>每日趋势</template>
          <div ref="trendChartRef" style="height: 300px"></div>
        </el-card>
      </el-col>
      <el-col :span="10">
        <el-card>
          <template #header>奖品分布</template>
          <div ref="pieChartRef" style="height: 300px"></div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 奖品发放明细 -->
    <el-card>
      <template #header>奖品发放明细</template>
      <el-table :data="stats.prize_distribution" v-loading="loading" stripe>
        <el-table-column prop="prize_name" label="奖品" min-width="150" />
        <el-table-column prop="prize_type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag size="small">{{ prizeTypeLabels[row.prize_type] || row.prize_type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="count" label="数量" width="100" align="right" />
        <el-table-column label="总价值" width="120" align="right">
          <template #default="{ row }">
            ${{ Number(row.total_value || 0).toFixed(2) }}
          </template>
        </el-table-column>
        <el-table-column label="占比" width="120" align="right">
          <template #default="{ row }">
            {{ stats.total_plays > 0 ? ((row.count / stats.total_plays) * 100).toFixed(2) : 0 }}%
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import * as echarts from 'echarts'
import { getGameDetail, getGameStats, type Game } from '@/api/game'
import dayjs from 'dayjs'

const route = useRoute()
const router = useRouter()
const gameId = Number(route.params.id)

const loading = ref(false)
const game = ref<Game | null>(null)
const trendChartRef = ref<HTMLDivElement>()
const pieChartRef = ref<HTMLDivElement>()
let trendChart: echarts.ECharts | null = null
let pieChart: echarts.ECharts | null = null

const prizeTypeLabels: Record<string, string> = {
  points: '积分',
  coupon: '优惠券',
  cash: '现金券',
  goods: '实物商品',
  chance: '游戏次数',
}

const dateRange = ref<[string, string]>([
  dayjs().subtract(30, 'day').format('YYYY-MM-DD'),
  dayjs().format('YYYY-MM-DD'),
])

const dateShortcuts = [
  {
    text: '最近7天',
    value: () => [dayjs().subtract(7, 'day').toDate(), dayjs().toDate()],
  },
  {
    text: '最近30天',
    value: () => [dayjs().subtract(30, 'day').toDate(), dayjs().toDate()],
  },
  {
    text: '最近90天',
    value: () => [dayjs().subtract(90, 'day').toDate(), dayjs().toDate()],
  },
]

const stats = reactive({
  total_plays: 0,
  total_value: 0,
  daily_stats: [] as Array<{ stat_date: string; total_count: number; total_value: number }>,
  prize_distribution: [] as Array<{ prize_type: string; prize_name: string; count: number; total_value: number }>,
})

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

const goBack = () => {
  router.push('/game')
}

const fetchGame = async () => {
  try {
    const res = await getGameDetail(gameId)
    game.value = res.data
  } catch (error: any) {
    ElMessage.error(error.message || 'Failed to load game')
  }
}

const fetchStats = async () => {
  if (!dateRange.value || dateRange.value.length !== 2) return

  loading.value = true
  try {
    const res = await getGameStats(gameId, dateRange.value[0], dateRange.value[1])
    const data = res.data

    stats.total_plays = data.total_plays || 0
    stats.total_value = data.total_value || 0
    stats.daily_stats = data.daily_stats || []
    stats.prize_distribution = data.prize_distribution || []

    await nextTick()
    renderCharts()
  } catch (error: any) {
    ElMessage.error(error.message || 'Failed to load stats')
  } finally {
    loading.value = false
  }
}

const renderCharts = () => {
  renderTrendChart()
  renderPieChart()
}

const renderTrendChart = () => {
  if (!trendChartRef.value) return

  if (!trendChart) {
    trendChart = echarts.init(trendChartRef.value)
  }

  const dates = stats.daily_stats.map(d => d.stat_date)
  const counts = stats.daily_stats.map(d => Number(d.total_count) || 0)
  const values = stats.daily_stats.map(d => Number(d.total_value) || 0)

  const option: echarts.EChartsOption = {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'cross' },
    },
    legend: {
      data: ['抽奖次数', '奖品价值 ($)'],
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      containLabel: true,
    },
    xAxis: {
      type: 'category',
      data: dates,
    },
    yAxis: [
      {
        type: 'value',
        name: '次数',
        position: 'left',
      },
      {
        type: 'value',
        name: '价值 ($)',
        position: 'right',
      },
    ],
    series: [
      {
        name: '抽奖次数',
        type: 'bar',
        data: counts,
        itemStyle: { color: '#409EFF' },
      },
      {
        name: '奖品价值 ($)',
        type: 'line',
        yAxisIndex: 1,
        data: values,
        itemStyle: { color: '#E6A23C' },
      },
    ],
  }

  trendChart.setOption(option)
}

const renderPieChart = () => {
  if (!pieChartRef.value) return

  if (!pieChart) {
    pieChart = echarts.init(pieChartRef.value)
  }

  const data = stats.prize_distribution.map(p => ({
    name: p.prize_name || `${p.prize_type} - Unknown`,
    value: p.count,
  }))

  const option: echarts.EChartsOption = {
    tooltip: {
      trigger: 'item',
      formatter: '{a} <br/>{b}: {c} ({d}%)',
    },
    legend: {
      orient: 'vertical',
      right: 10,
      top: 20,
    },
    series: [
      {
        name: '奖品',
        type: 'pie',
        radius: ['40%', '70%'],
        center: ['40%', '50%'],
        avoidLabelOverlap: false,
        label: {
          show: false,
          position: 'center',
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 16,
            fontWeight: 'bold',
          },
        },
        labelLine: {
          show: false,
        },
        data: data,
      },
    ],
  }

  pieChart.setOption(option)
}

// Resize charts on window resize
const handleResize = () => {
  trendChart?.resize()
  pieChart?.resize()
}

onMounted(() => {
  fetchGame()
  fetchStats()

  window.addEventListener('resize', handleResize)
})

// Cleanup
import { onUnmounted } from 'vue'
onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  trendChart?.dispose()
  pieChart?.dispose()
})
</script>

<style scoped>
.date-filter {
  display: flex;
  align-items: center;
}
</style>
