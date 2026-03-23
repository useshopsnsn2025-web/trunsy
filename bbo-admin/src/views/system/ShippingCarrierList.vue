<template>
  <div class="shipping-carrier-list">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>物流运输商管理</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增运输商
          </el-button>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="logo" label="Logo" width="80">
          <template #default="{ row }">
            <el-image v-if="row.logo" :src="row.logo" style="width: 40px; height: 40px;" fit="contain" />
            <span v-else class="no-icon">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="code" label="编码" width="120" />
        <el-table-column prop="name" label="名称" min-width="150" />
        <el-table-column prop="description" label="描述" min-width="200">
          <template #default="{ row }">
            {{ row.description || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="配送时间" width="120">
          <template #default="{ row }">
            <span v-if="row.estimated_days_min && row.estimated_days_max">
              {{ row.estimated_days_min }}-{{ row.estimated_days_max }}天
            </span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="country_count" label="国家数" width="90">
          <template #default="{ row }">
            <el-tag size="small">{{ row.country_count || 0 }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              :model-value="row.status === 1"
              @change="handleToggleStatus(row)"
              :loading="row.toggling"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="success" link @click="handleCountries(row)">国家配置</el-button>
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
    <el-dialog v-model="formDialogVisible" :title="isEdit ? '编辑运输商' : '新增运输商'" width="650px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="编码" prop="code">
          <el-input v-model="form.code" :disabled="isEdit" placeholder="如：ups, fedex, dhl" />
        </el-form-item>

        <!-- 多语言输入 -->
        <el-form-item label="名称/描述" required>
          <el-tabs v-model="activeLang" type="card" class="lang-tabs">
            <el-tab-pane label="繁體中文" name="zh-tw">
              <el-form-item label="名稱" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].name" placeholder="運輸商名稱" />
              </el-form-item>
              <el-form-item label="描述" label-width="80px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['zh-tw'].description" placeholder="描述（可選）" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="English" name="en-us">
              <el-form-item label="Name" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].name" placeholder="Carrier name" />
              </el-form-item>
              <el-form-item label="Desc" label-width="80px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['en-us'].description" placeholder="Description (optional)" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="日本語" name="ja-jp">
              <el-form-item label="名前" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].name" placeholder="配送業者名" />
              </el-form-item>
              <el-form-item label="説明" label-width="80px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['ja-jp'].description" placeholder="説明（任意）" />
              </el-form-item>
            </el-tab-pane>
          </el-tabs>
        </el-form-item>

        <el-form-item label="Logo">
          <ImagePicker v-model="form.logo" width="60px" height="60px" />
          <span class="image-tip">建议尺寸 60x60，正方形图标</span>
        </el-form-item>

        <el-form-item label="追踪URL">
          <el-input v-model="form.tracking_url" placeholder="如：https://ups.com/track?num={tracking_number}" />
          <span class="tip-text">使用 {tracking_number} 作为单号占位符</span>
        </el-form-item>

        <el-form-item label="配送天数">
          <el-input-number v-model="form.estimated_days_min" :min="0" :max="99" placeholder="最小" style="width: 100px;" />
          <span style="margin: 0 8px;">至</span>
          <el-input-number v-model="form.estimated_days_max" :min="0" :max="99" placeholder="最大" style="width: 100px;" />
          <span class="tip-text">默认配送时间（天）</span>
        </el-form-item>

        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="9999" />
          <span class="sort-tip">数值越大越靠前</span>
        </el-form-item>

        <el-form-item label="状态" prop="status">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 国家配置对话框 -->
    <el-dialog v-model="countryDialogVisible" :title="'国家配置 - ' + currentCarrier?.name" width="1000px">
      <div class="country-toolbar">
        <el-button type="primary" size="small" @click="handleAddCountry">
          <el-icon><Plus /></el-icon>
          添加国家
        </el-button>
      </div>

      <el-table :data="countryList" v-loading="countryLoading" stripe max-height="400">
        <el-table-column prop="country_code" label="国家" width="200">
          <template #default="{ row, $index }">
            <el-select v-model="countryList[$index].country_code" placeholder="搜索国家" size="small" filterable style="width: 180px;">
              <el-option v-for="c in availableCountries" :key="c.code" :label="c.name" :value="c.code">
                <span>{{ c.code }} - {{ c.name }}</span>
              </el-option>
            </el-select>
          </template>
        </el-table-column>
        <el-table-column label="运费" width="130">
          <template #default="{ row, $index }">
            <el-input-number v-model="countryList[$index].shipping_fee" :min="0" :precision="2" size="small" style="width: 100px;" />
          </template>
        </el-table-column>
        <el-table-column label="货币" width="100">
          <template #default="{ row, $index }">
            <el-select v-model="countryList[$index].currency" size="small" style="width: 80px;">
              <el-option label="USD" value="USD" />
              <el-option label="TWD" value="TWD" />
              <el-option label="JPY" value="JPY" />
            </el-select>
          </template>
        </el-table-column>
        <el-table-column label="免运费门槛" width="130">
          <template #default="{ row, $index }">
            <el-input-number v-model="countryList[$index].free_shipping_threshold" :min="0" :precision="2" size="small" style="width: 100px;" placeholder="无" />
          </template>
        </el-table-column>
        <el-table-column label="配送天数" width="160">
          <template #default="{ row, $index }">
            <el-input-number v-model="countryList[$index].estimated_days_min" :min="0" :max="99" size="small" style="width: 60px;" />
            <span style="margin: 0 4px;">-</span>
            <el-input-number v-model="countryList[$index].estimated_days_max" :min="0" :max="99" size="small" style="width: 60px;" />
          </template>
        </el-table-column>
        <el-table-column label="启用" width="80">
          <template #default="{ row, $index }">
            <el-switch v-model="countryList[$index].is_enabled" :active-value="1" :inactive-value="0" size="small" />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="80">
          <template #default="{ $index }">
            <el-button type="danger" link size="small" @click="handleRemoveCountry($index)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <template #footer>
        <el-button @click="countryDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSaveCountries" :loading="countrySaving">保存配置</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import ImagePicker from '@/components/ImagePicker.vue'
import {
  getShippingCarrierList,
  getShippingCarrierDetail,
  createShippingCarrier,
  updateShippingCarrier,
  deleteShippingCarrier,
  toggleShippingCarrierStatus,
  getCarrierCountries,
  saveCarrierCountries,
  type ShippingCarrier,
  type CarrierCountry
} from '@/api/shippingCarrier'

const list = ref<(ShippingCarrier & { toggling?: boolean })[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)
const activeLang = ref('zh-tw')

// 国家配置相关
const countryDialogVisible = ref(false)
const countryLoading = ref(false)
const countrySaving = ref(false)
const currentCarrier = ref<ShippingCarrier | null>(null)
const countryList = ref<CarrierCountry[]>([])

// 国家列表
const availableCountries = [
  // 亚洲
  { code: 'TW', name: '台灣 Taiwan' },
  { code: 'JP', name: '日本 Japan' },
  { code: 'KR', name: '韓國 South Korea' },
  { code: 'CN', name: '中國 China' },
  { code: 'HK', name: '香港 Hong Kong' },
  { code: 'MO', name: '澳門 Macau' },
  { code: 'SG', name: '新加坡 Singapore' },
  { code: 'MY', name: '馬來西亞 Malaysia' },
  { code: 'TH', name: '泰國 Thailand' },
  { code: 'VN', name: '越南 Vietnam' },
  { code: 'PH', name: '菲律賓 Philippines' },
  { code: 'ID', name: '印尼 Indonesia' },
  { code: 'IN', name: '印度 India' },
  { code: 'AE', name: '阿聯酋 UAE' },
  { code: 'SA', name: '沙特 Saudi Arabia' },
  // 北美
  { code: 'US', name: '美國 United States' },
  { code: 'CA', name: '加拿大 Canada' },
  { code: 'MX', name: '墨西哥 Mexico' },
  // 欧洲
  { code: 'GB', name: '英國 United Kingdom' },
  { code: 'DE', name: '德國 Germany' },
  { code: 'FR', name: '法國 France' },
  { code: 'IT', name: '義大利 Italy' },
  { code: 'ES', name: '西班牙 Spain' },
  { code: 'NL', name: '荷蘭 Netherlands' },
  { code: 'PT', name: '葡萄牙 Portugal' },
  { code: 'SE', name: '瑞典 Sweden' },
  { code: 'NO', name: '挪威 Norway' },
  { code: 'DK', name: '丹麥 Denmark' },
  { code: 'FI', name: '芬蘭 Finland' },
  { code: 'CH', name: '瑞士 Switzerland' },
  { code: 'AT', name: '奧地利 Austria' },
  { code: 'BE', name: '比利時 Belgium' },
  { code: 'PL', name: '波蘭 Poland' },
  { code: 'IE', name: '愛爾蘭 Ireland' },
  { code: 'RU', name: '俄羅斯 Russia' },
  { code: 'TR', name: '土耳其 Turkey' },
  // 大洋洲
  { code: 'AU', name: '澳洲 Australia' },
  { code: 'NZ', name: '紐西蘭 New Zealand' },
  // 南美
  { code: 'BR', name: '巴西 Brazil' },
  { code: 'AR', name: '阿根廷 Argentina' },
  { code: 'CL', name: '智利 Chile' },
  // 非洲
  { code: 'ZA', name: '南非 South Africa' },
  { code: 'EG', name: '埃及 Egypt' },
  { code: 'NG', name: '奈及利亞 Nigeria' },
]

// 初始翻译结构
const emptyTranslations = () => ({
  'zh-tw': { name: '', description: '' },
  'en-us': { name: '', description: '' },
  'ja-jp': { name: '', description: '' }
})

// 合并翻译数据
const mergeTranslations = (data: any) => {
  const empty = emptyTranslations()
  if (!data || typeof data !== 'object') return empty
  return {
    'zh-tw': { ...empty['zh-tw'], ...(data['zh-tw'] || {}) },
    'en-us': { ...empty['en-us'], ...(data['en-us'] || {}) },
    'ja-jp': { ...empty['ja-jp'], ...(data['ja-jp'] || {}) }
  }
}

const form = reactive({
  code: '',
  logo: '',
  tracking_url: '',
  estimated_days_min: null as number | null,
  estimated_days_max: null as number | null,
  sort: 0,
  status: 1,
  translations: emptyTranslations() as Record<string, { name: string; description: string }>
})

const rules: FormRules = {
  code: [
    { required: true, message: '请输入编码', trigger: 'blur' },
    { pattern: /^[a-zA-Z_][a-zA-Z0-9_]*$/, message: '编码只能包含字母、数字和下划线', trigger: 'blur' }
  ]
}

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getShippingCarrierList({
      page: pagination.page,
      pageSize: pagination.pageSize
    })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取列表失败:', error)
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  isEdit.value = false
  formDialogVisible.value = true
  activeLang.value = 'zh-tw'
  Object.assign(form, {
    code: '',
    logo: '',
    tracking_url: '',
    estimated_days_min: null,
    estimated_days_max: null,
    sort: 0,
    status: 1,
    translations: emptyTranslations()
  })
}

const handleEdit = async (row: ShippingCarrier) => {
  isEdit.value = true
  editId.value = row.id
  activeLang.value = 'zh-tw'
  try {
    const res: any = await getShippingCarrierDetail(row.id)
    const data = res.data
    Object.assign(form, {
      code: data.code,
      logo: data.logo || '',
      tracking_url: data.tracking_url || '',
      estimated_days_min: data.estimated_days_min,
      estimated_days_max: data.estimated_days_max,
      sort: data.sort,
      status: data.status,
      translations: mergeTranslations(data.translations)
    })
    formDialogVisible.value = true
  } catch (error) {
    console.error('获取详情失败:', error)
    ElMessage.error('获取详情失败')
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  // 验证至少填写一种语言的名称
  if (!form.translations['zh-tw'].name && !form.translations['en-us'].name && !form.translations['ja-jp'].name) {
    ElMessage.warning('请至少填写一种语言的运输商名称')
    return
  }
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      // 设置默认name为英文名称
      const name = form.translations['en-us'].name || form.translations['zh-tw'].name || form.translations['ja-jp'].name
      const submitData = { ...form, name }

      if (isEdit.value) {
        await updateShippingCarrier(editId.value, submitData)
        ElMessage.success('更新成功')
      } else {
        await createShippingCarrier(submitData)
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

const handleDelete = async (row: ShippingCarrier) => {
  try {
    await ElMessageBox.confirm(`确定删除"${row.name}"吗？`, '提示', { type: 'warning' })
    await deleteShippingCarrier(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') console.error('删除失败:', error)
  }
}

const handleToggleStatus = async (row: ShippingCarrier & { toggling?: boolean }) => {
  row.toggling = true
  try {
    const res: any = await toggleShippingCarrierStatus(row.id)
    row.status = res.data.status
    ElMessage.success(res.msg || '状态已更新')
  } catch (error) {
    console.error('状态切换失败:', error)
  } finally {
    row.toggling = false
  }
}

// 国家配置
const handleCountries = async (row: ShippingCarrier) => {
  currentCarrier.value = row
  countryLoading.value = true
  countryDialogVisible.value = true
  try {
    const res: any = await getCarrierCountries(row.id)
    countryList.value = res.data || []
  } catch (error) {
    console.error('获取国家配置失败:', error)
    ElMessage.error('获取国家配置失败')
  } finally {
    countryLoading.value = false
  }
}

const handleAddCountry = () => {
  countryList.value.push({
    country_code: '',
    shipping_fee: 0,
    currency: 'USD',
    free_shipping_threshold: null,
    estimated_days_min: null,
    estimated_days_max: null,
    is_enabled: 1
  })
}

const handleRemoveCountry = (index: number) => {
  countryList.value.splice(index, 1)
}

const handleSaveCountries = async () => {
  if (!currentCarrier.value) return

  // 验证
  for (const c of countryList.value) {
    if (!c.country_code) {
      ElMessage.warning('请选择国家代码')
      return
    }
  }

  // 检查重复
  const codes = countryList.value.map(c => c.country_code)
  if (new Set(codes).size !== codes.length) {
    ElMessage.warning('国家代码不能重复')
    return
  }

  countrySaving.value = true
  try {
    await saveCarrierCountries(currentCarrier.value.id, countryList.value)
    ElMessage.success('保存成功')
    countryDialogVisible.value = false
    fetchList()
  } catch (error) {
    console.error('保存失败:', error)
    ElMessage.error('保存失败')
  } finally {
    countrySaving.value = false
  }
}

onMounted(() => fetchList())
</script>

<style scoped>
.shipping-carrier-list {
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

.lang-tabs {
  width: 100%;
}

.lang-tabs :deep(.el-tabs__content) {
  padding: 15px;
  background: #f9f9f9;
  border-radius: 4px;
}

.no-icon {
  color: #c0c4cc;
}

.image-tip {
  margin-left: 12px;
  font-size: 12px;
  color: #909399;
}

.tip-text {
  display: block;
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.sort-tip {
  margin-left: 12px;
  font-size: 12px;
  color: #909399;
}

.country-toolbar {
  margin-bottom: 12px;
}
</style>
