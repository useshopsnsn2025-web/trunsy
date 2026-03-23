<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Conversation as ConversationModel;
use app\common\model\Message;
use app\common\model\User;
use app\common\model\Goods;
use app\common\model\CustomerService;

/**
 * 管理后台会话控制器
 */
class Conversation extends Base
{
    /**
     * 获取当前管理员对应的客服ID
     */
    protected function getServiceId(): int
    {
        if (!$this->adminId) {
            return 0;
        }
        $service = CustomerService::where('admin_id', $this->adminId)->find();
        return $service ? $service->id : 0;
    }

    /**
     * 获取会话列表（客服会话）
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $scene = input('scene'); // 1=售前, 2=售后
        $status = input('status'); // unread=未读, all=全部
        $assignType = input('assign'); // mine=我的, unassigned=未分配, all=全部

        $serviceId = $this->getServiceId();

        // 查询所有客服会话（包括平台商品和官方小号商品的会话）
        $query = ConversationModel::where('type', ConversationModel::TYPE_SERVICE)
            ->where('is_closed', 0)
            ->order('last_message_time', 'desc');

        // 根据分配类型筛选
        if ($assignType === 'mine' && $serviceId > 0) {
            // 只看分配给自己的会话
            $query->where('target_id', $serviceId)
                ->where('target_type', ConversationModel::TARGET_SERVICE);
        } elseif ($assignType === 'unassigned') {
            // 只看未分配的会话（等待接入）
            $query->where('target_id', 0);
        } else {
            // 默认：看到分配给自己的 + 未分配的
            if ($serviceId > 0) {
                $query->where(function ($q) use ($serviceId) {
                    $q->where('target_id', $serviceId)
                        ->whereOr('target_id', 0);
                });
            }
            // 如果不是客服身份，可以看到所有平台会话
        }

        // 筛选场景
        if ($scene) {
            $query->where('scene', (int)$scene);
        }

        // 筛选未读
        if ($status === 'unread') {
            $query->where('target_unread', '>', 0);
        }

        $total = (clone $query)->count();
        $conversations = $query->page($page, $pageSize)->select();

        $list = [];
        foreach ($conversations as $conv) {
            $list[] = $this->formatConversation($conv);
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 获取会话消息
     */
    public function messages(int $id): Response
    {
        $pageSize = (int)input('pageSize', 50);
        $lastId = input('lastId') ? (int)input('lastId') : null;

        // 验证会话存在且是客服会话
        $conversation = ConversationModel::where('id', $id)
            ->where('type', ConversationModel::TYPE_SERVICE)
            ->find();

        if (!$conversation) {
            return $this->error('会话不存在', 404);
        }

        $query = Message::where('conversation_id', $id)
            ->order('id', 'desc');

        if ($lastId) {
            $query->where('id', '<', $lastId);
        }

        $messages = $query->limit($pageSize)->select();

        $list = [];
        foreach ($messages as $msg) {
            $list[] = $this->formatMessage($msg);
        }

        return $this->success([
            'list' => $list,
            'conversation' => $this->formatConversation($conversation),
        ]);
    }

    /**
     * 接入会话（客服主动认领未分配的会话）
     */
    public function claim(int $id): Response
    {
        $serviceId = $this->getServiceId();

        if ($serviceId <= 0) {
            return $this->error('您还不是客服人员', 403);
        }

        // 查询客服信息，检查是否还能接更多会话
        $service = CustomerService::find($serviceId);
        if (!$service) {
            return $this->error('客服信息不存在', 404);
        }

        if ($service->current_sessions >= $service->max_sessions) {
            return $this->error('您的会话数已达上限');
        }

        // 验证会话存在且未分配
        $conversation = ConversationModel::where('id', $id)
            ->where('type', ConversationModel::TYPE_SERVICE)
            ->where('target_id', 0)
            ->find();

        if (!$conversation) {
            return $this->error('会话不存在或已被其他客服接入', 404);
        }

        // 分配给当前客服
        $conversation->target_id = $serviceId;
        $conversation->target_type = ConversationModel::TARGET_SERVICE;
        $conversation->save();

        // 更新客服当前会话数
        $service->current_sessions = $service->current_sessions + 1;
        $service->save();

        return $this->success([
            'conversation' => $this->formatConversation($conversation),
        ], '会话接入成功');
    }

    /**
     * 发送消息（平台客服身份）
     */
    public function send(int $id): Response
    {
        $serviceId = $this->getServiceId();
        $content = input('post.content', '');
        $type = (int)input('post.type', Message::TYPE_TEXT);

        if (empty(trim($content)) && $type === Message::TYPE_TEXT) {
            return $this->error('消息内容不能为空');
        }

        // 验证会话
        $conversation = ConversationModel::where('id', $id)
            ->where('type', ConversationModel::TYPE_SERVICE)
            ->find();

        if (!$conversation) {
            return $this->error('会话不存在', 404);
        }

        if ($conversation->is_closed) {
            return $this->error('会话已关闭');
        }

        // 如果会话未分配，自动分配给当前客服
        if ($conversation->target_id === 0 && $serviceId > 0) {
            $service = CustomerService::find($serviceId);
            if ($service && $service->current_sessions < $service->max_sessions) {
                $conversation->target_id = $serviceId;
                $conversation->target_type = ConversationModel::TARGET_SERVICE;
                $service->current_sessions = $service->current_sessions + 1;
                $service->save();
            }
        }

        // 创建消息（以客服身份）
        $message = Message::createMessage(
            $id,
            $serviceId > 0 ? $serviceId : 0, // 客服ID，0表示平台统一回复
            Message::SENDER_SERVICE,
            $content,
            $type
        );

        // 更新会话最后消息
        $conversation->updateLastMessage($content, 'service');

        return $this->success($this->formatMessage($message));
    }

    /**
     * 轮询获取新消息
     */
    public function poll(int $id): Response
    {
        $lastMsgId = (int)input('lastMsgId', 0);

        // 验证会话
        $conversation = ConversationModel::where('id', $id)
            ->where('type', ConversationModel::TYPE_SERVICE)
            ->find();

        if (!$conversation) {
            return $this->error('会话不存在', 404);
        }

        // 获取新消息
        $messages = Message::where('conversation_id', $id)
            ->where('id', '>', $lastMsgId)
            ->order('id', 'asc')
            ->select();

        $list = [];
        foreach ($messages as $msg) {
            $list[] = $this->formatMessage($msg);
        }

        // 标记用户发来的消息为已读
        if (count($list) > 0) {
            Message::where('conversation_id', $id)
                ->where('id', '>', $lastMsgId)
                ->where('sender_type', Message::SENDER_USER)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);

            // 清除平台未读数
            $conversation->target_unread = 0;
            $conversation->save();
        }

        return $this->success([
            'messages' => $list,
            'hasNew' => count($list) > 0,
        ]);
    }

    /**
     * 标记已读
     */
    public function read(int $id): Response
    {
        $conversation = ConversationModel::where('id', $id)
            ->where('type', ConversationModel::TYPE_SERVICE)
            ->find();

        if (!$conversation) {
            return $this->error('会话不存在', 404);
        }

        // 清除平台未读数
        $conversation->target_unread = 0;
        $conversation->save();

        // 标记消息已读
        Message::where('conversation_id', $id)
            ->where('sender_type', Message::SENDER_USER)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return $this->success([], '标记成功');
    }

    /**
     * 获取未读消息总数
     */
    public function unreadCount(): Response
    {
        // 统计所有客服会话的未读数
        $total = ConversationModel::where('type', ConversationModel::TYPE_SERVICE)
            ->where('is_closed', 0)
            ->sum('target_unread');

        // 统计未读会话数
        $unreadConversations = ConversationModel::where('type', ConversationModel::TYPE_SERVICE)
            ->where('is_closed', 0)
            ->where('target_unread', '>', 0)
            ->count();

        return $this->success([
            'total' => (int)$total,
            'conversations' => $unreadConversations,
        ]);
    }

    /**
     * 关闭会话
     */
    public function close(int $id): Response
    {
        $conversation = ConversationModel::where('id', $id)
            ->where('type', ConversationModel::TYPE_SERVICE)
            ->find();

        if (!$conversation) {
            return $this->error('会话不存在', 404);
        }

        // 如果会话分配给了客服，减少客服的当前会话数
        if ($conversation->target_id > 0 && $conversation->target_type === ConversationModel::TARGET_SERVICE) {
            $service = CustomerService::find($conversation->target_id);
            if ($service && $service->current_sessions > 0) {
                $service->current_sessions = $service->current_sessions - 1;
                $service->save();
            }
        }

        $conversation->is_closed = 1;
        $conversation->save();

        return $this->success([], '会话已关闭');
    }

    /**
     * 转接会话给其他客服
     */
    public function transfer(int $id): Response
    {
        $targetServiceId = (int)input('post.serviceId', 0);

        if ($targetServiceId <= 0) {
            return $this->error('请选择要转接的客服');
        }

        // 验证目标客服存在且在线
        $targetService = CustomerService::find($targetServiceId);
        if (!$targetService) {
            return $this->error('目标客服不存在', 404);
        }

        if ($targetService->status !== CustomerService::STATUS_ONLINE) {
            return $this->error('目标客服不在线');
        }

        if ($targetService->current_sessions >= $targetService->max_sessions) {
            return $this->error('目标客服会话数已达上限');
        }

        // 验证会话
        $conversation = ConversationModel::where('id', $id)
            ->where('type', ConversationModel::TYPE_SERVICE)
            ->find();

        if (!$conversation) {
            return $this->error('会话不存在', 404);
        }

        // 减少原客服的会话数
        if ($conversation->target_id > 0 && $conversation->target_type === ConversationModel::TARGET_SERVICE) {
            $oldService = CustomerService::find($conversation->target_id);
            if ($oldService && $oldService->current_sessions > 0) {
                $oldService->current_sessions = $oldService->current_sessions - 1;
                $oldService->save();
            }
        }

        // 分配给新客服
        $conversation->target_id = $targetServiceId;
        $conversation->target_type = ConversationModel::TARGET_SERVICE;
        $conversation->save();

        // 增加新客服的会话数
        $targetService->current_sessions = $targetService->current_sessions + 1;
        $targetService->save();

        // 发送系统消息
        Message::createMessage(
            $id,
            0,
            Message::SENDER_SYSTEM,
            '会话已转接给其他客服',
            Message::TYPE_SYSTEM
        );

        return $this->success([
            'conversation' => $this->formatConversation($conversation),
        ], '转接成功');
    }

    /**
     * 格式化会话数据
     */
    protected function formatConversation(ConversationModel $conv): array
    {
        // 获取用户信息
        $user = User::find($conv->user_id);

        // 获取商品信息
        $goodsInfo = null;
        if ($conv->goods_id) {
            $goods = Goods::find($conv->goods_id);
            if ($goods) {
                $goodsInfo = [
                    'id' => $goods->id,
                    'title' => $goods->getTranslated('title'),
                    'price' => $goods->price,
                    'image' => $goods->getFirstImage(),
                ];
            }
        }

        // 获取官方小号信息（如果是官方小号商品的会话）
        $officialUserInfo = null;
        if ($conv->goods_owner_id && $conv->goods_owner_type === ConversationModel::OWNER_SELLER) {
            $officialUser = User::where('id', $conv->goods_owner_id)
                ->where('is_official', 1)
                ->find();
            if ($officialUser) {
                $officialUserInfo = [
                    'id' => $officialUser->id,
                    'nickname' => $officialUser->nickname,
                    'avatar' => $officialUser->avatar,
                ];
            }
        }

        // 获取分配的客服信息
        $serviceInfo = null;
        $isAssigned = false;
        if ($conv->target_id > 0 && $conv->target_type === ConversationModel::TARGET_SERVICE) {
            $isAssigned = true;
            $service = CustomerService::with(['admin'])->find($conv->target_id);
            if ($service) {
                $serviceInfo = [
                    'id' => $service->id,
                    'name' => $service->name ?? ($service->admin->nickname ?? '客服'),
                    'avatar' => $service->avatar ?? '',
                ];
            }
        }

        // 将用户语言转换为后端使用的locale格式（统一使用小写格式：zh-tw, en-us, ja-jp）
        $userLanguage = $user ? $user->language : 'en-us';
        $langLower = strtolower($userLanguage);
        $localeMap = [
            'zh-cn' => 'zh-tw',   // 简体中文降级到繁体中文
            'zh-tw' => 'zh-tw',
            'en-us' => 'en-us',
            'en' => 'en-us',      // 兼容旧数据
            'ja-jp' => 'ja-jp',
            'ja' => 'ja-jp',      // 兼容旧数据
            'ko-kr' => 'ko-kr',
            'ko' => 'ko-kr',      // 兼容旧数据
        ];
        $userLocale = $localeMap[$langLower] ?? 'en-us';

        return [
            'id' => $conv->id,
            'type' => $conv->getConvType(),
            'scene' => (int)$conv->scene,
            'sceneText' => $conv->scene == ConversationModel::SCENE_PRE_SALE ? '售前咨询' : '售后服务',
            'userId' => $conv->user_id,
            'userName' => $user ? $user->nickname : '未知用户',
            'userAvatar' => $user ? $user->avatar : '',
            'userLanguage' => $userLanguage,
            'userLocale' => $userLocale,
            'lastMessage' => $conv->last_message,
            'lastMessageTime' => $conv->last_message_time,
            'unreadCount' => (int)$conv->target_unread,
            'goodsId' => $conv->goods_id,
            'goodsInfo' => $goodsInfo,
            'orderId' => $conv->order_id,
            'isClosed' => (bool)$conv->is_closed,
            'isAssigned' => $isAssigned,
            'serviceInfo' => $serviceInfo,
            'officialUserInfo' => $officialUserInfo, // 官方小号信息（如果有）
            'createdAt' => $conv->created_at,
        ];
    }

    /**
     * 格式化消息数据
     */
    protected function formatMessage(Message $msg): array
    {
        return [
            'id' => $msg->id,
            'conversationId' => $msg->conversation_id,
            'senderId' => $msg->sender_id,
            'senderType' => $msg->sender_type,
            'content' => $msg->content,
            'type' => (int)$msg->getData('type'),
            'extra' => $msg->extra ?? [],
            'isRead' => (bool)$msg->is_read,
            'createdAt' => $msg->created_at,
        ];
    }
}
