<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\User as UserModel;
use app\common\model\UserWallet;

/**
 * 用户管理控制器
 */
class User extends Base
{
    /**
     * 用户列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = UserModel::order('id', 'desc');

        // 搜索条件
        $keyword = input('keyword', '');
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('nickname', 'like', "%{$keyword}%")
                    ->whereOr('email', 'like', "%{$keyword}%")
                    ->whereOr('phone', 'like', "%{$keyword}%")
                    ->whereOr('uuid', 'like', "%{$keyword}%");
            });
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $isSeller = input('is_seller', '');
        if ($isSeller !== '') {
            $query->where('is_seller', (int) $isSeller);
        }

        $isVerified = input('is_verified', '');
        if ($isVerified !== '') {
            $query->where('is_verified', (int) $isVerified);
        }

        // 在线状态筛选
        $isOnline = input('is_online', '');
        if ($isOnline !== '') {
            $query->where('is_online', (int) $isOnline);
        }

        // 时间范围
        $startDate = input('start_date', '');
        $endDate = input('end_date', '');
        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)
            ->field('id,uuid,nickname,avatar,email,plain_password,phone,gender,language,currency,is_seller,is_verified,is_official,status,is_online,last_heartbeat_at,online_device,online_ip,created_at,last_login_at')
            ->select()
            ->toArray();

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 创建用户
     * @return Response
     */
    public function save(): Response
    {
        $email = input('post.email', '');
        $password = input('post.password', '123456');
        $nickname = input('post.nickname', '');
        $isOfficial = input('post.is_official', 1);

        // 验证必填字段
        if (empty($email)) {
            return $this->error('邮箱不能为空');
        }

        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('邮箱格式不正确');
        }

        // 检查邮箱是否已存在
        $exists = UserModel::where('email', $email)->find();
        if ($exists) {
            return $this->error('邮箱已存在');
        }

        // 生成 UUID
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        // 创建用户
        $user = new UserModel();
        $user->uuid = $uuid;
        $user->email = $email;
        $user->password = $password;
        $user->nickname = $nickname ?: explode('@', $email)[0];
        $user->is_official = $isOfficial;
        $user->status = 1;
        $user->register_source = 'admin';
        $user->save();

        return $this->success($user->toArray(), '用户创建成功');
    }

    /**
     * 用户详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $user = UserModel::field('id,uuid,nickname,avatar,email,phone,gender,birthday,bio,language,currency,is_seller,is_verified,is_official,status,is_online,last_heartbeat_at,online_device,online_ip,created_at,updated_at,last_login_at,last_login_ip')
            ->find($id);

        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $user->toArray();

        // 统计数据
        $data['statistics'] = [
            'goods_count' => \app\common\model\Goods::where('user_id', $id)->count(),
            'order_buy_count' => \app\common\model\Order::where('buyer_id', $id)->count(),
            'order_sell_count' => \app\common\model\Order::where('seller_id', $id)->count(),
        ];

        return $this->success($data);
    }

    /**
     * 更新用户
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = input('post.');

        // 允许更新的字段
        $allowFields = ['nickname', 'status', 'is_seller', 'is_verified', 'is_official'];

        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $user->$field = $data[$field];
            }
        }
        $user->save();

        return $this->success([], '更新成功');
    }

    /**
     * 禁用用户
     * @param int $id
     * @return Response
     */
    public function disable(int $id): Response
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $user->status = 0;
        $user->save();

        return $this->success([], '用户已禁用');
    }

    /**
     * 启用用户
     * @param int $id
     * @return Response
     */
    public function enable(int $id): Response
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $user->status = 1;
        $user->save();

        return $this->success([], '用户已启用');
    }

    /**
     * 重置密码
     * @param int $id
     * @return Response
     */
    public function resetPassword(int $id): Response
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $newPassword = input('post.password', '');
        if (strlen($newPassword) < 6) {
            return $this->error('密码长度不能少于6位');
        }

        $user->password = $newPassword;
        $user->save();

        return $this->success([], '密码已重置');
    }

    /**
     * 获取官方用户列表
     * @return Response
     */
    public function official(): Response
    {
        $list = UserModel::where('is_official', 1)
            ->where('status', 1)
            ->field('id,uuid,nickname,avatar,email')
            ->select()
            ->toArray();

        return $this->success($list);
    }

    /**
     * 用户统计
     * @return Response
     */
    public function statistics(): Response
    {
        // 总用户数
        $totalUsers = UserModel::count();

        // 今日新增
        $today = date('Y-m-d');
        $todayUsers = UserModel::whereDay('created_at', $today)->count();

        // 本月新增
        $monthUsers = UserModel::whereMonth('created_at')->count();

        // 卖家数量
        $sellerCount = UserModel::where('is_seller', 1)->count();

        // 已认证用户
        $verifiedCount = UserModel::where('is_verified', 1)->count();

        // 活跃用户（7天内登录）
        $activeUsers = UserModel::where('last_login_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))->count();

        // 当前在线用户数
        $onlineUsers = UserModel::getOnlineCount();

        return $this->success([
            'total_users' => $totalUsers,
            'today_users' => $todayUsers,
            'month_users' => $monthUsers,
            'seller_count' => $sellerCount,
            'verified_count' => $verifiedCount,
            'active_users' => $activeUsers,
            'online_users' => $onlineUsers,
        ]);
    }

    /**
     * 获取用户钱包信息
     * @param int $id
     * @return Response
     */
    public function wallet(int $id): Response
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('User not found', 404);
        }

        $wallet = UserWallet::getOrCreate($id);

        return $this->success([
            'balance'        => (float) $wallet->balance,
            'frozen'         => (float) $wallet->frozen,
            'total_income'   => (float) $wallet->total_income,
            'total_withdraw' => (float) $wallet->total_withdraw,
        ]);
    }

    /**
     * 调整用户余额
     * @param int $id
     * @return Response
     */
    public function adjustBalance(int $id): Response
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('User not found', 404);
        }

        $amount = (float) input('post.amount', 0);
        if ($amount == 0) {
            return $this->error('Amount cannot be zero');
        }

        $wallet = UserWallet::getOrCreate($id);
        $newBalance = $wallet->balance + $amount;

        if ($newBalance < 0) {
            return $this->error('Insufficient balance');
        }

        $wallet->balance = $newBalance;
        $wallet->save();

        return $this->success(['new_balance' => (float) $newBalance]);
    }
}
