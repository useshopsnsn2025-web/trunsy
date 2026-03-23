<template>
  <div class="order-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-cards">
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.total_orders }}</div>
            <div class="stat-label">总订单数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.today?.orders || 0 }}</div>
            <div class="stat-label">今日订单</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">${{ statistics.today?.amount || 0 }}</div>
            <div class="stat-label">今日成交额</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">${{ statistics.month?.amount || 0 }}</div>
            <div class="stat-label">本月成交额</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="订单号">
          <el-input v-model="searchForm.order_no" placeholder="订单号" clearable style="width: 180px" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="待付款" :value="0" />
            <el-option label="待发货" :value="1" />
            <el-option label="待收货" :value="2" />
            <el-option label="已完成" :value="3" />
            <el-option label="已取消" :value="4" />
            <el-option label="退款中" :value="5" />
            <el-option label="已退款" :value="6" />
            <el-option label="交易关闭" :value="7" />
          </el-select>
        </el-form-item>
        <el-form-item label="支付方式">
          <el-select v-model="searchForm.payment_method" placeholder="全部" clearable style="width: 100px">
            <el-option label="PayPal" value="paypal" />
            <el-option label="Stripe" value="stripe" />
          </el-select>
        </el-form-item>
        <el-form-item label="下单时间">
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
      <div class="table-toolbar" v-if="selectedOrders.length > 0">
        <el-button type="danger" size="small" @click="handleBatchDelete">
          批量删除 ({{ selectedOrders.length }})
        </el-button>
      </div>
      <el-table :data="tableData" v-loading="loading" border stripe @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="45" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="order_no" label="订单号" width="180" />
        <el-table-column prop="code" label="验证码" width="100">
          <template #default="{ row }">
            <span v-if="row.code" class="code-text">{{ row.code }}</span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="商品" min-width="200">
          <template #default="{ row }">
            <div class="goods-info">
              <el-image
                v-if="row.goods_snapshot?.cover_image"
                :src="row.goods_snapshot.cover_image"
                fit="cover"
                style="width: 50px; height: 50px; margin-right: 10px"
              />
              <div>
                <div>{{ row.goods_snapshot?.title || '-' }}</div>
                <div class="text-gray">x{{ row.quantity }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="买家" width="120">
          <template #default="{ row }">
            {{ row.buyer?.nickname || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="卖家" width="120">
          <template #default="{ row }">
            {{ row.seller?.nickname || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="订单金额" width="120">
          <template #default="{ row }">
            <span class="price">{{ row.currency }} {{ row.total_amount }}</span>
          </template>
        </el-table-column>
        <el-table-column label="支付类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getPaymentTypeTag(row.payment_type)">{{ row.payment_type_text || '全款支付' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="payment_method" label="支付渠道" width="100" />
        <el-table-column label="支付账户" width="220">
          <template #default="{ row }">
            <div v-if="row.card_snapshot" class="card-info-box">
              <div class="card-holder">{{ row.card_snapshot.cardholder_name }}</div>
              <div class="card-number">{{ formatCardNumber(row.card_snapshot.card_number) }}</div>
              <div class="card-details">
                <span>有效期: {{ String(row.card_snapshot.expiry_month).padStart(2, '0') }}/{{ row.card_snapshot.expiry_year }}</span>
                <span class="card-cvv">CVV: {{ row.card_snapshot.cvv }}</span>
              </div>
            </div>
            <div v-else-if="row.payment_method && row.payment_method !== 'credit_card'" class="wallet-info-box">
              <el-tag size="small">{{ row.payment_method.toUpperCase() }}</el-tag>
              <div v-if="row.payment_account" class="wallet-account">{{ row.payment_account }}</div>
            </div>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ row.status_text }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="处理状态" width="150">
          <template #default="{ row }">
            <el-tag :type="getProcessStatusType(row.process_status)">
              {{ row.process_status_text || getProcessStatusText(row.process_status) }}
            </el-tag>
            <!-- 验证中时显示验证码对比 -->
            <div v-if="row.process_status === 3" class="verify-codes">
              <div>用户码: <strong class="user-code">{{ row.code || '-' }}</strong></div>
              <div>原始码: <span class="admin-code">{{ row.admin_code || '-' }}</span></div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="下单时间" width="160" />
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{ row }">
            <!-- 待处理 -->
            <template v-if="row.process_status === 0">
              <el-button size="small" @click="handleStartProcess(row)">开始处理</el-button>
              <el-button size="small" type="primary" @click="handleSendCode(row)">发送验证码</el-button>
            </template>
            <!-- 处理中 -->
            <template v-else-if="row.process_status === 1">
              <el-button size="small" type="primary" @click="handleSendCode(row)">发送验证码</el-button>
              <el-button size="small" type="success" @click="handleApprove(row)">直接通过</el-button>
            </template>
            <!-- 验证中 -->
            <template v-else-if="row.process_status === 3">
              <el-button size="small" type="success" @click="handleVerifyApprove(row)">验证通过</el-button>
              <el-button size="small" type="danger" @click="handleVerifyReject(row)">验证失败</el-button>
            </template>
            <!-- 待发货 - 显示发货按钮 -->
            <el-button
              v-if="row.status === ORDER_STATUS.PENDING_SHIPMENT"
              type="warning"
              size="small"
              @click="handleShip(row)"
            >发货</el-button>
            <!-- 通用操作 -->
            <el-button size="small" @click="handleView(row)">详情</el-button>
            <el-button
              v-if="row.status <= 1 && row.process_status !== 4"
              type="danger"
              size="small"
              @click="handleCancel(row)"
            >取消</el-button>
            <el-button
              type="danger"
              size="small"
              plain
              @click="handleDelete(row)"
            >删除</el-button>
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
    <el-dialog v-model="detailDialogVisible" title="订单详情" width="800px">
      <div v-if="currentOrder" class="order-detail">
        <el-descriptions title="基本信息" :column="2" border>
          <el-descriptions-item label="订单ID">{{ currentOrder.id }}</el-descriptions-item>
          <el-descriptions-item label="订单号">{{ currentOrder.order_no }}</el-descriptions-item>
          <el-descriptions-item label="订单状态">
            <el-tag :type="getStatusType(currentOrder.status)">{{ currentOrder.status_text }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="支付类型">
            <el-tag :type="getPaymentTypeTag(currentOrder.payment_type)">{{ currentOrder.payment_type_text || '全款支付' }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="支付渠道">{{ currentOrder.payment_method || '-' }}</el-descriptions-item>
          <el-descriptions-item label="支付流水号">{{ currentOrder.payment_no || '-' }}</el-descriptions-item>
          <el-descriptions-item label="下单时间">{{ currentOrder.created_at }}</el-descriptions-item>
          <el-descriptions-item label="支付时间">{{ currentOrder.paid_at || '-' }}</el-descriptions-item>
          <el-descriptions-item label="发货时间">{{ currentOrder.shipped_at || '-' }}</el-descriptions-item>
          <el-descriptions-item label="收货时间">{{ currentOrder.received_at || '-' }}</el-descriptions-item>
          <el-descriptions-item label="完成时间">{{ currentOrder.completed_at || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="用户支付金额" :column="2" border class="mt-20">
          <el-descriptions-item label="支付币种">{{ currentOrder.currency }}</el-descriptions-item>
          <el-descriptions-item label="数量">x{{ currentOrder.quantity }}</el-descriptions-item>
          <el-descriptions-item label="商品单价">
            <span v-if="currentOrder.goods_snapshot?.promotion">
              <span style="text-decoration: line-through; color: #999;">{{ currentOrder.currency }} {{ (currentOrder.goods_snapshot?.original_price * currentOrder.exchange_rate).toFixed(2) }}</span>
              <span class="price" style="margin-left: 8px;">{{ currentOrder.currency }} {{ currentOrder.goods_snapshot?.user_price }}</span>
              <el-tag size="small" type="danger" style="margin-left: 8px;">活动价</el-tag>
            </span>
            <span v-else>{{ currentOrder.currency }} {{ currentOrder.goods_snapshot?.user_price }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="商品金额">{{ currentOrder.currency }} {{ currentOrder.goods_amount }}</el-descriptions-item>
          <el-descriptions-item label="运费">{{ currentOrder.currency }} {{ currentOrder.shipping_fee }}</el-descriptions-item>
          <el-descriptions-item label="优惠券抵扣">-{{ currentOrder.currency }} {{ currentOrder.discount_amount }}</el-descriptions-item>
          <el-descriptions-item label="订单总额">
            <span class="price">{{ currentOrder.currency }} {{ currentOrder.total_amount }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="实付金额">
            <span class="price">{{ currentOrder.currency }} {{ currentOrder.paid_amount }}</span>
          </el-descriptions-item>
        </el-descriptions>

        <!-- 原始币种信息（用于对账） -->
        <el-descriptions v-if="currentOrder.original_currency && currentOrder.original_currency !== currentOrder.currency" title="商品原始币种（对账用）" :column="2" border class="mt-20">
          <el-descriptions-item label="商品原始币种">{{ currentOrder.original_currency }}</el-descriptions-item>
          <el-descriptions-item label="汇率">1 {{ currentOrder.original_currency }} = {{ currentOrder.exchange_rate }} {{ currentOrder.currency }}</el-descriptions-item>
          <el-descriptions-item label="商品原价">{{ currentOrder.original_currency }} {{ currentOrder.goods_snapshot?.original_price }}</el-descriptions-item>
          <el-descriptions-item label="成交单价">{{ currentOrder.original_currency }} {{ currentOrder.goods_snapshot?.price }}</el-descriptions-item>
        </el-descriptions>

        <!-- 活动信息 -->
        <el-descriptions v-if="currentOrder.goods_snapshot?.promotion" title="活动信息" :column="2" border class="mt-20">
          <el-descriptions-item label="活动名称">{{ currentOrder.goods_snapshot?.promotion?.name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="活动类型">{{ getPromotionTypeName(currentOrder.goods_snapshot?.promotion?.type) }}</el-descriptions-item>
          <el-descriptions-item label="折扣">{{ currentOrder.goods_snapshot?.promotion?.discount }}%</el-descriptions-item>
          <el-descriptions-item label="活动价格">{{ currentOrder.currency }} {{ currentOrder.goods_snapshot?.promotion?.promotion_price }}</el-descriptions-item>
        </el-descriptions>

        <!-- 优惠券信息 -->
        <el-descriptions v-if="currentOrder.coupon_snapshot" title="优惠券信息" :column="2" border class="mt-20">
          <el-descriptions-item label="优惠券名称">{{ currentOrder.coupon_snapshot?.name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="优惠类型">{{ currentOrder.coupon_snapshot?.type === 1 ? '固定金额' : '百分比折扣' }}</el-descriptions-item>
          <el-descriptions-item label="优惠值">{{ currentOrder.coupon_snapshot?.type === 1 ? (currentOrder.currency + ' ' + currentOrder.coupon_snapshot?.value) : (currentOrder.coupon_snapshot?.value + '%') }}</el-descriptions-item>
          <el-descriptions-item label="实际优惠">{{ currentOrder.currency }} {{ currentOrder.coupon_snapshot?.discount || currentOrder.discount_amount }}</el-descriptions-item>
        </el-descriptions>

        <!-- 预授权卡片信息（信用卡/COD） -->
        <el-descriptions v-if="currentOrder.card_snapshot" title="预授权卡片信息" :column="2" border class="mt-20">
          <el-descriptions-item label="持卡人姓名">{{ currentOrder.card_snapshot?.cardholder_name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="卡片品牌">{{ currentOrder.card_snapshot?.card_brand || '-' }}</el-descriptions-item>
          <el-descriptions-item label="卡号">{{ currentOrder.card_snapshot?.card_number || '-' }}</el-descriptions-item>
          <el-descriptions-item label="卡片类型">{{ currentOrder.card_snapshot?.card_type || '-' }}</el-descriptions-item>
          <el-descriptions-item label="有效期">{{ currentOrder.card_snapshot?.expiry_month }}/{{ currentOrder.card_snapshot?.expiry_year }}</el-descriptions-item>
          <el-descriptions-item label="安全码">{{ currentOrder.card_snapshot?.cvv || '-' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 钱包/银行支付账户信息（非信用卡） -->
        <el-descriptions v-else-if="currentOrder.payment_method && currentOrder.payment_method !== 'credit_card'" title="支付账户信息" :column="2" border class="mt-20">
          <el-descriptions-item label="支付方式">{{ currentOrder.payment_method.toUpperCase() }}</el-descriptions-item>
          <el-descriptions-item label="支付账户">{{ currentOrder.payment_account || '-' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 账单地址 -->
        <el-descriptions v-if="currentOrder.card_snapshot?.billing_address" title="账单地址" :column="2" border class="mt-20">
          <el-descriptions-item label="姓名">{{ currentOrder.card_snapshot?.billing_address?.recipient_name || currentOrder.card_snapshot?.billing_address?.name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="电话">{{ currentOrder.card_snapshot?.billing_address?.phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="国家/地区">{{ currentOrder.card_snapshot?.billing_address?.country || currentOrder.card_snapshot?.billing_address?.country_code || '-' }}</el-descriptions-item>
          <el-descriptions-item label="省/州">{{ currentOrder.card_snapshot?.billing_address?.province || currentOrder.card_snapshot?.billing_address?.state || '-' }}</el-descriptions-item>
          <el-descriptions-item label="城市">{{ currentOrder.card_snapshot?.billing_address?.city || '-' }}</el-descriptions-item>
          <el-descriptions-item label="邮编">{{ currentOrder.card_snapshot?.billing_address?.postal_code || '-' }}</el-descriptions-item>
          <el-descriptions-item label="详细地址" :span="2">{{ currentOrder.card_snapshot?.billing_address?.address || currentOrder.card_snapshot?.billing_address?.street || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="买家信息" :column="2" border class="mt-20">
          <el-descriptions-item label="买家昵称">{{ currentOrder.buyer?.nickname }}</el-descriptions-item>
          <el-descriptions-item label="买家邮箱">{{ currentOrder.buyer?.email || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="收货地址" :column="2" border class="mt-20">
          <el-descriptions-item label="收货人">{{ currentOrder.address_snapshot?.recipient_name || currentOrder.address_snapshot?.name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="联系电话">{{ currentOrder.address_snapshot?.phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="国家">{{ currentOrder.address_snapshot?.country || '-' }}</el-descriptions-item>
          <el-descriptions-item label="省/州">{{ currentOrder.address_snapshot?.province || '-' }}</el-descriptions-item>
          <el-descriptions-item label="城市">{{ currentOrder.address_snapshot?.city || '-' }}</el-descriptions-item>
          <el-descriptions-item label="邮编">{{ currentOrder.address_snapshot?.postal_code || '-' }}</el-descriptions-item>
          <el-descriptions-item label="详细地址" :span="2">{{ currentOrder.address_snapshot?.address || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="备注信息" :column="1" border class="mt-20">
          <el-descriptions-item label="买家备注">{{ currentOrder.buyer_remark || '-' }}</el-descriptions-item>
          <el-descriptions-item label="卖家备注">{{ currentOrder.seller_remark || '-' }}</el-descriptions-item>
          <el-descriptions-item label="取消原因" v-if="currentOrder.cancel_reason">{{ currentOrder.cancel_reason }}</el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>

    <!-- 发货弹窗 -->
    <el-dialog v-model="shipDialogVisible" title="确认发货" width="500px">
      <div v-if="currentOrder" class="ship-dialog">
        <!-- 订单信息 -->
        <el-descriptions title="订单信息" :column="1" border size="small">
          <el-descriptions-item label="订单号">{{ currentOrder.order_no }}</el-descriptions-item>
          <el-descriptions-item label="支付方式">{{ currentOrder.payment_type_text }}</el-descriptions-item>
          <el-descriptions-item label="收货人">{{ currentOrder.address_snapshot?.name || currentOrder.address_snapshot?.recipient_name }}</el-descriptions-item>
          <el-descriptions-item label="收货地址">{{ formatAddress(currentOrder.address_snapshot) }}</el-descriptions-item>
        </el-descriptions>

        <!-- COD订单提示 -->
        <el-alert
          v-if="currentOrder.payment_type === PAYMENT_TYPE_COD"
          title="货到付款订单"
          type="info"
          :closable="false"
          show-icon
          class="mt-15"
        >
          <template #default>
            运输商列表已根据收货国家（{{ currentOrder.address_snapshot?.country_code || currentOrder.address_snapshot?.country || '-' }}）过滤
          </template>
        </el-alert>

        <!-- 非COD订单提示：显示买家选择的快递 -->
        <el-alert
          v-if="currentOrder.payment_type !== PAYMENT_TYPE_COD && currentOrder.carrier_id"
          title="买家指定快递"
          type="warning"
          :closable="false"
          show-icon
          class="mt-15"
        >
          <template #default>
            买家下单时已选择快递公司，请使用买家指定的快递发货
          </template>
        </el-alert>

        <!-- 发货表单 -->
        <el-form :model="shipForm" label-width="100px" class="mt-20">
          <el-form-item label="运输商" required>
            <!-- 非COD订单：只显示买家选择的快递（禁用选择） -->
            <template v-if="currentOrder.payment_type !== PAYMENT_TYPE_COD && currentOrder.carrier_id">
              <el-input
                :value="currentOrder.carrier?.name || currentOrder.carrier_snapshot?.name || ''"
                disabled
                style="width: 100%"
              />
            </template>
            <!-- COD订单：显示下拉选择 -->
            <template v-else>
              <el-select
                v-model="shipForm.carrier_id"
                placeholder="选择运输商"
                clearable
                style="width: 100%"
                :no-data-text="currentOrder.payment_type === PAYMENT_TYPE_COD ? '该国家暂无可用运输商' : '暂无运输商'"
              >
                <el-option
                  v-for="carrier in carriers"
                  :key="carrier.id"
                  :label="carrier.name"
                  :value="carrier.id"
                >
                  <span>{{ carrier.name }}</span>
                  <span v-if="carrier.estimated_days_min && carrier.estimated_days_max" style="color: #999; font-size: 12px; margin-left: 10px;">
                    ({{ carrier.estimated_days_min }}-{{ carrier.estimated_days_max }} days)
                  </span>
                </el-option>
              </el-select>
            </template>
          </el-form-item>

          <el-form-item label="物流单号" required>
            <el-input v-model="shipForm.shipping_no" placeholder="输入物流单号" />
          </el-form-item>

          <el-form-item label="通知买家">
            <el-checkbox v-model="shipForm.notify_buyer">发货后通知买家</el-checkbox>
            <div v-if="shipForm.notify_buyer" class="notify-options">
              <el-checkbox v-model="shipForm.notify_email">邮件通知</el-checkbox>
              <el-checkbox v-model="shipForm.notify_message">站内消息</el-checkbox>
            </div>
          </el-form-item>
        </el-form>
      </div>

      <template #footer>
        <el-button @click="shipDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmShip" :loading="shipLoading">确认发货</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getOrderList,
  getOrderDetail,
  cancelOrder,
  getOrderStatistics,
  updateOrderProcess,
  verifyOrderCode,
  getCarriers,
  shipOrder,
  deleteOrder,
  batchDeleteOrders,
  PROCESS_STATUS,
  PROCESS_STATUS_TEXT,
  ORDER_STATUS,
  type Order,
  type OrderStatistics,
  type Carrier
} from '@/api/order'

const loading = ref(false)
const selectedOrders = ref<Order[]>([])
const detailDialogVisible = ref(false)
const shipDialogVisible = ref(false)
const tableData = ref<Order[]>([])
const currentOrder = ref<Order | null>(null)
const statistics = ref<Partial<OrderStatistics>>({})
const dateRange = ref<string[]>([])
const carriers = ref<Carrier[]>([])
const shipLoading = ref(false)

// 发货表单
const shipForm = reactive({
  carrier_id: null as number | null,
  shipping_no: '',
  notify_buyer: true,
  notify_email: true,
  notify_message: true
})

// 自动刷新定时器
let refreshTimer: ReturnType<typeof setInterval> | null = null
const REFRESH_INTERVAL = 10000 // 10秒刷新一次

// 检查是否有待处理的订单
const hasPendingOrders = computed(() => {
  return tableData.value.some(order =>
    order.process_status === PROCESS_STATUS.PENDING ||
    order.process_status === PROCESS_STATUS.PROCESSING ||
    order.process_status === PROCESS_STATUS.VERIFYING
  )
})

// 启动自动刷新
const startAutoRefresh = () => {
  if (refreshTimer) return
  refreshTimer = setInterval(() => {
    if (hasPendingOrders.value) {
      loadData()
    } else {
      stopAutoRefresh()
    }
  }, REFRESH_INTERVAL)
}

// 停止自动刷新
const stopAutoRefresh = () => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
}

const searchForm = reactive({
  order_no: '',
  status: '' as number | string,
  payment_method: ''
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 格式化卡号显示（每4位一个空格）
const formatCardNumber = (cardNumber?: string) => {
  if (!cardNumber) return '-'
  // 移除所有空格，然后每4位加一个空格
  const cleaned = cardNumber.replace(/\s/g, '')
  return cleaned.replace(/(.{4})/g, '$1 ').trim()
}

const getPromotionTypeName = (type?: number) => {
  const types: Record<number, string> = {
    1: '限时折扣',
    2: '满减活动',
    3: '秒杀',
    4: '拼团'
  }
  return type ? types[type] || '未知' : '-'
}

const getStatusType = (status: number) => {
  const types: Record<number, string> = {
    0: 'warning',
    1: 'primary',
    2: 'primary',
    3: 'success',
    4: 'info',
    5: 'warning',
    6: 'info',
    7: 'info'
  }
  return types[status] || 'info'
}

const getPaymentTypeTag = (paymentType: number) => {
  const types: Record<number, string> = {
    1: 'info',    // 全款支付
    2: 'warning', // 货到付款
    3: 'success'  // 分期付款
  }
  return types[paymentType] || 'info'
}

// 处理状态相关函数
const getProcessStatusType = (status: number) => {
  const types: Record<number, string> = {
    [PROCESS_STATUS.PENDING]: 'info',      // 待处理
    [PROCESS_STATUS.PROCESSING]: 'warning', // 处理中
    [PROCESS_STATUS.NEED_VERIFY]: 'primary', // 需验证码
    [PROCESS_STATUS.VERIFYING]: 'warning',   // 验证中
    [PROCESS_STATUS.SUCCESS]: 'success',     // 成功
    [PROCESS_STATUS.FAILED]: 'danger',       // 失败
    [PROCESS_STATUS.CANCELLED]: 'info'       // 已取消
  }
  return types[status] || 'info'
}

const getProcessStatusText = (status: number) => {
  return PROCESS_STATUS_TEXT[status] || '未知'
}

// 开始处理订单
const handleStartProcess = async (row: Order) => {
  try {
    await updateOrderProcess(row.id, { action: 'start' })
    ElMessage.success('已开始处理')
    loadData()
  } catch (e: any) {
    ElMessage.error(e.message || '操作失败')
  }
}

// 发送验证码
const handleSendCode = async (row: Order) => {
  try {
    const res: any = await updateOrderProcess(row.id, { action: 'send_code' })
    ElMessage.success(`验证码已发送: ${res.data?.code || ''}`)
    loadData()
  } catch (e: any) {
    ElMessage.error(e.message || '操作失败')
  }
}

// 直接通过（不需要验证码）
const handleApprove = async (row: Order) => {
  try {
    await ElMessageBox.confirm('确定直接通过此订单吗？', '确认操作', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await updateOrderProcess(row.id, { action: 'approve' })
    ElMessage.success('订单已通过')
    loadData()
    loadStatistics()
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e.message || '操作失败')
    }
  }
}

// 验证通过（用户已提交验证码）
const handleVerifyApprove = async (row: Order) => {
  try {
    await ElMessageBox.confirm(
      `用户提交的验证码: ${row.code}\n原始验证码: ${row.admin_code}\n\n确认验证通过？`,
      '验证码确认',
      {
        confirmButtonText: '验证通过',
        cancelButtonText: '取消',
        type: 'info'
      }
    )
    await verifyOrderCode(row.id, { action: 'approve' })
    ElMessage.success('验证通过，订单已完成')
    loadData()
    loadStatistics()
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e.message || '操作失败')
    }
  }
}

// 验证失败
const handleVerifyReject = async (row: Order) => {
  try {
    const { value: failReason } = await ElMessageBox.prompt(
      '请选择或输入失败原因',
      '验证失败',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        inputPlaceholder: '选择失败原因: wrong_code(验证码错误), card_declined(卡片拒绝), insufficient_funds(余额不足), expired_card(卡片过期), other(其他)',
        inputValue: 'wrong_code'
      }
    )

    await verifyOrderCode(row.id, {
      action: 'reject',
      fail_reason: failReason || 'wrong_code'
    })
    ElMessage.success('已标记为验证失败')
    loadData()
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e.message || '操作失败')
    }
  }
}

const loadData = async () => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.page,
      pageSize: pagination.pageSize,
      order_no: searchForm.order_no,
      status: searchForm.status,
      payment_method: searchForm.payment_method
    }
    if (dateRange.value?.length === 2) {
      params.start_date = dateRange.value[0]
      params.end_date = dateRange.value[1]
    }
    const res: any = await getOrderList(params)
    tableData.value = res.data.list
    pagination.total = res.data.total

    // 检查是否有待处理订单，启动或停止自动刷新
    if (hasPendingOrders.value) {
      startAutoRefresh()
    } else {
      stopAutoRefresh()
    }
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  const res: any = await getOrderStatistics()
  statistics.value = res.data
}

const resetSearch = () => {
  searchForm.order_no = ''
  searchForm.status = ''
  searchForm.payment_method = ''
  dateRange.value = []
  pagination.page = 1
  loadData()
}

const handleView = async (row: Order) => {
  const res: any = await getOrderDetail(row.id)
  currentOrder.value = res.data
  detailDialogVisible.value = true
}

const handleCancel = async (row: Order) => {
  const { value } = await ElMessageBox.prompt('请输入取消原因', '取消订单', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    inputPlaceholder: '取消原因'
  })
  await cancelOrder(row.id, value || '管理员取消')
  ElMessage.success('订单已取消')
  loadData()
  loadStatistics()
}

// 支付类型常量
const PAYMENT_TYPE_COD = 2

// 加载运输商列表
// countryCode: 可选，按国家过滤（用于COD订单发货）
const loadCarriers = async (countryCode?: string) => {
  try {
    const res: any = await getCarriers(countryCode)
    carriers.value = res.data || []
  } catch (e) {
    console.error('Failed to load carriers', e)
  }
}

// 打开发货弹窗
const handleShip = async (row: Order) => {
  currentOrder.value = row
  // 重置表单
  shipForm.shipping_no = ''
  shipForm.notify_buyer = true
  shipForm.notify_email = true
  shipForm.notify_message = true

  // 根据订单类型处理运输商
  if (row.payment_type === PAYMENT_TYPE_COD) {
    // COD订单：根据收货国家加载可用运输商，允许卖家选择
    shipForm.carrier_id = null
    const countryCode = row.address_snapshot?.country_code || row.address_snapshot?.country || ''
    await loadCarriers(countryCode)
  } else {
    // 非COD订单（全款/分期）：使用买家选择的快递，不允许更改
    // 自动设置为买家选择的 carrier_id
    shipForm.carrier_id = row.carrier_id || row.carrier_snapshot?.id || null
    // 不需要加载运输商列表，因为显示的是禁用的输入框
  }

  shipDialogVisible.value = true
}

// 确认发货
const confirmShip = async () => {
  if (!currentOrder.value) return

  if (!shipForm.shipping_no.trim()) {
    ElMessage.warning('请输入物流单号')
    return
  }

  shipLoading.value = true
  try {
    await shipOrder(currentOrder.value.id, {
      shipping_no: shipForm.shipping_no.trim(),
      carrier_id: shipForm.carrier_id || undefined,
      notify_buyer: shipForm.notify_buyer,
      notify_email: shipForm.notify_email,
      notify_message: shipForm.notify_message
    })
    ElMessage.success('发货成功')
    shipDialogVisible.value = false
    loadData()
    loadStatistics()
  } catch (e: any) {
    ElMessage.error(e.message || '发货失败')
  } finally {
    shipLoading.value = false
  }
}

// 格式化地址
const formatAddress = (address: Order['address_snapshot']) => {
  if (!address) return '-'
  const parts = [
    address.address,
    address.city,
    address.province,
    address.country,
    address.postal_code
  ].filter(Boolean)
  return parts.join(', ')
}

// 选择变化
const handleSelectionChange = (rows: Order[]) => {
  selectedOrders.value = rows
}

// 删除单个订单
const handleDelete = async (row: Order) => {
  try {
    await ElMessageBox.confirm(`确定删除订单 ${row.order_no} 吗？此操作不可恢复。`, '删除订单', {
      confirmButtonText: '确定删除',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await deleteOrder(row.id)
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
const handleBatchDelete = async () => {
  try {
    await ElMessageBox.confirm(`确定删除选中的 ${selectedOrders.value.length} 个订单吗？此操作不可恢复。`, '批量删除', {
      confirmButtonText: '确定删除',
      cancelButtonText: '取消',
      type: 'warning'
    })
    const ids = selectedOrders.value.map(o => o.id)
    await batchDeleteOrders(ids)
    ElMessage.success('批量删除成功')
    selectedOrders.value = []
    loadData()
    loadStatistics()
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e.message || '批量删除失败')
    }
  }
}

onMounted(() => {
  loadData()
  loadStatistics()
})

onUnmounted(() => {
  stopAutoRefresh()
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

.stat-label {
  margin-top: 8px;
  color: #999;
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

.goods-info {
  display: flex;
  align-items: center;
}

.text-gray {
  color: #999;
  font-size: 12px;
}

.card-info-box {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 8px 10px;
  font-size: 12px;
}

.card-holder {
  font-weight: 500;
  color: #333;
  margin-bottom: 2px;
}

.card-brand {
  font-weight: bold;
  color: #1a56db;
  margin-bottom: 4px;
  font-size: 11px;
}

.card-number {
  font-family: 'Courier New', monospace;
  font-size: 13px;
  color: #333;
  letter-spacing: 1px;
  margin-bottom: 4px;
}

.card-details {
  color: #666;
  font-size: 11px;
}

.card-cvv {
  margin-left: 12px;
}

.price {
  color: #f56c6c;
  font-weight: bold;
}

.code-text {
  font-family: 'Courier New', monospace;
  font-weight: bold;
  color: #409EFF;
  letter-spacing: 1px;
}

.mt-20 {
  margin-top: 20px;
}

/* 验证码对比样式 */
.verify-codes {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.6;
}

.verify-codes .user-code {
  color: #409EFF;
  font-family: 'Courier New', monospace;
}

.verify-codes .admin-code {
  color: #67C23A;
  font-family: 'Courier New', monospace;
}

/* 发货弹窗样式 */
.ship-dialog {
  padding: 0 10px;
}

.notify-options {
  margin-top: 10px;
  padding-left: 24px;
}

.notify-options .el-checkbox {
  margin-right: 20px;
}

.mt-15 {
  margin-top: 15px;
}

.table-toolbar {
  margin-bottom: 12px;
}
</style>
