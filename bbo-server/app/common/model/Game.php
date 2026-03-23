<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\traits\Translatable;

/**
 * 游戏配置模型
 */
class Game extends Model
{
    use Translatable;

    protected $table = 'games';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $json = ['config'];
    protected $jsonAssoc = true;

    // 可翻译字段
    protected $translationModel = GameTranslation::class;
    protected $translationForeignKey = 'game_id';
    protected $translatable = ['name', 'description', 'rules'];

    // 游戏类型常量
    const TYPE_LOTTERY = 'lottery';
    const TYPE_TASK = 'task';
    const TYPE_CHECKIN = 'checkin';
    const TYPE_SHARE = 'share';

    // 游戏代码常量
    const CODE_WHEEL = 'wheel';
    const CODE_EGG = 'egg';
    const CODE_SCRATCH = 'scratch';
    const CODE_CHECKIN = 'checkin';

    /**
     * 关联奖品
     */
    public function prizes()
    {
        return $this->hasMany(GamePrize::class, 'game_id');
    }

    /**
     * 关联翻译
     */
    public function translations()
    {
        return $this->hasMany(GameTranslation::class, 'game_id');
    }

    /**
     * 获取启用的游戏列表
     */
    public static function getActiveGames(): array
    {
        return self::where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 根据代码获取游戏
     */
    public static function getByCode(string $code): ?self
    {
        return self::where('code', $code)->find();
    }

    /**
     * 检查游戏是否在活动时间内
     */
    public function isActive(): bool
    {
        if ($this->status != 1) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        if ($this->start_time && $this->start_time > $now) {
            return false;
        }

        if ($this->end_time && $this->end_time < $now) {
            return false;
        }

        return true;
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
            'name' => $this->getTranslated('name', $locale),
            'description' => $this->getTranslated('description', $locale),
            'rules' => $this->getTranslated('rules', $locale),
            'icon' => $this->icon,
            'bg_image' => $this->bg_image,
            'config' => $this->config,
            'status' => (int)$this->status,
            'is_active' => $this->isActive(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];
    }

    /**
     * 转换为管理端数组
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'icon' => $this->icon,
            'bg_image' => $this->bg_image,
            'config' => $this->config,
            'sort' => (int)$this->sort,
            'status' => (int)$this->status,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
