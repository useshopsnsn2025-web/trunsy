# TURNSY 项目开发指令

> **重要**：开发任务开始前，必须先读取 `DEVELOPMENT_GUIDE.md` 获取完整规范。
>
> 本文件仅为速查摘要，完整规范以 [DEVELOPMENT_GUIDE.md](DEVELOPMENT_GUIDE.md) 为准。
>
> **多语言开发**：请阅读 [docs/I18N_STANDARD.md](docs/I18N_STANDARD.md) 了解完整的国际化规范。

## 技术栈

| 模块 | 技术 |
|------|------|
| 后端 | ThinkPHP 8.x + MySQL 8.x |
| 管理端 | Vue 3 + Element Plus + TypeScript |
| APP端 | UniApp + Vue 3 + TypeScript |

## 核心规则

### 1. 语言代码（必须统一）

```
✅ 正确：zh-tw, en-us, ja-jp
❌ 禁止：en, zh-cn, ja
```

- 翻译表、后端、前端全部使用相同格式
- 降级顺序：请求语言 → en-us → 原始值
- 完整规范见 [docs/I18N_STANDARD.md](docs/I18N_STANDARD.md)

### 2. 货币显示（所有金额必须转换）

```typescript
// ✅ 正确
{{ formatPrice(goods.price, goods.currency) }}

// ❌ 禁止
${{ goods.price }}
{{ goods.price }} USD
```

- 使用 `appStore.formatPrice(amount, currency)`
- 后端 API 必须返回 `currency` 字段
- 日元自动显示整数（无小数）

### 3. 后端错误消息（数据库翻译）

```php
// ✅ 正确：英文翻译键
return $this->error('User not found');

// ❌ 禁止：硬编码中文
return $this->error('用户不存在');

// ❌ 禁止：在 app/lang/ 目录添加新翻译
```

- `Base.php` 自动从数据库 `ui_translations` 表获取翻译（命名空间：`message`）
- 键名自动转换：`User not found` → `user_not_found`
- **禁止在 `app/lang/` 目录硬编码新翻译**（该目录仅作为 fallback）
- 新增翻译：写 SQL 迁移文件 → 执行 → 清理 `runtime/cache/`

### 4. 前端 UI 翻译（API 获取 + 本地 Fallback）

```
⛔ 禁止：只在数据库添加翻译，不更新本地 fallback
✅ 正确：数据库 + 本地 fallback 同步更新
```

**新增命名空间时必须同步更新本地 fallback：**
```typescript
// 1. 创建 locale/zh-TW/xxx.json、locale/en-US/xxx.json、locale/ja-JP/xxx.json
// 2. 在 locale/index.ts 中导入并添加到 fallbackMessages
import zhTWXxx from './zh-TW/xxx.json'
const zhTWMessages = {
  ...
  xxx: zhTWXxx,  // 新增命名空间
}
```

**为什么需要本地 fallback？**

- vue-i18n 初始化时使用本地 fallback
- 页面渲染时 API 翻译可能还没加载完成
- 缺少本地 fallback 会导致显示原始键名（如 `game.dailyLoginReward`）

**新增翻译步骤：**

1. 写 SQL 迁移文件 → 执行 → 清理后端 `runtime/cache/`
2. **如果是新命名空间**：创建本地 JSON 文件并导入到 `locale/index.ts`
3. 递增 `TRANSLATIONS_CACHE_FORMAT_VERSION`

**弹窗必须设置按钮文字：**
```typescript
uni.showModal({
  title: t('user.logout'),
  content: t('user.logoutConfirm'),
  confirmText: t('common.confirm'),  // 必须
  cancelText: t('common.cancel'),    // 必须
})
```

**页面必须动态设置标题：**
```typescript
onShow(() => {
  uni.setNavigationBarTitle({ title: t('page.pageName') })
})
```

### 5. 加载状态

| 场景 | 方案 |
|------|------|
| 页面初始加载 | `<LoadingPage />` 组件 |
| 按钮操作 | `uni.showLoading()` |
| 列表加载更多 | 页内 spinner |

### 6. 商品价格显示

```
优先级：活动价格(promotionPrice) > 原始价格(price)
```

- 后端返回 `promotion` 字段
- 前端使用 `getDisplayPrice()` 获取显示价格
- 活动商品显示原价（删除线）+ 折扣标签



### 7. 数据库配置

- 数据库名称：bbo
- 用户名：bbo
- 密码：123456

### 8. 通知系统

```php
use app\common\service\NotificationService;

$notificationService = new NotificationService();

// 发送通知（默认邮件+站内消息）
$notificationService->notify(
    userId: $userId,
    type: NotificationService::TYPE_ORDER_SHIPPED,
    data: ['order' => $order, 'shipment' => $shipment]
);

// 快捷方法
$notificationService->notifyOrderShipped($userId, $orderData, $shipmentData);
$notificationService->notifyOrderCreated($userId, $orderData);
$notificationService->notifyOrderCancelled($userId, $orderData);
$notificationService->notifyOrderVerified($userId, $orderData);
$notificationService->notifyOrderVerifyFailed($userId, $orderData, $failReason, $failMessage);
```

**通知类型常量**：
- `TYPE_ORDER_CREATED` - 订单创建
- `TYPE_PAYMENT_SUCCESS` - 支付成功
- `TYPE_ORDER_SHIPPED` - 订单发货
- `TYPE_ORDER_CANCELLED` - 订单取消
- `TYPE_REFUND_SUCCESS` - 退款成功
- `TYPE_ORDER_VERIFIED` - 订单验证成功
- `TYPE_ORDER_VERIFY_FAILED` - 订单验证失败

**渠道常量**：

- `CHANNEL_EMAIL` - 邮件
- `CHANNEL_MESSAGE` - 站内消息

**邮件模板存储**：

- 模板存储在数据库 `email_templates` 表
- 翻译存储在 `email_template_translations` 表
- 使用 `EmailTemplate::render($type, $locale, $variables)` 获取渲染后的邮件内容
- 新增语言只需在 `email_template_translations` 表添加翻译记录

### 9. ThinkPHP Model 注意事项

```php
// ⚠️ 禁止：直接访问 $model->key（与 Model 基类属性冲突）
$result[$config->key] = $config->value;  // ❌ 返回 null

// ✅ 正确：使用 getData() 获取原始字段值
$result[$config->getData('key')] = $config->value;  // ✅
```

### 10. 图片路径处理（UrlHelper）

```php
use app\common\helper\UrlHelper;

// ✅ 正确：API 返回图片时使用 getFullUrl() 转换
'cover_image' => UrlHelper::getFullUrl($goods['cover_image'] ?? ''),

// ❌ 错误：直接返回相对路径（前端无法显示）
'cover_image' => $goods['cover_image'] ?? '',
```

- `getFullUrl($path)` - 相对路径转完整 URL
- `convertImageUrl($url)` - 转换本地存储 URL 的域名
- `convertFieldUrls($data, $fields)` - 批量转换数组中的图片字段
- 数据库快照字段（如 `goods_snapshot`）通常存储相对路径，需要转换

### 11. UI 设计与样式修改

**重要**：所有涉及 UI 样式和页面设计的任务，**必须调用 `/ui-ux-pro-max` 工具来完成**。

适用场景：

- 新建页面或组件的 UI 设计
- 修改现有页面的样式、布局
- 调整颜色、字体、间距等视觉元素
- 实现响应式设计
- 添加动画、过渡效果
- 优化用户交互体验

```text
✅ 正确：使用 /ui-ux-pro-max 工具处理 UI 任务
❌ 错误：直接手写样式代码
```

## 多语言模型 Trait

```php
use app\common\traits\Translatable;

class Example extends Model
{
    use Translatable;
    protected $translationModel = ExampleTranslation::class;
    protected $translationForeignKey = 'example_id';
    protected $translatable = ['title', 'description'];
}

// 使用
$model->getTranslated('title', $locale);
Example::appendTranslations($list, $locale);
SystemConfig::getConfigTranslated('key', $locale, $default);
```

## 快速检查清单

### 新增 API
- [ ] 错误消息使用英文翻译键
- [ ] 翻译添加到数据库 `ui_translations` 表（命名空间 `message`）
- [ ] **禁止在 `app/lang/` 目录添加新翻译**
- [ ] 金额返回 `currency` 字段
- [ ] 使用 `$this->locale` 获取翻译

### 新增页面（bbo-app）
- [ ] `onShow` 设置导航标题
- [ ] 金额使用 `formatPrice()`
- [ ] 弹窗设置按钮文字
- [ ] 页面加载使用 `LoadingPage`
- [ ] 新翻译键添加到数据库
- [ ] **新命名空间必须同步创建本地 fallback JSON 文件**
- [ ] **带插值的翻译使用智能检测方式处理**（检查是否包含占位符）

### 新增翻译模型
- [ ] 创建 `xxx_translations` 表
- [ ] 使用 `Translatable` Trait
- [ ] 定义 `$translatable` 字段
- [ ] `toApiArray()` 传入 `$locale`

## 目录结构

```
bbo/
├── bbo-server/app/
│   ├── admin/          # 管理端 API
│   ├── api/            # APP 端 API
│   ├── common/         # 模型、Trait
│   └── lang/           # 后端语言包 fallback（禁止新增，翻译从数据库获取）
├── bbo-admin/src/
│   ├── views/          # 管理端页面
│   └── locale/         # 管理端多语言
└── bbo-app/src/
    ├── pages/          # APP 页面
    ├── components/     # 组件 (LoadingPage 等)
    └── locale/         # APP 多语言 fallback（禁止新增，翻译从 API 获取）
```

## 常见错误

| 错误 | 原因 | 解决 |
|------|------|------|
| 英文显示中文 | locale 用了 `en` | 改为 `en-us` |
| 金额不转换 | 未用 `formatPrice` | 所有金额必须转换 |
| 弹窗按钮乱码 | 未设置按钮文字 | 添加 `confirmText/cancelText` |
| 标题不翻译 | 未在 `onShow` 设置 | 动态设置导航标题 |
| API 消息不翻译 | 翻译加到 `app/lang/` | 添加到数据库 `ui_translations` 表 |
| 翻译显示键名 | 新命名空间缺少本地 fallback | 创建 `locale/*/xxx.json` 并导入 |
| APP端显示 `{keyword}` | vue-i18n 插值不工作 | 使用智能检测 + 手动替换（见完整规范） |

---

## 文档维护说明

本文件为 `DEVELOPMENT_GUIDE.md` 的精简摘要，用于快速参考。

**更新原则**：
- `DEVELOPMENT_GUIDE.md` 是**唯一真实来源**
- 本文件仅包含**最常用的核心规则**
- 新增规范时，只需更新 `DEVELOPMENT_GUIDE.md`
- 本文件可选择性更新（仅当新规范属于高频使用的核心规则时）

**Claude 行为**：
- 执行开发任务前，会主动读取 `DEVELOPMENT_GUIDE.md`
- 遇到本文件未覆盖的场景，以完整规范为准
