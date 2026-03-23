<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\Request;
use think\exception\ValidateException;
use app\common\model\UiTranslation;

/**
 * API 基础控制器
 */
class Base
{
    /**
     * 当前语言
     * @var string
     */
    protected $locale = 'en-us';

    /**
     * 当前用户ID
     * @var int|null
     */
    protected $userId = null;

    /**
     * 当前用户信息
     * @var array|null
     */
    protected $user = null;

    /**
     * 请求对象
     * @var Request
     */
    protected $request;

    /**
     * 消息翻译缓存
     * @var array|null
     */
    protected static $messageTranslations = null;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->request = request();
        $this->locale = $this->request->locale ?? 'en-us';
        $this->userId = $this->request->userId ?? null;
        $this->user = $this->request->user ?? null;
    }

    /**
     * 成功响应
     * @param mixed $data 返回数据
     * @param string $msg 提示信息
     * @return Response
     */
    protected function success($data = [], string $msg = 'success'): Response
    {
        return json([
            'code' => 0,
            'msg' => $this->translateMessage($msg),
            'data' => $data
        ]);
    }

    /**
     * 错误响应
     * @param string $msg 错误信息
     * @param int $code 错误码
     * @param mixed $data 附加数据
     * @param array $params 翻译参数，如 ['minutes' => 30]
     * @return Response
     */
    protected function error(string $msg = 'error', int $code = 1, $data = null, array $params = []): Response
    {
        return json([
            'code' => $code,
            'msg' => $this->translateMessage($msg, $params),
            'data' => $data
        ]);
    }

    /**
     * 翻译消息
     * 优先从数据库获取翻译，fallback 到 lang() 函数
     * @param string $msg 消息键
     * @param array $params 替换参数，如 ['minutes' => 30]
     * @return string
     */
    protected function translateMessage(string $msg, array $params = []): string
    {
        // 将旧格式的消息键转换为数据库键格式
        // 例如: "User not found" -> "user_not_found"
        $dbKey = $this->convertToDbKey($msg);

        // 获取翻译
        $translations = $this->getMessageTranslations();

        // 优先使用数据库翻译
        if (isset($translations[$dbKey])) {
            $translated = $translations[$dbKey];
        } else {
            // Fallback 到 lang() 函数（兼容旧代码）
            $translated = lang($msg);
        }

        // 替换参数占位符，如 {minutes} -> 30
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $translated = str_replace('{' . $key . '}', (string)$value, $translated);
            }
        }

        return $translated;
    }

    /**
     * 获取消息翻译（带缓存）
     * @return array
     */
    protected function getMessageTranslations(): array
    {
        $cacheKey = 'message_translations_' . $this->locale;

        // 静态缓存
        if (self::$messageTranslations !== null && isset(self::$messageTranslations[$cacheKey])) {
            return self::$messageTranslations[$cacheKey];
        }

        // 从数据库获取
        $translations = UiTranslation::getTranslationsByNamespace($this->locale, 'message');

        // 存入静态缓存
        if (self::$messageTranslations === null) {
            self::$messageTranslations = [];
        }
        self::$messageTranslations[$cacheKey] = $translations;

        return $translations;
    }

    /**
     * 将旧格式的消息键转换为数据库键格式
     * 例如: "User not found" -> "user_not_found"
     * @param string $msg
     * @return string
     */
    protected function convertToDbKey(string $msg): string
    {
        // 如果已经是下划线格式，直接返回
        if (strpos($msg, '_') !== false && strpos($msg, ' ') === false) {
            return $msg;
        }

        // 转换为小写，空格替换为下划线
        return strtolower(str_replace(' ', '_', $msg));
    }

    /**
     * 分页成功响应
     * @param array $list 列表数据
     * @param int $total 总数
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @return Response
     */
    protected function paginate(array $list, int $total, int $page, int $pageSize): Response
    {
        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total / $pageSize)
        ]);
    }

    /**
     * 验证请求参数
     * @param array $data 验证数据
     * @param string $validate 验证器类名
     * @param string $scene 验证场景
     * @return bool
     * @throws ValidateException
     */
    protected function validate(array $data, string $validate, string $scene = ''): bool
    {
        $v = app($validate);
        if ($scene) {
            $v->scene($scene);
        }

        if (!$v->check($data)) {
            throw new ValidateException($v->getError());
        }

        return true;
    }

    /**
     * 获取分页参数
     * @return array
     */
    protected function getPageParams(): array
    {
        $page = max(1, (int) input('page', 1));
        $pageSize = min(100, max(1, (int) input('pageSize', 20)));

        return [$page, $pageSize];
    }

    /**
     * 生成唯一编号
     * @param string $prefix 前缀
     * @return string
     */
    protected function generateNo(string $prefix = ''): string
    {
        return $prefix . date('YmdHis') . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 生成 UUID
     * @return string
     */
    protected function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * 获取当前用户ID
     * @return int
     */
    protected function getUserId(): int
    {
        return (int)$this->userId;
    }
}
