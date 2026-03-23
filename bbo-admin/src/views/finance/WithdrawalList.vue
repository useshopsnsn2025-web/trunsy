<template>
  <div class="withdrawal-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-row">
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item pending"><div class="stat-value">{{ statistics.pending || 0 }}</div><div class="stat-label">待审核</div></div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item processing"><div class="stat-value">{{ statistics.processing || 0 }}</div><div class="stat-label">处理中</div></div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item"><div class="stat-value">${{ statistics.today_amount?.toFixed(2) || '0.00' }}</div><div class="stat-label">今日放款</div></div></el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover"><div class="stat-item"><div class="stat-value">${{ statistics.total_amount?.toFixed(2) || '0.00' }}</div><div class="stat-label">累计放款</div></div></el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card" shadow="never">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="提现单号">
          <el-input v-model="searchForm.withdrawal_no" placeholder="提现单号" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable>
            <el-option label="待审核" :value="0" />
            <el-option label="处理中" :value="1" />
            <el-option label="已完成" :value="2" />
            <el-option label="已拒绝" :value="3" />
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
      <el-table :data="list" v-loading="loading" stripe table-layout="auto">
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="withdrawal_no" label="提现单号" min-width="180" />
        <el-table-column prop="user_id" label="用户ID" width="80" />
        <el-table-column label="用户金额" min-width="120">
          <template #default="{ row }">
            <span v-if="row.display_amount && row.display_currency">
              {{ getCurrencySymbol(row.display_currency) }}{{ row.display_amount }}
            </span>
            <span v-else>${{ row.amount }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="amount" label="结算金额(USD)" min-width="120">
          <template #default="{ row }">${{ row.amount }}</template>
        </el-table-column>
        <el-table-column prop="fee" label="手续费" min-width="90">
          <template #default="{ row }">${{ row.fee }}</template>
        </el-table-column>
        <el-table-column prop="actual_amount" label="实际到账" min-width="100">
          <template #default="{ row }">${{ row.actual_amount }}</template>
        </el-table-column>
        <el-table-column prop="account_name" label="账户名" min-width="120" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="statusTypes[row.status]" size="small">{{ statusNames[row.status] }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="申请时间" min-width="160" />
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <template v-if="row.status === 0">
              <el-button type="success" link @click="handleApprove(row)">通过</el-button>
              <el-button type="danger" link @click="handleReject(row)">拒绝</el-button>
            </template>
            <template v-if="row.status === 1">
              <el-button type="primary" link @click="handleComplete(row)">标记完成</el-button>
            </template>
            <el-button type="info" link @click="handleView(row)">详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrapper">
        <el-pagination v-model:current-page="pagination.page" v-model:page-size="pagination.pageSize" :total="pagination.total" layout="total, sizes, prev, pager, next" @size-change="fetchList" @current-change="fetchList" />
      </div>
    </el-card>

    <!-- 拒绝对话框 -->
    <el-dialog v-model="rejectDialogVisible" title="拒绝提现" width="400px">
      <el-form label-width="80px">
        <el-form-item label="拒绝原因">
          <el-input v-model="rejectReason" type="textarea" :rows="3" placeholder="请输入拒绝原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectDialogVisible = false">取消</el-button>
        <el-button type="danger" @click="handleRejectSubmit">确认拒绝</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getWithdrawalList, getWithdrawalStatistics, approveWithdrawal, rejectWithdrawal, completeWithdrawal, type Withdrawal } from '@/api/withdrawal'

const statusNames: Record<number, string> = { 0: '待审核', 1: '处理中', 2: '已完成', 3: '已拒绝' }
const statusTypes: Record<number, string> = { 0: 'warning', 1: 'primary', 2: 'success', 3: 'danger' }

// 货币符号映射
const currencySymbols: Record<string, string> = {
  USD: '$',
  TWD: 'NT$',
  JPY: '¥',
  EUR: '€',
  GBP: '£',
  CNY: '¥',
}
const getCurrencySymbol = (currency: string) => currencySymbols[currency] || currency

const searchForm = reactive({ withdrawal_no: '', status: '' as number | string })
const list = ref<Withdrawal[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const statistics = ref<any>({})

const rejectDialogVisible = ref(false)
const rejectReason = ref('')
const currentId = ref(0)

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getWithdrawalList({ page: pagination.page, pageSize: pagination.pageSize, ...searchForm })
    list.value = res.data.list || []; pagination.total = res.data.total || 0
  } catch (error) { console.error('获取列表失败:', error) }
  finally { loading.value = false }
}

const fetchStatistics = async () => {
  try { const res: any = await getWithdrawalStatistics(); statistics.value = res.data || {} }
  catch (error) { console.error('获取统计失败:', error) }
}

const handleSearch = () => { pagination.page = 1; fetchList() }
const handleReset = () => { searchForm.withdrawal_no = ''; searchForm.status = ''; handleSearch() }

const handleApprove = async (row: Withdrawal) => {
  try {
    await ElMessageBox.confirm('确定审核通过该提现申请吗？', '提示', { type: 'warning' })
    await approveWithdrawal(row.id); ElMessage.success('审核通过'); fetchList(); fetchStatistics()
  } catch (error) { if (error !== 'cancel') console.error(error) }
}

const handleReject = (row: Withdrawal) => { currentId.value = row.id; rejectReason.value = ''; rejectDialogVisible.value = true }

const handleRejectSubmit = async () => {
  if (!rejectReason.value) { ElMessage.warning('请输入拒绝原因'); return }
  try {
    await rejectWithdrawal(currentId.value, rejectReason.value)
    ElMessage.success('已拒绝'); rejectDialogVisible.value = false; fetchList(); fetchStatistics()
  } catch (error) { console.error(error) }
}

const handleComplete = async (row: Withdrawal) => {
  try {
    await ElMessageBox.confirm('确定标记该提现为已完成吗？', '提示', { type: 'warning' })
    await completeWithdrawal(row.id); ElMessage.success('已完成'); fetchList(); fetchStatistics()
  } catch (error) { if (error !== 'cancel') console.error(error) }
}

const handleView = (row: Withdrawal) => { ElMessage.info('详情功能待开发') }

onMounted(() => { fetchStatistics(); fetchList() })
</script>

<style scoped>
.withdrawal-list { padding: 20px; }
.stat-row { margin-bottom: 20px; }
.stat-item { text-align: center; padding: 10px; }
.stat-item.pending .stat-value { color: #e6a23c; }
.stat-item.processing .stat-value { color: #409eff; }
.stat-value { font-size: 24px; font-weight: bold; color: #303133; }
.stat-label { font-size: 14px; color: #909399; margin-top: 5px; }
.search-card { margin-bottom: 20px; }
.pagination-wrapper { margin-top: 20px; display: flex; justify-content: flex-end; }
</style>
