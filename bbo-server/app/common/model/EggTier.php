<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 蛋分级模型
 */
class EggTier extends Model
{
    use Translatable;

    protected $table = 'egg_tiers';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 翻译配置
    protected $translationModel = EggTierTranslation::class;
    protected $translationForeignKey = 'egg_id';
    protected $translatable = ['name', 'description'];

    // 蛋代码
    const CODE_BRONZE = 'bronze_egg';
    const CODE_SILVER = 'silver_egg';
    const CODE_GOLD = 'gold_egg';
    const CODE_DIAMOND = 'diamond_egg';

    /**
     * 通过代码获取蛋
     */
    public static function getByCode(string $code): ?self
    {
        return self::where('code', $code)
            ->where('status', 1)
            ->find();
    }

    /**
     * 根据订单金额获取蛋类型
     */
    public static function getEggByOrderAmount(float $amount): ?self
    {
        // 获取满足条件的最高级蛋
        return self::where('status', 1)
            ->where('min_order_amount', '<=', $amount)
            ->order('min_order_amount', 'desc')
            ->find();
    }

    /**
     * 获取所有启用的蛋类型
     */
    public static function getActiveTiers(): array
    {
        return self::where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 执行蛋抽奖
     */
    public function lottery(): ?EggTierPrize
    {
        // 获取所有启用的奖品
        $prizes = EggTierPrize::where('egg_id', $this->id)
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
            'min_order_amount' => (float)$this->min_order_amount,
            'name' => $this->getTranslated('name', $locale),
            'description' => $this->getTranslated('description', $locale),
        ];
    }
}
