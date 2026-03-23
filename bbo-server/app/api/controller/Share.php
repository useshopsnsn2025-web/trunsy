<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\ShareLink;
use app\common\model\InviteRecord;
use app\common\model\ShareRewardConfig;
use app\common\model\UserGameChance;
use app\common\model\ChanceLog;
use app\common\model\Game as GameModel;
use app\common\model\SystemConfig;

/**
 * 分享控制器
 */
class Share extends Base
{
    /**
     * 生成分享链接
     */
    public function generate()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $type = $this->request->post('type', ShareLink::TYPE_GAME);
        $targetId = $this->request->post('target_id');
        $channel = $this->request->post('channel');

        // 验证类型
        $validTypes = [ShareLink::TYPE_GAME, ShareLink::TYPE_GOODS, ShareLink::TYPE_ACTIVITY];
        if (!in_array($type, $validTypes)) {
            return $this->error('Invalid share type', 400);
        }

        // 验证渠道
        if ($channel) {
            $validChannels = [
                ShareLink::CHANNEL_WHATSAPP,
                ShareLink::CHANNEL_FACEBOOK,
                ShareLink::CHANNEL_LINE,
                ShareLink::CHANNEL_TWITTER,
                ShareLink::CHANNEL_COPY,
            ];
            if (!in_array($channel, $validChannels)) {
                return $this->error('Invalid share channel', 400);
            }
        }

        // 创建或获取分享链接
        $shareLink = ShareLink::createLink($this->userId, $type, $targetId ? (int)$targetId : null, $channel);

        // 生成完整分享URL
        $baseUrl = rtrim(SystemConfig::getConfig('site_url', ''), '/');
        $shareUrl = $baseUrl . '/share/' . $shareLink->code;

        return $this->success([
            'share_link' => $shareLink->toApiArray(),
            'share_url' => $shareUrl,
            'share_code' => $shareLink->code,
        ]);
    }

    /**
     * 记录分享点击（被邀请人访问时调用）
     */
    public function click()
    {
        $code = $this->request->post('code');
        if (!$code) {
            return $this->error('Share code is required', 400);
        }

        $shareLink = ShareLink::getByCode($code);
        if (!$shareLink) {
            return $this->error('Invalid or expired share link', 400);
        }

        // 增加点击次数
        $shareLink->incrementClick();

        // 检查是否需要发放点击奖励给邀请人
        $this->issueClickReward($shareLink);

        return $this->success([
            'inviter_id' => $shareLink->user_id,
            'type' => $shareLink->type,
            'target_id' => $shareLink->target_id,
        ]);
    }

    /**
     * 记录邀请注册（新用户注册时调用）
     */
    public function registerWithCode()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $code = $this->request->post('code');
        if (!$code) {
            return $this->error('Share code is required', 400);
        }

        $shareLink = ShareLink::getByCode($code);
        if (!$shareLink) {
            return $this->error('Invalid or expired share link', 400);
        }

        // 不能邀请自己
        if ($shareLink->user_id === $this->userId) {
            return $this->error('Cannot invite yourself', 400);
        }

        // 检查是否已被邀请
        $existingInviter = InviteRecord::getInviter($this->userId);
        if ($existingInviter) {
            return $this->error('Already invited by another user', 400);
        }

        // 创建邀请记录
        $inviteRecord = InviteRecord::createRecord(
            $shareLink->user_id,
            $this->userId,
            $shareLink->id,
            $shareLink->code
        );

        if (!$inviteRecord) {
            return $this->error('Failed to create invite record', 500);
        }

        // 发放注册奖励
        $inviteRecord->issueRegisterReward();

        return $this->success([
            'message' => 'Successfully registered with invite code',
            'inviter_id' => $shareLink->user_id,
        ]);
    }

    /**
     * 获取分享统计
     */
    public function stats()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $shareStats = ShareLink::getUserStats($this->userId);
        $inviteStats = InviteRecord::getInviteStats($this->userId);

        return $this->success([
            'share' => $shareStats,
            'invite' => $inviteStats,
        ]);
    }

    /**
     * 获取我的分享链接列表
     */
    public function myLinks()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('page_size', 20);

        $result = ShareLink::getUserLinks($this->userId, $page, $pageSize);

        // 转换为API格式
        $list = [];
        foreach ($result['list'] as $item) {
            $list[] = [
                'id' => $item['id'],
                'code' => $item['code'],
                'type' => $item['type'],
                'channel' => $item['channel'],
                'click_count' => (int)$item['click_count'],
                'register_count' => (int)$item['register_count'],
                'order_count' => (int)$item['order_count'],
                'expires_at' => $item['expires_at'],
                'created_at' => $item['created_at'],
            ];
        }

        return $this->success([
            'total' => $result['total'],
            'list' => $list,
            'page' => $result['page'],
            'page_size' => $result['page_size'],
        ]);
    }

    /**
     * 获取我的邀请列表
     */
    public function myInvites()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('page_size', 20);

        $result = InviteRecord::getInviteList($this->userId, $page, $pageSize);

        return $this->success($result);
    }

    /**
     * 获取奖励配置（展示给用户）
     */
    public function rewardConfig()
    {
        $configs = ShareRewardConfig::getAllConfigs();

        $result = [
            'register' => [
                'inviter' => [],
                'invitee' => [],
            ],
            'first_order' => [
                'inviter' => [],
                'invitee' => [],
            ],
            'share_click' => [
                'inviter' => [],
            ],
        ];

        foreach ($configs as $config) {
            if (isset($result[$config['type']][$config['target']])) {
                $result[$config['type']][$config['target']][] = [
                    'reward_type' => $config['reward_type'],
                    'reward_value' => (float)$config['reward_value'],
                    'game_code' => $config['game_code'],
                ];
            }
        }

        return $this->success($result);
    }

    /**
     * 获取分享话术模板（多语言）
     */
    public function shareTemplates()
    {
        $templates = [
            'game' => [
                'en-us' => "I just won on BBO! Try your luck now and get FREE spins! Use my code: {code}",
                'zh-tw' => "我剛在BBO贏得大獎！快來試試手氣，使用我的邀請碼 {code} 可以獲得免費抽獎機會！",
                'ja-jp' => "BBOで当たりました！今すぐ無料スピンをゲット！招待コード: {code}",
            ],
            'goods' => [
                'en-us' => "Check out this amazing product on BBO! Use my code {code} for special rewards!",
                'zh-tw' => "在BBO發現超棒的商品！使用我的邀請碼 {code} 可以獲得特別獎勵！",
                'ja-jp' => "BBOでこの素晴らしい商品を見てください！コード {code} で特別報酬をゲット！",
            ],
        ];

        return $this->success($templates);
    }

    /**
     * 发放点击奖励
     */
    protected function issueClickReward(ShareLink $shareLink): void
    {
        // 检查今日是否已达到点击奖励上限
        $today = date('Y-m-d');
        $todayClicks = ChanceLog::where('user_id', $shareLink->user_id)
            ->where('source', ChanceLog::SOURCE_SHARE)
            ->whereDay('created_at', $today)
            ->count();

        // 每日最多2次点击奖励
        if ($todayClicks >= 2) {
            return;
        }

        // 获取点击奖励配置
        $clickReward = ShareRewardConfig::getConfig('share_click', 'inviter');
        if (!$clickReward) {
            return;
        }

        // 发放奖励
        ShareRewardConfig::issueReward(
            $clickReward->toArray(),
            $shareLink->user_id,
            ChanceLog::SOURCE_SHARE,
            (string)$shareLink->id
        );
    }
}
