<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\SystemConfig as SystemConfigModel;
use app\common\service\ExchangeRateService;
use app\common\service\EmailService;

/**
 * 系统配置控制器
 */
class SystemConfig extends Base
{
    /**
     * 配置分组
     */
    const GROUPS = [
        'basic' => '基础配置',
        'contact' => '联系方式',
        'trade' => '交易设置',
        'security' => '安全设置',
        'storage' => '存储设置',
        'currency' => '货币汇率',
        'translate' => '翻译设置',
        'mail' => '邮件配置',
        'oauth' => 'OAuth 登入',
        'crawl' => '采集设置',
    ];

    /**
     * 支持的语言
     */
    protected $supportedLocales = ['zh-tw', 'en-us', 'ja-jp'];

    /**
     * 获取所有配置
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $group = input('group', '');
        $keyword = input('keyword', '');
        $locale = input('locale', 'zh-tw');

        $query = SystemConfigModel::order('sort', 'desc')->order('id', 'asc');

        if ($group) {
            $query->where('group', $group);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('key', 'like', "%{$keyword}%")
                    ->whereOr('name', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 保存原始 value 值（不被翻译覆盖）
        $originalValues = [];
        foreach ($list as $item) {
            $originalValues[$item['id']] = $item['value'];
        }

        // 附加翻译内容（name, description）
        $list = SystemConfigModel::appendTranslations($list, $locale);

        // 恢复原始 value 值（value 不从翻译表获取，保持主表值）
        foreach ($list as &$item) {
            $item['value'] = $originalValues[$item['id']] ?? $item['value'];
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 获取单个配置
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $config = SystemConfigModel::find($id);
        if (!$config) {
            return $this->error('配置不存在', 404);
        }

        $data = $config->toArray();

        // 返回所有语言的翻译
        $data['translations'] = [];
        foreach ($this->supportedLocales as $locale) {
            $translation = $config->translation($locale);
            $data['translations'][$locale] = $translation ? [
                'name' => $translation->name ?? '',
                'description' => $translation->description ?? '',
                'value' => $translation->value ?? ''
            ] : ['name' => '', 'description' => '', 'value' => ''];
        }

        return $this->success($data);
    }

    /**
     * 创建配置
     * @return Response
     */
    public function create(): Response
    {
        $data = input('post.');
        $translations = $data['translations'] ?? [];

        // 验证必填字段
        if (empty($data['key'])) {
            return $this->error('请填写配置键');
        }
        if (empty($data['group'])) {
            return $this->error('请选择配置分组');
        }

        // 验证至少有一种语言的名称
        $hasName = false;
        foreach ($this->supportedLocales as $locale) {
            if (!empty($translations[$locale]['name'])) {
                $hasName = true;
                break;
            }
        }
        if (!$hasName) {
            return $this->error('请至少填写一种语言的配置名称');
        }

        // 检查key是否重复
        $exists = SystemConfigModel::where('key', $data['key'])->find();
        if ($exists) {
            return $this->error('配置键已存在');
        }

        $config = new SystemConfigModel();
        $config->group = $data['group'];
        $config->key = $data['key'];
        $config->type = $data['type'] ?? 'string';

        // 设置 value：对于字符串类型，优先使用翻译中的值
        if ($config->type === 'string' && !empty($translations)) {
            $config->value = $translations['zh-tw']['value']
                ?? ($translations['en-us']['value']
                ?? ($translations['ja-jp']['value'] ?? ''));
        } else {
            $config->value = $data['value'] ?? '';
        }

        // 使用繁体中文名称作为默认name字段
        $config->name = $translations['zh-tw']['name'] ?? ($translations['en-us']['name'] ?? '');
        $config->description = $translations['zh-tw']['description'] ?? ($translations['en-us']['description'] ?? null);
        $config->sort = $data['sort'] ?? 0;
        $config->save();

        // 保存翻译
        foreach ($this->supportedLocales as $locale) {
            if (isset($translations[$locale])) {
                $config->saveTranslation($locale, $translations[$locale]);
            }
        }

        return $this->success(['id' => $config->id], '创建成功');
    }

    /**
     * 更新配置
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $config = SystemConfigModel::find($id);
        if (!$config) {
            return $this->error('配置不存在', 404);
        }

        $data = input('post.');
        $translations = $data['translations'] ?? [];
        // 优先使用前端传递的类型，便于类型更改后立即生效
        $configType = $data['type'] ?? $config->getData('type');

        // 准备更新数据
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        // 处理 value 字段 - 先保存前端直接传递的 value
        $directValue = null;
        if (isset($data['value'])) {
            $directValue = is_array($data['value']) ? json_encode($data['value']) : (string)$data['value'];
            $updateData['value'] = $directValue;
        }

        // 处理 sort 字段
        if (isset($data['sort'])) {
            $updateData['sort'] = $data['sort'];
        }

        // 更新翻译
        if (!empty($translations)) {
            // 使用繁体中文名称作为默认name字段
            if (!empty($translations['zh-tw']['name'])) {
                $updateData['name'] = $translations['zh-tw']['name'];
                $updateData['description'] = $translations['zh-tw']['description'] ?? null;
            } elseif (!empty($translations['en-us']['name'])) {
                $updateData['name'] = $translations['en-us']['name'];
                $updateData['description'] = $translations['en-us']['description'] ?? null;
            }

            // 只有字符串类型才使用翻译中的 value 覆盖主表
            // number, boolean, json, image 类型直接使用前端传递的 value，不被翻译覆盖
            if ($configType === 'string') {
                if (isset($translations['zh-tw']['value']) && $translations['zh-tw']['value'] !== '') {
                    $updateData['value'] = $translations['zh-tw']['value'];
                } elseif (isset($translations['en-us']['value']) && $translations['en-us']['value'] !== '') {
                    $updateData['value'] = $translations['en-us']['value'];
                } elseif (isset($translations['ja-jp']['value']) && $translations['ja-jp']['value'] !== '') {
                    $updateData['value'] = $translations['ja-jp']['value'];
                }
            }
            // 对于 image 类型，确保使用前端直接传递的 value（不被翻译覆盖）
            // 因为图片类型的 value 由 ImagePicker 组件直接设置
            if ($configType === 'image' && $directValue !== null) {
                $updateData['value'] = $directValue;
            }

            // 保存翻译
            foreach ($this->supportedLocales as $locale) {
                if (isset($translations[$locale])) {
                    try {
                        $config->saveTranslation($locale, $translations[$locale]);
                    } catch (\Exception $e) {
                        \think\facade\Log::error("保存翻译失败 [{$locale}]: " . $e->getMessage());
                    }
                }
            }
        }

        // 直接更新数据库，避免模型访问器干扰
        SystemConfigModel::where('id', $id)->update($updateData);

        return $this->success([], '更新成功');
    }

    /**
     * 删除配置
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $config = SystemConfigModel::find($id);
        if (!$config) {
            return $this->error('配置不存在', 404);
        }

        $config->delete();

        return $this->success([], '删除成功');
    }

    /**
     * 批量更新配置
     * @return Response
     */
    public function batchUpdate(): Response
    {
        $configs = input('post.configs', []);

        if (empty($configs) || !is_array($configs)) {
            return $this->error('请提供配置数据');
        }

        $updated = 0;
        $failed = [];
        foreach ($configs as $item) {
            if (empty($item['key'])) {
                continue;
            }
            $result = SystemConfigModel::setConfig($item['key'], $item['value'] ?? '');
            if ($result) {
                $updated++;
            } else {
                $failed[] = $item['key'];
            }
        }

        if (!empty($failed)) {
            return $this->error('部分配置更新失败: ' . implode(', ', $failed));
        }

        return $this->success(['updated' => $updated], '保存成功');
    }

    /**
     * 获取配置分组列表
     * @return Response
     */
    public function groups(): Response
    {
        $groups = [];
        foreach (self::GROUPS as $key => $name) {
            $groups[] = [
                'key' => $key,
                'name' => $name,
            ];
        }
        return $this->success($groups);
    }

    /**
     * 手动更新汇率
     * @return Response
     */
    public function updateExchangeRate(): Response
    {
        $service = new ExchangeRateService();
        $result = $service->updateRates();

        if ($result['success']) {
            return $this->success([
                'rates' => $result['rates'],
                'last_update' => $service->getLastUpdateTime(),
            ], $result['message']);
        }

        return $this->error($result['message']);
    }

    /**
     * 获取汇率状态信息
     * @return Response
     */
    public function exchangeRateStatus(): Response
    {
        $service = new ExchangeRateService();

        return $this->success([
            'enabled' => $service->isAutoUpdateEnabled(),
            'rates' => $service->getCurrentRates(),
            'last_update' => $service->getLastUpdateTime(),
        ]);
    }

    /**
     * 发送测试邮件
     * @return Response
     */
    public function testMail(): Response
    {
        $to = input('post.to', '');

        if (empty($to)) {
            return $this->error('请输入收件人邮箱');
        }

        // 验证邮箱格式
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $this->error('邮箱格式不正确');
        }

        $emailService = new EmailService();

        // 构建测试邮件内容
        $subject = 'TURNSY 邮件配置测试';
        $content = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4A90D9; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .success { color: #67C23A; font-size: 24px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TURNSY Marketplace</h1>
        </div>
        <div class="content">
            <p class="success">✓ 邮件配置测试成功！</p>
            <p>如果您收到这封邮件，说明您的邮件服务配置正确。</p>
            <p><strong>发送时间：</strong> %s</p>
        </div>
        <div class="footer">
            <p>此邮件由 TURNSY 系统自动发送，请勿回复。</p>
        </div>
    </div>
</body>
</html>
HTML;

        $content = sprintf($content, date('Y-m-d H:i:s'));

        $result = $emailService->send($to, $subject, $content);

        if ($result['success']) {
            return $this->success([], '测试邮件发送成功');
        }

        return $this->error('邮件发送失败: ' . ($result['error'] ?? '未知错误'));
    }

    /**
     * 获取应用预览图配置
     */
    public function appPreviews(): Response
    {
        $list = [];
        for ($i = 1; $i <= 5; $i++) {
            $key = 'app_preview_' . $i;
            $config = SystemConfigModel::where('key', $key)->find();
            $value = $config ? $config->value : '';
            if ($value) {
                $value = \app\common\helper\UrlHelper::getFullUrl($value);
            }
            $list[] = [
                'index' => $i,
                'key' => $key,
                'image' => $value,
                'name' => $config ? $config->name : "预览图{$i}",
                'description' => $config ? $config->description : '建议尺寸: 1080 x 1920 (9:16)',
            ];
        }
        return $this->success($list);
    }

    /**
     * 更新应用预览图配置
     */
    public function updateAppPreviews(): Response
    {
        $previews = input('post.previews', []);
        if (!is_array($previews)) {
            return $this->error('Invalid data');
        }

        foreach ($previews as $item) {
            $key = $item['key'] ?? '';
            $image = $item['image'] ?? '';
            if (!preg_match('/^app_preview_[1-5]$/', $key)) continue;

            SystemConfigModel::setConfig($key, $image);
        }

        return $this->success([], 'Updated successfully');
    }
}
