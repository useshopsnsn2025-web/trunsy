<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\SupportTicket;
use app\common\model\TicketReply;
use app\common\model\UiTranslation;

/**
 * 用户工单控制器
 */
class Ticket extends Base
{
    /**
     * 工单翻译缓存
     */
    protected ?array $ticketTranslations = null;
    /**
     * 创建工单
     */
    public function create(): Response
    {
        $userId = $this->getUserId();

        $category = input('post.category', '');
        $subCategory = input('post.subCategory', '');
        $subject = input('post.subject', '');
        $content = input('post.content', '');
        $images = input('post.images', []);
        $relatedOrderId = input('post.relatedOrderId', 0);
        $relatedGoodsId = input('post.relatedGoodsId', 0);

        // 验证必填字段
        if (empty($category)) {
            return $this->error('Category required');
        }
        if (empty(trim($content))) {
            return $this->error('Content required');
        }

        // 验证分类有效性
        $validCategories = array_keys(SupportTicket::CATEGORY_MAP);
        if (!in_array($category, $validCategories)) {
            return $this->error('Invalid category');
        }

        // 自动生成标题（如果未提供）
        if (empty($subject)) {
            $categoryText = SupportTicket::CATEGORY_MAP[$category] ?? $category;
            $subject = $categoryText;
            if (!empty($subCategory)) {
                $subject .= ' - ' . $subCategory;
            }
        }

        // 创建工单
        $ticket = new SupportTicket();
        $ticket->ticket_no = SupportTicket::generateNo();
        $ticket->user_id = $userId;
        $ticket->category = $category;
        $ticket->sub_category = $subCategory;
        $ticket->subject = $subject;
        $ticket->content = trim($content);
        $ticket->images = is_array($images) ? $images : [];
        $ticket->status = SupportTicket::STATUS_PENDING;
        $ticket->priority = SupportTicket::PRIORITY_NORMAL;
        $ticket->related_order_id = (int)$relatedOrderId ?: null;
        $ticket->related_goods_id = (int)$relatedGoodsId ?: null;
        $ticket->save();

        return $this->success([
            'id' => $ticket->id,
            'ticketNo' => $ticket->ticket_no,
            'status' => $ticket->status,
            'statusText' => SupportTicket::STATUS_MAP[$ticket->status] ?? '待处理',
            'createdAt' => $ticket->created_at,
        ], 'Ticket created successfully');
    }

    /**
     * 获取我的工单列表
     */
    public function index(): Response
    {
        $userId = $this->getUserId();
        $page = (int)input('page', 1);
        $pageSize = (int)input('pageSize', 20);
        $status = input('status');

        $query = SupportTicket::where('user_id', $userId)
            ->order('created_at', 'desc');

        // 按状态筛选
        if ($status !== null && $status !== '') {
            $query->where('status', (int)$status);
        }

        $total = (clone $query)->count();
        $list = $query->page($page, $pageSize)->select();

        $result = [];
        foreach ($list as $ticket) {
            $result[] = $this->formatTicket($ticket);
        }

        return $this->success([
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }

    /**
     * 获取工单详情
     */
    public function detail(int $id): Response
    {
        $userId = $this->getUserId();

        $ticket = SupportTicket::where('id', $id)
            ->where('user_id', $userId)
            ->find();

        if (!$ticket) {
            return $this->error('Ticket not found', 404);
        }

        // 获取回复列表（排除内部备注）
        $replies = TicketReply::where('ticket_id', $id)
            ->where('is_internal', 0)
            ->order('created_at', 'asc')
            ->select();

        $replyList = [];
        foreach ($replies as $reply) {
            $replyList[] = [
                'id' => $reply->id,
                'content' => $reply->content,
                'fromType' => $reply->from_type,
                'images' => $reply->images ?? [],
                'createdAt' => $reply->created_at,
            ];
        }

        $result = $this->formatTicket($ticket);
        $result['replies'] = $replyList;

        return $this->success($result);
    }

    /**
     * 用户回复工单
     */
    public function reply(int $id): Response
    {
        $userId = $this->getUserId();
        $content = input('post.content', '');
        $images = input('post.images', []);

        if (empty(trim($content)) && empty($images)) {
            return $this->error('Reply content required');
        }

        $ticket = SupportTicket::where('id', $id)
            ->where('user_id', $userId)
            ->find();

        if (!$ticket) {
            return $this->error('Ticket not found', 404);
        }

        // 已关闭的工单不能回复
        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            return $this->error('Ticket closed');
        }

        // 创建回复
        $reply = new TicketReply();
        $reply->ticket_id = $id;
        $reply->from_type = TicketReply::FROM_USER;
        $reply->from_id = $userId;
        $reply->content = $content;
        $reply->images = is_array($images) ? $images : [];
        $reply->is_internal = 0;
        $reply->save();

        // 更新工单状态为待处理
        if ($ticket->status === SupportTicket::STATUS_REPLIED) {
            $ticket->status = SupportTicket::STATUS_PROCESSING;
            $ticket->save();
        }

        return $this->success([
            'id' => $reply->id,
            'content' => $reply->content,
            'fromType' => $reply->from_type,
            'images' => $reply->images ?? [],
            'createdAt' => $reply->created_at,
        ]);
    }

    /**
     * 格式化工单数据
     */
    protected function formatTicket(SupportTicket $ticket): array
    {
        $locale = $this->locale;

        return [
            'id' => $ticket->id,
            'ticketNo' => $ticket->ticket_no,
            'category' => $ticket->category,
            'categoryText' => $this->getCategoryText($ticket->category, $locale),
            'subCategory' => $ticket->sub_category,
            'subCategoryText' => $this->getSubCategoryText($ticket->sub_category, $locale),
            'subject' => $this->translateReportSubject($ticket, $locale),
            'content' => $ticket->content,
            'status' => $ticket->status,
            'statusText' => $this->getStatusText($ticket->status, $locale),
            'priority' => $ticket->priority,
            'priorityText' => $this->getPriorityText($ticket->priority, $locale),
            'images' => $ticket->images ?? [],
            'createdAt' => $ticket->created_at,
            'updatedAt' => $ticket->updated_at,
        ];
    }

    /**
     * 获取工单翻译
     */
    protected function getTicketTranslations(): array
    {
        if ($this->ticketTranslations === null) {
            // Debug: 记录当前 locale
            \think\facade\Log::debug('Ticket getTicketTranslations locale: ' . $this->locale);
            $this->ticketTranslations = UiTranslation::getTranslationsByNamespace($this->locale, 'ticket');
            \think\facade\Log::debug('Ticket translations loaded: ' . json_encode(array_keys($this->ticketTranslations)));
        }
        return $this->ticketTranslations;
    }

    /**
     * 获取状态文本
     */
    protected function getStatusText(int $status, string $locale): string
    {
        $translations = $this->getTicketTranslations();
        // 使用字符串键访问，因为 setNestedValue 将 "status.4" 转换为 ['status' => ['4' => ...]]
        $statusKey = (string)$status;
        return $translations['status'][$statusKey] ?? (SupportTicket::STATUS_MAP[$status] ?? '');
    }

    /**
     * 获取优先级文本
     */
    protected function getPriorityText(int $priority, string $locale): string
    {
        $translations = $this->getTicketTranslations();
        // 使用字符串键访问
        $priorityKey = (string)$priority;
        return $translations['priority'][$priorityKey] ?? (SupportTicket::PRIORITY_MAP[$priority] ?? '');
    }

    /**
     * 获取分类文本
     */
    protected function getCategoryText(string $category, string $locale): string
    {
        $translations = $this->getTicketTranslations();
        return $translations['category'][$category] ?? (SupportTicket::CATEGORY_MAP[$category] ?? $category);
    }

    /**
     * 获取子分类文本
     */
    protected function getSubCategoryText(?string $subCategory, string $locale): string
    {
        if (empty($subCategory)) {
            return '';
        }

        $translations = $this->getTicketTranslations();
        return $translations['subCategory'][$subCategory] ?? (SupportTicket::SUB_CATEGORY_MAP[$subCategory] ?? $subCategory);
    }

    /**
     * 获取举报类型文本
     */
    protected function getReportTypeText(string $type): string
    {
        $translations = $this->getTicketTranslations();

        // 原始类型到翻译键的映射
        $typeKeyMap = [
            'Item' => 'item',
            'Item or Listing' => 'item',
            'Member' => 'member',
            // 兼容已有的多语言标题
            '商品/刊登物品' => 'item',
            '會員' => 'member',
            '商品/出品物' => 'item',
            'メンバー' => 'member',
        ];

        $key = $typeKeyMap[$type] ?? null;
        if ($key && isset($translations['reportType'][$key])) {
            return $translations['reportType'][$key];
        }

        return $type;
    }

    /**
     * 翻译举报工单的标题
     * subject 格式：
     * - 举报商品：Item - {reason_code} 或 Item or Listing - {reason}
     * - 举报会员：Member - {username}
     */
    protected function translateReportSubject(SupportTicket $ticket, string $locale): string
    {
        $subject = $ticket->subject ?? '';
        $category = $ticket->category ?? '';

        // 非举报工单，直接返回原标题
        if ($category !== 'report') {
            return $subject;
        }

        // 解析标题格式：类型 - 原因/用户名
        $parts = explode(' - ', $subject, 2);
        if (count($parts) < 2) {
            return $subject;
        }

        $type = trim($parts[0]);
        $secondPart = trim($parts[1]);

        // 翻译类型
        $translatedType = $this->getReportTypeText($type);

        // 翻译原因（如果是举报会员，第二部分是用户名，不翻译）
        $subCategory = $ticket->sub_category ?? '';
        if ($subCategory === 'report_member') {
            // 举报会员，保留用户名
            return "{$translatedType} - {$secondPart}";
        } else {
            // 举报商品，翻译原因
            $translatedReason = $this->translateReportReason($secondPart, $subCategory);
            return "{$translatedType} - {$translatedReason}";
        }
    }

    /**
     * 翻译举报原因
     */
    protected function translateReportReason(string $reason, string $subCategory): string
    {
        $translations = $this->getTicketTranslations();

        // 原因文本到子分类键的映射
        $reasonKeyMap = [
            'counterfeit' => 'counterfeit',
            'prohibited' => 'prohibited',
            'misleading' => 'misleading',
            'infringement' => 'infringement',
            'fraud' => 'fraud',
            'spam' => 'spam',
            'other' => 'other',
            // 英文原因文本映射
            'Counterfeit or pirated' => 'counterfeit',
            'Counterfeit or pirated goods' => 'counterfeit',
            'Prohibited items' => 'prohibited',
            'Misleading description' => 'misleading',
            'Misleading description or images' => 'misleading',
            'Intellectual property infringement' => 'infringement',
            'Fraud' => 'fraud',
            'Fraud or scam' => 'fraud',
            'Spam or harassment' => 'spam',
            'Other' => 'other',
            'Other reason' => 'other',
        ];

        // 先尝试通过原因文本找到翻译
        $key = $reasonKeyMap[$reason] ?? null;
        if ($key && isset($translations['subCategory'][$key])) {
            return $translations['subCategory'][$key];
        }

        // 再尝试通过子分类找到翻译
        if (!empty($subCategory) && isset($translations['subCategory'][$subCategory])) {
            return $translations['subCategory'][$subCategory];
        }

        return $reason;
    }
}
