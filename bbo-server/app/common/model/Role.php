<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 角色模型
 */
class Role extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'roles';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'updated_at';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
        'permissions' => 'json',
    ];

    /**
     * 关联管理员
     * @return \think\model\relation\HasMany
     */
    public function admins()
    {
        return $this->hasMany(Admin::class, 'role_id', 'id');
    }

    /**
     * 检查是否有权限
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        if (in_array('*', $permissions)) {
            return true;
        }
        return in_array($permission, $permissions);
    }
}
