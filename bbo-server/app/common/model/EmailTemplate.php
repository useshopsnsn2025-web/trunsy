<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Cache;

/**
 * 邮件模板模型
 */
class EmailTemplate extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'email_templates';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'updated_at';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'is_active' => 'boolean',
        'variables' => 'json',
        'images' => 'json',
    ];

    /**
     * 默认图片配置
     */
    const DEFAULT_IMAGES = [
        'header_image' => '',       // Header 背景图
        'logo' => '',               // LOGO（空则使用系统 LOGO）
        'banner' => '',             // Banner 图
        'footer_image' => '',       // Footer 图片
        'category_image_1' => '',   // 分类图片1
        'category_image_2' => '',   // 分类图片2
        'category_image_3' => '',   // 分类图片3
        'category_image_4' => '',   // 分类图片4
        'protection_image' => '',   // 保护区域图片
        'live_image' => '',         // 实时发现图片
        'app_qr_code' => '',        // APP下载二维码
        'sell_image' => '',         // 销售区域图片
    ];

    /**
     * 缓存前缀
     */
    const CACHE_PREFIX = 'email_template:';

    /**
     * 缓存时间（秒）
     */
    const CACHE_TTL = 3600;

    /**
     * 关联翻译
     */
    public function translations()
    {
        return $this->hasMany(EmailTemplateTranslation::class, 'template_id');
    }

    /**
     * 根据类型和语言获取模板
     * @param string $type 模板类型
     * @param string $locale 语言代码
     * @return array|null ['subject' => '', 'content' => '']
     */
    public static function getTemplate(string $type, string $locale = 'en-us'): ?array
    {
        $locale = strtolower($locale);
        $cacheKey = self::CACHE_PREFIX . $type . ':' . $locale;

        // 尝试从缓存获取
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached ?: null;
        }

        // 查询模板
        $template = self::where('type', $type)
            ->where('is_active', 1)
            ->find();

        if (!$template) {
            Cache::set($cacheKey, false, self::CACHE_TTL);
            return null;
        }

        // 查找对应语言的翻译
        $translation = EmailTemplateTranslation::where('template_id', $template->id)
            ->where('locale', $locale)
            ->find();

        // 如果没有找到对应语言，尝试降级
        if (!$translation) {
            // 降级顺序：请求语言 -> en-us -> 默认值
            if ($locale !== 'en-us') {
                $translation = EmailTemplateTranslation::where('template_id', $template->id)
                    ->where('locale', 'en-us')
                    ->find();
            }
        }

        // 合并图片配置（模板配置覆盖默认配置）
        $images = array_merge(self::DEFAULT_IMAGES, $template->images ?? []);

        $result = [
            'subject' => $translation ? $translation->subject : $template->subject,
            'content' => $translation ? $translation->content : $template->content,
            'variables' => $template->variables ?? [],
            'images' => $images,
        ];

        Cache::set($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * 获取模板图片配置
     * @param string $type 模板类型
     * @return array
     */
    public static function getTemplateImages(string $type): array
    {
        $template = self::where('type', $type)->find();
        if (!$template) {
            return self::DEFAULT_IMAGES;
        }
        return array_merge(self::DEFAULT_IMAGES, $template->images ?? []);
    }

    /**
     * 更新模板图片配置
     * @param string $type 模板类型
     * @param array $images 图片配置
     * @return bool
     */
    public static function updateTemplateImages(string $type, array $images): bool
    {
        $template = self::where('type', $type)->find();
        if (!$template) {
            return false;
        }

        // 只更新有效的图片键
        $validKeys = array_keys(self::DEFAULT_IMAGES);
        $newImages = $template->images ?? [];
        foreach ($images as $key => $value) {
            if (in_array($key, $validKeys)) {
                $newImages[$key] = $value;
            }
        }

        $template->images = $newImages;
        $result = $template->save();

        // 清除缓存
        if ($result) {
            self::clearCache($type);
        }

        return $result !== false;
    }

    /**
     * 渲染模板
     * @param string $type 模板类型
     * @param string $locale 语言代码
     * @param array $variables 变量
     * @return array|null ['subject' => '', 'content' => '']
     */
    public static function render(string $type, string $locale, array $variables = []): ?array
    {
        $template = self::getTemplate($type, $locale);
        if (!$template) {
            return null;
        }

        // 添加系统级变量（如果未提供）
        $systemVariables = self::getSystemVariables();
        foreach ($systemVariables as $key => $value) {
            if (!isset($variables[$key])) {
                $variables[$key] = $value;
            }
        }

        // 合并模板图片变量到替换变量中
        // 图片变量使用 template_ 前缀，如 template_logo, template_banner 等
        if (!empty($template['images'])) {
            foreach ($template['images'] as $key => $value) {
                $variables['template_' . $key] = $value;
                // 同时支持不带前缀的变量名（兼容旧模板）
                if (!isset($variables[$key])) {
                    $variables[$key] = $value;
                }
            }
        }

        $subject = self::replaceVariables($template['subject'], $variables);
        $content = self::replaceVariables($template['content'], $variables);

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }

    /**
     * 获取系统级变量（用于所有邮件模板）
     * @return array
     */
    protected static function getSystemVariables(): array
    {
        // 从系统配置获取平台信息
        $platformName = SystemConfig::getConfig('platform_name', 'TURNSY Marketplace');
        $platformUrl = SystemConfig::getConfig('platform_url', 'https://www.trunsysg.com');
        $platformLogo = SystemConfig::getConfig('platform_logo', '');

        return [
            'platform_name' => $platformName,
            'platform_url' => $platformUrl,
            'platform_logo' => $platformLogo,
            'current_year' => date('Y'),
            'current_date' => date('Y-m-d'),
        ];
    }

    /**
     * 替换模板变量
     * @param string $template
     * @param array $variables
     * @return string
     */
    protected static function replaceVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // 跳过非标量值（如数组、对象）
            if (!is_scalar($value)) {
                continue;
            }
            // 将值转换为字符串，支持 {{$variable}} 和 {variable} 两种格式
            $stringValue = (string)$value;
            $template = str_replace(['{{\$' . $key . '}}', '{{$' . $key . '}}', '{' . $key . '}'], $stringValue, $template);
        }
        return $template;
    }

    /**
     * 清除模板缓存
     * @param string|null $type 指定类型，为空清除所有
     */
    public static function clearCache(?string $type = null): void
    {
        if ($type) {
            // 清除指定类型的所有语言缓存
            $locales = ['en-us', 'zh-tw', 'ja-jp'];
            foreach ($locales as $locale) {
                Cache::delete(self::CACHE_PREFIX . $type . ':' . $locale);
            }
        } else {
            // 清除所有模板缓存
            // 注意：这里需要根据实际缓存驱动实现
            // 如果使用 Redis，可以使用 keys 命令删除
        }
    }

    /**
     * 获取所有可用模板类型
     * @return array
     */
    public static function getAvailableTypes(): array
    {
        return [
            'order_created' => '订单创建',
            'payment_success' => '支付成功',
            'order_shipped' => '订单发货',
            'order_delivered' => '订单送达',
            'order_cancelled' => '订单取消',
            'refund_success' => '退款成功',
            'order_verified' => '订单验证成功（通用）',
            'order_verified_full' => '全款支付验证成功',
            'order_verified_cod' => '货到付款验证成功',
            'order_verified_installment' => '分期付款验证成功',
            'order_verify_failed' => '订单验证失败',
            'goods_sold' => '商品售出通知（卖家）',
        ];
    }
}
