<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * 国家/地区配置模型
 */
class Country extends Model
{
    protected $table = 'countries';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 允许写入的字段
    protected $field = [
        'id',
        'code',
        'name',
        'currency_code',
        'flag',
        'locale',
        'sort',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $type = [
        'sort' => 'integer',
        'is_active' => 'integer',
    ];

    /**
     * 关联货币
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * 关联翻译
     */
    public function translations()
    {
        return $this->hasMany(CountryTranslation::class, 'country_id');
    }

    /**
     * 获取所有启用的国家（带翻译）
     */
    public static function getActiveCountries(string $locale = 'en-us'): array
    {
        $countries = self::where('is_active', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        if (empty($countries)) {
            return [];
        }

        // 获取翻译
        $countryIds = array_column($countries, 'id');
        $translations = CountryTranslation::getTranslationsByCountryIds($countryIds, $locale);

        // 合并翻译
        foreach ($countries as &$country) {
            $country['translated_name'] = $translations[$country['id']] ?? $country['name'];
        }

        return $countries;
    }

    /**
     * 获取所有启用的国家代码
     */
    public static function getActiveCodes(): array
    {
        return self::where('is_active', 1)
            ->order('sort', 'asc')
            ->column('code');
    }

    /**
     * 获取国家代码到货币代码的映射
     */
    public static function getCurrencyMap(): array
    {
        return self::where('is_active', 1)
            ->column('currency_code', 'code');
    }

    /**
     * 检查国家代码是否已存在
     */
    public static function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = self::where('code', strtoupper($code));
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->count() > 0;
    }

    /**
     * 获取国家详情（带所有翻译）
     */
    public function getDetailWithTranslations(): array
    {
        $data = $this->toApiArray();

        // 获取所有翻译
        $translations = CountryTranslation::where('country_id', $this->id)
            ->select()
            ->toArray();

        $data['translations'] = [];
        foreach ($translations as $trans) {
            $data['translations'][$trans['locale']] = $trans['name'];
        }

        return $data;
    }

    /**
     * 转换为 API 数组
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'currency_code' => $this->currency_code,
            'flag' => $this->flag,
            'locale' => $this->locale,
            'sort' => $this->sort,
            'is_active' => (bool)$this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * 保存翻译
     */
    public function saveTranslations(array $translations): void
    {
        foreach ($translations as $locale => $name) {
            if (!empty($name)) {
                CountryTranslation::saveTranslation($this->id, $locale, $name);
            }
        }
    }

    /**
     * 获取国家列表（用于 APP 端）
     * 返回格式化的数据，包含翻译名称和货币信息
     */
    public static function getListForApp(string $locale = 'en-us'): array
    {
        $countries = self::getActiveCountries($locale);

        // 获取货币信息
        $currencyCodes = array_unique(array_column($countries, 'currency_code'));
        $currencies = Currency::whereIn('code', $currencyCodes)
            ->where('is_active', 1)
            ->column('symbol,decimals', 'code');

        $result = [];
        foreach ($countries as $country) {
            $currencyInfo = $currencies[$country['currency_code']] ?? ['symbol' => '$', 'decimals' => 2];
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
