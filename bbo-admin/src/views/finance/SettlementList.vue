<template>
  <div class="settlement-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-row">
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item pending"><div class="stat-value">{{ statistics.pending || 0 }}</div><div class="stat-label">待结算</div></div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item"><div class="stat-value">¥{{ statistics.pending_amount?.toFixed(2) || '0.00' }}</div><div class="stat-label">待结算金额</div></div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item"><div class="stat-value">¥{{ statistics.settled_amount?.toFixed(2) || '0.00' }}</div><div class="stat-label">已结算金额</div></div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item commission"><div class="stat-value">¥{{ statistics.total_commission?.toFixed(2) || '0.00' }}</div><div class="stat-label">平台佣金</div></div></el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card" shadow="never">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="结算单号">
          <el-input v-model="searchForm.settlement_no" placeholder="结算单号" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable>
            <el-option label="待结算" :value="0" />
            <el-option label="已结算" :value="1" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格 -->
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>结算列表</span>
          <el-button type="primary" @click="handleBatchSettle" :disabled="selectedIds.length === 0">
            批量结算 ({{ selectedIds.length }})
          </el-button>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="50" :selectable="row => row.status === 0" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="settlement_no" label="结算单号" min-width="200" />
        <el-table-column prop="user_id" label="卖家ID" width="90" />
        <el-table-column prop="order_id" label="订单ID" width="90" />
        <el-table-column prop="order_amount" label="订单金额" min-width="120">
          <template #default="{ row }">¥{{ row.order_amount }}</template>
        </el-table-column>
        <el-table-column prop="commission_rate" label="佣金率" width="80">
          <template #default="{ row }">{{ row.commission_rate }}%</template>
        </el-table-column>
        <el-table-column prop="commission_amount" label="佣金" min-width="100">
          <template #default="{ row }">¥{{ row.commission_amount }}</template>
        </el-table-column>
        <el-table-column prop="settlement_amount" label="结算金额" min-width="120">
          <template #default="{ row }"><span class="text-success">¥{{ row.settlement_amount }}</span></template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 0 ? 'warning' : 'success'" size="small">
              {{ row.status === 0 ? '待结算' : '已结算' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" min-width="170" />
        <el-table-column label="操作" width="80" fixed="right">
          <template #default="{ row }">
            <el-button v-if="row.status === 0" type="primary" link @click="handleSettle(row)">结算</el-button>
            <span v-else class="text-muted">已结算</span>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrapper">
        <el-pagination v-model:current-page="pagination.page" v-model:page-size="pagination.pageSize" :total="pagination.total" layout="total, sizes, prev, pager, next" @size-change="fetchList" @current-change="fetchList" />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getSettlementList, getSettlementStatistics, settleOne, batchSettle, type Settlement } from '@/api/settlement'

const searchForm = reactive({ settlement_no: '', status: '' as number | string })
const list = ref<Settlement[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const statistics = ref<any>({})
const selectedIds = ref<number[]>([])

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getSettlementList({ page: pagination.page, pageSize: pagination.pageSize, ...searchForm })
    list.value = res.data.list || []; pagination.total = res.data.total || 0
  } catch (error) { console.error('获取列表失败:', error) }
  finally { loading.value = false }
}

const fetchStatistics = async () => {
  try { const res: any = await getSettlementStatistics(); statistics.value = res.data || {} }
  catch (error) { console.error('获取统计失败:', error) }
}

const handleSearch = () => { pagination.page = 1; fetchList() }
const handleReset = () => { searchForm.settlement_no = ''; searchForm.status = ''; handleSearch() }

const handleSelectionChange = (rows: Settlement[]) => { selectedIds.value = rows.map(r => r.id) }

const handleSettle = async (row: Settlement) => {
  try {
    await ElMessageBox.confirm('确定结算该订单吗？', '提示', { type: 'warning' })
    await settleOne(row.id); ElMessage.success('结算成功'); fetchList(); fetchStatistics()
  } catch (error) { if (error !== 'cancel') console.error(error) }
}

const handleBatchSettle = async () => {
  try {
    await ElMessageBox.confirm(`确定结算选中的 ${selectedIds.value.length} 条记录吗？`, '提示', { type: 'warning' })
    const res: any = await batchSettle(selectedIds.value)
    ElMessage.success(res.message || '批量结算成功'); fetchList(); fetchStatistics()
  } catch (error) { if (error !== 'cancel') console.error(error) }
}

onMounted(() => { fetchStatistics(); fetchList() })
</script>

<style scoped>
.settlement-list { padding: 20px; }
.stat-row { margin-bottom: 20px; }
.stat-item { text-align: center; padding: 10px; }
.stat-item.pending .stat-value { color: #e6a23c; }
.stat-item.commission .stat-value { color: #67c23a; }
.stat-value { font-size: 24px; font-weight: bold; color: #303133; }
.stat-label { font-size: 14px; color: #909399; margin-top: 5px; }
.search-card { margin-bottom: 20px; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.pagination-wrapper { margin-top: 20px; display: flex; justify-content: flex-end; }
.text-success { color: #67c23a; font-weight: bold; }
.text-muted { color: #c0c4cc; }
</style>
