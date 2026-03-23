<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 支付方式模型
 */
class PaymentMethod extends Model
{
    use Translatable;

    protected $name = 'payment_methods';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 翻译模型
     */
    protected $translationModel = PaymentMethodTranslation::class;

    /**
     * 可翻译字段
     */
    protected $translatable = ['name', 'description', 'tag', 'link_text'];

    protected $type = [
        'id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    public static function getStatusNames(): array
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
    }

    /**
     * 获取启用的支付方式列表
     */
    public static function getEnabledList(string $locale = 'en-us'): array
    {
        $list = self::where('status', self::STATUS_ENABLED)
            ->order('sort', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return self::appendTranslations($list, $locale);
    }
}
