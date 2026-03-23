<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 用户工单模型
 */
class SupportTicket extends Model
{
    protected $name = 'support_tickets';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'service_id' => 'integer',
        'related_order_id' => 'integer',
        'related_goods_id' => 'integer',
        'priority' => 'integer',
        'status' => 'integer',
        'is_rated' => 'integer',
        'rating' => 'integer',
        'images' => 'json',
    ];

    // 自动追加获取器字段
    protected $append = [
        'status_text',
        'priority_text',
        'category_text',
        'sub_category_text',
    ];

    // 状态常量
    const STATUS_PENDING = 0;    // 待处理
    const STATUS_PROCESSING = 1; // 处理中
    const STATUS_REPLIED = 2;    // 已回复
    const STATUS_RESOLVED = 3;   // 已解决
    const STATUS_CLOSED = 4;     // 已关闭

    const STATUS_MAP = [
        self::STATUS_PENDING => '待处理',
        self::STATUS_PROCESSING => '处理中',
        self::STATUS_REPLIED => '已回复',
        self::STATUS_RESOLVED => '已解决',
        self::STATUS_CLOSED => '已关闭',
    ];

    // 优先级常量
    const PRIORITY_NORMAL = 1;
    const PRIORITY_URGENT = 2;
    const PRIORITY_CRITICAL = 3;

    const PRIORITY_MAP = [
        self::PRIORITY_NORMAL => '普通',
        self::PRIORITY_URGENT => '紧急',
        self::PRIORITY_CRITICAL => '非常紧急',
    ];

    // 问题分类
    const CATEGORY_ORDER = 'order';
    const CATEGORY_REFUND = 'refund';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_ACCOUNT = 'account';
    const CATEGORY_REPORT = 'report';
    const CATEGORY_OTHER = 'other';

    const CATEGORY_MAP = [
        self::CATEGORY_ORDER => '订单问题',
        self::CATEGORY_REFUND => '退货退款',
        self::CATEGORY_PAYMENT => '支付问题',
        self::CATEGORY_ACCOUNT => '账号问题',
        self::CATEGORY_REPORT => '举报用户',
        self::CATEGORY_OTHER => '其他问题',
    ];

    // 子分类映射
    const SUB_CATEGORY_MAP = [
        // 举报原因
        'counterfeit' => '仿冒品或盗版商品',
        'prohibited' => '禁止刊登的商品',
        'misleading' => '误导性描述或图片',
        'infringement' => '侵犯知识产权',
        'fraud' => '欺诈或诈骗行为',
        'spam' => '垃圾信息或骚扰',
        'report_member' => '举报会员',
        // 订单问题
        'order_status' => '订单状态查询',
        'not_received' => '未收到货',
        'wrong_item' => '商品与描述不符',
        'not_shipped' => '卖家不发货',
        'order_other' => '其他订单问题',
        // 退货退款
        'return_apply' => '如何申请退货',
        'refund_status' => '退款进度查询',
        'return_shipping' => '退货运费问题',
        'refund_other' => '其他退款问题',
        // 支付问题
        'pay_failed' => '付款失败',
        'double_charge' => '重复扣款',
        'refund_delay' => '退款未到账',
        'payment_other' => '其他支付问题',
        // 账号问题
        'login_issue' => '无法登录',
        'password_reset' => '重置密码',
        'account_security' => '账号安全',
        'account_other' => '其他账号问题',
        // 举报
        'report_seller' => '举报卖家',
        'report_buyer' => '举报买家',
        'report_goods' => '举报商品',
        'report_other' => '其他举报',
        // 其他
        'other_question' => '其他问题',
        'suggestion' => '建议反馈',
        'cooperation' => '商务合作',
        'other' => '其他原因',
    ];

    /**
     * 生成工单编号
     */
    public static function generateNo(): string
    {
        return 'TK' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联客服
     */
    public function service()
    {
        return $this->belongsTo(CustomerService::class, 'service_id', 'id');
    }

    /**
     * 关联订单
     */
    public function relatedOrder()
    {
        return $this->belongsTo(Order::class, 'related_order_id', 'id');
    }

    /**
     * 关联商品
     */
    public function goods()
    {
        return $this->belongsTo(Goods::class, 'related_goods_id', 'id');
    }

    /**
     * 关联回复
     */
    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id', 'id');
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::STATUS_MAP[$data['status']] ?? '未知';
    }

    /**
     * 获取优先级文本
     */
    public function getPriorityTextAttr($value, $data)
    {
        return self::PRIORITY_MAP[$data['priority']] ?? '未知';
    }

    /**
     * 获取分类文本
     */
    public function getCategoryTextAttr($value, $data)
    {
        return self::CATEGORY_MAP[$data['category']] ?? '未知';
    }

    /**
     * 获取子分类文本
     */
    public function getSubCategoryTextAttr($value, $data)
    {
        $subCategory = $data['sub_category'] ?? '';
        if (empty($subCategory)) {
            return '';
        }
        return self::SUB_CATEGORY_MAP[$subCategory] ?? $subCategory;
    }
}
