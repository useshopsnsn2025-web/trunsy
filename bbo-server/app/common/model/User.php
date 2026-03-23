<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
use app\common\helper\UrlHelper;

/**
 * 用户模型
 */
class User extends Model
{
    use SoftDelete;

    /**
     * 表名
     * @var string
     */
    protected $name = 'users';

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
     * 软删除字段
     * @var string
     */
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = ['password', 'deleted_at'];

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'gender' => 'integer',
        'is_verified' => 'integer',
        'is_seller' => 'integer',
        'status' => 'integer',
        'is_online' => 'integer',
    ];

    /**
     * 在线状态常量
     */
    const ONLINE_TIMEOUT = 60; // 心跳超时时间（秒）

    /**
     * 密码加密
     * @param string $value
     * @return string
     */
    public function setPasswordAttr(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT);
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
     * 是否为卖家
     * @return bool
     */
    public function isSeller(): bool
    {
        return $this->is_seller === 1;
    }

    /**
     * 是否已验证
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->is_verified === 1;
    }

    /**
     * 关联店铺
     * @return \think\model\relation\HasOne
     */
    public function shop()
    {
        return $this->hasOne(Shop::class, 'user_id', 'id');
    }

    /**
     * 关联收货地址
     * @return \think\model\relation\HasMany
     */
    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id', 'id');
    }

    /**
     * 获取默认地址
     * @return UserAddress|null
     */
    public function getDefaultAddress()
    {
        return $this->addresses()->where('is_default', 1)->find();
    }

    /**
     * 更新最后登录信息
     * @param string $ip
     * @return bool
     */
    public function updateLoginInfo(string $ip): bool
    {
        $this->last_login_at = date('Y-m-d H:i:s');
        $this->last_login_ip = $ip;
        return $this->save();
    }

    /**
     * 转换为数组（API输出格式）
     * @return array
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'nickname' => $this->nickname,
            'avatar' => UrlHelper::convertImageUrl($this->avatar),
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'birthday' => $this->birthday,
            'bio' => $this->bio,
            'language' => $this->language,
            'currency' => $this->currency,
            'isSeller' => $this->is_seller === 1,
            'isVerified' => $this->is_verified === 1,
            'createdAt' => $this->created_at,
            'lastLoginAt' => $this->last_login_at,
        ];
    }

    /**
     * 更新心跳
     * @param string $ip IP地址
     * @param string $device 设备类型
     * @return bool
     */
    public function updateHeartbeat(string $ip = '', string $device = ''): bool
    {
        $this->is_online = 1;
        $this->last_heartbeat_at = date('Y-m-d H:i:s');
        if ($ip) {
            $this->online_ip = $ip;
        }
        if ($device) {
            $this->online_device = $device;
        }
        return $this->save();
    }

    /**
     * 设置离线
     * @return bool
     */
    public function setOffline(): bool
    {
        $this->is_online = 0;
        return $this->save();
    }

    /**
     * 检查是否在线（根据心跳时间判断）
     * @return bool
     */
    public function checkOnline(): bool
    {
        if (!$this->last_heartbeat_at) {
            return false;
        }
        $lastHeartbeat = strtotime($this->last_heartbeat_at);
        return (time() - $lastHeartbeat) <= self::ONLINE_TIMEOUT;
    }

    /**
     * 获取所有超时用户并标记离线
     * @return int 标记离线的用户数
     */
    public static function markTimeoutUsersOffline(): int
    {
        $timeout = date('Y-m-d H:i:s', time() - self::ONLINE_TIMEOUT);
        return self::where('is_online', 1)
            ->where(function($query) use ($timeout) {
                $query->whereNull('last_heartbeat_at')
                    ->whereOr('last_heartbeat_at', '<', $timeout);
            })
            ->update(['is_online' => 0]);
    }

    /**
     * 获取在线用户数量
     * @return int
     */
    public static function getOnlineCount(): int
    {
        return self::where('is_online', 1)->count();
    }
}
