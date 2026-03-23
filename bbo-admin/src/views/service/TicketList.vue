<template>
  <div class="ticket-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-cards">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card pending">
          <div class="stat-value">{{ statistics.pending }}</div>
          <div class="stat-label">待处理</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card processing">
          <div class="stat-value">{{ statistics.processing }}</div>
          <div class="stat-label">处理中</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card replied">
          <div class="stat-value">{{ statistics.replied }}</div>
          <div class="stat-label">已回复</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card today">
          <div class="stat-value">{{ statistics.today_new }}</div>
          <div class="stat-label">今日新增</div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="工单号/标题" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="待处理" :value="0" />
            <el-option label="处理中" :value="1" />
            <el-option label="已回复" :value="2" />
            <el-option label="已解决" :value="3" />
            <el-option label="已关闭" :value="4" />
          </el-select>
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="searchForm.category" placeholder="全部" clearable style="width: 120px">
            <el-option
              v-for="cat in categories"
              :key="cat.key"
              :label="cat.name"
              :value="cat.key"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="优先级">
          <el-select v-model="searchForm.priority" placeholder="全部" clearable style="width: 100px">
            <el-option label="普通" :value="1" />
            <el-option label="紧急" :value="2" />
            <el-option label="非常紧急" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>
            搜索
          </el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格 -->
    <el-card>
      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="ticket_no" label="工单号" width="250" />
        <el-table-column prop="subject" label="标题" min-width="250" show-overflow-tooltip />
        <el-table-column label="用户" width="100">
          <template #default="{ row }">
            {{ row.user?.nickname || row.user?.phone || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="分类" width="90">
          <template #default="{ row }">
            <el-tag size="small">{{ row.category_text }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="优先级" width="90">
          <template #default="{ row }">
            <el-tag :type="getPriorityType(row.priority)" size="small">
              {{ row.priority_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">
              {{ row.status_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="处理客服" width="90">
          <template #default="{ row }">
            {{ row.service?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="160" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleView(row)">查看</el-button>
            <el-button
              v-if="row.status < 3"
              type="success" link size="small"
              @click="handleResolve(row)"
            >解决</el-button>
            <el-button
              v-if="row.status < 4"
              type="danger" link size="small"
              @click="handleClose(row)"
            >关闭</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="pagination.total"
        layout="total, sizes, prev, pager, next, jumper"
        class="pagination"
        @size-change="loadData"
        @current-change="loadData"
      />
    </el-card>

    <!-- 工单详情对话框 -->
    <el-dialog v-model="detailVisible" title="工单详情" width="800px">
      <div v-if="currentTicket" class="ticket-detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="工单号">{{ currentTicket.ticket_no }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusType(currentTicket.status)" size="small">
              {{ currentTicket.status_text }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="用户">
            {{ currentTicket.user?.nickname || currentTicket.user?.phone }}
          </el-descriptions-item>
          <el-descriptions-item label="分类">{{ currentTicket.category_text }}</el-descriptions-item>
          <el-descriptions-item label="优先级">
            <el-tag :type="getPriorityType(currentTicket.priority)" size="small">
              {{ currentTicket.priority_text }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="处理客服">
            {{ currentTicket.service?.name || '未分配' }}
          </el-descriptions-item>
          <el-descriptions-item label="创建时间">{{ currentTicket.created_at }}</el-descriptions-item>
          <el-descriptions-item label="首次回复">{{ currentTicket.first_reply_at || '-' }}</el-descriptions-item>
          <el-descriptions-item label="标题" :span="2">{{ currentTicket.subject }}</el-descriptions-item>
          <el-descriptions-item v-if="currentTicket.sub_category" label="子分类">
            {{ currentTicket.sub_category_text || currentTicket.sub_category }}
          </el-descriptions-item>
        </el-descriptions>

        <!-- 举报工单特殊显示 -->
        <div v-if="isReportTicket" class="report-section">
          <h4>举报信息</h4>
          <el-descriptions :column="2" border>
            <el-descriptions-item label="举报类型">
              <el-tag :type="reportInfo.type === 'item' ? 'warning' : 'danger'" size="small">
                {{ reportInfo.type === 'item' ? '商品/刊登物品' : '会员' }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="举报原因">
              <el-tag type="danger" size="small">{{ reportInfo.reasonText }}</el-tag>
            </el-descriptions-item>
            <el-descriptions-item v-if="reportInfo.memberAccount" label="涉及会员帐号">
              {{ reportInfo.memberAccount }}
            </el-descriptions-item>
            <el-descriptions-item v-if="reportInfo.itemNumber" label="涉及物品编号">
              {{ reportInfo.itemNumber }}
            </el-descriptions-item>
            <el-descriptions-item v-if="reportInfo.otherItems" label="其他物品编号" :span="2">
              {{ reportInfo.otherItems }}
            </el-descriptions-item>
            <el-descriptions-item v-if="reportInfo.details" label="详细描述" :span="2">
              <div class="ticket-content">{{ reportInfo.details }}</div>
            </el-descriptions-item>
          </el-descriptions>
        </div>

        <!-- 普通工单问题描述 -->
        <div v-else class="content-section">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="问题描述">
              <div class="ticket-content">{{ currentTicket.content }}</div>
            </el-descriptions-item>
          </el-descriptions>
        </div>

        <!-- 用户上传的图片 -->
        <div class="images-section" v-if="currentTicket.images?.length">
          <h4>附件图片</h4>
          <div class="image-list">
            <el-image
              v-for="(img, index) in currentTicket.images"
              :key="index"
              :src="img"
              :preview-src-list="currentTicket.images"
              :initial-index="index"
              fit="cover"
              class="ticket-image"
            />
          </div>
        </div>

        <!-- 关联订单 -->
        <div class="order-section" v-if="ticketRelatedOrder">
          <h4>关联订单</h4>
          <el-card shadow="never" class="order-card">
            <div class="order-info">
              <div class="order-goods" v-if="ticketRelatedOrder.goods_snapshot">
                <el-image
                  :src="ticketRelatedOrder.goods_snapshot.cover_image || ticketRelatedOrder.goods_snapshot.image"
                  fit="cover"
                  class="goods-image"
                />
                <div class="goods-detail">
                  <div class="goods-title">{{ ticketRelatedOrder.goods_snapshot.title }}</div>
                  <div class="goods-price">{{ ticketRelatedOrder.currency }} {{ ticketRelatedOrder.goods_snapshot.user_price || ticketRelatedOrder.goods_snapshot.price }}</div>
                </div>
              </div>
              <div class="order-meta">
                <div class="meta-item">
                  <span class="meta-label">订单号：</span>
                  <span class="meta-value">{{ ticketRelatedOrder.order_no }}</span>
                </div>
                <div class="meta-item">
                  <span class="meta-label">订单金额：</span>
                  <span class="meta-value">{{ ticketRelatedOrder.currency }} {{ ticketRelatedOrder.total_amount }}</span>
                </div>
                <div class="meta-item" v-if="ticketRelatedOrder.status_text">
                  <span class="meta-label">订单状态：</span>
                  <el-tag size="small">{{ ticketRelatedOrder.status_text }}</el-tag>
                </div>
                <div class="meta-item" v-if="ticketRelatedOrder.created_at">
                  <span class="meta-label">下单时间：</span>
                  <span class="meta-value">{{ ticketRelatedOrder.created_at }}</span>
                </div>
              </div>
            </div>
          </el-card>
        </div>

        <!-- 关联商品（如果没有关联订单但有关联商品） -->
        <div class="goods-section" v-if="!ticketRelatedOrder && ticketGoods">
          <h4>关联商品</h4>
          <el-card shadow="never" class="goods-card">
            <div class="order-goods">
              <el-image
                :src="ticketGoods.cover_image"
                fit="cover"
                class="goods-image"
              />
              <div class="goods-detail">
                <div class="goods-title">{{ ticketGoods.title }}</div>
                <div class="goods-price">{{ ticketGoods.price }}</div>
              </div>
            </div>
          </el-card>
        </div>

        <!-- 回复列表 -->
        <div class="replies-section" v-if="currentTicket.replies?.length">
          <h4>回复记录</h4>
          <div class="reply-list">
            <div
              v-for="reply in currentTicket.replies"
              :key="reply.id"
              :class="['reply-item', reply.from_type === 2 ? 'from-service' : 'from-user']"
            >
              <div class="reply-header">
                <span class="reply-from">
                  {{ reply.from_type === 1 ? '用户' : reply.from_type === 2 ? '客服' : '系统' }}
                </span>
                <span class="reply-time">{{ reply.created_at }}</span>
                <el-tag v-if="reply.is_internal" type="info" size="small">内部备注</el-tag>
              </div>
              <div class="reply-content">{{ reply.content }}</div>
            </div>
          </div>
        </div>

        <!-- 回复表单 -->
        <div class="reply-form" v-if="currentTicket.status < 4">
          <el-divider>回复工单</el-divider>
          <el-form>
            <el-form-item>
              <el-input
                v-model="replyContent"
                type="textarea"
                :rows="4"
                placeholder="请输入回复内容"
              />
            </el-form-item>
            <el-form-item>
              <el-checkbox v-model="isInternal">内部备注（用户不可见）</el-checkbox>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="handleReply" :loading="replying">
                发送回复
              </el-button>
            </el-form-item>
          </el-form>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import {
  getTicketList,
  getTicket,
  getTicketStatistics,
  getTicketCategories,
  replyTicket,
  resolveTicket,
  closeTicket,
  type SupportTicket,
  type TicketStatistics
} from '@/api/supportTicket'

const loading = ref(false)
const tableData = ref<SupportTicket[]>([])
const categories = ref<{ key: string; name: string }[]>([])

const statistics = reactive<TicketStatistics>({
  pending: 0,
  processing: 0,
  replied: 0,
  resolved: 0,
  today_new: 0,
  today_resolved: 0,
  avg_first_reply_minutes: 0
})

const searchForm = reactive({
  keyword: '',
  status: '',
  category: '',
  priority: ''
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 详情对话框
const detailVisible = ref(false)
const currentTicket = ref<SupportTicket | null>(null)
const replyContent = ref('')
const isInternal = ref(false)
const replying = ref(false)

// 兼容驼峰和下划线格式的关联订单
const ticketRelatedOrder = computed(() => {
  if (!currentTicket.value) return null
  // ThinkPHP 可能返回 relatedOrder (驼峰) 或 related_order (下划线)
  return (currentTicket.value as any).relatedOrder || currentTicket.value.related_order || null
})

// 兼容驼峰和下划线格式的关联商品
const ticketGoods = computed(() => {
  if (!currentTicket.value) return null
  return currentTicket.value.goods || null
})

// 是否为举报工单
const isReportTicket = computed(() => {
  return currentTicket.value?.category === 'report'
})

// 解析举报信息
const reportInfo = computed(() => {
  if (!currentTicket.value || currentTicket.value.category !== 'report') {
    return {
      type: 'item',
      memberAccount: '',
      itemNumber: '',
      otherItems: '',
      reason: '',
      reasonText: '',
      details: ''
    }
  }

  const content = currentTicket.value.content || ''
  const lines = content.split('\n')

  // 解析结构化内容
  const info: Record<string, string> = {}
  lines.forEach(line => {
    const colonIndex = line.indexOf(':')
    if (colonIndex > -1) {
      const key = line.substring(0, colonIndex).trim()
      const value = line.substring(colonIndex + 1).trim()
      info[key] = value
    }
  })

  // 判断举报类型
  let type = 'item'
  const typeValue = info['你想檢舉什麼？'] || info['What would you like to report?'] || info['何を報告しますか？'] || ''
  if (typeValue.includes('會員') || typeValue.includes('Member') || typeValue.includes('メンバー')) {
    type = 'member'
  }

  // 获取举报原因（从后端返回的 sub_category_text 字段获取）
  const reason = currentTicket.value.sub_category || ''
  const reasonText = currentTicket.value.sub_category_text || reason

  return {
    type,
    memberAccount: info['請提供與該內容有關的會員帳號'] || info['Member account related to this content'] || info['関連するメンバーアカウント'] || '',
    itemNumber: info['請提供與該內容有關的物品編號'] || info['Item number related to this content'] || info['関連する商品番号'] || '',
    otherItems: info['其他物品編號'] || info['Other item numbers'] || info['その他の商品番号'] || '',
    reason,
    reasonText,
    details: info['請提供更多詳細資料'] || info['Please provide more details'] || info['詳細を入力してください'] || ''
  }
})

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const res: any = await getTicketList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchForm
    })
    tableData.value = res.data.list
    pagination.total = res.data.total
  } catch (error) {
    console.error('加载工单列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 加载统计
const loadStatistics = async () => {
  try {
    const res: any = await getTicketStatistics()
    Object.assign(statistics, res.data)
  } catch (error) {
    console.error('加载统计失败:', error)
  }
}

// 加载分类
const loadCategories = async () => {
  try {
    const res: any = await getTicketCategories()
    categories.value = res.data
  } catch (error) {
    console.error('加载分类失败:', error)
  }
}

// 获取状态类型
const getStatusType = (status: number) => {
  const types: Record<number, string> = {
    0: 'danger',
    1: 'warning',
    2: 'primary',
    3: 'success',
    4: 'info'
  }
  return types[status] || 'info'
}

// 获取优先级类型
const getPriorityType = (priority: number): "primary" | "success" | "info" | "warning" | "danger" => {
  const types: Record<number, "primary" | "success" | "info" | "warning" | "danger"> = {
    1: 'info',
    2: 'warning',
    3: 'danger'
  }
  return types[priority] || 'info'
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  loadData()
}

// 重置
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.status = ''
  searchForm.category = ''
  searchForm.priority = ''
  handleSearch()
}

// 查看详情
const handleView = async (row: SupportTicket) => {
  try {
    const res: any = await getTicket(row.id)
    currentTicket.value = res.data
    replyContent.value = ''
    isInternal.value = false
    detailVisible.value = true
  } catch (error) {
    console.error('加载工单详情失败:', error)
  }
}

// 回复
const handleReply = async () => {
  if (!replyContent.value.trim()) {
    ElMessage.warning('请输入回复内容')
    return
  }
  if (!currentTicket.value) return

  replying.value = true
  try {
    await replyTicket(currentTicket.value.id, {
      content: replyContent.value,
      is_internal: isInternal.value ? 1 : 0
    })
    ElMessage.success('回复成功')
    // 重新加载详情
    const res: any = await getTicket(currentTicket.value.id)
    currentTicket.value = res.data
    replyContent.value = ''
    loadData()
    loadStatistics()
  } catch (error) {
    console.error('回复失败:', error)
  } finally {
    replying.value = false
  }
}

// 解决
const handleResolve = async (row: SupportTicket) => {
  try {
    await ElMessageBox.confirm('确定将此工单标记为已解决吗？', '确认', { type: 'info' })
    await resolveTicket(row.id)
    ElMessage.success('操作成功')
    loadData()
    loadStatistics()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('操作失败:', error)
    }
  }
}

// 关闭
const handleClose = async (row: SupportTicket) => {
  try {
    await ElMessageBox.confirm('确定要关闭此工单吗？关闭后用户将无法继续回复。', '确认关闭', { type: 'warning' })
    await closeTicket(row.id)
    ElMessage.success('工单已关闭')
    loadData()
    loadStatistics()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('操作失败:', error)
    }
  }
}

onMounted(() => {
  loadData()
  loadStatistics()
  loadCategories()
})
</script>

<style scoped>
.stat-cards {
  margin-bottom: 20px;
}

.stat-card {
  text-align: center;
  padding: 10px;
}

.stat-card .stat-value {
  font-size: 32px;
  font-weight: bold;
}

.stat-card .stat-label {
  font-size: 14px;
  color: #909399;
  margin-top: 5px;
}

.stat-card.pending .stat-value { color: #F56C6C; }
.stat-card.processing .stat-value { color: #E6A23C; }
.stat-card.replied .stat-value { color: #409EFF; }
.stat-card.today .stat-value { color: #67C23A; }

.search-card {
  margin-bottom: 20px;
}

.pagination {
  margin-top: 20px;
  justify-content: flex-end;
}

.ticket-detail {
  max-height: 70vh;
  overflow-y: auto;
}

.ticket-content {
  white-space: pre-wrap;
  word-break: break-word;
}

.replies-section {
  margin-top: 20px;
}

.replies-section h4 {
  margin-bottom: 15px;
  color: #303133;
}

.reply-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.reply-item {
  padding: 12px;
  border-radius: 8px;
  background: #f5f7fa;
}

.reply-item.from-service {
  background: #ecf5ff;
}

.reply-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  font-size: 12px;
  color: #909399;
}

.reply-from {
  font-weight: 500;
  color: #606266;
}

.reply-content {
  white-space: pre-wrap;
  word-break: break-word;
  color: #303133;
}

.reply-form {
  margin-top: 20px;
}

/* 图片区域 */
.images-section {
  margin-top: 20px;
}

.images-section h4 {
  margin-bottom: 15px;
  color: #303133;
}

.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.ticket-image {
  width: 100px;
  height: 100px;
  border-radius: 8px;
  cursor: pointer;
  border: 1px solid #ebeef5;
}

/* 关联订单区域 */
.order-section,
.goods-section {
  margin-top: 20px;
}

.order-section h4,
.goods-section h4 {
  margin-bottom: 15px;
  color: #303133;
}

.order-card,
.goods-card {
  background: #fafafa;
}

.order-info {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.order-goods {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.goods-image {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  flex-shrink: 0;
}

.goods-detail {
  flex: 1;
  min-width: 0;
}

.goods-title {
  font-size: 14px;
  color: #303133;
  line-height: 1.4;
  margin-bottom: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.goods-price {
  font-size: 14px;
  color: #f56c6c;
  font-weight: 500;
}

.order-meta {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  padding-top: 12px;
  border-top: 1px solid #ebeef5;
}

.meta-item {
  font-size: 13px;
}

.meta-label {
  color: #909399;
}

.meta-value {
  color: #303133;
}

/* 举报信息区域 */
.report-section,
.content-section {
  margin-top: 20px;
}

.report-section h4 {
  margin-bottom: 15px;
  color: #303133;
  display: flex;
  align-items: center;
  gap: 8px;
}

.report-section h4::before {
  content: '';
  display: inline-block;
  width: 4px;
  height: 16px;
  background-color: #f56c6c;
  border-radius: 2px;
}
</style>
