<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\Conversation as ConversationModel;
use app\common\model\Message;
use app\common\model\User;
use app\common\model\Goods;
use app\common\model\CustomerService;
use app\common\model\SupportTicket;
use app\common\model\UserBlock;
use app\common\helper\UrlHelper;

/**
 * 会话控制器
 */
class Conversation extends Base
{
    /**
     * 获取会话列表
     */
    public function index(): Response
    {
        $userId = $this->getUserId();

        // 获取用户的所有会话，置顶的排在前面
        $conversations = ConversationModel::where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->whereOr(function ($q) use ($userId) {
                    $q->where('target_id', $userId)
                        ->where('target_type', ConversationModel::TARGET_USER);
                });
        })
            ->where('is_closed', 0)
            ->order('is_pinned', 'desc')
            ->order('pinned_at', 'desc')
            ->order('last_message_time', 'desc')
            ->select();

        $result = [];
        foreach ($conversations as $conv) {
            $item = $this->formatConversation($conv, $userId);
            if ($item) {
                $result[] = $item;
            }
        }

        return $this->success($result);
    }

    /**
     * 创建或获取会话
     */
    public function create(): Response
    {
        $userId = $this->getUserId();
        $type = (int)input('post.type', ConversationModel::TYPE_PRIVATE);
        $targetId = (int)input('post.targetId', 0);
        $goodsId = input('post.goodsId') ? (int)input('post.goodsId') : null;
        $scene = (int)input('post.scene', ConversationModel::SCENE_PRE_SALE);
        $orderId = input('post.orderId') ? (int)input('post.orderId') : null;

        // 不能跟自己聊天
        if ($type === ConversationModel::TYPE_PRIVATE && $targetId === $userId) {
            return $this->error('Cannot chat with yourself');
        }

        // 检查商品所有者
        $goodsOwnerType = ConversationModel::OWNER_SELLER;
        $goodsOwnerId = 0;
        $goods = null;
        $isOfficialSellerGoods = false;

        if ($goodsId) {
            $goods = Goods::find($goodsId);
            if ($goods) {
                // 平台商品 user_id = 0
                if ($goods->user_id == 0) {
                    $goodsOwnerType = ConversationModel::OWNER_PLATFORM;
                    $goodsOwnerId = 0;
                } else {
                    $goodsOwnerType = ConversationModel::OWNER_SELLER;
                    $goodsOwnerId = $goods->user_id;

                    // 检查商品发布者是否为官方小号
                    $goodsOwner = User::find($goods->user_id);
                    if ($goodsOwner && $goodsOwner->is_official) {
                        $isOfficialSellerGoods = true;
                    }
                }
            }
        }

        // 确定目标类型和ID
        // 官方小号商品：用户联系时转发到客服，但显示官方小号信息

        if ($type === ConversationModel::TYPE_SERVICE || $goodsOwnerType === ConversationModel::OWNER_PLATFORM) {
            // 客服会话或平台商品咨询 - 尝试分配在线客服
            $service = $this->assignService();
            if ($service) {
                // 有在线客服，分配给该客服
                $targetId = $service->id;
            } else {
                // 没有在线客服，target_id = 0 表示由后台统一处理
                $targetId = 0;
            }
            $targetType = ConversationModel::TARGET_SERVICE;
            $type = ConversationModel::TYPE_SERVICE;
        } elseif ($isOfficialSellerGoods) {
            // 官方小号商品：创建客服会话，但记录原始卖家ID用于显示
            $service = $this->assignService();
            if ($service) {
                $targetId = $service->id;
            } else {
                $targetId = 0;
            }
            $targetType = ConversationModel::TARGET_SERVICE;
            $type = ConversationModel::TYPE_SERVICE;
            // goodsOwnerId 已在上面设置为 goods->user_id
        } else {
            // 私聊 - 验证目标用户存在
            // 如果是商品咨询，目标是卖家
            if ($goodsId && $goods) {
                $targetId = $goods->user_id;
            }
            $targetUser = User::find($targetId);
            if (!$targetUser) {
                return $this->error('User not found');
            }
            $targetType = ConversationModel::TARGET_USER;
        }

        // 获取或创建会话
        $conversation = ConversationModel::getOrCreate(
            $userId,
            $type,
            $targetId,
            $targetType,
            $goodsId,
            $scene,
            $goodsOwnerType,
            $goodsOwnerId,
            $orderId
        );

        // 格式化返回
        $result = $this->formatConversation($conversation, $userId);

        // 如果是新会话且有商品，发送商品卡片消息
        if ($goodsId && !$conversation->last_message) {
            if (!$goods) {
                $goods = Goods::find($goodsId);
            }
            if ($goods) {
                $firstImage = $goods->getFirstImage();
                $goodsTitle = $goods->getTranslated('title', $this->locale);
                Message::createMessage(
                    $conversation->id,
                    $userId,
                    Message::SENDER_USER,
                    '咨询商品',
                    Message::TYPE_GOODS,
                    [
                        'goodsId' => $goods->id,
                        'title' => $goodsTitle,
                        'price' => $goods->price,
                        'image' => $firstImage,
                    ]
                );
                $conversation->updateLastMessage('[商品] ' . $goodsTitle, 'user');
            }
        }

        return $this->success(['conversation' => $result]);
    }

    /**
     * 获取会话消息
     */
    public function messages(int $id): Response
    {
        $userId = $this->getUserId();
        $page = (int)input('page', 1);
        $pageSize = (int)input('pageSize', 20);
        $lastId = input('lastId') ? (int)input('lastId') : null;

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        $query = Message::where('conversation_id', $id)
            ->order('id', 'desc');

        if ($lastId) {
            $query->where('id', '<', $lastId);
        }

        $total = (clone $query)->count();
        $messages = $query->limit($pageSize)->select();

        // 格式化消息列表
        $list = [];
        foreach ($messages as $msg) {
            $list[] = $this->formatMessage($msg);
        }

        return $this->success([
            'list' => $list,
            'total' => $total,
        ]);
    }

    /**
     * 格式化消息数据
     */
    protected function formatMessage(Message $msg): array
    {
        $data = [
            'id' => $msg->id,
            'conversationId' => $msg->conversation_id,
            'senderId' => $msg->sender_id,
            'senderType' => $msg->sender_type,
            'content' => $msg->content,
            'type' => (int) $msg->getData('type'),
            'extra' => $msg->extra ?? [],
            'isRead' => (bool) $msg->is_read,
            'createdAt' => $msg->created_at,
            'quoteId' => $msg->quote_id,
        ];

        // 如果有引用消息，获取引用消息的摘要
        if ($msg->quote_id) {
            $quotedMsg = Message::find($msg->quote_id);
            if ($quotedMsg) {
                $data['quotedMessage'] = [
                    'id' => $quotedMsg->id,
                    'senderId' => $quotedMsg->sender_id,
                    'senderType' => $quotedMsg->sender_type,
                    'content' => $this->getQuotePreview($quotedMsg),
                    'type' => (int) $quotedMsg->getData('type'),
                ];
            }
        }

        return $data;
    }

    /**
     * 获取引用消息预览文本
     */
    protected function getQuotePreview(Message $msg): string
    {
        $type = (int) $msg->getData('type');
        switch ($type) {
            case Message::TYPE_TEXT:
                // 截取前50个字符
                return mb_strlen($msg->content) > 50
                    ? mb_substr($msg->content, 0, 50) . '...'
                    : $msg->content;
            case Message::TYPE_IMAGE:
                return '[图片]';
            case Message::TYPE_GOODS:
                return '[商品]';
            case Message::TYPE_ORDER:
                return '[订单]';
            case Message::TYPE_RECALLED:
                return '[已撤回]';
            default:
                return '[消息]';
        }
    }

    /**
     * 发送消息
     */
    public function send(int $id): Response
    {
        $userId = $this->getUserId();
        $content = input('post.content', '');
        $type = (int)input('post.type', Message::TYPE_TEXT);
        $extra = input('post.extra');
        $quoteId = input('post.quoteId') ? (int)input('post.quoteId') : null;

        if (empty(trim($content)) && $type === Message::TYPE_TEXT) {
            return $this->error('Message content required');
        }

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        if ($conversation->is_closed) {
            return $this->error('Conversation closed');
        }

        // 如果有引用消息，验证引用消息属于该会话
        if ($quoteId) {
            $quotedMsg = Message::where('id', $quoteId)
                ->where('conversation_id', $id)
                ->find();
            if (!$quotedMsg) {
                $quoteId = null; // 无效引用，忽略
            }
        }

        // 创建消息
        $message = Message::createMessage(
            $id,
            $userId,
            Message::SENDER_USER,
            $content,
            $type,
            $extra ? (is_array($extra) ? $extra : json_decode($extra, true)) : null,
            $quoteId
        );

        // 更新会话最后消息
        $lastMsg = $type === Message::TYPE_TEXT ? $content : '[' . $this->getMessageTypeText($type) . ']';
        $conversation->updateLastMessage($lastMsg, 'user');

        return $this->success($this->formatMessage($message));
    }

    /**
     * 标记已读
     */
    public function read(int $id): Response
    {
        $userId = $this->getUserId();

        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        // 判断是发起者还是目标
        if ($conversation->user_id === $userId) {
            $conversation->markAsRead('user');
        } else {
            $conversation->markAsRead('target');
        }

        // 标记消息已读
        Message::where('conversation_id', $id)
            ->where('sender_id', '<>', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return $this->success([]);
    }

    /**
     * 格式化会话数据
     */
    protected function formatConversation(ConversationModel $conv, int $currentUserId): ?array
    {
        // 判断当前用户是发起者还是目标
        $isInitiator = $conv->user_id === $currentUserId;
        $convType = $conv->getConvType();  // 使用方法获取 type 避免冲突

        // 获取对方信息
        // 检查是否是官方小号商品的客服会话（显示官方小号信息而不是客服信息）
        $isOfficialSellerChat = false;
        $officialUser = null;

        if ($convType === ConversationModel::TYPE_SERVICE && $conv->goods_owner_id && $conv->goods_id) {
            // 查询商品发布者是否为官方小号
            $officialUser = User::where('id', $conv->goods_owner_id)
                ->where('is_official', 1)
                ->find();
            if ($officialUser) {
                $isOfficialSellerChat = true;
            }
        }

        if ($isOfficialSellerChat && $officialUser && $conv->goods_id) {
            // 官方小号商品咨询（有关联商品时）：显示官方小号的用户信息
            $targetName = $officialUser->nickname;
            $targetAvatar = UrlHelper::getFullUrl($officialUser->avatar);
        } elseif ($convType === ConversationModel::TYPE_SERVICE) {
            // 普通客服会话
            $service = null;
            if ($conv->target_id > 0) {
                $service = CustomerService::with(['admin'])->find($conv->target_id);
            }
            // 如果没有指定客服或找不到，获取随机在线客服显示
            if (!$service) {
                $service = CustomerService::where('is_enabled', 1)
                    ->where('status', CustomerService::STATUS_ONLINE)
                    ->find();
            }
            // 如果还是没有，获取任意启用的客服
            if (!$service) {
                $service = CustomerService::where('is_enabled', 1)->find();
            }
            // 客服名称：检查是否有当前语言的翻译，否则使用多语言默认文本
            if ($service) {
                // 转换locale格式以匹配数据库存储格式
                $dbLocale = CustomerService::normalizeLocale($this->locale);
                // 直接查询是否有精确匹配当前语言的翻译
                $translation = $service->translations()->where('locale', $dbLocale)->find();
                $targetName = ($translation && $translation->name) ? $translation->name : $this->getCustomerServiceName();
            } else {
                $targetName = $this->getCustomerServiceName();
            }
            $targetAvatar = $service ? UrlHelper::getFullUrl($service->avatar ?? '') : '';
        } else {
            // 私聊
            if ($isInitiator) {
                $target = User::find($conv->target_id);
            } else {
                $target = User::find($conv->user_id);
            }

            if (!$target) {
                return null;
            }

            $targetName = $target->nickname;
            $targetAvatar = UrlHelper::getFullUrl($target->avatar);
        }

        // 获取商品信息
        $goodsInfo = null;
        if ($conv->goods_id) {
            $goods = Goods::find($conv->goods_id);
            if ($goods) {
                $goodsInfo = [
                    'id' => $goods->id,
                    'title' => $goods->getTranslated('title', $this->locale),
                    'price' => $goods->price,
                    'image' => $goods->getFirstImage(),
                ];
            }
        }

        return [
            'id' => $conv->id,
            'type' => $convType,
            'targetId' => $isInitiator ? $conv->target_id : $conv->user_id,
            'targetType' => $conv->target_type,
            'targetName' => $targetName,
            'targetAvatar' => $targetAvatar,
            'lastMessage' => $conv->last_message,
            'lastMessageTime' => $conv->last_message_time,
            'unreadCount' => $isInitiator ? $conv->user_unread : $conv->target_unread,
            'goodsId' => $conv->goods_id,
            'goodsInfo' => $goodsInfo,
            'isPinned' => (bool) ($conv->is_pinned ?? 0),
        ];
    }

    /**
     * 验证会话访问权限
     */
    protected function getConversationWithAccess(int $id, int $userId): ?ConversationModel
    {
        $conversation = ConversationModel::find($id);
        if (!$conversation) {
            return null;
        }

        // 验证用户是会话的参与者
        $isParticipant = $conversation->user_id === $userId ||
            ($conversation->target_type === ConversationModel::TARGET_USER && $conversation->target_id === $userId);

        if (!$isParticipant) {
            return null;
        }

        return $conversation;
    }

    /**
     * 分配客服
     */
    protected function assignService(): ?CustomerService
    {
        // 查找在线且未达到最大会话数的客服
        $service = CustomerService::where('status', CustomerService::STATUS_ONLINE)
            ->where('is_enabled', 1)
            ->whereRaw('current_sessions < max_sessions')
            ->order('current_sessions', 'asc')
            ->find();

        if ($service) {
            // 增加当前会话数
            $service->current_sessions = $service->current_sessions + 1;
            $service->save();
        }

        return $service;
    }

    /**
     * 获取消息类型文本
     */
    protected function getMessageTypeText(int $type): string
    {
        $types = [
            Message::TYPE_TEXT => '文本',
            Message::TYPE_IMAGE => '图片',
            Message::TYPE_GOODS => '商品',
            Message::TYPE_ORDER => '订单',
            Message::TYPE_SYSTEM => '系统消息',
        ];
        return $types[$type] ?? '消息';
    }

    /**
     * 获取未读消息总数
     */
    public function unreadCount(): Response
    {
        $userId = $this->getUserId();

        // 统计用户作为发起者的会话未读数
        $initiatorUnread = ConversationModel::where('user_id', $userId)
            ->where('is_closed', 0)
            ->sum('user_unread');

        // 统计用户作为目标的会话未读数
        $targetUnread = ConversationModel::where('target_id', $userId)
            ->where('target_type', ConversationModel::TARGET_USER)
            ->where('is_closed', 0)
            ->sum('target_unread');

        $total = (int)$initiatorUnread + (int)$targetUnread;

        return $this->success([
            'total' => $total,
        ]);
    }

    /**
     * 轮询获取新消息
     */
    public function poll(int $id): Response
    {
        $userId = $this->getUserId();
        $lastMsgId = (int)input('lastMsgId', 0);

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        // 获取比 lastMsgId 更新的消息
        $messages = Message::where('conversation_id', $id)
            ->where('id', '>', $lastMsgId)
            ->order('id', 'asc')
            ->select();

        $list = [];
        foreach ($messages as $msg) {
            $list[] = $this->formatMessage($msg);
        }

        // 如果有新消息且不是自己发的，标记为已读
        if (count($list) > 0) {
            Message::where('conversation_id', $id)
                ->where('id', '>', $lastMsgId)
                ->where('sender_id', '<>', $userId)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);

            // 更新会话未读数
            if ($conversation->user_id === $userId) {
                $conversation->user_unread = 0;
            } else {
                $conversation->target_unread = 0;
            }
            $conversation->save();
        }

        return $this->success([
            'messages' => $list,
            'hasNew' => count($list) > 0,
        ]);
    }

    /**
     * 获取客服在线状态
     */
    public function serviceStatus(): Response
    {
        // 查询在线客服数量
        $onlineCount = CustomerService::where('status', CustomerService::STATUS_ONLINE)
            ->where('is_enabled', 1)
            ->count();

        // 查询等待中的会话数
        $waitCount = ConversationModel::where('type', ConversationModel::TYPE_SERVICE)
            ->where('is_closed', 0)
            ->whereNull('last_message_time')
            ->count();

        return $this->success([
            'online' => $onlineCount > 0,
            'onlineCount' => $onlineCount,
            'waitCount' => $waitCount,
        ]);
    }

    /**
     * 举报用户（创建工单）
     */
    public function report(int $id): Response
    {
        $userId = $this->getUserId();
        $reason = input('post.reason', '');

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        // 只能举报私聊会话中的对方
        if ($conversation->getConvType() !== ConversationModel::TYPE_PRIVATE) {
            return $this->error('Can only report private chat users');
        }

        // 获取被举报用户ID
        $reportedUserId = $conversation->user_id === $userId
            ? $conversation->target_id
            : $conversation->user_id;

        // 获取被举报用户信息
        $reportedUser = User::find($reportedUserId);
        if (!$reportedUser) {
            return $this->error('User not found');
        }

        // 根据语言生成工单标题和内容
        $ticketTexts = $this->getReportTicketTexts($reportedUser->nickname);

        // 创建举报工单
        $ticket = new SupportTicket();
        $ticket->ticket_no = SupportTicket::generateNo();
        $ticket->user_id = $userId;
        $ticket->category = 'report'; // 举报分类
        $ticket->subject = $ticketTexts['subject'];
        $ticket->content = $reason ?: $ticketTexts['content'];
        $ticket->priority = SupportTicket::PRIORITY_NORMAL;
        $ticket->status = SupportTicket::STATUS_PENDING;
        $ticket->save();

        return $this->success([], 'Report submitted');
    }

    /**
     * 根据语言获取举报工单文案
     */
    protected function getReportTicketTexts(string $nickname): array
    {
        $texts = [
            'zh-tw' => [
                'subject' => '舉報用戶: ' . $nickname,
                'content' => '用戶通過聊天界面舉報了該用戶',
            ],
            'ja-jp' => [
                'subject' => 'ユーザーを報告: ' . $nickname,
                'content' => 'ユーザーがチャット画面からこのユーザーを報告しました',
            ],
            'en-us' => [
                'subject' => 'Report User: ' . $nickname,
                'content' => 'User reported this user via chat interface',
            ],
        ];

        $locale = strtolower($this->locale);
        return $texts[$locale] ?? $texts['en-us'];
    }

    /**
     * 拉黑用户
     */
    public function block(int $id): Response
    {
        $userId = $this->getUserId();

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        // 只能拉黑私聊会话中的对方
        if ($conversation->getConvType() !== ConversationModel::TYPE_PRIVATE) {
            return $this->error('Can only block private chat users');
        }

        // 获取要拉黑的用户ID
        $blockedUserId = $conversation->user_id === $userId
            ? $conversation->target_id
            : $conversation->user_id;

        // 添加拉黑记录
        UserBlock::addBlock($userId, $blockedUserId);

        // 关闭会话
        $conversation->is_closed = 1;
        $conversation->save();

        return $this->success([], 'User blocked');
    }

    /**
     * 清空聊天记录
     */
    public function clear(int $id): Response
    {
        $userId = $this->getUserId();

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        // 删除该会话的所有消息
        Message::where('conversation_id', $id)->delete();

        // 清空最后消息
        $conversation->last_message = '';
        $conversation->last_message_time = null;
        $conversation->user_unread = 0;
        $conversation->target_unread = 0;
        $conversation->save();

        return $this->success([], 'Chat cleared');
    }

    /**
     * 撤回消息
     */
    public function recallMessage(int $id, int $msgId): Response
    {
        $userId = $this->getUserId();

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        // 获取消息
        $message = Message::where('id', $msgId)
            ->where('conversation_id', $id)
            ->find();

        if (!$message) {
            return $this->error('Message not found', 404);
        }

        // 检查是否可以撤回
        if (!$message->canRecall($userId)) {
            return $this->error('Cannot recall message');
        }

        // 执行撤回
        $message->recall();

        // 如果是最后一条消息，更新会话的最后消息
        if ($conversation->last_message_time === $message->created_at) {
            $conversation->last_message = lang('Message recalled');
            $conversation->save();
        }

        return $this->success($this->formatMessage($message), 'Message recalled');
    }

    /**
     * 删除消息（仅删除自己可见）
     */
    public function deleteMessage(int $id, int $msgId): Response
    {
        $userId = $this->getUserId();

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        // 获取消息
        $message = Message::where('id', $msgId)
            ->where('conversation_id', $id)
            ->find();

        if (!$message) {
            return $this->error('Message not found', 404);
        }

        // 只能删除自己发送的消息
        if ($message->sender_id !== $userId || $message->sender_type !== Message::SENDER_USER) {
            return $this->error('Can only delete own messages');
        }

        // 物理删除消息
        $message->delete();

        return $this->success([], 'Message deleted');
    }

    /**
     * 获取多语言客服名称
     */
    protected function getCustomerServiceName(): string
    {
        $names = [
            'zh-tw' => '線上客服',
            'ja-jp' => 'カスタマーサービス',
            'en-us' => 'Customer Service',
        ];

        $locale = strtolower($this->locale);
        return $names[$locale] ?? $names['en-us'];
    }

    /**
     * 批量删除消息
     */
    public function deleteMessages(int $id): Response
    {
        $userId = $this->getUserId();
        $msgIds = input('post.msgIds', []);

        if (empty($msgIds) || !is_array($msgIds)) {
            return $this->error('Please select messages to delete');
        }

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        // 只删除自己发送的消息
        $deleted = Message::where('conversation_id', $id)
            ->whereIn('id', $msgIds)
            ->where('sender_id', $userId)
            ->where('sender_type', Message::SENDER_USER)
            ->delete();

        return $this->success(['deleted' => $deleted], 'Message deleted');
    }

    /**
     * 置顶/取消置顶会话
     */
    public function pin(int $id): Response
    {
        $userId = $this->getUserId();

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        // 切换置顶状态
        if ($conversation->is_pinned) {
            $conversation->is_pinned = 0;
            $conversation->pinned_at = null;
        } else {
            $conversation->is_pinned = 1;
            $conversation->pinned_at = date('Y-m-d H:i:s');
        }
        $conversation->save();

        return $this->success([
            'isPinned' => (bool) $conversation->is_pinned,
        ]);
    }

    /**
     * 删除会话
     */
    public function delete(int $id): Response
    {
        $userId = $this->getUserId();

        // 验证会话权限
        $conversation = $this->getConversationWithAccess($id, $userId);
        if (!$conversation) {
            return $this->error('Conversation not found', 404);
        }

        // 删除会话的所有消息
        Message::where('conversation_id', $id)->delete();

        // 删除会话
        $conversation->delete();

        return $this->success([], 'Message deleted');
    }
}
