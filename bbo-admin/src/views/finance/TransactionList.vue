<template>
  <div class="transaction-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-row">
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item"><div class="stat-value">¥{{ statistics.today_income?.toFixed(2) || '0.00' }}</div><div class="stat-label">今日收入</div></div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item"><div class="stat-value">¥{{ statistics.today_expense?.toFixed(2) || '0.00' }}</div><div class="stat-label">今日支出</div></div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item"><div class="stat-value">¥{{ statistics.month_income?.toFixed(2) || '0.00' }}</div><div class="stat-label">本月收入</div></div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item"><div class="stat-value">¥{{ statistics.total_income?.toFixed(2) || '0.00' }}</div><div class="stat-label">总收入</div></div></el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card" shadow="never">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="流水号">
          <el-input v-model="searchForm.transaction_no" placeholder="交易流水号" clearable />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="searchForm.type" placeholder="全部" clearable>
            <el-option label="收入" :value="1" />
            <el-option label="支出" :value="2" />
            <el-option label="冻结" :value="3" />
            <el-option label="解冻" :value="4" />
          </el-select>
        </el-form-item>
        <el-form-item label="日期">
          <el-date-picker v-model="dateRange" type="daterange" range-separator="至" start-placeholder="开始" end-placeholder="结束" value-format="YYYY-MM-DD" @change="handleDateChange" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格 -->
    <el-card shadow="never">
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="transaction_no" label="流水号" width="200" />
        <el-table-column prop="user_id" label="用户ID" width="80" />
        <el-table-column prop="type" label="类型" width="80">
          <template #default="{ row }">
            <el-tag :type="row.type === 1 ? 'success' : row.type === 2 ? 'danger' : 'warning'" size="small">
              {{ typeNames[row.type] }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="amount" label="金额" width="120">
          <template #default="{ row }">
            <span :class="row.type === 1 ? 'text-success' : 'text-danger'">
              {{ row.type === 1 ? '+' : '-' }}¥{{ row.amount }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="balance" label="余额" width="120">
          <template #default="{ row }">¥{{ row.balance }}</template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="150" />
        <el-table-column prop="created_at" label="时间" width="170" />
      </el-table>

      <div class="pagination-wrapper">
        <el-pagination v-model:current-page="pagination.page" v-model:page-size="pagination.pageSize" :total="pagination.total" layout="total, sizes, prev, pager, next" @size-change="fetchList" @current-change="fetchList" />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { getTransactionList, getTransactionStatistics, type Transaction } from '@/api/transaction'

const typeNames: Record<number, string> = { 1: '收入', 2: '支出', 3: '冻结', 4: '解冻' }
const searchForm = reactive({ transaction_no: '', type: '' as number | string, start_date: '', end_date: '' })
const dateRange = ref<[string, string] | null>(null)
const list = ref<Transaction[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const statistics = ref<any>({})

const handleDateChange = (val: [string, string] | null) => {
  searchForm.start_date = val ? val[0] : ''; searchForm.end_date = val ? val[1] : ''
}

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getTransactionList({ page: pagination.page, pageSize: pagination.pageSize, ...searchForm })
    list.value = res.data.list || []; pagination.total = res.data.total || 0
  } catch (error) { console.error('获取列表失败:', error) }
  finally { loading.value = false }
}

const fetchStatistics = async () => {
  try { const res: any = await getTransactionStatistics(); statistics.value = res.data || {} }
  catch (error) { console.error('获取统计失败:', error) }
}

const handleSearch = () => { pagination.page = 1; fetchList() }
const handleReset = () => { searchForm.transaction_no = ''; searchForm.type = ''; searchForm.start_date = ''; searchForm.end_date = ''; dateRange.value = null; handleSearch() }

onMounted(() => { fetchStatistics(); fetchList() })
</script>

<style scoped>
.transaction-list { padding: 20px; }
.stat-row { margin-bottom: 20px; }
.stat-item { text-align: center; padding: 10px; }
.stat-value { font-size: 24px; font-weight: bold; color: #303133; }
.stat-label { font-size: 14px; color: #909399; margin-top: 5px; }
.search-card { margin-bottom: 20px; }
.pagination-wrapper { margin-top: 20px; display: flex; justify-content: flex-end; }
.text-success { color: #67c23a; }
.text-danger { color: #f56c6c; }
</style>
