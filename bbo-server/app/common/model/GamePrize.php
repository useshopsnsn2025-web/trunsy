<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 游戏奖品模型
 */
class GamePrize extends Model
{
    use Translatable;

    protected $table = 'game_prizes';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 可翻译字段
    protected $translationModel = GamePrizeTranslation::class;
    protected $translationForeignKey = 'prize_id';
    protected $translatable = ['name', 'description'];

    // 奖品类型常量
    const TYPE_POINTS = 'points';     // 积分
    const TYPE_COUPON = 'coupon';     // 优惠券
    const TYPE_CASH = 'cash';         // 现金券
    const TYPE_GOODS = 'goods';       // 实物商品
    const TYPE_CHANCE = 'chance';     // 游戏次数

    /**
     * 关联游戏
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * 获取游戏的所有奖品
     */
    public static function getByGameId(int $gameId): array
    {
        return self::where('game_id', $gameId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 检查奖品是否可用（库存、每日限量等）
     */
    public function isAvailable(): bool
    {
        if ($this->status != 1) {
            return false;
        }

        // 检查总库存
        if ($this->stock != -1 && $this->stock <= 0) {
            return false;
        }

        // 检查每日限量
        if ($this->daily_limit != -1) {
            $todayIssued = DailyPrizeStats::getTodayIssued($this->id);
            if ($todayIssued >= $this->daily_limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查用户今日是否超过限制
     */
    public function checkUserDailyLimit(int $userId): bool
    {
        if ($this->user_daily_limit == -1) {
            return true;
        }

        $today = date('Y-m-d');
        $count = UserGameLog::where('user_id', $userId)
            ->where('prize_id', $this->id)
            ->whereDay('created_at', $today)
            ->count();

        return $count < $this->user_daily_limit;
    }

    /**
     * 扣减库存
     */
    public function deductStock(): bool
    {
        if ($this->stock == -1) {
            return true; // 无限库存
        }

        if ($this->stock <= 0) {
            return false;
        }

        $this->stock = $this->stock - 1;
        return $this->save();
    }

    /**
     * 转换为 API 数组
     * 注意：使用 getData() 获取 'type' 字段，避免与 Model::$type 属性冲突
     */
    public function toApiArray(string $locale = 'en-us'): array
    {
        return [
            'id' => $this->id,
            'type' => $this->getData('type'),
            'value' => (float)$this->value,
            'name' => $this->getTranslated('name', $locale),
            'description' => $this->getTranslatedSafe('description', $locale),
            'image' => $this->image,
            'color' => $this->color,
            'sort' => (int)$this->sort,
        ];
    }

    /**
     * 安全获取翻译字段（仅从翻译表获取，不回退到主表）
     * 用于主表没有对应字段的情况（如 description 只存在于翻译表）
     */
    protected function getTranslatedSafe(string $key, ?string $locale = null): ?string
    {
        $locale = $locale ?? 'en-us';

        // 尝试获取请求语言的翻译
        $translation = $this->translation($locale);
        if ($translation) {
            try {
                $value = $translation->getData($key);
                if (!empty($value)) {
                    return $value;
                }
            } catch (\think\exception\InvalidArgumentException $e) {
                // 字段不存在，继续降级
            }
        }

        // 降级到 en-us
        if ($locale !== 'en-us') {
            $translation = $this->translation('en-us');
            if ($translation) {
                try {
                    $value = $translation->getData($key);
                    if (!empty($value)) {
                        return $value;
                    }
                } catch (\think\exception\InvalidArgumentException $e) {
                    // 字段不存在
                }
            }
        }

        // 返回 null 而不是尝试从主表获取
        return null;
    }

    /**
     * 转换为管理端数组
     * 注意：使用 getData() 获取 'type' 字段，避免与 Model::$type 属性冲突
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'game_id' => (int)$this->game_id,
            'type' => $this->getData('type'),
            'value' => (float)$this->value,
            'coupon_id' => $this->coupon_id,
            'goods_id' => $this->goods_id,
            'probability' => (float)$this->probability * 100, // 转为百分比显示
            'stock' => (int)$this->stock,
            'daily_limit' => (int)$this->daily_limit,
            'user_daily_limit' => (int)$this->user_daily_limit,
            'image' => $this->image,
            'color' => $this->color,
            'sort' => (int)$this->sort,
            'status' => (int)$this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * 获取奖品类型标签
     */
    public static function getTypeLabel(string $type): string
    {
        $labels = [
            self::TYPE_POINTS => 'Points',
            self::TYPE_COUPON => 'Coupon',
            self::TYPE_CASH => 'Cash Voucher',
            self::TYPE_GOODS => 'Product',
            self::TYPE_CHANCE => 'Game Chance',
        ];
        return $labels[$type] ?? $type;
    }
}
