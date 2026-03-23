<template>
  <view class="category-picker" v-if="visible">
    <view class="mask" @click="handleClose"></view>
    <view class="picker-content">
      <view class="picker-header">
        <text class="title">{{ t('goods.fields.category') }}</text>
        <text class="close" @click="handleClose">×</text>
      </view>
      <view class="picker-body">
        <scroll-view scroll-y class="category-list">
          <view v-if="loading" class="loading">
            <text>{{ t('common.loading') }}</text>
          </view>
          <view v-else-if="categories.length === 0" class="empty">
            <text>{{ t('common.noData') }}</text>
          </view>
          <template v-else>
            <view
              v-for="category in displayCategories"
              :key="category.id"
              class="category-item"
              :class="{ active: isSelected(category), parent: category.children?.length }"
              @click="handleSelect(category)"
            >
              <image v-if="category.icon" :src="category.icon" class="category-icon" mode="aspectFit" />
              <text class="category-name">{{ category.name }}</text>
              <text v-if="category.children?.length" class="arrow">></text>
              <text v-else-if="isSelected(category)" class="check">✓</text>
            </view>
          </template>
        </scroll-view>
        <!-- 子分类列表 -->
        <scroll-view v-if="selectedParent" scroll-y class="subcategory-list">
          <view
            v-for="sub in selectedParent.children"
            :key="sub.id"
            class="category-item"
            :class="{ active: isSelected(sub) }"
            @click="handleSelectChild(sub)"
          >
            <text class="category-name">{{ sub.name }}</text>
            <text v-if="isSelected(sub)" class="check">✓</text>
          </view>
        </scroll-view>
      </view>
      <view class="picker-footer" v-if="selectedCategory">
        <button class="confirm-btn" @click="handleConfirm">
          {{ t('common.confirm') }}
        </button>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { getCategories, type Category } from '@/api/goods'

const { t } = useI18n()

const props = defineProps<{
  visible: boolean
  value?: Category | null
}>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'select', category: Category): void
}>()

const loading = ref(false)
const categories = ref<Category[]>([])
const selectedParent = ref<Category | null>(null)
const selectedCategory = ref<Category | null>(null)

// 显示的分类列表（顶级或选中父级的子级）
const displayCategories = computed(() => {
  return categories.value
})

// 判断是否选中
function isSelected(category: Category): boolean {
  return selectedCategory.value?.id === category.id
}

// 选择分类
function handleSelect(category: Category) {
  if (category.children?.length) {
    // 有子分类，展开
    selectedParent.value = selectedParent.value?.id === category.id ? null : category
  } else {
    // 无子分类，直接选择
    selectedCategory.value = category
    selectedParent.value = null
  }
}

// 选择子分类
function handleSelectChild(category: Category) {
  selectedCategory.value = category
}

// 确认选择
function handleConfirm() {
  if (selectedCategory.value) {
    emit('select', selectedCategory.value)
    handleClose()
  }
}

// 关闭
function handleClose() {
  emit('update:visible', false)
}

// 加载分类
async function loadCategories() {
  if (categories.value.length > 0) return

  loading.value = true
  try {
    const res = await getCategories()
    categories.value = res.data || []
  } catch (e) {
    console.error('Load categories failed:', e)
  } finally {
    loading.value = false
  }
}

// 监听显示状态
watch(() => props.visible, (val) => {
  if (val) {
    loadCategories()
    // 恢复已选择的值
    if (props.value) {
      selectedCategory.value = props.value
    }
  }
})

onMounted(() => {
  if (props.value) {
    selectedCategory.value = props.value
  }
})
</script>

<style lang="scss" scoped>
.category-picker {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
}

.mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
}

.picker-content {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: #fff;
  border-radius: 16px 16px 0 0;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
}

.picker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.close {
  font-size: 24px;
  color: #999;
  padding: 4px;
}

.picker-body {
  flex: 1;
  display: flex;
  overflow: hidden;
}

.category-list,
.subcategory-list {
  flex: 1;
  height: 300px;
}

.subcategory-list {
  border-left: 1px solid #f0f0f0;
  background-color: #fafafa;
}

.loading,
.empty {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  color: #999;
}

.category-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #f5f5f5;

  &.active {
    background-color: #fff5f0;
    color: #FF6B35;
  }

  &.parent.active {
    background-color: #f5f5f5;
    color: #333;
  }
}

.category-icon {
  width: 24px;
  height: 24px;
  margin-right: 12px;
}

.category-name {
  flex: 1;
  font-size: 14px;
}

.arrow {
  color: #ccc;
  font-size: 14px;
}

.check {
  color: #FF6B35;
  font-size: 16px;
  font-weight: bold;
}

.picker-footer {
  padding: 12px 16px;
  border-top: 1px solid #f0f0f0;
}

.confirm-btn {
  width: 100%;
  height: 44px;
  background-color: #FF6B35;
  color: #fff;
  border: none;
  border-radius: 22px;
  font-size: 16px;
  font-weight: 600;
}
</style>
