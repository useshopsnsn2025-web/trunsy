<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 客服人员模型
 */
class CustomerService extends Model
{
    protected $name = 'customer_services';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'admin_id' => 'integer',
        'max_sessions' => 'integer',
        'current_sessions' => 'integer',
        'status' => 'integer',
        'is_enabled' => 'integer',
    ];

    // 状态常量
    const STATUS_OFFLINE = 0;
    const STATUS_ONLINE = 1;
    const STATUS_BUSY = 2;

    const STATUS_MAP = [
        self::STATUS_OFFLINE => '离线',
        self::STATUS_ONLINE => '在线',
        self::STATUS_BUSY => '忙碌',
    ];

    /**
     * 关联管理员
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    /**
     * 关联工作组
     */
    public function groups()
    {
        return $this->belongsToMany(ServiceGroup::class, 'service_group_members', 'group_id', 'service_id');
    }

    /**
     * 关联翻译
     */
    public function translations()
    {
        return $this->hasMany(CustomerServiceTranslation::class, 'service_id', 'id');
    }

    /**
     * 获取指定语言的翻译
     */
    public function translation(string $locale = 'zh-tw')
    {
        return $this->hasOne(CustomerServiceTranslation::class, 'service_id', 'id')
            ->where('locale', $locale);
    }

    /**
     * 获取本地化名称
     */
    public function getLocalizedName(string $locale = 'zh-tw'): string
    {
        $translation = $this->translations()->where('locale', $locale)->find();
        if ($translation) {
            return $translation->name;
        }
        // 降级到繁体中文
        $translation = $this->translations()->where('locale', 'zh-tw')->find();
        if ($translation) {
            return $translation->name;
        }
        // 再降级到简体中文（兼容旧数据）
        $translation = $this->translations()->where('locale', 'zh-cn')->find();
        return $translation ? $translation->name : ($this->name ?? '');
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::STATUS_MAP[$data['status']] ?? '未知';
    }

    /**
     * 获取随机在线客服（用于平台商品的虚拟卖家）
     * @param string $locale 语言
     * @return array|null
     */
    public static function getRandomOnlineService(string $locale = 'en-us'): ?array
    {
        // 优先获取在线客服
        $service = self::where('is_enabled', 1)
            ->where('status', self::STATUS_ONLINE)
            ->orderRaw('RAND()')
            ->find();

        // 如果没有在线客服，获取任意启用的客服
        if (!$service) {
            $service = self::where('is_enabled', 1)
                ->orderRaw('RAND()')
                ->find();
        }

        if (!$service) {
            return null;
        }

        // 转换locale格式以匹配数据库存储格式
        $dbLocale = self::normalizeLocale($locale);

        // 检查是否有精确匹配当前语言的翻译
        $translation = $service->translations()->where('locale', $dbLocale)->find();
        if ($translation && $translation->name) {
            $name = $translation->name;
        } else {
            // 使用多语言默认客服名称
            $name = self::getDefaultServiceName($locale);
        }

        return [
            'id' => $service->id,
            'name' => $name,
            'avatar' => $service->avatar,
            'status' => $service->status,
            'isOnline' => $service->status === self::STATUS_ONLINE,
        ];
    }

    /**
     * 获取多语言默认客服名称
     * @param string $locale 语言
     * @return string
     */
    public static function getDefaultServiceName(string $locale = 'en-us'): string
    {
        $names = [
            'zh-tw' => '線上客服',
            'ja-jp' => 'カスタマーサービス',
            'en-us' => 'Customer Service',
            'en' => 'Customer Service',
        ];

        $locale = strtolower($locale);
        return $names[$locale] ?? $names['en-us'];
    }

    /**
     * 将API locale转换为数据库存储的locale格式
     * @param string $locale API传入的locale (如 en-us, zh-tw, ja-jp)
     * @return string 数据库存储的locale (如 en, zh-tw, ja-jp)
     */
    public static function normalizeLocale(string $locale): string
    {
        $locale = strtolower($locale);
        // en-us, en-gb 等都映射到 en
        if (strpos($locale, 'en') === 0) {
            return 'en';
        }
        return $locale;
    }

    /**
     * 获取所有启用的客服列表
     * @param string $locale 语言
     * @return array
     */
    public static function getEnabledList(string $locale = 'en-us'): array
    {
        $list = self::where('is_enabled', 1)
            ->order('status', 'desc') // 在线的排前面
            ->select();

        // 转换locale格式以匹配数据库存储格式
        $dbLocale = self::normalizeLocale($locale);

        $result = [];
        foreach ($list as $service) {
            // 检查是否有精确匹配当前语言的翻译
            $translation = $service->translations()->where('locale', $dbLocale)->find();
            if ($translation && $translation->name) {
                $name = $translation->name;
            } else {
                // 使用多语言默认客服名称
                $name = self::getDefaultServiceName($locale);
            }

            $result[] = [
                'id' => $service->id,
                'name' => $name,
                'avatar' => $service->avatar,
                'status' => $service->status,
                'isOnline' => $service->status === self::STATUS_ONLINE,
            ];
        }

        return $result;
    }
}
