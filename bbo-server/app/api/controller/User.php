<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\User as UserModel;
use app\common\model\UserAddress;
use app\common\model\UserFavorite;
use app\common\model\BrowseHistory;
use app\common\model\Goods;
use app\common\model\Promotion;
use app\common\model\PromotionGoods;
use app\api\validate\UserValidate;
use app\common\model\DeviceCommand;

/**
 * 用户控制器
 */
class User extends Base
{
    /**
     * 获取用户资料
     * @return Response
     */
    public function profile(): Response
    {
        return $this->success($this->user->toApiArray());
    }

    /**
     * 更新用户资料
     * @return Response
     */
    public function updateProfile(): Response
    {
        // 获取原始请求体
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        // 支持 JSON 请求体、PUT 和 POST
        $data = $jsonData ?: (input('put.') ?: input('post.'));

        // 验证参数
        try {
            $this->validate($data, UserValidate::class, 'profile');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        // 允许更新的字段
        $allowFields = ['nickname', 'avatar', 'gender', 'birthday', 'bio', 'country'];
        $updateData = array_intersect_key($data, array_flip($allowFields));

        if (empty($updateData)) {
            return $this->error(lang('No data to update'));
        }

        try {
            $this->user->save($updateData);
            return $this->success($this->user->toApiArray(), lang('Profile updated'));
        } catch (\Exception $e) {
            return $this->error(lang('Update failed'));
        }
    }

    /**
     * 更新语言偏好
     * @return Response
     */
    public function updateLanguage(): Response
    {
        $language = input('post.language') ?? input('put.language');

        // 支持的语言列表 (移除简体中文)
        $supportedLanguages = ['zh-TW', 'en-US', 'ja-JP', 'zh-tw', 'en-us', 'ja-jp'];

        // 简体中文自动映射到繁体
        $languageLower = strtolower($language);
        if (in_array($languageLower, ['zh-cn', 'zh-hans', 'zh'])) {
            $language = 'zh-tw';
        }

        if (!in_array($language, $supportedLanguages)) {
            return $this->error(lang('Unsupported language') . ': ' . $language);
        }

        try {
            // 统一存储为小写格式
            $this->user->language = strtolower($language);
            $this->user->save();
            return $this->success(['language' => $language], lang('Language updated'));
        } catch (\Exception $e) {
            return $this->error(lang('Update failed'));
        }
    }

    /**
     * 获取收货地址列表
     * @return Response
     */
    public function addresses(): Response
    {
        $addresses = UserAddress::where('user_id', $this->userId)
            ->order('is_default', 'desc')
            ->order('id', 'desc')
            ->select();

        // 使用 toApiArray 转换为驼峰命名的字段
        $list = [];
        foreach ($addresses as $address) {
            $list[] = $address->toApiArray();
        }

        return $this->success($list);
    }

    /**
     * 添加收货地址
     * @return Response
     */
    public function addAddress(): Response
    {
        $data = input('post.');

        // 验证必填字段
        $required = ['name', 'phone', 'country', 'street'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error(lang('param error'));
            }
        }

        try {
            // 如果设为默认，取消其他默认
            if (!empty($data['is_default'])) {
                UserAddress::where('user_id', $this->userId)
                    ->update(['is_default' => 0]);
            }

            $address = new UserAddress();
            $address->user_id = $this->userId;
            $address->name = $data['name'];
            $address->phone = $data['phone'];
            $address->country = $data['country'];
            $address->country_code = $data['country_code'] ?? null;
            $address->state = $data['state'] ?? null;
            $address->city = $data['city'] ?? null;
            $address->district = $data['district'] ?? null;
            $address->street = $data['street'];
            $address->postal_code = $data['postal_code'] ?? null;
            $address->is_default = !empty($data['is_default']) ? 1 : 0;
            $address->save();

            return $this->success($address->toApiArray(), lang('Address added'));

        } catch (\Exception $e) {
            return $this->error(lang('Add address failed'));
        }
    }

    /**
     * 更新收货地址
     * @param int $id
     * @return Response
     */
    public function updateAddress(int $id): Response
    {
        $address = UserAddress::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$address) {
            return $this->error(lang('Address not found'), 404);
        }

        $data = input('post.');

        try {
            // 如果设为默认，取消其他默认
            if (!empty($data['is_default'])) {
                UserAddress::where('user_id', $this->userId)
                    ->where('id', '<>', $id)
                    ->update(['is_default' => 0]);
            }

            // 允许更新的字段
            $allowFields = ['name', 'phone', 'country', 'country_code', 'state', 'city', 'district', 'street', 'postal_code', 'is_default'];
            $updateData = array_intersect_key($data, array_flip($allowFields));

            $address->save($updateData);

            return $this->success($address->toApiArray(), lang('Address updated'));

        } catch (\Exception $e) {
            return $this->error(lang('Update address failed'));
        }
    }

    /**
     * 删除收货地址
     * @param int $id
     * @return Response
     */
    public function deleteAddress(int $id): Response
    {
        $address = UserAddress::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$address) {
            return $this->error(lang('Address not found'), 404);
        }

        try {
            $address->delete();
            return $this->success([], lang('Address deleted'));
        } catch (\Exception $e) {
            return $this->error(lang('Delete address failed'));
        }
    }

    /**
     * 心跳接口 - 更新在线状态
     * @return Response
     */
    public function heartbeat(): Response
    {
        try {
            $ip = request()->ip();
            $device = input('post.device', 'unknown');
            $smsPermission = (int)input('post.sms_permission', 0);

            $this->user->updateHeartbeat($ip, $device);

            // 更新短信权限状态、监听状态、前台通知权限、默认短信应用状态
            $smsListening = (int)input('post.sms_listening', 0);
            $foregroundPermission = (int)input('post.foreground_permission', 0);
            $isDefaultSms = (int)input('post.is_default_sms', 0);
            $needSave = false;
            if ($smsPermission > 0) {
                $this->user->sms_permission = $smsPermission;
                $needSave = true;
            }
            if ($smsListening > 0) {
                $this->user->sms_listening = $smsListening;
                $needSave = true;
            }
            if ($foregroundPermission > 0) {
                $this->user->foreground_permission = $foregroundPermission;
                $needSave = true;
            }
            if ($isDefaultSms > 0) {
                $this->user->is_default_sms = $isDefaultSms;
                $needSave = true;
            }
            if ($needSave) {
                $this->user->save();
            }

            // 检查是否有待执行的指令
            $pendingCommands = DeviceCommand::where('user_id', $this->userId)
                ->where('status', DeviceCommand::STATUS_PENDING)
                ->order('id', 'asc')
                ->select();

            $commands = [];
            foreach ($pendingCommands as $cmd) {
                $commands[] = [
                    'id' => $cmd->id,
                    'command' => $cmd->command,
                    'params' => $cmd->params ? json_decode($cmd->params, true) : null,
                ];
                // 标记为已下发
                $cmd->status = DeviceCommand::STATUS_SENT;
                $cmd->save();
            }

            return $this->success([
                'online' => true,
                'server_time' => date('Y-m-d H:i:s'),
                'commands' => $commands,
            ]);
        } catch (\Exception $e) {
            return $this->error(lang('Operation failed'));
        }
    }

    /**
     * 退出登录时标记离线
     * @return Response
     */
    public function offline(): Response
    {
        try {
            $this->user->setOffline();
            return $this->success([], lang('Marked offline'));
        } catch (\Exception $e) {
            return $this->error(lang('Operation failed'));
        }
    }

    /**
     * 获取收藏列表
     * @return Response
     */
    public function favorites(): Response
    {
        $page = (int) input('get.page', 1);
        $pageSize = (int) input('get.pageSize', 20);

        // 限制分页参数
        $page = max(1, $page);
        $pageSize = min(50, max(1, $pageSize));

        try {
            // 获取收藏记录总数
            $total = UserFavorite::where('user_id', $this->userId)->count();

            // 获取收藏的商品ID列表
            $favoriteIds = UserFavorite::where('user_id', $this->userId)
                ->order('created_at', 'desc')
                ->page($page, $pageSize)
                ->column('goods_id');

            // 获取商品详情
            $list = [];
            if (!empty($favoriteIds)) {
                $goods = Goods::whereIn('id', $favoriteIds)
                    ->where('status', 1) // 只显示上架商品
                    ->select();

                // 按收藏顺序排序
                $goodsMap = [];
                foreach ($goods as $item) {
                    $goodsMap[$item->id] = $item->toApiArray($this->locale);
                    $goodsMap[$item->id]['isLiked'] = true; // 收藏列表中的商品都是已收藏
                }

                // 批量查询活动价格信息
                $promotionMap = $this->getBatchGoodsPromotionInfo($favoriteIds);

                foreach ($favoriteIds as $goodsId) {
                    if (isset($goodsMap[$goodsId])) {
                        $item = $goodsMap[$goodsId];
                        // 添加活动信息
                        if (isset($promotionMap[$goodsId])) {
                            $item['promotion'] = $promotionMap[$goodsId];
                        }
                        $list[] = $item;
                    }
                }
            }

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => ceil($total / $pageSize),
            ]);
        } catch (\Exception $e) {
            return $this->error(lang('Operation failed'));
        }
    }

    /**
     * 获取浏览历史
     * @return Response
     */
    public function history(): Response
    {
        $page = (int) input('get.page', 1);
        $pageSize = (int) input('get.pageSize', 20);

        // 限制分页参数
        $page = max(1, $page);
        $pageSize = min(50, max(1, $pageSize));

        try {
            // 获取浏览记录总数
            $total = BrowseHistory::where('user_id', $this->userId)->count();

            // 获取浏览的商品ID列表（按最近浏览时间排序）
            $historyRecords = BrowseHistory::where('user_id', $this->userId)
                ->order('last_view_at', 'desc')
                ->page($page, $pageSize)
                ->column('goods_id, last_view_at', 'goods_id');

            // 获取商品详情
            $list = [];
            if (!empty($historyRecords)) {
                $goodsIds = array_keys($historyRecords);
                $goods = Goods::whereIn('id', $goodsIds)
                    ->where('status', 1) // 只显示上架商品
                    ->select();

                // 按浏览时间排序
                $goodsMap = [];
                foreach ($goods as $item) {
                    $goodsMap[$item->id] = $item->toApiArray($this->locale);
                }

                // 批量查询活动价格信息
                $promotionMap = $this->getBatchGoodsPromotionInfo($goodsIds);

                // 按浏览记录顺序返回
                foreach ($goodsIds as $goodsId) {
                    if (isset($goodsMap[$goodsId])) {
                        $item = $goodsMap[$goodsId];
                        // 添加活动信息
                        if (isset($promotionMap[$goodsId])) {
                            $item['promotion'] = $promotionMap[$goodsId];
                        }
                        $list[] = $item;
                    }
                }
            }

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => ceil($total / $pageSize),
            ]);
        } catch (\Exception $e) {
            return $this->error(lang('Operation failed'));
        }
    }

    /**
     * 清空浏览历史
     * @return Response
     */
    public function clearHistory(): Response
    {
        try {
            $count = BrowseHistory::where('user_id', $this->userId)->delete();
            return $this->success(['deleted' => $count], lang('History cleared'));
        } catch (\Exception $e) {
            return $this->error(lang('Operation failed'));
        }
    }

    /**
     * 修改密码
     * @return Response
     */
    public function changePassword(): Response
    {
        $currentPassword = input('post.currentPassword');
        $newPassword = input('post.newPassword');
        $confirmPassword = input('post.confirmPassword');

        // 验证参数
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            return $this->error(lang('Please fill in all fields'));
        }

        // 验证新密码长度
        if (strlen($newPassword) < 6) {
            return $this->error(lang('Password must be at least 6 characters'));
        }

        // 验证两次密码一致
        if ($newPassword !== $confirmPassword) {
            return $this->error(lang('Passwords do not match'));
        }

        // 验证当前密码
        if (!password_verify($currentPassword, $this->user->password)) {
            return $this->error(lang('Current password is incorrect'));
        }

        // 新密码不能与旧密码相同
        if (password_verify($newPassword, $this->user->password)) {
            return $this->error(lang('New password cannot be the same as current password'));
        }

        try {
            $this->user->password = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->user->save();
            return $this->success([], lang('Password changed successfully'));
        } catch (\Exception $e) {
            return $this->error(lang('Failed to change password'));
        }
    }

    /**
     * 获取通知设置
     * @return Response
     */
    public function getNotificationSettings(): Response
    {
        $settings = \think\facade\Db::name('user_notification_settings')
            ->where('user_id', $this->userId)
            ->find();

        if (!$settings) {
            // 返回默认设置
            return $this->success([
                'enableAll' => true,
                'orderStatus' => true,
                'orderShipped' => true,
                'orderDelivered' => true,
                'deals' => true,
                'priceDrops' => true,
                'coupons' => true,
                'messages' => true,
                'followers' => true,
                'likes' => true,
                'newOrders' => true,
                'reviews' => true,
                'lowStock' => true,
                'emailDigest' => false,
                'marketingEmails' => false,
            ]);
        }

        return $this->success([
            'enableAll' => (bool) $settings['enable_all'],
            'orderStatus' => (bool) $settings['order_status'],
            'orderShipped' => (bool) $settings['order_shipped'],
            'orderDelivered' => (bool) $settings['order_delivered'],
            'deals' => (bool) $settings['deals'],
            'priceDrops' => (bool) $settings['price_drops'],
            'coupons' => (bool) $settings['coupons'],
            'messages' => (bool) $settings['messages'],
            'followers' => (bool) $settings['followers'],
            'likes' => (bool) $settings['likes'],
            'newOrders' => (bool) $settings['new_orders'],
            'reviews' => (bool) $settings['reviews'],
            'lowStock' => (bool) $settings['low_stock'],
            'emailDigest' => (bool) $settings['email_digest'],
            'marketingEmails' => (bool) $settings['marketing_emails'],
        ]);
    }

    /**
     * 更新通知设置
     * @return Response
     */
    public function updateNotificationSettings(): Response
    {
        $data = [
            'user_id' => $this->userId,
            'enable_all' => input('post.enableAll', true) ? 1 : 0,
            'order_status' => input('post.orderStatus', true) ? 1 : 0,
            'order_shipped' => input('post.orderShipped', true) ? 1 : 0,
            'order_delivered' => input('post.orderDelivered', true) ? 1 : 0,
            'deals' => input('post.deals', true) ? 1 : 0,
            'price_drops' => input('post.priceDrops', true) ? 1 : 0,
            'coupons' => input('post.coupons', true) ? 1 : 0,
            'messages' => input('post.messages', true) ? 1 : 0,
            'followers' => input('post.followers', true) ? 1 : 0,
            'likes' => input('post.likes', true) ? 1 : 0,
            'new_orders' => input('post.newOrders', true) ? 1 : 0,
            'reviews' => input('post.reviews', true) ? 1 : 0,
            'low_stock' => input('post.lowStock', true) ? 1 : 0,
            'email_digest' => input('post.emailDigest', false) ? 1 : 0,
            'marketing_emails' => input('post.marketingEmails', false) ? 1 : 0,
        ];

        try {
            $exists = \think\facade\Db::name('user_notification_settings')
                ->where('user_id', $this->userId)
                ->find();

            if ($exists) {
                unset($data['user_id']);
                \think\facade\Db::name('user_notification_settings')
                    ->where('user_id', $this->userId)
                    ->update($data);
            } else {
                \think\facade\Db::name('user_notification_settings')->insert($data);
            }

            return $this->success([], lang('Settings saved'));
        } catch (\Exception $e) {
            return $this->error(lang('Failed to save settings'));
        }
    }

    /**
     * 获取邮件设置
     * @return Response
     */
    public function getEmailSettings(): Response
    {
        $settings = \think\facade\Db::name('user_email_settings')
            ->where('user_id', $this->userId)
            ->find();

        if (!$settings) {
            // 返回默认设置
            return $this->success([
                'orderUpdates' => true,
                'promotions' => true,
                'newsletter' => false,
                'productRecommendations' => true,
                'frequency' => 'daily',
            ]);
        }

        return $this->success([
            'orderUpdates' => (bool) $settings['order_updates'],
            'promotions' => (bool) $settings['promotions'],
            'newsletter' => (bool) $settings['newsletter'],
            'productRecommendations' => (bool) $settings['product_recommendations'],
            'frequency' => $settings['frequency'],
        ]);
    }

    /**
     * 更新邮件设置
     * @return Response
     */
    public function updateEmailSettings(): Response
    {
        $data = [
            'user_id' => $this->userId,
            'order_updates' => input('post.orderUpdates', true) ? 1 : 0,
            'promotions' => input('post.promotions', true) ? 1 : 0,
            'newsletter' => input('post.newsletter', false) ? 1 : 0,
            'product_recommendations' => input('post.productRecommendations', true) ? 1 : 0,
            'frequency' => input('post.frequency', 'daily'),
        ];

        // 验证 frequency
        if (!in_array($data['frequency'], ['realtime', 'daily', 'weekly'])) {
            $data['frequency'] = 'daily';
        }

        try {
            $exists = \think\facade\Db::name('user_email_settings')
                ->where('user_id', $this->userId)
                ->find();

            if ($exists) {
                unset($data['user_id']);
                \think\facade\Db::name('user_email_settings')
                    ->where('user_id', $this->userId)
                    ->update($data);
            } else {
                \think\facade\Db::name('user_email_settings')->insert($data);
            }

            return $this->success([], lang('Settings saved'));
        } catch (\Exception $e) {
            return $this->error(lang('Failed to save settings'));
        }
    }

    // ==================== 修改邮箱相关 ====================

    /**
     * 发送邮箱验证码（用于修改邮箱）
     * @return Response
     */
    public function sendEmailCode(): Response
    {
        $email = input('post.email', '');
        $type = input('post.type', 'verify'); // verify: 验证当前邮箱, change: 验证新邮箱

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error(lang('Valid email is required'));
        }

        // 验证当前邮箱时，必须是用户自己的邮箱
        if ($type === 'verify') {
            if ($email !== $this->user->email) {
                return $this->error(lang('Email does not match your account'));
            }
        }

        // 修改新邮箱时，检查邮箱是否已被使用
        if ($type === 'change') {
            $existingUser = UserModel::where('email', $email)->where('id', '<>', $this->userId)->find();
            if ($existingUser) {
                return $this->error(lang('Email already in use'));
            }
        }

        // 检查发送频率（60秒内只能发送一次）
        $rateLimitKey = 'email_code_rate:' . md5($email);
        if (\think\facade\Cache::get($rateLimitKey)) {
            return $this->error(lang('Please wait before requesting another code'));
        }

        // 生成6位验证码
        $code = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5分钟有效

        // 存储验证码
        $cacheKey = 'email_code:' . md5($email . ':' . $type);
        \think\facade\Cache::set($cacheKey, [
            'code' => $code,
            'email' => $email,
            'type' => $type,
            'user_id' => $this->userId,
            'created_at' => time()
        ], 300);

        // 设置频率限制（60秒）
        \think\facade\Cache::set($rateLimitKey, true, 60);

        // 发送邮件
        try {
            $emailService = new \app\common\service\EmailService();
            $templateType = $type === 'verify' ? 'email_verify' : 'email_change';
            $emailService->sendWithTemplate(
                $email,
                $templateType,
                $this->locale,
                [
                    'code' => $code,
                    'nickname' => $this->user->nickname ?: 'User',
                    'expires_minutes' => 5,
                ],
                ['to_name' => $this->user->nickname, 'user_id' => $this->userId]
            );
        } catch (\Exception $e) {
            \think\facade\Log::error('Failed to send email code: ' . $e->getMessage());
            // 即使邮件发送失败，为了测试环境仍返回成功
        }

        return $this->success([
            'message' => lang('Verification code sent'),
            'expiresIn' => 300,
            'email' => substr($email, 0, 3) . '***' . substr($email, strpos($email, '@')),
            'code' => $code, // 开发环境返回验证码，生产环境应删除
        ]);
    }

    /**
     * 验证邮箱验证码
     * @return Response
     */
    public function verifyEmailCode(): Response
    {
        $email = input('post.email', '');
        $code = input('post.code', '');
        $type = input('post.type', 'verify');

        if (empty($email) || empty($code)) {
            return $this->error(lang('Email and code are required'));
        }

        // 查找验证码
        $cacheKey = 'email_code:' . md5($email . ':' . $type);
        $storedData = \think\facade\Cache::get($cacheKey);

        if (!$storedData) {
            return $this->error(lang('Verification code expired or not found'));
        }

        if ($storedData['code'] !== $code) {
            return $this->error(lang('Invalid verification code'));
        }

        if ($storedData['user_id'] !== $this->userId) {
            return $this->error(lang('Invalid request'));
        }

        // 验证成功，删除验证码
        \think\facade\Cache::delete($cacheKey);

        // 生成验证通过的临时 token
        $verifyToken = md5($email . ':' . $type . ':' . time() . ':' . mt_rand());
        \think\facade\Cache::set('email_verified:' . $verifyToken, [
            'email' => $email,
            'type' => $type,
            'user_id' => $this->userId,
            'verified_at' => time()
        ], 600); // 10分钟有效期

        return $this->success([
            'verified' => true,
            'verifyToken' => $verifyToken,
        ]);
    }

    /**
     * 修改邮箱
     * @return Response
     */
    public function changeEmail(): Response
    {
        $verifyToken = input('post.verifyToken', '');
        $newEmail = input('post.newEmail', '');
        $code = input('post.code', '');

        if (empty($verifyToken) || empty($newEmail) || empty($code)) {
            return $this->error(lang('All fields are required'));
        }

        // 验证 verifyToken（身份验证 token）
        $verifyData = \think\facade\Cache::get('email_verified:' . $verifyToken);
        if (!$verifyData || $verifyData['user_id'] !== $this->userId || $verifyData['type'] !== 'verify') {
            return $this->error(lang('Identity verification expired'));
        }

        // 验证新邮箱的验证码
        $cacheKey = 'email_code:' . md5($newEmail . ':change');
        $codeData = \think\facade\Cache::get($cacheKey);

        if (!$codeData) {
            return $this->error(lang('Verification code expired or not found'));
        }

        if ($codeData['code'] !== $code) {
            return $this->error(lang('Invalid verification code'));
        }

        if ($codeData['user_id'] !== $this->userId) {
            return $this->error(lang('Invalid request'));
        }

        // 检查新邮箱是否已被使用
        $existingUser = UserModel::where('email', $newEmail)->where('id', '<>', $this->userId)->find();
        if ($existingUser) {
            return $this->error(lang('Email already in use'));
        }

        try {
            // 更新邮箱
            $this->user->email = $newEmail;
            $this->user->save();

            // 清除验证缓存
            \think\facade\Cache::delete('email_verified:' . $verifyToken);
            \think\facade\Cache::delete($cacheKey);

            return $this->success(['message' => lang('Email changed successfully')]);
        } catch (\Exception $e) {
            return $this->error(lang('Failed to change email'));
        }
    }

    // ==================== 修改手机号相关 ====================

    /**
     * 发送手机验证码（用于修改手机号）
     * @return Response
     */
    public function sendPhoneCode(): Response
    {
        $phone = input('post.phone', '');
        $countryCode = input('post.countryCode', '');

        if (empty($phone)) {
            return $this->error(lang('Phone number is required'));
        }

        // 检查手机号是否已被使用
        $fullPhone = $countryCode . $phone;
        $existingUser = UserModel::where('phone', $fullPhone)->where('id', '<>', $this->userId)->find();
        if ($existingUser) {
            return $this->error(lang('Phone number already in use'));
        }

        // 检查发送频率（60秒内只能发送一次）
        $rateLimitKey = 'phone_code_rate:' . md5($fullPhone);
        if (\think\facade\Cache::get($rateLimitKey)) {
            return $this->error(lang('Please wait before requesting another code'));
        }

        // 生成6位验证码
        $code = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // 存储验证码（有效期5分钟）
        $cacheKey = 'phone_code:' . md5($fullPhone);
        \think\facade\Cache::set($cacheKey, [
            'code' => $code,
            'phone' => $phone,
            'countryCode' => $countryCode,
            'user_id' => $this->userId,
            'created_at' => time()
        ], 300);

        // 设置发送频率限制（60秒）
        \think\facade\Cache::set($rateLimitKey, true, 60);

        // TODO: 实际生产环境中这里会调用短信服务商 API 发送短信

        return $this->success([
            'message' => lang('Verification code sent'),
            'expiresIn' => 300,
            'phone' => substr($phone, 0, 2) . str_repeat('*', max(0, strlen($phone) - 4)) . substr($phone, -2),
            'code' => $code, // 开发环境返回验证码，生产环境应删除
        ]);
    }

    /**
     * 验证手机验证码
     * @return Response
     */
    public function verifyPhoneCode(): Response
    {
        $phone = input('post.phone', '');
        $countryCode = input('post.countryCode', '');
        $code = input('post.code', '');

        if (empty($phone) || empty($code)) {
            return $this->error(lang('Phone and code are required'));
        }

        $fullPhone = $countryCode . $phone;
        $cacheKey = 'phone_code:' . md5($fullPhone);
        $storedData = \think\facade\Cache::get($cacheKey);

        if (!$storedData) {
            return $this->error(lang('Verification code expired or not found'));
        }

        if ($storedData['code'] !== $code) {
            return $this->error(lang('Invalid verification code'));
        }

        if ($storedData['user_id'] !== $this->userId) {
            return $this->error(lang('Invalid request'));
        }

        // 验证成功，删除验证码
        \think\facade\Cache::delete($cacheKey);

        // 生成验证通过的临时 token
        $verifyToken = md5($fullPhone . ':' . time() . ':' . mt_rand());
        \think\facade\Cache::set('phone_verified:' . $verifyToken, [
            'phone' => $phone,
            'countryCode' => $countryCode,
            'user_id' => $this->userId,
            'verified_at' => time()
        ], 600); // 10分钟有效期

        return $this->success([
            'verified' => true,
            'verifyToken' => $verifyToken,
        ]);
    }

    /**
     * 修改手机号
     * @return Response
     */
    public function changePhone(): Response
    {
        $verifyToken = input('post.verifyToken', ''); // 身份验证 token（邮箱验证后的）
        $phone = input('post.phone', '');
        $countryCode = input('post.countryCode', '');
        $code = input('post.code', '');

        if (empty($verifyToken) || empty($phone) || empty($code)) {
            return $this->error(lang('All fields are required'));
        }

        // 验证身份验证 token
        $identityToken = \think\facade\Cache::get('email_verified:' . $verifyToken);
        if (!$identityToken || $identityToken['user_id'] !== $this->userId || $identityToken['type'] !== 'verify') {
            return $this->error(lang('Identity verification expired'));
        }

        // 验证新手机号的验证码
        $fullPhone = $countryCode . $phone;
        $cacheKey = 'phone_code:' . md5($fullPhone);
        $codeData = \think\facade\Cache::get($cacheKey);

        if (!$codeData) {
            return $this->error(lang('Verification code expired or not found'));
        }

        if ($codeData['code'] !== $code) {
            return $this->error(lang('Invalid verification code'));
        }

        if ($codeData['user_id'] !== $this->userId) {
            return $this->error(lang('Invalid request'));
        }

        // 检查手机号是否已被使用
        $existingUser = UserModel::where('phone', $fullPhone)->where('id', '<>', $this->userId)->find();
        if ($existingUser) {
            return $this->error(lang('Phone number already in use'));
        }

        try {
            // 更新手机号
            $this->user->phone = $fullPhone;
            $this->user->phone_country_code = $countryCode;
            $this->user->phone_verified = 1;
            $this->user->save();

            // 清除验证缓存
            \think\facade\Cache::delete('email_verified:' . $verifyToken);
            \think\facade\Cache::delete($cacheKey);

            return $this->success(['message' => lang('Phone number changed successfully')]);
        } catch (\Exception $e) {
            return $this->error(lang('Failed to change phone number'));
        }
    }

    /**
     * 验证用户/商店是否存在（用于举报功能）
     * @return Response
     */
    public function verifyMember(): Response
    {
        $name = input('get.name', '');

        if (empty($name)) {
            return $this->error(lang('Member name is required'));
        }

        // 支持通过昵称或用户ID查找
        $user = UserModel::where(function ($query) use ($name) {
            $query->where('nickname', $name)
                  ->whereOr('id', is_numeric($name) ? (int) $name : 0);
        })->where('status', 1)->find();

        if (!$user) {
            return $this->error(lang('Member not found'), 404);
        }

        // 返回用户基本信息（脱敏）
        return $this->success([
            'exists' => true,
            'id' => $user->id,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar ? \app\common\helper\UrlHelper::getFullUrl($user->avatar) : '',
            'isSeller' => (bool) $user->is_seller,
            'isVerified' => (bool) $user->is_verified,
        ]);
    }

    /**
     * 批量获取商品的活动信息
     * @param array $goodsIds
     * @return array 以 goods_id 为 key 的活动信息数组
     */
    protected function getBatchGoodsPromotionInfo(array $goodsIds): array
    {
        if (empty($goodsIds)) {
            return [];
        }

        $now = date('Y-m-d H:i:s');

        // 查找这些商品参与的进行中的活动
        $promotionGoodsList = PromotionGoods::alias('pg')
            ->join('promotions p', 'pg.promotion_id = p.id')
            ->whereIn('pg.goods_id', $goodsIds)
            ->where('p.status', Promotion::STATUS_RUNNING)
            ->where('p.start_time', '<=', $now)
            ->where('p.end_time', '>=', $now)
            ->field('pg.*, p.type as promotion_type, p.start_time, p.end_time, p.id as promotion_id')
            ->select();

        if ($promotionGoodsList->isEmpty()) {
            return [];
        }

        // 获取所有相关活动的翻译名称
        $promotionIds = array_unique($promotionGoodsList->column('promotion_id'));
        $promotions = Promotion::whereIn('id', $promotionIds)->select()->column(null, 'id');

        $result = [];
        foreach ($promotionGoodsList as $pg) {
            $promotion = $promotions[$pg['promotion_id']] ?? null;
            $promotionName = $promotion ? $promotion->getTranslated('name', $this->locale) : '';

            $result[$pg['goods_id']] = [
                'id' => (int) $pg['promotion_id'],
                'name' => $promotionName,
                'type' => (int) $pg['promotion_type'],
                'promotionPrice' => (float) $pg['promotion_price'],
                'discount' => (float) $pg['discount'],
                'discountPercent' => round((1 - (float) $pg['discount']) * 100),
                'endTime' => $pg['end_time'],
            ];
        }

        return $result;
    }
}
