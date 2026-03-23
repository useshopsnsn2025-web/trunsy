<template>
  <div class="cache-manage">
    <!-- 缓存状态卡片 -->
    <el-row :gutter="20" class="status-cards">
      <el-col :span="8">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">
              <el-icon><FolderOpened /></el-icon>
              <span>数据缓存</span>
            </div>
          </template>
          <div class="card-content">
            <div class="size">{{ cacheStatus.cache_size_text || '0 B' }}</div>
            <div class="desc">包含数据缓存、配置缓存等</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">
              <el-icon><Files /></el-icon>
              <span>临时文件</span>
            </div>
          </template>
          <div class="card-content">
            <div class="size">{{ cacheStatus.temp_size_text || '0 B' }}</div>
            <div class="desc">包含视图缓存、编译文件等</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <template #header>
            <div class="card-header">
              <el-icon><Document /></el-icon>
              <span>日志文件</span>
            </div>
          </template>
          <div class="card-content">
            <div class="size">{{ cacheStatus.log_size_text || '0 B' }}</div>
            <div class="desc">系统运行日志文件</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 操作区域 -->
    <el-card class="action-card">
      <template #header>
        <span>缓存操作</span>
      </template>

      <el-row :gutter="20">
        <el-col :span="12">
          <div class="action-section">
            <h4>清理缓存</h4>
            <p class="action-desc">选择要清理的缓存类型，清理后将自动重新生成</p>

            <el-radio-group v-model="selectedCacheType" class="cache-type-group">
              <el-radio value="all">全部缓存</el-radio>
              <el-radio value="data">数据缓存</el-radio>
              <el-radio value="view">视图缓存</el-radio>
              <el-radio value="route">路由缓存</el-radio>
              <el-radio value="config">配置缓存</el-radio>
            </el-radio-group>

            <el-button
              type="primary"
              :loading="clearingCache"
              @click="handleClearCache"
            >
              <el-icon><Delete /></el-icon>
              清理缓存
            </el-button>
          </div>
        </el-col>

        <el-col :span="12">
          <div class="action-section">
            <h4>清理日志</h4>
            <p class="action-desc">清理系统运行日志，可选择保留最近几天的日志</p>

            <el-radio-group v-model="keepLogDays" class="log-days-group">
              <el-radio :value="0">全部清理</el-radio>
              <el-radio :value="7">保留7天</el-radio>
              <el-radio :value="30">保留30天</el-radio>
              <el-radio :value="90">保留90天</el-radio>
            </el-radio-group>

            <el-button
              type="warning"
              :loading="clearingLogs"
              @click="handleClearLogs"
            >
              <el-icon><DeleteFilled /></el-icon>
              清理日志
            </el-button>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <!-- 说明信息 -->
    <el-card class="info-card">
      <template #header>
        <span>说明</span>
      </template>
      <el-descriptions :column="1" border>
        <el-descriptions-item label="数据缓存">
          系统数据缓存，包括数据库查询结果、API响应等临时存储数据
        </el-descriptions-item>
        <el-descriptions-item label="视图缓存">
          模板编译后的缓存文件，清理后首次访问会重新编译
        </el-descriptions-item>
        <el-descriptions-item label="路由缓存">
          路由规则的缓存文件，修改路由配置后建议清理
        </el-descriptions-item>
        <el-descriptions-item label="配置缓存">
          系统配置的缓存文件，修改配置后建议清理
        </el-descriptions-item>
        <el-descriptions-item label="日志文件">
          系统运行产生的日志文件，定期清理可释放磁盘空间
        </el-descriptions-item>
      </el-descriptions>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { FolderOpened, Files, Document, Delete, DeleteFilled } from '@element-plus/icons-vue'
import { getCacheStatus, clearCache, clearLogs, type CacheStatus } from '@/api/cache'

const cacheStatus = reactive<CacheStatus>({
  cache_size: 0,
  cache_size_text: '0 B',
  temp_size: 0,
  temp_size_text: '0 B',
  log_size: 0,
  log_size_text: '0 B',
  cache_types: {}
})

const selectedCacheType = ref('all')
const keepLogDays = ref(0)
const clearingCache = ref(false)
const clearingLogs = ref(false)

// 加载缓存状态
const loadCacheStatus = async () => {
  try {
    const res: any = await getCacheStatus()
    Object.assign(cacheStatus, res.data)
  } catch (error) {
    console.error('获取缓存状态失败:', error)
  }
}

// 清理缓存
const handleClearCache = async () => {
  try {
    await ElMessageBox.confirm(
      `确定要清理${selectedCacheType.value === 'all' ? '全部' : '选中的'}缓存吗？`,
      '确认清理',
      { type: 'warning' }
    )

    clearingCache.value = true
    await clearCache(selectedCacheType.value)
    ElMessage.success('缓存清理成功')
    loadCacheStatus()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('清理缓存失败:', error)
    }
  } finally {
    clearingCache.value = false
  }
}

// 清理日志
const handleClearLogs = async () => {
  const msg = keepLogDays.value === 0
    ? '确定要清理全部日志文件吗？'
    : `确定要清理 ${keepLogDays.value} 天前的日志文件吗？`

  try {
    await ElMessageBox.confirm(msg, '确认清理', { type: 'warning' })

    clearingLogs.value = true
    const res: any = await clearLogs(keepLogDays.value)
    ElMessage.success(`已清理 ${res.data.cleared_count} 个日志文件，释放 ${res.data.cleared_size_text}`)
    loadCacheStatus()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('清理日志失败:', error)
    }
  } finally {
    clearingLogs.value = false
  }
}

onMounted(() => {
  loadCacheStatus()
})
</script>

<style scoped>
.cache-manage {
  padding: 0;
}

.status-cards {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}

.card-content {
  text-align: center;
  padding: 10px 0;
}

.card-content .size {
  font-size: 28px;
  font-weight: bold;
  color: #409EFF;
  margin-bottom: 8px;
}

.card-content .desc {
  font-size: 12px;
  color: #909399;
}

.action-card {
  margin-bottom: 20px;
}

.action-section {
  padding: 20px;
  background: #fafafa;
  border-radius: 8px;
}

.action-section h4 {
  margin: 0 0 10px 0;
  font-size: 16px;
}

.action-desc {
  color: #909399;
  font-size: 13px;
  margin-bottom: 15px;
}

.cache-type-group,
.log-days-group {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 20px;
}

.info-card {
  margin-bottom: 20px;
}

:deep(.el-descriptions__label) {
  width: 120px;
  font-weight: 500;
}
</style>
