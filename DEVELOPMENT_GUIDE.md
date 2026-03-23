# TURNSY 项目开发规范

## 目录

1. [项目架构概述](#项目架构概述)
2. [多语言系统规范](#多语言系统规范)
3. [货币汇率转换规范](#货币汇率转换规范)
4. [货币与国家管理系统](#货币与国家管理系统)
5. [后端开发规范](#后端开发规范)
6. [前端开发规范](#前端开发规范)
   - [加载状态规范（LoadingPage 组件）](#加载状态规范loadingpage-组件)
   - [Vue 组件规范](#vue-组件规范)
7. [数据库规范](#数据库规范)
   - [翻译表结构规范](#翻译表结构规范)
   - [自动翻译服务配置](#自动翻译服务配置)
8. [API 规范](#api-规范)
9. [通知系统规范](#通知系统规范)
10. [代码审查清单](#代码审查清单)

---

## 项目架构概述

### 技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后端 | ThinkPHP 8.x | PHP 框架 |
| 前端管理端 | Vue 3 + Element Plus + TypeScript | 管理后台 |
| 前端 APP | UniApp + Vue 3 + TypeScript | 移动端应用 |
| 数据库 | MySQL 8.x | 关系型数据库 |

### 目录结构

```
bbo/
├── bbo-server/          # 后端服务
│   ├── app/
│   │   ├── admin/       # 管理端 API
│   │   ├── api/         # APP 端 API
│   │   └── common/      # 公共模块（模型、Trait等）
│   └── database/        # 数据库迁移和种子
├── bbo-admin/           # 管理后台前端
│   └── src/
│       ├── views/       # 页面组件
│       └── locale/      # 管理端多语言文件
└── bbo-app/             # 移动端前端
    └── src/
        ├── pages/       # 页面组件
        └── locale/      # APP 多语言文件
            ├── zh-TW/   # 繁体中文
            ├── en-US/   # 英文
            └── ja-JP/   # 日语
```

---

## 多语言系统规范

### 动态语言管理

系统支持**动态添加语言**，无需修改代码。管理员可以在后台添加新语言，系统会自动：
1. 将新语言添加到支持列表
2. 调用翻译 API 生成所有翻译表的翻译内容
3. 前端 APP 自动获取可用语言列表

### 支持的语言（动态）

语言列表存储在 `languages` 数据库表中，可通过管理后台动态管理。

| 语言代码 | 说明 | 默认 |
|----------|------|------|
| `zh-tw` | 繁体中文 | ✅ |
| `en-us` | 英文 | |
| `ja-jp` | 日语 | |
| `ko-kr` | 韩语 | |
| ... | 可动态添加更多语言 | |

### 语言代码规范

**重要**：全系统统一使用 `xx-xx` 格式的语言代码（小写字母 + 连字符 + 小写字母）。

```php
// 语言代码格式：xx-xx（小写）
// 示例：zh-tw, en-us, ja-jp, ko-kr

// 语言代码映射（仅用于处理非标准输入）
$localeMap = [
    'en' => 'en-us',      // 简写兼容
    'zh-cn' => 'zh-tw',   // 简体中文降级到繁体中文
    'ja' => 'ja-jp',      // 日语简写兼容
    'ko' => 'ko-kr',      // 韩语简写兼容
];
```

**注意**：
- ⛔ **禁止**在翻译表中使用简写（如 `en`），必须使用完整格式（如 `en-us`）
- ⛔ **禁止**硬编码支持的语言列表，必须从数据库动态获取
- ✅ 所有翻译表、前后端代码统一使用 `xx-xx` 格式

### 多语言降级策略

当请求的语言翻译不存在时，按以下顺序降级：

```
请求语言 → en-us（英文） → 主表原始值
```

> **完整的多语言规范**：请参阅 [docs/I18N_STANDARD.md](docs/I18N_STANDARD.md)

---

### 后端语言中间件架构

#### 1. 动态语言支持（重要架构变更）

**重要**：语言中间件从数据库动态获取支持的语言列表，禁止硬编码语言数组。

**文件位置**：`bbo-server/app/api/middleware/Language.php`

```php
<?php
namespace app\api\middleware;

use think\facade\Cache;
use app\common\model\Language as LanguageModel;

class Language
{
    // ⛔ 禁止硬编码语言列表
    // protected $supported = ['zh-tw', 'en-us', 'ja-jp']; // 错误写法

    // ✅ 从数据库动态获取，带缓存
    protected function getSupportedLanguages(): array
    {
        $cacheKey = 'supported_languages';
        $supported = Cache::get($cacheKey);

        if ($supported === null) {
            try {
                // 从数据库获取所有启用的语言代码
                $supported = LanguageModel::where('is_active', 1)->column('code');

                if (empty($supported)) {
                    $supported = ['en-us'];
                }

                // 缓存 1 小时
                Cache::set($cacheKey, $supported, 3600);
            } catch (\Exception $e) {
                // 数据库错误时使用默认值
                $supported = ['zh-tw', 'en-us', 'ja-jp'];
            }
        }

        return $supported;
    }
}
```

#### 2. 语言代码标准化

中间件将各种语言代码变体标准化为系统统一格式：

```php
protected function normalizeLocale(string $locale): string
{
    $locale = strtolower(trim($locale));

    $map = [
        // 繁体中文
        'zh-hant' => 'zh-tw',
        'zh-tw' => 'zh-tw',
        'zh-hk' => 'zh-tw',
        'zh' => 'zh-tw',
        'zh-hans' => 'zh-tw',  // 简体中文降级到繁体
        'zh-cn' => 'zh-tw',

        // 英文
        'en' => 'en-us',
        'en-us' => 'en-us',
        'en-gb' => 'en-us',

        // 日文
        'ja' => 'ja-jp',
        'ja-jp' => 'ja-jp',

        // 韩文
        'ko' => 'ko-kr',
        'ko-kr' => 'ko-kr',

        // 其他语言按需添加...
    ];

    return $map[$locale] ?? $locale;
}
```

#### 3. 缓存清除（管理员操作时必须）

当在管理后台进行以下操作时，**必须清除语言缓存**：

| 操作 | 需要清除缓存 |
|------|-------------|
| 新增语言 | ✅ 必须 |
| 删除语言 | ✅ 必须 |
| 启用/禁用语言 | ✅ 必须 |
| 修改语言代码 | ✅ 必须 |

**在语言管理控制器中实现**：

```php
// bbo-server/app/admin/controller/Language.php

public function create(): Response
{
    // ... 创建语言 ...

    // 清除语言缓存
    Cache::delete('supported_languages');

    return $this->success($language);
}

public function update($id): Response
{
    // ... 更新语言 ...

    Cache::delete('supported_languages');

    return $this->success($language);
}

public function delete($id): Response
{
    // ... 删除语言 ...

    Cache::delete('supported_languages');

    return $this->success(null, 'Deleted');
}

public function updateStatus($id): Response
{
    // ... 切换启用状态 ...

    Cache::delete('supported_languages');

    return $this->success($language);
}
```

#### 4. 添加新语言的完整流程

添加新语言时的正确流程：

1. **管理后台**：系统管理 → 语言管理 → 新增语言
2. **系统自动**：
   - 语言记录保存到 `languages` 表
   - 清除 `supported_languages` 缓存
   - 触发批量翻译任务（调用翻译 API）
3. **前端自动**：API 中间件下次请求时从数据库读取新的语言列表

**无需修改任何代码**，新语言即可生效！

---

### 后端多语言实现

#### 1. 模型层：使用 Translatable Trait

**适用场景**：需要支持多语言的数据模型（商品、分类、配置等）

```php
<?php
namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

class Example extends Model
{
    use Translatable;

    // 翻译模型类
    protected $translationModel = ExampleTranslation::class;

    // 翻译外键字段
    protected $translationForeignKey = 'example_id';

    // 可翻译字段
    protected $translatable = ['title', 'description', 'value'];
}
```

#### 2. 翻译表结构

每个需要多语言的主表都需要一个对应的翻译表：

```sql
CREATE TABLE `example_translations` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `example_id` int(11) UNSIGNED NOT NULL COMMENT '主表ID',
    `locale` varchar(10) NOT NULL COMMENT '语言代码',
    `title` varchar(255) DEFAULT NULL COMMENT '标题',
    `description` text DEFAULT NULL COMMENT '描述',
    `value` text DEFAULT NULL COMMENT '值',
    `is_original` tinyint(1) DEFAULT 0 COMMENT '是否原始语言',
    `is_auto_translated` tinyint(1) DEFAULT 0 COMMENT '是否自动翻译',
    `translated_at` datetime DEFAULT NULL COMMENT '翻译时间',
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_translation` (`example_id`, `locale`),
    KEY `idx_locale` (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3. 获取翻译值

```php
// 方法1：获取单个字段的翻译值
$translatedTitle = $model->getTranslated('title', $locale);

// 方法2：批量附加翻译到列表
$list = Example::appendTranslations($list, $locale);

// 方法3：获取系统配置的翻译值（支持多语言）
$value = SystemConfig::getConfigTranslated('config_key', $locale, $default);
```

#### 4. 控制器中使用多语言

```php
<?php
namespace app\api\controller;

class Example extends Base
{
    public function read(int $id): Response
    {
        $model = ExampleModel::find($id);

        // 使用 $this->locale 获取当前用户语言
        $data = $model->toApiArray($this->locale);

        // 获取系统配置时传入语言参数
        $configValue = SystemConfig::getConfigTranslated('key', $this->locale, '');

        return $this->success($data);
    }
}
```

#### 5. 管理端保存翻译

```php
// 管理端支持的语言列表（统一使用标准格式）
protected $supportedLocales = ['zh-tw', 'en-us', 'ja-jp'];

// 保存翻译
foreach ($this->supportedLocales as $locale) {
    if (isset($translations[$locale])) {
        $model->saveTranslation($locale, $translations[$locale]);
    }
}
```

#### 6. API 返回的状态文本（禁止硬编码）

**重要**：后端 API 返回的状态文本（如订单状态、支付类型等）**必须从数据库 `ui_translations` 表获取**，禁止在模型常量中硬编码中文。

```
⛔ 禁止：在模型中硬编码状态文本
const STATUS_TEXT = [
    0 => '待付款',    // 硬编码中文，不支持多语言
    1 => '待发货',
];

✅ 正确：从数据库获取翻译
$statusText = $this->getStatusText($order->status);  // 根据用户语言返回翻译
```

**实现方法**：

**1. 在数据库添加状态翻译**（namespace='order'）

```sql
-- bbo-server/database/add_order_status_translations.sql
INSERT INTO `ui_translations` (`key`, `locale`, `namespace`, `value`, `created_at`, `updated_at`) VALUES
('status.0', 'zh-tw', 'order', '待付款', NOW(), NOW()),
('status.0', 'en-us', 'order', 'Pending Payment', NOW(), NOW()),
('status.0', 'ja-jp', 'order', '支払い待ち', NOW(), NOW()),
('status.1', 'zh-tw', 'order', '待發貨', NOW(), NOW()),
('status.1', 'en-us', 'order', 'Pending Shipment', NOW(), NOW()),
('status.1', 'ja-jp', 'order', '発送待ち', NOW(), NOW())
-- ... 更多状态
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW();
```

**2. 在控制器中获取翻译**

```php
<?php
namespace app\api\controller;

use app\common\model\UiTranslation;

class Order extends Base
{
    // 翻译缓存（避免重复查询）
    protected ?array $orderTranslations = null;

    /**
     * 获取订单相关翻译（从数据库）
     */
    protected function getOrderTranslations(): array
    {
        if ($this->orderTranslations === null) {
            $this->orderTranslations = UiTranslation::getTranslationsByNamespace($this->locale, 'order');
        }
        return $this->orderTranslations;
    }

    /**
     * 获取订单状态文本（多语言）
     */
    protected function getStatusText(int $status): string
    {
        $translations = $this->getOrderTranslations();
        // 优先从数据库获取，fallback 到模型常量
        return $translations['status'][$status] ?? (OrderModel::STATUS_TEXT[$status] ?? '');
    }

    /**
     * 格式化订单
     */
    protected function formatOrder($order): array
    {
        return [
            'status' => $order->status,
            'status_text' => $this->getStatusText($order->status),  // 多语言状态文本
            // ...
        ];
    }
}
```

**3. 翻译键命名规范**

| 翻译键格式 | 说明 | 示例 |
|-----------|------|------|
| `status.{数字}` | 订单状态 | `status.0`, `status.1` |
| `paymentType.{数字}` | 支付类型 | `paymentType.1`, `paymentType.2` |
| `preauthStatus.{字符串}` | 预授权状态 | `preauthStatus.authorized` |
| `processStatus.{数字}` | 处理状态 | `processStatus.0` |

**为什么禁止硬编码？**
- 硬编码只能显示一种语言，无法支持国际化
- 新增语言时需要修改代码，增加维护成本
- 数据库翻译可以通过管理端统一维护，无需发布新版本

---

### 前端多语言实现

#### 核心原则：API 获取翻译（禁止本地硬编码）

**重要**：TURNSY 用户端（bbo-app）的所有 UI 翻译**必须通过 API 从数据库获取**，禁止在本地 JSON 文件中硬编码新的翻译内容。

```
⛔ 禁止：在 locale/*.json 文件中添加新的翻译键值
✅ 正确：在数据库 ui_translations 表中添加翻译，前端自动从 API 获取
```

**为什么采用 API 方式？**
- 统一管理：所有翻译集中在数据库，便于维护
- 动态更新：无需发布新版本即可更新翻译
- 支持自动翻译：新增语言时可通过翻译服务自动生成
- 一致性：避免本地文件和数据库翻译不同步

#### 翻译数据来源

| 数据类型 | 存储位置 | 获取方式 |
|---------|---------|---------|
| UI 界面翻译 | `ui_translations` 表 | API `/api/system/ui-translations` |
| 业务数据翻译 | `*_translations` 表 | 各业务 API 返回 |
| 本地 fallback | `locale/*.json` 文件 | 仅作为 API 失败时的降级（禁止新增） |

#### 新增翻译的正确流程

**1. 创建 SQL 文件**（bbo-server/database/ui_translations_xxx.sql）

```sql
-- 删除已存在的相关翻译（避免重复插入）
DELETE FROM `ui_translations` WHERE `namespace` = 'goods' AND `key` LIKE 'newFeature.%';

-- 新增翻译键（四种语言：zh-tw, en-us, ja-jp, ko-kr）
INSERT INTO `ui_translations` (`locale`, `namespace`, `key`, `value`, `is_original`, `is_auto_translated`, `created_at`, `updated_at`) VALUES
('zh-tw', 'goods', 'newFeature.title', '新功能標題', 0, 0, NOW(), NOW()),
('en-us', 'goods', 'newFeature.title', 'New Feature Title', 1, 0, NOW(), NOW()),
('ja-jp', 'goods', 'newFeature.title', '新機能タイトル', 0, 0, NOW(), NOW()),
('ko-kr', 'goods', 'newFeature.title', '새 기능 제목', 0, 0, NOW(), NOW());
```

**2. 执行 SQL 并清除后端缓存**

**3. 更新前端缓存版本**（如需强制更新，递增 `TRANSLATIONS_CACHE_FORMAT_VERSION`）

**4. 在组件中使用**：`t('goods.newFeature.title')`

#### 翻译键命名规范

| 命名空间 | 说明 | 前端使用 |
|---------|------|---------|
| `common` | 通用翻译（展开到顶层） | `t('action.confirm')` |
| `goods` | 商品模块 | `t('goods.price')` |
| `user` | 用户模块 | `t('user.login')` |
| `order` | 订单模块 | `t('order.status.pending')` |
| `cart` | 购物车模块 | `t('cart.empty')` |

**注意**：`common` 命名空间的内容会展开到顶层，其他命名空间保留结构。

#### 前端语言初始化与 localeMap

**文件位置**：`bbo-app/src/locale/index.ts`

前端 `localeMap` 仅用于**首次访问时根据系统语言自动选择界面语言**，不影响已保存语言偏好的用户。

```typescript
// bbo-app/src/locale/index.ts

function getDefaultLocale(): string {
  // 优先使用已保存的语言设置
  const stored = uni.getStorageSync('locale')
  if (stored) {
    return stored  // 直接返回，不经过 localeMap
  }

  // 仅首次访问时使用 localeMap 映射系统语言
  const systemInfo = uni.getSystemInfoSync()
  const lang = systemInfo.language || 'en-US'

  const localeMap: Record<string, string> = {
    // 繁体中文
    'zh-Hant': 'zh-TW',
    'zh-TW': 'zh-TW',
    'zh-HK': 'zh-TW',

    // 英文
    'en': 'en-US',
    'en-US': 'en-US',
    'en-GB': 'en-US',

    // 日文
    'ja': 'ja-JP',
    'ja-JP': 'ja-JP',

    // 韩文
    'ko': 'ko-KR',
    'ko-KR': 'ko-KR',
  }

  return localeMap[lang] || 'en-US'
}
```

**localeMap 的作用**：
- ✅ 仅用于首次访问用户的默认语言选择
- ✅ 将系统语言映射到 APP 支持的语言格式
- ❌ **不影响**用户手动选择的语言（已保存到 storage）

**添加新语言时是否需要更新 localeMap？**
- **可选优化**：如果希望首次访问的韩语系统用户自动看到韩语界面，可以添加映射
- **非必需**：用户可以在 APP 内手动选择语言，选择后会保存到 storage

#### 本地 fallback 文件（仅用于降级）

本地 `locale/*.json` 文件**仅作为 API 失败时的降级方案**，禁止添加新的翻译键：

```
locale/
├── zh-TW/
│   ├── common.json      # Fallback（禁止新增）
│   ├── goods.json       # Fallback（禁止新增）
│   └── ...
├── en-US/
│   └── ...
└── ja-JP/
    └── ...
```

#### 在组件中使用翻译

```typescript
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

// 简单翻译
const text = t('goods.title')

// 带参数的翻译
const priceText = t('goods.installment.monthlyPayment', {
  amount: '$99.99',
  months: 12
})

// 获取当前语言
const currentLocale = locale.value  // 'zh-TW', 'en-US', 'ja-JP'
```

#### ⚠️ APP端插值限制与解决方案

**重要限制**：UniApp APP端（非H5）的 vue-i18n **无法处理运行时动态添加的带插值的翻译消息**。

**问题表现**：
- ✅ **H5端正常**：`t('search.resultsFor', { keyword: '手机' })` → `「手机」的搜尋結果`
- ❌ **APP端异常**：`t('search.resultsFor', { keyword: '手机' })` → `「{keyword}」的搜尋結果`

**根本原因**：
- H5端：浏览器环境，vue-i18n 可以运行时编译插值表达式
- APP端：UniApp 编译后的原生环境，vue-i18n 无法运行时编译动态添加的消息

**解决方案**：智能检测 + 自动适配

```typescript
// ✅ 正确：兼容 H5 和 APP 端的插值处理
const resultsForText = computed(() => {
  // 1. 先尝试使用 vue-i18n 插值
  const interpolated = t('search.resultsFor', { keyword: keyword.value })

  // 2. 检查插值是否成功（APP端会失败，仍包含占位符）
  if (interpolated.includes('{keyword}')) {
    // APP端：插值失败 → 手动替换占位符
    const template = t('search.resultsFor')
    return template.replace('{keyword}', keyword.value)
  }

  // H5端：插值成功 → 直接返回
  return interpolated
})

const resultsCountText = computed(() => {
  const interpolated = t('search.resultsCount', { count: totalResults.value })

  if (interpolated.includes('{count}')) {
    const template = t('search.resultsCount')
    return template.replace('{count}', String(totalResults.value))
  }

  return interpolated
})
```

```vue
<!-- 在模板中使用 -->
<text>{{ resultsForText }}</text>
<text>{{ resultsCountText }}</text>
```

**工作原理**：
1. **优先使用 vue-i18n 插值**：确保 H5端正常工作
2. **自动检测插值结果**：通过检查是否仍包含占位符来判断是否成功
3. **失败时手动替换**：在 APP端使用 JavaScript 字符串替换

**注意事项**：
- ⚠️ 本地 fallback 中的翻译支持插值，但 API 动态加载的翻译在 APP端不支持
- ✅ 推荐将需要插值的常用翻译（如搜索结果提示）保留在本地 fallback 中
- ✅ 使用计算属性处理插值，避免在模板中直接使用 `t()` 带参数

**最佳实践**：
```typescript
// 推荐：使用计算属性处理需要插值的翻译
const messageText = computed(() => {
  const text = t('message.template', { name: userName.value })
  // 自动适配 H5 和 APP
  if (text.includes('{name}')) {
    return t('message.template').replace('{name}', userName.value)
  }
  return text
})
```

```typescript
// 不推荐：直接在模板中使用（APP端会显示占位符）
<text>{{ t('message.template', { name: userName }) }}</text>
```

#### UI 翻译 vs 业务数据翻译

**UI 翻译**：来自 `ui_translations` 表，通过 API 获取，使用 `t()` 函数
```typescript
// 标签、按钮文本、固定说明等
const label = t('goods.delivery.shipping')
const buttonText = t('action.confirm')
```

**业务数据翻译**：来自各业务 API，通过返回字段获取
```typescript
// 商品名称、分类名称等需要从业务表获取的内容
const goodsTitle = goods.value?.title || ''
const categoryName = category.value?.name || ''
```

#### 弹窗多语言规范

**重要**：所有使用 `uni.showModal` 的弹窗必须显式设置 `confirmText` 和 `cancelText`。

```typescript
// ✅ 正确写法
uni.showModal({
  title: t('user.logout'),
  content: t('user.logoutConfirm'),
  confirmText: t('common.confirm'),   // 必须设置
  cancelText: t('common.cancel'),      // 必须设置
  success: (res) => {
    if (res.confirm) {
      // 确认操作
    }
  }
})

// ✅ 只有确认按钮的弹窗
uni.showModal({
  title: t('common.success'),
  content: t('message.text'),
  showCancel: false,
  confirmText: t('common.ok'),   // 必须设置
})

// ❌ 错误写法（使用默认按钮文字）
uni.showModal({
  title: t('user.logout'),
  content: t('user.logoutConfirm'),
  // 缺少 confirmText 和 cancelText
})
```

**翻译键**（在 `ui_translations` 表中，namespace='common'）：
- `action.confirm` = "確認"
- `action.cancel` = "取消"
- `action.ok` = "好的"

#### 6. 页面导航标题多语言规范

**重要**：由于 `pages.json` 是静态配置文件，不支持动态翻译，因此每个页面必须在 `onShow` 生命周期中动态设置导航栏标题。

```typescript
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// 在 onShow 中设置导航标题（必须）
onShow(() => {
  uni.setNavigationBarTitle({ title: t('page.pageName') })
})
```

**翻译键**（在 `ui_translations` 表中，namespace='common'）：
- `page.home` = "首頁"
- `page.category` = "分類"
- `page.details` = "商品詳情"
- 等等...

**特殊情况**：
- 如果页面标题需要根据数据动态变化（如聊天页面显示对方名称），可以在数据加载完成后调用 `uni.setNavigationBarTitle`
- 如果页面标题从 URL 参数传入，可以继续使用 `onLoad` 中的逻辑

```typescript
// 动态标题示例
onLoad((options) => {
  if (options?.title) {
    uni.setNavigationBarTitle({ title: decodeURIComponent(options.title) })
  }
})
```

#### 7. 日期格式化

根据语言格式化日期显示：

```typescript
const formatDeliveryDate = (date: Date) => {
  const locale = uni.getLocale()
  const month = date.getMonth() + 1
  const day = date.getDate()

  if (locale === 'zh-CN' || locale === 'zh-TW') {
    const weekdaysCN = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六']
    return `${month}月${day}日, ${weekdaysCN[date.getDay()]}`
  }

  if (locale === 'ja-JP') {
    const weekdaysJP = ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日']
    return `${month}月${day}日, ${weekdaysJP[date.getDay()]}`
  }

  // 英文默认
  const weekdaysEN = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  const monthsEN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  return `${monthsEN[date.getMonth()]} ${day}, ${weekdaysEN[date.getDay()]}`
}
```

---

## 货币汇率转换规范

### 概述

TURNSY 应用支持根据用户选择的国家/地区自动显示对应的货币和汇率转换后的金额。所有涉及金额显示的页面都必须使用统一的汇率转换方法。

### 支持的国家与货币映射

| 国家/地区 | 代码 | 货币 | 货币代码 |
|-----------|------|------|----------|
| 美国 | US | 美元 | USD |
| 英国 | UK | 英镑 | GBP |
| 澳大利亚 | AU | 澳元 | AUD |
| 加拿大 | CA | 加元 | CAD |
| 新西兰 | NZ | 新西兰元 | NZD |
| 爱尔兰 | IE | 欧元 | EUR |
| 新加坡 | SG | 新加坡元 | SGD |
| 台湾 | TW | 新台币 | TWD |
| 香港 | HK | 港币 | HKD |
| 澳门 | MO | 澳门元 | MOP |
| 日本 | JP | 日元 | JPY |

### 后端汇率配置

#### 1. 数据库存储

汇率配置存储在 `system_configs` 表中，`group` 为 `currency`：

```sql
-- 基准货币
INSERT INTO system_configs (`group`, `key`, `value`, `name`)
VALUES ('currency', 'base_currency', 'USD', '基准货币');

-- 汇率配置（相对于基准货币的比率）
INSERT INTO system_configs (`group`, `key`, `value`, `name`)
VALUES
('currency', 'rate_USD', '1', 'USD Exchange Rate'),
('currency', 'rate_TWD', '32.50', 'TWD Exchange Rate'),
('currency', 'rate_JPY', '157.00', 'JPY Exchange Rate'),
('currency', 'rate_HKD', '7.82', 'HKD Exchange Rate');
-- ... 其他货币
```

#### 2. API 接口

汇率 API：`GET /api/system/exchange-rates`

```php
// app/api/controller/System.php
public function exchangeRates(): Response
{
    $rates = [];
    $configs = Db::table('system_configs')
        ->where('group', 'currency')
        ->where('key', 'like', 'rate_%')
        ->field('`key`, value')
        ->select()->toArray();

    foreach ($configs as $config) {
        $currency = str_replace('rate_', '', $config['key']);
        $rates[$currency] = (float) $config['value'];
    }

    $baseCurrency = SystemConfig::getConfig('base_currency', 'USD');

    return $this->success([
        'baseCurrency' => $baseCurrency,
        'rates' => $rates,
    ]);
}
```

### 前端实现规范

#### 1. 核心工具文件

**货币工具** (`src/utils/currency.ts`)：

```typescript
// 国家到货币的映射
export const REGION_CURRENCY_MAP: Record<string, string> = {
  US: 'USD', UK: 'GBP', AU: 'AUD', CA: 'CAD', NZ: 'NZD',
  IE: 'EUR', SG: 'SGD', TW: 'TWD', HK: 'HKD', MO: 'MOP', JP: 'JPY',
}

// 根据地区获取货币代码
export function getCurrencyByRegion(region: string): string {
  return REGION_CURRENCY_MAP[region] || 'USD'
}

// 货币转换
export function convertAmount(
  amount: number,
  fromCurrency: string,
  toCurrency: string,
  rates: Record<string, number>
): number {
  if (fromCurrency === toCurrency) return amount
  const fromRate = rates[fromCurrency] || 1
  const toRate = rates[toCurrency] || 1
  const amountInBase = amount / fromRate
  return amountInBase * toRate
}

// 格式化货币金额
export function formatCurrencyAmount(
  amount: number,
  currency: string,
  locale: string = 'en-US'
): string {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: currency === 'JPY' ? 0 : 2,
    maximumFractionDigits: currency === 'JPY' ? 0 : 2,
  }).format(amount)
}
```

#### 2. Store 中的方法

**App Store** (`src/store/modules/app.ts`)：

```typescript
// 状态
const region = ref<string>(getStorage(STORAGE_KEYS.REGION) || 'US')
const currency = ref<string>(getCurrencyByRegion(region.value))
const exchangeRates = ref<Record<string, number>>({})
const baseCurrency = ref<string>('USD')

// 设置地区（会同时更新货币）
function setRegion(newRegion: string) {
  region.value = newRegion
  setStorage(STORAGE_KEYS.REGION, newRegion)
  const newCurrency = getCurrencyByRegion(newRegion)
  setCurrency(newCurrency)
}

// 获取汇率
async function fetchExchangeRates() {
  const res = await get<ExchangeRates>(API_PATHS.system.exchangeRates)
  if (res.code === 0 && res.data) {
    exchangeRates.value = res.data.rates
    baseCurrency.value = res.data.baseCurrency
  }
}

// 转换并格式化价格（从商品原始货币转换为用户选择的货币）
// 【重要】所有金额显示都必须使用此方法
function formatPrice(amount: number, originalCurrency: string = 'USD'): string {
  const targetCurrency = currency.value
  if (Object.keys(exchangeRates.value).length > 0) {
    const converted = convertAmount(amount, originalCurrency, targetCurrency, exchangeRates.value)
    return formatCurrencyAmount(converted, targetCurrency, locale.value)
  }
  return formatCurrencyAmount(amount, originalCurrency, locale.value)
}
```

#### 3. 应用初始化

**App.vue** 中获取汇率数据：

```typescript
import { useAppStore } from '@/store/modules/app'

onLaunch(() => {
  const appStore = useAppStore()
  appStore.fetchExchangeRates()
})
```

#### 4. 页面中使用（必须遵循）

**所有涉及金额显示的页面都必须按以下规范实现：**

```typescript
<script setup lang="ts">
import { useAppStore } from '@/store/modules/app'

const appStore = useAppStore()

// 格式化价格（使用汇率转换）
function formatPrice(amount: number, currency: string = 'USD'): string {
  return appStore.formatPrice(amount, currency)
}
</script>

<template>
  <!-- ✅ 正确：使用 formatPrice，传入金额和原始货币 -->
  <text>{{ formatPrice(goods.price, goods.currency) }}</text>
  <text>{{ formatPrice(order.total_amount, order.currency) }}</text>

  <!-- ❌ 错误：直接显示金额 -->
  <text>${{ goods.price }}</text>
  <text>{{ goods.price }} {{ goods.currency }}</text>
</template>
```

#### 5. 重要规则：所有金额必须转换

**所有涉及金额的显示都必须使用 `formatPrice` 进行汇率转换**，包括但不限于：

- 商品价格（现价、原价）
- 运费
- 订单金额（总价、分期金额）
- 优惠金额
- 服务费、手续费
- 任何其他金额字段

```typescript
// ✅ 正确：所有金额都使用 formatPrice 转换
<text class="current-price">{{ formatPrice(goods.price, goods.currency) }}</text>
<text class="original-price">{{ formatPrice(goods.originalPrice, goods.currency) }}</text>
<text class="shipping-fee">{{ formatPrice(goods.shippingFee, goods.currency) }}</text>
<text class="total-amount">{{ formatPrice(order.totalAmount, order.currency) }}</text>

// ❌ 错误：不转换直接显示
<text class="price">${{ goods.price }}</text>
<text class="price">{{ goods.price }} USD</text>
```

### 已支持汇率转换的页面

以下页面已实现汇率转换，可作为参考：

| 页面 | 文件路径 | 说明 |
|------|----------|------|
| 首页 | `pages/index/index.vue` | 商品价格、原价 |
| 商品列表 | `pages/goods/list.vue` | 商品价格 |
| 商品详情 | `pages/goods/detail.vue` | 价格、原价、运费、分期金额 |
| 搜索结果 | `pages/search/index.vue` | 商品价格 |
| 聊天页 | `pages/chat/index.vue` | 商品卡片价格 |
| 分期订单列表 | `pages/credit/orders.vue` | 总金额、期付金额 |
| 分期订单详情 | `pages/credit/order-detail.vue` | 各项金额、还款计划 |

### 新增页面开发清单

开发涉及金额显示的新页面时，必须完成以下步骤：

1. [ ] 引入 `useAppStore`
2. [ ] 定义 `formatPrice` 函数调用 `appStore.formatPrice`
3. [ ] 所有金额显示使用 `formatPrice(amount, currency)` 格式
4. [ ] 确保后端 API 返回金额的同时返回 `currency` 字段
5. [ ] 测试切换国家后金额显示是否正确更新

### 常见问题

#### Q1: 切换国家后价格没有更新

**原因**：`handleRegionSelect` 函数没有调用 `appStore.setRegion()`

**解决**：
```typescript
function handleRegionSelect(region: { code: string; locale: string }) {
  selectedRegion.value = region.code
  appStore.setRegion(region.code)  // 必须调用，会自动更新货币
  appStore.setLocale(region.locale)
}
```

#### Q2: 汇率数据为空

**原因**：
1. 数据库没有汇率配置
2. API 路由未添加
3. App 启动时未调用 `fetchExchangeRates`

**解决**：
1. 检查 `system_configs` 表是否有 `group='currency'` 的记录
2. 确认 `route.php` 中有 `system/exchange-rates` 路由
3. 确认 `App.vue` 的 `onLaunch` 中调用了 `appStore.fetchExchangeRates()`

#### Q3: 日元显示小数

**原因**：日元不应该显示小数位

**解决**：`formatCurrencyAmount` 函数已处理，日元自动使用 0 位小数

---

## 货币与国家管理系统

### 概述

TURNSY 系统采用**数据库驱动**的国家/货币管理方案，所有国家、货币、汇率数据均存储在数据库中，通过管理后台动态管理，无需修改代码。

**核心优势**：
- 运营人员可自行添加新国家/货币
- 国家名称支持多语言翻译
- 新增货币自动创建汇率配置
- 前端动态获取数据，无需硬编码

### 数据库设计

#### 1. 货币表 `currencies`

```sql
CREATE TABLE `currencies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(3) NOT NULL COMMENT '货币代码 USD/HKD/TWD',
  `name` varchar(50) NOT NULL COMMENT '货币名称',
  `symbol` varchar(10) NOT NULL COMMENT '货币符号 $/HK$/NT$',
  `decimals` tinyint NOT NULL DEFAULT 2 COMMENT '小数位数',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `is_active` tinyint NOT NULL DEFAULT 1 COMMENT '是否启用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='货币配置表';
```

#### 2. 国家/地区表 `countries`

```sql
CREATE TABLE `countries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(2) NOT NULL COMMENT '国家代码 US/TW/JP',
  `name` varchar(50) NOT NULL COMMENT '国家名称（默认语言）',
  `currency_code` varchar(3) NOT NULL COMMENT '默认货币代码',
  `flag` varchar(10) DEFAULT NULL COMMENT '国旗代码（用于 flagcdn）',
  `locale` varchar(10) DEFAULT NULL COMMENT '默认语言 en-us/zh-tw',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `is_active` tinyint NOT NULL DEFAULT 1 COMMENT '是否启用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `currency_code` (`currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='国家/地区配置表';
```

#### 3. 国家名称翻译表 `country_translations`

```sql
CREATE TABLE `country_translations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int unsigned NOT NULL COMMENT '国家ID',
  `locale` varchar(10) NOT NULL COMMENT '语言代码',
  `name` varchar(50) NOT NULL COMMENT '国家名称',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_locale` (`country_id`, `locale`),
  KEY `locale` (`locale`),
  CONSTRAINT `fk_country_trans_country` FOREIGN KEY (`country_id`)
    REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='国家名称翻译表';
```

### 后端 API

#### 1. 获取国家列表（APP 端）

**端点**：`GET /api/system/countries`

**响应格式**：

```json
{
  "code": 0,
  "msg": "success",
  "data": [
    {
      "code": "US",
      "name": "美國",
      "flag": "us",
      "locale": "en-us",
      "currencyCode": "USD",
      "currencySymbol": "$",
      "currencyDecimals": 2
    },
    {
      "code": "TW",
      "name": "台灣",
      "flag": "tw",
      "locale": "zh-tw",
      "currencyCode": "TWD",
      "currencySymbol": "NT$",
      "currencyDecimals": 0
    }
  ]
}
```

**注意**：国家名称 `name` 会根据请求的 `Accept-Language` 头返回对应语言的翻译。

#### 2. 获取货币列表（APP 端）

**端点**：`GET /api/system/currencies`

**响应格式**：

```json
{
  "code": 0,
  "msg": "success",
  "data": [
    {
      "code": "USD",
      "symbol": "$",
      "decimals": 2
    },
    {
      "code": "TWD",
      "symbol": "NT$",
      "decimals": 0
    }
  ]
}
```

### 后端实现

#### 1. 货币模型

**文件**：`bbo-server/app/common/model/Currency.php`

```php
class Currency extends Model
{
    /**
     * 获取所有启用货币的代码列表
     */
    public static function getActiveCodes(): array
    {
        return self::where('is_active', 1)
            ->order('sort', 'asc')
            ->column('code');
    }

    /**
     * 获取货币信息（用于国家关联）
     */
    public static function getInfoByCode(string $code): ?array
    {
        $currency = self::where('code', $code)->find();
        if (!$currency) return null;
        return [
            'code' => $currency->code,
            'symbol' => $currency->symbol,
            'decimals' => $currency->decimals,
        ];
    }
}
```

#### 2. 国家模型

**文件**：`bbo-server/app/common/model/Country.php`

```php
class Country extends Model
{
    use Translatable;

    protected $translationModel = CountryTranslation::class;
    protected $translationForeignKey = 'country_id';
    protected $translatable = ['name'];

    /**
     * 获取 APP 端国家列表（包含货币信息和多语言名称）
     */
    public static function getListForApp(string $locale): array
    {
        $countries = self::where('is_active', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        // 附加翻译
        $countries = self::appendTranslations($countries, $locale);

        $result = [];
        foreach ($countries as $country) {
            $currencyInfo = Currency::getInfoByCode($country['currency_code']);
            if (!$currencyInfo) continue;

            $result[] = [
                'code' => $country['code'],
                'name' => $country['translated_name'],
                'flag' => $country['flag'],
                'locale' => $country['locale'],
                'currencyCode' => $country['currency_code'],
                'currencySymbol' => $currencyInfo['symbol'],
                'currencyDecimals' => (int)$currencyInfo['decimals'],
            ];
        }

        return $result;
    }
}
```

#### 3. 新增货币自动创建汇率配置

**重要**：当通过管理后台新增货币时，系统自动在 `system_configs` 表中创建对应的汇率配置。

**文件**：`bbo-server/app/admin/controller/Currency.php`

```php
public function create(): Response
{
    // ... 创建货币记录 ...

    // 自动创建汇率配置
    $rateKey = 'rate_' . $code;
    $existingRate = Db::table('system_configs')->where('key', $rateKey)->find();
    if (!$existingRate) {
        Db::table('system_configs')->insert([
            'group' => 'currency',
            'key' => $rateKey,
            'value' => '1',  // 默认汇率为 1
            'type' => 'number',
            'name' => $code . ' Exchange Rate',
            'description' => 'Exchange rate for ' . $code . ' (relative to USD)',
            'sort' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    return $this->success($currency);
}
```

### 前端实现

#### 1. Store 类型定义

**文件**：`bbo-app/src/store/modules/app.ts`

```typescript
export interface CountryOption {
  code: string           // 国家代码 US/TW/JP
  name: string           // 国家名称（当前语言）
  flag: string           // 国旗代码（用于 flagcdn）
  currencyCode: string   // 货币代码 USD/TWD
  currencySymbol: string // 货币符号 $/NT$
  currencyDecimals: number // 小数位数
  locale: string         // 默认语言
}

export interface CurrencyOption {
  code: string     // 货币代码
  symbol: string   // 货币符号
  decimals: number // 小数位数
}
```

#### 2. 获取数据方法

```typescript
// 获取可用国家列表
async function fetchAvailableCountries() {
  const res = await get<CountryOption[]>(API_PATHS.system.countries)
  if (res.code === 0 && res.data) {
    availableCountries.value = res.data
    // 同步更新动态货币数据到 currency.ts
    setDynamicCurrencyData(
      res.data.map(c => ({
        code: c.currencyCode,
        symbol: c.currencySymbol,
        decimals: c.currencyDecimals,
      })),
      res.data.map(c => ({
        code: c.code,
        currencyCode: c.currencyCode,
      }))
    )
  }
}

// 获取可用货币列表
async function fetchAvailableCurrencies() {
  const res = await get<CurrencyOption[]>(API_PATHS.system.currencies)
  if (res.code === 0 && res.data) {
    availableCurrencies.value = res.data
  }
}
```

#### 3. 语言切换时刷新国家名称

```typescript
// 切换语言时重新获取国家列表（以获取新语言的国家名称）
async function setLocale(newLocale: string) {
  locale.value = newLocale
  await setI18nLocale(newLocale)
  // 重新获取国家列表以更新国家名称翻译
  fetchAvailableCountries()
}
```

#### 4. 国家选择自动切换语言和货币

**文件**：`bbo-app/src/pages/profile/index.vue`

```typescript
function handleRegionSelect(region: { code: string; locale: string }) {
  selectedRegion.value = region.code
  appStore.setRegion(region.code)  // 自动更新货币

  // 自动切换到国家的默认语言
  if (region.locale) {
    // 转换格式：en-us → en-US（前端格式）
    const frontendLocale = region.locale.replace(
      /-(\w+)$/,
      (_, p1) => `-${p1.toUpperCase()}`
    )
    appStore.setLocale(frontendLocale)
  }
}
```

### 价格显示规范

#### 1. 使用货币符号（非代码）

**重要**：价格显示使用货币符号（如 `$`、`NT$`、`¥`），不使用货币代码（如 `USD`、`TWD`、`JPY`）。

```typescript
// ✅ 正确：显示 $100、NT$3,250、¥15,700
formatPrice(100, 'USD')   // → $100
formatPrice(3250, 'TWD')  // → NT$3,250
formatPrice(15700, 'JPY') // → ¥15,700

// ❌ 错误：显示 USD 100、TWD 3,250
```

#### 2. 所有金额取整（无小数）

**重要**：为简化显示，所有转换后的金额统一取整，不显示小数位。

**文件**：`bbo-app/src/utils/currency.ts`

```typescript
export function formatCurrencyAmount(
  amount: number,
  currency: string,
  locale: string = 'en-US'
): string {
  // 所有货币统一取整，不显示小数
  const roundedAmount = Math.round(amount)

  // 获取货币符号
  const symbol = getCurrencySymbol(currency)

  // 格式化数字（带千分位）
  const formattedNumber = new Intl.NumberFormat(locale, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(roundedAmount)

  // 返回符号 + 金额格式
  return `${symbol}${formattedNumber}`
}
```

#### 3. 动态数据与 Fallback

```typescript
// 优先使用 API 获取的动态数据，fallback 到硬编码值
export function getCurrencySymbol(currency: string): string {
  return dynamicCurrencySymbols[currency]       // 优先：API 数据
    || CURRENCY_SYMBOLS_FALLBACK[currency]      // Fallback：硬编码
    || currency                                  // 最终：显示货币代码
}

export function getCurrencyDecimals(currency: string): number {
  return dynamicCurrencyDecimals[currency]
    ?? CURRENCY_DECIMALS_FALLBACK[currency]
    ?? 2
}
```

### 管理后台操作

#### 1. 新增国家流程

1. **系统管理 → 国家管理 → 新增**
2. 填写：国家代码、默认名称、关联货币、国旗代码、默认语言
3. 保存后，添加各语言的国家名称翻译

#### 2. 新增货币流程

1. **系统管理 → 货币管理 → 新增**
2. 填写：货币代码、名称、符号、小数位数
3. 保存后，系统自动创建 `rate_XXX` 汇率配置（默认值为 1）
4. **在系统配置中设置正确的汇率**

#### 3. 关联配置

- 新国家必须关联已存在的货币
- 新货币需要在汇率配置中设置正确的汇率值
- 如需更新汇率，在"系统配置 → 货币设置"中修改 `rate_XXX` 的值

### 检查清单

#### 新增国家

- [ ] 在 `countries` 表中添加记录
- [ ] 关联正确的 `currency_code`
- [ ] 添加 `country_translations` 多语言名称
- [ ] 设置正确的 `locale`（默认语言）和 `flag`（国旗代码）

#### 新增货币

- [ ] 在 `currencies` 表中添加记录
- [ ] 系统自动创建 `rate_XXX` 汇率配置
- [ ] 在系统配置中设置正确的汇率值
- [ ] 检查汇率 API 是否支持该货币（如需自动更新）

#### 前端适配

- [ ] 确保 `fetchAvailableCountries` 在应用启动时调用
- [ ] 确保 `fetchAvailableCurrencies` 在应用启动时调用
- [ ] 语言切换时重新获取国家列表
- [ ] 国家选择时自动切换语言和货币

---

## 后端开发规范

### 控制器规范

#### 1. 基础结构

```php
<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\Example as ExampleModel;

class Example extends Base
{
    /**
     * 列表接口
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = ExampleModel::order('id', 'desc');

        // 筛选条件
        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where('title', 'like', "%{$keyword}%");
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 附加翻译
        $list = ExampleModel::appendTranslations($list, $this->locale);

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 详情接口
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $model = ExampleModel::find($id);
        if (!$model) {
            return $this->error('Not found', 404);
        }

        $data = $model->toApiArray($this->locale);

        return $this->success($data);
    }
}
```

#### 2. 管理端控制器翻译保存

```php
/**
 * 创建记录
 */
public function create(): Response
{
    $data = input('post.');
    $translations = $data['translations'] ?? [];

    // 验证至少有一种语言的必填字段
    $hasRequired = false;
    foreach ($this->supportedLocales as $locale) {
        if (!empty($translations[$locale]['title'])) {
            $hasRequired = true;
            break;
        }
    }
    if (!$hasRequired) {
        return $this->error('请至少填写一种语言的标题');
    }

    // 创建主记录
    $model = new ExampleModel();
    $model->title = $translations['zh-tw']['title'] ?? ($translations['en-us']['title'] ?? '');
    $model->save();

    // 保存翻译
    foreach ($this->supportedLocales as $locale) {
        if (isset($translations[$locale])) {
            $model->saveTranslation($locale, $translations[$locale]);
        }
    }

    return $this->success(['id' => $model->id], '创建成功');
}
```

### API 错误消息多语言规范

#### 核心原则

**重要**：
1. 所有 API 控制器返回的错误消息必须使用英文翻译键，禁止硬编码中文或其他语言文本
2. **禁止在 `app/lang/` 目录下硬编码翻译**，必须使用数据库 `ui_translations` 表存储翻译
3. 这样当添加新语言时，可以通过自动翻译 API 生成翻译，无需手动维护语言文件

#### 工作原理

`Base.php` 中的 `error()` 和 `success()` 方法会自动从数据库获取翻译：

```php
// Base.php
protected function error(string $msg = 'error', int $code = 1, $data = null): Response
{
    return json([
        'code' => $code,
        'msg' => $this->translateMessage($msg),  // 从数据库获取翻译
        'data' => $data
    ]);
}

// translateMessage 方法会：
// 1. 将消息键转换为数据库格式（如 "User not found" -> "user_not_found"）
// 2. 从 ui_translations 表的 'message' 命名空间获取翻译
// 3. 如果数据库中没有，fallback 到 lang() 函数
```

#### 正确用法

```php
// ✅ 正确：使用英文翻译键
return $this->error('Conversation not found', 404);
return $this->error('User not found');
return $this->error('Message content required');
return $this->success([], 'Report submitted');

// ❌ 错误：硬编码中文
return $this->error('会话不存在', 404);
return $this->error('用户不存在');
return $this->error('消息内容不能为空');
return $this->success([], '举报已提交');

// ❌ 错误：多次调用 lang() 或 translateMessage()
return $this->error(lang('Conversation not found'), 404);
```

#### 翻译存储位置

**数据库表**：`ui_translations`
**命名空间**：`message`

```sql
-- 示例数据
INSERT INTO ui_translations (locale, namespace, `key`, value, is_original, created_at) VALUES
('en-us', 'message', 'user_not_found', 'User not found', 1, NOW()),
('zh-tw', 'message', 'user_not_found', '使用者不存在', 0, NOW()),
('ja-jp', 'message', 'user_not_found', 'ユーザーが見つかりません', 0, NOW());
```

#### 添加新翻译键

1. **定义英文键**（在控制器中使用）：

   ```php
   return $this->error('New error message');
   ```

2. **在数据库中添加翻译**（推荐写 SQL 迁移文件）：

   ```sql
   -- database/migrations/20260123_add_new_error_message.sql

   INSERT INTO ui_translations (locale, namespace, `key`, value, is_original, created_at) VALUES
   -- 英文（原始）
   ('en-us', 'message', 'new_error_message', 'New error message', 1, NOW()),
   -- 繁体中文
   ('zh-tw', 'message', 'new_error_message', '新的錯誤訊息', 0, NOW()),
   -- 日语
   ('ja-jp', 'message', 'new_error_message', '新しいエラーメッセージ', 0, NOW());
   ```

3. **执行 SQL**：

   ```bash
   mysql -ubbo -p123456 bbo < database/migrations/20260123_add_new_error_message.sql
   ```

4. **清理缓存**：

   ```bash
   rm -rf runtime/cache/*
   ```

#### 键名转换规则

控制器中的消息键会自动转换为数据库键格式：

| 控制器中使用 | 数据库键 |
|-------------|---------|
| `User not found` | `user_not_found` |
| `Conversation not found` | `conversation_not_found` |
| `Message content required` | `message_content_required` |
| `no permission` | `no_permission` |

转换规则：空格替换为下划线，转为小写。

#### 常用翻译键参考

| 数据库键 | 英文 | 繁体中文 | 使用场景 |
|---------|------|----------|----------|
| `not_found` | Not found | 未找到 | 通用未找到 |
| `user_not_found` | User not found | 使用者不存在 | 用户不存在 |
| `conversation_not_found` | Conversation not found | 會話不存在 | 会话不存在 |
| `message_not_found` | Message not found | 訊息不存在 | 消息不存在 |
| `order_not_found` | Order not found | 訂單不存在 | 订单不存在 |
| `goods_not_found` | Product not found | 商品不存在 | 商品不存在 |
| `ticket_not_found` | Ticket not found | 工單不存在 | 工单不存在 |
| `no_permission` | No permission | 沒有權限 | 无权限 |
| `please_login` | Please login first | 請先登入 | 未登录 |
| `param_error` | Parameter error | 參數錯誤 | 参数错误 |
| `operation_failed` | Operation failed | 操作失敗 | 操作失败 |

#### 新增 API 时的检查清单

- [ ] 所有错误消息使用英文翻译键
- [ ] 翻译已添加到 `ui_translations` 表（命名空间为 `message`）
- [ ] 翻译包含所有支持的语言（en-us, zh-tw, ja-jp）
- [ ] 未在 `$this->error()` 中多次调用翻译函数
- [ ] 未硬编码中文或其他语言文本
- [ ] **未在 `app/lang/` 目录添加硬编码翻译**

#### 新语言支持

当在管理后台添加新语言时，`message` 命名空间的所有翻译会自动通过翻译 API 生成，无需手动添加。

#### 遗留代码兼容

`app/lang/` 目录下的文件作为 fallback 保留，但**禁止新增内容**。所有新翻译必须添加到数据库。

---

### 模型规范

#### 1. toApiArray 方法

每个模型都应实现 `toApiArray` 方法，确保传递 `$locale` 参数：

```php
/**
 * 转换为 API 数组
 * @param string|null $locale
 * @return array
 */
public function toApiArray(?string $locale = null): array
{
    $data = [
        'id' => $this->id,
        // ... 其他字段
    ];

    // 获取翻译字段
    $data['title'] = $this->getTranslated('title', $locale);
    $data['description'] = $this->getTranslated('description', $locale);

    // 获取系统配置（需要多语言时）
    $data['configValue'] = SystemConfig::getConfigTranslated('key', $locale, '');

    return $data;
}
```

---

### URL 与图片路径处理规范

#### UrlHelper 工具类

位置：`app/common/helper/UrlHelper.php`

用于处理图片等资源 URL 的动态域名转换和相对路径转完整 URL。

#### 核心方法

| 方法 | 说明 | 使用场景 |
|------|------|----------|
| `getFullUrl($path)` | 相对路径转完整 URL | API 返回图片路径 |
| `convertImageUrl($url)` | 转换域名（本地存储 URL） | 多域名环境 |
| `convertFieldUrls($data, $fields)` | 批量转换数组中的字段 | 处理多个图片字段 |
| `convertListFieldUrls($list, $fields)` | 批量转换列表数据 | 处理列表中的图片 |

#### 使用示例

```php
use app\common\helper\UrlHelper;

// 1. 相对路径转完整 URL（最常用）
$goods = [
    'cover_image' => UrlHelper::getFullUrl($goods['cover_image'] ?? ''),
    // 输入: "/storage/goods/1.jpg"
    // 输出: "http://example.com/storage/goods/1.jpg"
];

// 2. 已经是完整 URL 的情况（自动处理域名转换）
$url = UrlHelper::getFullUrl("http://old-domain.com/storage/img.jpg");
// 输出: "http://current-domain.com/storage/img.jpg"（如果是本地存储路径）

// 3. 第三方 URL 保持不变
$url = UrlHelper::getFullUrl("https://cdn.example.com/image.jpg");
// 输出: "https://cdn.example.com/image.jpg"（不做转换）

// 4. 批量转换数组中的多个图片字段
$data = UrlHelper::convertFieldUrls($data, ['cover_image', 'avatar', 'logo']);

// 5. 批量转换列表数据
$list = UrlHelper::convertListFieldUrls($list, ['cover_image', 'avatar']);
```

#### API 返回图片路径规范

**重要**：API 返回的图片路径**必须使用 `UrlHelper::getFullUrl()` 转换**，确保前端获得完整可用的 URL。

```php
// ✅ 正确：使用 UrlHelper 转换
protected function formatOrder($order): array
{
    $goods = $order->goods_snapshot ?? [];
    return [
        'goods' => [
            'cover_image' => UrlHelper::getFullUrl($goods['cover_image'] ?? ''),
        ],
    ];
}

// ❌ 错误：直接返回相对路径
protected function formatOrder($order): array
{
    $goods = $order->goods_snapshot ?? [];
    return [
        'goods' => [
            'cover_image' => $goods['cover_image'] ?? '',  // 前端无法显示
        ],
    ];
}
```

#### 注意事项

1. **数据库快照字段**：订单中的 `goods_snapshot` 等快照字段可能存储相对路径，需要转换
2. **空值处理**：`getFullUrl()` 会安全处理 `null` 和空字符串
3. **第三方 URL**：非本地存储的 URL（如 CDN、外部图片）不会被转换
4. **本地存储识别**：路径中包含 `/storage/` 的被视为本地存储

---

### 商品价格显示规范

#### 核心原则

商品在任何位置显示时，必须检查是否参与活动，**优先显示活动价格**。

#### 价格显示优先级

```
活动价格（promotionPrice） > 原始价格（price）
```

#### 后端实现

**1. 批量查询活动信息（推荐用于列表页）**

```php
/**
 * 批量获取商品的活动信息
 * @param array $goodsIds
 * @return array 以 goods_id 为 key 的活动信息数组
 */
protected function getBatchGoodsPromotionInfo(array $goodsIds): array
{
    if (empty($goodsIds)) {
        return [];
    }

    $now = date('Y-m-d H:i:s');

    // 查找这些商品参与的进行中的活动
    $promotionGoodsList = PromotionGoods::alias('pg')
        ->join('promotions p', 'pg.promotion_id = p.id')
        ->whereIn('pg.goods_id', $goodsIds)
        ->where('p.status', Promotion::STATUS_RUNNING)
        ->where('p.start_time', '<=', $now)
        ->where('p.end_time', '>=', $now)
        ->field('pg.*, p.type as promotion_type, p.start_time, p.end_time, p.id as promotion_id')
        ->select();

    if ($promotionGoodsList->isEmpty()) {
        return [];
    }

    // 获取所有相关活动的翻译名称
    $promotionIds = array_unique($promotionGoodsList->column('promotion_id'));
    $promotions = Promotion::whereIn('id', $promotionIds)->select()->column(null, 'id');

    $result = [];
    foreach ($promotionGoodsList as $pg) {
        $promotion = $promotions[$pg['promotion_id']] ?? null;
        $promotionName = $promotion ? $promotion->getTranslated('name', $this->locale) : '';

        $result[$pg['goods_id']] = [
            'id' => (int) $pg['promotion_id'],
            'name' => $promotionName,
            'type' => (int) $pg['promotion_type'],
            'promotionPrice' => (float) $pg['promotion_price'],
            'discount' => (float) $pg['discount'],
            'discountPercent' => round((1 - (float) $pg['discount']) * 100),
            'endTime' => $pg['end_time'],
        ];
    }

    return $result;
}
```

**2. 在列表 API 中使用**

```php
public function list(): Response
{
    // ... 获取商品列表
    $goodsIds = array_column($list, 'id');

    // 批量查询活动信息
    $promotionMap = $this->getBatchGoodsPromotionInfo($goodsIds);

    // 附加到商品数据
    foreach ($list as &$item) {
        if (isset($promotionMap[$item['id']])) {
            $item['promotion'] = $promotionMap[$item['id']];
        }
    }

    return $this->success($list);
}
```

#### 前端实现

**1. 获取显示价格的辅助函数**

```typescript
interface Goods {
  id: number
  price: number
  currency: string
  promotion?: {
    id: number
    name: string
    promotionPrice: number
    discount: number
    discountPercent: number
    endTime: string
  }
}

// 获取应显示的价格（优先活动价格）
const getDisplayPrice = (item: Goods): number => {
  if (item.promotion && item.promotion.promotionPrice) {
    return item.promotion.promotionPrice
  }
  return item.price
}

// 判断是否有活动
const hasPromotion = (item: Goods): boolean => {
  return !!(item.promotion && item.promotion.promotionPrice)
}
```

**2. 价格显示模板**

```vue
<template>
  <view class="price-container">
    <!-- 当前价格（活动价或原价） -->
    <text class="current-price">
      {{ formatPrice(getDisplayPrice(item), item.currency) }}
    </text>

    <!-- 原价（仅活动时显示，带删除线） -->
    <text v-if="item.promotion" class="original-price">
      {{ formatPrice(item.price, item.currency) }}
    </text>

    <!-- 折扣标签 -->
    <view v-if="item.promotion" class="discount-badge">
      <text>-{{ item.promotion.discountPercent }}%</text>
    </view>
  </view>
</template>

<style lang="scss">
.current-price {
  font-size: 16px;
  font-weight: 700;
  color: $color-primary;  // 使用主题色
}

.original-price {
  font-size: 12px;
  color: $color-muted;
  text-decoration: line-through;
  margin-left: 4px;
}

.discount-badge {
  display: inline-block;
  padding: 1px 4px;
  background-color: $color-accent;
  border-radius: 4px;

  text {
    font-size: 10px;
    color: #fff;
    font-weight: 600;
  }
}
</style>
```

#### 需要显示活动价格的页面

以下页面在显示商品卡片时，**必须**检查并显示活动价格：

| 页面 | 说明 |
|------|------|
| 首页商品列表 | 推荐商品、热销商品等 |
| 分类商品列表 | 分类下的商品 |
| 搜索结果 | 搜索到的商品 |
| 收藏列表 | 用户收藏的商品 |
| 浏览历史 | 最近浏览的商品 |
| 购物车 | 购物车中的商品 |
| 商品详情页 | 商品主价格区域 |

#### 检查清单

- [ ] 后端 API 是否返回了 `promotion` 字段？
- [ ] 前端是否使用 `getDisplayPrice()` 获取显示价格？
- [ ] 活动商品是否显示原价（带删除线）？
- [ ] 是否显示折扣百分比标签？
- [ ] 价格颜色是否使用主题色？

---

## 前端开发规范

### UI 设计与样式修改规范

#### 核心原则

**重要**：所有涉及 UI 样式和页面设计的任务，**必须调用 `/ui-ux-pro-max` 工具来完成**。

#### 适用场景

| 场景类型 | 说明 | 示例 |
|---------|------|------|
| 页面设计 | 新建页面的整体布局和样式 | 新建商品详情页、用户中心页 |
| 组件开发 | 新建或修改 UI 组件 | 按钮、卡片、弹窗、导航栏 |
| 样式调整 | 修改现有页面的视觉效果 | 调整间距、颜色、字体 |
| 响应式设计 | 适配不同屏幕尺寸 | 移动端、平板、桌面端适配 |
| 动画效果 | 添加过渡、动画 | 加载动画、页面切换效果 |
| 交互优化 | 改善用户操作体验 | hover 效果、点击反馈 |

#### 正确做法

```text
✅ 正确：遇到 UI 相关任务时，调用 /ui-ux-pro-max 工具
❌ 错误：直接手写样式代码而不使用专业工具
```

#### 工具能力

`/ui-ux-pro-max` 工具提供：

- **50+ 设计风格**：glassmorphism、minimalism、brutalism、neumorphism 等
- **21 种调色板**：专业配色方案
- **50+ 字体搭配**：优化的字体组合
- **20+ 图表类型**：数据可视化支持
- **8 种技术栈**：React、Vue、Svelte、Tailwind、Flutter 等

---

### 加载状态规范（LoadingPage 组件）

#### 概述

TURNSY 用户端（bbo-app）统一使用 `LoadingPage` 组件处理所有加载状态显示。该组件采用极简设计风格，参考 eBay App 的加载页设计，提供简洁高级感的用户体验。

**组件位置**：`bbo-app/src/components/LoadingPage.vue`

#### 设计理念

| 特性 | 说明 |
|------|------|
| 风格 | Minimalism（极简主义） |
| 参考 | eBay App 加载页 |
| 核心元素 | Logo + 呼吸动画 + 点状加载器 |
| 主题支持 | 浅色（light）/ 深色（dark） |

#### 组件 Props

| Prop | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `theme` | `'light' \| 'dark'` | `'light'` | 主题模式 |
| `showText` | `boolean` | `false` | 是否显示加载文字 |
| `text` | `string` | `''` | 自定义加载文字（为空时使用 `t('common.loading')`） |
| `showProgress` | `boolean` | `false` | 是否显示进度条 |
| `progress` | `number` | `0` | 进度值 0-100 |

#### 使用场景

**1. 页面初始加载（推荐）**

```vue
<template>
  <LoadingPage v-if="pageLoading" />
  <view v-else class="page">
    <!-- 页面内容 -->
  </view>
</template>

<script setup lang="ts">
import LoadingPage from '@/components/LoadingPage.vue'

const pageLoading = ref(true)

onShow(async () => {
  pageLoading.value = true
  await loadData()
  pageLoading.value = false
})
</script>
```

**2. 显示加载文字**

```vue
<LoadingPage v-if="loading" :show-text="true" />
```

**3. 自定义加载文字**

```vue
<LoadingPage
  v-if="loading"
  :show-text="true"
  :text="t('goods.loadingGoods')"
/>
```

**4. 显示进度条（适用于上传、下载等场景）**

```vue
<LoadingPage
  v-if="uploading"
  :show-progress="true"
  :progress="uploadProgress"
/>
```

**5. 深色主题**

```vue
<LoadingPage v-if="loading" theme="dark" />
```

**6. 完整配置示例**

```vue
<LoadingPage
  v-if="loading"
  theme="light"
  :show-text="true"
  :text="t('order.processing')"
  :show-progress="true"
  :progress="processProgress"
/>
```

#### 禁止使用的方式

```vue
<!-- ❌ 错误：使用 uni.showLoading 进行页面级加载 -->
<script setup>
onShow(() => {
  uni.showLoading({ title: '加载中...' })
  await loadData()
  uni.hideLoading()
})
</script>

<!-- ❌ 错误：自定义加载样式 -->
<view v-if="loading" class="custom-loading">
  <text>加载中...</text>
</view>

<!-- ❌ 错误：使用第三方加载组件 -->
<u-loading v-if="loading" />
```

#### uni.showLoading 的适用场景

`uni.showLoading` 仅用于**短暂的操作反馈**，而非页面级加载：

```typescript
// ✅ 正确：按钮操作的短暂加载
async function submitOrder() {
  uni.showLoading({ title: t('common.submitting') })
  try {
    await createOrder()
    uni.hideLoading()
    uni.showToast({ title: t('common.success'), icon: 'success' })
  } catch (e) {
    uni.hideLoading()
    uni.showToast({ title: t('common.failed'), icon: 'none' })
  }
}

// ✅ 正确：切换操作的短暂加载
async function toggleFavorite() {
  uni.showLoading({ title: t('common.loading') })
  await toggleLike(goodsId)
  uni.hideLoading()
}
```

#### 使用决策流程

```
需要显示加载状态？
    │
    ├── 页面初次加载 / 数据为空时的全屏加载
    │   └── 使用 LoadingPage 组件 ✅
    │
    ├── 按钮点击后的短暂操作（提交、切换等）
    │   └── 使用 uni.showLoading ✅
    │
    ├── 列表加载更多
    │   └── 使用页面内的加载指示器（如 loading-more-spinner）✅
    │
    └── 下拉刷新
        └── 使用 uni 自带的下拉刷新指示器 ✅
```

#### 代码审查清单

- [ ] 页面初始加载是否使用 `LoadingPage` 组件？
- [ ] 是否避免使用 `uni.showLoading` 进行页面级加载？
- [ ] 加载文字是否使用 i18n 翻译？
- [ ] 是否根据场景选择合适的主题（light/dark）？
- [ ] 长时间操作是否显示进度条？

---

### Vue 组件规范

#### 1. 组件结构

```vue
<template>
  <view class="page-container">
    <!-- 使用 t() 函数获取翻译 -->
    <text>{{ t('module.key') }}</text>

    <!-- 使用后端返回的翻译数据 -->
    <text>{{ data?.translatedField }}</text>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// 类型定义
interface DataType {
  id: number
  translatedField: string
}

const data = ref<DataType | null>(null)
</script>
```

#### 2. 类型转换

处理后端返回的数据类型：

```typescript
// 处理数字类型（el-input-number 需要 Number 类型）
form.price = Number(goods.price) || 0
form.stock = Number(goods.stock) || 1

// 处理数组类型（el-image preview-src-list 需要 Array 类型）
const ensureImagesArray = (images: any): string[] => {
  if (!images) return []
  if (Array.isArray(images)) return images
  if (typeof images === 'object') return Object.values(images)
  return []
}
```

### UI 翻译维护（数据库方式）

#### 1. 新增 UI 翻译的正确流程

**重要**：禁止在本地 JSON 文件中添加翻译，必须通过数据库添加。

```bash
# ⛔ 禁止：修改本地文件
bbo-app/src/locale/zh-TW/module.json  # 禁止添加新键

# ✅ 正确：创建 SQL 文件并执行
bbo-server/database/ui_translations_xxx.sql
```

#### 2. ui_translations 表字段格式规范（重要）

**⚠️ 核心原则：namespace 和 key 必须与前端 JSON 文件结构对应**

查看 `bbo-app/src/locale/` 下的 JSON 文件结构来确定正确的格式：

**情况 A：翻译键在 common.json 中（如 auth、checkout、page）**

```json
// common.json 结构
{
  "auth": {           // auth 是 common.json 下的嵌套对象
    "login": "...",
    "resetPassword": "..."
  },
  "checkout": { ... },
  "page": { ... }
}
```

| 前端调用方式 | namespace | key | 说明 |
|-------------|-----------|-----|------|
| `t('auth.resetPassword')` | `common` | `auth.resetPassword` | auth 是 common 下的嵌套对象 |
| `t('checkout.pleaseSelectAddress')` | `common` | `checkout.pleaseSelectAddress` | checkout 是 common 下的嵌套对象 |
| `t('page.home')` | `common` | `page.home` | page 是 common 下的嵌套对象 |

**情况 B：翻译键有独立 JSON 文件（如 orderList、goods、user）**

```
bbo-app/src/locale/zh-TW/
├── common.json
├── user.json        // 独立文件
├── goods.json       // 独立文件
└── order.json       // 独立文件
```

| 前端调用方式 | namespace | key | 说明 |
|-------------|-----------|-----|------|
| `t('orderList.title')` | `orderList` | `title` | orderList 有独立文件或是顶级命名空间 |
| `t('goods.price')` | `goods` | `price` | goods 有独立文件 |
| `t('user.myProfile')` | `user` | `myProfile` | user 有独立文件 |

**⚠️ 常见错误**：

```sql
-- ⛔ 错误：auth 是 common.json 下的嵌套对象，但使用了独立 namespace
INSERT INTO ui_translations (namespace, `key`, ...) VALUES
('auth', 'resetPassword', ...);  -- 错误！auth 不是顶级命名空间

-- ✅ 正确：使用 namespace='common'，key 带 'auth.' 前缀
INSERT INTO ui_translations (namespace, `key`, ...) VALUES
('common', 'auth.resetPassword', ...);  -- 正确！
```

**如何判断用哪种格式？**

1. 查看 `bbo-app/src/locale/zh-TW/` 目录
2. 如果翻译键对应的是 `common.json` 里的嵌套对象 → 用情况 A
3. 如果翻译键对应的是独立 JSON 文件 → 用情况 B
4. 参考现有 SQL 文件：`database/ui_translations_checkout_order.sql`（情况 A 示例）

**SQL 文件模板示例**：

```sql
-- 情况 A：auth 等 common.json 下的嵌套对象
-- 参考：database/ui_translations_checkout_order.sql
INSERT INTO `ui_translations` (`locale`, `namespace`, `key`, `value`, ...) VALUES
('en-us', 'common', 'auth.resetPassword', 'Reset Password', ...),
('en-us', 'common', 'auth.sendCode', 'Send Code', ...),
('zh-tw', 'common', 'auth.resetPassword', '重設密碼', ...);

-- 情况 B：独立命名空间
INSERT INTO `ui_translations` (`locale`, `namespace`, `key`, `value`, ...) VALUES
('en-us', 'orderList', 'title', 'My Orders', ...),
('en-us', 'orderList', 'statusPending', 'Pending', ...),
('zh-tw', 'orderList', 'title', '我的訂單', ...);
```

#### 3. 执行和缓存清除

```bash
# 执行 SQL
mysql -u bbo -p123456 bbo < database/ui_translations_xxx.sql

# 清除后端缓存
php -r "
require 'vendor/autoload.php';
\$app = new think\App(); \$app->initialize();
use think\facade\Cache;
foreach (['zh-tw','en-us','ja-jp','ko-kr'] as \$l) Cache::delete('ui_translations:'.\$l);
"
```

#### 4. 强制前端更新（可选）

递增 `bbo-app/src/locale/index.ts` 中的 `TRANSLATIONS_CACHE_FORMAT_VERSION`

---

## 数据库规范

### 表命名规范

| 类型 | 命名规则 | 示例 |
|------|----------|------|
| 主表 | 小写复数 | `goods`, `users`, `orders` |
| 翻译表 | 主表名_translations | `goods_translations`, `category_translations` |
| 关联表 | 表1_表2 | `user_roles`, `goods_categories` |

### 翻译表结构规范

#### 方案 A：简单翻译表（推荐）

适用于大多数场景，不需要追踪翻译来源：

```sql
CREATE TABLE `xxx_translations` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `xxx_id` int(11) UNSIGNED NOT NULL COMMENT '主表ID',
  `locale` varchar(10) NOT NULL COMMENT '语言代码',
  `name` varchar(100) NOT NULL COMMENT '名称',
  `description` varchar(500) DEFAULT NULL COMMENT '描述',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_xxx_locale` (`xxx_id`, `locale`),
  KEY `idx_locale` (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 方案 B：完整翻译表

适用于需要追踪翻译来源、支持自动翻译标记的场景：

```sql
CREATE TABLE `xxx_translations` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `xxx_id` int(11) UNSIGNED NOT NULL COMMENT '主表ID',
  `locale` varchar(10) NOT NULL COMMENT '语言代码',
  `name` varchar(100) NOT NULL COMMENT '名称',
  `description` varchar(500) DEFAULT NULL COMMENT '描述',
  `is_original` tinyint(1) DEFAULT 0 COMMENT '是否原始语言',
  `is_auto_translated` tinyint(1) DEFAULT 0 COMMENT '是否自动翻译',
  `translated_at` datetime DEFAULT NULL COMMENT '翻译时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_xxx_locale` (`xxx_id`, `locale`),
  KEY `idx_locale` (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 自动翻译服务配置

#### 概述

系统支持在新增语言时自动调用翻译 API 为所有翻译表生成翻译内容。当创建新的翻译表时，需要在 `LanguageBatchTranslateService` 中注册该表。

#### 配置文件

**文件位置**：`bbo-server/app/common/service/LanguageBatchTranslateService.php`

#### 注册新翻译表

在 `$translationTables` 数组中添加新表的配置：

```php
protected $translationTables = [
    // ... 已有配置 ...

    // 新增翻译表配置
    'xxx_translations' => [
        'foreign_key' => 'xxx_id',           // 外键字段名
        'fields' => ['name', 'description'], // 需要翻译的字段
        'has_extra_fields' => false,         // 是否有额外字段
    ],
];
```

#### 配置项说明

| 配置项 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| `foreign_key` | string | 是 | 关联主表的外键字段名，如 `goods_id`、`category_id` |
| `fields` | array | 是 | 需要翻译的字段数组，如 `['name', 'description']` |
| `has_extra_fields` | bool | 是 | 表是否包含 `is_original`、`is_auto_translated`、`translated_at` 字段 |

#### has_extra_fields 取值规则

| 表结构 | `has_extra_fields` 值 |
|--------|----------------------|
| 方案 A（简单翻译表） | `false` |
| 方案 B（完整翻译表） | `true` |

**重要**：如果表没有额外字段但设置为 `true`，插入时会报错！

#### 已注册的翻译表

| 表名 | 外键 | 翻译字段 | 有额外字段 |
|------|------|----------|-----------|
| `goods_translations` | `goods_id` | title, description | ✅ |
| `category_translations` | `category_id` | name, description | ❌ |
| `brand_translations` | `brand_id` | name, description | ✅ |
| `banner_translations` | `banner_id` | title, subtitle | ❌ |
| `coupon_translations` | `coupon_id` | name, description | ❌ |
| `category_attribute_translations` | `attribute_id` | name, placeholder | ❌ |
| `attribute_option_translations` | `option_id` | label | ❌ |
| `system_config_translations` | `config_id` | name, description | ✅ |
| `payment_method_translations` | `payment_method_id` | name, description | ✅ |
| `customer_service_translations` | `service_id` | name | ❌ |
| `shipping_carrier_translations` | `carrier_id` | name, description | ✅ |
| `category_condition_group_translations` | `group_id` | name | ❌ |
| `category_condition_option_translations` | `option_id` | name | ❌ |

#### 新增翻译表检查清单

- [ ] 确定使用方案 A（简单）还是方案 B（完整）
- [ ] 创建数据库翻译表
- [ ] 在 `LanguageBatchTranslateService.php` 的 `$translationTables` 数组中添加配置
- [ ] 正确设置 `has_extra_fields`（方案 A 为 `false`，方案 B 为 `true`）
- [ ] 测试新增语言时该表能否正常翻译

#### 翻译 API 配置

系统配置中需要设置以下项（系统管理 → 系统配置 → 翻译设置）：

| 配置键 | 说明 | 示例值 |
|--------|------|--------|
| `translate_api_provider` | 翻译服务商 | `google`、`deepl`、`baidu` |
| `translate_api_key` | API 密钥 | DeepL: `xxx:fx`（Free）或 `xxx`（Pro） |
| `translate_api_region` | API 区域（可选） | 部分服务商需要 |

**支持的翻译服务商**：

| 服务商 | 代码 | API Key 格式 | 说明 |
|--------|------|-------------|------|
| Google Translate | `google` | API Key | 支持 100+ 语言 |
| DeepL | `deepl` | `xxx:fx`（Free）或 `xxx`（Pro） | 翻译质量高，支持 29 种语言 |
| 百度翻译 | `baidu` | `appid\|secret_key` | 中文翻译效果好 |

---

## API 规范

### 请求头

```
Accept-Language: zh-tw | en-us | ja-jp
Authorization: Bearer {token}
```

### 响应格式

```json
{
  "code": 0,
  "msg": "success",
  "data": {
    // 响应数据
  }
}
```

### 分页响应

```json
{
  "code": 0,
  "msg": "success",
  "data": {
    "list": [],
    "total": 100,
    "page": 1,
    "pageSize": 20,
    "totalPages": 5
  }
}
```

---

## 通知系统规范

### 架构概述

通知系统采用统一入口设计，支持多渠道通知（邮件、站内消息、推送）。

```
┌─────────────────────────────────────────────────────────────────┐
│                      NotificationService                         │
│                      (统一通知服务入口)                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  notify(userId, type, data, channels)                          │
│     │                                                           │
│     ├─→ EmailService::send()       → 邮件通知                   │
│     ├─→ Notification::create()     → 站内消息                   │
│     └─→ PushService::send()        → APP推送（未来扩展）        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 通知类型常量

```php
// app/common/service/NotificationService.php

class NotificationService
{
    // 通知类型
    const TYPE_ORDER_CREATED = 'order_created';       // 订单创建
    const TYPE_PAYMENT_SUCCESS = 'payment_success';   // 支付成功
    const TYPE_ORDER_SHIPPED = 'order_shipped';       // 订单发货
    const TYPE_ORDER_DELIVERED = 'order_delivered';   // 订单送达
    const TYPE_ORDER_CANCELLED = 'order_cancelled';   // 订单取消
    const TYPE_REFUND_SUCCESS = 'refund_success';     // 退款成功
    const TYPE_PREAUTH_VOIDED = 'preauth_voided';     // 预授权释放

    // 通知渠道
    const CHANNEL_EMAIL = 'email';      // 邮件
    const CHANNEL_MESSAGE = 'message';  // 站内消息
    const CHANNEL_PUSH = 'push';        // APP推送（预留）
}
```

### 使用方法

#### 基础用法

```php
use app\common\service\NotificationService;

$notificationService = new NotificationService();

// 发送通知（默认邮件+站内消息）
$notificationService->notify(
    userId: $order->buyer_id,
    type: NotificationService::TYPE_ORDER_SHIPPED,
    data: [
        'order' => $order->toArray(),
        'shipment' => $shipment->toArray(),
    ]
);
```

#### 指定通知渠道

```php
// 仅发送邮件
$notificationService->notify(
    userId: $userId,
    type: NotificationService::TYPE_ORDER_SHIPPED,
    data: ['order' => $order],
    channels: [NotificationService::CHANNEL_EMAIL]
);

// 仅发送站内消息
$notificationService->notify(
    userId: $userId,
    type: NotificationService::TYPE_ORDER_CANCELLED,
    data: ['order' => $order],
    channels: [NotificationService::CHANNEL_MESSAGE]
);
```

#### 快捷方法

```php
// 订单发货通知
$notificationService->notifyOrderShipped($userId, $orderData, $shipmentData);

// 订单创建通知
$notificationService->notifyOrderCreated($userId, $orderData);

// 订单取消通知
$notificationService->notifyOrderCancelled($userId, $orderData);

// 支付成功通知
$notificationService->notifyPaymentSuccess($userId, $orderData);

// 退款成功通知
$notificationService->notifyRefundSuccess($userId, $orderData, $refundAmount, $currency);
```

### 邮件服务配置

邮件配置存储在数据库 `system_configs` 表中（`group = 'mail'`），通过管理后台配置。

| 配置项 | 说明 | 示例值 |
|--------|------|--------|
| `mail_enabled` | 启用邮件发送 | `1` |
| `mail_driver` | 邮件驱动 | `smtp` |
| `mail_host` | SMTP服务器 | `smtp.gmail.com` |
| `mail_port` | SMTP端口 | `587` |
| `mail_username` | SMTP用户名 | `your@gmail.com` |
| `mail_password` | SMTP密码/应用密码 | `xxxx` |
| `mail_encryption` | 加密方式 | `tls` |
| `mail_from_address` | 发件人邮箱 | `turnsyshop@gmail.com` |
| `mail_from_name` | 发件人名称 | `TURNSY Marketplace` |

**Gmail 应用密码配置**：

1. 开启 Google 账号两步验证
2. 访问 <https://myaccount.google.com/apppasswords>
3. 生成应用专用密码
4. 将密码填入 `mail_password`

### 邮件模板

邮件模板存放在 `bbo-server/app/view/email/` 目录，按通知类型和语言组织：

```
app/view/email/
├── order_shipped/
│   ├── en-us.html     # 英文模板
│   ├── zh-tw.html     # 繁体中文模板
│   └── ja-jp.html     # 日文模板
├── order_created/
│   ├── en-us.html
│   ├── zh-tw.html
│   └── ja-jp.html
└── ...
```

#### 模板变量

模板中可使用以下变量（使用 `{{$variable}}` 或 `{variable}` 语法）：

**用户信息**：

- `{{$buyer_name}}` - 买家昵称
- `{{$user_email}}` - 用户邮箱

**订单信息**：

- `{{$order_no}}` - 订单号
- `{{$total_amount}}` - 订单总金额（已格式化）
- `{{$goods_title}}` - 商品标题
- `{{$quantity}}` - 商品数量
- `{{$price}}` - 商品价格

**物流信息**：

- `{{$tracking_no}}` - 物流单号
- `{{$carrier_name}}` - 运输商名称
- `{{$tracking_url}}` - 物流追踪链接
- `{{$shipped_date}}` - 发货日期
- `{{$estimated_delivery}}` - 预计到达时间

**收货地址**：

- `{{$recipient_name}}` - 收件人
- `{{$address}}` - 街道地址
- `{{$city}}` / `{{$state}}` / `{{$postal_code}}` / `{{$country}}`

### 站内消息

站内消息存储在 `notifications` 表中：

```sql
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '接收用户ID',
  `type` VARCHAR(50) NOT NULL COMMENT '通知类型',
  `title` VARCHAR(200) NOT NULL COMMENT '通知标题',
  `content` TEXT NOT NULL COMMENT '通知内容',
  `data` JSON DEFAULT NULL COMMENT '关联数据',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已读',
  `read_at` DATETIME DEFAULT NULL COMMENT '阅读时间',
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 邮件日志

所有邮件发送记录保存在 `email_logs` 表中：

```sql
CREATE TABLE `email_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联用户ID',
  `to_email` VARCHAR(255) NOT NULL COMMENT '收件人邮箱',
  `to_name` VARCHAR(100) DEFAULT NULL COMMENT '收件人姓名',
  `subject` VARCHAR(500) NOT NULL COMMENT '邮件主题',
  `template` VARCHAR(100) DEFAULT NULL COMMENT '邮件模板',
  `content` LONGTEXT DEFAULT NULL COMMENT '邮件内容',
  `data` JSON DEFAULT NULL COMMENT '模板数据',
  `status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '状态: 0待发送 1已发送 2发送失败',
  `error_message` TEXT DEFAULT NULL COMMENT '错误信息',
  `sent_at` DATETIME DEFAULT NULL COMMENT '发送时间',
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 注意事项

1. **禁止使用 `$model->key`**：ThinkPHP Model 基类有 `protected $key` 属性，访问数据库 `key` 字段时必须使用 `$model->getData('key')`

2. **PHP 7.2 兼容性**：
   - 不支持属性类型声明（如 `protected array $config`）
   - 不支持 nullsafe 操作符（`?->`），使用三元运算符替代

3. **邮件发送失败排查**：
   - 检查 `mail_enabled` 是否为 `'1'`
   - 检查 SMTP 凭据是否正确
   - Gmail 需使用应用专用密码
   - 查看 `runtime/log/` 日志文件

4. **多语言支持**：通知服务会自动根据用户的 `language` 字段选择对应语言的邮件模板和消息内容

---

## 代码审查清单

### 多语言相关

- [ ] 新增的模型是否需要多语言支持？如需要，是否：
  - [ ] 创建了翻译表
  - [ ] 使用了 Translatable Trait
  - [ ] 定义了 translatable 字段
- [ ] 控制器是否正确传递了 `$this->locale` 参数？
- [ ] `toApiArray` 方法是否正确处理了翻译？
- [ ] 系统配置获取是否使用了 `getConfigTranslated`？
- [ ] **UI 翻译**：新增翻译是否通过数据库（`ui_translations` 表）添加？
- [ ] **禁止本地硬编码**：是否避免在 `locale/*.json` 文件中添加新的翻译键？
- [ ] 翻译 SQL 是否包含四种语言（zh-tw, en-us, ja-jp, ko-kr）？
- [ ] **弹窗多语言**：使用 `uni.showModal` 时是否设置了 `confirmText` 和 `cancelText`？
- [ ] **导航标题多语言**：页面是否在 `onShow` 中使用 `uni.setNavigationBarTitle` 设置了标题？

### 货币汇率相关（必须遵守）

**重要原则：所有金额都必须进行汇率转换，无一例外！**

- [ ] 页面是否涉及金额显示？如涉及，是否：
  - [ ] 引入了 `useAppStore`
  - [ ] 定义了 `formatPrice` 函数调用 `appStore.formatPrice`
  - [ ] **所有金额**（现价、原价、运费、服务费等）都使用 `formatPrice(amount, currency)` 格式显示
- [ ] 后端 API 返回金额时是否同时返回 `currency` 字段？
- [ ] 是否测试了切换国家后**所有金额**显示是否正确更新？
- [ ] 日元金额是否正确显示为整数（无小数位）？
- [ ] **禁止**：直接显示金额数字（如 `${{ price }}`）
- [ ] **禁止**：硬编码货币符号（如 `$`、`¥`）
- [ ] **禁止**：任何金额不经过 `formatPrice` 转换直接显示

### 数据类型相关

- [ ] 后端返回的数字字段是否正确转换为数字类型？
- [ ] 后端返回的数组字段是否正确返回数组格式？
- [ ] 前端是否正确处理了可能的 null/undefined 值？

### 通用检查

- [ ] 是否有 SQL 注入风险？
- [ ] 是否有 XSS 风险？
- [ ] 是否正确处理了错误情况？
- [ ] 是否有适当的日志记录？
- [ ] API 是否有权限控制？

---

## 常见问题与解决方案

### Q1: 英文环境下显示中文

**原因**：语言代码不一致，翻译表中可能使用了 `en` 而不是 `en-us`

**解决**：
1. 确保翻译表中使用统一的 `en-us` 作为英文 locale
2. 检查后端控制器的 `$supportedLocales` 是否为 `['zh-tw', 'en-us', 'ja-jp']`
3. 检查前端管理界面的 locale key 是否使用 `'en-us'`

**数据库修复**（如果存在旧数据）：
```sql
-- 将旧的 'en' locale 迁移到 'en-us'
UPDATE xxx_translations SET locale = 'en-us' WHERE locale = 'en';
```

### Q2: 翻译不生效，显示原始值

**原因**：
1. 翻译表中没有对应语言的记录
2. 翻译字段为空
3. 未正确调用翻译方法
4. locale 格式不匹配（如使用 `en` 而非 `en-us`）

**解决**：
1. 检查翻译表数据，确认 locale 格式正确（`zh-tw`、`en-us`、`ja-jp`）
2. 确保在管理端保存了翻译
3. 使用 `getTranslated` 或 `getConfigTranslated` 方法

### Q3: Element Plus 组件类型警告

**原因**：后端返回的数据类型与组件期望类型不匹配

**解决**：
```typescript
// 数字类型
form.price = Number(data.price) || 0

// 数组类型
form.images = ensureImagesArray(data.images)
```

### Q4: 新增系统配置不显示翻译

**检查步骤**：
1. 配置的 `type` 是否为 `string`
2. 翻译表 `system_config_translations` 是否有 `value` 字段
3. 是否在管理端保存了各语言的翻译值
4. 获取配置时是否使用了 `getConfigTranslated` 方法

---

## 版本历史

| 版本 | 日期 | 说明 |
|------|------|------|
| 1.0 | 2024-12-30 | 初始版本 |
| 1.1 | 2024-12-30 | 新增货币汇率转换规范 |
| 1.2 | 2026-01-08 | **统一语言代码规范**：全系统统一使用 `en-us` 替代 `en`，翻译表、后端控制器、前端管理界面均使用相同的 locale 格式（`zh-tw`、`en-us`、`ja-jp`） |
| 1.3 | 2026-01-08 | **新增商品价格显示规范**：定义活动价格优先显示策略，包含后端批量查询方法、前端价格显示模板、需检查的页面清单 |
| 1.4 | 2026-01-08 | **新增加载状态规范**：定义 `LoadingPage` 组件作为用户端统一加载页，包含使用场景、Props 说明、决策流程、代码审查清单 |
| 1.5 | 2026-01-12 | **新增自动翻译服务配置规范**：定义翻译表结构（方案 A/B）、`LanguageBatchTranslateService` 注册配置、`has_extra_fields` 取值规则、翻译 API 配置说明 |
| 1.6 | 2026-01-13 | **语言中间件动态化重构**：语言中间件改为从 `languages` 数据库表动态获取支持的语言列表（带缓存），禁止硬编码语言数组；新增语言管理控制器缓存清除规范；新增韩语（ko-kr）语言代码标准化支持；新增前端 localeMap 使用说明（仅用于首次访问用户的系统语言映射） |
| 1.7 | 2026-01-13 | **货币与国家管理系统重构**：将国家/货币硬编码数据迁移到数据库，通过 API 动态管理；新增 currencies、countries、country_translations 表；价格显示统一使用货币符号（非代码）并取整；国家选择自动切换语言和货币 |
| 1.8 | 2026-01-13 | **前端 UI 翻译 API 化**：明确禁止在本地 JSON 文件中硬编码新的翻译内容，所有 UI 翻译必须通过数据库 `ui_translations` 表管理，前端通过 API `/api/system/ui-translations` 获取；新增翻译 SQL 文件模板、缓存清除流程、代码审查清单 |
| 1.9 | 2026-01-14 | **新增通知系统规范**：定义 `NotificationService` 统一通知入口，支持邮件和站内消息渠道；邮件配置存储于数据库 `system_configs` 表；邮件模板按语言组织（`app/view/email/`）；新增 `notifications`、`email_logs` 表结构；注意 ThinkPHP Model `$key` 属性冲突问题 |
