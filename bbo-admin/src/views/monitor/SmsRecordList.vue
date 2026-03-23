<template>
  <div class="sms-record-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-row">
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.total_records || 0 }}</div>
            <div class="stat-label">总记录数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.today_records || 0 }}</div>
            <div class="stat-label">今日上报</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.total_users || 0 }}</div>
            <div class="stat-label">上报用户数</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索区 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="手机号">
          <el-input v-model="searchForm.phone_number" placeholder="发送方号码" clearable style="width: 160px" />
        </el-form-item>
        <el-form-item label="内容关键词">
          <el-input v-model="searchForm.keyword" placeholder="短信内容关键词" clearable style="width: 180px" />
        </el-form-item>
        <el-form-item label="用户ID">
          <el-input v-model="searchForm.user_id" placeholder="用户ID" clearable style="width: 120px" />
        </el-form-item>
        <el-form-item label="接收时间">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            @change="handleDateChange"
            style="width: 240px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card>
      <div style="margin-bottom: 12px" v-if="selectedIds.length > 0">
        <el-button type="danger" size="small" @click="handleBatchDelete">
          批量删除 ({{ selectedIds.length }})
        </el-button>
      </div>

      <el-table :data="list" v-loading="loading" stripe @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="用户" width="160">
          <template #default="{ row }">
            <div v-if="row.user">
              <div>{{ row.user.nickname || row.user.email }}</div>
              <div style="font-size: 12px; color: #909399">ID: {{ row.user_id }}</div>
            </div>
            <span v-else style="color: #909399">用户 #{{ row.user_id }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="phone_number" label="发送方号码" width="150" />
        <el-table-column prop="content" label="短信内容" min-width="300" show-overflow-tooltip />
        <el-table-column prop="received_at" label="接收时间" width="170" />
        <el-table-column prop="device_info" label="设备信息" width="200" show-overflow-tooltip />
        <el-table-column prop="created_at" label="上报时间" width="170" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="showDetail(row)">详情</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
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

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="短信记录详情" width="600px">
      <el-descriptions :column="1" border v-if="currentRecord">
        <el-descriptions-item label="记录ID">{{ currentRecord.id }}</el-descriptions-item>
        <el-descriptions-item label="用户">
          <span v-if="currentRecord.user">
            {{ currentRecord.user.nickname || currentRecord.user.email }} (ID: {{ currentRecord.user_id }})
          </span>
          <span v-else>用户 #{{ currentRecord.user_id }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="发送方号码">{{ currentRecord.phone_number }}</el-descriptions-item>
        <el-descriptions-item label="短信内容">
          <div style="white-space: pre-wrap; word-break: break-all">{{ currentRecord.content }}</div>
        </el-descriptions-item>
        <el-descriptions-item label="接收时间">{{ currentRecord.received_at }}</el-descriptions-item>
        <el-descriptions-item label="设备信息">{{ currentRecord.device_info }}</el-descriptions-item>
        <el-descriptions-item label="上报时间">{{ currentRecord.created_at }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getSmsRecordList, getSmsStatistics,
  deleteSmsRecord, batchDeleteSmsRecords,
  type SmsRecord, type SmsStatistics,
} from '@/api/monitor'

const searchForm = reactive({
  phone_number: '',
  keyword: '',
  user_id: '',
  start_date: '',
  end_date: '',
})
const dateRange = ref<[string, string] | null>(null)
const list = ref<SmsRecord[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const statistics = ref<SmsStatistics>({ total_records: 0, today_records: 0, total_users: 0 })

const detailVisible = ref(false)
const currentRecord = ref<SmsRecord | null>(null)

const handleDateChange = (val: [string, string] | null) => {
  searchForm.start_date = val ? val[0] : ''
  searchForm.end_date = val ? val[1] : ''
}

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getSmsRecordList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...searchForm,
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取短信记录失败:', error)
  } finally {
    loading.value = false
  }
}

const fetchStatistics = async () => {
  try {
    const res: any = await getSmsStatistics()
    statistics.value = res.data || { total_records: 0, today_records: 0, total_users: 0 }
  } catch (error) {
    console.error('获取统计失败:', error)
  }
}

const handleSearch = () => {
  pagination.page = 1
  fetchList()
}

const handleReset = () => {
  searchForm.phone_number = ''
  searchForm.keyword = ''
  searchForm.user_id = ''
  searchForm.start_date = ''
  searchForm.end_date = ''
  dateRange.value = null
  handleSearch()
}

const showDetail = (row: SmsRecord) => {
  currentRecord.value = row
  detailVisible.value = true
}

// 多选
const selectedIds = ref<number[]>([])
const handleSelectionChange = (rows: SmsRecord[]) => {
  selectedIds.value = rows.map(r => r.id)
}

// 单条删除
const handleDelete = async (row: SmsRecord) => {
  try {
    await ElMessageBox.confirm(`确定要删除记录 #${row.id} 吗？`, '确认删除', { type: 'warning' })
  } catch { return }

  try {
    await deleteSmsRecord(row.id)
    ElMessage.success('删除成功')
    fetchList()
    fetchStatistics()
  } catch (error: any) {
    ElMessage.error(error?.response?.data?.msg || '删除失败')
  }
}

// 批量删除
const handleBatchDelete = async () => {
  if (selectedIds.value.length === 0) return

  try {
    await ElMessageBox.confirm(`确定要删除选中的 ${selectedIds.value.length} 条记录吗？`, '批量删除', { type: 'warning' })
  } catch { return }

  try {
    await batchDeleteSmsRecords(selectedIds.value)
    ElMessage.success('批量删除成功')
    selectedIds.value = []
    fetchList()
    fetchStatistics()
  } catch (error: any) {
    ElMessage.error(error?.response?.data?.msg || '批量删除失败')
  }
}

onMounted(() => {
  fetchStatistics()
  fetchList()
})
</script>

<style scoped>
.sms-record-list {
  padding: 0;
}
.stat-row {
  margin-bottom: 20px;
}
.stat-item {
  text-align: center;
  padding: 10px 0;
}
.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #409eff;
}
.stat-label {
  font-size: 14px;
  color: #909399;
  margin-top: 4px;
}
.search-card {
  margin-bottom: 20px;
}
.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
