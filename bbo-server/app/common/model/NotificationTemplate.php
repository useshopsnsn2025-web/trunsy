<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use app\common\service\TranslateService;

/**
 * 通知模板模型
 */
class NotificationTemplate extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'notification_templates';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'updated_at';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 渠道常量
     */
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_MESSAGE = 'message';
    const CHANNEL_PUSH = 'push';

    /**
     * 获取模板
     * @param string $type 通知类型
     * @param string $channel 渠道
     * @param string $locale 语言
     * @return NotificationTemplate|null
     */
    public static function getTemplate(string $type, string $channel, string $locale = 'en-us'): ?self
    {
        // 尝试获取指定语言的模板
        $template = self::where('type', $type)
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->where('status', 1)
            ->find();

        if ($template) {
            return $template;
        }

        // 如果没有找到，获取英文模板
        if ($locale === 'en-us') {
            return null;
        }

        $enTemplate = self::where('type', $type)
            ->where('channel', $channel)
            ->where('locale', 'en-us')
            ->where('status', 1)
            ->find();

        if (!$enTemplate) {
            return null;
        }

        // 自动翻译英文模板并保存为新语言模板
        try {
            $translated = self::translateAndSaveTemplate($enTemplate, $locale);
            return $translated ?: $enTemplate;
        } catch (\Exception $e) {
            // 翻译失败，返回英文模板
            return $enTemplate;
        }
    }

    /**
     * 翻译英文模板并保存为新语言
     */
    protected static function translateAndSaveTemplate(self $enTemplate, string $locale): ?self
    {
        $translator = new TranslateService();

        // 收集需要翻译的文本（保留 {variable} 占位符）
        $textsToTranslate = [];
        $textsToTranslate['title'] = $enTemplate->title;
        if (!empty($enTemplate->content)) {
            $textsToTranslate['content'] = $enTemplate->content;
        }
        if (!empty($enTemplate->subject)) {
            $textsToTranslate['subject'] = $enTemplate->subject;
        }

        // 提取占位符，替换为标记防止被翻译破坏
        $placeholderMap = [];
        foreach ($textsToTranslate as $key => $text) {
            preg_match_all('/\{(\w+)\}/', $text, $matches);
            foreach ($matches[0] as $i => $placeholder) {
                $marker = "PLHDR" . count($placeholderMap) . "X";
                $placeholderMap[$marker] = $placeholder;
                $textsToTranslate[$key] = str_replace($placeholder, $marker, $textsToTranslate[$key]);
            }
        }

        // 批量翻译
        $translated = $translator->batchTranslate(array_values($textsToTranslate), 'en', $locale);

        if (empty($translated)) {
            return null;
        }

        // 还原占位符
        $keys = array_keys($textsToTranslate);
        $translatedTexts = [];
        foreach ($keys as $i => $key) {
            $text = $translated[$i] ?? $textsToTranslate[$key];
            // 还原占位符
            foreach ($placeholderMap as $marker => $placeholder) {
                $text = str_replace($marker, $placeholder, $text);
            }
            $translatedTexts[$key] = $text;
        }

        // 保存新语言模板
        $newTemplate = self::create([
            'type' => $enTemplate->getData('type'),
            'category' => $enTemplate->getData('category') ?? 'order',
            'channel' => $enTemplate->channel,
            'locale' => $locale,
            'subject' => $translatedTexts['subject'] ?? $enTemplate->subject,
            'title' => $translatedTexts['title'] ?? $enTemplate->title,
            'content' => $translatedTexts['content'] ?? $enTemplate->content,
            'status' => 1,
        ]);

        return $newTemplate;
    }

    /**
     * 渲染模板内容
     * @param array $variables 变量数组
     * @return string
     */
    public function renderContent(array $variables): string
    {
        $content = $this->content;
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', (string)$value, $content);
        }
        return $content;
    }

    /**
     * 渲染模板标题
     * @param array $variables 变量数组
     * @return string
     */
    public function renderTitle(array $variables): string
    {
        $title = $this->title;
        foreach ($variables as $key => $value) {
            $title = str_replace('{' . $key . '}', (string)$value, $title);
        }
        return $title;
    }

    /**
     * 渲染邮件主题
     * @param array $variables 变量数组
     * @return string
     */
    public function renderSubject(array $variables): string
    {
        $subject = $this->subject ?? $this->title;
        foreach ($variables as $key => $value) {
            $subject = str_replace('{' . $key . '}', (string)$value, $subject);
        }
        return $subject;
    }
}
