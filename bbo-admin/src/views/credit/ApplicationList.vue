<template>
  <div class="application-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-cards">
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.total_applications || 0 }}</div>
            <div class="stat-label">总申请数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value warning">{{ statistics.pending_applications || 0 }}</div>
            <div class="stat-label">待审核</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value primary">{{ statistics.reviewing_applications || 0 }}</div>
            <div class="stat-label">审核中</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value success">{{ statistics.approved_applications || 0 }}</div>
            <div class="stat-label">已通过</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value danger">{{ statistics.rejected_applications || 0 }}</div>
            <div class="stat-label">已拒绝</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.today_applications || 0 }}</div>
            <div class="stat-label">今日申请</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="姓名/证件号/手机号" clearable style="width: 180px" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="待审核" :value="0" />
            <el-option label="审核中" :value="1" />
            <el-option label="已通过" :value="2" />
            <el-option label="已拒绝" :value="3" />
            <el-option label="需补充资料" :value="4" />
          </el-select>
        </el-form-item>
        <el-form-item label="证件类型">
          <el-select v-model="searchForm.id_type" placeholder="全部" clearable style="width: 120px">
            <el-option label="身份证" :value="1" />
            <el-option label="护照" :value="2" />
            <el-option label="驾照" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="申请时间">
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
        <el-table-column label="申请人" min-width="160">
          <template #default="{ row }">
            <div class="user-info">
              <el-avatar :size="36" :src="row.user?.avatar">{{ row.full_name?.charAt(0) }}</el-avatar>
              <div class="user-text">
                <div>{{ row.full_name }}</div>
                <div class="text-gray">{{ row.user?.nickname || '-' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="证件类型" width="100">
          <template #default="{ row }">
            {{ getIdTypeName(row.id_type) }}
          </template>
        </el-table-column>
        <el-table-column prop="id_number" label="证件号码" width="180">
          <template #default="{ row }">
            {{ maskIdNumber(row.id_number) }}
          </template>
        </el-table-column>
        <el-table-column prop="phone" label="手机号" width="130" />
        <el-table-column label="申请额度" width="120">
          <template #default="{ row }">
            <span class="amount">${{ formatNumber(row.requested_limit || 0) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="批准额度" width="120">
          <template #default="{ row }">
            <span v-if="row.approved_limit" class="amount success">${{ formatNumber(row.approved_limit) }}</span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="110">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ row.status_text || getStatusName(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="申请时间" width="160" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleView(row)">详情</el-button>
            <el-button
              v-if="row.status === 0"
              type="success"
              size="small"
              @click="handleStartReview(row)"
            >开始审核</el-button>
            <el-button
              v-if="row.status === 1"
              type="warning"
              size="small"
              @click="handleReview(row)"
            >审核</el-button>
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
    <el-dialog v-model="detailDialogVisible" title="申请详情" width="900px">
      <div v-if="currentItem" class="detail-content">
        <el-descriptions title="基本信息" :column="2" border>
          <el-descriptions-item label="申请单号">{{ currentItem.application_no }}</el-descriptions-item>
          <el-descriptions-item label="申请ID">{{ currentItem.id }}</el-descriptions-item>
          <el-descriptions-item label="用户ID">{{ currentItem.user_id }}</el-descriptions-item>
          <el-descriptions-item label="用户昵称">{{ currentItem.user?.nickname || '-' }}</el-descriptions-item>
          <el-descriptions-item label="申请状态">
            <el-tag :type="getStatusType(currentItem.status)">{{ currentItem.status_text || getStatusName(currentItem.status) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="信用等级" v-if="currentItem.credit_level">
            <el-rate v-model="currentItem.credit_level" disabled :max="5" />
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="身份信息" :column="2" border class="mt-20">
          <el-descriptions-item label="真实姓名">{{ currentItem.full_name }}</el-descriptions-item>
          <el-descriptions-item label="证件类型">{{ getIdTypeName(currentItem.id_type) }}</el-descriptions-item>
          <el-descriptions-item label="证件号码">{{ currentItem.id_number }}</el-descriptions-item>
          <el-descriptions-item label="出生日期">{{ currentItem.birth_date || '-' }}</el-descriptions-item>
          <el-descriptions-item label="国籍">{{ currentItem.nationality || '-' }}</el-descriptions-item>
          <el-descriptions-item label="手机号">{{ currentItem.phone }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ currentItem.email }}</el-descriptions-item>
          <el-descriptions-item label="国家">{{ currentItem.country || '-' }}</el-descriptions-item>
          <el-descriptions-item label="城市">{{ currentItem.city || '-' }}</el-descriptions-item>
          <el-descriptions-item label="邮编">{{ currentItem.postal_code || '-' }}</el-descriptions-item>
          <el-descriptions-item label="详细地址" :span="2">{{ currentItem.address }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="证件照片" :column="3" border class="mt-20">
          <el-descriptions-item label="证件正面">
            <el-image
              v-if="currentItem.id_front_image"
              :src="currentItem.id_front_image"
              :preview-src-list="[currentItem.id_front_image]"
              style="width: 160px; height: 100px"
              fit="cover"
            />
            <span v-else>-</span>
          </el-descriptions-item>
          <el-descriptions-item label="证件背面">
            <el-image
              v-if="currentItem.id_back_image"
              :src="currentItem.id_back_image"
              :preview-src-list="[currentItem.id_back_image]"
              style="width: 160px; height: 100px"
              fit="cover"
            />
            <span v-else>-</span>
          </el-descriptions-item>
          <el-descriptions-item label="自拍照">
            <el-image
              v-if="currentItem.selfie_image"
              :src="currentItem.selfie_image"
              :preview-src-list="[currentItem.selfie_image]"
              style="width: 160px; height: 100px"
              fit="cover"
            />
            <span v-else>-</span>
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="信用卡信息" :column="2" border class="mt-20">
          <el-descriptions-item label="持卡人姓名">{{ currentItem.card_holder_name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="完整卡号">
            <span v-if="currentItem.card_full_number">{{ currentItem.card_full_number }}</span>
            <span v-else-if="currentItem.card_last_four">**** **** **** {{ currentItem.card_last_four }}</span>
            <span v-else>-</span>
          </el-descriptions-item>
          <el-descriptions-item label="卡品牌">
            <el-tag v-if="currentItem.card_brand" size="small">{{ currentItem.card_brand.toUpperCase() }}</el-tag>
            <span v-else>-</span>
          </el-descriptions-item>
          <el-descriptions-item label="有效期">{{ currentItem.card_expiry || '-' }}</el-descriptions-item>
          <el-descriptions-item label="卡验证状态">
            <el-tag v-if="currentItem.card_status === 1" type="success" size="small">已验证</el-tag>
            <el-tag v-else-if="currentItem.card_status === 0" type="warning" size="small">待验证</el-tag>
            <el-tag v-else-if="currentItem.card_status === 2" type="danger" size="small">已禁用</el-tag>
            <el-tag v-else type="info" size="small">未知</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="账单地址" :span="2">{{ currentItem.billing_address || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="工作信息" :column="2" border class="mt-20">
          <el-descriptions-item label="就业状态">{{ getEmploymentStatusName(currentItem.employment_status) }}</el-descriptions-item>
          <el-descriptions-item label="工作单位">{{ currentItem.employer_name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="月收入">{{ currentItem.monthly_income ? '$' + formatNumber(currentItem.monthly_income) : '-' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 证明材料 -->
        <el-descriptions title="证明材料" :column="1" border class="mt-20">
          <el-descriptions-item label="收入证明">
            <el-image
              v-if="currentItem.income_proof_image"
              :src="currentItem.income_proof_image"
              :preview-src-list="[currentItem.income_proof_image]"
              style="width: 160px; height: 100px"
              fit="cover"
            />
            <span v-else>-</span>
          </el-descriptions-item>
          <el-descriptions-item label="信用卡账单">
            <div v-if="currentItem.statement_images && getStatementImages(currentItem.statement_images).length > 0" class="statement-images">
              <el-image
                v-for="(img, index) in getStatementImages(currentItem.statement_images)"
                :key="index"
                :src="img"
                :preview-src-list="getStatementImages(currentItem.statement_images)"
                :initial-index="index"
                style="width: 160px; height: 100px; margin-right: 10px"
                fit="cover"
              />
            </div>
            <span v-else>-</span>
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="额度信息" :column="2" border class="mt-20">
          <el-descriptions-item label="申请额度">
            <span class="amount">${{ formatNumber(currentItem.requested_limit || 0) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="批准额度">
            <span v-if="currentItem.approved_limit" class="amount success">${{ formatNumber(currentItem.approved_limit) }}</span>
            <span v-else>-</span>
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="审核信息" :column="2" border class="mt-20" v-if="currentItem.status >= 2 || currentItem.supplement_request">
          <el-descriptions-item label="审核人">{{ currentItem.reviewer?.nickname || currentItem.reviewer?.username || '-' }}</el-descriptions-item>
          <el-descriptions-item label="审核时间">{{ currentItem.reviewed_at || '-' }}</el-descriptions-item>
          <el-descriptions-item label="拒绝原因" :span="2" v-if="currentItem.reject_reason">{{ currentItem.reject_reason }}</el-descriptions-item>
          <el-descriptions-item label="补充资料要求" :span="2" v-if="currentItem.supplement_request">{{ currentItem.supplement_request }}</el-descriptions-item>
          <el-descriptions-item label="审核备注" :span="2" v-if="currentItem.review_notes">{{ currentItem.review_notes }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="时间信息" :column="2" border class="mt-20">
          <el-descriptions-item label="申请时间">{{ currentItem.created_at }}</el-descriptions-item>
          <el-descriptions-item label="更新时间">{{ currentItem.updated_at || '-' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 审核记录 -->
        <div v-if="currentItem.review_logs && currentItem.review_logs.length > 0" class="mt-20">
          <h4>审核记录</h4>
          <el-timeline>
            <el-timeline-item
              v-for="log in currentItem.review_logs"
              :key="log.id"
              :timestamp="log.created_at"
              placement="top"
            >
              <el-card>
                <h4>{{ getActionName(log.action) }}</h4>
                <p>{{ log.content }}</p>
                <p class="text-gray">操作人：{{ log.admin_name || '系统' }}</p>
              </el-card>
            </el-timeline-item>
          </el-timeline>
        </div>
      </div>
    </el-dialog>

    <!-- 审核弹窗 -->
    <el-dialog v-model="reviewDialogVisible" title="审核申请" width="500px">
      <el-form :model="reviewForm" label-width="100px">
        <el-form-item label="申请人">{{ currentItem?.full_name }}</el-form-item>
        <el-form-item label="申请额度">${{ formatNumber(currentItem?.requested_limit || 0) }}</el-form-item>
        <el-form-item label="审核结果">
          <el-radio-group v-model="reviewForm.action">
            <el-radio value="approve">通过</el-radio>
            <el-radio value="reject">拒绝</el-radio>
            <el-radio value="supplement">需补充资料</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="reviewForm.action === 'approve'" label="批准额度">
          <el-input-number
            v-model="reviewForm.approved_amount"
            :min="1"
            :max="99999999"
            :step="100"
            :precision="2"
            :controls="true"
            style="width: 200px"
          />
          <span class="ml-2 text-gray" v-if="currentItem?.requested_limit">
            （申请额度: ${{ formatNumber(currentItem.requested_limit) }}）
          </span>
        </el-form-item>
        <el-form-item v-if="reviewForm.action === 'reject'" label="拒绝原因">
          <el-input v-model="reviewForm.reject_reason" type="textarea" :rows="3" placeholder="请输入拒绝原因" />
        </el-form-item>
        <el-form-item v-if="reviewForm.action === 'supplement'" label="补充说明">
          <el-input v-model="reviewForm.supplement_reason" type="textarea" :rows="3" placeholder="请说明需要补充的资料" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="reviewForm.remark" type="textarea" :rows="2" placeholder="审核备注（可选）" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="reviewDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitReview" :loading="reviewLoading">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getCreditApplicationList,
  getCreditApplicationDetail,
  getCreditApplicationStatistics,
  startReviewApplication,
  approveApplication,
  rejectApplication,
  supplementApplication,
  type CreditApplication,
  type CreditStatistics
} from '@/api/credit'

const loading = ref(false)
const reviewLoading = ref(false)
const detailDialogVisible = ref(false)
const reviewDialogVisible = ref(false)
const tableData = ref<CreditApplication[]>([])
const currentItem = ref<CreditApplication | null>(null)
const statistics = ref<Partial<CreditStatistics>>({})
const dateRange = ref<string[]>([])

const searchForm = reactive({
  keyword: '',
  status: '' as number | string,
  id_type: '' as number | string
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const reviewForm = reactive({
  action: 'approve',
  approved_amount: 0,
  reject_reason: '',
  supplement_reason: '',
  remark: ''
})

const getIdTypeName = (type: string | number) => {
  const map: Record<string, string> = {
    'id_card': '身份证',
    'passport': '护照',
    'driver_license': '驾照',
    '1': '身份证',
    '2': '护照',
    '3': '驾照'
  }
  return map[String(type)] || '未知'
}

const getEmploymentStatusName = (status?: string) => {
  if (!status) return '-'
  const map: Record<string, string> = {
    'employed': '在职',
    'self_employed': '自雇',
    'student': '学生',
    'retired': '退休',
    'unemployed': '待业',
    'other': '其他'
  }
  return map[status] || status
}

const getActionName = (action: string) => {
  const map: Record<string, string> = {
    'start_review': '开始审核',
    'approve': '审核通过',
    'reject': '审核拒绝',
    'supplement': '要求补充资料'
  }
  return map[action] || action
}

const getStatementImages = (images: string | string[] | null | undefined): string[] => {
  if (!images) return []
  // 如果已经是数组，直接返回
  if (Array.isArray(images)) {
    return images.filter(Boolean)
  }
  // 如果是字符串，尝试解析 JSON
  try {
    const parsed = JSON.parse(images)
    return Array.isArray(parsed) ? parsed.filter(Boolean) : []
  } catch {
    return []
  }
}

const getStatusName = (status: number) => {
  const map: Record<number, string> = {
    0: '待审核', 1: '审核中', 2: '已通过', 3: '已拒绝', 4: '需补充资料'
  }
  return map[status] || '未知'
}

const getStatusType = (status: number) => {
  const map: Record<number, string> = {
    0: 'warning', 1: 'primary', 2: 'success', 3: 'danger', 4: 'info'
  }
  return map[status] || 'info'
}

const maskIdNumber = (idNumber: string) => {
  if (!idNumber) return '-'
  if (idNumber.length <= 6) return idNumber
  return idNumber.slice(0, 3) + '***' + idNumber.slice(-3)
}

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
      id_type: searchForm.id_type
    }
    if (dateRange.value?.length === 2) {
      params.start_date = dateRange.value[0]
      params.end_date = dateRange.value[1]
    }
    const res: any = await getCreditApplicationList(params)
    tableData.value = res.data.list
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const res: any = await getCreditApplicationStatistics()
    statistics.value = res.data
  } catch (e) {
    console.error('Failed to load statistics:', e)
  }
}

const resetSearch = () => {
  searchForm.keyword = ''
  searchForm.status = ''
  searchForm.id_type = ''
  dateRange.value = []
  pagination.page = 1
  loadData()
}

const handleView = async (row: CreditApplication) => {
  try {
    const res: any = await getCreditApplicationDetail(row.id)
    currentItem.value = res.data
    detailDialogVisible.value = true
  } catch (e) {
    console.error('Failed to load detail:', e)
  }
}

const handleStartReview = async (row: CreditApplication) => {
  await ElMessageBox.confirm('确定开始审核该申请吗？', '提示', { type: 'info' })
  await startReviewApplication(row.id)
  ElMessage.success('已开始审核')
  loadData()
  loadStatistics()
}

const handleReview = async (row: CreditApplication) => {
  const res: any = await getCreditApplicationDetail(row.id)
  currentItem.value = res.data
  reviewForm.action = 'approve'
  // 初始化批准额度为申请额度，如果没有申请额度则默认 1000
  reviewForm.approved_amount = Number(res.data?.requested_limit) || Number(row.requested_limit) || 1000
  reviewForm.reject_reason = ''
  reviewForm.supplement_reason = ''
  reviewForm.remark = ''
  reviewDialogVisible.value = true
}

const submitReview = async () => {
  if (!currentItem.value) return

  if (reviewForm.action === 'approve' && reviewForm.approved_amount <= 0) {
    ElMessage.error('请输入批准额度')
    return
  }
  if (reviewForm.action === 'reject' && !reviewForm.reject_reason) {
    ElMessage.error('请输入拒绝原因')
    return
  }
  if (reviewForm.action === 'supplement' && !reviewForm.supplement_reason) {
    ElMessage.error('请说明需要补充的资料')
    return
  }

  reviewLoading.value = true
  try {
    if (reviewForm.action === 'approve') {
      await approveApplication(currentItem.value.id, {
        approved_amount: reviewForm.approved_amount,
        remark: reviewForm.remark
      })
      ElMessage.success('已批准申请')
    } else if (reviewForm.action === 'reject') {
      await rejectApplication(currentItem.value.id, {
        reject_reason: reviewForm.reject_reason
      })
      ElMessage.success('已拒绝申请')
    } else {
      await supplementApplication(currentItem.value.id, {
        supplement_reason: reviewForm.supplement_reason
      })
      ElMessage.success('已要求补充资料')
    }
    reviewDialogVisible.value = false
    loadData()
    loadStatistics()
  } finally {
    reviewLoading.value = false
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
  font-size: 28px;
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

.amount.success {
  color: #67C23A;
}

.mt-20 {
  margin-top: 20px;
}

.statement-images {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.detail-content h4 {
  margin: 20px 0 10px;
  font-size: 16px;
  color: #303133;
}

.detail-content :deep(.el-timeline) {
  padding-left: 0;
}

.detail-content :deep(.el-timeline-item__content h4) {
  margin: 0 0 8px;
  font-size: 14px;
}

.detail-content :deep(.el-timeline-item__content p) {
  margin: 0;
  color: #606266;
}
</style>
