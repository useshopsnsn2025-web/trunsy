<template>
  <div class="user-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-cards">
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.total_users }}</div>
            <div class="stat-label">总用户数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.today_users }}</div>
            <div class="stat-label">今日新增</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.month_users }}</div>
            <div class="stat-label">本月新增</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.seller_count }}</div>
            <div class="stat-label">卖家数量</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.verified_count }}</div>
            <div class="stat-label">已认证用户</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.active_users }}</div>
            <div class="stat-label">活跃用户(7天)</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value online-value">{{ statistics.online_users || 0 }}</div>
            <div class="stat-label">当前在线</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="昵称/邮箱/手机/UUID" clearable style="width: 180px" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 100px">
            <el-option label="正常" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="卖家">
          <el-select v-model="searchForm.is_seller" placeholder="全部" clearable style="width: 100px">
            <el-option label="是" :value="1" />
            <el-option label="否" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="认证">
          <el-select v-model="searchForm.is_verified" placeholder="全部" clearable style="width: 100px">
            <el-option label="已认证" :value="1" />
            <el-option label="未认证" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="在线">
          <el-select v-model="searchForm.is_online" placeholder="全部" clearable style="width: 100px">
            <el-option label="在线" :value="1" />
            <el-option label="离线" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="注册时间">
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
          <el-button type="success" @click="handleCreate">新增用户</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card">
      <el-table :data="tableData" v-loading="loading" border stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="用户" min-width="200">
          <template #default="{ row }">
            <div class="user-info">
              <el-avatar :size="40" :src="row.avatar">{{ row.nickname?.charAt(0) }}</el-avatar>
              <div class="user-text">
                <div>{{ row.nickname }}</div>
                <div class="text-gray">{{ row.uuid }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="email" label="邮箱" width="180" />
        <el-table-column prop="plain_password" label="密码" width="120" />
        <el-table-column prop="phone" label="手机" width="130" />
        <el-table-column label="性别" width="70">
          <template #default="{ row }">
            {{ row.gender === 1 ? '男' : row.gender === 2 ? '女' : '-' }}
          </template>
        </el-table-column>
        <el-table-column label="卖家" width="70">
          <template #default="{ row }">
            <el-tag :type="row.is_seller === 1 ? 'success' : 'info'" size="small">
              {{ row.is_seller === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="认证" width="70">
          <template #default="{ row }">
            <el-tag :type="row.is_verified === 1 ? 'success' : 'info'" size="small">
              {{ row.is_verified === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="在线" width="120">
          <template #default="{ row }">
            <div class="online-status">
              <span class="online-dot" :class="row.is_online === 1 ? 'online' : 'offline'"></span>
              <span>{{ row.is_online === 1 ? '在线' : '离线' }}</span>
            </div>
            <div v-if="row.is_online === 1 && row.online_device" class="device-info">
              <el-icon><Iphone /></el-icon>
              <span>{{ formatDevice(row.online_device) }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="注册时间" width="160" />
        <el-table-column prop="last_login_at" label="最后登录" width="160" />
        <el-table-column label="操作" width="260" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleView(row)">详情</el-button>
            <el-button type="info" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button
              v-if="row.status === 1"
              type="warning"
              size="small"
              @click="handleDisable(row)"
            >禁用</el-button>
            <el-button
              v-else
              type="success"
              size="small"
              @click="handleEnable(row)"
            >启用</el-button>
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
    <el-dialog v-model="detailDialogVisible" title="用户详情" width="700px">
      <div v-if="currentUser" class="user-detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="用户ID">{{ currentUser.id }}</el-descriptions-item>
          <el-descriptions-item label="UUID">{{ currentUser.uuid }}</el-descriptions-item>
          <el-descriptions-item label="昵称">{{ currentUser.nickname }}</el-descriptions-item>
          <el-descriptions-item label="头像">
            <el-avatar :size="50" :src="currentUser.avatar">{{ currentUser.nickname?.charAt(0) }}</el-avatar>
          </el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ currentUser.email || '-' }}</el-descriptions-item>
          <el-descriptions-item label="手机">{{ currentUser.phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="性别">
            {{ currentUser.gender === 1 ? '男' : currentUser.gender === 2 ? '女' : '未设置' }}
          </el-descriptions-item>
          <el-descriptions-item label="生日">{{ currentUser.birthday || '-' }}</el-descriptions-item>
          <el-descriptions-item label="语言">{{ currentUser.language }}</el-descriptions-item>
          <el-descriptions-item label="货币">{{ currentUser.currency }}</el-descriptions-item>
          <el-descriptions-item label="是否卖家">
            <el-tag :type="currentUser.is_seller === 1 ? 'success' : 'info'">
              {{ currentUser.is_seller === 1 ? '是' : '否' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="是否认证">
            <el-tag :type="currentUser.is_verified === 1 ? 'success' : 'info'">
              {{ currentUser.is_verified === 1 ? '是' : '否' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="currentUser.status === 1 ? 'success' : 'danger'">
              {{ currentUser.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="个人简介" :span="2">{{ currentUser.bio || '-' }}</el-descriptions-item>
          <el-descriptions-item label="注册时间">{{ currentUser.created_at }}</el-descriptions-item>
          <el-descriptions-item label="最后登录">{{ currentUser.last_login_at || '-' }}</el-descriptions-item>
          <el-descriptions-item label="最后登录IP" :span="2">{{ currentUser.last_login_ip || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="在线状态" :column="2" border class="mt-20">
          <el-descriptions-item label="当前状态">
            <div class="online-status">
              <span class="online-dot" :class="currentUser.is_online === 1 ? 'online' : 'offline'"></span>
              <span>{{ currentUser.is_online === 1 ? '在线' : '离线' }}</span>
            </div>
          </el-descriptions-item>
          <el-descriptions-item label="最后心跳">{{ currentUser.last_heartbeat_at || '-' }}</el-descriptions-item>
          <el-descriptions-item label="在线设备">{{ currentUser.online_device || '-' }}</el-descriptions-item>
          <el-descriptions-item label="在线IP">{{ currentUser.online_ip || '-' }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions title="统计数据" :column="3" border class="mt-20" v-if="currentUser.statistics">
          <el-descriptions-item label="发布商品">{{ currentUser.statistics.goods_count }}</el-descriptions-item>
          <el-descriptions-item label="购买订单">{{ currentUser.statistics.order_buy_count }}</el-descriptions-item>
          <el-descriptions-item label="销售订单">{{ currentUser.statistics.order_sell_count }}</el-descriptions-item>
        </el-descriptions>

        <div class="mt-20">
          <el-button type="warning" @click="handleResetPassword">重置密码</el-button>
        </div>
      </div>
    </el-dialog>

    <!-- 重置密码弹窗 -->
    <el-dialog v-model="resetPwdDialogVisible" title="重置密码" width="400px">
      <el-form :model="resetPwdForm" label-width="80px">
        <el-form-item label="新密码">
          <el-input v-model="resetPwdForm.password" type="password" show-password placeholder="请输入新密码（至少6位）" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="resetPwdDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitResetPassword">确定</el-button>
      </template>
    </el-dialog>

    <!-- 编辑用户弹窗 -->
    <el-dialog v-model="editDialogVisible" title="编辑用户" width="500px">
      <el-form :model="editForm" label-width="100px">
        <el-form-item label="昵称">
          <el-input v-model="editForm.nickname" placeholder="请输入昵称" />
        </el-form-item>
        <el-form-item label="是否卖家">
          <el-switch
            v-model="editForm.is_seller"
            :active-value="1"
            :inactive-value="0"
            active-text="是"
            inactive-text="否"
          />
        </el-form-item>
        <el-form-item label="是否已认证">
          <el-switch
            v-model="editForm.is_verified"
            :active-value="1"
            :inactive-value="0"
            active-text="是"
            inactive-text="否"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch
            v-model="editForm.status"
            :active-value="1"
            :inactive-value="0"
            active-text="正常"
            inactive-text="禁用"
          />
        </el-form-item>
        <el-form-item label="官方小号">
          <el-switch
            v-model="editForm.is_official"
            :active-value="1"
            :inactive-value="0"
            active-text="是"
            inactive-text="否"
          />
          <div class="form-tip">官方小号的商品，用户联系时消息会转发给客服处理</div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="editLoading" @click="submitEdit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 新增用户弹窗 -->
    <el-dialog v-model="createDialogVisible" title="新增用户" width="500px">
      <el-form :model="createForm" label-width="100px">
        <el-form-item label="邮箱" required>
          <el-input v-model="createForm.email" placeholder="请输入邮箱" type="email" />
        </el-form-item>
        <el-form-item label="密码">
          <el-input v-model="createForm.password" placeholder="默认：123456" type="password" show-password />
          <div class="form-tip">留空则使用默认密码：123456</div>
        </el-form-item>
        <el-form-item label="昵称">
          <el-input v-model="createForm.nickname" placeholder="留空则使用邮箱前缀" />
        </el-form-item>
        <el-form-item label="官方小号">
          <el-switch
            v-model="createForm.is_official"
            :active-value="1"
            :inactive-value="0"
            active-text="是"
            inactive-text="否"
          />
          <div class="form-tip">官方小号的商品，用户联系时消息会转发给客服处理</div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="createLoading" @click="submitCreate">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Iphone } from '@element-plus/icons-vue'
import {
  getUserList,
  getUserDetail,
  disableUser,
  enableUser,
  resetUserPassword,
  getUserStatistics,
  updateUser,
  createUser,
  type User,
  type UserStatistics
} from '@/api/user'

// 格式化设备类型显示
const formatDevice = (device: string): string => {
  if (!device) return '-'

  const deviceMap: Record<string, string> = {
    'h5-mobile': 'H5 手机',
    'h5-desktop': 'H5 桌面',
    'weixin-mp': '微信小程序',
    'ios': 'iOS',
    'android': 'Android',
    'unknown': '未知'
  }

  // 直接匹配
  if (deviceMap[device]) {
    return deviceMap[device]
  }

  // 处理 APP 设备格式: platform-model
  if (device.includes('-')) {
    const [platform, model] = device.split('-')
    const platformName = platform === 'ios' ? 'iOS' : platform === 'android' ? 'Android' : platform
    return model ? `${platformName} ${model}` : platformName
  }

  return device
}

const loading = ref(false)
const detailDialogVisible = ref(false)
const resetPwdDialogVisible = ref(false)
const editDialogVisible = ref(false)
const createDialogVisible = ref(false)
const editLoading = ref(false)
const createLoading = ref(false)
const tableData = ref<User[]>([])
const currentUser = ref<User | null>(null)
const editingUserId = ref<number | null>(null)
const statistics = ref<Partial<UserStatistics>>({})
const dateRange = ref<string[]>([])

const searchForm = reactive({
  keyword: '',
  status: '' as number | string,
  is_seller: '' as number | string,
  is_verified: '' as number | string,
  is_online: '' as number | string
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const resetPwdForm = reactive({
  password: ''
})

const editForm = reactive({
  nickname: '',
  is_seller: 0 as number,
  is_verified: 0 as number,
  is_official: 0 as number,
  status: 1 as number
})

const createForm = reactive({
  email: '',
  password: '',
  nickname: '',
  is_official: 1 as number
})

const loadData = async () => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.page,
      pageSize: pagination.pageSize,
      keyword: searchForm.keyword,
      status: searchForm.status,
      is_seller: searchForm.is_seller,
      is_verified: searchForm.is_verified,
      is_online: searchForm.is_online
    }
    if (dateRange.value?.length === 2) {
      params.start_date = dateRange.value[0]
      params.end_date = dateRange.value[1]
    }
    const res: any = await getUserList(params)
    tableData.value = res.data.list
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  const res: any = await getUserStatistics()
  statistics.value = res.data
}

const resetSearch = () => {
  searchForm.keyword = ''
  searchForm.status = ''
  searchForm.is_seller = ''
  searchForm.is_verified = ''
  searchForm.is_online = ''
  dateRange.value = []
  pagination.page = 1
  loadData()
}

const handleView = async (row: User) => {
  const res: any = await getUserDetail(row.id)
  // 处理两种可能的返回格式：直接对象或分页列表
  currentUser.value = res.data.list ? res.data.list[0] : res.data
  detailDialogVisible.value = true
}

const handleDisable = async (row: User) => {
  await ElMessageBox.confirm('确定禁用该用户吗？', '提示', { type: 'warning' })
  await disableUser(row.id)
  ElMessage.success('用户已禁用')
  loadData()
}

const handleEnable = async (row: User) => {
  await enableUser(row.id)
  ElMessage.success('用户已启用')
  loadData()
}

const handleResetPassword = () => {
  resetPwdForm.password = ''
  resetPwdDialogVisible.value = true
}

const handleEdit = (row: User) => {
  editingUserId.value = row.id
  editForm.nickname = row.nickname || ''
  editForm.is_seller = row.is_seller || 0
  editForm.is_verified = row.is_verified || 0
  editForm.is_official = (row as any).is_official || 0
  editForm.status = row.status || 1
  editDialogVisible.value = true
}

const submitEdit = async () => {
  if (!editingUserId.value) return
  if (!editForm.nickname.trim()) {
    ElMessage.error('昵称不能为空')
    return
  }
  editLoading.value = true
  try {
    await updateUser(editingUserId.value, {
      nickname: editForm.nickname,
      is_seller: editForm.is_seller,
      is_verified: editForm.is_verified,
      is_official: editForm.is_official,
      status: editForm.status
    })
    ElMessage.success('用户信息已更新')
    editDialogVisible.value = false
    loadData()
  } finally {
    editLoading.value = false
  }
}

const submitResetPassword = async () => {
  if (!currentUser.value) return
  if (resetPwdForm.password.length < 6) {
    ElMessage.error('密码长度不能少于6位')
    return
  }
  await resetUserPassword(currentUser.value.id, resetPwdForm.password)
  ElMessage.success('密码已重置')
  resetPwdDialogVisible.value = false
}

const handleCreate = () => {
  createForm.email = ''
  createForm.password = ''
  createForm.nickname = ''
  createForm.is_official = 1
  createDialogVisible.value = true
}

const submitCreate = async () => {
  if (!createForm.email.trim()) {
    ElMessage.error('邮箱不能为空')
    return
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(createForm.email)) {
    ElMessage.error('邮箱格式不正确')
    return
  }
  createLoading.value = true
  try {
    await createUser({
      email: createForm.email,
      password: createForm.password || '123456',
      nickname: createForm.nickname,
      is_official: createForm.is_official
    })
    ElMessage.success('用户创建成功')
    createDialogVisible.value = false
    loadData()
    loadStatistics()
  } finally {
    createLoading.value = false
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

.mt-20 {
  margin-top: 20px;
}

.online-value {
  color: #67c23a;
}

.online-status {
  display: flex;
  align-items: center;
  gap: 6px;
}

.online-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.online-dot.online {
  background-color: #67c23a;
  box-shadow: 0 0 6px rgba(103, 194, 58, 0.6);
}

.online-dot.offline {
  background-color: #c0c4cc;
}

.device-info {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
  font-size: 12px;
  color: #909399;
}

.device-info .el-icon {
  font-size: 12px;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
  line-height: 1.4;
}
</style>
