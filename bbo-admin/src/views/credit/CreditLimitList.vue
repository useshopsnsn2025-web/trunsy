<template>
  <div class="credit-limit-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-cards">
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.total_users || 0 }}</div>
            <div class="stat-label">总用户数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value success">{{ statistics.active_users || 0 }}</div>
            <div class="stat-label">正常用户</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value danger">{{ statistics.frozen_users || 0 }}</div>
            <div class="stat-label">冻结用户</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">${{ formatNumber(statistics.total_limit || 0) }}</div>
            <div class="stat-label">总额度</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value warning">${{ formatNumber(statistics.total_used || 0) }}</div>
            <div class="stat-label">已用额度</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value primary">${{ formatNumber(statistics.total_available || 0) }}</div>
            <div class="stat-label">可用额度</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="用户昵称/邮箱/UUID" clearable style="width: 180px" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="正常" :value="1" />
            <el-option label="冻结" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="信用等级">
          <el-select v-model="searchForm.credit_level" placeholder="全部" clearable style="width: 120px">
            <el-option label="1级" :value="1" />
            <el-option label="2级" :value="2" />
            <el-option label="3级" :value="3" />
            <el-option label="4级" :value="4" />
            <el-option label="5级" :value="5" />
          </el-select>
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
        <el-table-column label="用户" min-width="180">
          <template #default="{ row }">
            <div class="user-info">
              <el-avatar :size="36" :src="row.user?.avatar">{{ row.user?.nickname?.charAt(0) || 'U' }}</el-avatar>
              <div class="user-text">
                <div>{{ row.user?.nickname || '-' }}</div>
                <div class="text-gray">{{ row.user?.uuid || '-' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="信用等级" width="140">
          <template #default="{ row }">
            <el-rate v-model="row.credit_level" disabled :max="5" />
          </template>
        </el-table-column>
        <el-table-column label="总额度" width="120">
          <template #default="{ row }">
            <span class="amount">${{ formatNumber(row.total_limit) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="已用额度" width="120">
          <template #default="{ row }">
            <span class="amount warning">${{ formatNumber(row.used_limit) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="可用额度" width="120">
          <template #default="{ row }">
            <span class="amount success">${{ formatNumber(row.available_limit) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="冻结额度" width="120">
          <template #default="{ row }">
            <span v-if="row.frozen_limit > 0" class="amount danger">${{ formatNumber(row.frozen_limit) }}</span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="逾期次数" width="90">
          <template #default="{ row }">
            <el-tag v-if="row.overdue_count > 0" type="danger" size="small">{{ row.overdue_count }}</el-tag>
            <span v-else>0</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '正常' : '冻结' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="开通时间" width="160" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleView(row)">详情</el-button>
            <el-button type="warning" size="small" @click="handleAdjust(row)">调额</el-button>
            <el-button
              v-if="row.status === 1"
              type="danger"
              size="small"
              @click="handleFreeze(row)"
            >冻结</el-button>
            <el-button
              v-else
              type="success"
              size="small"
              @click="handleUnfreeze(row)"
            >解冻</el-button>
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
    <el-dialog v-model="detailDialogVisible" title="额度详情" width="800px">
      <div v-if="currentItem" class="detail-content">
        <el-descriptions title="用户信息" :column="2" border>
          <el-descriptions-item label="用户ID">{{ currentItem.user_id }}</el-descriptions-item>
          <el-descriptions-item label="用户昵称">{{ currentItem.user?.nickname || '-' }}</el-descriptions-item>
          <el-descriptions-item label="UUID">{{ currentItem.user?.uuid || '-' }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ currentItem.user?.email || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="额度信息" :column="2" border class="mt-20">
          <el-descriptions-item label="信用等级">
            <el-rate v-model="currentItem.credit_level" disabled :max="5" show-text :texts="['1级', '2级', '3级', '4级', '5级']" />
          </el-descriptions-item>
          <el-descriptions-item label="账户状态">
            <el-tag :type="currentItem.status === 1 ? 'success' : 'danger'">
              {{ currentItem.status === 1 ? '正常' : '冻结' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="总额度">
            <span class="amount">${{ formatNumber(currentItem.total_limit) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="已用额度">
            <span class="amount warning">${{ formatNumber(currentItem.used_limit) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="可用额度">
            <span class="amount success">${{ formatNumber(currentItem.available_limit) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="冻结额度">
            <span class="amount danger">${{ formatNumber(currentItem.frozen_limit) }}</span>
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="还款统计" :column="2" border class="mt-20">
          <el-descriptions-item label="逾期次数">
            <el-tag v-if="currentItem.overdue_count > 0" type="danger">{{ currentItem.overdue_count }}</el-tag>
            <span v-else>0</span>
          </el-descriptions-item>
          <el-descriptions-item label="累计还款">
            <span class="amount">${{ formatNumber(currentItem.total_repaid) }}</span>
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="时间信息" :column="2" border class="mt-20">
          <el-descriptions-item label="开通时间">{{ currentItem.created_at }}</el-descriptions-item>
          <el-descriptions-item label="更新时间">{{ currentItem.updated_at || '-' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 调整日志 -->
        <div class="mt-20" v-if="adjustmentLogs.length > 0">
          <h4>调整记录</h4>
          <el-table :data="adjustmentLogs" border size="small">
            <el-table-column prop="type" label="类型" width="100" />
            <el-table-column prop="amount" label="金额" width="120">
              <template #default="{ row }">
                <span :class="row.amount > 0 ? 'success' : 'danger'">
                  {{ row.amount > 0 ? '+' : '' }}${{ formatNumber(row.amount) }}
                </span>
              </template>
            </el-table-column>
            <el-table-column prop="reason" label="原因" />
            <el-table-column prop="operator" label="操作人" width="100" />
            <el-table-column prop="created_at" label="时间" width="160" />
          </el-table>
        </div>
      </div>
    </el-dialog>

    <!-- 调额弹窗 -->
    <el-dialog v-model="adjustDialogVisible" title="调整额度" width="500px">
      <el-form :model="adjustForm" label-width="100px">
        <el-form-item label="用户">{{ currentItem?.user?.nickname }}</el-form-item>
        <el-form-item label="当前额度">${{ formatNumber(currentItem?.total_limit || 0) }}</el-form-item>
        <el-form-item label="调整类型">
          <el-radio-group v-model="adjustForm.type">
            <el-radio value="increase">增加</el-radio>
            <el-radio value="decrease">减少</el-radio>
            <el-radio value="level">调整等级</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="adjustForm.type !== 'level'" label="调整金额">
          <el-input-number v-model="adjustForm.amount" :min="0" :step="100" style="width: 200px" />
        </el-form-item>
        <el-form-item v-if="adjustForm.type === 'level'" label="信用等级">
          <el-rate v-model="adjustForm.credit_level" :max="5" show-text :texts="['1级', '2级', '3级', '4级', '5级']" />
        </el-form-item>
        <el-form-item label="调整原因">
          <el-input v-model="adjustForm.reason" type="textarea" :rows="3" placeholder="请输入调整原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adjustDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitAdjust" :loading="adjustLoading">确定</el-button>
      </template>
    </el-dialog>

    <!-- 冻结弹窗 -->
    <el-dialog v-model="freezeDialogVisible" title="冻结额度" width="500px">
      <el-form :model="freezeForm" label-width="100px">
        <el-form-item label="用户">{{ currentItem?.user?.nickname }}</el-form-item>
        <el-form-item label="可用额度">${{ formatNumber(currentItem?.available_limit || 0) }}</el-form-item>
        <el-form-item label="冻结金额">
          <el-input-number v-model="freezeForm.amount" :min="0" :max="currentItem?.available_limit" :step="100" style="width: 200px" />
        </el-form-item>
        <el-form-item label="冻结原因">
          <el-input v-model="freezeForm.reason" type="textarea" :rows="3" placeholder="请输入冻结原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="freezeDialogVisible = false">取消</el-button>
        <el-button type="danger" @click="submitFreeze" :loading="freezeLoading">确定冻结</el-button>
      </template>
    </el-dialog>

    <!-- 解冻弹窗 -->
    <el-dialog v-model="unfreezeDialogVisible" title="解冻额度" width="500px">
      <el-form :model="unfreezeForm" label-width="100px">
        <el-form-item label="用户">{{ currentItem?.user?.nickname }}</el-form-item>
        <el-form-item label="冻结额度">${{ formatNumber(currentItem?.frozen_limit || 0) }}</el-form-item>
        <el-form-item label="解冻金额">
          <el-input-number v-model="unfreezeForm.amount" :min="0" :max="currentItem?.frozen_limit" :step="100" style="width: 200px" />
        </el-form-item>
        <el-form-item label="解冻原因">
          <el-input v-model="unfreezeForm.reason" type="textarea" :rows="3" placeholder="请输入解冻原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="unfreezeDialogVisible = false">取消</el-button>
        <el-button type="success" @click="submitUnfreeze" :loading="unfreezeLoading">确定解冻</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import {
  getUserCreditLimitList,
  getUserCreditLimitDetail,
  getCreditLimitStatistics,
  getCreditLimitLogs,
  adjustCreditLimit,
  freezeCreditLimit,
  unfreezeCreditLimit,
  updateCreditLevel,
  type UserCreditLimit,
  type CreditLimitStatistics
} from '@/api/credit'

const loading = ref(false)
const adjustLoading = ref(false)
const freezeLoading = ref(false)
const unfreezeLoading = ref(false)
const detailDialogVisible = ref(false)
const adjustDialogVisible = ref(false)
const freezeDialogVisible = ref(false)
const unfreezeDialogVisible = ref(false)
const tableData = ref<UserCreditLimit[]>([])
const currentItem = ref<UserCreditLimit | null>(null)
const statistics = ref<Partial<CreditLimitStatistics>>({})
const adjustmentLogs = ref<any[]>([])

const searchForm = reactive({
  keyword: '',
  status: '' as number | string,
  credit_level: '' as number | string
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const adjustForm = reactive({
  type: 'increase',
  amount: 0,
  credit_level: 1,
  reason: ''
})

const freezeForm = reactive({
  amount: 0,
  reason: ''
})

const unfreezeForm = reactive({
  amount: 0,
  reason: ''
})

const formatNumber = (num: number) => {
  return num?.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00'
}

const loadData = async () => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.page,
      pageSize: pagination.pageSize,
      keyword: searchForm.keyword,
      status: searchForm.status,
      credit_level: searchForm.credit_level
    }
    const res: any = await getUserCreditLimitList(params)
    tableData.value = res.data.list
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const res: any = await getCreditLimitStatistics()
    statistics.value = res.data
  } catch (e) {
    console.error('Failed to load statistics:', e)
  }
}

const resetSearch = () => {
  searchForm.keyword = ''
  searchForm.status = ''
  searchForm.credit_level = ''
  pagination.page = 1
  loadData()
}

const handleView = async (row: UserCreditLimit) => {
  try {
    const res: any = await getUserCreditLimitDetail(row.id)
    currentItem.value = res.data
    // 加载调整日志
    try {
      const logsRes: any = await getCreditLimitLogs(row.id)
      adjustmentLogs.value = logsRes.data || []
    } catch (e) {
      adjustmentLogs.value = []
    }
    detailDialogVisible.value = true
  } catch (e) {
    console.error('Failed to load detail:', e)
  }
}

const handleAdjust = (row: UserCreditLimit) => {
  currentItem.value = row
  adjustForm.type = 'increase'
  adjustForm.amount = 0
  adjustForm.credit_level = row.credit_level
  adjustForm.reason = ''
  adjustDialogVisible.value = true
}

const handleFreeze = (row: UserCreditLimit) => {
  currentItem.value = row
  freezeForm.amount = 0
  freezeForm.reason = ''
  freezeDialogVisible.value = true
}

const handleUnfreeze = (row: UserCreditLimit) => {
  currentItem.value = row
  unfreezeForm.amount = row.frozen_limit
  unfreezeForm.reason = ''
  unfreezeDialogVisible.value = true
}

const submitAdjust = async () => {
  if (!currentItem.value) return
  if (!adjustForm.reason) {
    ElMessage.error('请输入调整原因')
    return
  }

  adjustLoading.value = true
  try {
    if (adjustForm.type === 'level') {
      await updateCreditLevel(currentItem.value.id, {
        credit_level: adjustForm.credit_level,
        reason: adjustForm.reason
      })
      ElMessage.success('信用等级已更新')
    } else {
      const adjustment = adjustForm.type === 'increase' ? adjustForm.amount : -adjustForm.amount
      await adjustCreditLimit(currentItem.value.id, {
        adjustment,
        reason: adjustForm.reason
      })
      ElMessage.success('额度已调整')
    }
    adjustDialogVisible.value = false
    loadData()
    loadStatistics()
  } finally {
    adjustLoading.value = false
  }
}

const submitFreeze = async () => {
  if (!currentItem.value) return
  if (freezeForm.amount <= 0) {
    ElMessage.error('请输入冻结金额')
    return
  }
  if (!freezeForm.reason) {
    ElMessage.error('请输入冻结原因')
    return
  }

  freezeLoading.value = true
  try {
    await freezeCreditLimit(currentItem.value.id, {
      amount: freezeForm.amount,
      reason: freezeForm.reason
    })
    ElMessage.success('额度已冻结')
    freezeDialogVisible.value = false
    loadData()
    loadStatistics()
  } finally {
    freezeLoading.value = false
  }
}

const submitUnfreeze = async () => {
  if (!currentItem.value) return
  if (unfreezeForm.amount <= 0) {
    ElMessage.error('请输入解冻金额')
    return
  }
  if (!unfreezeForm.reason) {
    ElMessage.error('请输入解冻原因')
    return
  }

  unfreezeLoading.value = true
  try {
    await unfreezeCreditLimit(currentItem.value.id, {
      amount: unfreezeForm.amount,
      reason: unfreezeForm.reason
    })
    ElMessage.success('额度已解冻')
    unfreezeDialogVisible.value = false
    loadData()
    loadStatistics()
  } finally {
    unfreezeLoading.value = false
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

.stat-value.warning { color: #E6A23C; }
.stat-value.success { color: #67C23A; }
.stat-value.danger { color: #F56C6C; }
.stat-value.primary { color: #409EFF; }

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
  color: #409EFF;
}

.amount.warning { color: #E6A23C; }
.amount.success { color: #67C23A; }
.amount.danger { color: #F56C6C; }

.success { color: #67C23A; }
.danger { color: #F56C6C; }

.mt-20 {
  margin-top: 20px;
}
</style>
