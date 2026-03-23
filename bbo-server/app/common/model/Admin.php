<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 管理员模型
 */
class Admin extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'admins';

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
     * 隐藏字段
     * @var array
     */
    protected $hidden = ['password'];

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'role_id' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 密码加密
     * @param string $value
     * @return string
     */
    public function setPasswordAttr(string $value): string
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * 验证密码
     * @param string $password
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * 关联角色
     * @return \think\model\relation\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
