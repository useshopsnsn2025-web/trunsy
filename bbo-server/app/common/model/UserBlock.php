<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 用户拉黑模型
 */
class UserBlock extends Model
{
    protected $name = 'user_blocks';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'blocked_user_id' => 'integer',
    ];

    /**
     * 检查是否已拉黑
     */
    public static function isBlocked(int $userId, int $blockedUserId): bool
    {
        return self::where('user_id', $userId)
            ->where('blocked_user_id', $blockedUserId)
            ->count() > 0;
    }

    /**
     * 添加拉黑
     */
    public static function addBlock(int $userId, int $blockedUserId): self
    {
        // 检查是否已经拉黑
        $existing = self::where('user_id', $userId)
            ->where('blocked_user_id', $blockedUserId)
            ->find();

        if ($existing) {
            return $existing;
        }

        $block = new self();
        $block->user_id = $userId;
        $block->blocked_user_id = $blockedUserId;
        $block->save();

        return $block;
    }

    /**
     * 取消拉黑
     */
    public static function removeBlock(int $userId, int $blockedUserId): bool
    {
        return self::where('user_id', $userId)
            ->where('blocked_user_id', $blockedUserId)
            ->delete() > 0;
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联被拉黑用户
     */
    public function blockedUser()
    {
        return $this->belongsTo(User::class, 'blocked_user_id', 'id');
    }
}
