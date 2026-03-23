<template>
  <div class="country-list">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>国家/地区管理</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增国家/地区
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
        <el-table-column prop="code" label="代码" width="100">
          <template #default="{ row }">
            <el-tag type="info" size="small">{{ row.code }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="名称" min-width="150">
          <template #default="{ row }">
            {{ row.name }}
            <el-tag v-if="row.code === defaultCountryCode" type="success" size="small" style="margin-left: 8px;">默认</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="currency_code" label="默认货币" width="120">
          <template #default="{ row }">
            <el-tag type="success" size="small">{{ row.currency_code }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="locale" label="默认语言" width="120">
          <template #default="{ row }">
            <el-tag type="warning" size="small">{{ row.locale || '-' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column prop="is_active" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              :model-value="row.is_active"
              @change="handleToggleStatus(row)"
              :loading="row.toggling"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-button
              v-if="row.code !== defaultCountryCode"
              type="success"
              link
              @click="handleSetDefault(row)"
            >设为默认</el-button>
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
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
    <el-dialog v-model="formDialogVisible" :title="isEdit ? '编辑国家/地区' : '新增国家/地区'" width="650px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="国家代码" prop="code">
          <el-input
            v-model="form.code"
            :disabled="isEdit"
            placeholder="如：BR, KR, TH"
            maxlength="2"
            style="width: 100px;"
          />
          <div class="form-tip">ISO 3166-1 alpha-2 代码，2位大写字母</div>
        </el-form-item>

        <el-form-item label="默认名称" prop="name">
          <el-input v-model="form.name" placeholder="如：Brazil, South Korea" />
          <div class="form-tip">当无对应语言翻译时显示此名称</div>
        </el-form-item>

        <el-form-item label="国旗" prop="flag">
          <div class="flag-form-field">
            <div class="flag-preview" v-if="form.flag">
              <img :src="getFlagUrl(form.flag)" :alt="form.flag" class="flag-img-preview" @error="handleFlagError" />
              <span class="flag-code">{{ form.flag.toUpperCase() }}</span>
            </div>
            <el-input v-model="form.flag" placeholder="如：br, kr" style="width: 100px;" />
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
                  @click="selectFlag(flag)"
                >
                  <img :src="getFlagUrl(flag.code)" :alt="flag.code" class="flag-item-img" />
                  <span class="flag-item-name">{{ flag.name }}</span>
                </div>
              </div>
            </el-popover>
          </div>
          <div class="form-tip">用于 flagcdn.com 显示国旗图片</div>
        </el-form-item>

        <el-form-item label="默认货币" prop="currency_code">
          <el-select v-model="form.currency_code" placeholder="请选择货币" style="width: 200px;">
            <el-option
              v-for="currency in currencyOptions"
              :key="currency.code"
              :label="`${currency.code} - ${currency.name} (${currency.symbol})`"
              :value="currency.code"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="默认语言" prop="locale">
          <el-select v-model="form.locale" placeholder="请选择语言" style="width: 200px;" clearable>
            <el-option
              v-for="lang in languageOptions"
              :key="lang.code"
              :label="`${lang.code} - ${lang.name}`"
              :value="lang.code"
            />
          </el-select>
          <div class="form-tip">用户选择此地区时自动切换的语言</div>
        </el-form-item>

        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
        </el-form-item>

        <el-form-item label="状态" prop="is_active">
          <el-switch v-model="form.is_active" />
          <span class="switch-label">{{ form.is_active ? '启用' : '禁用' }}</span>
        </el-form-item>

        <!-- 多语言名称翻译 -->
        <el-divider content-position="left">多语言名称</el-divider>
        <div class="translations-section">
          <div class="translation-row" v-for="lang in languageOptions" :key="lang.code">
            <span class="translation-label">
              <img
                v-if="lang.flag"
                :src="getFlagUrl(lang.flag)"
                class="translation-flag"
                @error="handleFlagError"
              />
              {{ lang.name }} ({{ lang.code }})
            </span>
            <el-input
              v-model="form.translations[lang.code]"
              :placeholder="`${lang.name}名称`"
              style="width: 300px;"
            />
          </div>
        </div>
      </el-form>
      <template #footer>
        <el-button @click="formDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getCountryList,
  getCountryDetail,
  createCountry,
  updateCountry,
  deleteCountry,
  updateCountryStatus,
  getCountryLanguages,
  setDefaultCountry,
  getDefaultCountry,
  type Country
} from '@/api/country'
import { getCurrencyOptions, type Currency } from '@/api/currency'

// 常用国旗
const commonFlags = [
  { code: 'tw', name: '台湾' },
  { code: 'us', name: '美国' },
  { code: 'jp', name: '日本' },
  { code: 'kr', name: '韩国' },
  { code: 'cn', name: '中国' },
  { code: 'hk', name: '香港' },
  { code: 'mo', name: '澳门' },
  { code: 'gb', name: '英国' },
  { code: 'ie', name: '爱尔兰' },
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
  { code: 'za', name: '南非' },
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

type CountryWithStatus = Country & { toggling?: boolean }

interface LanguageOption {
  code: string
  name: string
  flag?: string
}

const list = ref<CountryWithStatus[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const defaultCountryCode = ref<string>('')

const currencyOptions = ref<Currency[]>([])
const languageOptions = ref<LanguageOption[]>([])

const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)

const getDefaultForm = () => ({
  code: '',
  name: '',
  flag: '',
  currency_code: 'USD',
  locale: '',
  sort: 0,
  is_active: true,
  translations: {} as Record<string, string>
})

const form = reactive(getDefaultForm())

const rules: FormRules = {
  code: [
    { required: true, message: '请输入国家代码', trigger: 'blur' },
    { pattern: /^[A-Za-z]{2}$/, message: '格式不正确，需要2位字母', trigger: 'blur' }
  ],
  name: [
    { required: true, message: '请输入国家名称', trigger: 'blur' }
  ],
  currency_code: [
    { required: true, message: '请选择默认货币', trigger: 'change' }
  ]
}

onMounted(async () => {
  fetchList()
  // 获取货币和语言选项
  try {
    const [currencyRes, langRes, defaultRes]: any[] = await Promise.all([
      getCurrencyOptions(),
      getCountryLanguages(),
      getDefaultCountry()
    ])
    currencyOptions.value = currencyRes.data || []
    languageOptions.value = langRes.data || []
    defaultCountryCode.value = defaultRes.data?.code || ''
  } catch (e) {
    console.error('获取选项数据失败:', e)
  }
})

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getCountryList({
      page: pagination.page,
      pageSize: pagination.pageSize
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } finally {
    loading.value = false
  }
}

// 选择国旗时自动填充代码
const selectFlag = (flag: { code: string; name: string }) => {
  form.flag = flag.code
  // 如果代码为空，自动填充
  if (!form.code) {
    form.code = flag.code.toUpperCase()
  }
}

const handleAdd = () => {
  isEdit.value = false
  editId.value = 0
  Object.assign(form, getDefaultForm())
  // 初始化翻译对象
  form.translations = {}
  languageOptions.value.forEach(lang => {
    form.translations[lang.code] = ''
  })
  // 获取最大排序值 +1
  if (list.value.length > 0) {
    form.sort = Math.max(...list.value.map(l => l.sort)) + 1
  }
  formDialogVisible.value = true
}

const handleEdit = async (row: Country) => {
  isEdit.value = true
  editId.value = row.id
  try {
    const res: any = await getCountryDetail(row.id)
    const data = res.data
    Object.assign(form, {
      code: data.code,
      name: data.name,
      flag: data.flag || '',
      currency_code: data.currency_code,
      locale: data.locale || '',
      sort: data.sort,
      is_active: data.is_active,
      translations: data.translations || {}
    })
    // 确保所有语言都有翻译字段
    languageOptions.value.forEach(lang => {
      if (!form.translations[lang.code]) {
        form.translations[lang.code] = ''
      }
    })
    formDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取国家详情失败')
  }
}

const handleSubmit = async () => {
  try {
    await formRef.value?.validate()
  } catch (e) {
    return
  }

  submitting.value = true
  try {
    // 过滤空翻译
    const translations: Record<string, string> = {}
    Object.entries(form.translations).forEach(([locale, name]) => {
      if (name && name.trim()) {
        translations[locale] = name.trim()
      }
    })

    const submitData = {
      code: form.code.toUpperCase(),
      name: form.name,
      flag: form.flag.toLowerCase(),
      currency_code: form.currency_code,
      locale: form.locale,
      sort: form.sort,
      is_active: form.is_active ? 1 : 0,
      translations
    }

    if (isEdit.value) {
      await updateCountry(editId.value, submitData)
      ElMessage.success('更新成功')
    } else {
      await createCountry(submitData)
      ElMessage.success('创建成功')
    }
    formDialogVisible.value = false
    fetchList()
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || e?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleToggleStatus = async (row: CountryWithStatus) => {
  row.toggling = true
  try {
    await updateCountryStatus(row.id)
    row.is_active = !row.is_active
    ElMessage.success('状态已更新')
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || '操作失败')
  } finally {
    row.toggling = false
  }
}

const handleDelete = async (row: Country) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除"${row.name}"吗？`,
      '删除国家/地区',
      { type: 'warning' }
    )
    await deleteCountry(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e?.response?.data?.message || '删除失败')
    }
  }
}

const handleSetDefault = async (row: Country) => {
  if (!row.is_active) {
    ElMessage.warning('请先启用该国家/地区')
    return
  }
  try {
    await setDefaultCountry(row.id)
    defaultCountryCode.value = row.code
    ElMessage.success(`已将"${row.name}"设为默认`)
  } catch (e: any) {
    ElMessage.error(e?.response?.data?.message || '设置失败')
  }
}
</script>

<style scoped>
.country-list {
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

/* 多语言翻译区域 */
.translations-section {
  padding: 0 10px;
}

.translation-row {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 12px;
}

.translation-label {
  width: 150px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #606266;
}

.translation-flag {
  width: 20px;
  height: auto;
  border-radius: 2px;
}
</style>
