<?php
declare(strict_types=1);

namespace app\api\validate;

use think\Validate;

/**
 * 用户验证器
 */
class UserValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'email' => 'email',
        'phone' => 'mobile',
        'password' => 'require|min:6|max:32',
        'nickname' => 'min:2|max:50',  // 更新时不强制要求
        'avatar' => 'max:500',  // 允许URL或路径，只限制长度
        'gender' => 'in:0,1,2',
        'birthday' => 'date',
        'bio' => 'max:200',
        'language' => 'in:zh-cn,zh-tw,en-us,ja-jp',
        'currency' => 'in:USD,CNY,HKD,TWD,EUR,GBP',
    ];

    /**
     * 错误信息
     * @var array
     */
    protected $message = [
        'email.email' => 'Invalid email format',
        'phone.mobile' => 'Invalid phone format',
        'password.require' => 'Password is required',
        'password.min' => 'Password must be at least 6 characters',
        'password.max' => 'Password must be at most 32 characters',
        'nickname.require' => 'Nickname is required',
        'nickname.min' => 'Nickname must be at least 2 characters',
        'nickname.max' => 'Nickname must be at most 50 characters',
        'avatar.max' => 'Avatar URL is too long',
        'gender.in' => 'Invalid gender value',
        'birthday.date' => 'Invalid birthday format',
        'bio.max' => 'Bio must be at most 200 characters',
        'language.in' => 'Unsupported language',
        'currency.in' => 'Unsupported currency',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'register' => ['email', 'phone', 'password', 'nickname'],
        'login' => ['password'],
        'profile' => ['nickname', 'avatar', 'gender', 'birthday', 'bio'],
        'language' => ['language'],
        'currency' => ['currency'],
    ];

    /**
     * 注册场景验证
     * @return UserValidate
     */
    public function sceneRegister(): UserValidate
    {
        return $this->only(['email', 'phone', 'password', 'nickname'])
            ->append('password', 'require')
            ->append('nickname', 'require');
    }

    /**
     * 登录场景验证
     * @return UserValidate
     */
    public function sceneLogin(): UserValidate
    {
        return $this->only(['email', 'phone', 'password'])
            ->append('password', 'require');
    }
}
