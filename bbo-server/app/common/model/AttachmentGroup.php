<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 附件分组模型
 */
class AttachmentGroup extends Model
{
    protected $name = 'attachment_groups';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    protected $type = [
        'id' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 获取分组下的附件
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'group_id');
    }

    /**
     * 获取分组下的附件数量
     */
    public function getCountAttr($value, $data)
    {
        return Attachment::where('group_id', $data['id'])->count();
    }
}
