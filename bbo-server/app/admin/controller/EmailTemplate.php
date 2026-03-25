<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\EmailTemplate as EmailTemplateModel;
use app\common\model\EmailTemplateTranslation;
use app\common\model\Language;
use app\common\model\SystemConfig;

/**
 * 邮件模板管理控制器
 */
class EmailTemplate extends Base
{
    /**
     * 获取模板列表
     * @return Response
     */
    public function index(): Response
    {
        $list = EmailTemplateModel::order('id', 'asc')
            ->select()
            ->toArray();

        // 获取每个模板的翻译数量
        foreach ($list as &$item) {
            $item['translation_count'] = EmailTemplateTranslation::where('template_id', $item['id'])->count();
        }

        return $this->success($list);
    }

    /**
     * 获取单个模板详情（包含所有翻译）
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $template = EmailTemplateModel::find($id);
        if (!$template) {
            return $this->error('Template not found', 404);
        }

        $data = $template->toArray();

        // 获取所有翻译
        $translations = EmailTemplateTranslation::where('template_id', $id)
            ->select()
            ->toArray();

        // 转换为 locale => translation 格式
        $data['translations'] = [];
        foreach ($translations as $trans) {
            $data['translations'][$trans['locale']] = [
                'id' => $trans['id'],
                'subject' => $trans['subject'],
                'content' => $trans['content'],
            ];
        }

        // 获取支持的语言列表
        $data['available_locales'] = $this->getAvailableLocales();

        return $this->success($data);
    }

    /**
     * 更新模板基本信息
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $template = EmailTemplateModel::find($id);
        if (!$template) {
            return $this->error('Template not found', 404);
        }

        $name = input('post.name');
        $subject = input('post.subject');
        $content = input('post.content');
        $isActive = input('post.is_active');

        if ($name !== null) {
            $template->name = $name;
        }
        if ($subject !== null) {
            $template->subject = $subject;
        }
        if ($content !== null) {
            $template->content = $content;
        }
        if ($isActive !== null) {
            $template->is_active = (bool)$isActive;
        }

        $template->save();

        // 清除缓存
        EmailTemplateModel::clearCache($template->type);

        return $this->success($template->toArray(), 'Template updated');
    }

    /**
     * 获取模板翻译
     * @param int $id 模板ID
     * @param string $locale 语言代码
     * @return Response
     */
    public function getTranslation(int $id, string $locale): Response
    {
        $template = EmailTemplateModel::find($id);
        if (!$template) {
            return $this->error('Template not found', 404);
        }

        // 英文（en-us）使用主表内容
        if ($locale === 'en-us') {
            return $this->success([
                'id' => null, // 主表没有翻译ID
                'template_id' => $id,
                'locale' => $locale,
                'subject' => $template->subject,
                'content' => $template->content,
                'is_primary' => true, // 标记为主语言
            ]);
        }

        $translation = EmailTemplateTranslation::where('template_id', $id)
            ->where('locale', $locale)
            ->find();

        if ($translation) {
            return $this->success($translation->toArray());
        }

        // 返回空模板供创建
        return $this->success([
            'template_id' => $id,
            'locale' => $locale,
            'subject' => '',
            'content' => '',
        ]);
    }

    /**
     * 保存模板翻译
     * @param int $id 模板ID
     * @param string $locale 语言代码
     * @return Response
     */
    public function saveTranslation(int $id, string $locale): Response
    {
        $template = EmailTemplateModel::find($id);
        if (!$template) {
            return $this->error('Template not found', 404);
        }

        $subject = input('post.subject', '');
        $content = input('post.content', '');

        if (empty($subject) || empty($content)) {
            return $this->error('Subject and content are required');
        }

        // 英文（en-us）更新主表 + 翻译表
        if ($locale === 'en-us') {
            $template->subject = $subject;
            $template->content = $content;
            $template->save();

            // 同时更新或创建 translations 表中的 en-us 记录
            $translation = EmailTemplateTranslation::where('template_id', $id)
                ->where('locale', 'en-us')
                ->find();
            if ($translation) {
                $translation->subject = $subject;
                $translation->content = $content;
                $translation->save();
            } else {
                $translation = EmailTemplateTranslation::create([
                    'template_id' => $id,
                    'locale' => 'en-us',
                    'subject' => $subject,
                    'content' => $content,
                ]);
            }

            // 清除所有相关缓存
            EmailTemplateModel::clearCache($template->type);
            \think\facade\Cache::delete('email_template:' . $template->type . ':en-us');

            return $this->success([
                'id' => $translation->id,
                'template_id' => $id,
                'locale' => $locale,
                'subject' => $subject,
                'content' => $content,
                'is_primary' => true,
            ], 'Template saved');
        }

        $translation = EmailTemplateTranslation::where('template_id', $id)
            ->where('locale', $locale)
            ->find();

        if ($translation) {
            // 更新
            $translation->subject = $subject;
            $translation->content = $content;
            $translation->save();
        } else {
            // 创建
            $translation = EmailTemplateTranslation::create([
                'template_id' => $id,
                'locale' => $locale,
                'subject' => $subject,
                'content' => $content,
            ]);
        }

        // 清除缓存
        EmailTemplateModel::clearCache($template->type);

        return $this->success($translation->toArray(), 'Translation saved');
    }

    /**
     * 删除模板翻译
     * @param int $id 模板ID
     * @param string $locale 语言代码
     * @return Response
     */
    public function deleteTranslation(int $id, string $locale): Response
    {
        $template = EmailTemplateModel::find($id);
        if (!$template) {
            return $this->error('Template not found', 404);
        }

        // 禁止删除英文（主语言）
        if ($locale === 'en-us') {
            return $this->error('Cannot delete primary language content');
        }

        $translation = EmailTemplateTranslation::where('template_id', $id)
            ->where('locale', $locale)
            ->find();

        if (!$translation) {
            return $this->error('Translation not found', 404);
        }

        $translation->delete();

        // 清除缓存
        EmailTemplateModel::clearCache($template->type);

        return $this->success(null, 'Translation deleted');
    }

    /**
     * 预览模板
     * @param int $id
     * @return Response
     */
    public function preview(int $id): Response
    {
        $template = EmailTemplateModel::find($id);
        if (!$template) {
            return $this->error('Template not found', 404);
        }

        $locale = input('locale', 'en-us');

        // 准备示例数据
        $sampleData = $this->getSampleData($template->type);

        // 渲染模板
        $rendered = EmailTemplateModel::render($template->type, $locale, $sampleData);

        if (!$rendered) {
            return $this->error('Failed to render template');
        }

        return $this->success([
            'subject' => $rendered['subject'],
            'content' => $rendered['content'],
        ]);
    }

    /**
     * 发送测试邮件
     * @param int $id
     * @return Response
     */
    public function sendTest(int $id): Response
    {
        $template = EmailTemplateModel::find($id);
        if (!$template) {
            return $this->error('Template not found', 404);
        }

        $email = input('post.email', '');
        $locale = input('post.locale', 'en-us');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Valid email address is required');
        }

        // 准备示例数据
        $sampleData = $this->getSampleData($template->type);

        // 发送测试邮件
        $emailService = new \app\common\service\EmailService();
        $result = $emailService->sendWithTemplate(
            $email,
            $template->type,
            $locale,
            $sampleData,
            ['to_name' => 'Test User']
        );

        if ($result['success']) {
            return $this->success($result, 'Test email sent successfully');
        }

        return $this->error($result['error'] ?? 'Failed to send test email');
    }

    /**
     * 获取可用模板类型
     * @return Response
     */
    public function types(): Response
    {
        return $this->success(EmailTemplateModel::getAvailableTypes());
    }

    /**
     * 获取模板图片配置
     * @param int $id 模板ID
     * @return Response
     */
    public function getImages(int $id): Response
    {
        $template = EmailTemplateModel::find($id);
        if (!$template) {
            return $this->error('Template not found', 404);
        }

        $images = EmailTemplateModel::getTemplateImages($template->type);

        return $this->success([
            'template_id' => $id,
            'type' => $template->type,
            'images' => $images,
            'available_keys' => array_keys(EmailTemplateModel::DEFAULT_IMAGES),
        ]);
    }

    /**
     * 更新模板图片配置
     * @param int $id 模板ID
     * @return Response
     */
    public function updateImages(int $id): Response
    {
        $template = EmailTemplateModel::find($id);
        if (!$template) {
            return $this->error('Template not found', 404);
        }

        $images = input('post.images', []);
        if (!is_array($images)) {
            return $this->error('Invalid images data');
        }

        $result = EmailTemplateModel::updateTemplateImages($template->type, $images);

        if ($result) {
            return $this->success([
                'images' => EmailTemplateModel::getTemplateImages($template->type),
            ], 'Images updated');
        }

        return $this->error('Failed to update images');
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
     * 获取示例数据
     * @param string $type
     * @return array
     */
    protected function getSampleData(string $type): array
    {
        $siteUrl = SystemConfig::getConfig('site_url', 'https://www.turnsysg.com');

        $baseData = [
            'buyer_name' => 'John Doe',
            'user_name' => 'John Doe',
            'nickname' => 'John',
            'email' => 'john.doe@example.com',
            'login_url' => $siteUrl . '/login',
            'order_no' => 'ORD20260114123456',
            'order_date' => 'January 14, 2026',
            'total_amount' => '$299.00',
            'paid_amount' => '$299.00',
            'currency' => 'USD',
            'goods_title' => 'Sample Product - Premium Edition',
            'goods_image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=80&h=80&fit=crop',
            'quantity' => 1,
            'price' => '$299.00',
            'payment_method' => 'Credit Card',
            'recipient_name' => 'John Doe',
            'address' => '123 Main Street',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'United States',
            'platform_name' => SystemConfig::getConfig('site_name', 'TURNSY'),
            'platform_url' => $siteUrl,
            'platform_logo' => SystemConfig::getConfig('site_logo', ''),
            'current_year' => date('Y'),
        ];

        // 根据模板类型添加特定数据
        switch ($type) {
            case 'order_shipped':
                $baseData['shipped_date'] = 'January 14, 2026';
                $baseData['carrier_name'] = 'FedEx Express';
                $baseData['tracking_no'] = '794644790132';
                $baseData['tracking_url'] = 'https://www.fedex.com/track?tracknumbers=794644790132';
                $baseData['estimated_delivery'] = 'January 18 - January 20, 2026';
                break;

            case 'order_verify_failed':
                $baseData['fail_message'] = 'The verification code you entered is incorrect. Please check and try again.';
                $baseData['fail_reason'] = 'wrong_code';
                break;

            case 'user_registered':
                // 预览时显示优惠券提示区块（示例）
                $baseData['coupon_section'] = $this->generateSampleCouponSection($siteUrl);
                break;

            case 'goods_sold':
                // 卖家商品售出通知示例数据
                $baseData['seller_name'] = 'Jane Smith';
                $baseData['phone'] = '+1 (555) 123-4567';
                break;
        }

        return $baseData;
    }

    /**
     * 生成示例优惠券区块 HTML（用于预览）
     * @param string $platformUrl
     * @return string
     */
    protected function generateSampleCouponSection(string $platformUrl): string
    {
        $couponUrl = rtrim($platformUrl, '/') . '/user/coupon';

        return <<<HTML
<tr>
    <td style="padding: 0 24px 24px 24px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 20px 24px;">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="48" valign="top">
                        <div style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.2); border-radius: 50%; text-align: center; line-height: 40px;">
                            <span style="font-size: 20px;">🎁</span>
                        </div>
                    </td>
                    <td style="padding-left: 12px;">
                        <p style="margin: 0 0 4px 0; font-size: 16px; font-weight: 600; color: #ffffff; line-height: 1.4;">
                            Welcome Gift!
                        </p>
                        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.9); line-height: 1.5;">
                            A coupon has been added to your account. Check your <a href="{$couponUrl}" style="color: #ffffff; text-decoration: underline; font-weight: 500;">My Coupons</a> to view and use it!
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </td>
</tr>
HTML;
    }
}
