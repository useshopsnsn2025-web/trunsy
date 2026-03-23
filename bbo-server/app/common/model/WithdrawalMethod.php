<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 提现方式模型
 */
class WithdrawalMethod extends Model
{
    protected $table = 'withdrawal_methods';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $field = [
        'id',
        'code',
        'logo',
        'route_path',
        'sort',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $type = [
        'sort' => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    /**
     * 关联翻译
     */
    public function translations()
    {
        return $this->hasMany(WithdrawalMethodTranslation::class, 'method_id');
    }

    /**
     * 关联国家（通过中间表）
     */
    public function countries()
    {
        return $this->belongsToMany(
            Country::class,
            WithdrawalMethodCountry::class,
            'country_id',
            'method_id'
        );
    }

    /**
     * 获取所有启用的提现方式（带翻译）
     */
    public static function getActiveMethods(string $locale = 'en-us'): array
    {
        $methods = self::where('status', self::STATUS_ENABLED)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        if (empty($methods)) {
            return [];
        }

        // 获取翻译
        $methodIds = array_column($methods, 'id');
        $translations = WithdrawalMethodTranslation::getTranslationsByMethodIds($methodIds, $locale);

        // 获取国家关联
        $countryRelations = WithdrawalMethodCountry::whereIn('method_id', $methodIds)
            ->select()
            ->toArray();

        $methodCountries = [];
        foreach ($countryRelations as $rel) {
            $methodCountries[$rel['method_id']][] = $rel['country_id'];
        }

        // 合并数据
        foreach ($methods as &$method) {
            $method['name'] = $translations[$method['id']] ?? $method['code'];
            $method['country_ids'] = $methodCountries[$method['id']] ?? [];
        }

        return $methods;
    }

    /**
     * 根据国家获取可用的提现方式
     */
    public static function getMethodsByCountry(int $countryId, string $locale = 'en-us'): array
    {
        // 查询该国家支持的提现方式ID
        $methodIds = WithdrawalMethodCountry::where('country_id', $countryId)
            ->column('method_id');

        if (empty($methodIds)) {
            return [];
        }

        $methods = self::whereIn('id', $methodIds)
            ->where('status', self::STATUS_ENABLED)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        if (empty($methods)) {
            return [];
        }

        // 获取翻译
        $translations = WithdrawalMethodTranslation::getTranslationsByMethodIds($methodIds, $locale);

        $result = [];
        foreach ($methods as $method) {
            $result[] = [
                'id' => $method['id'],
                'code' => $method['code'],
                'name' => $translations[$method['id']] ?? $method['code'],
                'logo' => $method['logo'],
                'routePath' => $method['route_path'],
            ];
        }

        return $result;
    }

    /**
     * 检查 code 是否已存在
     */
    public static function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = self::where('code', $code);
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->count() > 0;
    }

    /**
     * 获取详情（带所有翻译和国家）
     */
    public function getDetailWithTranslations(): array
    {
        $data = $this->toApiArray();

        // 获取所有翻译
        $translations = WithdrawalMethodTranslation::where('method_id', $this->id)
            ->select()
            ->toArray();

        $data['translations'] = [];
        foreach ($translations as $trans) {
            $data['translations'][$trans['locale']] = $trans['name'];
        }

        // 获取关联的国家ID
        $data['country_ids'] = WithdrawalMethodCountry::where('method_id', $this->id)
            ->column('country_id');

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
            'logo' => $this->logo,
            'route_path' => $this->route_path,
            'sort' => $this->sort,
            'status' => (int)$this->status,
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
                WithdrawalMethodTranslation::saveTranslation($this->id, $locale, $name);
            }
        }
    }

    /**
     * 同步国家关联
     */
    public function syncCountries(array $countryIds): void
    {
        // 删除旧的关联
        WithdrawalMethodCountry::where('method_id', $this->id)->delete();

        // 添加新的关联
        if (!empty($countryIds)) {
            $data = [];
            foreach ($countryIds as $countryId) {
                $data[] = [
                    'method_id' => $this->id,
                    'country_id' => $countryId,
                ];
            }
            (new WithdrawalMethodCountry())->saveAll($data);
        }
    }
}
