<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 用户银行卡模型
 */
class UserCard extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'user_cards';

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
        'expiry_month' => 'integer',
        'expiry_year' => 'integer',
        'billing_address_id' => 'integer',
        'is_default' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 卡片状态常量
     */
    const STATUS_DELETED = 0;    // 已删除
    const STATUS_ACTIVE = 1;     // 正常
    const STATUS_DISABLED = 2;   // 已停用
    const STATUS_REJECTED = 3;   // 已被拒
    const STATUS_PENDING = 4;    // 审核中

    /**
     * 状态映射（数字 => 字符串）
     */
    const STATUS_MAP = [
        self::STATUS_DELETED => 'deleted',
        self::STATUS_ACTIVE => 'active',
        self::STATUS_DISABLED => 'disabled',
        self::STATUS_REJECTED => 'rejected',
        self::STATUS_PENDING => 'pending',
    ];

    /**
     * 卡类型映射
     */
    const CARD_TYPES = [
        '4' => ['type' => 'visa', 'brand' => 'Visa'],
        '5' => ['type' => 'mastercard', 'brand' => 'Mastercard'],
        '34' => ['type' => 'amex', 'brand' => 'American Express'],
        '37' => ['type' => 'amex', 'brand' => 'American Express'],
        '6' => ['type' => 'discover', 'brand' => 'Discover'],
        '62' => ['type' => 'unionpay', 'brand' => 'UnionPay'],
        '81' => ['type' => 'unionpay', 'brand' => 'UnionPay'],
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
     * 关联账单地址
     * @return \think\model\relation\BelongsTo
     */
    public function billingAddress()
    {
        return $this->belongsTo(UserAddress::class, 'billing_address_id', 'id');
    }

    /**
     * 根据卡号识别卡类型
     * @param string $cardNumber
     * @return array
     */
    public static function detectCardType(string $cardNumber): array
    {
        $cardNumber = preg_replace('/\s+/', '', $cardNumber);

        // 检查前两位
        $prefix2 = substr($cardNumber, 0, 2);
        if (isset(self::CARD_TYPES[$prefix2])) {
            return self::CARD_TYPES[$prefix2];
        }

        // 检查前一位
        $prefix1 = substr($cardNumber, 0, 1);
        if (isset(self::CARD_TYPES[$prefix1])) {
            return self::CARD_TYPES[$prefix1];
        }

        return ['type' => 'unknown', 'brand' => 'Card'];
    }

    /**
     * 转换为数组（API输出格式）
     * @return array
     */
    public function toApiArray(): array
    {
        $data = [
            'id' => $this->id,
            'cardType' => $this->card_type,
            'cardBrand' => $this->card_brand,
            'lastFour' => $this->last_four,
            'expiryMonth' => $this->expiry_month,
            'expiryYear' => $this->expiry_year,
            'expiry' => sprintf('%02d/%02d', $this->expiry_month, $this->expiry_year % 100),
            'cardholderName' => $this->cardholder_name,
            'billingAddressId' => $this->billing_address_id,
            'isDefault' => $this->is_default === 1,
            'status' => $this->getCardStatus(),
            'statusReason' => $this->status_reason ?? null,
        ];

        // 加载账单地址
        if ($this->billing_address_id) {
            $address = $this->billingAddress;
            if ($address) {
                $data['billingAddress'] = [
                    'id' => $address->id,
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'country' => $address->country,
                    'countryCode' => $address->country_code ?? null,
                    'state' => $address->state ?? null,
                    'city' => $address->city,
                    'street' => $address->street,
                    'postalCode' => $address->postal_code ?? null,
                    'fullAddress' => $this->formatFullAddress($address),
                ];
            }
        }

        return $data;
    }

    /**
     * 格式化完整地址
     * @param UserAddress $address
     * @return string
     */
    private function formatFullAddress($address): string
    {
        $parts = [];
        if (!empty($address->street)) {
            $parts[] = $address->street;
        }
        if (!empty($address->city)) {
            $parts[] = $address->city;
        }
        if (!empty($address->state)) {
            $parts[] = $address->state;
        }
        if (!empty($address->country)) {
            $parts[] = $address->country;
        }
        if (!empty($address->postal_code)) {
            $parts[] = $address->postal_code;
        }
        return implode(', ', $parts);
    }

    /**
     * 获取卡片状态（自动检测过期）
     * @return string
     */
    public function getCardStatus(): string
    {
        // 如果数据库状态不是 active，直接返回对应状态
        if ($this->status !== self::STATUS_ACTIVE) {
            return self::STATUS_MAP[$this->status] ?? 'disabled';
        }

        // 检查是否过期
        if ($this->isExpired()) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * 检查卡片是否过期
     * @return bool
     */
    public function isExpired(): bool
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        if ($this->expiry_year < $currentYear) {
            return true;
        }

        if ($this->expiry_year === $currentYear && $this->expiry_month < $currentMonth) {
            return true;
        }

        return false;
    }

    /**
     * 获取掩码卡号显示
     * @return string
     */
    public function getMaskedNumberAttr(): string
    {
        return '•••• •••• •••• ' . $this->last_four;
    }
}
