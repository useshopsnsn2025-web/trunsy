<template>
  <div class="banner-list">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>广告Banner管理</span>
          <el-button type="primary" @click="handleAdd">新增Banner</el-button>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="预览" width="180">
          <template #default="{ row }">
            <!-- 图片型 -->
            <el-image v-if="row.display_type === 1" :src="row.image" style="width: 150px; height: 75px;" fit="cover" />
            <!-- 背景色型 -->
            <div v-else class="preview-color-banner" :style="getPreviewStyle(row)">
              <span class="preview-title" :style="{ color: row.text_color || '#fff' }">{{ row.title }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="150" />
        <el-table-column label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.display_type === 1 ? 'primary' : 'success'" size="small">
              {{ row.display_type === 1 ? '图片型' : '背景色型' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="position" label="位置" width="120">
          <template #default="{ row }">
            {{ positionNames[row.position] || row.position }}
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column prop="click_count" label="点击" width="80" />
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
          layout="total, prev, pager, next"
          @current-change="fetchList"
        />
      </div>
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog v-model="formDialogVisible" :title="isEdit ? '编辑Banner' : '新增Banner'" width="700px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <!-- 展示类型选择 -->
        <el-form-item label="展示类型" prop="display_type">
          <el-radio-group v-model="form.display_type">
            <el-radio :value="1">图片型</el-radio>
            <el-radio :value="2">背景色型</el-radio>
          </el-radio-group>
        </el-form-item>

        <!-- 多语言输入 -->
        <el-form-item label="多语言内容" required>
          <el-tabs v-model="activeLang" type="card" class="lang-tabs">
            <el-tab-pane label="繁體中文" name="zh-tw">
              <el-form-item label="標題" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].title" placeholder="請輸入標題" />
              </el-form-item>
              <el-form-item label="副標題" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].subtitle" placeholder="副標題（可選）" />
              </el-form-item>
              <el-form-item v-if="form.display_type === 2" label="內容" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['zh-tw'].content" type="textarea" :rows="2" placeholder="內容描述（可選）" />
              </el-form-item>
              <el-form-item v-if="form.display_type === 2" label="按鈕文字" label-width="80px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['zh-tw'].button_text" placeholder="如：立即開始、領取優惠券" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="English" name="en-us">
              <el-form-item label="Title" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].title" placeholder="Enter title" />
              </el-form-item>
              <el-form-item label="Subtitle" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].subtitle" placeholder="Subtitle (optional)" />
              </el-form-item>
              <el-form-item v-if="form.display_type === 2" label="Content" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['en-us'].content" type="textarea" :rows="2" placeholder="Content description (optional)" />
              </el-form-item>
              <el-form-item v-if="form.display_type === 2" label="Button" label-width="80px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['en-us'].button_text" placeholder="e.g. Start now, Get coupon" />
              </el-form-item>
            </el-tab-pane>
            <el-tab-pane label="日本語" name="ja-jp">
              <el-form-item label="タイトル" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].title" placeholder="タイトルを入力" />
              </el-form-item>
              <el-form-item label="サブ" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].subtitle" placeholder="サブタイトル（任意）" />
              </el-form-item>
              <el-form-item v-if="form.display_type === 2" label="内容" label-width="80px" style="margin-bottom: 12px;">
                <el-input v-model="form.translations['ja-jp'].content" type="textarea" :rows="2" placeholder="説明文（任意）" />
              </el-form-item>
              <el-form-item v-if="form.display_type === 2" label="ボタン" label-width="80px" style="margin-bottom: 0;">
                <el-input v-model="form.translations['ja-jp'].button_text" placeholder="例：今すぐ開始" />
              </el-form-item>
            </el-tab-pane>
          </el-tabs>
        </el-form-item>

        <!-- 图片型：上传图片 -->
        <el-form-item v-if="form.display_type === 1" label="Banner图片" prop="image">
          <ImagePicker v-model="form.image" width="300px" height="150px" />
        </el-form-item>

        <!-- 背景色型：背景设置 -->
        <template v-if="form.display_type === 2">
          <el-form-item label="背景类型">
            <el-radio-group v-model="bgType">
              <el-radio value="color">纯色</el-radio>
              <el-radio value="gradient">渐变色</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item v-if="bgType === 'color'" label="背景色">
            <el-color-picker v-model="form.bg_color" show-alpha />
            <span style="margin-left: 12px; color: #999;">{{ form.bg_color }}</span>
          </el-form-item>
          <el-form-item v-if="bgType === 'gradient'" label="渐变色">
            <div class="gradient-inputs">
              <el-color-picker v-model="gradientStart" />
              <span style="margin: 0 8px;">→</span>
              <el-color-picker v-model="gradientEnd" />
              <el-select v-model="gradientDirection" style="width: 120px; margin-left: 12px;">
                <el-option label="从左到右" value="to right" />
                <el-option label="从上到下" value="to bottom" />
                <el-option label="对角线" value="135deg" />
              </el-select>
            </div>
            <div class="gradient-preview" :style="{ background: form.bg_gradient }"></div>
          </el-form-item>
          <el-form-item label="文字颜色">
            <el-color-picker v-model="form.text_color" />
            <span style="margin-left: 12px; color: #999;">{{ form.text_color }}</span>
          </el-form-item>
          <el-form-item label="按钮样式">
            <el-radio-group v-model="form.button_style">
              <el-radio :value="1">实心黑</el-radio>
              <el-radio :value="2">实心主题色</el-radio>
              <el-radio :value="3">边框</el-radio>
              <el-radio :value="4">自定义颜色</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item v-if="form.button_style === 4" label="按钮颜色">
            <el-color-picker v-model="form.button_color" />
            <span style="margin-left: 12px; color: #999;">{{ form.button_color }}</span>
          </el-form-item>
          <el-form-item label="产品图片">
            <div class="product-images-wrapper">
              <div v-for="(img, idx) in form.product_images" :key="idx" class="product-image-item">
                <el-image :src="img" style="width: 60px; height: 60px;" fit="cover" />
                <el-button type="danger" link size="small" @click="removeProductImage(idx)">删除</el-button>
              </div>
              <ImagePicker v-if="form.product_images.length < 4" v-model="newProductImage" width="60px" height="60px" @update:modelValue="addProductImage" />
            </div>
            <div class="form-tip">最多添加4张产品展示图（可选）</div>
          </el-form-item>
        </template>

        <el-form-item label="位置" prop="position">
          <el-select v-model="form.position" style="width: 100%">
            <el-option-group label="首页">
              <el-option label="首页顶部轮播" value="home_top" />
              <el-option label="首页中部横幅" value="home_middle" />
              <el-option label="首页底部" value="home_bottom" />
            </el-option-group>
            <el-option-group label="分类相关">
              <el-option label="分类页顶部" value="category_top" />
              <el-option label="搜索页顶部" value="search_top" />
            </el-option-group>
            <el-option-group label="购物相关">
              <el-option label="购物车顶部" value="cart_top" />
              <el-option label="购物车底部" value="cart_bottom" />
            </el-option-group>
            <el-option-group label="活动相关">
              <el-option label="活动列表顶部" value="promotion_top" />
              <el-option label="优惠券中心顶部" value="coupon_top" />
            </el-option-group>
            <el-option-group label="用户中心">
              <el-option label="个人中心顶部" value="profile_top" />
              <el-option label="钱包页顶部" value="wallet_top" />
              <el-option label="收藏夹顶部" value="favorites_top" />
            </el-option-group>
            <el-option-group label="卖家中心">
              <el-option label="卖家中心顶部" value="sell_top" />
              <el-option label="发布商品页顶部" value="publish_top" />
            </el-option-group>
            <el-option-group label="其他">
              <el-option label="信用购页顶部" value="credit_top" />
              <el-option label="启动页/开屏广告" value="splash" />
            </el-option-group>
          </el-select>
        </el-form-item>
        <el-form-item label="链接类型" prop="link_type">
          <el-select v-model="form.link_type" style="width: 100%" @change="onLinkTypeChange">
            <el-option-group label="基础">
              <el-option label="无链接" :value="0" />
              <el-option label="外部链接" :value="4" />
              <el-option label="自定义页面" :value="15" />
            </el-option-group>
            <el-option-group label="商品相关">
              <el-option label="商品详情" :value="1" />
              <el-option label="分类页" :value="2" />
              <el-option label="品牌页" :value="5" />
              <el-option label="搜索结果" :value="6" />
            </el-option-group>
            <el-option-group label="营销活动">
              <el-option label="活动详情" :value="3" />
              <el-option label="优惠券中心" :value="7" />
            </el-option-group>
            <el-option-group label="用户中心">
              <el-option label="购物车" :value="8" />
              <el-option label="钱包" :value="9" />
              <el-option label="收藏夹" :value="10" />
              <el-option label="订单列表" :value="11" />
              <el-option label="发布商品" :value="12" />
              <el-option label="信用购" :value="13" />
              <el-option label="卖家中心" :value="14" />
            </el-option-group>
          </el-select>
        </el-form-item>
        <el-form-item v-if="needsLinkValue" :label="linkValueLabel" prop="link_value">
          <el-input v-model="form.link_value" :placeholder="linkValuePlaceholder" />
          <div class="form-tip">{{ linkValueTip }}</div>
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>

        <!-- 预览 -->
        <el-form-item label="效果预览">
          <div class="banner-preview" :style="getFormPreviewStyle()">
            <template v-if="form.display_type === 1">
              <img v-if="form.image" :src="form.image" class="preview-img" />
              <div v-else class="preview-placeholder">请上传图片</div>
            </template>
            <template v-else>
              <div class="preview-color-content">
                <div class="preview-text-area">
                  <div class="preview-main-title" :style="{ color: form.text_color }">
                    {{ form.translations[activeLang]?.title || '标题预览' }}
                  </div>
                  <div class="preview-main-subtitle" :style="{ color: form.text_color }">
                    {{ form.translations[activeLang]?.subtitle || '' }}
                  </div>
                  <div v-if="form.translations[activeLang]?.content" class="preview-main-content" :style="{ color: form.text_color }">
                    {{ form.translations[activeLang]?.content }}
                  </div>
                  <div v-if="form.translations[activeLang]?.button_text" class="preview-button" :class="'btn-style-' + form.button_style" :style="form.button_style === 4 ? { background: form.button_color, color: '#fff' } : {}">
                    {{ form.translations[activeLang]?.button_text }}
                  </div>
                </div>
                <div v-if="form.product_images.length > 0" class="preview-products">
                  <img v-for="(img, idx) in form.product_images" :key="idx" :src="img" class="preview-product-img" />
                </div>
              </div>
            </template>
          </div>
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
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { getBannerList, getBannerDetail, createBanner, updateBanner, deleteBanner, type Banner } from '@/api/banner'
import ImagePicker from '@/components/ImagePicker.vue'

const positionNames: Record<string, string> = {
  // 首页
  home_top: '首页顶部轮播',
  home_middle: '首页中部横幅',
  home_bottom: '首页底部',
  // 分类相关
  category_top: '分类页顶部',
  search_top: '搜索页顶部',
  // 购物相关
  cart_top: '购物车顶部',
  cart_bottom: '购物车底部',
  // 活动相关
  promotion_top: '活动列表顶部',
  coupon_top: '优惠券中心顶部',
  // 用户中心
  profile_top: '个人中心顶部',
  wallet_top: '钱包页顶部',
  favorites_top: '收藏夹顶部',
  // 卖家中心
  sell_top: '卖家中心顶部',
  publish_top: '发布商品页顶部',
  // 其他
  credit_top: '信用购页顶部',
  splash: '启动页/开屏广告',
  // 兼容旧数据
  category: '分类页'
}

const list = ref<Banner[]>([])
const loading = ref(false)
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })

const formDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref(0)
const formRef = ref<FormInstance>()
const submitting = ref(false)
const activeLang = ref('zh-tw')

// 背景色相关
const bgType = ref<'color' | 'gradient'>('color')
const gradientStart = ref('#a8e063')
const gradientEnd = ref('#56ab2f')
const gradientDirection = ref('135deg')
const newProductImage = ref('')

// 监听渐变色变化，更新 bg_gradient
watch([gradientStart, gradientEnd, gradientDirection], () => {
  if (bgType.value === 'gradient') {
    form.bg_gradient = `linear-gradient(${gradientDirection.value}, ${gradientStart.value}, ${gradientEnd.value})`
  }
}, { immediate: true })

watch(bgType, (val) => {
  if (val === 'color') {
    form.bg_gradient = null
    if (!form.bg_color) form.bg_color = '#f5f5f5'
  } else {
    form.bg_color = null
    form.bg_gradient = `linear-gradient(${gradientDirection.value}, ${gradientStart.value}, ${gradientEnd.value})`
  }
})

// 初始翻译结构
const emptyTranslations = () => ({
  'zh-tw': { title: '', subtitle: '', content: '', button_text: '' },
  'en-us': { title: '', subtitle: '', content: '', button_text: '' },
  'ja-jp': { title: '', subtitle: '', content: '', button_text: '' }
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

interface FormType {
  image: string
  link_type: number
  link_value: string
  position: string
  display_type: number
  bg_color: string | null
  bg_gradient: string | null
  text_color: string
  button_style: number
  button_color: string | null
  product_images: string[]
  sort: number
  status: number
  translations: Record<string, { title: string; subtitle: string; content: string; button_text: string }>
}

const form = reactive<FormType>({
  image: '',
  link_type: 0,
  link_value: '',
  position: 'home_top',
  display_type: 1,
  bg_color: '#f5f5f5',
  bg_gradient: null,
  text_color: '#333333',
  button_style: 1,
  button_color: null,
  product_images: [],
  sort: 0,
  status: 1,
  translations: emptyTranslations()
})

const rules: FormRules = {}

// 链接类型配置
const linkTypeConfig: Record<number, { needsValue: boolean, label: string, placeholder: string, tip: string }> = {
  0: { needsValue: false, label: '', placeholder: '', tip: '' },
  1: { needsValue: true, label: '商品ID', placeholder: '请输入商品ID', tip: '输入要跳转的商品ID' },
  2: { needsValue: true, label: '分类ID（可选）', placeholder: '留空则跳转分类首页', tip: '输入分类ID跳转指定分类，留空则跳转分类首页' },
  3: { needsValue: true, label: '活动ID', placeholder: '请输入活动ID', tip: '输入要跳转的活动ID' },
  4: { needsValue: true, label: '链接地址', placeholder: '请输入完整URL，如 https://example.com', tip: '输入完整的外部链接地址' },
  5: { needsValue: true, label: '品牌ID', placeholder: '请输入品牌ID', tip: '输入要跳转的品牌ID' },
  6: { needsValue: true, label: '搜索关键词', placeholder: '请输入搜索关键词', tip: '用户点击后将直接搜索该关键词' },
  7: { needsValue: false, label: '', placeholder: '', tip: '' },
  8: { needsValue: false, label: '', placeholder: '', tip: '' },
  9: { needsValue: false, label: '', placeholder: '', tip: '' },
  10: { needsValue: false, label: '', placeholder: '', tip: '' },
  11: { needsValue: false, label: '', placeholder: '', tip: '' },
  12: { needsValue: false, label: '', placeholder: '', tip: '' },
  13: { needsValue: false, label: '', placeholder: '', tip: '' },
  14: { needsValue: false, label: '', placeholder: '', tip: '' },
  15: { needsValue: true, label: '页面路径', placeholder: '如 /pages/promotion/index', tip: '输入APP内页面路径，以 /pages/ 开头' },
}

const needsLinkValue = computed(() => linkTypeConfig[form.link_type]?.needsValue ?? false)
const linkValueLabel = computed(() => linkTypeConfig[form.link_type]?.label ?? '链接值')
const linkValuePlaceholder = computed(() => linkTypeConfig[form.link_type]?.placeholder ?? '')
const linkValueTip = computed(() => linkTypeConfig[form.link_type]?.tip ?? '')

const onLinkTypeChange = () => {
  // 切换链接类型时清空链接值
  form.link_value = ''
}

// 列表预览样式
const getPreviewStyle = (row: Banner) => {
  if (row.bg_gradient) {
    return { background: row.bg_gradient }
  }
  return { background: row.bg_color || '#f5f5f5' }
}

// 表单预览样式
const getFormPreviewStyle = () => {
  if (form.display_type === 1) {
    return {}
  }
  if (form.bg_gradient) {
    return { background: form.bg_gradient }
  }
  return { background: form.bg_color || '#f5f5f5' }
}

// 添加产品图片
const addProductImage = (url: string) => {
  if (url && form.product_images.length < 4) {
    form.product_images.push(url)
    newProductImage.value = ''
  }
}

// 删除产品图片
const removeProductImage = (idx: number) => {
  form.product_images.splice(idx, 1)
}

const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getBannerList({ page: pagination.page, pageSize: pagination.pageSize })
    list.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) { console.error('获取列表失败:', error) }
  finally { loading.value = false }
}

const handleAdd = () => {
  isEdit.value = false
  formDialogVisible.value = true
  activeLang.value = 'zh-tw'
  bgType.value = 'color'
  gradientStart.value = '#a8e063'
  gradientEnd.value = '#56ab2f'
  gradientDirection.value = '135deg'
  newProductImage.value = ''
  Object.assign(form, {
    image: '',
    link_type: 0,
    link_value: '',
    position: 'home_top',
    display_type: 1,
    bg_color: '#f5f5f5',
    bg_gradient: null,
    text_color: '#333333',
    button_style: 1,
    button_color: null,
    product_images: [],
    sort: 0,
    status: 1,
    translations: emptyTranslations()
  })
}

const handleEdit = async (row: Banner) => {
  isEdit.value = true
  editId.value = row.id
  activeLang.value = 'zh-tw'
  newProductImage.value = ''
  try {
    const res: any = await getBannerDetail(row.id)
    const data = res.data

    // 解析渐变色
    if (data.bg_gradient) {
      bgType.value = 'gradient'
      // 尝试解析渐变色
      const match = data.bg_gradient.match(/linear-gradient\(([^,]+),\s*([^,]+),\s*([^)]+)\)/)
      if (match) {
        gradientDirection.value = match[1].trim()
        gradientStart.value = match[2].trim()
        gradientEnd.value = match[3].trim()
      }
    } else {
      bgType.value = 'color'
    }

    Object.assign(form, {
      image: data.image || '',
      link_type: data.link_type,
      link_value: data.link_value || '',
      position: data.position,
      display_type: data.display_type || 1,
      bg_color: data.bg_color || '#f5f5f5',
      bg_gradient: data.bg_gradient || null,
      text_color: data.text_color || '#333333',
      button_style: data.button_style || 1,
      button_color: data.button_color || null,
      product_images: data.product_images || [],
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
  // 验证至少填写一种语言的标题
  if (!form.translations['zh-tw'].title && !form.translations['en-us'].title && !form.translations['ja-jp'].title) {
    ElMessage.warning('请至少填写一种语言的标题')
    return
  }
  // 图片型验证
  if (form.display_type === 1 && !form.image) {
    ElMessage.warning('请上传Banner图片')
    return
  }
  // 背景色型验证
  if (form.display_type === 2 && !form.bg_color && !form.bg_gradient) {
    ElMessage.warning('请设置背景色')
    return
  }

  submitting.value = true
  try {
    if (isEdit.value) {
      await updateBanner(editId.value, form)
      ElMessage.success('更新成功')
    } else {
      await createBanner(form)
      ElMessage.success('创建成功')
    }
    formDialogVisible.value = false
    fetchList()
  } catch (error) { console.error('提交失败:', error) }
  finally { submitting.value = false }
}

const handleDelete = async (row: Banner) => {
  try {
    await ElMessageBox.confirm(`确定删除"${row.title}"吗？`, '提示', { type: 'warning' })
    await deleteBanner(row.id); ElMessage.success('删除成功'); fetchList()
  } catch (error) { if (error !== 'cancel') console.error('删除失败:', error) }
}

onMounted(() => fetchList())
</script>

<style scoped>
.banner-list { padding: 20px; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.pagination-wrapper { margin-top: 20px; display: flex; justify-content: flex-end; }
.lang-tabs { width: 100%; }
.lang-tabs :deep(.el-tabs__content) { padding: 15px; background: #f9f9f9; border-radius: 4px; }

/* 列表预览 */
.preview-color-banner {
  width: 150px;
  height: 75px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.preview-title {
  font-size: 12px;
  font-weight: 600;
  text-align: center;
  padding: 4px;
}

/* 渐变色输入 */
.gradient-inputs {
  display: flex;
  align-items: center;
}
.gradient-preview {
  margin-top: 8px;
  width: 200px;
  height: 30px;
  border-radius: 4px;
  border: 1px solid #ddd;
}

/* 产品图片 */
.product-images-wrapper {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}
.product-image-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.form-tip {
  font-size: 12px;
  color: #999;
  margin-top: 8px;
}

/* 表单预览 */
.banner-preview {
  width: 100%;
  min-height: 120px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #eee;
}
.preview-img {
  width: 100%;
  height: 120px;
  object-fit: cover;
}
.preview-placeholder {
  width: 100%;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  color: #999;
}
.preview-color-content {
  display: flex;
  justify-content: space-between;
  padding: 20px;
  min-height: 120px;
}
.preview-text-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.preview-main-title {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 6px;
}
.preview-main-subtitle {
  font-size: 14px;
  margin-bottom: 4px;
  opacity: 0.9;
}
.preview-main-content {
  font-size: 13px;
  opacity: 0.8;
  margin-bottom: 12px;
}
.preview-button {
  display: inline-block;
  padding: 8px 20px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  width: fit-content;
}
.preview-button.btn-style-1 {
  background: #000;
  color: #fff;
}
.preview-button.btn-style-2 {
  background: #409eff;
  color: #fff;
}
.preview-button.btn-style-3 {
  background: transparent;
  border: 2px solid currentColor;
}
.preview-products {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-left: 20px;
}
.preview-product-img {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  object-fit: cover;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>
