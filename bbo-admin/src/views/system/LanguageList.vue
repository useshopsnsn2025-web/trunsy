<template>
  <div class="language-list">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>语言管理</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增语言
          </el-button>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="flag" label="国旗" width="80" align="center">
          <template #default="{ row }">
            <img
              v-if="row.flag"
              :src="getFlagUrl(row.flag)"
              :alt="row.flag"
              class="flag-img"
              @error="handleFlagError"
            />
            <span v-else class="flag-placeholder">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="code" label="语言代码" width="120">
          <template #default="{ row }">
            <el-tag type="info" size="small">{{ row.code }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="名称" min-width="120" />
        <el-table-column prop="native_name" label="本地名称" min-width="120" />
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column prop="is_default" label="默认语言" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.is_default" type="success" size="small">默认</el-tag>
            <el-button
              v-else
              link
              type="primary"
              size="small"
              @click="handleSetDefault(row)"
              :loading="row.settingDefault"
            >
              设为默认
            </el-button>
          </template>
        </el-table-column>
        <el-table-column prop="is_active" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              :model-value="row.is_active"
              @change="handleToggleStatus(row)"
              :loading="row.toggling"
              :disabled="row.is_default"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="300" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button
              type="warning"
              link
              @click="handleCheckStatus(row)"
              :loading="row.checking"
              :disabled="row.is_default"
            >
              检查翻译
            </el-button>
            <el-button
              type="success"
              link
              @click="handleTranslate(row)"
              :loading="row.translating"
              :disabled="row.is_default"
            >
              生成翻译
            </el-button>
            <el-button
              type="danger"
              link
              @click="handleDelete(row)"
              :disabled="row.is_default"
            >
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :total="pagination.total"
          layout="total, prev, pager, next"
          @current-change="fetchList"
        />
      </div>
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog v-model="formDialogVisible" :title="isEdit ? '编辑语言' : '新增语言'" width="500px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="语言代码" prop="code">
          <el-input
            v-model="form.code"
            :disabled="isEdit"
            placeholder="如：ko-kr, fr-fr, de-de"
            maxlength="10"
          />
          <div class="form-tip">格式：小写语言代码-小写地区代码，如 en-us</div>
        </el-form-item>

        <el-form-item label="名称" prop="name">
          <el-input v-model="form.name" placeholder="如：韩文、法文" />
          <div class="form-tip">后台管理显示的名称</div>
        </el-form-item>

        <el-form-item label="本地名称" prop="native_name">
          <el-input v-model="form.native_name" placeholder="如：한국어、Français" />
          <div class="form-tip">该语言的母语显示名称</div>
        </el-form-item>

        <el-form-item label="国旗" prop="flag">
          <div class="flag-form-field">
            <div class="flag-preview" v-if="form.flag">
              <img :src="getFlagUrl(form.flag)" :alt="form.flag" class="flag-img-preview" @error="handleFlagError" />
              <span class="flag-code">{{ form.flag.toUpperCase() }}</span>
            </div>
            <el-input v-model="form.flag" placeholder="如：kr, fr, de" style="width: 100px;" />
            <el-popover placement="right" :width="380" trigger="click">
              <template #reference>
                <el-button link type="primary" style="margin-left: 10px;">选择国旗</el-button>
              </template>
              <div class="flag-picker">
                <div
                  v-for="flag in commonFlags"
                  :key="flag.code"
                  class="flag-item"
                  :class="{ active: form.flag === flag.code }"
                  :title="flag.name"
                  @click="form.flag = flag.code"
                >
                  <img :src="getFlagUrl(flag.code)" :alt="flag.code" class="flag-item-img" />
                  <span class="flag-item-name">{{ flag.name }}</span>
                </div>
              </div>
            </el-popover>
          </div>
          <div class="form-tip">输入 ISO 3166-1 国家代码（2位小写字母），如 tw, us, jp</div>
        </el-form-item>

        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
          <div class="form-tip">数值越小越靠前</div>
        </el-form-item>

        <el-form-item label="状态" prop="is_active">
          <el-switch v-model="form.is_active" />
          <span class="switch-label">{{ form.is_active ? '启用' : '禁用' }}</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 翻译类型选择对话框 -->
    <el-dialog
      v-model="translateDialogVisible"
      :title="`为「${translateDialogLanguageName}」生成翻译`"
      width="520px"
    >
      <div style="line-height: 1.8;">
        <p style="margin-bottom: 10px; font-weight: 500;">请选择翻译类型：</p>
        <el-radio-group v-model="translateDialogType" style="display: flex; flex-direction: column; gap: 12px;">
          <el-radio value="ui" style="height: auto; align-items: flex-start;">
            <div>
              <strong>仅 UI 翻译</strong>（推荐）
              <div style="color: #909399; font-size: 12px;">前端界面文案，约 1000+ 条，预计 1-3 分钟</div>
            </div>
          </el-radio>
          <el-radio value="goods" style="height: auto; align-items: flex-start;">
            <div>
              <strong>仅商品相关翻译</strong>
              <div style="color: #909399; font-size: 12px;">商品、分类、品牌、属性等商品相关内容</div>
            </div>
          </el-radio>
          <el-radio value="single" style="height: auto; align-items: flex-start;">
            <div>
              <strong>单表翻译</strong>
              <div style="color: #909399; font-size: 12px;">选择一个翻译表单独翻译</div>
              <el-select
                v-if="translateDialogType === 'single'"
                v-model="translateDialogTable"
                placeholder="请选择翻译表"
                style="width: 100%; margin-top: 8px;"
                filterable
                @click.stop
              >
                <el-option
                  v-for="t in translationTableList"
                  :key="t.table"
                  :label="`${t.label}（${t.table}）`"
                  :value="t.table"
                />
              </el-select>
            </div>
          </el-radio>
          <el-radio value="content" style="height: auto; align-items: flex-start;">
            <div>
              <strong>仅内容翻译（全部）</strong>
              <div style="color: #909399; font-size: 12px;">所有数据库内容，分批处理，自动续传</div>
            </div>
          </el-radio>
          <el-radio value="all" style="height: auto; align-items: flex-start;">
            <div>
              <strong>全部翻译</strong>
              <div style="color: #909399; font-size: 12px;">UI + 所有内容，分批处理</div>
            </div>
          </el-radio>
        </el-radio-group>

        <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #eee;">
          <el-checkbox v-model="translateDialogForce">
            <strong>强制重新翻译</strong>
          </el-checkbox>
          <div style="color: #E6A23C; font-size: 12px; margin-left: 24px;">覆盖已有翻译（谨慎使用，会覆盖人工修改的内容）</div>
        </div>
      </div>
      <template #footer>
        <el-button @click="translateDialogVisible = false">取消</el-button>
        <el-button
          type="primary"
          @click="startTranslate"
          :disabled="translateDialogType === 'single' && !translateDialogTable"
        >
          开始翻译
        </el-button>
      </template>
    </el-dialog>

    <!-- 翻译进度对话框 -->
    <el-dialog
      v-model="translateProgress.visible"
      :title="`翻译进度 - ${translateProgress.languageName}`"
      width="500px"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :show-close="translateProgress.status !== 'running'"
    >
      <div class="translate-progress">
        <!-- 状态图标 -->
        <div class="progress-status">
          <el-icon v-if="translateProgress.status === 'running'" class="is-loading" :size="48" color="#409EFF">
            <Loading />
          </el-icon>
          <el-icon v-else-if="translateProgress.status === 'completed'" :size="48" color="#67C23A">
            <CircleCheck />
          </el-icon>
          <el-icon v-else-if="translateProgress.status === 'error'" :size="48" color="#F56C6C">
            <CircleClose />
          </el-icon>
        </div>

        <!-- 进度信息 -->
        <div class="progress-info">
          <template v-if="translateProgress.status === 'running'">
            <p class="progress-text">正在翻译中，请勿关闭此窗口...</p>
            <p v-if="translateProgress.currentTable" class="progress-detail">
              当前处理: {{ translateProgress.currentTable }}
            </p>
            <!-- 表级别进度条 -->
            <el-progress
              v-if="translateProgress.tablesTotal > 0"
              :percentage="Math.round((translateProgress.tablesDone / translateProgress.tablesTotal) * 100)"
              :format="() => `${translateProgress.tablesDone} / ${translateProgress.tablesTotal} 表`"
              style="margin-top: 15px;"
            />
            <!-- 记录级别进度（当前表） -->
            <div v-if="translateProgress.recordProgress.tableTotal > 0" style="margin-top: 12px;">
              <p class="progress-detail" style="margin-bottom: 6px;">
                当前表进度: {{ translateProgress.recordProgress.tableTranslated }} / {{ translateProgress.recordProgress.tableTotal }} 条
              </p>
              <el-progress
                :percentage="Math.round((translateProgress.recordProgress.tableTranslated / translateProgress.recordProgress.tableTotal) * 100)"
                :stroke-width="14"
                :text-inside="true"
                :color="'#67C23A'"
              />
            </div>
            <!-- 总体记录进度 -->
            <div v-if="translateProgress.recordProgress.overallTranslated > 0" style="margin-top: 10px; color: #909399; font-size: 13px;">
              累计已翻译: <strong style="color: #67C23A;">{{ translateProgress.recordProgress.overallTranslated }}</strong> 条
              <span v-if="translateProgress.recordProgress.overallFailed > 0">
                ，失败: <strong style="color: #F56C6C;">{{ translateProgress.recordProgress.overallFailed }}</strong> 条
              </span>
            </div>
          </template>

          <template v-else-if="translateProgress.status === 'completed'">
            <p class="progress-text success">翻译完成！</p>
            <div class="result-summary">
              <div v-if="translateProgress.uiResult" class="result-item">
                <span class="result-label">UI翻译:</span>
                <span class="result-value">
                  成功 <strong>{{ translateProgress.uiResult.translated || 0 }}</strong>,
                  跳过 {{ translateProgress.uiResult.skipped || 0 }},
                  失败 <span :class="{ 'text-danger': translateProgress.uiResult.failed > 0 }">{{ translateProgress.uiResult.failed || 0 }}</span>
                </span>
              </div>
              <div v-if="translateProgress.contentResult" class="result-item">
                <span class="result-label">内容翻译:</span>
                <span class="result-value">
                  成功 <strong>{{ translateProgress.contentResult.translated_records || 0 }}</strong>,
                  失败 <span :class="{ 'text-danger': translateProgress.contentResult.failed_records > 0 }">{{ translateProgress.contentResult.failed_records || 0 }}</span>
                </span>
              </div>
            </div>
          </template>

          <template v-else-if="translateProgress.status === 'error'">
            <p class="progress-text error">翻译失败</p>
            <p class="error-message">{{ translateProgress.error }}</p>
          </template>
        </div>
      </div>

      <template #footer>
        <el-button
          v-if="translateProgress.status !== 'running'"
          type="primary"
          @click="translateProgress.visible = false"
        >
          关闭
        </el-button>
      </template>
    </el-dialog>
    <!-- 翻译状态检查结果对话框 -->
    <el-dialog
      v-model="checkStatusVisible"
      :title="`翻译状态检查 - ${checkStatusLanguageName}`"
      width="900px"
      top="5vh"
    >
      <div v-loading="checkStatusLoading">
        <!-- 总体统计 -->
        <div v-if="checkStatusData" class="check-status-summary">
          <div class="summary-header">
            <div class="summary-item">
              <span class="summary-label">总体完成率</span>
              <el-progress
                :percentage="checkStatusData.summary.completion_rate"
                :color="getProgressColor(checkStatusData.summary.completion_rate)"
                :stroke-width="20"
                :text-inside="true"
                style="width: 200px;"
              />
            </div>
            <div class="summary-stats">
              <span>基准语言: <el-tag size="small" type="info">{{ checkStatusData.source_locale }}</el-tag></span>
              <span>总条目: <strong>{{ checkStatusData.summary.total_source }}</strong></span>
              <span>已翻译: <strong style="color: #67C23A;">{{ checkStatusData.summary.total_target }}</strong></span>
              <span>缺失: <strong :style="{ color: checkStatusData.summary.total_missing > 0 ? '#F56C6C' : '#67C23A' }">{{ checkStatusData.summary.total_missing }}</strong></span>
            </div>
          </div>
        </div>

        <!-- UI 翻译详情 -->
        <div v-if="checkStatusData" style="margin-top: 20px;">
          <h4 style="margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            UI 翻译
            <el-tag :type="checkStatusData.ui.missing_count === 0 ? 'success' : 'warning'" size="small">
              {{ checkStatusData.ui.completion_rate }}%
            </el-tag>
          </h4>
          <el-table :data="checkStatusData.ui.namespaces" size="small" stripe max-height="280">
            <el-table-column prop="namespace" label="命名空间" min-width="140" />
            <el-table-column prop="source_count" label="基准条目" width="100" align="center" />
            <el-table-column prop="target_count" label="已翻译" width="100" align="center" />
            <el-table-column prop="missing_count" label="缺失" width="80" align="center">
              <template #default="{ row }">
                <span :style="{ color: row.missing_count > 0 ? '#F56C6C' : '#67C23A', fontWeight: 600 }">
                  {{ row.missing_count }}
                </span>
              </template>
            </el-table-column>
            <el-table-column label="完成率" width="150">
              <template #default="{ row }">
                <el-progress
                  :percentage="row.completion_rate"
                  :color="getProgressColor(row.completion_rate)"
                  :stroke-width="14"
                  :text-inside="true"
                />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="100" align="center">
              <template #default="{ row }">
                <el-button
                  v-if="row.missing_count > 0"
                  type="primary"
                  link
                  size="small"
                  @click="handleTranslateUi"
                >
                  翻译
                </el-button>
                <el-tag v-else type="success" size="small">已完成</el-tag>
              </template>
            </el-table-column>
          </el-table>
        </div>

        <!-- 内容翻译详情 -->
        <div v-if="checkStatusData && checkStatusData.content.length > 0" style="margin-top: 20px;">
          <h4 style="margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            内容翻译
          </h4>
          <el-table :data="checkStatusData.content" size="small" stripe max-height="350">
            <el-table-column prop="label" label="内容表" min-width="120" />
            <el-table-column prop="source_total" label="基准条目" width="100" align="center" />
            <el-table-column prop="target_total" label="已翻译" width="100" align="center" />
            <el-table-column prop="missing_count" label="缺失" width="80" align="center">
              <template #default="{ row }">
                <span :style="{ color: row.missing_count > 0 ? '#F56C6C' : '#67C23A', fontWeight: 600 }">
                  {{ row.missing_count }}
                </span>
              </template>
            </el-table-column>
            <el-table-column label="完成率" width="150">
              <template #default="{ row }">
                <el-progress
                  :percentage="row.completion_rate"
                  :color="getProgressColor(row.completion_rate)"
                  :stroke-width="14"
                  :text-inside="true"
                />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="100" align="center">
              <template #default="{ row }">
                <el-button
                  v-if="row.missing_count > 0"
                  type="primary"
                  link
                  size="small"
                  @click="handleTranslateSingleTable(row.table)"
                >
                  翻译
                </el-button>
                <el-tag v-else type="success" size="small">已完成</el-tag>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>

      <template #footer>
        <el-button @click="checkStatusVisible = false">关闭</el-button>
        <el-button
          v-if="checkStatusData && checkStatusData.summary.total_missing > 0"
          type="primary"
          @click="checkStatusVisible = false; if (checkStatusRow) handleTranslate(checkStatusRow)"
        >
          全部翻译
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus, Loading, CircleCheck, CircleClose } from '@element-plus/icons-vue'
import {
  getLanguageList,
  getLanguageDetail,
  createLanguage,
  updateLanguage,
  deleteLanguage,
  updateLanguageStatus,
  setDefaultLanguage,
  getTranslateConfig,
  getTranslationTables,
  triggerTranslate,
  checkTranslationStatus,
  type Language,
  type TranslateType,
  type TranslationTable
} from '@/api/language'

// 常用国旗 - 使用 ISO 3166-1 alpha-2 国家代码
const commonFlags = [
  { code: 'tw', name: '台湾' },
  { code: 'us', name: '美国' },
  { code: 'jp', name: '日本' },
  { code: 'kr', name: '韩国' },
  { code: 'cn', name: '中国' },
  { code: 'hk', name: '香港' },
  { code: 'gb', name: '英国' },
  { code: 'fr', name: '法国' },
  { code: 'de', name: '德国' },
  { code: 'es', name: '西班牙' },
  { code: 'it', name: '意大利' },
  { code: 'pt', name: '葡萄牙' },
  { code: 'ru', name: '俄罗斯' },
  { code: 'th', name: '泰国' },
  { code: 'vn', name: '越南' },
  { code: 'id', name: '印尼' },
  { code: 'my', name: '马来西亚' },
  { code: 'sg', name: '新加坡' },
  { code: 'ph', name: '菲律宾' },
  { code: 'in', name: '印度' },
  { code: 'ae', name: '阿联酋' },
  { code: 'sa', name: '沙特' },
  { code: 'au', name: '澳大利亚' },
  { code: 'nz', name: '新西兰' },
  { code: 'ca', name: '加拿大' },
  { code: 'br', name: '巴西' },
  { code: 'mx', name: '墨西哥' },
  { code: 'ar', name: '阿根廷' },
  { code: 'nl', name: '荷兰' },
  { code: 'be', name: '比利时' },
  { code: 'se', name: '瑞典' },
  { code: 'no', name: '挪威' },
  { code: 'dk', name: '丹麦' },
  { code: 'fi', name: '芬兰' },
  { code: 'pl', name: '波兰' },
  { code: 'tr', name: '土耳其' },
]

// 获取国旗图片 URL
const getFlagUrl = (code: string) => {
  if (!code) return ''
  return `https://flagcdn.com/w40/${code.toLowerCase()}.png`
}

// 处理图片加载失败
const handleFlagError = (e: Event) => {
  const img = e.target as HTMLImageElement
  img.style.display = 'none'
}

type LanguageWithStatus = Language & {
  toggling?: boolean
  settingDefault?: boolean
  translating?: boolean
  checking?: boolean
}

// 翻译 API 配置状态
const translateConfigured = ref(false)

// 可翻译的内容表列表
const translationTableList = ref<TranslationTable[]>([])

const list = ref<LanguageWithStatus[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)

const getDefaultForm = () => ({
  code: '',
  name: '',
  native_name: '',
  flag: '',
  sort: 0,
  is_active: true
})

const form = reactive(getDefaultForm())

const rules: FormRules = {
  code: [
    { required: true, message: '请输入语言代码', trigger: 'blur' },
    { pattern: /^[a-z]{2}-[a-z]{2}$/, message: '格式不正确，如：en-us', trigger: 'blur' }
  ],
  name: [
    { required: true, message: '请输入语言名称', trigger: 'blur' }
  ]
}

onMounted(async () => {
  fetchList()
  // 检查翻译 API 配置
  try {
    const res: any = await getTranslateConfig()
    translateConfigured.value = res.data.configured
  } catch (e) {
    translateConfigured.value = false
  }
  // 获取可翻译的内容表列表
  try {
    const res: any = await getTranslationTables()
    translationTableList.value = res.data || []
  } catch (e) {
    translationTableList.value = []
  }
})

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getLanguageList({
      page: pagination.page,
      pageSize: pagination.pageSize
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  isEdit.value = false
  editId.value = 0
  Object.assign(form, getDefaultForm())
  // 获取最大排序值 +1
  if (list.value.length > 0) {
    form.sort = Math.max(...list.value.map(l => l.sort)) + 1
  }
  formDialogVisible.value = true
}

const handleEdit = async (row: Language) => {
  isEdit.value = true
  editId.value = row.id
  try {
    const res: any = await getLanguageDetail(row.id)
    const data = res.data
    Object.assign(form, {
      code: data.code,
      name: data.name,
      native_name: data.native_name || '',
      flag: data.flag || '',
      sort: data.sort,
      is_active: data.is_active
    })
    formDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取语言详情失败')
  }
}

const handleSubmit = async () => {
  try {
    await formRef.value?.validate()
  } catch (e) {
    return // 验证失败，不继续
  }

  submitting.value = true
  try {
    const submitData = {
      code: form.code.toLowerCase(),
      name: form.name,
      native_name: form.native_name || form.name,
      flag: form.flag,
      sort: form.sort,
      is_active: form.is_active ? 1 : 0
    }

    if (isEdit.value) {
      await updateLanguage(editId.value, submitData)
      ElMessage.success('更新成功')
    } else {
      await createLanguage(submitData)
      ElMessage.success('创建成功')
    }
    formDialogVisible.value = false
    fetchList()
  } catch (e: any) {
    console.error('提交失败:', e)
    ElMessage.error(e?.response?.data?.message || e?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleToggleStatus = async (row: LanguageWithStatus) => {
  if (row.is_default) {
    ElMessage.warning('默认语言不能禁用')
    return
  }
  row.toggling = true
  try {
    await updateLanguageStatus(row.id)
    row.is_active = !row.is_active
    ElMessage.success('状态已更新')
  } finally {
    row.toggling = false
  }
}

const handleSetDefault = async (row: LanguageWithStatus) => {
  try {
    await ElMessageBox.confirm(
      `确定要将"${row.name}"设为默认语言吗？`,
      '设置默认语言',
      { type: 'warning' }
    )
    row.settingDefault = true
    await setDefaultLanguage(row.id)
    ElMessage.success('默认语言已更新')
    fetchList()
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error('操作失败')
    }
  } finally {
    row.settingDefault = false
  }
}

const handleDelete = async (row: Language) => {
  if (row.is_default) {
    ElMessage.warning('默认语言不能删除')
    return
  }
  try {
    await ElMessageBox.confirm(
      `确定要删除语言"${row.name}"吗？删除后相关翻译数据将无法使用。`,
      '删除语言',
      { type: 'warning' }
    )
    await deleteLanguage(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 翻译进度状态
const translateProgress = reactive({
  visible: false,
  languageName: '',
  type: '' as TranslateType,
  status: 'idle' as 'idle' | 'running' | 'completed' | 'error',
  currentTable: '',
  tablesDone: 0,
  tablesTotal: 0,
  uiResult: null as any,
  contentResult: null as any,
  error: '',
  // 记录级别进度
  recordProgress: {
    table: '',
    tableTranslated: 0,
    tableTotal: 0,
    overallTranslated: 0,
    overallFailed: 0,
  }
})

// 翻译类型选择对话框状态
const translateDialogVisible = ref(false)
const translateDialogLanguageName = ref('')
const translateDialogType = ref<TranslateType>('ui')
const translateDialogTable = ref('')
const translateDialogForce = ref(false)
let translateDialogRow: LanguageWithStatus | null = null

const handleTranslate = (row: LanguageWithStatus) => {
  if (!translateConfigured.value) {
    ElMessage.warning('翻译 API 未配置，请先在系统配置中设置 translate_api_provider 和 translate_api_key')
    return
  }

  // 打开翻译类型选择对话框
  translateDialogRow = row
  translateDialogLanguageName.value = row.name
  translateDialogType.value = 'ui'
  translateDialogTable.value = ''
  translateDialogForce.value = false
  translateDialogVisible.value = true
}

const startTranslate = async () => {
  const row = translateDialogRow
  if (!row) return

  const translateType = translateDialogType.value
  const forceRetranslate = translateDialogForce.value
  const singleTable = translateType === 'single' ? translateDialogTable.value : undefined

  translateDialogVisible.value = false

  // 初始化进度状态
  translateProgress.visible = true
  translateProgress.languageName = row.name
  translateProgress.type = translateType
  translateProgress.status = 'running'
  translateProgress.currentTable = ''
  translateProgress.tablesDone = 0
  translateProgress.tablesTotal = 0
  translateProgress.uiResult = null
  translateProgress.contentResult = null
  translateProgress.error = ''
  translateProgress.recordProgress = { table: '', tableTranslated: 0, tableTotal: 0, overallTranslated: 0, overallFailed: 0 }

  row.translating = true

  try {
    // 循环请求直到完成（后端会自动续传进度）
    let hasMore = true
    let isFirstRequest = true

    while (hasMore) {
      const res: any = await triggerTranslate(
        row.id,
        'en-us',
        translateType,
        isFirstRequest ? forceRetranslate : false,
        isFirstRequest,
        singleTable
      )

      isFirstRequest = false

      if (res.data.error) {
        throw new Error(res.data.error)
      }

      if (res.data.ui_translations) {
        translateProgress.uiResult = res.data.ui_translations
      }

      if (res.data.current_table) {
        translateProgress.currentTable = res.data.current_table
      }
      if (res.data.tables_done !== undefined) {
        translateProgress.tablesDone = res.data.tables_done
      }
      if (res.data.tables_total !== undefined) {
        translateProgress.tablesTotal = res.data.tables_total
      }

      if (res.data.content_results) {
        translateProgress.contentResult = res.data.content_results
      }

      // 更新记录级别进度
      if (res.data.record_progress) {
        const rp = res.data.record_progress
        translateProgress.recordProgress.table = rp.table
        translateProgress.recordProgress.tableTranslated = rp.table_translated
        translateProgress.recordProgress.tableTotal = rp.table_total
        translateProgress.recordProgress.overallTranslated = rp.overall_translated
        translateProgress.recordProgress.overallFailed = rp.overall_failed
      }

      hasMore = res.data.has_more === true
    }

    translateProgress.status = 'completed'

    const messages: string[] = []

    if (translateProgress.uiResult) {
      const ui = translateProgress.uiResult
      messages.push(`UI翻译: 成功 ${ui.translated || 0}, 跳过 ${ui.skipped || 0}, 失败 ${ui.failed || 0}`)
    }

    if (translateProgress.contentResult) {
      const content = translateProgress.contentResult
      messages.push(`内容翻译: 成功 ${content.translated_records || 0}, 失败 ${content.failed_records || 0}`)
    }

    const hasFailures = (translateProgress.uiResult?.failed > 0) || (translateProgress.contentResult?.failed_records > 0)

    if (hasFailures) {
      ElMessage.warning(`翻译完成（有部分失败）\n${messages.join('\n')}`)
    } else {
      ElMessage.success(`翻译完成！\n${messages.join('\n')}`)
    }

  } catch (e: any) {
    translateProgress.status = 'error'
    translateProgress.error = e?.response?.data?.message || e?.message || '翻译失败'
    console.error('翻译错误:', e)

    if (translateProgress.error.includes('timeout')) {
      ElMessage.error('翻译请求超时，请稍后重试')
    } else {
      ElMessage.error(translateProgress.error)
    }
  } finally {
    row.translating = false
  }
}

// ============ 检查翻译状态 ============
const checkStatusVisible = ref(false)
const checkStatusLoading = ref(false)
const checkStatusLanguageName = ref('')
const checkStatusData = ref<any>(null)
let checkStatusRow: LanguageWithStatus | null = null

const getProgressColor = (percentage: number) => {
  if (percentage >= 100) return '#67C23A'
  if (percentage >= 80) return '#E6A23C'
  return '#F56C6C'
}

const handleCheckStatus = async (row: LanguageWithStatus) => {
  checkStatusRow = row
  checkStatusLanguageName.value = row.name
  checkStatusData.value = null
  checkStatusVisible.value = true
  checkStatusLoading.value = true
  row.checking = true

  try {
    const res: any = await checkTranslationStatus(row.id)
    checkStatusData.value = res.data
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || e?.message || '检查失败')
    checkStatusVisible.value = false
  } finally {
    checkStatusLoading.value = false
    row.checking = false
  }
}

// 从检查弹窗中直接翻译 UI
const handleTranslateUi = () => {
  if (!checkStatusRow || !translateConfigured.value) {
    ElMessage.warning('翻译 API 未配置')
    return
  }
  checkStatusVisible.value = false
  translateDialogRow = checkStatusRow
  translateDialogLanguageName.value = checkStatusRow.name
  translateDialogType.value = 'ui'
  translateDialogTable.value = ''
  translateDialogForce.value = false
  translateDialogVisible.value = true
}

// 从检查弹窗中直接翻译单个内容表
const handleTranslateSingleTable = (tableName: string) => {
  if (!checkStatusRow || !translateConfigured.value) {
    ElMessage.warning('翻译 API 未配置')
    return
  }
  checkStatusVisible.value = false
  // 直接开始翻译，跳过选择对话框
  translateDialogRow = checkStatusRow
  const row = checkStatusRow
  row.translating = true

  // 初始化进度状态
  translateProgress.visible = true
  translateProgress.languageName = row.name
  translateProgress.type = 'single'
  translateProgress.status = 'running'
  translateProgress.currentTable = tableName
  translateProgress.tablesDone = 0
  translateProgress.tablesTotal = 1
  translateProgress.uiResult = null
  translateProgress.contentResult = null
  translateProgress.error = ''
  translateProgress.recordProgress = { table: '', tableTranslated: 0, tableTotal: 0, overallTranslated: 0, overallFailed: 0 }

  ;(async () => {
    try {
      let hasMore = true
      let isFirstRequest = true

      while (hasMore) {
        const res: any = await triggerTranslate(
          row.id,
          'en-us',
          'single',
          false,
          isFirstRequest,
          tableName
        )

        isFirstRequest = false

        if (res.data.error) {
          throw new Error(res.data.error)
        }

        if (res.data.current_table) {
          translateProgress.currentTable = res.data.current_table
        }
        if (res.data.tables_done !== undefined) {
          translateProgress.tablesDone = res.data.tables_done
        }
        if (res.data.tables_total !== undefined) {
          translateProgress.tablesTotal = res.data.tables_total
        }
        if (res.data.content_results) {
          translateProgress.contentResult = res.data.content_results
        }

        // 更新记录级别进度
        if (res.data.record_progress) {
          const rp = res.data.record_progress
          translateProgress.recordProgress.table = rp.table
          translateProgress.recordProgress.tableTranslated = rp.table_translated
          translateProgress.recordProgress.tableTotal = rp.table_total
          translateProgress.recordProgress.overallTranslated = rp.overall_translated
          translateProgress.recordProgress.overallFailed = rp.overall_failed
        }

        hasMore = res.data.has_more === true
      }

      translateProgress.status = 'completed'
      ElMessage.success('翻译完成')
    } catch (e: any) {
      translateProgress.status = 'error'
      translateProgress.error = e?.response?.data?.message || e?.message || '翻译失败'
      ElMessage.error(translateProgress.error)
    } finally {
      row.translating = false
    }
  })()
}
</script>

<style scoped>
.language-list {
  padding: 20px;
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

.flag-img {
  width: 32px;
  height: auto;
  border-radius: 2px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.flag-placeholder {
  color: #c0c4cc;
  font-size: 14px;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.switch-label {
  margin-left: 10px;
  color: #606266;
}

.flag-form-field {
  display: flex;
  align-items: center;
  gap: 10px;
}

.flag-preview {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  background: #f5f7fa;
  border-radius: 4px;
}

.flag-img-preview {
  width: 24px;
  height: auto;
  border-radius: 2px;
}

.flag-code {
  font-size: 12px;
  color: #606266;
  font-weight: 500;
}

.flag-picker {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  max-height: 400px;
  overflow-y: auto;
}

.flag-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px 4px;
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s;
  border: 2px solid transparent;
}

.flag-item:hover {
  background: #f0f9ff;
  border-color: #409eff;
}

.flag-item.active {
  background: #ecf5ff;
  border-color: #409eff;
}

.flag-item-img {
  width: 32px;
  height: auto;
  border-radius: 2px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.flag-item-name {
  font-size: 11px;
  color: #606266;
  margin-top: 4px;
  text-align: center;
}

/* 翻译进度样式 */
.translate-progress {
  text-align: center;
  padding: 20px 0;
}

.progress-status {
  margin-bottom: 20px;
}

.progress-status .is-loading {
  animation: rotating 2s linear infinite;
}

@keyframes rotating {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.progress-info {
  min-height: 80px;
}

.progress-text {
  font-size: 16px;
  color: #303133;
  margin-bottom: 10px;
}

.progress-text.success {
  color: #67C23A;
}

.progress-text.error {
  color: #F56C6C;
}

.progress-detail {
  font-size: 13px;
  color: #909399;
}

.error-message {
  font-size: 13px;
  color: #F56C6C;
  background: #fef0f0;
  padding: 10px 15px;
  border-radius: 4px;
  margin-top: 10px;
}

.result-summary {
  text-align: left;
  background: #f5f7fa;
  padding: 15px 20px;
  border-radius: 6px;
  margin-top: 15px;
}

.result-item {
  margin-bottom: 8px;
}

.result-item:last-child {
  margin-bottom: 0;
}

.result-label {
  color: #606266;
  margin-right: 10px;
}

.result-value {
  color: #303133;
}

.result-value strong {
  color: #67C23A;
}

.text-danger {
  color: #F56C6C;
}

/* 检查翻译状态样式 */
.check-status-summary {
  background: #f5f7fa;
  padding: 16px 20px;
  border-radius: 8px;
}

.summary-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.summary-label {
  font-weight: 600;
  color: #303133;
  white-space: nowrap;
}

.summary-stats {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #606266;
}
</style>
