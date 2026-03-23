<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 宝箱奖品模型
 */
class TreasureBoxPrize extends Model
{
    use Translatable;

    protected $table = 'treasure_box_prizes';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 翻译配置
    protected $translationModel = TreasureBoxPrizeTranslation::class;
    protected $translationForeignKey = 'prize_id';
    protected $translatable = ['name', 'description'];

    // 奖品类型
    const TYPE_POINTS = 'points';
    const TYPE_COUPON = 'coupon';
    const TYPE_CASH = 'cash';
    const TYPE_CHANCE = 'chance';

    /**
     * 获取宝箱的奖品列表
     */
    public static function getBoxPrizes(int $boxId): array
    {
        return self::where('box_id', $boxId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 转换为 API 数组
     */
    public function toApiArray(string $locale = 'en-us'): array
    {
        return [
            'id' => $this->id,
            'box_id' => $this->box_id,
            'type' => $this->getData('type'),
            'value' => (float)$this->value,
            'probability' => (float)$this->probability,
            'image' => $this->image,
            'color' => $this->color,
            'name' => $this->getTranslated('name', $locale),
            'description' => $this->getTranslated('description', $locale),
        ];
    }
}
