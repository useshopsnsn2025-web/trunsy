<template>
  <div class="operation-log">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="statistics-row">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-content">
            <div class="stat-icon total">
              <el-icon><Document /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.total_logs }}</div>
              <div class="stat-label">总日志数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-content">
            <div class="stat-icon today">
              <el-icon><Calendar /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.today_logs }}</div>
              <div class="stat-label">今日日志</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-content">
            <div class="stat-icon week">
              <el-icon><TrendCharts /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.week_logs }}</div>
              <div class="stat-label">本周日志</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-content">
            <div class="stat-icon modules">
              <el-icon><Menu /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ statistics.module_stats?.length || 0 }}</div>
              <div class="stat-label">模块数量</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card" shadow="never">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="管理员/URL/IP"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="模块">
          <el-select v-model="searchForm.module" placeholder="全部" clearable>
            <el-option
              v-for="m in modules"
              :key="m"
              :label="m"
              :value="m"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="操作">
          <el-input v-model="searchForm.action" placeholder="操作名称" clearable />
        </el-form-item>
        <el-form-item label="日期范围">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            @change="handleDateChange"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格 -->
    <el-card class="table-card" shadow="never">
      <template #header>
        <div class="card-header">
          <span>操作日志</span>
          <el-button type="danger" @click="handleClear">
            <el-icon><Delete /></el-icon>
            清理日志
          </el-button>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="admin_name" label="管理员" width="120" />
        <el-table-column prop="module" label="模块" width="100">
          <template #default="{ row }">
            <el-tag size="small">{{ row.module }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="action" label="操作" width="120" />
        <el-table-column prop="method" label="方法" width="80">
          <template #default="{ row }">
            <el-tag :type="getMethodType(row.method)" size="small">
              {{ row.method }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="url" label="URL" min-width="250" show-overflow-tooltip />
        <el-table-column prop="ip" label="IP地址" width="130" />
        <el-table-column prop="created_at" label="操作时间" width="170" />
        <el-table-column label="操作" width="80" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleView(row)">详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="日志详情" width="700px">
      <el-descriptions :column="2" border v-if="currentLog">
        <el-descriptions-item label="ID">{{ currentLog.id }}</el-descriptions-item>
        <el-descriptions-item label="管理员">{{ currentLog.admin_name }}</el-descriptions-item>
        <el-descriptions-item label="模块">{{ currentLog.module }}</el-descriptions-item>
        <el-descriptions-item label="操作">{{ currentLog.action }}</el-descriptions-item>
        <el-descriptions-item label="请求方法">
          <el-tag :type="getMethodType(currentLog.method)" size="small">
            {{ currentLog.method }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="IP地址">{{ currentLog.ip }}</el-descriptions-item>
        <el-descriptions-item label="请求URL" :span="2">{{ currentLog.url }}</el-descriptions-item>
        <el-descriptions-item label="操作时间" :span="2">{{ currentLog.created_at }}</el-descriptions-item>
        <el-descriptions-item label="User-Agent" :span="2">{{ currentLog.user_agent }}</el-descriptions-item>
        <el-descriptions-item label="请求参数" :span="2">
          <pre class="json-content">{{ formatJson(currentLog.params) }}</pre>
        </el-descriptions-item>
        <el-descriptions-item label="响应结果" :span="2">
          <pre class="json-content">{{ formatJson(currentLog.result) }}</pre>
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <!-- 清理对话框 -->
    <el-dialog v-model="clearDialogVisible" title="清理日志" width="400px">
      <el-form label-width="120px">
        <el-form-item label="保留天数">
          <el-input-number v-model="clearDays" :min="7" :max="365" />
          <span style="margin-left: 10px; color: #999;">天</span>
        </el-form-item>
        <el-alert
          type="warning"
          :closable="false"
          show-icon
          style="margin-top: 10px;"
        >
          将删除 {{ clearDays }} 天前的所有日志，此操作不可恢复！
        </el-alert>
      </el-form>
      <template #footer>
        <el-button @click="clearDialogVisible = false">取消</el-button>
        <el-button type="danger" @click="handleClearSubmit" :loading="clearing">确认清理</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Document, Calendar, TrendCharts, Menu, Delete } from '@element-plus/icons-vue'
import {
  getOperationLogList,
  getOperationLogDetail,
  getOperationLogModules,
  getOperationLogStatistics,
  clearOperationLogs,
  type OperationLog,
  type LogStatistics
} from '@/api/operationLog'

// 统计数据
const statistics = ref<LogStatistics>({
  total_logs: 0,
  today_logs: 0,
  week_logs: 0,
  module_stats: [],
  daily_stats: []
})

// 模块列表
const modules = ref<string[]>([])

// 搜索表单
const searchForm = reactive({
  keyword: '',
  module: '',
  action: '',
  start_date: '',
  end_date: ''
})

const dateRange = ref<[string, string] | null>(null)

// 列表数据
const list = ref<OperationLog[]>([])
const loading = ref(false)
const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 详情
const detailDialogVisible = ref(false)
const currentLog = ref<OperationLog | null>(null)

// 清理
const clearDialogVisible = ref(false)
const clearDays = ref(30)
const clearing = ref(false)

// 获取方法标签类型
const getMethodType = (method: string) => {
  const types: Record<string, string> = {
    GET: 'success',
    POST: 'primary',
    PUT: 'warning',
    DELETE: 'danger'
  }
  return types[method] || 'info'
}

// 格式化JSON
const formatJson = (data: any) => {
  if (!data) return '-'
  try {
    if (typeof data === 'string') {
      data = JSON.parse(data)
    }
    return JSON.stringify(data, null, 2)
  } catch {
    return String(data)
  }
}

// 获取统计数据
const fetchStatistics = async () => {
  try {
    const res: any = await getOperationLogStatistics()
    statistics.value = res.data || {}
  } catch (error) {
    console.error('获取统计数据失败:', error)
  }
}

// 获取模块列表
const fetchModules = async () => {
  try {
    const res: any = await getOperationLogModules()
    modules.value = res.data || []
  } catch (error) {
    console.error('获取模块列表失败:', error)
  }
}

// 获取列表
const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getOperationLogList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchForm
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取日志列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 日期范围变化
const handleDateChange = (val: [string, string] | null) => {
  if (val) {
    searchForm.start_date = val[0]
    searchForm.end_date = val[1]
  } else {
    searchForm.start_date = ''
    searchForm.end_date = ''
  }
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  fetchList()
}

// 重置
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.module = ''
  searchForm.action = ''
  searchForm.start_date = ''
  searchForm.end_date = ''
  dateRange.value = null
  handleSearch()
}

// 分页
const handleSizeChange = (size: number) => {
  pagination.pageSize = size
  fetchList()
}

const handleCurrentChange = (page: number) => {
  pagination.page = page
  fetchList()
}

// 查看详情
const handleView = async (row: OperationLog) => {
  try {
    const res: any = await getOperationLogDetail(row.id)
    currentLog.value = res.data.list ? res.data.list[0] : res.data
    detailDialogVisible.value = true
  } catch (error) {
    console.error('获取日志详情失败:', error)
  }
}

// 清理日志
const handleClear = () => {
  clearDays.value = 30
  clearDialogVisible.value = true
}

const handleClearSubmit = async () => {
  try {
    await ElMessageBox.confirm(
      `确定要清理 ${clearDays.value} 天前的所有日志吗？此操作不可恢复！`,
      '警告',
      { type: 'warning' }
    )
    clearing.value = true
    const res: any = await clearOperationLogs(clearDays.value)
    ElMessage.success(res.message || '清理成功')
    clearDialogVisible.value = false
    fetchList()
    fetchStatistics()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('清理失败:', error)
    }
  } finally {
    clearing.value = false
  }
}

onMounted(() => {
  fetchStatistics()
  fetchModules()
  fetchList()
})
</script>

<style scoped>
.operation-log {
  padding: 20px;
}

.statistics-row {
  margin-bottom: 20px;
}

.stat-card {
  cursor: pointer;
  transition: transform 0.3s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-content {
  display: flex;
  align-items: center;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: #fff;
  margin-right: 15px;
}

.stat-icon.total {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.today {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.week {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.modules {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #303133;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  margin-top: 4px;
}

.search-card {
  margin-bottom: 20px;
}

.search-form {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.json-content {
  background: #f5f7fa;
  padding: 10px;
  border-radius: 4px;
  max-height: 200px;
  overflow: auto;
  margin: 0;
  font-size: 12px;
  white-space: pre-wrap;
  word-break: break-all;
}
</style>
