# TURNSY 多语言国际化规范 (I18N Standard)

> 本文档定义了 TURNSY 项目前后端多语言系统的统一标准，所有开发人员必须遵守。

## 1. 语言代码标准

### 1.1 格式规范

| 格式 | 示例 | 使用场景 |
|------|------|----------|
| `xx-xx` (小写) | `zh-tw`, `en-us`, `ja-jp`, `ko-kr` | 数据库、API、后端代码 |
| `xx-XX` (混合) | `zh-TW`, `en-US`, `ja-JP`, `ko-KR` | 前端目录名、文件名 |

```
✅ 正确：zh-tw, en-us, ja-jp, ko-kr
❌ 禁止：en, zh, ja, ko, zh-cn, zh_tw, zh_TW
```

### 1.2 支持的语言

| 代码 | 语言 | 数据库格式 | 前端目录 | 状态 |
|------|------|-----------|----------|------|
| 繁体中文 | Traditional Chinese | `zh-tw` | `zh-TW/` | 默认语言 |
| 英文 | English | `en-us` | `en-US/` | 已支持 |
| 日文 | Japanese | `ja-jp` | `ja-JP/` | 已支持 |
| 韩文 | Korean | `ko-kr` | `ko-KR/` | 动态添加 |

### 1.3 语言降级顺序

```
请求语言 → en-us (英文) → 原始值
```

---

## 2. 翻译架构

### 2.1 系统架构图

```
┌─────────────────────────────────────────────────────────────────┐
│                        翻译数据来源                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │ UI 翻译     │    │ 内容翻译    │    │ 后端消息    │        │
│  │ ui_translations│ │ *_translations│ │ lang/*.php  │        │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘        │
│         │                  │                  │                │
│         ▼                  ▼                  ▼                │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │ /api/system/│    │ API 响应    │    │ lang()函数  │        │
│  │ ui-translations│ │ 字段翻译    │    │ 自动翻译    │        │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘        │
│         │                  │                  │                │
│         ▼                  ▼                  ▼                │
│  ┌─────────────────────────────────────────────────────┐      │
│  │                    前端应用                          │      │
│  │  t('key') ──────▶ i18n ◀────── API 响应数据        │      │
│  └─────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 三种翻译类型

| 类型 | 存储位置 | 用途 | 示例 |
|------|----------|------|------|
| **UI 翻译** | `ui_translations` 表 | 界面文字、按钮、标签 | "确认"、"取消"、"登录" |
| **内容翻译** | `*_translations` 表 | 业务数据翻译 | 商品名、分类名、品牌名 |
| **后端消息** | `app/lang/*.php` | API 错误消息 | "用户不存在"、"密码错误" |

---

## 3. UI 翻译规范

### 3.1 命名空间定义

UI 翻译按功能模块划分命名空间：

| 命名空间 | 用途 | 示例键 |
|----------|------|--------|
| `common` | 通用文字 | `common.confirm`, `common.cancel` |
| `auth` | 登录注册 | `auth.login`, `auth.password` |
| `user` | 用户中心 | `user.profile`, `user.settings` |
| `goods` | 商品相关 | `goods.price`, `goods.stock` |
| `order` | 订单相关 | `order.status`, `order.pay` |
| `cart` | 购物车 | `cart.empty`, `cart.checkout` |
| `checkout` | 结账页 | `checkout.title`, `checkout.total` |
| `payment` | 支付相关 | `payment.method`, `payment.success` |
| `address` | 地址管理 | `address.add`, `address.default` |
| `search` | 搜索相关 | `search.placeholder`, `search.result` |
| `chat` | 聊天消息 | `chat.send`, `chat.input` |
| `publish` | 发布商品 | `publish.title`, `publish.upload` |
| `credit` | 信用额度 | `credit.apply`, `credit.limit` |
| `coupon` | 优惠券 | `coupon.use`, `coupon.expired` |
| `promotion` | 促销活动 | `promotion.discount`, `promotion.end` |
| `favorites` | 收藏 | `favorites.add`, `favorites.remove` |
| `share` | 分享 | `share.success`, `share.copy` |
| `page` | 页面标题 | `page.home`, `page.profile` |
| `action` | 操作按钮 | `action.save`, `action.delete` |
| `time` | 时间显示 | `time.today`, `time.yesterday` |
| `region` | 地区选择 | `region.us`, `region.tw` |
| `ticket` | 工单系统 | `ticket.submit`, `ticket.reply` |
| `message` | 消息通知 | `message.unread`, `message.mark` |

### 3.2 翻译键命名规范

```
{namespace}.{category}.{action/property}
```

**规则**：
- 使用 camelCase（小驼峰）
- 层级最多 3 层
- 语义清晰，避免缩写

```javascript
// ✅ 正确
t('checkout.orderSummary')
t('auth.login.title')
t('goods.addToCart')
t('payment.method.creditCard')

// ❌ 错误
t('chk.ordSum')           // 过度缩写
t('checkout_order_summary') // 下划线
t('a.b.c.d.e')            // 层级过深
```

### 3.3 数据库存储格式

**表结构**：`ui_translations`

| 字段 | 类型 | 说明 |
|------|------|------|
| `locale` | varchar(10) | 语言代码，小写 (zh-tw) |
| `namespace` | varchar(50) | 命名空间 (common, goods) |
| `key` | varchar(200) | 翻译键，支持点号嵌套 (login.title) |
| `value` | text | 翻译值 |

**存储示例**：

| locale | namespace | key | value |
|--------|-----------|-----|-------|
| zh-tw | checkout | title | 結帳 |
| zh-tw | checkout | orderSummary | 訂單摘要 |
| en-us | checkout | title | Checkout |
| en-us | checkout | orderSummary | Order Summary |

### 3.4 API 返回格式

**请求**：`GET /api/system/ui-translations?locale=zh-tw`

**响应**：
```json
{
  "code": 0,
  "data": {
    "locale": "zh-tw",
    "version": "abc123",
    "translations": {
      "common": {
        "checkout": {
          "title": "結帳",
          "orderSummary": "訂單摘要"
        },
        "auth": {
          "login": "登入"
        }
      },
      "user": {
        "myProfile": "我的帳戶",
        "buying": "購買"
      },
      "goods": {
        "price": "價格",
        "stock": "庫存"
      }
    }
  }
}
```

### 3.5 前端数据格式转换

前端从 API 加载翻译后，需要转换为与本地 JSON 组装一致的格式：

**转换规则**：
| 命名空间 | 处理方式 | 原因 |
|----------|----------|------|
| `common` | 展开内容到顶层 | `common.json` 用 `...spread` 展开 |
| 其他 | 保留命名空间结构 | 其他 JSON 用 `namespace: content` 组装 |

**转换示例**：
```
API 返回:
{
  common: { checkout: {...}, auth: {...} },
  user: { myProfile: '...' },
  goods: { price: '...' }
}

转换后:
{
  checkout: {...},    // common 内容展开
  auth: {...},        // common 内容展开
  user: { myProfile: '...' },  // 保留结构
  goods: { price: '...' }      // 保留结构
}
```

**注意**：前端使用缓存格式版本号控制，当转换逻辑变化时需要递增版本号。

### 3.6 前端 JSON 文件结构

**目录结构**：
```
bbo-app/src/locale/
├── zh-TW/
│   ├── common.json      # 通用翻译
│   ├── user.json        # 用户相关
│   ├── goods.json       # 商品相关
│   ├── order.json       # 订单相关
│   ├── cart.json        # 购物车
│   ├── credit.json      # 信用额度
│   ├── coupon.json      # 优惠券
│   ├── promotion.json   # 促销
│   ├── favorites.json   # 收藏
│   └── share.json       # 分享
├── en-US/
│   └── ... (同上)
└── ja-JP/
    └── ... (同上)
```

**文件结构规范**：

每个 JSON 文件的顶级键就是命名空间，文件名对应主命名空间：

```json
// common.json - 包含多个小命名空间
{
  "common": {
    "confirm": "確認",
    "cancel": "取消"
  },
  "action": {
    "save": "儲存",
    "delete": "刪除"
  },
  "page": {
    "home": "首頁",
    "profile": "我的"
  }
}

// goods.json - 单一命名空间
{
  "goods": {
    "price": "價格",
    "stock": "庫存",
    "addToCart": "加入購物車"
  }
}
```

---

## 4. 内容翻译规范（业务数据）

### 4.1 翻译表命名

```
{主表名}_translations
```

| 主表 | 翻译表 | 可翻译字段 |
|------|--------|-----------|
| goods | goods_translations | title, description |
| categories | category_translations | name, description |
| brands | brand_translations | name, description |
| banners | banner_translations | title, description, link_text |
| coupons | coupon_translations | name, description |

### 4.2 翻译表结构

```sql
CREATE TABLE `xxx_translations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `xxx_id` int(11) unsigned NOT NULL COMMENT '关联主表ID',
  `locale` varchar(10) NOT NULL COMMENT '语言代码',
  `title` varchar(255) DEFAULT NULL COMMENT '标题',
  `description` text COMMENT '描述',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `xxx_locale` (`xxx_id`, `locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.3 模型使用 Translatable Trait

```php
<?php
namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

class Goods extends Model
{
    use Translatable;

    // 翻译模型类
    protected $translationModel = GoodsTranslation::class;

    // 外键名
    protected $translationForeignKey = 'goods_id';

    // 可翻译字段
    protected $translatable = ['title', 'description'];
}
```

### 4.4 API 返回翻译内容

```php
// 控制器中
public function detail($id)
{
    $goods = Goods::find($id);
    return $this->success($goods->toApiArray($this->locale));
}

// 模型中
public function toApiArray(string $locale = 'zh-tw'): array
{
    return [
        'id' => $this->id,
        'title' => $this->getTranslated('title', $locale),
        'description' => $this->getTranslated('description', $locale),
        'price' => $this->price,
        // ...
    ];
}
```

---

## 5. 后端消息翻译规范

### 5.1 语言包文件

**位置**：`bbo-server/app/lang/{locale}/message.php`

```php
<?php
// app/lang/en-us/message.php
return [
    // 通用
    'Success' => 'Success',
    'Operation failed' => 'Operation failed',
    'Parameter error' => 'Parameter error',

    // 用户
    'User not found' => 'User not found',
    'Password incorrect' => 'Password incorrect',

    // 商品
    'Product not found' => 'Product not found',
    'Insufficient stock' => 'Insufficient stock',
];
```

```php
<?php
// app/lang/zh-tw/message.php
return [
    // 通用
    'Success' => '操作成功',
    'Operation failed' => '操作失敗',
    'Parameter error' => '參數錯誤',

    // 用户
    'User not found' => '用戶不存在',
    'Password incorrect' => '密碼錯誤',

    // 商品
    'Product not found' => '商品不存在',
    'Insufficient stock' => '庫存不足',
];
```

### 5.2 控制器使用

```php
// ✅ 正确：使用英文键
return $this->error('User not found');
return $this->error('Password incorrect');

// ❌ 错误：硬编码中文
return $this->error('用戶不存在');
```

Base 控制器会自动调用 `lang()` 函数翻译。

---

## 6. 前端使用规范

### 6.1 基本使用

```vue
<template>
  <!-- 模板中 -->
  <text>{{ t('common.confirm') }}</text>
  <button>{{ t('action.save') }}</button>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// 脚本中
const title = t('page.home')
</script>
```

### 6.2 弹窗必须设置按钮文字

```typescript
// ✅ 正确
uni.showModal({
  title: t('common.confirm'),
  content: t('user.logoutConfirm'),
  confirmText: t('common.confirm'),  // 必须
  cancelText: t('common.cancel'),    // 必须
})

// ❌ 错误：缺少按钮文字
uni.showModal({
  title: t('common.confirm'),
  content: t('user.logoutConfirm'),
})
```

### 6.3 页面标题动态设置

```typescript
import { onShow } from '@dcloudio/uni-app'

onShow(() => {
  uni.setNavigationBarTitle({
    title: t('page.checkout')
  })
})
```

### 6.4 带参数的翻译

```json
// 翻译文件
{
  "order": {
    "itemCount": "共 {count} 件商品",
    "totalPrice": "總計: {currency}{amount}"
  }
}
```

```typescript
// 使用
t('order.itemCount', { count: 5 })
// 输出: "共 5 件商品"

t('order.totalPrice', { currency: '$', amount: '99.00' })
// 输出: "總計: $99.00"
```

---

## 7. 新增语言流程

### 7.1 后端步骤

1. **数据库添加语言**
   ```sql
   INSERT INTO `languages` (`code`, `name`, `native_name`, `flag`, `sort`, `is_active`)
   VALUES ('ko-kr', '韓文', '한국어', '🇰🇷', 4, 1);
   ```

2. **生成 UI 翻译**
   ```bash
   php think translate:generate ko-kr --source=en-us
   ```

3. **生成内容翻译**
   ```bash
   php think translate:content ko-kr --source=en-us
   ```

4. **添加后端语言包**
   - 复制 `app/lang/en-us/message.php` 到 `app/lang/ko-kr/message.php`
   - 翻译所有消息

### 7.2 前端步骤（可选，作为 fallback）

1. **创建语言目录**
   ```
   bbo-app/src/locale/ko-KR/
   ```

2. **复制并翻译 JSON 文件**
   - 从 `en-US/` 复制所有 JSON 文件
   - 翻译内容

3. **更新 locale/index.ts**
   - 添加 import 语句
   - 添加到 fallbackMessages

---

## 8. 导入导出命令

### 8.1 导入 UI 翻译

```bash
# 导入所有语言
php think ui:import

# 导入指定语言
php think ui:import --locale=ko-KR

# 清空后重新导入
php think ui:import --clear
```

### 8.2 导出 UI 翻译

```bash
# 导出所有语言
php think ui:export

# 导出指定语言
php think ui:export --locale=zh-tw
```

### 8.3 生成翻译

```bash
# 为新语言生成 UI 翻译
php think translate:generate ko-kr --source=en-us

# 为新语言生成内容翻译
php think translate:content ko-kr --source=en-us
```

---

## 9. 检查清单

### 9.1 新增 API

- [ ] 错误消息使用英文翻译键
- [ ] 三个语言包同步更新
- [ ] 返回的业务数据使用 `$this->locale` 获取翻译

### 9.2 新增 APP 页面

- [ ] `onShow` 动态设置页面标题
- [ ] 弹窗设置 `confirmText` 和 `cancelText`
- [ ] 所有文字使用 `t('key')` 而非硬编码
- [ ] 翻译键添加到正确的命名空间

### 9.3 新增翻译键

- [ ] 键名符合命名规范
- [ ] 添加到正确的命名空间
- [ ] 三种语言同步添加
- [ ] 导入到数据库 (ui_translations)

### 9.4 新增翻译模型

- [ ] 创建 `xxx_translations` 表
- [ ] 使用 `Translatable` Trait
- [ ] 定义 `$translatable` 字段
- [ ] `toApiArray()` 传入 `$locale`

---

## 10. 常见问题

### Q1: 前端显示英文，但数据库有翻译？

**原因**：缓存问题或格式转换问题

**解决**：
1. 清除 APP 本地缓存
2. 检查 `flattenToNested` 函数是否正确展开命名空间

### Q2: 新语言添加后不显示？

**检查**：
1. `languages` 表是否有该语言且 `is_active=1`
2. `ui_translations` 表是否有该语言的数据
3. 前端 `supportedLocales` 是否包含该语言

### Q3: API 返回英文消息？

**原因**：后端语言包缺少翻译

**解决**：
1. 检查 `app/lang/{locale}/message.php` 是否有对应翻译
2. 检查请求头 `Accept-Language` 是否正确

---

## 版本历史

| 版本 | 日期 | 说明 |
|------|------|------|
| 1.0 | 2026-01-13 | 初始版本 |
