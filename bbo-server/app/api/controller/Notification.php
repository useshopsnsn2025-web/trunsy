<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\Notification as NotificationModel;

/**
 * 通知控制器
 */
class Notification extends Base
{
    /**
     * 获取通知列表
     * GET /api/notifications
     * @return Response
     */
    public function index(): Response
    {
        $userId = $this->getUserId();
        [$page, $pageSize] = $this->getPageParams();

        $query = NotificationModel::where('user_id', $userId)
            ->order('created_at', 'desc');

        // 分类筛选
        $category = input('category', '');
        if ($category) {
            $query->where('category', $category);
        }

        // 类型筛选
        $type = input('type', '');
        if ($type) {
            $query->where('type', $type);
        }

        // 已读/未读筛选
        $isRead = input('is_read', '');
        if ($isRead !== '') {
            $query->where('is_read', (int)$isRead);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        $items = [];
        foreach ($list as $item) {
            $items[] = $item->toApiArray($this->locale);
        }

        return $this->paginate($items, $total, $page, $pageSize);
    }

    /**
     * 获取通知详情
     * GET /api/notifications/:id
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $userId = $this->getUserId();

        $notification = NotificationModel::where('id', $id)
            ->where('user_id', $userId)
            ->find();

        if (!$notification) {
            return $this->error('Notification not found', 404);
        }

        // 标记为已读
        $notification->markAsRead();

        return $this->success($notification->toApiArray($this->locale));
    }

    /**
     * 标记通知为已读
     * POST /api/notifications/:id/read
     * @param int $id
     * @return Response
     */
    public function markRead(int $id): Response
    {
        $userId = $this->getUserId();

        $notification = NotificationModel::where('id', $id)
            ->where('user_id', $userId)
            ->find();

        if (!$notification) {
            return $this->error('Notification not found', 404);
        }

        $notification->markAsRead();

        return $this->success(['message' => 'Notification marked as read']);
    }

    /**
     * 标记所有通知为已读
     * POST /api/notifications/read-all
     * @return Response
     */
    public function markAllRead(): Response
    {
        $userId = $this->getUserId();

        $count = NotificationModel::markAsReadBatch($userId);

        return $this->success([
            'message' => 'All notifications marked as read',
            'count' => $count,
        ]);
    }

    /**
     * 批量标记已读
     * POST /api/notifications/batch-read
     * @return Response
     */
    public function batchRead(): Response
    {
        $userId = $this->getUserId();
        $ids = input('post.ids', []);

        if (empty($ids) || !is_array($ids)) {
            return $this->error('Invalid notification IDs');
        }

        $count = NotificationModel::markAsReadBatch($userId, $ids);

        return $this->success([
            'message' => 'Notifications marked as read',
            'count' => $count,
        ]);
    }

    /**
     * 删除通知
     * DELETE /api/notifications/:id
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $userId = $this->getUserId();

        $notification = NotificationModel::where('id', $id)
            ->where('user_id', $userId)
            ->find();

        if (!$notification) {
            return $this->error('Notification not found', 404);
        }

        $notification->delete();

        return $this->success(['message' => 'Notification deleted']);
    }

    /**
     * 获取未读通知数量
     * GET /api/notifications/unread-count
     * @return Response
     */
    public function unreadCount(): Response
    {
        $userId = $this->getUserId();

        $count = NotificationModel::getUnreadCount($userId);

        return $this->success([
            'count' => $count,
        ]);
    }

    /**
     * 获取通知统计
     * GET /api/notifications/stats
     * @return Response
     */
    public function stats(): Response
    {
        $userId = $this->getUserId();

        $unreadCount = NotificationModel::where('user_id', $userId)
            ->where('is_read', 0)
            ->count();

        $totalCount = NotificationModel::where('user_id', $userId)->count();

        // 各类型未读数量
        $typeStats = NotificationModel::where('user_id', $userId)
            ->where('is_read', 0)
            ->group('type')
            ->column('count(*)', 'type');

        // 各分类未读数量
        $categoryStats = NotificationModel::where('user_id', $userId)
            ->where('is_read', 0)
            ->group('category')
            ->column('count(*)', 'category');

        return $this->success([
            'unread_count' => $unreadCount,
            'total_count' => $totalCount,
            'type_stats' => $typeStats,
            'category_stats' => $categoryStats,
        ]);
    }
}
