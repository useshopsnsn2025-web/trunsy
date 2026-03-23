// 购物车状态管理
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  getCartList,
  addToCart as apiAddToCart,
  updateCartItem as apiUpdateCartItem,
  removeFromCart as apiRemoveFromCart,
  clearCart as apiClearCart,
  getCartCount,
  selectAllCart as apiSelectAllCart,
  type CartItem,
  type AddToCartParams,
  type UpdateCartParams,
} from '@/api/cart'
import { useUserStore } from './user'
import { useAppStore } from './app'
import { convertAmount } from '@/utils/currency'

export const useCartStore = defineStore('cart', () => {
  // 状态
  const items = ref<CartItem[]>([])
  const loading = ref(false)
  const count = ref(0)

  // 计算属性
  const totalCount = computed(() => count.value)

  const selectedItems = computed(() => items.value.filter(item => item.selected))

  const selectedCount = computed(() => selectedItems.value.length)

  const isAllSelected = computed(() => {
    if (items.value.length === 0) return false
    return items.value.every(item => item.selected)
  })

  // 计算总金额（使用活动价格并进行汇率转换）
  const totalAmount = computed(() => {
    const appStore = useAppStore()
    const targetCurrency = appStore.currency
    const rates = appStore.exchangeRates

    return selectedItems.value.reduce((sum, item) => {
      // 优先使用活动价格
      const unitPrice = item.goods.promotion?.promotionPrice ?? item.goods.price
      const originalCurrency = item.goods.currency || 'USD'

      // 进行汇率转换
      let convertedPrice = unitPrice
      if (Object.keys(rates).length > 0 && originalCurrency !== targetCurrency) {
        convertedPrice = convertAmount(unitPrice, originalCurrency, targetCurrency, rates)
      }

      return sum + convertedPrice * item.quantity
    }, 0)
  })

  // 计算总运费（进行汇率转换）
  const totalShipping = computed(() => {
    const appStore = useAppStore()
    const targetCurrency = appStore.currency
    const rates = appStore.exchangeRates

    return selectedItems.value.reduce((sum, item) => {
      if (item.goods.freeShipping) return sum

      const shippingFee = item.goods.shippingFee || 0
      const originalCurrency = item.goods.currency || 'USD'

      // 进行汇率转换
      let convertedFee = shippingFee
      if (Object.keys(rates).length > 0 && originalCurrency !== targetCurrency) {
        convertedFee = convertAmount(shippingFee, originalCurrency, targetCurrency, rates)
      }

      return sum + convertedFee
    }, 0)
  })

  const grandTotal = computed(() => totalAmount.value + totalShipping.value)

  // 获取购物车列表
  async function fetchCart() {
    const userStore = useUserStore()
    if (!userStore.isLoggedIn) {
      items.value = []
      count.value = 0
      return
    }

    loading.value = true
    try {
      const res = await getCartList()
      items.value = res.data.list || []
      count.value = items.value.length
    } catch (e) {
      console.error('Failed to fetch cart:', e)
    } finally {
      loading.value = false
    }
  }

  // 获取购物车数量（轻量级）
  async function fetchCount() {
    const userStore = useUserStore()
    if (!userStore.isLoggedIn) {
      count.value = 0
      return
    }

    try {
      const res = await getCartCount()
      count.value = res.data.count || 0
    } catch (e) {
      console.error('Failed to fetch cart count:', e)
    }
  }

  // 添加到购物车
  async function addToCart(params: AddToCartParams) {
    try {
      await apiAddToCart(params)
      count.value++
      // 如果购物车列表已加载，刷新列表
      if (items.value.length > 0) {
        await fetchCart()
      }
      return true
    } catch (e) {
      console.error('Failed to add to cart:', e)
      return false
    }
  }

  // 更新购物车项
  async function updateItem(id: number, params: UpdateCartParams) {
    try {
      await apiUpdateCartItem(id, params)
      // 本地更新
      const index = items.value.findIndex(item => item.id === id)
      if (index !== -1) {
        if (params.quantity !== undefined) {
          items.value[index].quantity = params.quantity
        }
        if (params.selected !== undefined) {
          items.value[index].selected = params.selected
        }
      }
      return true
    } catch (e) {
      console.error('Failed to update cart item:', e)
      return false
    }
  }

  // 从购物车移除
  async function removeItem(id: number) {
    try {
      await apiRemoveFromCart(id)
      items.value = items.value.filter(item => item.id !== id)
      count.value = Math.max(0, count.value - 1)
      return true
    } catch (e) {
      console.error('Failed to remove from cart:', e)
      return false
    }
  }

  // 清空购物车
  async function clearAll() {
    try {
      await apiClearCart()
      items.value = []
      count.value = 0
      return true
    } catch (e) {
      console.error('Failed to clear cart:', e)
      return false
    }
  }

  // 全选/取消全选
  async function selectAll(selected: boolean) {
    try {
      await apiSelectAllCart(selected)
      items.value.forEach(item => {
        item.selected = selected
      })
      return true
    } catch (e) {
      console.error('Failed to select all:', e)
      return false
    }
  }

  // 重置状态（登出时调用）
  function reset() {
    items.value = []
    count.value = 0
    loading.value = false
  }

  return {
    // 状态
    items,
    loading,
    count,
    // 计算属性
    totalCount,
    selectedItems,
    selectedCount,
    isAllSelected,
    totalAmount,
    totalShipping,
    grandTotal,
    // 方法
    fetchCart,
    fetchCount,
    addToCart,
    updateItem,
    removeItem,
    clearAll,
    selectAll,
    reset,
  }
})
