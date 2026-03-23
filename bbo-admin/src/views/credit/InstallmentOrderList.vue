<template>
  <div class="installment-order-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-cards">
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.total_orders || 0 }}</div>
            <div class="stat-label">总订单数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value primary">{{ statistics.active_orders || 0 }}</div>
            <div class="stat-label">进行中</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value success">{{ statistics.completed_orders || 0 }}</div>
            <div class="stat-label">已完成</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value danger">{{ statistics.overdue_orders || 0 }}</div>
            <div class="stat-label">逾期中</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ formatNumber(statistics.total_amount || 0) }}</div>
            <div class="stat-label">分期总额 (多币种)</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value success">{{ formatNumber(statistics.total_repaid || 0) }}</div>
            <div class="stat-label">已还款 (多币种)</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="分期单号/用户昵称" clearable style="width: 180px" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="待确认" :value="0" />
            <el-option label="进行中" :value="1" />
            <el-option label="已完成" :value="2" />
            <el-option label="已逾期" :value="3" />
            <el-option label="已取消" :value="4" />
          </el-select>
        </el-form-item>
        <el-form-item label="创建时间">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="-"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            style="width: 240px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">查询</el-button>
          <el-button @click="resetSearch">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card">
      <el-table :data="tableData" v-loading="loading" border stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="installment_no" label="分期单号" width="180" />
        <el-table-column label="用户" min-width="160">
          <template #default="{ row }">
            <div class="user-info">
              <el-avatar :size="36" :src="row.user?.avatar">{{ row.user?.nickname?.charAt(0) || 'U' }}</el-avatar>
              <div class="user-text">
                <div>{{ row.user?.nickname || '-' }}</div>
                <div class="text-gray">{{ row.user?.email || '-' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="分期方案" width="120">
          <template #default="{ row }">
            {{ row.plan?.name || row.periods + '期' }}
          </template>
        </el-table-column>
        <el-table-column label="分期总额" width="120">
          <template #default="{ row }">
            <span class="amount">{{ formatPrice(row.total_amount, row.currency) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="每期金额" width="120">
          <template #default="{ row }">
            <span class="amount primary">{{ formatPrice(row.period_amount, row.currency) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="进度" width="140">
          <template #default="{ row }">
            <div class="progress-info">
              <el-progress :percentage="Math.round(row.paid_periods / row.periods * 1000) / 10" :stroke-width="8" />
              <span class="progress-text">{{ row.paid_periods }}/{{ row.periods }}期</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="逾期天数" width="90">
          <template #default="{ row }">
            <el-tag v-if="row.overdue_days > 0" type="danger" size="small">{{ row.overdue_days }}天</el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ row.status_text || getStatusName(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="160" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleView(row)">详情</el-button>
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

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailDialogVisible" title="分期订单详情" width="900px">
      <div v-if="currentItem" class="detail-content">
        <el-descriptions title="订单信息" :column="3" border>
          <el-descriptions-item label="分期单号">{{ currentItem.installment_no }}</el-descriptions-item>
          <el-descriptions-item label="关联订单">{{ currentItem.order_id || '-' }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusType(currentItem.status)">{{ currentItem.status_text || getStatusName(currentItem.status) }}</el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="用户信息" :column="3" border class="mt-20">
          <el-descriptions-item label="用户ID">{{ currentItem.user_id }}</el-descriptions-item>
          <el-descriptions-item label="用户昵称">{{ currentItem.user?.nickname || '-' }}</el-descriptions-item>
          <el-descriptions-item label="用户邮箱">{{ currentItem.user?.email || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="金额信息" :column="3" border class="mt-20">
          <el-descriptions-item label="商品总额">
            <span class="amount">{{ formatPrice(currentItem.total_amount, currentItem.currency) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="已付金额">
            <span class="amount success">{{ formatPrice(currentItem.paid_periods * currentItem.period_amount, currentItem.currency) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="分期金额">
            <span class="amount primary">{{ formatPrice(currentItem.financed_amount, currentItem.currency) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="总利息">
            <span class="amount warning">{{ formatPrice(currentItem.total_interest, currentItem.currency) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="手续费">
            <span class="amount warning">{{ formatPrice(currentItem.total_fee, currentItem.currency) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="每期还款">
            <span class="amount success">{{ formatPrice(currentItem.period_amount, currentItem.currency) }}</span>
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="分期信息" :column="3" border class="mt-20">
          <el-descriptions-item label="分期方案">{{ currentItem.plan?.name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="总期数">{{ currentItem.periods }}期</el-descriptions-item>
          <el-descriptions-item label="已还期数">{{ currentItem.paid_periods }}期</el-descriptions-item>
          <el-descriptions-item label="下期还款日">{{ currentItem.next_due_date || '-' }}</el-descriptions-item>
          <el-descriptions-item label="逾期天数">
            <el-tag v-if="currentItem.overdue_days > 0" type="danger">{{ currentItem.overdue_days }}天</el-tag>
            <span v-else>0</span>
          </el-descriptions-item>
          <el-descriptions-item label="自动扣款">
            <el-tag :type="currentItem.auto_deduct ? 'success' : 'info'">
              {{ currentItem.auto_deduct ? '已开启' : '未开启' }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="时间信息" :column="2" border class="mt-20">
          <el-descriptions-item label="创建时间">{{ currentItem.created_at }}</el-descriptions-item>
          <el-descriptions-item label="完成时间">{{ currentItem.completed_at || '-' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 还款计划 -->
        <div class="mt-20">
          <h4>还款计划</h4>
          <el-table :data="currentItem.schedules || []" border size="small">
            <el-table-column prop="period" label="期数" width="80">
              <template #default="{ row }">第{{ row.period }}期</template>
            </el-table-column>
            <el-table-column label="本金" width="100">
              <template #default="{ row }">{{ formatPrice(row.principal, currentItem.currency) }}</template>
            </el-table-column>
            <el-table-column label="利息" width="100">
              <template #default="{ row }">{{ formatPrice(row.interest, currentItem.currency) }}</template>
            </el-table-column>
            <el-table-column label="手续费" width="100">
              <template #default="{ row }">{{ formatPrice(row.fee, currentItem.currency) }}</template>
            </el-table-column>
            <el-table-column label="应还金额" width="120">
              <template #default="{ row }">
                <span class="amount">{{ formatPrice(row.amount, currentItem.currency) }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="due_date" label="还款日期" width="120" />
            <el-table-column prop="paid_at" label="实际还款" width="160">
              <template #default="{ row }">{{ row.paid_at || '-' }}</template>
            </el-table-column>
            <el-table-column label="逾期" width="80">
              <template #default="{ row }">
                <el-tag v-if="row.overdue_days > 0" type="danger" size="small">{{ row.overdue_days }}天</el-tag>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="状态" width="90">
              <template #default="{ row }">
                <el-tag :type="getScheduleStatusType(row.status)" size="small">
                  {{ row.status_text || getScheduleStatusName(row.status) }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import {
  getInstallmentOrderList,
  getInstallmentOrderDetail,
  getInstallmentStatistics,
  type InstallmentOrder,
  type InstallmentStatistics
} from '@/api/credit'

const loading = ref(false)
const detailDialogVisible = ref(false)
const tableData = ref<InstallmentOrder[]>([])
const currentItem = ref<InstallmentOrder | null>(null)
const statistics = ref<Partial<InstallmentStatistics>>({})
const dateRange = ref<string[]>([])

const searchForm = reactive({
  keyword: '',
  status: '' as number | string
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const getStatusName = (status: number) => {
  const map: Record<number, string> = {
    0: '待确认', 1: '进行中', 2: '已完成', 3: '已逾期', 4: '已取消'
  }
  return map[status] || '未知'
}

const getStatusType = (status: number) => {
  const map: Record<number, string> = {
    0: 'warning', 1: 'primary', 2: 'success', 3: 'danger', 4: 'info'
  }
  return map[status] || 'info'
}

const getScheduleStatusName = (status: number) => {
  const map: Record<number, string> = {
    0: '待还款', 1: '已还款', 2: '已逾期'
  }
  return map[status] || '未知'
}

const getScheduleStatusType = (status: number) => {
  const map: Record<number, string> = {
    0: 'warning', 1: 'success', 2: 'danger'
  }
  return map[status] || 'info'
}

const formatNumber = (num: number) => {
  return num?.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00'
}

// 货币符号映射
const currencySymbols: Record<string, string> = {
  'USD': '$',
  'CAD': 'C$',
  'TWD': 'NT$',
  'JPY': '¥',
  'EUR': '€',
  'GBP': '£',
  'CNY': '¥',
  'HKD': 'HK$',
}

// 格式化金额（带货币符号）
const formatPrice = (amount: number, currency?: string) => {
  const symbol = currencySymbols[currency || 'USD'] || currency || '$'
  // 日元不显示小数
  if (currency === 'JPY') {
    return `${symbol}${Math.round(amount || 0).toLocaleString()}`
  }
  return `${symbol}${formatNumber(amount)}`
}

const loadData = async () => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.page,
      pageSize: pagination.pageSize,
      keyword: searchForm.keyword,
      status: searchForm.status
    }
    if (dateRange.value?.length === 2) {
      params.start_date = dateRange.value[0]
      params.end_date = dateRange.value[1]
    }
    const res: any = await getInstallmentOrderList(params)
    tableData.value = res.data.list
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const res: any = await getInstallmentStatistics()
    statistics.value = res.data
  } catch (e) {
    console.error('Failed to load statistics:', e)
  }
}

const resetSearch = () => {
  searchForm.keyword = ''
  searchForm.status = ''
  dateRange.value = []
  pagination.page = 1
  loadData()
}

const handleView = async (row: InstallmentOrder) => {
  try {
    const res: any = await getInstallmentOrderDetail(row.id)
    currentItem.value = res.data
    detailDialogVisible.value = true
  } catch (e) {
    console.error('Failed to load detail:', e)
  }
}

onMounted(() => {
  loadData()
  loadStatistics()
})
</script>

<style scoped>
.stat-cards {
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  color: #409EFF;
}

.stat-value.primary { color: #409EFF; }
.stat-value.success { color: #67C23A; }
.stat-value.danger { color: #F56C6C; }

.stat-label {
  margin-top: 8px;
  color: #999;
  font-size: 12px;
}

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

.user-info {
  display: flex;
  align-items: center;
}

.user-text {
  margin-left: 10px;
}

.text-gray {
  color: #999;
  font-size: 12px;
}

.amount {
  font-weight: bold;
  color: #333;
}

.amount.primary { color: #409EFF; }
.amount.success { color: #67C23A; }
.amount.warning { color: #E6A23C; }

.progress-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.progress-text {
  font-size: 12px;
  color: #999;
}

.mt-20 {
  margin-top: 20px;
}
</style>
