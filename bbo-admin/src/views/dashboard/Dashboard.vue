<template>
  <div class="dashboard">
    <!-- 概览卡片 -->
    <el-row :gutter="20" class="overview-row">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card users">
          <div class="stat-content">
            <div class="stat-icon">
              <el-icon><User /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ overview.users?.total || 0 }}</div>
              <div class="stat-label">总用户数</div>
              <div class="stat-sub">今日新增 {{ overview.users?.today || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card goods">
          <div class="stat-content">
            <div class="stat-icon">
              <el-icon><Goods /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ overview.goods?.total || 0 }}</div>
              <div class="stat-label">总商品数</div>
              <div class="stat-sub">在线 {{ overview.goods?.online || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card orders">
          <div class="stat-content">
            <div class="stat-icon">
              <el-icon><Document /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ overview.orders?.total || 0 }}</div>
              <div class="stat-label">总订单数</div>
              <div class="stat-sub">今日 {{ overview.orders?.today || 0 }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card amount">
          <div class="stat-content">
            <div class="stat-icon">
              <el-icon><Money /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">¥{{ formatMoney(overview.orders?.month_amount || 0) }}</div>
              <div class="stat-label">本月交易额</div>
              <div class="stat-sub">今日 ¥{{ formatMoney(overview.orders?.today_amount || 0) }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 待处理事项 -->
    <el-row :gutter="20" class="pending-row">
      <el-col :span="24">
        <el-card shadow="never">
          <template #header>
            <span>待处理事项</span>
          </template>
          <el-row :gutter="20">
            <el-col :span="8">
              <div class="pending-item" @click="$router.push('/goods?status=0')">
                <div class="pending-count">{{ pending.pending_goods || 0 }}</div>
                <div class="pending-label">待审核商品</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="pending-item" @click="$router.push('/orders?status=1')">
                <div class="pending-count">{{ pending.pending_orders || 0 }}</div>
                <div class="pending-label">待处理订单</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="pending-item" @click="$router.push('/withdrawals?status=0')">
                <div class="pending-count">{{ pending.pending_withdrawals || 0 }}</div>
                <div class="pending-label">待审核提现</div>
              </div>
            </el-col>
          </el-row>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <!-- 趋势图表 -->
      <el-col :span="16">
        <el-card shadow="never">
          <template #header>
            <div class="chart-header">
              <span>数据趋势</span>
              <el-radio-group v-model="trendDays" size="small" @change="fetchTrend">
                <el-radio-button :value="7">近7天</el-radio-button>
                <el-radio-button :value="30">近30天</el-radio-button>
              </el-radio-group>
            </div>
          </template>
          <div class="trend-chart">
            <div v-if="trendData.length" class="simple-chart">
              <div class="chart-row" v-for="item in trendData" :key="item.date">
                <span class="chart-date">{{ item.date.slice(5) }}</span>
                <div class="chart-bars">
                  <div class="bar users" :style="{ width: getBarWidth(item.users, 'users') }"></div>
                  <div class="bar orders" :style="{ width: getBarWidth(item.orders, 'orders') }"></div>
                </div>
                <span class="chart-value">{{ item.orders }}单</span>
              </div>
            </div>
            <el-empty v-else description="暂无数据" />
          </div>
        </el-card>
      </el-col>

      <!-- 订单状态分布 -->
      <el-col :span="8">
        <el-card shadow="never">
          <template #header>
            <span>订单状态分布</span>
          </template>
          <div class="status-list">
            <div v-for="item in orderStatus" :key="item.status" class="status-item">
              <span class="status-name">{{ item.name }}</span>
              <span class="status-count">{{ item.count }}</span>
            </div>
            <el-empty v-if="!orderStatus.length" description="暂无数据" />
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" style="margin-top: 20px;">
      <!-- 热门分类 -->
      <el-col :span="12">
        <el-card shadow="never">
          <template #header>
            <span>热门分类 TOP10</span>
          </template>
          <el-table :data="hotCategories" size="small">
            <el-table-column type="index" label="排名" width="60" />
            <el-table-column prop="name" label="分类名称" />
            <el-table-column prop="goods_count" label="商品数" width="100" />
          </el-table>
        </el-card>
      </el-col>

      <!-- 最新订单 -->
      <el-col :span="12">
        <el-card shadow="never">
          <template #header>
            <span>最新订单</span>
          </template>
          <el-table :data="recentOrders" size="small">
            <el-table-column prop="order_no" label="订单号" width="180" />
            <el-table-column prop="total_amount" label="金额" width="100">
              <template #default="{ row }">
                ¥{{ row.total_amount }}
              </template>
            </el-table-column>
            <el-table-column prop="status" label="状态" width="80">
              <template #default="{ row }">
                <el-tag :type="getOrderStatusType(row.status)" size="small">
                  {{ getOrderStatusName(row.status) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="时间" />
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { User, Goods, Document, Money } from '@element-plus/icons-vue'
import {
  getDashboardOverview,
  getDashboardTrend,
  getDashboardOrderStatus,
  getDashboardHotCategories,
  getDashboardRecentOrders,
  getDashboardPending,
  type Overview,
  type TrendData
} from '@/api/dashboard'

const overview = ref<Partial<Overview>>({})
const pending = ref<any>({})
const trendDays = ref(7)
const trendData = ref<TrendData[]>([])
const orderStatus = ref<any[]>([])
const hotCategories = ref<any[]>([])
const recentOrders = ref<any[]>([])

const formatMoney = (value: number) => {
  return value.toLocaleString('zh-CN', { minimumFractionDigits: 2 })
}

const getBarWidth = (value: number, type: string) => {
  const maxValues = {
    users: Math.max(...trendData.value.map(d => d.users)) || 1,
    orders: Math.max(...trendData.value.map(d => d.orders)) || 1,
  }
  const max = maxValues[type as keyof typeof maxValues]
  return `${Math.min((value / max) * 100, 100)}%`
}

const getOrderStatusName = (status: number) => {
  const names: Record<number, string> = {
    0: '待支付', 1: '待发货', 2: '待收货', 3: '已完成', 4: '已取消', 5: '已退款'
  }
  return names[status] || '未知'
}

const getOrderStatusType = (status: number) => {
  const types: Record<number, string> = {
    0: 'warning', 1: 'primary', 2: 'info', 3: 'success', 4: 'danger', 5: 'danger'
  }
  return types[status] || 'info'
}

const fetchOverview = async () => {
  try {
    const res: any = await getDashboardOverview()
    overview.value = res.data || {}
  } catch (error) {
    console.error('获取概览失败:', error)
  }
}

const fetchPending = async () => {
  try {
    const res: any = await getDashboardPending()
    pending.value = res.data || {}
  } catch (error) {
    console.error('获取待处理事项失败:', error)
  }
}

const fetchTrend = async () => {
  try {
    const res: any = await getDashboardTrend(trendDays.value)
    trendData.value = res.data || []
  } catch (error) {
    console.error('获取趋势失败:', error)
  }
}

const fetchOrderStatus = async () => {
  try {
    const res: any = await getDashboardOrderStatus()
    orderStatus.value = res.data || []
  } catch (error) {
    console.error('获取订单状态失败:', error)
  }
}

const fetchHotCategories = async () => {
  try {
    const res: any = await getDashboardHotCategories()
    hotCategories.value = res.data || []
  } catch (error) {
    console.error('获取热门分类失败:', error)
  }
}

const fetchRecentOrders = async () => {
  try {
    const res: any = await getDashboardRecentOrders()
    recentOrders.value = res.data || []
  } catch (error) {
    console.error('获取最新订单失败:', error)
  }
}

onMounted(() => {
  fetchOverview()
  fetchPending()
  fetchTrend()
  fetchOrderStatus()
  fetchHotCategories()
  fetchRecentOrders()
})
</script>

<style scoped>
.dashboard {
  padding: 20px;
}

.overview-row {
  margin-bottom: 20px;
}

.stat-card {
  cursor: pointer;
  transition: transform 0.3s;
}

.stat-card:hover {
  transform: translateY(-3px);
}

.stat-content {
  display: flex;
  align-items: center;
}

.stat-icon {
  width: 70px;
  height: 70px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  color: #fff;
  margin-right: 15px;
}

.stat-card.users .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-card.goods .stat-icon { background: linear-gradient(135deg, #f093fb, #f5576c); }
.stat-card.orders .stat-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-card.amount .stat-icon { background: linear-gradient(135deg, #43e97b, #38f9d7); }

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #303133;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  margin-top: 4px;
}

.stat-sub {
  font-size: 12px;
  color: #c0c4cc;
  margin-top: 2px;
}

.pending-row {
  margin-bottom: 20px;
}

.pending-item {
  text-align: center;
  padding: 20px;
  cursor: pointer;
  border-radius: 8px;
  transition: background 0.3s;
}

.pending-item:hover {
  background: #f5f7fa;
}

.pending-count {
  font-size: 36px;
  font-weight: bold;
  color: #409eff;
}

.pending-label {
  font-size: 14px;
  color: #909399;
  margin-top: 8px;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.trend-chart {
  min-height: 300px;
}

.simple-chart {
  padding: 10px 0;
}

.chart-row {
  display: flex;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.chart-date {
  width: 50px;
  font-size: 12px;
  color: #909399;
}

.chart-bars {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 0 10px;
}

.bar {
  height: 12px;
  border-radius: 6px;
  transition: width 0.3s;
}

.bar.users {
  background: linear-gradient(90deg, #667eea, #764ba2);
}

.bar.orders {
  background: linear-gradient(90deg, #4facfe, #00f2fe);
}

.chart-value {
  width: 60px;
  text-align: right;
  font-size: 12px;
  color: #606266;
}

.status-list {
  padding: 10px 0;
}

.status-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.status-item:last-child {
  border-bottom: none;
}

.status-name {
  color: #606266;
}

.status-count {
  font-weight: bold;
  color: #303133;
}
</style>
