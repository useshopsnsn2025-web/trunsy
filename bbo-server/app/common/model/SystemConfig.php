<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 系统配置模型
 */
class SystemConfig extends Model
{
    use Translatable;

    /**
     * 翻译模型类
     * @var string
     */
    protected $translationModel = SystemConfigTranslation::class;

    /**
     * 翻译外键字段
     * @var string
     */
    protected $translationForeignKey = 'config_id';

    /**
     * 可翻译字段
     * @var array
     */
    protected $translatable = ['name', 'description', 'value'];
    /**
     * 表名
     * @var string
     */
    protected $name = 'system_configs';

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
        'sort' => 'integer',
    ];

    /**
     * 获取配置值（自动转换类型）
     * @return mixed
     */
    public function getValueAttr($value)
    {
        // 使用 getData 获取原始 type 字段值，避免与模型 $type 属性冲突
        $configType = $this->getData('type');
        switch ($configType) {
            case 'number':
                return is_numeric($value) ? (float)$value : 0;
            case 'boolean':
                // 返回字符串 '1' 或 '0'，与前端 switch 组件兼容
                return in_array(strtolower((string)$value), ['true', '1', 'yes']) ? '1' : '0';
            case 'json':
                return json_decode($value, true) ?? [];
            case 'image':
                // 图片类型直接返回URL字符串
                return (string)$value;
            default:
                return $value;
        }
    }

    /**
     * 根据key获取配置值
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getConfig(string $key, $default = null)
    {
        $config = self::where('key', $key)->find();
        return $config ? $config->value : $default;
    }

    /**
     * 根据key获取配置值（支持多语言）
     * @param string $key 配置键名
     * @param string $locale 语言代码
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function getConfigTranslated(string $key, string $locale, $default = null)
    {
        $config = self::where('key', $key)->find();
        if (!$config) {
            return $default;
        }

        // 标准化语言代码（API 使用 en-us/zh-tw/ja-jp，翻译表使用 en/zh-tw/ja-jp）
        $normalizedLocale = self::normalizeLocale($locale);

        // 尝试获取翻译值
        $translatedValue = $config->getTranslated('value', $normalizedLocale);
        if (!empty($translatedValue)) {
            return $translatedValue;
        }

        // 降级到繁体中文
        if ($normalizedLocale !== 'zh-tw') {
            $translatedValue = $config->getTranslated('value', 'zh-tw');
            if (!empty($translatedValue)) {
                return $translatedValue;
            }
        }

        // 降级到英文
        if ($normalizedLocale !== 'en-us') {
            $translatedValue = $config->getTranslated('value', 'en-us');
            if (!empty($translatedValue)) {
                return $translatedValue;
            }
        }

        // 最后使用原始值
        return $config->value ?: $default;
    }

    /**
     * 标准化语言代码
     * 将 API 传入的语言代码转换为翻译表使用的格式
     * @param string $locale
     * @return string
     */
    protected static function normalizeLocale(string $locale): string
    {
        $locale = strtolower($locale);

        // 短格式映射到标准格式
        $localeMap = [
            'en' => 'en-us',
            'ja' => 'ja-jp',
            'zh' => 'zh-tw',
        ];

        return $localeMap[$locale] ?? $locale;
    }

    /**
     * 批量获取配置
     * @param string|null $group
     * @return array
     */
    public static function getConfigs(?string $group = null): array
    {
        $query = self::order('sort', 'desc');
        if ($group) {
            $query->where('group', $group);
        }
        $configs = $query->select();

        $result = [];
        foreach ($configs as $config) {
            // 使用 getData() 获取原始字段值，避免与 Model 的 $key 属性冲突
            $result[$config->getData('key')] = $config->value;
        }
        return $result;
    }

    /**
     * 设置配置值（如果不存在则创建）
     * @param string $key
     * @param mixed $value
     * @param string $group 配置分组（新建时使用）
     * @return bool
     */
    public static function setConfig(string $key, $value, string $group = 'system'): bool
    {
        $newValue = is_array($value) ? json_encode($value) : (string)$value;
        $config = self::where('key', $key)->find();

        if ($config) {
            // 使用 update 直接更新数据库，避免模型属性访问器干扰
            return self::where('key', $key)->update(['value' => $newValue, 'updated_at' => date('Y-m-d H:i:s')]) !== false;
        }

        // 配置不存在，使用 Db 直接插入（避免模型字段映射问题）
        $now = date('Y-m-d H:i:s');
        return \think\facade\Db::table('system_configs')->insert([
            'group' => $group,
            'key' => $key,
            'value' => $newValue,
            'type' => 'string',
            'name' => $key,
            'description' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]) > 0;
    }
}
