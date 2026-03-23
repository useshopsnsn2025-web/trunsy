<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 宝箱模型
 */
class TreasureBox extends Model
{
    use Translatable;

    protected $table = 'treasure_boxes';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 翻译配置
    protected $translationModel = TreasureBoxTranslation::class;
    protected $translationForeignKey = 'box_id';
    protected $translatable = ['name', 'description'];

    // 宝箱代码
    const CODE_SILVER = 'silver_box';
    const CODE_GOLD = 'gold_box';
    const CODE_DIAMOND = 'diamond_box';

    /**
     * 通过代码获取宝箱
     */
    public static function getByCode(string $code): ?self
    {
        return self::where('code', $code)
            ->where('status', 1)
            ->find();
    }

    /**
     * 获取所有启用的宝箱
     */
    public static function getActiveBoxes(): array
    {
        return self::where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 执行宝箱抽奖
     */
    public function lottery(): ?TreasureBoxPrize
    {
        // 获取所有启用的奖品
        $prizes = TreasureBoxPrize::where('box_id', $this->id)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();

        if ($prizes->isEmpty()) {
            return null;
        }

        // 构建概率区间
        $ranges = [];
        $cumulative = 0;

        foreach ($prizes as $prize) {
            $cumulative += $prize->probability;
            $ranges[] = [
                'prize' => $prize,
                'threshold' => $cumulative,
            ];
        }

        // 生成随机数
        $random = mt_rand(1, 10000) / 10000;

        // 确定中奖奖品
        foreach ($ranges as $range) {
            if ($random <= $range['threshold']) {
                return $range['prize'];
            }
        }

        // 如果没有命中，返回第一个奖品
        return $ranges[0]['prize'] ?? null;
    }

    /**
     * 转换为 API 数组
     */
    public function toApiArray(string $locale = 'en-us'): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'icon' => $this->icon,
            'bg_color' => $this->bg_color,
            'name' => $this->getTranslated('name', $locale),
            'description' => $this->getTranslated('description', $locale),
        ];
    }
}
