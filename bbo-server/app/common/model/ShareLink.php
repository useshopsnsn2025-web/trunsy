<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 分享链接模型
 */
class ShareLink extends Model
{
    protected $table = 'share_links';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 分享类型
    const TYPE_GAME = 'game';
    const TYPE_GOODS = 'goods';
    const TYPE_ACTIVITY = 'activity';

    // 分享渠道
    const CHANNEL_WHATSAPP = 'whatsapp';
    const CHANNEL_FACEBOOK = 'facebook';
    const CHANNEL_LINE = 'line';
    const CHANNEL_TWITTER = 'twitter';
    const CHANNEL_COPY = 'copy';

    /**
     * 生成分享码
     */
    public static function generateCode(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        // 确保唯一性
        while (self::where('code', $code)->count() > 0) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        }

        return $code;
    }

    /**
     * 创建分享链接
     */
    public static function createLink(int $userId, string $type = self::TYPE_GAME, ?int $targetId = null, ?string $channel = null): self
    {
        // 检查是否已有相同类型的分享链接
        $existing = self::where('user_id', $userId)
            ->where('type', $type)
            ->where('target_id', $targetId)
            ->where('channel', $channel)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->whereOr('expires_at', '>', date('Y-m-d H:i:s'));
            })
            ->find();

        if ($existing) {
            return $existing;
        }

        return self::create([
            'user_id' => $userId,
            'code' => self::generateCode(),
            'type' => $type,
            'target_id' => $targetId,
            'channel' => $channel,
            'click_count' => 0,
            'register_count' => 0,
            'order_count' => 0,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ]);
    }

    /**
     * 通过分享码获取链接
     */
    public static function getByCode(string $code): ?self
    {
        return self::where('code', $code)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->whereOr('expires_at', '>', date('Y-m-d H:i:s'));
            })
            ->find();
    }

    /**
     * 增加点击次数
     */
    public function incrementClick(): void
    {
        $this->click_count = $this->click_count + 1;
        $this->save();
    }

    /**
     * 增加注册次数
     */
    public function incrementRegister(): void
    {
        $this->register_count = $this->register_count + 1;
        $this->save();
    }

    /**
     * 增加下单次数
     */
    public function incrementOrder(): void
    {
        $this->order_count = $this->order_count + 1;
        $this->save();
    }

    /**
     * 获取用户的分享链接列表
     */
    public static function getUserLinks(int $userId, int $page = 1, int $pageSize = 20): array
    {
        $query = self::where('user_id', $userId)
            ->order('created_at', 'desc');

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return [
            'total' => $total,
            'list' => $list,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取用户分享统计
     */
    public static function getUserStats(int $userId): array
    {
        $stats = self::where('user_id', $userId)
            ->field('SUM(click_count) as total_clicks, SUM(register_count) as total_registers, SUM(order_count) as total_orders, COUNT(*) as total_links')
            ->find();

        return [
            'total_links' => (int)($stats['total_links'] ?? 0),
            'total_clicks' => (int)($stats['total_clicks'] ?? 0),
            'total_registers' => (int)($stats['total_registers'] ?? 0),
            'total_orders' => (int)($stats['total_orders'] ?? 0),
        ];
    }

    /**
     * 转换为 API 数组
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'target_id' => $this->target_id,
            'channel' => $this->channel,
            'click_count' => (int)$this->click_count,
            'register_count' => (int)$this->register_count,
            'order_count' => (int)$this->order_count,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
