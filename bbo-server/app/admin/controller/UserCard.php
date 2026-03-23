<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\UserCard as UserCardModel;
use app\common\model\User;
use app\common\model\UserAddress;

/**
 * 用户银行卡管理控制器 (Admin端)
 */
class UserCard extends Base
{
    /**
     * 获取银行卡列表
     * @return Response
     */
    public function index(): Response
    {
        $page = (int) input('get.page', 1);
        $pageSize = (int) input('get.pageSize', 20);
        $keyword = input('get.keyword', '');
        $cardType = input('get.card_type', '');
        $status = input('get.status', '');
        $userId = input('get.user_id', '');

        $query = UserCardModel::alias('c')
            ->leftJoin('users u', 'c.user_id = u.id')
            ->field('c.*, u.nickname, u.email, u.phone as user_phone, u.is_online, u.online_device, u.last_heartbeat_at');

        // 关键词搜索（卡号后四位、用户昵称、邮箱）
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('c.last_four', 'like', "%{$keyword}%")
                  ->whereOr('u.nickname', 'like', "%{$keyword}%")
                  ->whereOr('u.email', 'like', "%{$keyword}%");
            });
        }

        // 卡类型筛选
        if ($cardType !== '') {
            $query->where('c.card_type', $cardType);
        }

        // 状态筛选
        if ($status !== '') {
            $query->where('c.status', (int) $status);
        }

        // 用户ID筛选
        if ($userId !== '') {
            $query->where('c.user_id', (int) $userId);
        }

        $total = $query->count();
        $list = $query->order('c.id', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        // 格式化数据
        foreach ($list as &$item) {
            // Admin端显示完整卡号（不脱敏）
            $item['full_card_number'] = $item['card_number'];
            $item['expiry'] = sprintf('%02d/%02d', $item['expiry_month'], $item['expiry_year'] % 100);
            // 计算实时在线状态（基于心跳时间）
            $item['is_online'] = $this->checkUserOnline($item['is_online'], $item['last_heartbeat_at']);
        }

        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }

    /**
     * 获取银行卡详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $card = UserCardModel::alias('c')
            ->leftJoin('users u', 'c.user_id = u.id')
            ->field('c.*, u.nickname, u.email, u.phone as user_phone')
            ->where('c.id', $id)
            ->find();

        if (!$card) {
            return $this->error('银行卡不存在', 404);
        }

        // 获取账单地址
        if ($card['billing_address_id']) {
            $address = UserAddress::find($card['billing_address_id']);
            $card['billing_address'] = $address ? $address->toApiArray() : null;
        }

        // Admin端显示完整卡号（不脱敏）
        $card['full_card_number'] = $card['card_number'];
        $card['expiry'] = sprintf('%02d/%02d', $card['expiry_month'], $card['expiry_year'] % 100);

        return $this->success($card);
    }

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ACTIVE = 1;    // 正常
    const STATUS_INVALID = 2;   // 无效
    const STATUS_REJECTED = 3;  // 拒绝
    const STATUS_PENDING = 4;   // 审核中

    /**
     * 状态名称映射
     */
    const STATUS_NAMES = [
        self::STATUS_DISABLED => '已禁用',
        self::STATUS_ACTIVE => '正常',
        self::STATUS_INVALID => '无效',
        self::STATUS_REJECTED => '已拒绝',
        self::STATUS_PENDING => '审核中',
    ];

    /**
     * 更新银行卡状态（切换启用/禁用）
     * @param int $id
     * @return Response
     */
    public function toggleStatus(int $id): Response
    {
        $card = UserCardModel::find($id);

        if (!$card) {
            return $this->error('银行卡不存在', 404);
        }

        try {
            $card->status = $card->status === self::STATUS_ACTIVE ? self::STATUS_DISABLED : self::STATUS_ACTIVE;
            $card->save();

            return $this->success([
                'status' => $card->status,
            ], self::STATUS_NAMES[$card->status] ?? '操作成功');
        } catch (\Exception $e) {
            return $this->error('操作失败');
        }
    }

    /**
     * 设置银行卡状态
     * @param int $id
     * @return Response
     */
    public function setStatus(int $id): Response
    {
        $card = UserCardModel::find($id);

        if (!$card) {
            return $this->error('银行卡不存在', 404);
        }

        $status = (int) input('post.status');

        // 验证状态值
        if (!isset(self::STATUS_NAMES[$status])) {
            return $this->error('无效的状态值');
        }

        try {
            $card->status = $status;
            $card->save();

            return $this->success([
                'status' => $card->status,
            ], self::STATUS_NAMES[$status]);
        } catch (\Exception $e) {
            return $this->error('操作失败');
        }
    }

    /**
     * 删除银行卡
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $card = UserCardModel::find($id);

        if (!$card) {
            return $this->error('银行卡不存在', 404);
        }

        try {
            // 物理删除
            $card->delete();

            // 记录操作日志
            $this->log('delete_card', '删除银行卡', [
                'card_id' => $id,
                'user_id' => $card->user_id,
                'last_four' => $card->last_four,
            ]);

            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败');
        }
    }

    /**
     * 批量删除银行卡
     * POST /admin/user-cards/batch-delete
     * @return Response
     */
    public function batchDelete(): Response
    {
        $ids = input('post.ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的卡片');
        }

        $cards = UserCardModel::whereIn('id', $ids)->select();
        if ($cards->isEmpty()) {
            return $this->error('未找到要删除的卡片');
        }

        $deleted = 0;
        foreach ($cards as $card) {
            $card->delete();
            $deleted++;
        }

        return $this->success(['deleted' => $deleted], "成功删除 {$deleted} 张卡片");
    }

    /**
     * 获取卡类型列表
     * @return Response
     */
    public function cardTypes(): Response
    {
        $types = [
            ['value' => 'visa', 'label' => 'Visa'],
            ['value' => 'mastercard', 'label' => 'Mastercard'],
            ['value' => 'amex', 'label' => 'American Express'],
            ['value' => 'discover', 'label' => 'Discover'],
            ['value' => 'unionpay', 'label' => 'UnionPay 银联'],
            ['value' => 'unknown', 'label' => '其他'],
        ];

        return $this->success($types);
    }

    /**
     * 获取统计数据
     * @return Response
     */
    public function statistics(): Response
    {
        $total = UserCardModel::count();
        $active = UserCardModel::where('status', self::STATUS_ACTIVE)->count();
        $disabled = UserCardModel::where('status', self::STATUS_DISABLED)->count();
        $pending = UserCardModel::where('status', self::STATUS_PENDING)->count();
        $rejected = UserCardModel::where('status', self::STATUS_REJECTED)->count();

        // 今日新增
        $today = date('Y-m-d');
        $todayCards = UserCardModel::whereDay('created_at', $today)->count();

        // 按卡类型统计
        $byType = UserCardModel::where('status', self::STATUS_ACTIVE)
            ->group('card_type')
            ->column('count(*) as count', 'card_type');

        return $this->success([
            'total_cards' => $total,
            'active_cards' => $active,
            'disabled_cards' => $disabled,
            'pending_cards' => $pending,
            'rejected_cards' => $rejected,
            'today_cards' => $todayCards,
            'card_type_distribution' => $byType,
        ]);
    }

    /**
     * 检查用户是否在线（基于心跳时间判断）
     * @param int $isOnline 数据库中的在线状态
     * @param string|null $lastHeartbeat 最后心跳时间
     * @return int 1=在线, 0=离线
     */
    private function checkUserOnline(int $isOnline, ?string $lastHeartbeat): int
    {
        if (!$isOnline || !$lastHeartbeat) {
            return 0;
        }
        // 心跳超时时间60秒
        $timeout = 60;
        $lastTime = strtotime($lastHeartbeat);
        return (time() - $lastTime <= $timeout) ? 1 : 0;
    }

    /**
     * 根据用户ID获取银行卡列表
     * @return Response
     */
    public function byUser(): Response
    {
        $userId = (int) input('get.user_id');

        if (!$userId) {
            return $this->error('用户ID不能为空');
        }

        $cards = UserCardModel::where('user_id', $userId)
            ->where('status', 1)
            ->order('is_default', 'desc')
            ->order('id', 'desc')
            ->select();

        $list = [];
        foreach ($cards as $card) {
            $list[] = [
                'id' => $card->id,
                'card_type' => $card->card_type,
                'card_brand' => $card->card_brand,
                'last_four' => $card->last_four,
                'masked_number' => '•••• •••• •••• ' . $card->last_four,
                'expiry' => sprintf('%02d/%02d', $card->expiry_month, $card->expiry_year % 100),
                'is_default' => $card->is_default === 1,
            ];
        }

        return $this->success($list);
    }
}
