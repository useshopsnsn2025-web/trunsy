<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use think\facade\Cache;
use app\common\model\NotificationTemplate as NotificationTemplateModel;
use app\common\model\Language;
use app\common\service\NotificationService;

/**
 * 站内信模板管理控制器
 */
class NotificationTemplate extends Base
{
    /**
     * 获取模板列表（按类型分组）
     * @return Response
     */
    public function index(): Response
    {
        $channel = input('channel', 'message'); // 默认站内消息渠道

        // 获取所有模板
        $templates = NotificationTemplateModel::where('channel', $channel)
            ->order('type', 'asc')
            ->order('locale', 'asc')
            ->select()
            ->toArray();

        // 按类型分组
        $grouped = [];
        foreach ($templates as $template) {
            $type = $template['type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [
                    'type' => $type,
                    'type_name' => $this->getTypeName($type),
                    'category' => $template['category'],
                    'channel' => $template['channel'],
                    'locales' => [],
                    'translation_count' => 0,
                ];
            }
            $grouped[$type]['locales'][$template['locale']] = [
                'id' => $template['id'],
                'title' => $template['title'],
                'content' => $template['content'],
                'status' => $template['status'],
            ];
            $grouped[$type]['translation_count']++;
        }

        // 转为数组并添加状态标记
        $list = array_values($grouped);
        foreach ($list as &$item) {
            // 以英文模板的状态为准
            $item['status'] = $item['locales']['en-us']['status'] ?? 1;
        }

        return $this->success($list);
    }

    /**
     * 获取单个模板类型的详情（包含所有语言版本）
     * @param string $type 模板类型
     * @return Response
     */
    public function read(string $type): Response
    {
        $channel = input('channel', 'message');

        $templates = NotificationTemplateModel::where('type', $type)
            ->where('channel', $channel)
            ->select()
            ->toArray();

        if (empty($templates)) {
            return $this->error('Template not found', 404);
        }

        // 转换为按语言分组的格式
        $translations = [];
        $category = '';
        foreach ($templates as $template) {
            $translations[$template['locale']] = [
                'id' => $template['id'],
                'title' => $template['title'],
                'content' => $template['content'],
                'status' => $template['status'],
            ];
            $category = $template['category'];
        }

        $data = [
            'type' => $type,
            'type_name' => $this->getTypeName($type),
            'category' => $category,
            'channel' => $channel,
            'translations' => $translations,
            'available_locales' => $this->getAvailableLocales(),
            'available_variables' => $this->getAvailableVariables($type),
        ];

        return $this->success($data);
    }

    /**
     * 获取指定语言的模板翻译
     * @param string $type 模板类型
     * @param string $locale 语言代码
     * @return Response
     */
    public function getTranslation(string $type, string $locale): Response
    {
        $channel = input('channel', 'message');

        $template = NotificationTemplateModel::where('type', $type)
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->find();

        if ($template) {
            return $this->success($template->toArray());
        }

        // 返回空模板供创建
        return $this->success([
            'type' => $type,
            'channel' => $channel,
            'locale' => $locale,
            'title' => '',
            'content' => '',
            'status' => 1,
        ]);
    }

    /**
     * 保存模板翻译
     * @param string $type 模板类型
     * @param string $locale 语言代码
     * @return Response
     */
    public function saveTranslation(string $type, string $locale): Response
    {
        $channel = input('post.channel', 'message');
        $title = input('post.title', '');
        $content = input('post.content', '');
        $category = input('post.category', 'order');
        $status = input('post.status', 1);

        if (empty($title)) {
            return $this->error('Title is required');
        }
        if (empty($content)) {
            return $this->error('Content is required');
        }

        $template = NotificationTemplateModel::where('type', $type)
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->find();

        if ($template) {
            // 更新
            $template->title = $title;
            $template->content = $content;
            $template->category = $category;
            $template->status = (int)$status;
            $template->save();
        } else {
            // 创建
            $template = NotificationTemplateModel::create([
                'type' => $type,
                'channel' => $channel,
                'locale' => $locale,
                'category' => $category,
                'title' => $title,
                'content' => $content,
                'status' => (int)$status,
            ]);
        }

        // 清除缓存
        $this->clearTemplateCache($type, $channel);

        return $this->success($template->toArray(), 'Template saved');
    }

    /**
     * 删除模板翻译
     * @param string $type 模板类型
     * @param string $locale 语言代码
     * @return Response
     */
    public function deleteTranslation(string $type, string $locale): Response
    {
        $channel = input('channel', 'message');

        // 禁止删除英文（主语言）
        if ($locale === 'en-us') {
            return $this->error('Cannot delete primary language template');
        }

        $template = NotificationTemplateModel::where('type', $type)
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->find();

        if (!$template) {
            return $this->error('Template not found', 404);
        }

        $template->delete();

        // 清除缓存
        $this->clearTemplateCache($type, $channel);

        return $this->success(null, 'Template deleted');
    }

    /**
     * 切换模板状态
     * @param string $type 模板类型
     * @return Response
     */
    public function toggleStatus(string $type): Response
    {
        $channel = input('post.channel', 'message');
        $status = input('post.status');

        if ($status === null) {
            return $this->error('Status is required');
        }

        // 更新该类型所有语言版本的状态
        NotificationTemplateModel::where('type', $type)
            ->where('channel', $channel)
            ->update(['status' => (int)$status]);

        // 清除缓存
        $this->clearTemplateCache($type, $channel);

        return $this->success(null, 'Status updated');
    }

    /**
     * 预览模板
     * @param string $type 模板类型
     * @return Response
     */
    public function preview(string $type): Response
    {
        $channel = input('channel', 'message');
        $locale = input('locale', 'en-us');

        $template = NotificationTemplateModel::where('type', $type)
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->find();

        if (!$template) {
            return $this->error('Template not found', 404);
        }

        // 准备示例数据
        $sampleData = $this->getSampleData($type);

        // 渲染
        $title = $template->renderTitle($sampleData);
        $content = $template->renderContent($sampleData);

        return $this->success([
            'title' => $title,
            'content' => $content,
        ]);
    }

    /**
     * 获取可用模板类型列表
     * @return Response
     */
    public function types(): Response
    {
        return $this->success($this->getAllTypes());
    }

    /**
     * 创建新模板类型（同时创建三个语言版本）
     * @return Response
     */
    public function create(): Response
    {
        $type = input('post.type', '');
        $channel = input('post.channel', 'message');
        $category = input('post.category', 'order');
        $titleEn = input('post.title_en', '');
        $contentEn = input('post.content_en', '');
        $titleZh = input('post.title_zh', '');
        $contentZh = input('post.content_zh', '');
        $titleJa = input('post.title_ja', '');
        $contentJa = input('post.content_ja', '');

        if (empty($type)) {
            return $this->error('Type is required');
        }
        if (empty($titleEn) || empty($contentEn)) {
            return $this->error('English title and content are required');
        }

        // 检查类型是否已存在
        $exists = NotificationTemplateModel::where('type', $type)
            ->where('channel', $channel)
            ->find();
        if ($exists) {
            return $this->error('Template type already exists');
        }

        // 创建三个语言版本
        $locales = [
            'en-us' => ['title' => $titleEn, 'content' => $contentEn],
            'zh-tw' => ['title' => $titleZh ?: $titleEn, 'content' => $contentZh ?: $contentEn],
            'ja-jp' => ['title' => $titleJa ?: $titleEn, 'content' => $contentJa ?: $contentEn],
        ];

        foreach ($locales as $locale => $data) {
            NotificationTemplateModel::create([
                'type' => $type,
                'channel' => $channel,
                'locale' => $locale,
                'category' => $category,
                'title' => $data['title'],
                'content' => $data['content'],
                'status' => 1,
            ]);
        }

        return $this->success(null, 'Template created');
    }

    /**
     * 获取可用语言列表
     * @return array
     */
    protected function getAvailableLocales(): array
    {
        $languages = Language::where('is_active', 1)
            ->order('sort', 'desc')
            ->select();

        $locales = [];
        foreach ($languages as $lang) {
            $locales[] = [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
            ];
        }

        return $locales;
    }

    /**
     * 获取所有模板类型
     * @return array
     */
    protected function getAllTypes(): array
    {
        return [
            // 订单相关
            'order_created' => ['name' => '订单创建', 'category' => 'order'],
            'payment_success' => ['name' => '支付成功', 'category' => 'order'],
            'order_shipped' => ['name' => '订单发货', 'category' => 'order'],
            'order_delivered' => ['name' => '订单送达', 'category' => 'order'],
            'order_cancelled' => ['name' => '订单取消', 'category' => 'order'],
            'refund_success' => ['name' => '退款成功', 'category' => 'order'],
            'order_verified' => ['name' => '订单验证成功', 'category' => 'order'],
            'order_verified_full' => ['name' => '全款支付验证成功', 'category' => 'order'],
            'order_verified_cod' => ['name' => '货到付款验证成功', 'category' => 'order'],
            'order_verified_installment' => ['name' => '分期付款验证成功', 'category' => 'order'],
            'order_verify_failed' => ['name' => '订单验证失败', 'category' => 'important'],
            // 商品相关
            'goods_approved' => ['name' => '商品审核通过', 'category' => 'important'],
            'goods_rejected' => ['name' => '商品审核拒绝', 'category' => 'important'],
            'goods_sold' => ['name' => '商品售出通知', 'category' => 'order'],
            // 退货相关
            'return_requested' => ['name' => '新退货申请', 'category' => 'order'],
            'return_approved' => ['name' => '退货申请通过', 'category' => 'order'],
            'return_rejected' => ['name' => '退货申请拒绝', 'category' => 'important'],
            'return_shipped' => ['name' => '退货已寄出', 'category' => 'order'],
            'return_received' => ['name' => '退货已签收', 'category' => 'order'],
            // 账户相关
            'user_registered' => ['name' => '用户注册欢迎', 'category' => 'account'],
        ];
    }

    /**
     * 获取类型名称
     * @param string $type
     * @return string
     */
    protected function getTypeName(string $type): string
    {
        $types = $this->getAllTypes();
        return $types[$type]['name'] ?? $type;
    }

    /**
     * 获取类型对应的可用变量
     * @param string $type
     * @return array
     */
    protected function getAvailableVariables(string $type): array
    {
        // 通用变量
        $common = [
            'platform_name' => '平台名称',
            'platform_url' => '平台网址',
            'current_year' => '当前年份',
        ];

        // 订单相关变量
        $orderVars = [
            'order_no' => '订单号',
            'order_date' => '订单日期',
            'total_amount' => '订单总额',
            'currency' => '货币',
        ];

        // 商品相关变量
        $goodsVars = [
            'goods_title' => '商品名称',
            'goods_image' => '商品图片',
            'price' => '商品价格',
        ];

        // 用户相关变量
        $userVars = [
            'user_name' => '用户名',
            'nickname' => '昵称',
        ];

        // 退货相关变量
        $returnVars = [
            'return_no' => '退货单号',
            'refund_amount' => '退款金额',
            'reject_reason' => '拒绝原因',
        ];

        // 根据类型返回对应变量
        $typeVariables = [
            'order_created' => array_merge($common, $userVars, $orderVars, $goodsVars),
            'payment_success' => array_merge($common, $userVars, $orderVars),
            'order_shipped' => array_merge($common, $userVars, $orderVars, ['tracking_no' => '物流单号', 'carrier_name' => '物流公司']),
            'order_delivered' => array_merge($common, $userVars, $orderVars),
            'order_cancelled' => array_merge($common, $userVars, $orderVars),
            'refund_success' => array_merge($common, $userVars, $orderVars, ['refund_amount' => '退款金额']),
            'order_verified' => array_merge($common, $userVars, $orderVars),
            'order_verified_full' => array_merge($common, $userVars, $orderVars),
            'order_verified_cod' => array_merge($common, $userVars, $orderVars),
            'order_verified_installment' => array_merge($common, $userVars, $orderVars, ['installment_periods' => '分期期数', 'installment_period_amount' => '每期金额']),
            'order_verify_failed' => array_merge($common, $userVars, $orderVars, ['fail_reason' => '失败原因', 'fail_message' => '失败描述']),
            'goods_approved' => array_merge($common, $userVars, $goodsVars),
            'goods_rejected' => array_merge($common, $userVars, $goodsVars, ['reject_reason' => '拒绝原因']),
            'goods_sold' => array_merge($common, $userVars, $orderVars, $goodsVars),
            'return_requested' => array_merge($common, $userVars, $orderVars, $returnVars),
            'return_approved' => array_merge($common, $userVars, $orderVars, $returnVars),
            'return_rejected' => array_merge($common, $userVars, $orderVars, $returnVars),
            'return_shipped' => array_merge($common, $userVars, $orderVars, $returnVars),
            'return_received' => array_merge($common, $userVars, $orderVars, $returnVars),
            'user_registered' => array_merge($common, $userVars),
        ];

        return $typeVariables[$type] ?? $common;
    }

    /**
     * 获取示例数据
     * @param string $type
     * @return array
     */
    protected function getSampleData(string $type): array
    {
        return [
            'platform_name' => 'TURNSY',
            'platform_url' => 'https://www.turnsysg.com',
            'current_year' => date('Y'),
            'user_name' => 'John Doe',
            'nickname' => 'John',
            'order_no' => 'ORD20260123123456',
            'order_date' => 'January 23, 2026',
            'total_amount' => '$299.00',
            'currency' => 'USD',
            'goods_title' => 'Sample Product',
            'goods_image' => 'https://example.com/image.jpg',
            'price' => '$299.00',
            'tracking_no' => '794644790132',
            'carrier_name' => 'FedEx Express',
            'refund_amount' => '$299.00',
            'reject_reason' => 'Does not meet return policy requirements',
            'fail_reason' => 'Payment failed',
            'fail_message' => 'Insufficient funds',
            'return_no' => 'RET20260123000001',
            'installment_periods' => '3',
            'installment_period_amount' => '$100.00',
        ];
    }

    /**
     * 清除模板缓存
     * @param string $type
     * @param string $channel
     */
    protected function clearTemplateCache(string $type, string $channel): void
    {
        $locales = ['en-us', 'zh-tw', 'ja-jp'];
        foreach ($locales as $locale) {
            Cache::delete("notification_template:{$type}:{$channel}:{$locale}");
        }
    }
}
