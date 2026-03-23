<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\service\R2Storage;

/**
 * 管理后台上传控制器
 */
class Upload extends Base
{
    /**
     * R2 存储服务
     * @var R2Storage
     */
    protected $storage;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->storage = new R2Storage();
    }

    /**
     * 上传图片（通用）
     * @return Response
     */
    public function image(): Response
    {
        $file = request()->file('file');

        if (!$file) {
            return $this->error('No file uploaded');
        }

        try {
            $type = input('post.type', 'common');
            $result = $this->storage->uploadImage($file, $type);

            return $this->success($result, 'Image uploaded');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 富文本编辑器图片上传
     * WangEditor 要求返回特定格式
     * @return Response
     */
    public function editor(): Response
    {
        $file = request()->file('file');

        if (!$file) {
            return json([
                'errno' => 1,
                'message' => 'No file uploaded'
            ]);
        }

        try {
            $result = $this->storage->uploadImage($file, 'editor');

            // WangEditor 要求的返回格式
            return json([
                'errno' => 0,
                'data' => [
                    'url' => $result['url'],
                    'alt' => '',
                    'href' => ''
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'errno' => 1,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 上传视频
     * @return Response
     */
    public function video(): Response
    {
        $file = request()->file('file');

        if (!$file) {
            return $this->error('No file uploaded');
        }

        try {
            $type = input('post.type', 'common');
            $result = $this->storage->uploadVideo($file, $type);

            return $this->success($result, 'Video uploaded');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 批量上传图片
     * @return Response
     */
    public function images(): Response
    {
        $files = request()->file('files');

        if (!$files || !is_array($files)) {
            return $this->error('No files uploaded');
        }

        if (count($files) > 9) {
            return $this->error('Maximum 9 images allowed');
        }

        $type = input('post.type', 'common');
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $result = $this->storage->uploadImage($file, $type);
                $results[] = $result;
            } catch (\Exception $e) {
                $errors[] = "File {$index}: {$e->getMessage()}";
            }
        }

        return $this->success([
            'uploaded' => $results,
            'errors' => $errors,
            'count' => count($results),
        ], count($results) . ' images uploaded');
    }
}
