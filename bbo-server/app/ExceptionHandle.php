<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // API 请求返回 JSON 格式的错误信息
        $pathInfo = $request->pathinfo();
        if ($request->isAjax() || strpos($pathInfo, 'api/') === 0 || strpos($pathInfo, 'admin/') === 0) {
            $code = $e->getCode() ?: 500;
            $message = $e->getMessage();

            // 开发环境返回详细错误信息
            $data = null;
            if (app()->isDebug()) {
                $data = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ];
            }

            return json([
                'code' => $code,
                'msg' => $message,
                'data' => $data,
            ]);
        }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
