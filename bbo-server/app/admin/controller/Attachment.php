<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use think\facade\Db;
use app\common\model\Attachment as AttachmentModel;
use app\common\model\AttachmentGroup;
use app\common\service\R2Storage;
use app\common\helper\UrlHelper;

/**
 * 附件管理控制器
 */
class Attachment extends Base
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
     * 获取附件列表
     * @return Response
     */
    public function list(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = AttachmentModel::order('created_at', 'desc');

        // 按类型筛选
        $type = input('type', '');
        if ($type) {
            $query->where('type', $type);
        }

        // 按分组筛选
        $groupId = input('group_id', '');
        if ($groupId !== '') {
            $query->where('group_id', (int)$groupId);
        }

        // 关键词搜索
        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where('name|original_name', 'like', "%{$keyword}%");
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)
            ->select()
            ->each(function ($item) {
                $item->size_text = $item->size_text;
                // 添加完整 URL 用于前端显示，url 字段保持原始值（相对路径）用于存储
                $item->full_url = UrlHelper::getFullUrl($item->url);
                return $item;
            })
            ->toArray();

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 获取分组列表
     * @return Response
     */
    public function groups(): Response
    {
        $groups = AttachmentGroup::order('sort', 'asc')
            ->select()
            ->each(function ($item) {
                $item->count = AttachmentModel::where('group_id', $item->id)->count();
                return $item;
            })
            ->toArray();

        // 添加全部分组
        $allCount = AttachmentModel::count();
        array_unshift($groups, [
            'id' => 0,
            'name' => '全部',
            'sort' => -1,
            'count' => $allCount,
        ]);

        return $this->success($groups);
    }

    /**
     * 创建分组
     * @return Response
     */
    public function createGroup(): Response
    {
        $name = input('post.name', '');
        if (!$name) {
            return $this->error('分组名称不能为空');
        }

        $group = new AttachmentGroup();
        $group->name = $name;
        $group->sort = (int)input('post.sort', 0);
        $group->save();

        return $this->success($group->toArray(), '创建成功');
    }

    /**
     * 更新分组
     * @param int $id
     * @return Response
     */
    public function updateGroup(int $id): Response
    {
        $group = AttachmentGroup::find($id);
        if (!$group) {
            return $this->error('分组不存在');
        }

        $name = input('post.name', '');
        if ($name) {
            $group->name = $name;
        }

        $sort = input('post.sort');
        if ($sort !== null) {
            $group->sort = (int)$sort;
        }

        $group->save();

        return $this->success($group->toArray(), '更新成功');
    }

    /**
     * 删除分组
     * @param int $id
     * @return Response
     */
    public function deleteGroup(int $id): Response
    {
        $group = AttachmentGroup::find($id);
        if (!$group) {
            return $this->error('分组不存在');
        }

        // 将该分组下的附件移动到默认分组
        AttachmentModel::where('group_id', $id)->update(['group_id' => 0]);

        $group->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 上传附件
     * @return Response
     */
    public function upload(): Response
    {
        $file = request()->file('file');

        if (!$file) {
            return $this->error('没有上传文件');
        }

        try {
            $type = input('post.type', 'common');
            $groupId = (int)input('post.group_id', 0);

            // 判断是图片还是视频
            $mimeType = $file->getMime();
            $fileType = AttachmentModel::TYPE_FILE;
            if (strpos($mimeType, 'image/') === 0) {
                $fileType = AttachmentModel::TYPE_IMAGE;
                $result = $this->storage->uploadImage($file, $type);
            } elseif (strpos($mimeType, 'video/') === 0) {
                $fileType = AttachmentModel::TYPE_VIDEO;
                $result = $this->storage->uploadVideo($file, $type);
            } else {
                return $this->error('不支持的文件类型');
            }

            // 获取图片尺寸
            $width = null;
            $height = null;
            if ($fileType === AttachmentModel::TYPE_IMAGE) {
                $imageInfo = getimagesize($file->getPathname());
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            }

            // 保存到数据库
            $attachment = new AttachmentModel();
            $attachment->name = $result['name'] ?? basename($result['url']);
            $attachment->original_name = $file->getOriginalName();
            $attachment->path = $result['path'] ?? $result['url'];
            $attachment->url = $result['url'];
            $attachment->type = $fileType;
            $attachment->mime_type = $mimeType;
            $attachment->size = $file->getSize();
            $attachment->width = $width;
            $attachment->height = $height;
            $attachment->group_id = $groupId;
            $attachment->storage = 'r2';
            $attachment->is_external = 0;
            $attachment->admin_id = $this->adminId;
            $attachment->save();

            $data = $attachment->toArray();
            $data['full_url'] = UrlHelper::getFullUrl($data['url']);
            return $this->success($data, '上传成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 添加外部链接
     * @return Response
     */
    public function addExternal(): Response
    {
        $url = input('post.url', '');
        if (!$url) {
            return $this->error('URL不能为空');
        }

        // 验证URL格式
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->error('无效的URL格式');
        }

        $groupId = (int)input('post.group_id', 0);
        $name = input('post.name', '') ?: basename(parse_url($url, PHP_URL_PATH));

        // 尝试获取图片信息
        $width = null;
        $height = null;
        $mimeType = 'image/jpeg';

        // 尝试获取远程图片尺寸（可选，可能失败）
        try {
            $headers = @get_headers($url, true);
            if ($headers && isset($headers['Content-Type'])) {
                $contentType = is_array($headers['Content-Type']) ? $headers['Content-Type'][0] : $headers['Content-Type'];
                if (strpos($contentType, 'image/') === 0) {
                    $mimeType = $contentType;
                }
            }
        } catch (\Exception $e) {
            // 忽略错误
        }

        $attachment = new AttachmentModel();
        $attachment->name = $name;
        $attachment->original_name = $name;
        $attachment->path = $url;
        $attachment->url = $url;
        $attachment->type = AttachmentModel::TYPE_IMAGE;
        $attachment->mime_type = $mimeType;
        $attachment->size = 0;
        $attachment->width = $width;
        $attachment->height = $height;
        $attachment->group_id = $groupId;
        $attachment->storage = 'external';
        $attachment->is_external = 1;
        $attachment->admin_id = $this->adminId;
        $attachment->save();

        $data = $attachment->toArray();
        $data['full_url'] = UrlHelper::getFullUrl($data['url']);
        return $this->success($data, '添加成功');
    }

    /**
     * 更新附件信息
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $attachment = AttachmentModel::find($id);
        if (!$attachment) {
            return $this->error('附件不存在');
        }

        $name = input('post.name');
        if ($name !== null) {
            $attachment->name = $name;
        }

        $groupId = input('post.group_id');
        if ($groupId !== null) {
            $attachment->group_id = (int)$groupId;
        }

        $attachment->save();

        return $this->success($attachment->toArray(), '更新成功');
    }

    /**
     * 批量移动到分组
     * @return Response
     */
    public function batchMove(): Response
    {
        $ids = input('post.ids', []);
        $groupId = input('post.group_id', 0);

        if (empty($ids)) {
            return $this->error('请选择要移动的附件');
        }

        AttachmentModel::whereIn('id', $ids)->update(['group_id' => (int)$groupId]);

        return $this->success(null, '移动成功');
    }

    /**
     * 删除附件
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $attachment = AttachmentModel::find($id);
        if (!$attachment) {
            return $this->error('附件不存在');
        }

        // 如果不是外部链接，尝试删除存储文件
        if (!$attachment->is_external && $attachment->storage === 'r2') {
            try {
                $this->storage->delete($attachment->path);
            } catch (\Exception $e) {
                // 忽略删除存储文件的错误
            }
        }

        $attachment->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 批量删除附件
     * @return Response
     */
    public function batchDelete(): Response
    {
        $ids = input('post.ids', []);

        if (empty($ids)) {
            return $this->error('请选择要删除的附件');
        }

        $attachments = AttachmentModel::whereIn('id', $ids)->select();

        foreach ($attachments as $attachment) {
            // 如果不是外部链接，尝试删除存储文件
            if (!$attachment->is_external && $attachment->storage === 'r2') {
                try {
                    $this->storage->delete($attachment->path);
                } catch (\Exception $e) {
                    // 忽略删除存储文件的错误
                }
            }
            $attachment->delete();
        }

        return $this->success(null, '删除成功');
    }
}
