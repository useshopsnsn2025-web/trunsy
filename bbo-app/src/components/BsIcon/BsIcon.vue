<template>
  <text :class="iconClass" :style="iconStyle"></text>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  name: string // 图标名称，如 'heart', 'cart', 'search'
  size?: number | string // 图标大小
  color?: string // 图标颜色
}

const props = withDefaults(defineProps<Props>(), {
  size: 16,
  color: ''
})

const iconClass = computed(() => {
  return `bi bi-${props.name}`
})

const iconStyle = computed(() => {
  const style: Record<string, string> = {}

  if (props.size) {
    const sizeValue = typeof props.size === 'number' ? `${props.size}px` : props.size
    style.fontSize = sizeValue
  }

  if (props.color) {
    style.color = props.color
  }

  return style
})
</script>

<style scoped>
/* 确保图标在 uni-app 中正确显示 */
text {
  display: inline-block;
  line-height: 1;
}
</style>
