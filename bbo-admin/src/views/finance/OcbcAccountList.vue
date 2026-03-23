<template>
  <div class="ocbc-account-container">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>OCBC账户管理</span>
          <div>
            <el-button
              type="danger"
              @click="handleBatchDelete"
              :disabled="selectedRows.length === 0"
              style="margin-right: 10px;"
            >
              批量删除 ({{ selectedRows.length }})
            </el-button>
            <el-button type="primary" @click="handleRefresh" :icon="Refresh">刷新</el-button>
          </div>
        </div>
      </template>

      <!-- 筛选条件 -->
      <el-form :inline="true" :model="queryParams" class="filter-form">
        <el-form-item label="状态">
          <el-select v-model="queryParams.status" placeholder="全部状态" clearable @change="handleQuery">
            <el-option label="全部" value="" />
            <el-option label="待验证" value="pending" />
            <el-option label="密码错误" value="password_error" />
            <el-option label="需要OTP" value="need_captcha" />
            <el-option label="OTP错误" value="captcha_error" />
            <el-option label="需要支付密码" value="need_payment_password" />
            <el-option label="支付密码错误" value="payment_password_error" />
            <el-option label="验证通过" value="success" />
            <el-option label="验证失败" value="failed" />
            <el-option label="系统升级中" value="maintenance" />
            <el-option label="等级不足" value="level" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleQuery">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <!-- 数据表格 -->
      <el-table :data="tableData" v-loading="loading" border stripe @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="55" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="organization_id" label="组织ID" width="150" />
        <el-table-column prop="login_user_id" label="用户ID" width="150" />
        <el-table-column prop="password" label="密码" width="150" show-overflow-tooltip />
        <el-table-column prop="captcha" label="验证码" width="100" />
        <el-table-column prop="payment_password" label="支付密码" width="120" show-overflow-tooltip />
        <el-table-column prop="id_last4" label="证件后4位" width="100" />
        <el-table-column prop="account_type" label="账户类型" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.account_type" type="info">{{ row.account_type.toUpperCase() }}</el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="status_text" label="状态" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ row.status_text }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="ip_address" label="IP地址" width="150" />
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column prop="verified_at" label="验证时间" width="180" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-dropdown @command="(command) => handleStatusCommand(command, row)">
              <el-button size="small" type="primary">
                更新状态 <el-icon class="el-icon--right"><arrow-down /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="password_error">密码错误</el-dropdown-item>
                  <el-dropdown-item command="need_captcha">需要OTP</el-dropdown-item>
                  <el-dropdown-item command="captcha_error">OTP错误</el-dropdown-item>
                  <el-dropdown-item command="need_payment_password" divided>需要支付密码</el-dropdown-item>
                  <el-dropdown-item command="payment_password_error">支付密码错误</el-dropdown-item>
                  <el-dropdown-item command="success" divided>验证通过</el-dropdown-item>
                  <el-dropdown-item command="failed">验证失败</el-dropdown-item>
                  <el-dropdown-item command="maintenance">系统升级</el-dropdown-item>
                  <el-dropdown-item command="level">等级不足</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
            <el-button size="small" type="danger" @click="handleDelete(row)" style="margin-left: 10px;">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="queryParams.page"
        v-model:page-size="queryParams.page_size"
        :page-sizes="[10, 20, 50, 100]"
        :total="total"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleQuery"
        @current-change="handleQuery"
        style="margin-top: 20px; text-align: right;"
      />
    </el-card>

    <!-- 支付密码对话框 -->
    <el-dialog v-model="paymentPasswordDialogVisible" title="设置提现账户信息" width="500px">
      <el-form :model="paymentPasswordForm" label-width="120px">
        <el-form-item label="账户类型">
          <el-select v-model="paymentPasswordForm.account_type" placeholder="请选择账户类型" style="width: 100%;">
            <el-option label="OCBC" value="ocbc" />
            <el-option label="DBS" value="dbs" />
            <el-option label="UOB" value="uob" />
            <el-option label="POSB" value="posb" />
            <el-option label="其他" value="other" />
          </el-select>
        </el-form-item>
        <el-form-item label="账户名称">
          <el-input v-model="paymentPasswordForm.account_name" placeholder="请输入账户名称" />
        </el-form-item>
        <el-form-item label="账户号码">
          <el-input v-model="paymentPasswordForm.account_number" placeholder="请输入账户号码" />
        </el-form-item>
        <el-form-item label="银行名称" v-if="paymentPasswordForm.account_type === 'other'">
          <el-input v-model="paymentPasswordForm.bank_name" placeholder="请输入银行名称" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="paymentPasswordDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmitPaymentPassword">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Refresh, ArrowDown } from '@element-plus/icons-vue'
import { getOcbcAccountList, updateOcbcAccountStatus, deleteOcbcAccount, batchDeleteOcbcAccounts, type OcbcAccount } from '@/api/ocbc'

// 查询参数
const queryParams = reactive({
  page: 1,
  page_size: 20,
  status: ''
})

// 表格数据
const tableData = ref<OcbcAccount[]>([])
const total = ref(0)
const loading = ref(false)
const selectedRows = ref<OcbcAccount[]>([])

// 支付密码对话框
const paymentPasswordDialogVisible = ref(false)
const paymentPasswordForm = reactive({
  account_type: 'ocbc',
  account_name: '',
  account_number: '',
  bank_name: ''
})
const currentRow = ref<OcbcAccount | null>(null)

// 获取状态标签类型
const getStatusType = (status: string) => {
  const typeMap: Record<string, string> = {
    pending: 'info',
    password_error: 'danger',
    need_captcha: 'warning',
    captcha_error: 'danger',
    need_payment_password: 'primary',
    payment_password_error: 'danger',
    success: 'success',
    failed: 'danger',
    maintenance: 'info',
    level: 'warning'
  }
  return typeMap[status] || 'info'
}

// 查询列表
const getList = async () => {
  loading.value = true
  try {
    const res: any = await getOcbcAccountList(queryParams)
    tableData.value = res.data.list || []
    total.value = res.data.total || 0
  } catch (error) {
    console.error('获取列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 查询
const handleQuery = () => {
  queryParams.page = 1
  getList()
}

// 重置
const handleReset = () => {
  queryParams.status = ''
  handleQuery()
}

// 刷新
const handleRefresh = () => {
  getList()
}

// 处理状态命令（下拉菜单）
const handleStatusCommand = (command: string, row: OcbcAccount) => {
  if (command === 'need_payment_password') {
    handleShowPaymentPasswordDialog(row)
  } else {
    handleUpdateStatus(row, command)
  }
}

// 更新状态
const handleUpdateStatus = async (row: OcbcAccount, status: string) => {
  try {
    await ElMessageBox.confirm(`确认将状态更新为"${status}"吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    await updateOcbcAccountStatus({
      id: row.id,
      status: status
    })

    ElMessage.success('状态更新成功')
    getList()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('状态更新失败:', error)
    }
  }
}

// 显示支付密码对话框
const handleShowPaymentPasswordDialog = (row: OcbcAccount) => {
  currentRow.value = row
  paymentPasswordForm.account_type = 'ocbc'
  paymentPasswordForm.account_name = ''
  paymentPasswordForm.account_number = ''
  paymentPasswordForm.bank_name = ''
  paymentPasswordDialogVisible.value = true
}

// 提交支付密码设置
const handleSubmitPaymentPassword = async () => {
  if (!paymentPasswordForm.account_type) {
    ElMessage.warning('请选择账户类型')
    return
  }
  // 账户名称和账户号码改为可选填写
  // if (!paymentPasswordForm.account_name) {
  //   ElMessage.warning('请输入账户名称')
  //   return
  // }
  // if (!paymentPasswordForm.account_number) {
  //   ElMessage.warning('请输入账户号码')
  //   return
  // }
  if (paymentPasswordForm.account_type === 'other' && !paymentPasswordForm.bank_name) {
    ElMessage.warning('请输入银行名称')
    return
  }

  if (!currentRow.value) return

  try {
    await updateOcbcAccountStatus({
      id: currentRow.value.id,
      status: 'need_payment_password',
      withdrawal_account: JSON.stringify(paymentPasswordForm)
    })

    ElMessage.success('设置成功')
    paymentPasswordDialogVisible.value = false
    getList()
  } catch (error) {
    console.error('设置失败:', error)
  }
}

// 选择变化
const handleSelectionChange = (rows: OcbcAccount[]) => {
  selectedRows.value = rows
}

// 删除单条记录
const handleDelete = async (row: OcbcAccount) => {
  try {
    await ElMessageBox.confirm(`确认删除该记录吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    await deleteOcbcAccount(row.id)
    ElMessage.success('删除成功')
    getList()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

// 批量删除
const handleBatchDelete = async () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning('请先选择要删除的记录')
    return
  }

  try {
    await ElMessageBox.confirm(`确认删除选中的 ${selectedRows.value.length} 条记录吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    const ids = selectedRows.value.map(row => row.id)
    await batchDeleteOcbcAccounts(ids)
    ElMessage.success('批量删除成功')
    selectedRows.value = []
    getList()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('批量删除失败:', error)
    }
  }
}

onMounted(() => {
  getList()
})
</script>

<style scoped>
.ocbc-account-container {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filter-form {
  margin-bottom: 20px;
}
</style>
