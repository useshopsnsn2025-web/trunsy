// 商品相关 API
import { get, post, put, del } from '@/utils/request'
import { API_PATHS } from '@/config/api'

// 商品列表查询参数
export interface GoodsListParams {
  page?: number
  pageSize?: number
  categoryId?: number
  keyword?: string
  minPrice?: number
  maxPrice?: number
  condition?: number
  sort?: 'newest' | 'price_asc' | 'price_desc' | 'popular'
  type?: 1 | 2 // 1: C2C, 2: B2C
  shopId?: number
  userId?: number
  brand?: string   // 品牌筛选
  model?: string   // 型号/系列筛选
}

// 商品信息
export interface Goods {
  id: number
  goodsNo: string
  userId: number
  shopId?: number
  categoryId: number
  type: number
  condition: number
  price: number
  originalPrice?: number
  currency: string
  stock: number
  soldCount: number
  images: string[]
  video?: string
  locationCountry?: string
  locationCity?: string
  shippingFee: number
  freeShipping: boolean
  views: number
  likes: number
  isNegotiable: boolean
  status: number
  statusText?: string // 状态文本: active, off, pending, sold 等
  title: string
  description: string
  createdAt: string
  updatedAt: string
  // 关联信息
  seller?: {
    id: number
    uuid: string
    nickname: string
    avatar: string
    rating?: number
    goodsCount?: number
    isService?: boolean  // 是否为客服（虚拟卖家）
    serviceId?: number   // 客服ID，用于聊天
    isOnline?: boolean   // 客服是否在线
    isVerified?: boolean // 是否已认证
  }
  shop?: {
    id: number
    shopNo: string
    name: string
    logo?: string
    rating: number
  }
  category?: {
    id: number
    name: string
  }
  isLiked?: boolean
  // 商品规格属性
  specs?: Array<{
    key: string
    label: string
    value: string
  }>
  // 系统配置的快递设置
  expressDelivery?: string
  // 活动信息（如果商品参与进行中的活动）
  promotion?: {
    id: number
    name: string
    type: number // 1: 限时折扣, 2: 满减, 3: 秒杀, 4: 拼团
    promotionPrice: number
    discount: number // 折扣率 0.1-1
    discountPercent: number // 折扣百分比，如 20 表示 8折
    stock?: number
    soldCount?: number
    limitPerUser?: number
    startTime?: string
    endTime: string
  }
  // 商品状态配置值
  conditionValues?: Array<{
    groupId: number
    groupName: string
    groupIcon?: string
    optionId: number
    optionName: string
    impactLevel?: number | null
    sort: number
  }>
}

// 商品列表响应
export interface GoodsListResponse {
  list: Goods[]
  total: number
  page: number
  pageSize: number
  totalPages: number
}

// 发布商品参数
export interface PublishGoodsParams {
  categoryId: number
  title: string
  description: string
  condition: number
  price: number
  originalPrice?: number
  currency?: string
  stock?: number
  images: string[]
  video?: string
  locationCountry?: string
  locationCity?: string
  shippingFee?: number
  freeShipping?: boolean
  isNegotiable?: boolean
  specs?: Record<string, string | string[]> // 商品规格属性
  conditionValues?: Record<number, number> // 商品状态属性 groupId -> optionId
}

// 分类信息
export interface Category {
  id: number
  parentId: number
  name: string
  icon?: string
  image?: string
  children?: Category[]
  goods_count?: number  // 商品数量（热门分类返回）
}

// 品牌信息
export interface Brand {
  id: number
  name: string
  logo: string
  icon?: string
  sort: number
  status: number
}

// 属性选项
export interface AttributeOption {
  value: string
  label: string
  image?: string | null  // 选项图片（如品牌logo、颜色色块）
  parent_value?: string | null  // 用于联动筛选，如型号根据品牌筛选
}

// 品类属性
export interface CategoryAttribute {
  key: string
  name: string
  placeholder?: string
  input_type: 'select' | 'input' | 'multi_select'
  is_required: boolean
  parent_key?: string | null  // 父属性key，如model的parent_key是brand
  options: AttributeOption[]
}

// 品类属性响应
export interface CategoryAttributesResponse {
  category_id: number
  attributes: CategoryAttribute[]
}

// 商品条件选项
export interface ConditionOption {
  id: number
  name: string
  impact_level?: number | null
}

// 商品条件组
export interface ConditionGroup {
  id: number
  name: string
  icon?: string | null
  is_required: boolean
  options: ConditionOption[]
}

// 分类条件组响应
export interface CategoryConditionsResponse {
  category_id: number
  condition_groups: ConditionGroup[]
}

// 获取商品列表
export function getGoodsList(params: GoodsListParams & { is_hot?: number; is_recommend?: number }) {
  return get<GoodsListResponse>(API_PATHS.goods.list, params)
}

// 获取热门商品
export function getHotGoods(limit = 10) {
  return get<GoodsListResponse>(API_PATHS.goods.list, { page: 1, pageSize: limit, is_hot: 1, sort: 'popular' })
}

// 获取推荐商品
export function getRecommendGoods(limit = 10) {
  return get<GoodsListResponse>(API_PATHS.goods.list, { page: 1, pageSize: limit, is_recommend: 1, sort: 'newest' })
}

// 获取商品详情
export function getGoodsDetail(id: number | string) {
  return get<Goods>(API_PATHS.goods.detail(id))
}

// 发布商品
export function publishGoods(data: PublishGoodsParams) {
  return post<Goods>(API_PATHS.goods.create, data, { showLoading: true })
}

// 更新商品
export function updateGoods(id: number | string, data: Partial<PublishGoodsParams>) {
  return put<Goods>(API_PATHS.goods.update(id), data, { showLoading: true })
}

// 删除商品
export function deleteGoods(id: number | string) {
  return del(API_PATHS.goods.delete(id), undefined, { showLoading: true })
}

// 收藏/取消收藏商品
export function toggleLike(id: number | string) {
  return post<{ isLiked: boolean }>(API_PATHS.goods.like(id))
}

// 获取分类列表
export function getCategories() {
  return get<Category[]>(API_PATHS.categories)
}

// 获取热门分类列表
export function getHotCategories() {
  return get<Category[]>(API_PATHS.categoriesHot)
}

// 获取品牌列表
export function getBrands() {
  return get<Brand[]>(API_PATHS.brands)
}

// 获取分类属性模板
export function getCategoryAttributes(categoryId: number | string) {
  return get<CategoryAttributesResponse>(API_PATHS.categoryAttributes(categoryId))
}

// 获取分类商品条件组
export function getCategoryConditions(categoryId: number | string) {
  return get<CategoryConditionsResponse>(API_PATHS.categoryConditions(categoryId))
}

// 品牌系列型号信息
export interface ModelInfo {
  value: string
  name: string
  image: string | null
  min_price: number | null
}

// 品牌信息（含系列）
export interface BrandWithModels {
  brand_value: string
  brand_name: string
  brand_image: string | null
  models: ModelInfo[]
  goods_count?: number
  min_price?: number | null
}

// 分类品牌响应
export interface CategoryBrandsResponse {
  category_id: number
  category_name: string
  brands: BrandWithModels[]
}

// 获取分类下的品牌和系列
export function getCategoryBrands(categoryId: number | string) {
  return get<CategoryBrandsResponse>(API_PATHS.categoryBrands(categoryId))
}

// 品牌型号信息（跨分类）
export interface BrandModelInfo {
  value: string
  name: string
  image: string | null
  min_price: number | null
  category_id: number | null
}

// 品牌型号响应
export interface BrandModelsResponse {
  brand_value: string
  brand_name: string
  brand_image: string | null
  models: BrandModelInfo[]
}

// 获取品牌下的所有产品系列（跨分类）
export function getBrandModels(brandValue: string) {
  return get<BrandModelsResponse>(API_PATHS.brandModels(brandValue))
}

// 上传商品图片 - 使用统一的上传模块
export { uploadGoodsImage, uploadGoodsVideo } from './upload'

// 获取用户收藏列表
export function getFavorites(params: { page?: number; pageSize?: number }) {
  return get<GoodsListResponse>('/user/favorites', params)
}

// 获取浏览历史
export function getBrowseHistory(params: { page?: number; pageSize?: number }) {
  return get<GoodsListResponse>('/user/history', params)
}

// 搜索建议
export function getSearchSuggestions(keyword: string) {
  return get<string[]>('/goods/suggestions', { keyword })
}

// 热门搜索
export function getHotSearches() {
  return get<string[]>('/goods/hot-searches')
}

// 获取相似商品
export function getSimilarGoods(goodsId: number, limit = 10) {
  return get<Goods[]>(API_PATHS.goods.similar, { id: goodsId, limit })
}

// 获取用户也看了
export function getAlsoViewedGoods(goodsId: number, limit = 10) {
  return get<Goods[]>(API_PATHS.goods.alsoViewed, { id: goodsId, limit })
}

// 获取推荐商品
export function getRecommendations(excludeId: number, limit = 10) {
  return get<Goods[]>(API_PATHS.goods.recommendations, { exclude: excludeId, limit })
}

// 草稿相关类型
export interface GoodsDraft {
  id: number
  category_id: number | null
  category_name?: string
  title: string | null
  description: string | null
  images: string[]
  price: number | null
  condition_level: number | null
  is_negotiable: boolean
  free_shipping: boolean
  shipping_fee: number | null
  specs: Record<string, string | string[]>
  condition_values: Record<string, number>
  current_step: number
  updated_at: string
}

export interface SaveDraftParams {
  categoryId?: number | null
  title?: string
  description?: string
  images?: string[]
  price?: number | null
  condition?: number
  isNegotiable?: boolean
  freeShipping?: boolean
  shippingFee?: number | null
  specs?: Record<string, string | string[]>
  conditionValues?: Record<string, number>
  currentStep?: number
}

// 保存草稿
// AI 生成商品描述
export function generateDescription(data: { title: string; brand?: string; model?: string; condition?: string; specs?: Record<string, string | string[]> }) {
  return post<{ description: string; descriptions: Record<string, string> }>('/goods/generate-description', data, { timeout: 30000 })
}

export function saveDraft(data: SaveDraftParams) {
  return post<{ id: number; updated_at: string }>('/goods/draft', data)
}

// 获取草稿
export function getDraft() {
  return get<GoodsDraft | null>('/goods/draft')
}

// 删除草稿
export function deleteDraft() {
  return del('/goods/draft')
}

// 我的商品列表参数
export interface MyGoodsParams {
  page?: number
  pageSize?: number
  status?: 'all' | 'active' | 'pending' | 'sold' | 'off'
  sort?: 'newest' | 'price_asc' | 'price_desc' | 'views'
}

// 获取我的商品列表
export function getMyGoods(params: MyGoodsParams) {
  return get<GoodsListResponse>('/goods/my', params)
}

// 商品统计数据
export interface MyGoodsStats {
  total: number
  active: number
  pending: number
  sold: number
  off: number
  draft: number
}

// 获取我的商品统计
export function getMyGoodsStats() {
  return get<MyGoodsStats>('/goods/my/stats')
}

// 下架商品
export function offShelfGoods(id: number | string) {
  return post<{ status: string }>(`/goods/${id}/off-shelf`, undefined, { showLoading: true })
}

// 上架商品
export function onShelfGoods(id: number | string) {
  return post<{ status: string }>(`/goods/${id}/on-shelf`, undefined, { showLoading: true })
}

// 型号/系列统计数据
export interface ModelStats {
  goodsCount: number
  totalViews: number
  minPrice: number | null
  maxPrice: number | null
}

// 获取型号/系列统计数据
export function getModelStats(model: string, categoryId?: number) {
  return get<ModelStats>('/goods/model-stats', { model, categoryId })
}
