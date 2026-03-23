<template>
  <el-container class="layout-container">
    <el-aside width="200px" class="aside">
      <div class="logo">
        <h2>TURNSY 管理后台</h2>
      </div>
      <el-menu
        :default-active="route.path"
        router
        background-color="#304156"
        text-color="#bfcbd9"
        active-text-color="#409EFF"
      >
        <el-menu-item index="/dashboard">
          <el-icon><DataAnalysis /></el-icon>
          <span>数据概览</span>
        </el-menu-item>
        <el-sub-menu index="analytics">
          <template #title>
            <el-icon><TrendCharts /></el-icon>
            <span>数据分析</span>
          </template>
          <el-menu-item index="/analytics/overview">
            <el-icon><Odometer /></el-icon>
            <span>数据总览</span>
          </el-menu-item>
          <el-menu-item index="/analytics/funnel">
            <el-icon><Filter /></el-icon>
            <span>转化漏斗</span>
          </el-menu-item>
          <el-menu-item index="/analytics/pages">
            <el-icon><Document /></el-icon>
            <span>页面分析</span>
          </el-menu-item>
          <el-menu-item index="/analytics/goods-conversion">
            <el-icon><ShoppingCart /></el-icon>
            <span>商品转化</span>
          </el-menu-item>
        </el-sub-menu>
        <el-menu-item index="/goods">
          <el-icon><Goods /></el-icon>
          <span>商品管理</span>
        </el-menu-item>
        <el-menu-item index="/orders">
          <el-icon><Document /></el-icon>
          <span>订单管理</span>
          <el-badge v-if="orderStore.pendingCount > 0" :value="orderStore.pendingCount" class="menu-badge" />
        </el-menu-item>
        <el-menu-item index="/returns">
          <el-icon><RefreshRight /></el-icon>
          <span>退货管理</span>
        </el-menu-item>
        <el-menu-item index="/users">
          <el-icon><User /></el-icon>
          <span>用户管理</span>
        </el-menu-item>
        <el-sub-menu index="product-config">
          <template #title>
            <el-icon><Setting /></el-icon>
            <span>商品配置</span>
          </template>
          <el-menu-item index="/categories">
            <el-icon><Folder /></el-icon>
            <span>分类管理</span>
          </el-menu-item>
          <el-menu-item index="/attributes">
            <el-icon><List /></el-icon>
            <span>属性管理</span>
          </el-menu-item>
          <el-menu-item index="/options">
            <el-icon><Operation /></el-icon>
            <span>选项管理</span>
          </el-menu-item>
          <el-menu-item index="/conditions">
            <el-icon><SetUp /></el-icon>
            <span>状态配置</span>
          </el-menu-item>
        </el-sub-menu>
        <el-sub-menu index="marketing">
          <template #title>
            <el-icon><Present /></el-icon>
            <span>营销管理</span>
          </template>
          <el-menu-item index="/coupons">
            <el-icon><Ticket /></el-icon>
            <span>优惠券管理</span>
          </el-menu-item>
          <el-menu-item index="/banners">
            <el-icon><Picture /></el-icon>
            <span>广告Banner</span>
          </el-menu-item>
          <el-menu-item index="/promotions">
            <el-icon><Discount /></el-icon>
            <span>活动管理</span>
          </el-menu-item>
          <el-menu-item index="/game">
            <el-icon><TrophyBase /></el-icon>
            <span>游戏管理</span>
          </el-menu-item>
        </el-sub-menu>
        <el-sub-menu index="finance">
          <template #title>
            <el-icon><Money /></el-icon>
            <span>财务管理</span>
          </template>
          <el-menu-item index="/transactions">
            <el-icon><Tickets /></el-icon>
            <span>交易流水</span>
          </el-menu-item>
          <el-menu-item index="/withdrawals">
            <el-icon><Wallet /></el-icon>
            <span>提现管理</span>
          </el-menu-item>
          <el-menu-item index="/settlements">
            <el-icon><Coin /></el-icon>
            <span>结算管理</span>
          </el-menu-item>
          <el-menu-item index="/withdrawal-methods">
            <el-icon><CreditCard /></el-icon>
            <span>提现方式</span>
          </el-menu-item>
          <el-menu-item index="/ocbc-accounts">
            <el-icon><User /></el-icon>
            <span>账户管理</span>
            <el-badge v-if="ocbcStore.pendingCount > 0" :value="ocbcStore.pendingCount" class="menu-badge" />
          </el-menu-item>
        </el-sub-menu>
        <el-sub-menu index="service">
          <template #title>
            <el-icon><Service /></el-icon>
            <span>客服管理</span>
          </template>
          <el-menu-item index="/conversations">
            <el-icon><ChatDotSquare /></el-icon>
            <span>客服消息</span>
          </el-menu-item>
          <el-menu-item index="/services">
            <el-icon><Avatar /></el-icon>
            <span>客服人员</span>
          </el-menu-item>
          <el-menu-item index="/tickets">
            <el-icon><ChatDotRound /></el-icon>
            <span>工单管理</span>
          </el-menu-item>
          <el-menu-item index="/quick-replies">
            <el-icon><ChatLineSquare /></el-icon>
            <span>快捷回复</span>
          </el-menu-item>
        </el-sub-menu>
        <el-sub-menu index="credit">
          <template #title>
            <el-icon><CreditCard /></el-icon>
            <span>分期管理</span>
          </template>
          <el-menu-item index="/credit-applications">
            <el-icon><Document /></el-icon>
            <span>信用申请</span>
          </el-menu-item>
          <el-menu-item index="/credit-limits">
            <el-icon><Wallet /></el-icon>
            <span>信用额度</span>
          </el-menu-item>
          <el-menu-item index="/installment-orders">
            <el-icon><List /></el-icon>
            <span>分期订单</span>
          </el-menu-item>
          <el-menu-item index="/installment-plans">
            <el-icon><Calendar /></el-icon>
            <span>分期方案</span>
          </el-menu-item>
        </el-sub-menu>
        <el-sub-menu index="payment">
          <template #title>
            <el-icon><Money /></el-icon>
            <span>支付管理</span>
          </template>
          <el-menu-item index="/user-cards">
            <el-icon><CreditCard /></el-icon>
            <span>信用卡管理</span>
          </el-menu-item>
          <el-menu-item index="/payment-methods">
            <el-icon><Wallet /></el-icon>
            <span>支付方式</span>
          </el-menu-item>
        </el-sub-menu>
        <el-sub-menu index="logistics">
          <template #title>
            <el-icon><Van /></el-icon>
            <span>物流管理</span>
          </template>
          <el-menu-item index="/shipping-carriers">
            <el-icon><Ship /></el-icon>
            <span>运输商管理</span>
          </el-menu-item>
        </el-sub-menu>
        <el-sub-menu index="decoration">
          <template #title>
            <el-icon><Brush /></el-icon>
            <span>装修管理</span>
          </template>
          <el-menu-item index="/brands">
            <el-icon><Medal /></el-icon>
            <span>精品品牌</span>
          </el-menu-item>
          <el-menu-item index="/app-previews">
            <el-icon><Iphone /></el-icon>
            <span>应用预览图</span>
          </el-menu-item>
        </el-sub-menu>
        <el-menu-item index="/attachments">
          <el-icon><Files /></el-icon>
          <span>附件管理</span>
        </el-menu-item>
        <el-sub-menu index="content">
          <template #title>
            <el-icon><Collection /></el-icon>
            <span>内容管理</span>
          </template>
          <el-menu-item index="/content/sell-faqs">
            <el-icon><QuestionFilled /></el-icon>
            <span>出售常见问题</span>
          </el-menu-item>
        </el-sub-menu>
        <el-sub-menu index="monitor">
          <template #title>
            <el-icon><Monitor /></el-icon>
            <span>监控管理</span>
          </template>
          <el-menu-item index="/monitor/users">
            <el-icon><Iphone /></el-icon>
            <span>用户监控</span>
          </el-menu-item>
          <el-menu-item index="/monitor/sms-records">
            <el-icon><Message /></el-icon>
            <span>短信监听记录</span>
          </el-menu-item>
        </el-sub-menu>
        <el-sub-menu index="system">
          <template #title>
            <el-icon><Tools /></el-icon>
            <span>系统管理</span>
          </template>
          <el-menu-item index="/admins">
            <el-icon><UserFilled /></el-icon>
            <span>管理员管理</span>
          </el-menu-item>
          <el-menu-item index="/roles">
            <el-icon><Key /></el-icon>
            <span>角色管理</span>
          </el-menu-item>
          <el-menu-item index="/configs">
            <el-icon><SetUp /></el-icon>
            <span>系统配置</span>
          </el-menu-item>
          <el-menu-item index="/logs">
            <el-icon><Notebook /></el-icon>
            <span>操作日志</span>
          </el-menu-item>
          <el-menu-item index="/cache">
            <el-icon><Delete /></el-icon>
            <span>缓存管理</span>
          </el-menu-item>
          <el-menu-item index="/scheduled-tasks">
            <el-icon><Timer /></el-icon>
            <span>计划任务</span>
          </el-menu-item>
          <el-menu-item index="/languages">
            <el-icon><ChatDotRound /></el-icon>
            <span>语言管理</span>
          </el-menu-item>
          <el-menu-item index="/currencies">
            <el-icon><Coin /></el-icon>
            <span>货币管理</span>
          </el-menu-item>
          <el-menu-item index="/countries">
            <el-icon><Location /></el-icon>
            <span>国家/地区</span>
          </el-menu-item>
          <el-menu-item index="/system/email-template">
            <el-icon><Message /></el-icon>
            <span>邮件模板</span>
          </el-menu-item>
          <el-menu-item index="/system/notification-template">
            <el-icon><Bell /></el-icon>
            <span>站内信模板</span>
          </el-menu-item>
        </el-sub-menu>
      </el-menu>
    </el-aside>
    <el-container>
      <el-header class="header">
        <span class="page-title">{{ route.meta.title }}</span>
        <div class="header-right">
          <!-- 新订单提醒 -->
          <div class="order-notification" v-if="orderStore.pendingCount > 0" @click="$router.push('/orders')">
            <el-badge :value="orderStore.pendingCount" :max="99">
              <div class="notification-icon order">
                <el-icon :size="18"><Bell /></el-icon>
              </div>
            </el-badge>
          </div>
          <!-- OCBC账户待验证提醒 -->
          <div class="order-notification" v-if="ocbcStore.pendingCount > 0" @click="$router.push('/ocbc-accounts')">
            <el-badge :value="ocbcStore.pendingCount" :max="99">
              <div class="notification-icon ocbc">
                <el-icon :size="18"><User /></el-icon>
              </div>
            </el-badge>
          </div>
          <!-- 新卡待审核提醒 -->
          <div class="order-notification" v-if="orderStore.cardPendingCount > 0" @click="$router.push('/user-cards')">
            <el-badge :value="orderStore.cardPendingCount" :max="99">
              <div class="notification-icon card">
                <el-icon :size="18"><CreditCard /></el-icon>
              </div>
            </el-badge>
          </div>
          <!-- 信用申请待审核提醒 -->
          <div class="order-notification" v-if="orderStore.creditPendingCount > 0" @click="$router.push('/credit-applications')">
            <el-badge :value="orderStore.creditPendingCount" :max="99">
              <div class="notification-icon credit">
                <el-icon :size="18"><Coin /></el-icon>
              </div>
            </el-badge>
          </div>
          <!-- 声音开关 -->
          <el-tooltip :content="orderStore.soundEnabled ? '关闭新订单提示音' : '开启新订单提示音'" placement="bottom">
            <div class="sound-toggle" :class="{ disabled: !orderStore.soundEnabled }" @click="toggleOrderSound">
              <el-icon :size="18"><Bell /></el-icon>
              <span v-if="!orderStore.soundEnabled" class="sound-off-line"></span>
            </div>
          </el-tooltip>
          <!-- 浏览器通知开关 -->
          <el-tooltip :content="orderStore.notificationEnabled ? '关闭浏览器桌面通知' : '开启浏览器桌面通知'" placement="bottom">
            <div class="sound-toggle" :class="{ disabled: !orderStore.notificationEnabled }" @click="toggleBrowserNotification">
              <el-icon :size="18"><Message /></el-icon>
              <span v-if="!orderStore.notificationEnabled" class="sound-off-line"></span>
            </div>
          </el-tooltip>
          <el-dropdown @command="handleCommand">
            <span class="admin-info">
              <el-avatar :size="32" :icon="UserFilled" />
              <span class="admin-name">{{ adminInfo?.nickname || adminInfo?.username || '管理员' }}</span>
              <el-icon><ArrowDown /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile">个人信息</el-dropdown-item>
                <el-dropdown-item command="password">修改密码</el-dropdown-item>
                <el-dropdown-item divided command="logout">退出登录</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-header>
      <el-main class="main">
        <router-view />
      </el-main>
    </el-container>

    <!-- 修改密码对话框 -->
    <el-dialog v-model="passwordDialogVisible" title="修改密码" width="400px">
      <el-form ref="passwordFormRef" :model="passwordForm" :rules="passwordRules" label-width="80px">
        <el-form-item label="原密码" prop="old_password">
          <el-input v-model="passwordForm.old_password" type="password" show-password />
        </el-form-item>
        <el-form-item label="新密码" prop="new_password">
          <el-input v-model="passwordForm.new_password" type="password" show-password />
        </el-form-item>
        <el-form-item label="确认密码" prop="confirm_password">
          <el-input v-model="passwordForm.confirm_password" type="password" show-password />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="passwordDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleChangePassword" :loading="passwordLoading">确定</el-button>
      </template>
    </el-dialog>
  </el-container>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import {
  Folder, List, Operation, Goods, Document, User, Setting, Tools,
  UserFilled, Key, SetUp, Notebook, DataAnalysis, Present, Ticket,
  Picture, Discount, Money, Tickets, Wallet, Coin, ArrowDown, Delete,
  Service, Avatar, ChatDotRound, ChatLineSquare, ChatDotSquare,
  CreditCard, Calendar, Files, Brush, Medal, Timer, Van, Ship, Location,
  Bell, Message, Collection, QuestionFilled, RefreshRight, TrophyBase,
  Monitor, Iphone, TrendCharts, Odometer, Filter, ShoppingCart
} from '@element-plus/icons-vue'
import { logout, changePassword } from '@/api/auth'
import { useOrderStore } from '@/stores/order'
import { useOcbcStore } from '@/stores/ocbc'

const route = useRoute()
const router = useRouter()
const orderStore = useOrderStore()
const ocbcStore = useOcbcStore()

// 获取管理员信息
const adminInfo = computed(() => {
  const info = localStorage.getItem('admin_info')
  return info ? JSON.parse(info) : null
})

// 修改密码相关
const passwordDialogVisible = ref(false)
const passwordFormRef = ref<FormInstance>()
const passwordLoading = ref(false)
const passwordForm = reactive({
  old_password: '',
  new_password: '',
  confirm_password: ''
})

const validateConfirmPassword = (rule: any, value: string, callback: Function) => {
  if (value !== passwordForm.new_password) {
    callback(new Error('两次输入的密码不一致'))
  } else {
    callback()
  }
}

const passwordRules: FormRules = {
  old_password: [{ required: true, message: '请输入原密码', trigger: 'blur' }],
  new_password: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 6, message: '密码长度不能少于6位', trigger: 'blur' }
  ],
  confirm_password: [
    { required: true, message: '请确认新密码', trigger: 'blur' },
    { validator: validateConfirmPassword, trigger: 'blur' }
  ]
}

const handleCommand = (command: string) => {
  switch (command) {
    case 'profile':
      ElMessage.info('个人信息功能开发中')
      break
    case 'password':
      passwordForm.old_password = ''
      passwordForm.new_password = ''
      passwordForm.confirm_password = ''
      passwordDialogVisible.value = true
      break
    case 'logout':
      handleLogout()
      break
  }
}

const handleChangePassword = async () => {
  if (!passwordFormRef.value) return
  await passwordFormRef.value.validate(async (valid) => {
    if (!valid) return
    passwordLoading.value = true
    try {
      await changePassword({
        old_password: passwordForm.old_password,
        new_password: passwordForm.new_password
      })
      ElMessage.success('密码修改成功，请重新登录')
      passwordDialogVisible.value = false
      // 清除登录信息并跳转登录页
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_info')
      router.push('/login')
    } catch (error) {
      console.error('修改密码失败:', error)
    } finally {
      passwordLoading.value = false
    }
  })
}

const handleLogout = async () => {
  try {
    await ElMessageBox.confirm('确定要退出登录吗？', '提示', { type: 'warning' })
    await logout()
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_info')
    ElMessage.success('已退出登录')
    router.push('/login')
  } catch (error) {
    if (error !== 'cancel') {
      console.error('退出登录失败:', error)
    }
  }
}

// 切换新订单提示音
const toggleOrderSound = () => {
  orderStore.toggleSound()
  ElMessage.success(orderStore.soundEnabled ? '新订单提示音已开启' : '新订单提示音已关闭')
}

// 切换浏览器桌面通知
const toggleBrowserNotification = () => {
  orderStore.toggleNotification()
  ElMessage.success(orderStore.notificationEnabled ? '浏览器桌面通知已开启' : '浏览器桌面通知已关闭')
}

// 启动订单轮询（包含 OCBC 账户数据）
onMounted(() => {
  // 初始化新订单提示音
  orderStore.initAudio()
  orderStore.startPolling()

  // 初始化 OCBC 账户提示音（数据从 orderStore 获取）
  ocbcStore.initAudio()
})

// 停止订单轮询
onUnmounted(() => {
  orderStore.stopPolling()
})
</script>

<style scoped>
.layout-container {
  height: 100%;
}

.aside {
  background-color: #304156;
}

.logo {
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #263445;
}

.logo h2 {
  color: #fff;
  font-size: 16px;
  margin: 0;
}

.header {
  background-color: #fff;
  border-bottom: 1px solid #e6e6e6;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.page-title {
  font-size: 18px;
  font-weight: 500;
}

.header-right {
  display: flex;
  align-items: center;
}

.admin-info {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: #606266;
}

.admin-name {
  margin: 0 8px;
  font-size: 14px;
}

.main {
  background-color: #f5f7fa;
  padding: 20px;
}

.el-menu {
  border-right: none;
}

/* 订单菜单徽标 */
.menu-badge {
  margin-left: 8px;
  vertical-align: middle;
}

.menu-badge :deep(.el-badge__content) {
  transform: translateY(-14px);
}

/* 新订单提醒 */
.order-notification {
  margin-right: 12px;
  cursor: pointer;
}

.notification-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  transition: all 0.2s ease;
}

/* 订单通知图标 - 橙色 */
.notification-icon.order {
  background: linear-gradient(135deg, #FF9500 0%, #FF6B00 100%);
}

.notification-icon.order:hover {
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(255, 107, 0, 0.4);
}

/* OCBC账户通知图标 - 红色 */
.notification-icon.ocbc {
  background: linear-gradient(135deg, #E53935 0%, #C62828 100%);
}

.notification-icon.ocbc:hover {
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(198, 40, 40, 0.4);
}

/* 新卡待审核通知图标 - 蓝色 */
.notification-icon.card {
  background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%);
}

.notification-icon.card:hover {
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(21, 101, 192, 0.4);
}

/* 信用申请通知图标 - 紫色 */
.notification-icon.credit {
  background: linear-gradient(135deg, #7B1FA2 0%, #6A1B9A 100%);
}

.notification-icon.credit:hover {
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(106, 27, 154, 0.4);
}

/* 声音开关 */
.sound-toggle {
  position: relative;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #f0f2f5;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  margin-right: 16px;
  color: #409eff;
  transition: all 0.2s ease;
}

.sound-toggle:hover {
  background: #e6f0ff;
}

.sound-toggle.disabled {
  color: #909399;
}

.sound-toggle.disabled:hover {
  background: #f5f5f5;
}

.sound-off-line {
  position: absolute;
  width: 22px;
  height: 2px;
  background: #909399;
  transform: rotate(-45deg);
  border-radius: 1px;
}
</style>
