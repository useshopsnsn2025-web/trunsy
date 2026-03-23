<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 游戏奖品翻译模型
 */
class GamePrizeTranslation extends Model
{
    protected $table = 'game_prize_translations';
    protected $autoWriteTimestamp = false;

    /**
     * 关联奖品
     */
    public function prize()
    {
        return $this->belongsTo(GamePrize::class, 'prize_id');
    }

    /**
     * 获取奖品的所有翻译
     */
    public static function getByPrizeId(int $prizeId): array
    {
        $translations = self::where('prize_id', $prizeId)->select()->toArray();
        $result = [];
        foreach ($translations as $t) {
            $result[$t['locale']] = [
                'name' => $t['name'],
                'description' => $t['description'] ?? '',
            ];
        }
        return $result;
    }

    /**
     * 批量更新奖品翻译
     */
    public static function updateTranslations(int $prizeId, array $translations): void
    {
        foreach ($translations as $locale => $data) {
            self::updateOrCreate(
                ['prize_id' => $prizeId, 'locale' => $locale],
                [
                    'name' => $data['name'] ?? '',
                    'description' => $data['description'] ?? '',
                ]
            );
        }
    }

    /**
     * 更新或创建记录
     */
    protected static function updateOrCreate(array $condition, array $data): void
    {
        $record = self::where($condition)->find();
        if ($record) {
            $record->save($data);
        } else {
            self::create(array_merge($condition, $data));
        }
    }

    /**
     * 删除奖品的所有翻译
     */
    public static function deleteByPrizeId(int $prizeId): void
    {
        self::where('prize_id', $prizeId)->delete();
    }
}
