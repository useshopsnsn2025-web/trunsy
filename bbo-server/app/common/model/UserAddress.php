<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 用户收货地址模型
 */
class UserAddress extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'user_addresses';

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
        'user_id' => 'integer',
        'is_default' => 'integer',
    ];

    /**
     * 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 获取完整地址
     * @return string
     */
    public function getFullAddressAttr(): string
    {
        $parts = array_filter([
            $this->street,
            $this->district,
            $this->city,
            $this->state,
            $this->country,
            $this->postal_code
        ]);

        return implode(', ', $parts);
    }

    /**
     * 转换为数组（API输出格式）
     * @return array
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getData('name'), // 使用 getData 避免与模型 $name 属性冲突
            'phone' => $this->phone,
            'country' => $this->country,
            'countryCode' => $this->country_code,
            'state' => $this->state,
            'city' => $this->city,
            'district' => $this->district,
            'street' => $this->street,
            'postalCode' => $this->postal_code,
            'isDefault' => $this->is_default === 1,
            'fullAddress' => $this->full_address,
        ];
    }
}
