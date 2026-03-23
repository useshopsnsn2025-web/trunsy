<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use think\exception\ValidateException;
use app\common\helper\UrlHelper;

/**
 * 管理后台基础控制器
 */
class Base
{
    /**
     * 当前管理员ID
     * @var int|null
     */
    protected $adminId = null;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->adminId = request()->adminId ?? null;
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
            'msg' => $msg,
            'data' => $this->convertStorageUrls($data)
        ]);
    }

    /**
     * 错误响应
     * @param string $msg 错误信息
     * @param int $code 错误码
     * @param mixed $data 附加数据
     * @return Response
     */
    protected function error(string $msg = 'error', int $code = 1, $data = null): Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
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
            'totalPages' => (int) ceil($total / $pageSize)
        ]);
    }

    /**
     * 获取分页参数
     * @return array
     */
    protected function getPageParams(): array
    {
        $page = max(1, (int) input('page', 1));
        $pageSize = min(5000, max(1, (int) input('pageSize', 20)));

        return [$page, $pageSize];
    }

    /**
     * 已知的图片字段名列表
     * 当这些字段的值以 /storage/ 开头时，自动转换为完整 URL
     * @var array
     */
    protected static $imageFields = [
        'icon', 'image', 'avatar', 'logo', 'banner', 'cover_image',
        'bg_image', 'button_icon', 'video', 'selfie_image',
        'id_front_image', 'id_back_image', 'income_proof',
    ];

    /**
     * 递归转换数据中的本地存储路径为完整 URL
     * 仅转换已知图片字段中以 /storage/ 开头的值
     * @param mixed $data
     * @return mixed
     */
    protected function convertStorageUrls($data)
    {
        if (is_string($data)) {
            return $data;
        }

        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                // images 字段是图片数组，特殊处理
                if ($key === 'images' || $key === 'product_images' || $key === 'statement_images') {
                    $value = array_map(function ($img) {
                        if (is_string($img) && str_starts_with($img, '/storage/')) {
                            return UrlHelper::getFullUrl($img);
                        }
                        return $img;
                    }, $value);
                } else {
                    $value = $this->convertStorageUrls($value);
                }
            } elseif (is_string($value) && str_starts_with($value, '/storage/')) {
                // 只转换已知图片字段或所有以 /storage/ 开头的字段
                if (in_array($key, self::$imageFields) || $this->isLikelyImageField($key)) {
                    $value = UrlHelper::getFullUrl($value);
                }
            }
        }

        return $data;
    }

    /**
     * 判断字段名是否可能是图片字段
     * @param string|int $key
     * @return bool
     */
    private function isLikelyImageField($key): bool
    {
        if (is_int($key)) return false;
        $patterns = ['image', 'icon', 'avatar', 'logo', 'photo', 'banner', 'cover', 'thumb', 'poster'];
        foreach ($patterns as $pattern) {
            if (stripos($key, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
}
