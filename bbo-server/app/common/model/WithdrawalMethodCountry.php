<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 提现方式与国家关联模型
 */
class WithdrawalMethodCountry extends Model
{
    protected $table = 'withdrawal_method_countries';
    protected $pk = 'id';

    protected $autoWriteTimestamp = false;

    protected $field = [
        'id',
        'method_id',
        'country_id',
        'created_at',
    ];

    /**
     * 关联提现方式
     */
    public function method()
    {
        return $this->belongsTo(WithdrawalMethod::class, 'method_id');
    }

    /**
     * 关联国家
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * 根据提现方式ID获取国家ID列表
     */
    public static function getCountryIdsByMethodId(int $methodId): array
    {
        return self::where('method_id', $methodId)->column('country_id');
    }

    /**
     * 根据国家ID获取提现方式ID列表
     */
    public static function getMethodIdsByCountryId(int $countryId): array
    {
        return self::where('country_id', $countryId)->column('method_id');
    }

    /**
     * 删除提现方式的所有国家关联
     */
    public static function deleteByMethodId(int $methodId): void
    {
        self::where('method_id', $methodId)->delete();
    }
}
