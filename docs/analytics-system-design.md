# 📊 客户端浏览数据监控分析系统 - 完整方案

> **文档状态**: 待开发
> **创建时间**: 2026-01-01
> **前置条件**: 需等待APP端所有核心页面开发完成后再实施

---

## 一、系统概述

### 1.1 目标

构建一个完整的客户端浏览数据监控分析系统，用于：
- 实时监控用户访问情况
- 分析流量趋势和高峰期
- 追踪产品热度和转化率
- 为运营决策提供数据支持

### 1.2 功能模块总览

| 模块 | 功能描述 | 优先级 |
|------|----------|--------|
| **实时监控面板** | 当前在线用户数、实时PV/UV、活跃页面热力图 | P0 |
| **流量趋势分析** | 每日/每时流量曲线、同比/环比对比 | P0 |
| **高峰期分析** | 识别流量高峰时段、预测流量峰值 | P1 |
| **产品流量分析** | 商品浏览排行、分类热度、转化漏斗 | P0 |
| **用户行为分析** | 访问路径、停留时长、跳出率 | P1 |
| **设备与来源** | 设备类型分布、访问来源渠道 | P2 |

---

## 二、数据采集方案

### 2.1 浏览行为记录表 (page_views)

```sql
CREATE TABLE `page_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL COMMENT '用户ID（未登录为null）',
  `session_id` varchar(64) NOT NULL COMMENT '会话标识',
  `page_url` varchar(500) NOT NULL COMMENT '访问页面URL',
  `page_type` varchar(50) NOT NULL COMMENT '页面类型（home/category/goods/cart/order等）',
  `ref_id` int unsigned DEFAULT NULL COMMENT '关联ID（商品ID/分类ID等）',
  `referrer` varchar(500) DEFAULT NULL COMMENT '来源页面',
  `device_type` varchar(20) NOT NULL COMMENT '设备类型（ios/android/web/miniprogram）',
  `device_model` varchar(100) DEFAULT NULL COMMENT '设备型号',
  `os_version` varchar(50) DEFAULT NULL COMMENT '系统版本',
  `app_version` varchar(20) DEFAULT NULL COMMENT 'APP版本',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '浏览器信息',
  `duration` int unsigned DEFAULT 0 COMMENT '页面停留时长（秒）',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '访问时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_page_type` (`page_type`),
  KEY `idx_ref_id` (`ref_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_device_type` (`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面浏览记录表';
```

### 2.2 用户行为事件表 (user_events)

```sql
CREATE TABLE `user_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL COMMENT '用户ID',
  `session_id` varchar(64) NOT NULL COMMENT '会话标识',
  `event_type` varchar(50) NOT NULL COMMENT '事件类型（click/search/add_cart/order等）',
  `event_target` varchar(100) DEFAULT NULL COMMENT '事件目标',
  `event_data` json DEFAULT NULL COMMENT '事件附加数据',
  `page_url` varchar(500) DEFAULT NULL COMMENT '发生页面',
  `device_type` varchar(20) NOT NULL COMMENT '设备类型',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户行为事件表';
```

### 2.3 小时统计汇总表 (hourly_statistics)

```sql
CREATE TABLE `hourly_statistics` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `stat_date` date NOT NULL COMMENT '统计日期',
  `stat_hour` tinyint unsigned NOT NULL COMMENT '统计小时（0-23）',
  `pv` int unsigned NOT NULL DEFAULT 0 COMMENT '页面浏览量',
  `uv` int unsigned NOT NULL DEFAULT 0 COMMENT '独立访客数',
  `new_visitors` int unsigned NOT NULL DEFAULT 0 COMMENT '新访客数',
  `sessions` int unsigned NOT NULL DEFAULT 0 COMMENT '会话数',
  `avg_duration` int unsigned NOT NULL DEFAULT 0 COMMENT '平均停留时长（秒）',
  `bounce_count` int unsigned NOT NULL DEFAULT 0 COMMENT '跳出次数',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_hour` (`stat_date`, `stat_hour`),
  KEY `idx_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小时统计汇总表';
```

### 2.4 产品流量统计表 (product_traffic_stats)

```sql
CREATE TABLE `product_traffic_stats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `stat_date` date NOT NULL COMMENT '统计日期',
  `goods_id` int unsigned NOT NULL COMMENT '商品ID',
  `category_id` int unsigned DEFAULT NULL COMMENT '分类ID',
  `brand_id` int unsigned DEFAULT NULL COMMENT '品牌ID',
  `views` int unsigned NOT NULL DEFAULT 0 COMMENT '浏览次数',
  `visitors` int unsigned NOT NULL DEFAULT 0 COMMENT '访客数',
  `avg_duration` int unsigned NOT NULL DEFAULT 0 COMMENT '平均停留时长（秒）',
  `cart_adds` int unsigned NOT NULL DEFAULT 0 COMMENT '加购次数',
  `orders` int unsigned NOT NULL DEFAULT 0 COMMENT '下单数',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_goods` (`stat_date`, `goods_id`),
  KEY `idx_stat_date` (`stat_date`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_brand_id` (`brand_id`),
  KEY `idx_views` (`views`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='产品流量统计表';
```

### 2.5 搜索关键词统计表 (search_keyword_stats)

```sql
CREATE TABLE `search_keyword_stats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `stat_date` date NOT NULL COMMENT '统计日期',
  `keyword` varchar(100) NOT NULL COMMENT '搜索关键词',
  `search_count` int unsigned NOT NULL DEFAULT 0 COMMENT '搜索次数',
  `user_count` int unsigned NOT NULL DEFAULT 0 COMMENT '搜索用户数',
  `result_clicks` int unsigned NOT NULL DEFAULT 0 COMMENT '结果点击数',
  `no_result_count` int unsigned NOT NULL DEFAULT 0 COMMENT '无结果次数',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_keyword` (`stat_date`, `keyword`),
  KEY `idx_stat_date` (`stat_date`),
  KEY `idx_search_count` (`search_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索关键词统计表';
```

---

## 三、后端架构设计

### 3.1 目录结构

```
app/
├── admin/controller/
│   └── Analytics.php              # 数据分析主控制器
│
├── api/controller/
│   └── Track.php                  # 埋点数据接收API
│
├── common/
│   ├── model/
│   │   ├── PageView.php           # 浏览记录模型
│   │   ├── UserEvent.php          # 用户事件模型
│   │   ├── HourlyStatistics.php   # 小时统计模型
│   │   ├── ProductTrafficStats.php # 产品流量模型
│   │   └── SearchKeywordStats.php # 搜索统计模型
│   │
│   └── service/
│       └── AnalyticsService.php   # 数据分析服务层
│
└── command/
    └── AggregateAnalytics.php     # 定时聚合统计任务
```

### 3.2 API接口设计

#### 3.2.1 埋点数据接收 (APP端调用)

```
POST /api/track/pageview
请求体:
{
  "page_url": "/pages/goods/detail",
  "page_type": "goods_detail",
  "ref_id": 123,
  "referrer": "/pages/category/index",
  "duration": 45
}

POST /api/track/event
请求体:
{
  "event_type": "add_cart",
  "event_target": "goods_123",
  "event_data": {"quantity": 1, "sku_id": 456}
}
```

#### 3.2.2 后台分析接口

```
# 实时数据
GET /admin/analytics/realtime

# 流量趋势
GET /admin/analytics/traffic-trend?start_date=2026-01-01&end_date=2026-01-07&granularity=day

# 高峰期分析
GET /admin/analytics/peak-analysis?date_range=30

# 产品流量排行
GET /admin/analytics/product-traffic?start_date=2026-01-01&end_date=2026-01-07&category_id=1&limit=50

# 搜索热词
GET /admin/analytics/search-keywords?start_date=2026-01-01&end_date=2026-01-07&limit=100

# 设备分布
GET /admin/analytics/device-distribution?start_date=2026-01-01&end_date=2026-01-07

# 转化漏斗
GET /admin/analytics/conversion-funnel?start_date=2026-01-01&end_date=2026-01-07
```

### 3.3 控制器示例代码

```php
<?php
// app/admin/controller/Analytics.php

namespace app\admin\controller;

use app\common\service\AnalyticsService;
use think\facade\Request;

class Analytics extends Base
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * 实时数据
     */
    public function realtime()
    {
        $data = $this->analyticsService->getRealtimeData();
        return $this->success($data);
    }

    /**
     * 流量趋势
     */
    public function trafficTrend()
    {
        $startDate = Request::get('start_date');
        $endDate = Request::get('end_date');
        $granularity = Request::get('granularity', 'day'); // day/hour

        $data = $this->analyticsService->getTrafficTrend($startDate, $endDate, $granularity);
        return $this->success($data);
    }

    /**
     * 高峰期分析
     */
    public function peakAnalysis()
    {
        $dateRange = Request::get('date_range', 30);
        $data = $this->analyticsService->getPeakAnalysis($dateRange);
        return $this->success($data);
    }

    /**
     * 产品流量
     */
    public function productTraffic()
    {
        $params = Request::get();
        $data = $this->analyticsService->getProductTraffic($params);
        return $this->success($data);
    }

    /**
     * 搜索热词
     */
    public function searchKeywords()
    {
        $params = Request::get();
        $data = $this->analyticsService->getSearchKeywords($params);
        return $this->success($data);
    }

    /**
     * 设备分布
     */
    public function deviceDistribution()
    {
        $params = Request::get();
        $data = $this->analyticsService->getDeviceDistribution($params);
        return $this->success($data);
    }

    /**
     * 转化漏斗
     */
    public function conversionFunnel()
    {
        $params = Request::get();
        $data = $this->analyticsService->getConversionFunnel($params);
        return $this->success($data);
    }
}
```

### 3.4 定时任务设计

```php
<?php
// app/command/AggregateAnalytics.php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class AggregateAnalytics extends Command
{
    protected function configure()
    {
        $this->setName('analytics:aggregate')
            ->setDescription('聚合统计数据');
    }

    protected function execute(Input $input, Output $output)
    {
        // 1. 聚合小时统计
        $this->aggregateHourlyStats();

        // 2. 聚合产品流量
        $this->aggregateProductTraffic();

        // 3. 聚合搜索关键词
        $this->aggregateSearchKeywords();

        // 4. 清理过期原始数据（保留90天）
        $this->cleanupOldData();

        $output->writeln('Analytics aggregation completed.');
    }
}
```

---

## 四、APP端埋点方案

### 4.1 埋点工具类

```typescript
// src/utils/track.ts

import { useUserStore } from '@/stores/user'

interface PageViewData {
  page_url: string
  page_type: string
  ref_id?: number
  referrer?: string
  duration?: number
}

interface EventData {
  event_type: string
  event_target?: string
  event_data?: Record<string, any>
}

class TrackService {
  private sessionId: string
  private pageEnterTime: number = 0
  private currentPage: string = ''

  constructor() {
    this.sessionId = this.generateSessionId()
  }

  private generateSessionId(): string {
    return `${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
  }

  private getDeviceInfo() {
    const systemInfo = uni.getSystemInfoSync()
    return {
      device_type: systemInfo.platform,
      device_model: systemInfo.model,
      os_version: systemInfo.system,
      app_version: systemInfo.appVersion
    }
  }

  /**
   * 页面进入
   */
  pageEnter(pageUrl: string, pageType: string, refId?: number) {
    this.pageEnterTime = Date.now()
    this.currentPage = pageUrl
  }

  /**
   * 页面离开（自动计算停留时长）
   */
  pageLeave(pageUrl: string, pageType: string, refId?: number) {
    const duration = Math.floor((Date.now() - this.pageEnterTime) / 1000)

    this.trackPageView({
      page_url: pageUrl,
      page_type: pageType,
      ref_id: refId,
      referrer: this.currentPage,
      duration
    })
  }

  /**
   * 上报页面浏览
   */
  async trackPageView(data: PageViewData) {
    try {
      const userStore = useUserStore()
      await uni.request({
        url: '/api/track/pageview',
        method: 'POST',
        data: {
          ...data,
          ...this.getDeviceInfo(),
          session_id: this.sessionId,
          user_id: userStore.userId
        }
      })
    } catch (e) {
      console.error('Track pageview failed:', e)
    }
  }

  /**
   * 上报用户事件
   */
  async trackEvent(data: EventData) {
    try {
      const userStore = useUserStore()
      await uni.request({
        url: '/api/track/event',
        method: 'POST',
        data: {
          ...data,
          ...this.getDeviceInfo(),
          session_id: this.sessionId,
          user_id: userStore.userId,
          page_url: this.currentPage
        }
      })
    } catch (e) {
      console.error('Track event failed:', e)
    }
  }
}

export const trackService = new TrackService()

// 便捷方法
export const trackPageView = (data: PageViewData) => trackService.trackPageView(data)
export const trackEvent = (data: EventData) => trackService.trackEvent(data)
export const pageEnter = (url: string, type: string, refId?: number) => trackService.pageEnter(url, type, refId)
export const pageLeave = (url: string, type: string, refId?: number) => trackService.pageLeave(url, type, refId)
```

### 4.2 页面埋点示例

```vue
<!-- src/pages/goods/detail.vue -->
<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { pageEnter, pageLeave, trackEvent } from '@/utils/track'

const props = defineProps<{ id: number }>()

onMounted(() => {
  // 页面进入埋点
  pageEnter('/pages/goods/detail', 'goods_detail', props.id)
})

onUnmounted(() => {
  // 页面离开埋点（自动计算停留时长）
  pageLeave('/pages/goods/detail', 'goods_detail', props.id)
})

// 加入购物车事件埋点
const addToCart = () => {
  trackEvent({
    event_type: 'add_cart',
    event_target: `goods_${props.id}`,
    event_data: { goods_id: props.id, quantity: 1 }
  })
  // ... 实际加购逻辑
}
</script>
```

### 4.3 全局路由埋点（推荐）

```typescript
// src/utils/track-router.ts

import { pageEnter, pageLeave } from './track'

// 页面类型映射
const PAGE_TYPE_MAP: Record<string, string> = {
  '/pages/index/index': 'home',
  '/pages/category/index': 'category',
  '/pages/goods/list': 'goods_list',
  '/pages/goods/detail': 'goods_detail',
  '/pages/cart/index': 'cart',
  '/pages/order/list': 'order_list',
  '/pages/order/detail': 'order_detail',
  '/pages/search/index': 'search',
  '/pages/profile/index': 'profile',
  // ... 其他页面
}

let lastPage = ''

// 在 App.vue 或路由守卫中调用
export function onPageChange(currentPage: string, query?: Record<string, any>) {
  // 离开上一个页面
  if (lastPage) {
    const lastType = PAGE_TYPE_MAP[lastPage] || 'other'
    pageLeave(lastPage, lastType)
  }

  // 进入当前页面
  const currentType = PAGE_TYPE_MAP[currentPage] || 'other'
  const refId = query?.id ? parseInt(query.id) : undefined
  pageEnter(currentPage, currentType, refId)

  lastPage = currentPage
}
```

---

## 五、前端管理后台设计

### 5.1 目录结构

```
src/views/analytics/
├── index.vue                      # 数据分析首页（总览仪表盘）
├── traffic.vue                    # 流量趋势分析页
├── product.vue                    # 产品流量分析页
├── search.vue                     # 搜索分析页
├── components/
│   ├── RealtimePanel.vue          # 实时监控面板组件
│   ├── TrafficTrendChart.vue      # 流量趋势图表组件
│   ├── PeakHeatmap.vue            # 高峰期热力图组件
│   ├── ProductRankingTable.vue    # 产品排行表格组件
│   ├── ConversionFunnel.vue       # 转化漏斗图组件
│   ├── DevicePieChart.vue         # 设备分布饼图组件
│   ├── SearchKeywordCloud.vue     # 搜索词云组件
│   └── DateRangeFilter.vue        # 日期范围筛选组件
└── composables/
    └── useAnalytics.ts            # 数据分析相关hooks
```

### 5.2 路由配置

```typescript
// src/router/index.ts 添加

{
  path: '/analytics',
  name: 'Analytics',
  meta: { title: '数据分析', icon: 'DataAnalysis' },
  children: [
    {
      path: 'overview',
      name: 'AnalyticsOverview',
      component: () => import('@/views/analytics/index.vue'),
      meta: { title: '数据概览' }
    },
    {
      path: 'traffic',
      name: 'TrafficAnalysis',
      component: () => import('@/views/analytics/traffic.vue'),
      meta: { title: '流量分析' }
    },
    {
      path: 'product',
      name: 'ProductAnalysis',
      component: () => import('@/views/analytics/product.vue'),
      meta: { title: '产品分析' }
    },
    {
      path: 'search',
      name: 'SearchAnalysis',
      component: () => import('@/views/analytics/search.vue'),
      meta: { title: '搜索分析' }
    }
  ]
}
```

### 5.3 页面设计原型

#### 5.3.1 数据概览页

```
┌──────────────────────────────────────────────────────────────────┐
│  📊 数据概览                                        [日期选择器]  │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │ 今日PV   │  │ 今日UV   │  │ 平均停留  │  │ 跳出率   │        │
│  │  12,456  │  │  3,892   │  │  3分25秒  │  │  32.5%  │        │
│  │ ↑12.3%   │  │ ↑8.7%    │  │ ↑15秒    │  │ ↓2.1%   │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
│                                                                  │
├────────────────────────────────┬─────────────────────────────────┤
│  流量趋势（最近7天）            │  设备分布                        │
│  ┌────────────────────────┐   │  ┌─────────────────────────┐    │
│  │     ↗                  │   │  │      ┌───┐              │    │
│  │   ╱  ╲    ╱╲          │   │  │  iOS │   │ 45%         │    │
│  │  ╱    ╲  ╱  ╲         │   │  │      └───┘              │    │
│  │ ╱      ╲╱    ╲        │   │  │  Android ████ 38%      │    │
│  │                        │   │  │  Web ██ 12%            │    │
│  └────────────────────────┘   │  │  小程序 █ 5%           │    │
│                               │  └─────────────────────────┘    │
├────────────────────────────────┴─────────────────────────────────┤
│  热门商品 TOP10                                                   │
│  ┌────┬────────────────┬────────┬────────┬────────┬────────┐   │
│  │排名│ 商品名称        │ 浏览量  │ 访客数  │ 加购率  │ 转化率  │   │
│  ├────┼────────────────┼────────┼────────┼────────┼────────┤   │
│  │ 1  │ iPhone 15 Pro  │ 2,345  │  892   │ 15.2%  │  8.5%  │   │
│  │ 2  │ MacBook Pro    │ 1,892  │  756   │ 12.8%  │  6.2%  │   │
│  │ ...│ ...            │ ...    │  ...   │ ...    │  ...   │   │
│  └────┴────────────────┴────────┴────────┴────────┴────────┘   │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

#### 5.3.2 流量分析页

```
┌──────────────────────────────────────────────────────────────────┐
│  📈 流量分析                                                      │
├──────────────────────────────────────────────────────────────────┤
│  筛选: [日期范围▼] [对比: 上周期 | 去年同期▼] [粒度: 天 | 时▼]     │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                    PV/UV 趋势对比图                         │ │
│  │         ↗                                                  │ │
│  │       ╱  ╲     ╱╲        ── 本期PV                        │ │
│  │      ╱    ╲   ╱  ╲       ── 本期UV                        │ │
│  │     ╱      ╲ ╱    ╲      -- 对比期PV                      │ │
│  │    ╱        ╳      ╲     -- 对比期UV                      │ │
│  │   ╱                 ╲                                      │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
├──────────────────────────────────────────────────────────────────┤
│  高峰时段热力图                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │        0  2  4  6  8  10 12 14 16 18 20 22                │ │
│  │   周一 ░░░░░░▓▓▓▓▓▓░░▓▓▓▓▓▓▓▓░░                          │ │
│  │   周二 ░░░░░░▓▓▓▓▓░░░▓▓▓▓▓▓▓▓░░                          │ │
│  │   周三 ░░░░░░▓▓▓▓░░░░▓▓▓▓▓▓▓░░░                          │ │
│  │   周四 ░░░░░░▓▓▓▓▓░░░▓▓▓▓▓▓▓▓░░                          │ │
│  │   周五 ░░░░░░▓▓▓▓▓▓░░▓▓▓▓▓▓▓▓▓░                          │ │
│  │   周六 ░░░░░░░▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░                          │ │
│  │   周日 ░░░░░░░▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░                          │ │
│  └────────────────────────────────────────────────────────────┘ │
│  🔥 高峰时段: 周六 14:00-16:00    📉 低谷时段: 周三 03:00-05:00   │
│                                                                  │
├──────────────────────────────────────────────────────────────────┤
│  详细数据表格                                     [导出Excel]     │
│  ┌────────┬────────┬────────┬────────┬────────┬────────┐       │
│  │  日期   │   PV   │   UV   │ 新访客  │ 环比PV │ 环比UV │       │
│  ├────────┼────────┼────────┼────────┼────────┼────────┤       │
│  │ 01-01  │ 5,234  │ 1,203  │  156   │ +12.3% │ +8.7%  │       │
│  │ 01-02  │ 4,892  │ 1,089  │  134   │ -6.5%  │ -9.5%  │       │
│  └────────┴────────┴────────┴────────┴────────┴────────┘       │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

#### 5.3.3 产品分析页

```
┌──────────────────────────────────────────────────────────────────┐
│  🛍️ 产品分析                                                     │
├──────────────────────────────────────────────────────────────────┤
│  筛选: [日期范围▼] [分类▼] [品牌▼] [排序: 浏览量▼]                 │
├────────────────────────────────┬─────────────────────────────────┤
│  转化漏斗                       │  分类热度分布                    │
│  ┌────────────────────────┐   │  ┌─────────────────────────┐    │
│  │ ████████████████████   │   │  │ 手机数码   ████████ 35% │    │
│  │ 浏览 10,000            │   │  │ 电脑办公   ██████ 25%   │    │
│  │                        │   │  │ 家用电器   ████ 18%     │    │
│  │ ████████████           │   │  │ 服饰鞋包   ███ 12%      │    │
│  │ 加购 1,500 (15%)       │   │  │ 其他      ██ 10%        │    │
│  │                        │   │  └─────────────────────────┘    │
│  │ ████████               │   │                                 │
│  │ 下单 800 (53%)         │   │                                 │
│  │                        │   │                                 │
│  │ ███████                │   │                                 │
│  │ 支付 720 (90%)         │   │                                 │
│  └────────────────────────┘   │                                 │
├────────────────────────────────┴─────────────────────────────────┤
│  商品流量排行                                      [导出Excel]    │
│  ┌────┬────────┬────────────────┬────────┬────────┬────────┬───┤
│  │排名│ 图片    │ 商品名称        │ 浏览量  │ 访客数  │ 加购率  │...│
│  ├────┼────────┼────────────────┼────────┼────────┼────────┼───┤
│  │ 1  │ [img]  │ iPhone 15 Pro  │ 2,345  │  892   │ 15.2%  │...│
│  │ 2  │ [img]  │ MacBook Pro    │ 1,892  │  756   │ 12.8%  │...│
│  │ 3  │ [img]  │ AirPods Pro    │ 1,567  │  623   │ 18.5%  │...│
│  └────┴────────┴────────────────┴────────┴────────┴────────┴───┘
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 六、技术选型

| 方面 | 推荐方案 | 说明 |
|------|----------|------|
| **图表库** | ECharts 5.x | 功能强大，与Vue3配合良好 |
| **实时数据** | WebSocket | 项目已有WebSocket基础设施 |
| **数据缓存** | Redis | 缓存热点统计数据，减少数据库压力 |
| **大数据存储** | 按月分表 | page_views表按月分表，避免单表过大 |
| **定时任务** | ThinkPHP Command | 每小时执行聚合统计 |
| **导出功能** | PhpSpreadsheet | Excel导出 |

---

## 七、实施步骤

### 阶段一：数据采集基础（预计工作量：中）

- [ ] 创建数据库表结构
- [ ] 实现埋点接收API（Track控制器）
- [ ] 开发APP端埋点工具类
- [ ] 在现有页面添加基础埋点

### 阶段二：数据聚合处理（预计工作量：中）

- [ ] 开发定时聚合任务
- [ ] 实现小时/日/产品统计聚合逻辑
- [ ] 配置定时任务执行计划
- [ ] 添加数据清理机制

### 阶段三：后台分析API（预计工作量：中）

- [ ] 实现Analytics控制器
- [ ] 开发AnalyticsService服务层
- [ ] 添加数据缓存优化
- [ ] 实现数据导出功能

### 阶段四：前端可视化（预计工作量：大）

- [ ] 开发数据概览页面
- [ ] 开发流量分析页面
- [ ] 开发产品分析页面
- [ ] 开发搜索分析页面
- [ ] 实现各类图表组件

### 阶段五：优化完善（预计工作量：小）

- [ ] 性能优化（大数据量场景）
- [ ] 添加实时推送功能
- [ ] 完善筛选和导出功能
- [ ] 添加数据异常告警

---

## 八、注意事项

### 8.1 性能考虑

1. **原始数据分表**: page_views表建议按月分表，避免单表数据过大
2. **异步上报**: 埋点数据异步上报，不阻塞用户操作
3. **批量处理**: 聚合统计使用批量SQL，避免逐条处理
4. **缓存策略**: 热点数据（如实时统计）使用Redis缓存

### 8.2 数据准确性

1. **去重处理**: UV统计需要根据session_id或user_id去重
2. **异常过滤**: 过滤爬虫、异常请求等无效数据
3. **时区处理**: 统一使用服务器时区进行统计

### 8.3 隐私合规

1. **IP脱敏**: 存储时考虑IP地址脱敏处理
2. **数据保留**: 原始数据建议保留90天，之后只保留聚合数据
3. **用户授权**: 确保在隐私政策中说明数据收集用途

---

## 九、相关文档

- [ThinkPHP 8.x 文档](https://doc.thinkphp.cn/)
- [ECharts 官方文档](https://echarts.apache.org/zh/index.html)
- [Element Plus 组件库](https://element-plus.org/)
- [UniApp 文档](https://uniapp.dcloud.net.cn/)

---

## 十、更新记录

| 日期 | 版本 | 更新内容 | 作者 |
|------|------|----------|------|
| 2026-01-01 | v1.0 | 初始方案设计 | Claude |
