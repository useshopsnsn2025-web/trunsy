<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 客服工作组模型
 */
class ServiceGroup extends Model
{
    protected $name = 'service_groups';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'sort' => 'integer',
        'is_enabled' => 'integer',
    ];

    /**
     * 关联客服成员
     */
    public function members()
    {
        return $this->belongsToMany(CustomerService::class, 'service_group_members', 'service_id', 'group_id');
    }
}
