<template>
  <div class="return-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-cards">
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.total }}</div>
            <div class="stat-label">总申请数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-warning">{{ statistics.pending }}</div>
            <div class="stat-label">待处理</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-primary">{{ statistics.approved }}</div>
            <div class="stat-label">已同意</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-danger">{{ statistics.rejected }}</div>
            <div class="stat-label">已拒绝</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-success">{{ statistics.completed }}</div>
            <div class="stat-label">已完成</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-info">{{ statistics.today }}</div>
            <div class="stat-label">今日新增</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索表单 -->
    <el-card class="search-card" shadow="never">
      <el-form :inline="true" :model="searchForm" @submit.prevent="handleSearch">
        <el-form-item label="退货单号">
          <el-input v-model="searchForm.return_no" placeholder="请输入退货单号" clearable />
        </el-form-item>
        <el-form-item label="订单号">
          <el-input v-model="searchForm.order_no" placeholder="请输入订单号" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部状态" clearable style="width: 140px">
            <el-option label="待处理" :value="0" />
            <el-option label="已同意" :value="1" />
            <el-option label="已拒绝" :value="2" />
            <el-option label="已取消" :value="3" />
            <el-option label="退货中" :value="4" />
            <el-option label="已完成" :value="5" />
          </el-select>
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="searchForm.type" placeholder="全部类型" clearable style="width: 120px">
            <el-option label="仅退款" :value="1" />
            <el-option label="退货退款" :value="2" />
          </el-select>
        </el-form-item>
        <el-form-item label="申请时间">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            style="width: 240px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 批量操作 -->
    <el-card class="batch-card" v-if="selectedIds.length > 0">
      <span>已选择 {{ selectedIds.length }} 项</span>
      <el-button type="danger" size="small" @click="handleBatchDelete">批量删除</el-button>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card" shadow="never">
      <el-table
        v-loading="loading"
        :data="tableData"
        stripe
        style="width: 100%"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="return_no" label="退货单号" width="180">
          <template #default="{ row }">
            <span class="link-text" @click="handleView(row)">{{ row.return_no }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="order_no" label="订单号" width="180" />
        <el-table-column label="商品" min-width="200">
          <template #default="{ row }">
            <div class="goods-cell">
              <el-image
                :src="row.goods_snapshot?.cover_image"
                fit="cover"
                class="goods-image"
                :preview-src-list="[row.goods_snapshot?.cover_image]"
              />
              <div class="goods-info">
                <div class="goods-title">{{ row.goods_snapshot?.title }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="买家" width="120">
          <template #default="{ row }">
            <span>{{ row.buyer?.nickname || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="卖家" width="120">
          <template #default="{ row }">
            <span>{{ row.seller?.nickname || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="退款金额" width="120">
          <template #default="{ row }">
            <span class="price">{{ row.currency }} {{ row.refund_amount }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="type_text" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type === 1 ? 'warning' : 'primary'" size="small">
              {{ row.type_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="原因" width="120">
          <template #default="{ row }">
            <el-tooltip :content="row.reason_detail || getReasonText(row.reason)" placement="top">
              <span class="ellipsis">{{ getReasonText(row.reason) }}</span>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column prop="status_text" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">
              {{ row.status_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="申请时间" width="160" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleView(row)">详情</el-button>
            <template v-if="row.status === 0">
              <el-button type="success" link size="small" @click="handleApprove(row)">同意</el-button>
              <el-button type="danger" link size="small" @click="handleReject(row)">拒绝</el-button>
            </template>
            <template v-if="row.status === 4">
              <el-button type="info" link size="small" @click="handleReceive(row)">确认收货</el-button>
            </template>
            <template v-if="row.status === 1 || (row.status === 4 && row.received_at)">
              <el-button type="warning" link size="small" @click="handleRefund(row)">退款</el-button>
            </template>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="退货详情" width="800px" destroy-on-close>
      <el-descriptions :column="2" border v-if="currentReturn">
        <el-descriptions-item label="退货单号">{{ currentReturn.return_no }}</el-descriptions-item>
        <el-descriptions-item label="订单号">{{ currentReturn.order_no }}</el-descriptions-item>
        <el-descriptions-item label="退货类型">
          <el-tag :type="currentReturn.type === 1 ? 'warning' : 'primary'" size="small">
            {{ currentReturn.type_text }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="getStatusType(currentReturn.status)" size="small">
            {{ currentReturn.status_text }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="退款金额">
          <span class="price">{{ currentReturn.currency }} {{ currentReturn.refund_amount }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="申请时间">{{ currentReturn.created_at }}</el-descriptions-item>
        <el-descriptions-item label="买家">{{ currentReturn.buyer?.nickname || '-' }}</el-descriptions-item>
        <el-descriptions-item label="卖家">{{ currentReturn.seller?.nickname || '-' }}</el-descriptions-item>
        <el-descriptions-item label="退货原因" :span="2">{{ getReasonText(currentReturn.reason) }}</el-descriptions-item>
        <el-descriptions-item label="详细说明" :span="2">{{ currentReturn.reason_detail || '-' }}</el-descriptions-item>
        <el-descriptions-item v-if="currentReturn.reject_reason" label="拒绝原因" :span="2">
          <span class="text-danger">{{ currentReturn.reject_reason }}</span>
        </el-descriptions-item>
        <el-descriptions-item v-if="currentReturn.return_tracking_no" label="退货物流单号">
          {{ currentReturn.return_tracking_no }}
        </el-descriptions-item>
        <el-descriptions-item v-if="currentReturn.return_carrier" label="退货快递">
          {{ currentReturn.return_carrier }}
        </el-descriptions-item>
        <el-descriptions-item v-if="currentReturn.shipped_at" label="退货时间">
          {{ currentReturn.shipped_at }}
        </el-descriptions-item>
        <el-descriptions-item v-if="currentReturn.received_at" label="收货时间">
          {{ currentReturn.received_at }}
        </el-descriptions-item>
        <el-descriptions-item v-if="currentReturn.refunded_at" label="退款时间">
          {{ currentReturn.refunded_at }}
        </el-descriptions-item>
        <el-descriptions-item v-if="currentReturn.admin_remark" label="管理员备注" :span="2">
          {{ currentReturn.admin_remark }}
        </el-descriptions-item>
      </el-descriptions>

      <!-- 商品信息 -->
      <div class="section-title">商品信息</div>
      <div class="goods-detail" v-if="currentReturn?.goods_snapshot">
        <el-image
          :src="currentReturn.goods_snapshot.cover_image"
          fit="cover"
          class="goods-detail-image"
          :preview-src-list="[currentReturn.goods_snapshot.cover_image]"
        />
        <div class="goods-detail-info">
          <div class="goods-detail-title">{{ currentReturn.goods_snapshot.title }}</div>
          <div class="goods-detail-price">
            {{ currentReturn.currency }} {{ currentReturn.goods_snapshot.price }}
          </div>
        </div>
      </div>

      <!-- 图片凭证 -->
      <div class="section-title" v-if="currentReturn?.images?.length">上传凭证</div>
      <div class="image-list" v-if="currentReturn?.images?.length">
        <el-image
          v-for="(img, index) in currentReturn.images"
          :key="index"
          :src="img"
          fit="cover"
          class="evidence-image"
          :preview-src-list="currentReturn.images"
          :initial-index="index"
        />
      </div>

      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
        <template v-if="currentReturn?.status === 0">
          <el-button type="success" @click="handleApprove(currentReturn)">同意退货</el-button>
          <el-button type="danger" @click="handleReject(currentReturn)">拒绝退货</el-button>
        </template>
        <template v-if="currentReturn?.status === 4">
          <el-button type="info" @click="handleReceive(currentReturn)">确认收货</el-button>
        </template>
        <template v-if="currentReturn?.status === 1 || (currentReturn?.status === 4 && currentReturn?.received_at)">
          <el-button type="warning" @click="handleRefund(currentReturn)">执行退款</el-button>
        </template>
      </template>
    </el-dialog>

    <!-- 拒绝弹窗 -->
    <el-dialog v-model="rejectVisible" title="拒绝退货" width="500px" destroy-on-close>
      <el-form :model="rejectForm" label-width="100px">
        <el-form-item label="拒绝原因" required>
          <el-input
            v-model="rejectForm.reason"
            type="textarea"
            :rows="3"
            placeholder="请输入拒绝原因"
          />
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="rejectForm.remark"
            type="textarea"
            :rows="2"
            placeholder="内部备注（可选）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectVisible = false">取消</el-button>
        <el-button type="danger" :loading="submitting" @click="confirmReject">确认拒绝</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getReturnList,
  getReturnDetail,
  getReturnStatistics,
  approveReturn,
  rejectReturn,
  confirmReceive,
  processRefund,
  deleteReturn,
  batchDeleteReturns,
  type ReturnInfo
} from '@/api/return'

// 加载状态
const loading = ref(false)
const submitting = ref(false)

// 统计数据
const statistics = reactive({
  total: 0,
  pending: 0,
  approved: 0,
  rejected: 0,
  completed: 0,
  today: 0
})

// 搜索表单
const searchForm = reactive({
  return_no: '',
  order_no: '',
  status: '' as number | string,
  type: '' as number | string
})
const dateRange = ref<string[]>([])

// 分页
const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 表格数据
const tableData = ref<ReturnInfo[]>([])

// 详情弹窗
const detailVisible = ref(false)
const currentReturn = ref<ReturnInfo | null>(null)

// 拒绝弹窗
const rejectVisible = ref(false)
const rejectForm = reactive({
  reason: '',
  remark: ''
})
const rejectTargetId = ref<number | null>(null)

// 获取状态标签类型
function getStatusType(status: number): string {
  const typeMap: Record<number, string> = {
    0: 'warning',   // 待处理
    1: 'primary',   // 已同意
    2: 'danger',    // 已拒绝
    3: 'info',      // 已取消
    4: 'info',      // 退货中
    5: 'success',   // 已完成
  }
  return typeMap[status] || 'info'
}

// 退货原因中文映射
const reasonMap: Record<string, string> = {
  'not_as_described': '商品与描述不符',
  'wrong_item': '收到错误商品',
  'damaged': '商品损坏/有缺陷',
  'missing_parts': '缺少配件/配件不全',
  'quality_issue': '质量问题',
  'not_needed': '不再需要',
  'better_price': '找到更优惠价格',
  'other': '其他原因',
  // 兼容英文原因文本
  'Item not as described': '商品与描述不符',
  'Wrong item received': '收到错误商品',
  'Item damaged/defective': '商品损坏/有缺陷',
  'Missing parts/accessories': '缺少配件/配件不全',
  'Quality issue': '质量问题',
  'No longer needed': '不再需要',
  'Found better price': '找到更优惠价格',
  'Other': '其他原因',
}

// 获取退货原因中文文本
function getReasonText(reason: string): string {
  return reasonMap[reason] || reason
}

// 加载统计数据
async function loadStatistics() {
  try {
    const res: any = await getReturnStatistics()
    if (res.data) {
      Object.assign(statistics, res.data)
    }
  } catch (e) {
    console.error('Failed to load statistics:', e)
  }
}

// 加载列表数据
async function loadData() {
  loading.value = true
  try {
    const params: any = {
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchForm
    }

    // 处理日期范围
    if (dateRange.value?.length === 2) {
      params.start_date = dateRange.value[0]
      params.end_date = dateRange.value[1]
    }

    // 移除空值
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null || params[key] === undefined) {
        delete params[key]
      }
    })

    const res: any = await getReturnList(params)
    tableData.value = res.data?.list || []
    pagination.total = res.data?.total || 0
  } catch (e) {
    console.error('Failed to load data:', e)
  } finally {
    loading.value = false
  }
}

// 搜索
function handleSearch() {
  pagination.page = 1
  loadData()
}

// 重置
function handleReset() {
  searchForm.return_no = ''
  searchForm.order_no = ''
  searchForm.status = ''
  searchForm.type = ''
  dateRange.value = []
  pagination.page = 1
  loadData()
}

// 分页变化
function handleSizeChange() {
  pagination.page = 1
  loadData()
}

function handlePageChange() {
  loadData()
}

// 查看详情
async function handleView(row: ReturnInfo) {
  try {
    const res: any = await getReturnDetail(row.id)
    currentReturn.value = res.data
    detailVisible.value = true
  } catch (e) {
    console.error('Failed to load detail:', e)
  }
}

// 同意退货
async function handleApprove(row: ReturnInfo) {
  try {
    await ElMessageBox.confirm(
      `确定同意退货申请 ${row.return_no} 吗？`,
      '同意退货',
      { type: 'warning' }
    )

    await approveReturn(row.id)
    ElMessage.success('已同意退货申请')
    loadData()
    loadStatistics()
    detailVisible.value = false
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e.message || '操作失败')
    }
  }
}

// 打开拒绝弹窗
function handleReject(row: ReturnInfo) {
  rejectTargetId.value = row.id
  rejectForm.reason = ''
  rejectForm.remark = ''
  rejectVisible.value = true
}

// 确认拒绝
async function confirmReject() {
  if (!rejectForm.reason.trim()) {
    ElMessage.warning('请输入拒绝原因')
    return
  }

  submitting.value = true
  try {
    await rejectReturn(rejectTargetId.value!, {
      reason: rejectForm.reason,
      remark: rejectForm.remark
    })
    ElMessage.success('已拒绝退货申请')
    rejectVisible.value = false
    detailVisible.value = false
    loadData()
    loadStatistics()
  } catch (e: any) {
    ElMessage.error(e.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

// 确认收货
async function handleReceive(row: ReturnInfo) {
  try {
    await ElMessageBox.confirm(
      `确定已收到退货商品吗？`,
      '确认收货',
      { type: 'warning' }
    )

    await confirmReceive(row.id)
    ElMessage.success('已确认收货')
    loadData()
    loadStatistics()
    detailVisible.value = false
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e.message || '操作失败')
    }
  }
}

// 执行退款
async function handleRefund(row: ReturnInfo) {
  try {
    await ElMessageBox.confirm(
      `确定执行退款 ${row.currency} ${row.refund_amount} 给买家吗？`,
      '执行退款',
      { type: 'warning' }
    )

    await processRefund(row.id)
    ElMessage.success('退款成功')
    loadData()
    loadStatistics()
    detailVisible.value = false
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e.message || '操作失败')
    }
  }
}

// 选择相关
const selectedIds = ref<number[]>([])

function handleSelectionChange(rows: ReturnInfo[]) {
  selectedIds.value = rows.map(r => r.id)
}

// 删除单条
async function handleDelete(row: ReturnInfo) {
  try {
    await ElMessageBox.confirm(`确定删除退货单 ${row.return_no} 吗？`, '警告', { type: 'warning' })
    await deleteReturn(row.id)
    ElMessage.success('删除成功')
    loadData()
    loadStatistics()
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e.message || '删除失败')
    }
  }
}

// 批量删除
async function handleBatchDelete() {
  try {
    await ElMessageBox.confirm(`确定批量删除 ${selectedIds.value.length} 条退货记录吗？删除后无法恢复`, '警告', { type: 'warning' })
    const res: any = await batchDeleteReturns(selectedIds.value)
    ElMessage.success(res.msg || '删除成功')
    loadData()
    loadStatistics()
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e.message || '删除失败')
    }
  }
}

// 初始化
onMounted(() => {
  loadData()
  loadStatistics()
})
</script>

<style scoped>
.return-list {
  padding: 20px;
}

.stat-cards {
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
  padding: 10px 0;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #409EFF;
}

.stat-label {
  font-size: 14px;
  color: #999;
  margin-top: 5px;
}

.text-warning {
  color: #E6A23C !important;
}

.text-primary {
  color: #409EFF !important;
}

.text-danger {
  color: #F56C6C !important;
}

.text-success {
  color: #67C23A !important;
}

.text-info {
  color: #909399 !important;
}

.search-card {
  margin-bottom: 20px;
}

.batch-card {
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.table-card {
  min-height: 400px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.link-text {
  color: #409EFF;
  cursor: pointer;
}

.link-text:hover {
  text-decoration: underline;
}

.goods-cell {
  display: flex;
  align-items: center;
}

.goods-image {
  width: 50px;
  height: 50px;
  border-radius: 4px;
  margin-right: 10px;
  flex-shrink: 0;
}

.goods-info {
  min-width: 0;
}

.goods-title {
  font-size: 13px;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.price {
  color: #F56C6C;
  font-weight: bold;
}

.ellipsis {
  display: inline-block;
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.section-title {
  font-size: 14px;
  font-weight: bold;
  color: #333;
  margin: 20px 0 10px;
  padding-left: 10px;
  border-left: 3px solid #409EFF;
}

.goods-detail {
  display: flex;
  align-items: center;
  padding: 15px;
  background: #f5f7fa;
  border-radius: 4px;
}

.goods-detail-image {
  width: 80px;
  height: 80px;
  border-radius: 4px;
  margin-right: 15px;
}

.goods-detail-info {
  flex: 1;
}

.goods-detail-title {
  font-size: 14px;
  color: #333;
  margin-bottom: 8px;
}

.goods-detail-price {
  font-size: 16px;
  color: #F56C6C;
  font-weight: bold;
}

.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.evidence-image {
  width: 100px;
  height: 100px;
  border-radius: 4px;
  cursor: pointer;
}
</style>
