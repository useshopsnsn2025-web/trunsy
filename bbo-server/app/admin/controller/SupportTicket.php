<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\SupportTicket as TicketModel;
use app\common\model\TicketReply;
use app\common\model\UiTranslation;

/**
 * 工单管理控制器
 */
class SupportTicket extends Base
{
    /**
     * 工单列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        // 构建查询条件
        $where = [];

        // 搜索条件
        $keyword = input('keyword', '');
        $status = input('status', '');
        $category = input('category', '');
        $priority = input('priority', '');
        $serviceId = input('service_id', '');
        $userId = input('user_id', '');
        $startDate = input('start_date', '');
        $endDate = input('end_date', '');

        $query = TicketModel::order('id', 'desc');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('ticket_no', 'like', "%{$keyword}%")
                  ->whereOr('subject', 'like', "%{$keyword}%");
            });
        }

        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($priority !== '') {
            $query->where('priority', (int)$priority);
        }

        if ($serviceId !== '') {
            $query->where('service_id', (int)$serviceId);
        }

        if ($userId !== '') {
            $query->where('user_id', (int)$userId);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        // 先获取总数（clone查询对象避免影响后续查询）
        $total = (clone $query)->count();

        // 获取列表数据
        $list = $query->page($page, $pageSize)
            ->select();

        // 手动加载关联数据以避免潜在问题
        $result = [];
        foreach ($list as $item) {
            $data = $item->toArray();
            // 安全地获取关联数据
            try {
                $data['user'] = $item->user ? $item->user->toArray() : null;
            } catch (\Exception $e) {
                $data['user'] = null;
            }
            try {
                $data['service'] = $item->service ? $item->service->toArray() : null;
            } catch (\Exception $e) {
                $data['service'] = null;
            }
            // 翻译举报工单的标题为中文
            $data['subject'] = $this->translateReportSubject($data);
            $result[] = $data;
        }

        return $this->paginate($result, $total, $page, $pageSize);
    }

    /**
     * 工单详情
     */
    public function read(int $id): Response
    {
        $ticket = TicketModel::with(['user', 'service', 'relatedOrder', 'goods', 'replies'])
            ->find($id);

        if (!$ticket) {
            return $this->error('工单不存在', 404);
        }

        $data = $ticket->toArray();
        // 翻译举报工单的标题为中文
        $data['subject'] = $this->translateReportSubject($data);

        return $this->success($data);
    }

    /**
     * 分配客服
     */
    public function assign(int $id): Response
    {
        $ticket = TicketModel::find($id);
        if (!$ticket) {
            return $this->error('工单不存在', 404);
        }

        $serviceId = input('post.service_id');
        if (empty($serviceId)) {
            return $this->error('请选择客服');
        }

        $ticket->service_id = $serviceId;
        if ($ticket->status == TicketModel::STATUS_PENDING) {
            $ticket->status = TicketModel::STATUS_PROCESSING;
        }
        $ticket->save();

        return $this->success([], '分配成功');
    }

    /**
     * 回复工单
     */
    public function reply(int $id): Response
    {
        $ticket = TicketModel::find($id);
        if (!$ticket) {
            return $this->error('工单不存在', 404);
        }

        $content = input('post.content', '');
        if (empty($content)) {
            return $this->error('请填写回复内容');
        }

        $images = input('post.images', []);
        $isInternal = input('post.is_internal', 0);

        // 创建回复
        $reply = new TicketReply();
        $reply->ticket_id = $id;
        $reply->from_type = TicketReply::FROM_SERVICE;
        $reply->from_id = request()->adminId ?? 0;
        $reply->content = $content;
        $reply->images = $images;
        $reply->is_internal = $isInternal;
        $reply->save();

        // 更新工单状态
        if (!$isInternal) {
            $ticket->status = TicketModel::STATUS_REPLIED;
            if (!$ticket->first_reply_at) {
                $ticket->first_reply_at = date('Y-m-d H:i:s');
            }
            // 如果没有分配客服，自动分配
            if (!$ticket->service_id) {
                $ticket->service_id = request()->adminId ?? null;
            }
        }
        $ticket->save();

        return $this->success(['id' => $reply->id], '回复成功');
    }

    /**
     * 关闭工单
     */
    public function close(int $id): Response
    {
        $ticket = TicketModel::find($id);
        if (!$ticket) {
            return $this->error('工单不存在', 404);
        }

        if ($ticket->status == TicketModel::STATUS_CLOSED) {
            return $this->error('工单已关闭');
        }

        $ticket->status = TicketModel::STATUS_CLOSED;
        $ticket->closed_at = date('Y-m-d H:i:s');
        $ticket->save();

        // 添加系统回复
        $reply = new TicketReply();
        $reply->ticket_id = $id;
        $reply->from_type = TicketReply::FROM_SYSTEM;
        $reply->from_id = 0;
        $reply->content = '工单已关闭';
        $reply->save();

        return $this->success([], '工单已关闭');
    }

    /**
     * 解决工单
     */
    public function resolve(int $id): Response
    {
        $ticket = TicketModel::find($id);
        if (!$ticket) {
            return $this->error('工单不存在', 404);
        }

        $ticket->status = TicketModel::STATUS_RESOLVED;
        $ticket->resolved_at = date('Y-m-d H:i:s');
        $ticket->save();

        return $this->success([], '工单已标记为解决');
    }

    /**
     * 修改优先级
     */
    public function priority(int $id): Response
    {
        $ticket = TicketModel::find($id);
        if (!$ticket) {
            return $this->error('工单不存在', 404);
        }

        $priority = input('post.priority', 1);
        $ticket->priority = $priority;
        $ticket->save();

        return $this->success([], '优先级已更新');
    }

    /**
     * 统计数据
     */
    public function statistics(): Response
    {
        $pending = TicketModel::where('status', TicketModel::STATUS_PENDING)->count();
        $processing = TicketModel::where('status', TicketModel::STATUS_PROCESSING)->count();
        $replied = TicketModel::where('status', TicketModel::STATUS_REPLIED)->count();
        $resolved = TicketModel::where('status', TicketModel::STATUS_RESOLVED)->count();

        // 今日新增
        $today = date('Y-m-d');
        $todayNew = TicketModel::where('created_at', '>=', $today . ' 00:00:00')
            ->where('created_at', '<=', $today . ' 23:59:59')
            ->count();
        $todayResolved = TicketModel::where('resolved_at', '>=', $today . ' 00:00:00')
            ->where('resolved_at', '<=', $today . ' 23:59:59')
            ->count();

        // 平均首次响应时间（分钟）
        $avgTime = 0;
        $ticketsWithReply = TicketModel::whereNotNull('first_reply_at')
            ->whereColumn('first_reply_at', '>', 'created_at')
            ->field('created_at, first_reply_at')
            ->select();

        if ($ticketsWithReply->count() > 0) {
            $totalMinutes = 0;
            foreach ($ticketsWithReply as $ticket) {
                $created = strtotime($ticket->created_at);
                $replied = strtotime($ticket->first_reply_at);
                $totalMinutes += ($replied - $created) / 60;
            }
            $avgTime = $totalMinutes / $ticketsWithReply->count();
        }

        return $this->success([
            'pending' => $pending,
            'processing' => $processing,
            'replied' => $replied,
            'resolved' => $resolved,
            'today_new' => $todayNew,
            'today_resolved' => $todayResolved,
            'avg_first_reply_minutes' => round($avgTime, 1),
        ]);
    }

    /**
     * 获取分类列表
     */
    public function categories(): Response
    {
        $categories = [];
        foreach (TicketModel::CATEGORY_MAP as $key => $name) {
            $categories[] = ['key' => $key, 'name' => $name];
        }
        return $this->success($categories);
    }

    /**
     * 翻译工单标题为中文（从数据库获取翻译）
     * subject 格式：
     * - 举报商品：Item - {reason_code}
     * - 举报会员：Member - {username}
     * - 其他工单：直接翻译标题
     */
    private function translateReportSubject(array $data): string
    {
        $subject = $data['subject'] ?? '';
        $category = $data['category'] ?? '';
        $subCategory = $data['sub_category'] ?? '';

        // 从数据库获取翻译映射（zh-tw 用于管理后台）
        $translations = $this->getTicketSubjectTranslations();
        $reverseMap = $this->getTicketSubjectReverseMap();

        // 非举报工单，尝试翻译标题
        if ($category !== 'report') {
            // 先尝试通过 sub_category 翻译
            if ($subCategory && isset($translations[$subCategory])) {
                return $translations[$subCategory];
            }
            // 再尝试通过标题反向查找 key，然后翻译
            if (isset($reverseMap[$subject])) {
                $key = $reverseMap[$subject];
                if (isset($translations[$key])) {
                    return $translations[$key];
                }
            }
            // 无法翻译，返回原标题
            return $subject;
        }

        // 以下是举报工单的翻译逻辑
        // 解析标题格式：类型 - 原因/用户名
        $parts = explode(' - ', $subject, 2);
        if (count($parts) < 2) {
            return $subject;
        }

        $type = trim($parts[0]);
        $secondPart = trim($parts[1]);

        // 翻译类型：先反向查找 key，再翻译
        $typeKey = $reverseMap[$type] ?? null;
        $translatedType = $typeKey && isset($translations[$typeKey]) ? $translations[$typeKey] : $type;

        // 翻译原因（如果是举报会员，第二部分是用户名，不翻译）
        if ($subCategory === 'report_member') {
            // 举报会员，保留用户名
            return "{$translatedType} - {$secondPart}";
        } else {
            // 举报商品，翻译原因
            $reasonKey = $reverseMap[$secondPart] ?? $subCategory;
            $translatedReason = isset($translations[$reasonKey]) ? $translations[$reasonKey] : $secondPart;
            return "{$translatedType} - {$translatedReason}";
        }
    }

    /**
     * 获取工单标题翻译（zh-tw）
     * @return array
     */
    private function getTicketSubjectTranslations(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        // 管理后台使用繁体中文
        $cache = UiTranslation::getTranslationsByNamespace('zh-tw', 'ticket_subject');
        return $cache;
    }

    /**
     * 获取工单标题反向映射（各语言标题 -> key）
     * @return array
     */
    private function getTicketSubjectReverseMap(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [];
        // 获取所有语言的反向映射
        foreach (['en-us', 'zh-tw', 'ja-jp'] as $locale) {
            $translations = UiTranslation::getTranslationsByNamespace($locale, 'ticket_subject_reverse');
            foreach ($translations as $text => $key) {
                $cache[$text] = $key;
            }
        }
        return $cache;
    }
}
