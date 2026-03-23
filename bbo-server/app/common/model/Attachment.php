<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 附件模型
 */
class Attachment extends Model
{
    protected $name = 'attachments';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'group_id' => 'integer',
        'is_external' => 'integer',
        'admin_id' => 'integer',
        'user_id' => 'integer',
    ];

    // 文件类型常量
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_FILE = 'file';

    /**
     * 获取分组
     */
    public function group()
    {
        return $this->belongsTo(AttachmentGroup::class, 'group_id');
    }

    /**
     * 格式化文件大小
     */
    public function getSizeTextAttr($value, $data)
    {
        $size = $data['size'] ?? 0;
        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1024 * 1024) {
            return round($size / 1024, 2) . ' KB';
        } elseif ($size < 1024 * 1024 * 1024) {
            return round($size / 1024 / 1024, 2) . ' MB';
        } else {
            return round($size / 1024 / 1024 / 1024, 2) . ' GB';
        }
    }
}
