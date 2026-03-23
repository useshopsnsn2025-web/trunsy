<template>
  <div class="scheduled-task">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>计划任务</span>
          <div class="header-actions">
            <el-button @click="showHelp = true">
              <el-icon><QuestionFilled /></el-icon>
              使用说明
            </el-button>
            <el-button type="primary" @click="handleAdd">
              <el-icon><Plus /></el-icon>
              新增任务
            </el-button>
          </div>
        </div>
      </template>

      <!-- 搜索栏 -->
      <div class="search-bar">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索任务名称或命令"
          clearable
          style="width: 200px"
          @keyup.enter="handleSearch"
        />
        <el-select v-model="searchStatus" placeholder="状态" clearable style="width: 120px">
          <el-option label="启用" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
        <el-button type="primary" @click="handleSearch">搜索</el-button>
        <el-button @click="handleReset">重置</el-button>
      </div>

      <!-- 任务列表 -->
      <el-table :data="taskList" v-loading="loading" stripe>
        <el-table-column prop="name" label="任务名称" width="150" />
        <el-table-column prop="command" label="命令" width="150">
          <template #default="{ row }">
            <el-tag size="small" type="info">{{ row.command }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="执行周期" width="180">
          <template #default="{ row }">
            <div>{{ row.cron_description }}</div>
            <div class="sub-text">{{ row.cron_expression }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="上次执行" width="180">
          <template #default="{ row }">
            <template v-if="row.last_run_at">
              <div>{{ row.last_run_at }}</div>
              <el-tag
                :type="row.last_result === 1 ? 'success' : row.last_result === 0 ? 'danger' : 'info'"
                size="small"
              >
                {{ row.last_result_text }}
              </el-tag>
            </template>
            <span v-else class="text-muted">未执行</span>
          </template>
        </el-table-column>
        <el-table-column label="下次执行" width="160">
          <template #default="{ row }">
            <span v-if="row.next_run_at">{{ row.next_run_at }}</span>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column label="执行统计" width="120">
          <template #default="{ row }">
            <div>执行: {{ row.run_count }} 次</div>
            <div v-if="row.fail_count > 0" class="text-danger">失败: {{ row.fail_count }} 次</div>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="success" link @click="handleRun(row)">执行</el-button>
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="info" link @click="handleViewLogs(row)">日志</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="pageSize"
          :total="total"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next"
          @size-change="fetchList"
          @current-change="fetchList"
        />
      </div>
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="formDialogVisible"
      :title="isEdit ? '编辑任务' : '新增任务'"
      width="600px"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="任务名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入任务名称" />
        </el-form-item>
        <el-form-item label="任务描述">
          <el-input v-model="form.description" type="textarea" :rows="2" placeholder="任务描述（可选）" />
        </el-form-item>
        <el-form-item label="执行命令" prop="command">
          <el-select v-model="form.command" filterable allow-create placeholder="选择或输入命令" style="width: 100%">
            <el-option
              v-for="cmd in commands"
              :key="cmd.command"
              :label="`${cmd.name} (${cmd.command})`"
              :value="cmd.command"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Cron表达式" prop="cron_expression">
          <el-input v-model="form.cron_expression" placeholder="如: 0 8 * * *">
            <template #append>
              <el-dropdown @command="handleCronPreset">
                <el-button>常用</el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item
                      v-for="preset in cronPresets"
                      :key="preset.expression"
                      :command="preset.expression"
                    >
                      {{ preset.name }} ({{ preset.expression }})
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </template>
          </el-input>
          <div class="form-tip">
            格式: 分 时 日 月 周 (例如: 0 8 * * * 表示每天8点)
          </div>
        </el-form-item>
        <el-form-item label="超时时间">
          <el-input-number v-model="form.timeout" :min="10" :max="3600" />
          <span class="form-unit">秒</span>
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
          <span class="switch-label">{{ form.status === 1 ? '启用' : '禁用' }}</span>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 日志对话框 -->
    <el-dialog
      v-model="logDialogVisible"
      :title="`执行日志 - ${currentTask?.name || ''}`"
      width="800px"
    >
      <el-table :data="logList" v-loading="logLoading" stripe max-height="400">
        <el-table-column prop="started_at" label="开始时间" width="160" />
        <el-table-column prop="ended_at" label="结束时间" width="160" />
        <el-table-column label="耗时" width="80">
          <template #default="{ row }">
            {{ row.duration ? `${row.duration}s` : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag
              :type="row.status === 1 ? 'success' : row.status === 2 ? 'danger' : 'warning'"
              size="small"
            >
              {{ row.status_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="输出/错误" min-width="200">
          <template #default="{ row }">
            <div v-if="row.output" class="log-output">{{ row.output }}</div>
            <div v-if="row.error" class="log-error">{{ row.error }}</div>
          </template>
        </el-table-column>
      </el-table>

      <div class="log-pagination">
        <el-pagination
          v-model:current-page="logPage"
          v-model:page-size="logPageSize"
          :total="logTotal"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next"
          @size-change="fetchLogs"
          @current-change="fetchLogs"
        />
      </div>

      <template #footer>
        <el-button @click="handleClearLogs" type="danger">清除30天前日志</el-button>
        <el-button @click="logDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 使用说明对话框 -->
    <el-dialog
      v-model="showHelp"
      title="计划任务使用说明"
      width="700px"
    >
      <div class="help-content">
        <el-alert
          title="重要提示"
          type="warning"
          :closable="false"
          show-icon
          style="margin-bottom: 20px"
        >
          计划任务需要在服务器上配置定时执行调度器命令，任务才能按计划自动运行。
        </el-alert>

        <h4>一、生产环境配置（Linux 服务器）</h4>
        <p>在服务器上使用 crontab 配置，每分钟执行一次调度器：</p>
        <div class="code-block">
          <code>* * * * * cd /www/wwwroot/项目目录/bbo-server && php think schedule:run >> /var/log/scheduler.log 2>&1</code>
          <el-button size="small" @click="copyCode('linux')">复制</el-button>
        </div>
        <p class="help-tip">配置方法：运行 <code>crontab -e</code> 命令，将上面的代码添加到文件末尾保存即可。</p>

        <el-divider />

        <h4>二、宝塔面板配置</h4>
        <ol>
          <li>登录宝塔面板</li>
          <li>进入「计划任务」</li>
          <li>添加任务：
            <ul>
              <li>任务类型：Shell 脚本</li>
              <li>任务名称：TURNSY调度器</li>
              <li>执行周期：每分钟</li>
              <li>脚本内容：<code>cd /www/wwwroot/项目目录/bbo-server && php think schedule:run</code></li>
            </ul>
          </li>
        </ol>

        <el-divider />

        <h4>三、Windows 开发环境（phpStudy）</h4>
        <p>使用 Windows 任务计划程序：</p>
        <ol>
          <li>打开「任务计划程序」（在开始菜单搜索）</li>
          <li>点击「创建基本任务」</li>
          <li>设置名称：TURNSY调度器</li>
          <li>触发器：每天（或根据需要设置）</li>
          <li>操作：启动程序</li>
          <li>程序路径：<code>D:\phpstudy_pro\Extensions\php\php8.x.x\php.exe</code></li>
          <li>参数：<code>D:\phpstudy_pro\WWW\bbo\bbo-server\think schedule:run</code></li>
        </ol>

        <el-divider />

        <h4>四、Cron 表达式说明</h4>
        <p>格式：<code>分 时 日 月 周</code></p>
        <el-table :data="cronExamples" stripe size="small" style="margin-top: 10px">
          <el-table-column prop="expression" label="表达式" width="150" />
          <el-table-column prop="description" label="说明" />
        </el-table>

        <el-divider />

        <h4>五、常见问题</h4>
        <el-collapse>
          <el-collapse-item title="Q: 任务创建后为什么没有自动执行？">
            <p>A: 需要先在服务器上配置调度器（见上方步骤），调度器每分钟检查一次，找到到期的任务才会执行。</p>
          </el-collapse-item>
          <el-collapse-item title="Q: 如何测试任务是否正常？">
            <p>A: 可以点击任务列表中的「执行」按钮，立即手动执行任务，查看执行结果。</p>
          </el-collapse-item>
          <el-collapse-item title="Q: 调度器命令可以手动测试吗？">
            <p>A: 可以。在服务器上运行：<code>php think schedule:run</code>，会立即检查并执行所有到期任务。</p>
          </el-collapse-item>
          <el-collapse-item title="Q: 如何查看任务执行历史？">
            <p>A: 点击任务列表中的「日志」按钮，可以查看该任务的所有执行记录。</p>
          </el-collapse-item>
        </el-collapse>
      </div>
      <template #footer>
        <el-button type="primary" @click="showHelp = false">我知道了</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus, QuestionFilled } from '@element-plus/icons-vue'
import {
  getTaskList,
  getTaskDetail,
  createTask,
  updateTask,
  deleteTask,
  enableTask,
  disableTask,
  runTask,
  getTaskLogs,
  clearTaskLogs,
  getCommands,
  getCronPresets,
  type ScheduledTask,
  type ScheduledTaskLog
} from '@/api/scheduledTask'

// 列表相关
const taskList = ref<ScheduledTask[]>([])
const loading = ref(false)
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)
const searchKeyword = ref('')
const searchStatus = ref<number | string>('')

// 表单相关
const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)

const form = reactive({
  name: '',
  description: '',
  command: '',
  cron_expression: '',
  status: 0,
  timeout: 300,
  is_singleton: 1,
  sort: 0
})

const rules: FormRules = {
  name: [{ required: true, message: '请输入任务名称', trigger: 'blur' }],
  command: [{ required: true, message: '请选择或输入命令', trigger: 'change' }],
  cron_expression: [{ required: true, message: '请输入Cron表达式', trigger: 'blur' }]
}

// 命令和预设
const commands = ref<{ command: string; name: string; description: string }[]>([])
const cronPresets = ref<{ expression: string; name: string }[]>([])

// 使用说明相关
const showHelp = ref(false)
const cronExamples = [
  { expression: '* * * * *', description: '每分钟执行' },
  { expression: '0 * * * *', description: '每小时整点执行' },
  { expression: '0 0 * * *', description: '每天凌晨0点执行' },
  { expression: '0 8 * * *', description: '每天上午8点执行' },
  { expression: '0 8,20 * * *', description: '每天8点和20点执行' },
  { expression: '0 0 * * 0', description: '每周日凌晨执行' },
  { expression: '0 0 1 * *', description: '每月1号凌晨执行' },
  { expression: '*/5 * * * *', description: '每5分钟执行' },
  { expression: '0 */2 * * *', description: '每2小时执行' }
]

const copyCode = (type: string) => {
  let code = ''
  if (type === 'linux') {
    code = '* * * * * cd /www/wwwroot/项目目录/bbo-server && php think schedule:run >> /var/log/scheduler.log 2>&1'
  }
  navigator.clipboard.writeText(code).then(() => {
    ElMessage.success('已复制到剪贴板')
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制')
  })
}

// 日志相关
const logDialogVisible = ref(false)
const logList = ref<ScheduledTaskLog[]>([])
const logLoading = ref(false)
const logPage = ref(1)
const logPageSize = ref(20)
const logTotal = ref(0)
const currentTask = ref<ScheduledTask | null>(null)

// 获取任务列表
const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getTaskList({
      page: page.value,
      pageSize: pageSize.value,
      keyword: searchKeyword.value,
      status: searchStatus.value
    })
    taskList.value = res.data.list || []
    total.value = res.data.total || 0
  } catch (error) {
    console.error('获取任务列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  page.value = 1
  fetchList()
}

// 重置
const handleReset = () => {
  searchKeyword.value = ''
  searchStatus.value = ''
  page.value = 1
  fetchList()
}

// 新增
const handleAdd = () => {
  isEdit.value = false
  formDialogVisible.value = true
  Object.assign(form, {
    name: '',
    description: '',
    command: '',
    cron_expression: '',
    status: 0,
    timeout: 300,
    is_singleton: 1,
    sort: 0
  })
}

// 编辑
const handleEdit = async (row: ScheduledTask) => {
  isEdit.value = true
  editId.value = row.id
  try {
    const res: any = await getTaskDetail(row.id)
    Object.assign(form, res.data)
    formDialogVisible.value = true
  } catch (error) {
    console.error('获取任务详情失败:', error)
    ElMessage.error('获取任务详情失败')
  }
}

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      if (isEdit.value) {
        await updateTask(editId.value, form)
        ElMessage.success('更新成功')
      } else {
        await createTask(form)
        ElMessage.success('创建成功')
      }
      formDialogVisible.value = false
      fetchList()
    } catch (error) {
      console.error('提交失败:', error)
    } finally {
      submitting.value = false
    }
  })
}

// 状态切换
const handleStatusChange = async (row: ScheduledTask) => {
  try {
    if (row.status === 1) {
      await enableTask(row.id)
      ElMessage.success('已启用')
    } else {
      await disableTask(row.id)
      ElMessage.success('已禁用')
    }
    fetchList()
  } catch (error) {
    console.error('状态切换失败:', error)
    // 回滚状态
    row.status = row.status === 1 ? 0 : 1
  }
}

// 删除
const handleDelete = async (row: ScheduledTask) => {
  try {
    await ElMessageBox.confirm(`确定要删除任务"${row.name}"吗？`, '提示', {
      type: 'warning'
    })
    await deleteTask(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
    }
  }
}

// 立即执行
const handleRun = async (row: ScheduledTask) => {
  try {
    await ElMessageBox.confirm(`确定要立即执行任务"${row.name}"吗？`, '提示', {
      type: 'info'
    })
    ElMessage.info('任务执行中...')
    const res: any = await runTask(row.id)
    if (res.code === 0) {
      ElMessage.success(res.msg || '执行成功')
    } else {
      ElMessage.error(res.msg || '执行失败')
    }
    fetchList()
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('执行失败:', error)
      ElMessage.error(error.message || '执行失败')
    }
  }
}

// 查看日志
const handleViewLogs = async (row: ScheduledTask) => {
  currentTask.value = row
  logPage.value = 1
  logDialogVisible.value = true
  await fetchLogs()
}

// 获取日志
const fetchLogs = async () => {
  if (!currentTask.value) return
  logLoading.value = true
  try {
    const res: any = await getTaskLogs(currentTask.value.id, {
      page: logPage.value,
      pageSize: logPageSize.value
    })
    logList.value = res.data.list || []
    logTotal.value = res.data.total || 0
  } catch (error) {
    console.error('获取日志失败:', error)
  } finally {
    logLoading.value = false
  }
}

// 清除日志
const handleClearLogs = async () => {
  if (!currentTask.value) return
  try {
    await ElMessageBox.confirm('确定要清除30天前的日志吗？', '提示', {
      type: 'warning'
    })
    const res: any = await clearTaskLogs(currentTask.value.id, 30)
    ElMessage.success(res.msg || '清除成功')
    fetchLogs()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('清除日志失败:', error)
    }
  }
}

// Cron 预设选择
const handleCronPreset = (expression: string) => {
  form.cron_expression = expression
}

// 初始化
const init = async () => {
  try {
    const [cmdRes, presetRes]: any = await Promise.all([
      getCommands(),
      getCronPresets()
    ])
    commands.value = cmdRes.data || []
    cronPresets.value = presetRes.data || []
  } catch (error) {
    console.error('获取初始数据失败:', error)
  }
}

onMounted(() => {
  init()
  fetchList()
})
</script>

<style scoped>
.scheduled-task {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-bar {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.sub-text {
  font-size: 12px;
  color: #909399;
}

.text-muted {
  color: #909399;
}

.text-danger {
  color: #f56c6c;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.form-unit {
  margin-left: 8px;
  color: #606266;
}

.switch-label {
  margin-left: 8px;
  color: #606266;
}

.log-pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.log-output {
  font-size: 12px;
  color: #67c23a;
  white-space: pre-wrap;
  word-break: break-all;
}

.log-error {
  font-size: 12px;
  color: #f56c6c;
  white-space: pre-wrap;
  word-break: break-all;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.help-content {
  line-height: 1.8;
}

.help-content h4 {
  margin: 16px 0 8px;
  color: #303133;
  font-weight: 600;
}

.help-content p {
  margin: 8px 0;
  color: #606266;
}

.help-content ol,
.help-content ul {
  padding-left: 20px;
  margin: 8px 0;
}

.help-content li {
  margin: 4px 0;
  color: #606266;
}

.help-content code {
  background-color: #f5f7fa;
  padding: 2px 6px;
  border-radius: 4px;
  font-family: Consolas, Monaco, monospace;
  font-size: 13px;
  color: #e6a23c;
}

.code-block {
  display: flex;
  align-items: center;
  gap: 10px;
  background-color: #1e1e1e;
  border-radius: 4px;
  padding: 12px;
  margin: 10px 0;
}

.code-block code {
  flex: 1;
  color: #9cdcfe;
  background: none;
  padding: 0;
  font-size: 13px;
  word-break: break-all;
}

.help-tip {
  font-size: 13px;
  color: #909399;
  margin: 8px 0;
}
</style>
