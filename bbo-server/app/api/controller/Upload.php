<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\service\R2Storage;

/**
 * 上传控制器
 * 统一使用 R2Storage 服务，支持本地存储和云存储自动切换
 */
class Upload extends Base
{
    /**
     * R2存储服务
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
     * 上传单张图片
     * @return Response
     */
    public function image(): Response
    {
        $file = request()->file('file');

        if (!$file) {
            return $this->error('No file uploaded');
        }

        try {
            // 获取上传类型 (goods, avatar, chat, shop 等)
            $type = input('post.type', 'common');

            // 使用统一的存储服务上传
            $result = $this->storage->uploadImage($file, $type);

            return $this->success([
                'url' => $result['url'],
                'path' => $result['path'],
            ], 'Image uploaded');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
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
            // 获取上传类型
            $type = input('post.type', 'common');

            // 使用统一的存储服务上传
            $result = $this->storage->uploadVideo($file, $type);

            return $this->success([
                'url' => $result['url'],
                'path' => $result['path'],
            ], 'Video uploaded');

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
                $results[] = [
                    'url' => $result['url'],
                    'path' => $result['path'],
                ];
            } catch (\Exception $e) {
                $errors[] = "File {$index}: " . $e->getMessage();
            }
        }

        return $this->success([
            'uploaded' => $results,
            'errors' => $errors,
            'count' => count($results),
        ], count($results) . ' images uploaded');
    }
}
