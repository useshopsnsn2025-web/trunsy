<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * 邀请记录模型
 */
class InviteRecord extends Model
{
    protected $table = 'invite_records';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 状态
    const STATUS_REGISTERED = 1;    // 已注册
    const STATUS_FIRST_ORDER = 2;   // 已完成首单

    /**
     * 创建邀请记录
     */
    public static function createRecord(int $inviterId, int $inviteeId, ?int $shareLinkId = null, ?string $shareCode = null): ?self
    {
        // 检查是否已存在邀请关系
        $existing = self::where('inviter_id', $inviterId)
            ->where('invitee_id', $inviteeId)
            ->find();

        if ($existing) {
            return null;
        }

        // 不能邀请自己
        if ($inviterId === $inviteeId) {
            return null;
        }

        return self::create([
            'inviter_id' => $inviterId,
            'invitee_id' => $inviteeId,
            'share_link_id' => $shareLinkId,
            'share_code' => $shareCode,
            'status' => self::STATUS_REGISTERED,
            'register_reward_issued' => 0,
            'order_reward_issued' => 0,
        ]);
    }

    /**
     * 获取用户的邀请人
     */
    public static function getInviter(int $inviteeId): ?int
    {
        $record = self::where('invitee_id', $inviteeId)->find();
        return $record ? (int)$record->inviter_id : null;
    }

    /**
     * 获取用户邀请的人数
     */
    public static function getInviteCount(int $inviterId): int
    {
        return self::where('inviter_id', $inviterId)->count();
    }

    /**
     * 获取用户邀请列表
     */
    public static function getInviteList(int $inviterId, int $page = 1, int $pageSize = 20): array
    {
        $query = self::where('inviter_id', $inviterId)
            ->order('created_at', 'desc');

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        $result = [];
        foreach ($list as $record) {
            // 获取被邀请人信息
            $invitee = User::find($record->invitee_id);
            $result[] = [
                'id' => $record->id,
                'invitee_id' => $record->invitee_id,
                'invitee_nickname' => $invitee ? $invitee->nickname : 'Unknown',
                'invitee_avatar' => $invitee ? $invitee->avatar : '',
                'status' => $record->status,
                'register_reward_issued' => (bool)$record->register_reward_issued,
                'order_reward_issued' => (bool)$record->order_reward_issued,
                'first_order_at' => $record->first_order_at,
                'created_at' => $record->created_at,
            ];
        }

        return [
            'total' => $total,
            'list' => $result,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 标记首单完成
     */
    public function markFirstOrder(int $orderId): bool
    {
        if ($this->status >= self::STATUS_FIRST_ORDER) {
            return false;
        }

        $this->status = self::STATUS_FIRST_ORDER;
        $this->first_order_id = $orderId;
        $this->first_order_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 发放注册奖励
     */
    public function issueRegisterReward(): bool
    {
        if ($this->register_reward_issued) {
            return false;
        }

        Db::startTrans();
        try {
            // 获取奖励配置
            $inviterReward = ShareRewardConfig::getConfig('register', 'inviter');
            $inviteeReward = ShareRewardConfig::getConfig('register', 'invitee');

            // 发放邀请人奖励
            if ($inviterReward) {
                ShareRewardConfig::issueReward($inviterReward, $this->inviter_id, 'invite_register', (string)$this->id);
            }

            // 发放被邀请人奖励
            if ($inviteeReward) {
                ShareRewardConfig::issueReward($inviteeReward, $this->invitee_id, 'invite_register', (string)$this->id);
            }

            $this->register_reward_issued = 1;
            $this->save();

            // 更新分享链接的注册统计
            if ($this->share_link_id) {
                $shareLink = ShareLink::find($this->share_link_id);
                if ($shareLink) {
                    $shareLink->incrementRegister();
                }
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            \think\facade\Log::error('Issue register reward failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发放首单奖励
     */
    public function issueFirstOrderReward(): bool
    {
        if ($this->order_reward_issued) {
            return false;
        }

        if ($this->status < self::STATUS_FIRST_ORDER) {
            return false;
        }

        Db::startTrans();
        try {
            // 获取奖励配置
            $inviterRewards = ShareRewardConfig::getConfigs('first_order', 'inviter');
            $inviteeRewards = ShareRewardConfig::getConfigs('first_order', 'invitee');

            // 发放邀请人奖励（可能有多个）
            foreach ($inviterRewards as $reward) {
                ShareRewardConfig::issueReward($reward, $this->inviter_id, 'invite_first_order', (string)$this->id);
            }

            // 发放被邀请人奖励
            foreach ($inviteeRewards as $reward) {
                ShareRewardConfig::issueReward($reward, $this->invitee_id, 'invite_first_order', (string)$this->id);
            }

            $this->order_reward_issued = 1;
            $this->save();

            // 更新分享链接的订单统计
            if ($this->share_link_id) {
                $shareLink = ShareLink::find($this->share_link_id);
                if ($shareLink) {
                    $shareLink->incrementOrder();
                }
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            \think\facade\Log::error('Issue first order reward failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取邀请统计
     */
    public static function getInviteStats(int $inviterId): array
    {
        $totalInvites = self::where('inviter_id', $inviterId)->count();
        $completedOrders = self::where('inviter_id', $inviterId)
            ->where('status', self::STATUS_FIRST_ORDER)
            ->count();

        return [
            'total_invites' => $totalInvites,
            'completed_orders' => $completedOrders,
            'pending_orders' => $totalInvites - $completedOrders,
        ];
    }
}
