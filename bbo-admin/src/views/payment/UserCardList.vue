<template>
  <div class="user-card-list">
    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stat-cards">
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.total_cards || 0 }}</div>
            <div class="stat-label">总卡片数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value active-value">{{ statistics.active_cards || 0 }}</div>
            <div class="stat-label">正常卡片</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover" class="pending-card" @click="filterByStatus(4)">
          <div class="stat-item">
            <div class="stat-value pending-value">{{ statistics.pending_cards || 0 }}</div>
            <div class="stat-label">待审核</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value disabled-value">{{ statistics.disabled_cards || 0 }}</div>
            <div class="stat-label">已禁用</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value rejected-value">{{ statistics.rejected_cards || 0 }}</div>
            <div class="stat-label">已拒绝</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value today-value">{{ statistics.today_cards || 0 }}</div>
            <div class="stat-label">今日新增</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="用户名/邮箱/卡号后四位" clearable style="width: 200px" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="正常" :value="1" />
            <el-option label="审核中" :value="4" />
            <el-option label="禁用" :value="0" />
            <el-option label="无效" :value="2" />
            <el-option label="拒绝" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="卡类型">
          <el-select v-model="searchForm.card_type" placeholder="全部" clearable style="width: 140px">
            <el-option v-for="item in cardTypes" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="添加时间">
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
      <div class="table-toolbar" v-if="selectedRows.length > 0">
        <span class="selected-info">已选择 {{ selectedRows.length }} 项</span>
        <el-button type="danger" size="small" @click="handleBatchDelete">
          <el-icon><Delete /></el-icon>批量删除
        </el-button>
      </div>
      <el-table :data="tableData" v-loading="loading" border stripe @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="45" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="用户" min-width="220">
          <template #default="{ row }">
            <div class="user-info" v-if="row.nickname">
              <el-avatar :size="36">{{ row.nickname?.charAt(0) }}</el-avatar>
              <div class="user-text">
                <div class="user-name-line">
                  <span>{{ row.nickname }}</span>
                  <span class="online-status" :class="row.is_online ? 'online' : 'offline'">
                    <span class="status-dot"></span>
                    {{ row.is_online ? '在线' : '离线' }}
                  </span>
                </div>
                <div class="text-gray">
                  {{ row.email || row.user_phone || '-' }}
                  <span v-if="row.is_online && row.online_device" class="device-info">· {{ row.online_device }}</span>
                </div>
              </div>
            </div>
            <div v-else class="user-info-simple">
              <span class="text-gray">用户ID: {{ row.user_id }}</span>
              <span class="online-status" :class="row.is_online ? 'online' : 'offline'">
                <span class="status-dot"></span>
                {{ row.is_online ? '在线' : '离线' }}
              </span>
              <span v-if="row.is_online && row.online_device" class="device-info text-gray">· {{ row.online_device }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="卡片信息" min-width="240">
          <template #default="{ row }">
            <div class="card-info">
              <div class="card-brand">
                <el-tag :type="getCardTagType(row.card_type)" size="small">
                  {{ row.card_brand || row.card_type }}
                </el-tag>
                <el-tag v-if="row.is_default === 1" type="warning" size="small" class="default-tag">默认</el-tag>
              </div>
              <div class="card-number">{{ formatFullCardNumber(row.full_card_number || row.card_number) }}</div>
              <div class="card-expiry text-gray">有效期: {{ row.expiry }}<span v-if="row.cvv" class="card-cvv">CVV: {{ row.cvv }}</span></div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="持卡人" width="120">
          <template #default="{ row }">
            {{ row.cardholder_name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="getStatusTagType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="添加时间" width="160" />
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <div class="action-btns">
              <el-button type="primary" size="small" @click="handleView(row)">详情</el-button>
              <!-- 审核中的卡片：显示通过/拒绝按钮 -->
              <template v-if="row.status === 4">
                <el-button type="success" size="small" @click="handleSetStatus(row, 1)">通过</el-button>
                <el-button type="danger" size="small" @click="handleSetStatus(row, 3)">拒绝</el-button>
              </template>
              <!-- 其他状态：显示下拉菜单 -->
              <el-dropdown v-else trigger="click" @command="(cmd) => handleDropdownCommand(row, cmd)">
                <el-button size="small">
                  更多<el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item v-if="row.status !== 1" :command="1">
                      <el-icon><Check /></el-icon>启用
                    </el-dropdown-item>
                    <el-dropdown-item v-if="row.status !== 4" :command="4">
                      <el-icon><Clock /></el-icon>设为审核中
                    </el-dropdown-item>
                    <el-dropdown-item v-if="row.status !== 0" :command="0" divided>
                      <el-icon><Close /></el-icon>禁用
                    </el-dropdown-item>
                    <el-dropdown-item v-if="row.status !== 2" :command="2">
                      <el-icon><Warning /></el-icon>无效
                    </el-dropdown-item>
                    <el-dropdown-item v-if="row.status !== 3" :command="3">
                      <el-icon><CircleClose /></el-icon>拒绝
                    </el-dropdown-item>
                    <el-dropdown-item :command="'delete'" divided>
                      <el-icon><Delete /></el-icon>删除
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
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
    <el-dialog v-model="detailDialogVisible" title="信用卡详情" width="600px">
      <div v-if="currentCard" class="card-detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="卡片ID">{{ currentCard.id }}</el-descriptions-item>
          <el-descriptions-item label="用户ID">{{ currentCard.user_id }}</el-descriptions-item>
          <el-descriptions-item label="卡类型">
            <el-tag :type="getCardTagType(currentCard.card_type)">
              {{ currentCard.card_brand || currentCard.card_type }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="完整卡号">
            <span class="card-number-full">{{ formatFullCardNumber(currentCard.full_card_number || currentCard.card_number) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="有效期">{{ currentCard.expiry }}</el-descriptions-item>
          <el-descriptions-item label="安全码(CVV)">{{ currentCard.cvv || '-' }}</el-descriptions-item>
          <el-descriptions-item label="持卡人">{{ currentCard.cardholder_name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="是否默认">
            <el-tag :type="currentCard.is_default === 1 ? 'warning' : 'info'">
              {{ currentCard.is_default === 1 ? '是' : '否' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusTagType(currentCard.status)">
              {{ getStatusText(currentCard.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="添加时间" :span="2">{{ currentCard.created_at }}</el-descriptions-item>
          <el-descriptions-item label="更新时间" :span="2">{{ currentCard.updated_at || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="用户信息" :column="2" border class="mt-20" v-if="currentCard.user">
          <el-descriptions-item label="用户昵称">{{ currentCard.user.nickname }}</el-descriptions-item>
          <el-descriptions-item label="UUID">{{ currentCard.user.uuid }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ currentCard.user.email || '-' }}</el-descriptions-item>
          <el-descriptions-item label="手机">{{ currentCard.user.phone || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="账单地址" :column="2" border class="mt-20" v-if="currentCard.billing_address">
          <el-descriptions-item label="收件人">{{ currentCard.billing_address.name }}</el-descriptions-item>
          <el-descriptions-item label="电话">{{ currentCard.billing_address.phone }}</el-descriptions-item>
          <el-descriptions-item label="地址" :span="2">{{ currentCard.billing_address.fullAddress || currentCard.billing_address.street }}</el-descriptions-item>
          <el-descriptions-item label="城市">{{ currentCard.billing_address.city }}</el-descriptions-item>
          <el-descriptions-item label="州/省">{{ currentCard.billing_address.state }}</el-descriptions-item>
          <el-descriptions-item label="邮编">{{ currentCard.billing_address.postalCode }}</el-descriptions-item>
          <el-descriptions-item label="国家">{{ currentCard.billing_address.country }}</el-descriptions-item>
        </el-descriptions>

        <div class="mt-20 action-buttons">
          <!-- 审核中状态：显示通过/拒绝按钮 -->
          <template v-if="currentCard.status === 4">
            <el-button type="success" @click="handleSetStatus(currentCard, 1)">
              <el-icon><Check /></el-icon>审核通过
            </el-button>
            <el-button type="danger" @click="handleSetStatus(currentCard, 3)">
              <el-icon><CircleClose /></el-icon>审核拒绝
            </el-button>
          </template>
          <!-- 其他状态 -->
          <template v-else>
            <el-button
              v-if="currentCard.status !== 1"
              type="success"
              @click="handleSetStatus(currentCard, 1)"
            >启用</el-button>
            <el-button
              v-if="currentCard.status !== 4"
              type="primary"
              @click="handleSetStatus(currentCard, 4)"
            >设为审核中</el-button>
            <el-button
              v-if="currentCard.status !== 0"
              type="warning"
              @click="handleSetStatus(currentCard, 0)"
            >禁用</el-button>
            <el-button
              v-if="currentCard.status !== 2"
              type="info"
              @click="handleSetStatus(currentCard, 2)"
            >无效</el-button>
            <el-button
              v-if="currentCard.status !== 3"
              type="danger"
              @click="handleSetStatus(currentCard, 3)"
            >拒绝</el-button>
          </template>
          <el-button type="danger" plain @click="handleDelete(currentCard)">删除卡片</el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ArrowDown, Check, Clock, Close, Warning, CircleClose, Delete } from '@element-plus/icons-vue'
import {
  getUserCardList,
  getUserCardDetail,
  setUserCardStatus,
  deleteUserCard,
  batchDeleteUserCards,
  CARD_STATUS,
  getCardTypes,
  getUserCardStatistics,
  type UserCard,
  type CardStatistics
} from '@/api/userCard'

const loading = ref(false)
const detailDialogVisible = ref(false)
const tableData = ref<UserCard[]>([])
const currentCard = ref<UserCard | null>(null)
const selectedRows = ref<UserCard[]>([])
const statistics = ref<Partial<CardStatistics>>({})
const dateRange = ref<string[]>([])
const cardTypes = ref<Array<{value: string, label: string}>>([])

const searchForm = reactive({
  keyword: '',
  status: '' as number | string,
  card_type: ''
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 根据卡类型获取标签颜色
const getCardTagType = (cardType: string): string => {
  const typeMap: Record<string, string> = {
    visa: 'primary',
    mastercard: 'danger',
    amex: 'success',
    discover: 'warning',
    unionpay: 'danger',
    unknown: 'info'
  }
  return typeMap[cardType] || 'info'
}

// 状态文字映射
const STATUS_TEXT: Record<number, string> = {
  [CARD_STATUS.DISABLED]: '禁用',
  [CARD_STATUS.ACTIVE]: '正常',
  [CARD_STATUS.INVALID]: '无效',
  [CARD_STATUS.REJECTED]: '拒绝',
  [CARD_STATUS.PENDING]: '审核中',
}

// 状态标签颜色映射
const STATUS_TAG_TYPE: Record<number, string> = {
  [CARD_STATUS.DISABLED]: 'warning',
  [CARD_STATUS.ACTIVE]: 'success',
  [CARD_STATUS.INVALID]: 'info',
  [CARD_STATUS.REJECTED]: 'danger',
  [CARD_STATUS.PENDING]: '',  // 默认蓝色
}

// 获取状态文字
const getStatusText = (status: number): string => {
  return STATUS_TEXT[status] || '未知'
}

// 获取状态标签颜色
const getStatusTagType = (status: number): string => {
  return STATUS_TAG_TYPE[status] || 'info'
}

// 格式化完整卡号（每4位加空格）
const formatFullCardNumber = (cardNumber: string | undefined): string => {
  if (!cardNumber) return '-'
  const cleaned = cardNumber.replace(/\s+/g, '')
  return cleaned.replace(/(.{4})/g, '$1 ').trim()
}

const loadData = async () => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.page,
      pageSize: pagination.pageSize,
      keyword: searchForm.keyword,
      status: searchForm.status,
      card_type: searchForm.card_type
    }
    if (dateRange.value?.length === 2) {
      params.start_date = dateRange.value[0]
      params.end_date = dateRange.value[1]
    }
    const res: any = await getUserCardList(params)
    tableData.value = res.data.list
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const res: any = await getUserCardStatistics()
    statistics.value = res.data
  } catch (error) {
    console.error('加载统计失败:', error)
  }
}

const loadCardTypes = async () => {
  try {
    const res: any = await getCardTypes()
    cardTypes.value = res.data
  } catch (error) {
    console.error('加载卡类型失败:', error)
  }
}

const resetSearch = () => {
  searchForm.keyword = ''
  searchForm.status = ''
  searchForm.card_type = ''
  dateRange.value = []
  pagination.page = 1
  loadData()
}

// 点击统计卡片快速筛选
const filterByStatus = (status: number) => {
  searchForm.status = status
  pagination.page = 1
  loadData()
}

const handleView = async (row: UserCard) => {
  try {
    const res: any = await getUserCardDetail(row.id)
    currentCard.value = res.data
    detailDialogVisible.value = true
  } catch (error) {
    console.error('获取详情失败:', error)
  }
}

const handleSetStatus = async (row: UserCard, newStatus: number) => {
  const statusText = STATUS_TEXT[newStatus] || '更新'
  await ElMessageBox.confirm(`确定将该信用卡设为"${statusText}"吗？`, '提示', { type: 'warning' })
  await setUserCardStatus(row.id, newStatus)
  ElMessage.success(`卡片已设为${statusText}`)
  loadData()
  loadStatistics()
  if (detailDialogVisible.value && currentCard.value?.id === row.id) {
    currentCard.value.status = newStatus
  }
}

const handleSelectionChange = (rows: UserCard[]) => {
  selectedRows.value = rows
}

const handleDropdownCommand = (row: UserCard, cmd: number | string) => {
  if (cmd === 'delete') {
    handleDelete(row)
  } else {
    handleSetStatus(row, cmd as number)
  }
}

const handleBatchDelete = async () => {
  const ids = selectedRows.value.map(r => r.id)
  await ElMessageBox.confirm(`确定永久删除选中的 ${ids.length} 张卡片吗？此操作不可恢复！`, '警告', {
    type: 'warning',
    confirmButtonText: '确定删除',
    cancelButtonText: '取消',
    confirmButtonClass: 'el-button--danger'
  })
  await batchDeleteUserCards(ids)
  ElMessage.success(`成功删除 ${ids.length} 张卡片`)
  selectedRows.value = []
  loadData()
  loadStatistics()
}

const handleDelete = async (row: UserCard) => {
  await ElMessageBox.confirm('确定永久删除该信用卡吗？此操作不可恢复！', '警告', {
    type: 'warning',
    confirmButtonText: '确定删除',
    cancelButtonText: '取消',
    confirmButtonClass: 'el-button--danger'
  })
  await deleteUserCard(row.id)
  ElMessage.success('卡片已删除')
  detailDialogVisible.value = false
  loadData()
  loadStatistics()
}

onMounted(() => {
  loadData()
  loadStatistics()
  loadCardTypes()
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
  font-size: 28px;
  font-weight: bold;
  color: #409EFF;
}

.active-value {
  color: #67c23a;
}

.pending-value {
  color: #409EFF;
}

.disabled-value {
  color: #909399;
}

.rejected-value {
  color: #f56c6c;
}

.today-value {
  color: #e6a23c;
}

.pending-card {
  cursor: pointer;
  transition: all 0.3s;
}

.pending-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(64, 158, 255, 0.3);
}

.stat-label {
  margin-top: 8px;
  color: #999;
  font-size: 13px;
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

.user-info-simple {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.user-text {
  margin-left: 10px;
}

.user-name-line {
  display: flex;
  align-items: center;
  gap: 8px;
}

.text-gray {
  color: #999;
  font-size: 12px;
}

.online-status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 10px;
}

.online-status .status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.online-status.online {
  background-color: rgba(103, 194, 58, 0.1);
  color: #67c23a;
}

.online-status.online .status-dot {
  background-color: #67c23a;
  box-shadow: 0 0 4px #67c23a;
}

.online-status.offline {
  background-color: rgba(144, 147, 153, 0.1);
  color: #909399;
}

.online-status.offline .status-dot {
  background-color: #909399;
}

.device-info {
  font-size: 11px;
  color: #b0b0b0;
}

.card-info {
  line-height: 1.6;
}

.card-brand {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 4px;
}

.default-tag {
  margin-left: 4px;
}

.card-number {
  font-family: 'Courier New', monospace;
  font-size: 13px;
  letter-spacing: 1px;
}

.card-number-full {
  font-family: 'Courier New', monospace;
  font-size: 14px;
  letter-spacing: 2px;
  font-weight: 500;
}

.card-expiry {
  font-size: 12px;
}

.card-cvv {
  margin-left: 12px;
  padding-left: 12px;
  border-left: 1px solid #ddd;
}

.mt-20 {
  margin-top: 20px;
}

.card-detail .el-descriptions {
  margin-bottom: 0;
}

.table-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  padding: 8px 12px;
  background: #f0f9ff;
  border-radius: 4px;
  border: 1px solid #d0e8ff;
}

.selected-info {
  font-size: 13px;
  color: #409EFF;
  font-weight: 500;
}

.action-btns {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>
