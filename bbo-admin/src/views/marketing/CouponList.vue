<template>
  <div class="coupon-list">
    <!-- 搜索区域 -->
    <el-card class="search-card" shadow="never">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="名称/代码" clearable @keyup.enter="handleSearch" />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="searchForm.type" placeholder="全部" clearable>
            <el-option label="满减券" :value="1" />
            <el-option label="折扣券" :value="2" />
            <el-option label="代金券" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable>
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格 -->
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>优惠券列表</span>
          <el-button type="primary" @click="handleAdd">新增优惠券</el-button>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="图片" width="80">
          <template #default="{ row }">
            <el-image
              v-if="row.image"
              :src="row.image"
              :preview-src-list="[row.image]"
              fit="cover"
              style="width: 50px; height: 50px; border-radius: 4px;"
            />
            <span v-else style="color: #909399; font-size: 12px;">无</span>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="名称" min-width="150" />
        <el-table-column prop="code" label="代码" width="130" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type === 1 ? 'primary' : row.type === 2 ? 'success' : 'warning'" size="small">
              {{ typeNames[row.type] }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="value" label="优惠值" width="100">
          <template #default="{ row }">
            {{ row.type === 2 ? (row.value * 10) + '折' : '¥' + row.value }}
          </template>
        </el-table-column>
        <el-table-column prop="min_amount" label="门槛" width="100">
          <template #default="{ row }">
            {{ row.min_amount > 0 ? '满' + row.min_amount : '无门槛' }}
          </template>
        </el-table-column>
        <el-table-column label="领取/使用" width="100">
          <template #default="{ row }">
            {{ row.received_count }} / {{ row.used_count }}
          </template>
        </el-table-column>
        <el-table-column prop="end_time" label="有效期" width="170" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
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
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog v-model="formDialogVisible" :title="isEdit ? '编辑优惠券' : '新增优惠券'" width="700px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <!-- 优惠券图片 -->
        <el-form-item label="优惠券图片">
          <ImagePicker v-model="form.image" width="120px" height="80px" />
          <div class="image-tip">建议尺寸: 240x160px，用于优惠券展示</div>
        </el-form-item>
        <!-- 多语言输入 -->
        <el-form-item label="名称/说明" required>
          <el-tabs v-model="activeLang" type="card" class="lang-tabs">
            <el-tab-pane label="繁體中文" name="zh-tw">
              <el-form-item label="名稱" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].name" placeholder="請輸入繁體中文名稱" />
              </el-form-item>
              <el-form-item label="說明" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['zh-tw'].description" type="textarea" :rows="2" placeholder="使用說明（繁體中文）" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="English" name="en-us">
              <el-form-item label="Name" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].name" placeholder="Enter coupon name" />
              </el-form-item>
              <el-form-item label="Desc" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['en-us'].description" type="textarea" :rows="2" placeholder="Description (English)" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="日本語" name="ja-jp">
              <el-form-item label="Name" label-width="60px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].name" placeholder="クーポン名を入力してください" />
              </el-form-item>
              <el-form-item label="Desc" label-width="60px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['ja-jp'].description" type="textarea" :rows="2" placeholder="説明（日本語）" />
              </el-form-item>
            </el-tab-pane>
          </el-tabs>
        </el-form-item>
        <el-form-item label="代码" prop="code">
          <el-input v-model="form.code" :disabled="isEdit" placeholder="请输入优惠券代码" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-radio-group v-model="form.type">
            <el-radio :value="1">满减券</el-radio>
            <el-radio :value="2">折扣券</el-radio>
            <el-radio :value="3">代金券</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="优惠值" prop="value">
          <el-input-number v-model="form.value" :min="0" :precision="2" />
          <span style="margin-left: 10px; color: #909399;">
            {{ form.type === 2 ? '折扣比例(0.9表示9折)' : '金额(元)' }}
          </span>
        </el-form-item>
        <el-form-item label="最低消费" prop="min_amount">
          <el-input-number v-model="form.min_amount" :min="0" :precision="2" />
          <span style="margin-left: 10px; color: #909399;">0表示无门槛</span>
        </el-form-item>
        <el-form-item label="发放数量" prop="total_count">
          <el-input-number v-model="form.total_count" :min="0" />
          <span style="margin-left: 10px; color: #909399;">0表示不限量</span>
        </el-form-item>
        <el-form-item label="每人限领" prop="per_limit">
          <el-input-number v-model="form.per_limit" :min="1" />
        </el-form-item>
        <el-form-item label="有效期" prop="dateRange">
          <el-date-picker
            v-model="dateRange"
            type="datetimerange"
            range-separator="至"
            start-placeholder="开始时间"
            end-placeholder="结束时间"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
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
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { getCouponList, getCouponDetail, createCoupon, updateCoupon, deleteCoupon, type Coupon } from '@/api/coupon'
import ImagePicker from '@/components/ImagePicker.vue'

const typeNames: Record<number, string> = { 1: '满减券', 2: '折扣券', 3: '代金券' }

const searchForm = reactive({ keyword: '', type: '' as number | string, status: '' as number | string })
const list = ref<Coupon[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)
const dateRange = ref<[string, string] | null>(null)
const activeLang = ref('zh-tw')

// 初始翻译结构
const emptyTranslations = () => ({
  'zh-tw': { name: '', description: '' },
  'en-us': { name: '', description: '' },
  'ja-jp': { name: '', description: '' }
})

// 合并翻译数据，确保所有语言键都存在
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
  code: '', type: 1, value: 0, min_amount: 0, total_count: 0,
  per_limit: 1, start_time: '', end_time: '', status: 1, image: '',
  translations: emptyTranslations() as Record<string, { name: string; description: string }>
})

const rules: FormRules = {
  code: [{ required: true, message: '请输入代码', trigger: 'blur' }],
  value: [{ required: true, message: '请输入优惠值', trigger: 'blur' }],
}

watch(dateRange, (val) => {
  if (val) { form.start_time = val[0]; form.end_time = val[1] }
  else { form.start_time = ''; form.end_time = '' }
})

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getCouponList({ page: pagination.page, pageSize: pagination.pageSize, ...searchForm })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) { console.error('获取列表失败:', error) }
  finally { loading.value = false }
}

const handleSearch = () => { pagination.page = 1; fetchList() }
const handleReset = () => { searchForm.keyword = ''; searchForm.type = ''; searchForm.status = ''; handleSearch() }
const handleSizeChange = () => fetchList()
const handleCurrentChange = () => fetchList()

const handleAdd = () => {
  isEdit.value = false
  formDialogVisible.value = true
  dateRange.value = null
  activeLang.value = 'zh-tw'
  Object.assign(form, {
    code: '', type: 1, value: 0, min_amount: 0, total_count: 0,
    per_limit: 1, start_time: '', end_time: '', status: 1, image: '',
    translations: emptyTranslations()
  })
}

const handleEdit = async (row: Coupon) => {
  isEdit.value = true
  editId.value = row.id
  activeLang.value = 'zh-tw'
  try {
    const res: any = await getCouponDetail(row.id)
    const data = res.data
    dateRange.value = [data.start_time, data.end_time]
    Object.assign(form, {
      code: data.code,
      type: data.type,
      value: data.value,
      min_amount: data.min_amount,
      total_count: data.total_count,
      per_limit: data.per_limit,
      start_time: data.start_time,
      end_time: data.end_time,
      status: data.status,
      image: data.image || '',
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
    ElMessage.warning('请至少填写一种语言的名称')
    return
  }
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      const submitData = { ...form }
      if (isEdit.value) {
        await updateCoupon(editId.value, submitData)
        ElMessage.success('更新成功')
      } else {
        await createCoupon(submitData)
        ElMessage.success('创建成功')
      }
      formDialogVisible.value = false
      fetchList()
    } catch (error) { console.error('提交失败:', error) }
    finally { submitting.value = false }
  })
}

const handleDelete = async (row: Coupon) => {
  try {
    await ElMessageBox.confirm(`确定删除优惠券"${row.name}"吗？`, '提示', { type: 'warning' })
    await deleteCoupon(row.id); ElMessage.success('删除成功'); fetchList()
  } catch (error) { if (error !== 'cancel') console.error('删除失败:', error) }
}

onMounted(() => fetchList())
</script>

<style scoped>
.coupon-list { padding: 20px; }
.search-card { margin-bottom: 20px; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.pagination-wrapper { margin-top: 20px; display: flex; justify-content: flex-end; }
.lang-tabs { width: 100%; }
.lang-tabs :deep(.el-tabs__content) { padding: 15px; background: #f9f9f9; border-radius: 4px; }
.image-tip { margin-top: 8px; font-size: 12px; color: #909399; }
</style>
