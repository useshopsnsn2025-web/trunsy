<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\EmailLog;
use app\common\model\EmailTemplate;
use app\common\model\SystemConfig;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use think\facade\Log;

/**
 * 邮件发送服务
 */
class EmailService
{
    /**
     * 邮件配置
     * @var array
     */
    protected $config;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * 从数据库加载邮件配置
     */
    protected function loadConfig(): void
    {
        // 从数据库读取 mail 分组的配置
        $mailConfigs = SystemConfig::getConfigs('mail');

        // mail_enabled 可能是字符串 '1' 或整数 1，使用宽松比较
        $enabled = isset($mailConfigs['mail_enabled']) && $mailConfigs['mail_enabled'] == '1';

        $this->config = [
            'enabled' => $enabled,
            'driver' => $mailConfigs['mail_driver'] ?? 'smtp',
            'host' => $mailConfigs['mail_host'] ?? '',
            'port' => (int)($mailConfigs['mail_port'] ?? 587),
            'username' => $mailConfigs['mail_username'] ?? '',
            'password' => $mailConfigs['mail_password'] ?? '',
            'encryption' => $mailConfigs['mail_encryption'] ?? 'tls',
            'from' => [
                'address' => $mailConfigs['mail_from_address'] ?? '',
                'name' => $mailConfigs['mail_from_name'] ?? 'TURNSY Marketplace',
            ],
            'template_path' => root_path() . 'app/view/email/',
            'log_enabled' => true,
            'timeout' => 30,
            'debug' => false,
        ];
    }

    /**
     * 发送邮件
     * @param string $to 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $content HTML内容
     * @param array $options 可选参数
     * @return array
     */
    public function send(
        string $to,
        string $subject,
        string $content,
        array $options = []
    ): array {
        $toName = $options['to_name'] ?? '';
        $template = $options['template'] ?? 'default';
        $data = $options['data'] ?? [];
        $userId = $options['user_id'] ?? null;

        // 检查是否启用邮件发送
        if (!$this->config['enabled']) {
            Log::info('Email sending is disabled', [
                'to' => $to,
                'subject' => $subject,
            ]);
            return [
                'success' => true,
                'message' => 'Email sending is disabled',
                'simulated' => true,
            ];
        }

        // 创建邮件日志记录
        $emailLog = null;
        if ($this->config['log_enabled'] ?? true) {
            $emailLog = EmailLog::create([
                'user_id' => $userId,
                'to_email' => $to,
                'to_name' => $toName,
                'subject' => $subject,
                'template' => $template,
                'content' => $content,
                'data' => $data,
                'status' => EmailLog::STATUS_PENDING,
            ]);
        }

        try {
            $mail = new PHPMailer(true);

            // 服务器配置
            if ($this->config['debug'] ?? false) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            }

            $mail->isSMTP();
            $mail->Host = $this->config['host'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'] ?? '';
            $mail->Password = $this->config['password'] ?? '';

            $encryption = $this->config['encryption'] ?? 'tls';
            $mail->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)($this->config['port'] ?? 587);
            $mail->Timeout = (int)($this->config['timeout'] ?? 30);

            // 发件人
            $fromAddress = $this->config['from']['address'] ?? 'turnsyshop@gmail.com';
            $fromName = $this->config['from']['name'] ?? 'TURNSY Marketplace';
            $mail->setFrom($fromAddress, $fromName);

            // 收件人
            $mail->addAddress($to, $toName);

            // 邮件内容
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $content;
            $mail->AltBody = strip_tags($content);

            // 添加附件
            if (!empty($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (is_array($attachment)) {
                        // 支持 ['path' => '/path/to/file', 'name' => 'filename.ext'] 格式
                        $path = $attachment['path'] ?? '';
                        $name = $attachment['name'] ?? basename($path);
                        if ($path && file_exists($path)) {
                            $mail->addAttachment($path, $name);
                        }
                    } elseif (is_string($attachment) && file_exists($attachment)) {
                        // 支持直接传入文件路径
                        $mail->addAttachment($attachment);
                    }
                }
            }

            // 发送
            $mail->send();

            // 更新日志状态
            if ($emailLog) {
                $emailLog->markAsSent();
            }

            Log::info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'email_log_id' => $emailLog ? $emailLog->id : null,
            ];

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();

            // 更新日志状态
            if ($emailLog) {
                $emailLog->markAsFailed($errorMessage);
            }

            Log::error('Email sending failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => 'Email sending failed',
                'error' => $errorMessage,
                'email_log_id' => $emailLog ? $emailLog->id : null,
            ];
        }
    }

    /**
     * 使用模板发送邮件
     * @param string $to 收件人邮箱
     * @param string $template 模板名称/类型
     * @param string $locale 语言
     * @param array $data 模板数据
     * @param array $options 可选参数
     * @return array
     */
    public function sendWithTemplate(
        string $to,
        string $template,
        string $locale,
        array $data = [],
        array $options = []
    ): array {
        // 优先从数据库获取模板
        $dbTemplate = EmailTemplate::render($template, $locale, $data);

        if ($dbTemplate) {
            Log::info('EmailService::sendWithTemplate - using database template for: ' . $template);
            $subject = $dbTemplate['subject'];
            $content = $dbTemplate['content'];
        } else {
            // 降级到文件模板
            Log::info('EmailService::sendWithTemplate - database template not found, falling back to file');
            $templatePath = ($this->config['template_path'] ?? root_path() . 'app/view/email/')
                . $template . '/' . $locale . '.html';

            // 如果指定语言的模板不存在，尝试使用英文模板
            if (!file_exists($templatePath)) {
                $templatePath = ($this->config['template_path'] ?? root_path() . 'app/view/email/')
                    . $template . '/en-us.html';
            }

            if (!file_exists($templatePath)) {
                Log::warning('EmailService::sendWithTemplate - template not found: ' . $templatePath);
                return [
                    'success' => false,
                    'message' => 'Email template not found',
                    'error' => "Template not found: {$template}/{$locale}",
                ];
            }

            // 读取模板内容
            $content = file_get_contents($templatePath);

            // 替换变量
            foreach ($data as $key => $value) {
                if (is_scalar($value)) {
                    $content = str_replace('{{$' . $key . '}}', (string)$value, $content);
                    $content = str_replace('{{ $' . $key . ' }}', (string)$value, $content);
                    $content = str_replace('{' . $key . '}', (string)$value, $content);
                }
            }

            // 获取邮件主题
            $subject = $options['subject'] ?? $this->extractSubjectFromTemplate($content, $data);
        }

        $options['template'] = $template;
        $options['data'] = $data;

        return $this->send($to, $subject, $content, $options);
    }

    /**
     * 从模板中提取主题（如果模板包含 <title> 标签）
     * @param string $content
     * @param array $data
     * @return string
     */
    protected function extractSubjectFromTemplate(string $content, array $data): string
    {
        if (preg_match('/<title>(.*?)<\/title>/is', $content, $matches)) {
            $subject = $matches[1];
            // 替换变量
            foreach ($data as $key => $value) {
                if (is_scalar($value)) {
                    $subject = str_replace('{{$' . $key . '}}', (string)$value, $subject);
                    $subject = str_replace('{' . $key . '}', (string)$value, $subject);
                }
            }
            return trim($subject);
        }
        return 'Notification from TURNSY';
    }

    /**
     * 批量发送邮件
     * @param array $recipients [[email, name], ...]
     * @param string $subject
     * @param string $content
     * @return array
     */
    public function sendBatch(array $recipients, string $subject, string $content): array
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name = is_array($recipient) ? ($recipient['name'] ?? '') : '';

            $results[] = $this->send($email, $subject, $content, [
                'to_name' => $name,
            ]);
        }
        return $results;
    }
}
