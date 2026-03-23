# NavBar 导航栏组件使用规范

## 概述

`NavBar` 是应用统一的顶部导航栏组件，用于保持所有页面导航栏的一致性。组件自动处理状态栏高度适配，支持标题左对齐/居中、返回按钮、右侧操作按钮等功能。

## 快速开始

```vue
<script setup lang="ts">
import NavBar from '@/components/NavBar.vue'
</script>

<template>
  <view class="page">
    <NavBar title="页面标题" />
    <!-- 页面内容 -->
  </view>
</template>
```

## 设计规范

### 1. 布局结构

```
┌─────────────────────────────────────────┐
│            状态栏 (自动适配)              │
├─────────────────────────────────────────┤
│  [←]  标题文字                    [操作] │
│  48px 高度                               │
└─────────────────────────────────────────┘
```

### 2. 标题对齐方式

| 场景 | 对齐方式 | 示例 |
|------|---------|------|
| 普通内页 | 左对齐 (默认) | 浏览历史、收藏夹、设置 |
| 详情页/弹窗式页面 | 居中 | 商品详情、个人资料编辑 |
| 首页/主Tab页 | 左对齐，无返回按钮 | 首页、分类、消息、我的 |

### 3. 颜色规范

| 元素 | 默认颜色 | 说明 |
|------|---------|------|
| 背景色 | `#FFFFFF` | 白色背景 |
| 标题/图标 | `#191919` | 深色文字 |
| 底部边框 | `#E5E5E5` | 浅灰色分隔线 |

### 4. 尺寸规范

| 元素 | 尺寸 |
|------|------|
| 导航栏高度 | 48px |
| 返回按钮区域 | 36x36px |
| 右侧操作按钮 | 36x36px |
| 标题字号 | 17px |
| 图标字号 | 22px |

## Props 属性

| 属性名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| `title` | `String` | `''` | 导航栏标题 |
| `titleAlign` | `'left' \| 'center'` | `'left'` | 标题对齐方式 |
| `showBack` | `Boolean` | `true` | 是否显示返回按钮 |
| `background` | `String` | `'#FFFFFF'` | 导航栏背景色 |
| `color` | `String` | `'#191919'` | 标题和图标颜色 |
| `transparent` | `Boolean` | `false` | 是否透明背景 |
| `border` | `Boolean` | `true` | 是否显示底部边框 |

## Events 事件

| 事件名 | 参数 | 说明 |
|--------|------|------|
| `back` | - | 点击返回按钮时触发 |

## Slots 插槽

| 插槽名 | 说明 |
|--------|------|
| `left` | 左侧区域，可替换默认返回按钮 |
| `right` | 右侧操作区域 |

## 使用示例

### 1. 基础用法

```vue
<NavBar title="浏览历史" />
```

### 2. 标题居中

```vue
<NavBar title="商品详情" title-align="center" />
```

### 3. 国际化标题

```vue
<NavBar :title="t('history.title')" />
```

### 4. 带右侧操作按钮

```vue
<NavBar :title="t('history.title')">
  <template #right>
    <view class="nav-action" @click="handleClear">
      <text class="bi bi-trash"></text>
    </view>
  </template>
</NavBar>
```

### 5. 多个右侧按钮

```vue
<NavBar title="设置">
  <template #right>
    <view class="nav-action" @click="handleSearch">
      <text class="bi bi-search"></text>
    </view>
    <view class="nav-action" @click="handleMore">
      <text class="bi bi-three-dots"></text>
    </view>
  </template>
</NavBar>
```

### 6. 无返回按钮 (首页)

```vue
<NavBar title="首页" :show-back="false" />
```

### 7. 自定义返回逻辑

```vue
<NavBar title="编辑" @back="handleCustomBack" />

<script setup>
function handleCustomBack() {
  // 自定义返回逻辑，如保存草稿提示
  uni.showModal({
    title: '提示',
    content: '是否保存草稿？',
    success: (res) => {
      if (res.confirm) {
        saveDraft()
      }
      uni.navigateBack()
    }
  })
}
</script>
```

### 8. 透明背景 (沉浸式)

```vue
<NavBar title="商品详情" transparent color="#FFFFFF" />
```

### 9. 自定义颜色

```vue
<NavBar title="活动页" background="#FF6B35" color="#FFFFFF" />
```

### 10. 无底部边框

```vue
<NavBar title="设置" :border="false" />
```

### 11. 条件渲染右侧按钮

```vue
<NavBar :title="t('history.title')">
  <template #right>
    <view v-if="items.length > 0" class="nav-action" @click="clearHistory">
      <text class="bi bi-archive"></text>
    </view>
  </template>
</NavBar>
```

## 右侧操作按钮样式

组件已内置 `.nav-action` 样式，直接使用即可：

```vue
<template #right>
  <view class="nav-action" @click="handleAction">
    <text class="bi bi-icon-name"></text>
  </view>
</template>
```

样式特性：
- 36x36px 点击区域
- 圆形按钮
- 点击态背景变化
- 图标 20px

## 图标使用

导航栏图标统一使用 Bootstrap Icons：

| 功能 | 图标类名 |
|------|---------|
| 返回 | `bi-arrow-left` |
| 搜索 | `bi-search` |
| 更多 | `bi-three-dots` |
| 删除 | `bi-trash` |
| 清空 | `bi-archive` |
| 分享 | `bi-share` |
| 设置 | `bi-gear` |
| 编辑 | `bi-pencil` |
| 关闭 | `bi-x` |

## 页面模板

```vue
<template>
  <view class="page">
    <!-- 导航栏 -->
    <NavBar :title="t('page.title')">
      <template #right>
        <!-- 可选的右侧操作按钮 -->
      </template>
    </NavBar>

    <!-- 页面内容 -->
    <scroll-view class="page-content" scroll-y>
      <!-- 内容区域 -->
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #F7F7F7;
}

.page-content {
  // 内容样式
}
</style>
```

## 注意事项

1. **组件自动处理占位**：NavBar 组件已包含占位元素，无需额外添加
2. **返回按钮默认行为**：默认调用 `uni.navigateBack()`，如无法返回则跳转首页
3. **状态栏适配**：组件自动获取状态栏高度并适配
4. **z-index**：导航栏 z-index 为 100，确保在页面内容之上
5. **国际化**：标题建议使用 i18n 的 `t()` 函数

## 迁移指南

从旧的自定义导航栏迁移到 NavBar 组件：

### 旧代码
```vue
<view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
  <view class="nav-bar-content">
    <view class="nav-back" @click="goBack">
      <text class="bi bi-arrow-left"></text>
    </view>
    <text class="nav-title">{{ t('page.title') }}</text>
    <view class="nav-action" @click="handleAction">
      <text class="bi bi-icon"></text>
    </view>
  </view>
</view>
<view class="nav-bar-placeholder" :style="{ height: (statusBarHeight + 48) + 'px' }"></view>
```

### 新代码
```vue
<NavBar :title="t('page.title')">
  <template #right>
    <view class="nav-action" @click="handleAction">
      <text class="bi bi-icon"></text>
    </view>
  </template>
</NavBar>
```

### 需要删除的代码
1. `statusBarHeight` 相关的状态和获取逻辑
2. `goBack()` 函数（除非需要自定义返回逻辑）
3. 页面内的导航栏样式（`.nav-bar`, `.nav-bar-content`, `.nav-title` 等）
