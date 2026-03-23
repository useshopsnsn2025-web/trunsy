<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 游戏翻译模型
 */
class GameTranslation extends Model
{
    protected $table = 'game_translations';
    protected $autoWriteTimestamp = false;

    /**
     * 关联游戏
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * 获取游戏的所有翻译
     */
    public static function getByGameId(int $gameId): array
    {
        $translations = self::where('game_id', $gameId)->select()->toArray();
        $result = [];
        foreach ($translations as $t) {
            $result[$t['locale']] = [
                'name' => $t['name'],
                'description' => $t['description'],
                'rules' => $t['rules'],
            ];
        }
        return $result;
    }

    /**
     * 批量更新游戏翻译
     */
    public static function updateTranslations(int $gameId, array $translations): void
    {
        foreach ($translations as $locale => $data) {
            self::updateOrCreate(
                ['game_id' => $gameId, 'locale' => $locale],
                [
                    'name' => $data['name'] ?? '',
                    'description' => $data['description'] ?? '',
                    'rules' => $data['rules'] ?? '',
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
}
