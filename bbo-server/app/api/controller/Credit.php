<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\CreditApplication;
use app\common\model\UserCreditLimit;
use app\common\model\InstallmentPlan;
use app\common\model\InstallmentOrder;
use app\common\model\InstallmentSchedule;

/**
 * 分期付款 API 控制器
 */
class Credit extends Base
{
    /**
     * 获取分期方案列表
     * GET /credit/plans?amount=100
     */
    public function plans(): Response
    {
        $amount = (float)input('amount', 0);
        if ($amount <= 0) {
            return $this->error('Amount is required');
        }

        // 获取用户信用等级
        $creditLevel = 1;
        if ($this->userId) {
            $userCredit = UserCreditLimit::getByUser($this->userId);
            if ($userCredit) {
                $creditLevel = $userCredit->credit_level;
            }
        }

        $plans = InstallmentPlan::getAvailablePlans($amount, $creditLevel, $this->locale);

        return $this->success($plans);
    }

    /**
     * 计算分期详情
     * POST /credit/calculate
     */
    public function calculate(): Response
    {
        $amount = (float)input('amount', 0);
        $planId = (int)input('plan_id', 0);

        if ($amount <= 0 || $planId <= 0) {
            return $this->error('Invalid parameters');
        }

        $detail = InstallmentPlan::calculateInstallmentDetail($amount, $planId, $this->locale);
        if (!$detail) {
            return $this->error('Plan not available for this amount');
        }

        return $this->success($detail);
    }

    /**
     * 获取用户分期额度
     * GET /credit/limit
     */
    public function limit(): Response
    {
        $credit = UserCreditLimit::getByUser($this->userId);

        if (!$credit) {
            // 检查是否有待审核的申请
            $pendingApplication = CreditApplication::where('user_id', $this->userId)
                ->whereIn('status', [
                    CreditApplication::STATUS_PENDING,
                    CreditApplication::STATUS_REVIEWING,
                    CreditApplication::STATUS_SUPPLEMENT
                ])
                ->find();

            return $this->success([
                'has_credit' => false,
                'pending_application' => $pendingApplication ? [
                    'application_no' => $pendingApplication->application_no,
                    'status' => $pendingApplication->status,
                    'status_text' => $pendingApplication->status_text,
                    'created_at' => $pendingApplication->created_at,
                    'supplement_request' => $pendingApplication->supplement_request,
                ] : null,
            ]);
        }

        return $this->success([
            'has_credit' => true,
            'total_limit' => $credit->total_limit,
            'used_limit' => $credit->used_limit,
            'frozen_limit' => $credit->frozen_limit,
            'available_limit' => $credit->available_limit,
            'credit_level' => $credit->credit_level,
            'status' => $credit->status,
            'expires_at' => $credit->expires_at,
        ]);
    }

    /**
     * 提交分期资质申请
     * POST /credit/apply
     */
    public function apply(): Response
    {
        // 检查是否已有待处理的申请
        if (CreditApplication::hasPendingApplication($this->userId)) {
            return $this->error('You already have a pending application');
        }

        // 检查是否已有额度
        if (UserCreditLimit::hasAvailableLimit($this->userId)) {
            return $this->error('You already have credit limit');
        }

        $data = input();

        // 如果提供了 user_card_id，从用户卡片获取信息
        $userCard = null;
        if (!empty($data['user_card_id'])) {
            $userCard = \app\common\model\UserCard::where('id', $data['user_card_id'])
                ->where('user_id', $this->userId)
                ->with('billingAddress')
                ->find();

            if (!$userCard) {
                return $this->error('Card not found');
            }

            // 从卡片和账单地址自动填充信息
            if ($userCard->billingAddress) {
                $addr = $userCard->billingAddress;
                $data['full_name'] = $data['full_name'] ?? $addr->name;
                $data['phone'] = $data['phone'] ?? $addr->phone;
                $data['country'] = $data['country'] ?? $addr->country;
                $data['city'] = $data['city'] ?? $addr->city;
                $data['address'] = $data['address'] ?? $addr->street;
                $data['postal_code'] = $data['postal_code'] ?? $addr->postal_code;
            }

            // 从卡片获取持卡人和卡片信息
            $data['card_holder_name'] = $data['card_holder_name'] ?? $userCard->cardholder_name;
        }

        // 验证必填字段（简化版，因为很多字段可从卡片获取）
        $required = [
            'full_name', 'id_type', 'id_number', 'id_front_image',
            'phone', 'email', 'address'
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error("Field '{$field}' is required");
            }
        }

        // 验证证件类型
        $validIdTypes = [
            CreditApplication::ID_TYPE_PASSPORT,
            CreditApplication::ID_TYPE_ID_CARD,
            CreditApplication::ID_TYPE_DRIVER_LICENSE
        ];
        if (!in_array($data['id_type'], $validIdTypes)) {
            return $this->error('Invalid ID type');
        }

        // 处理信用卡信息
        $cardNumberEncrypted = null;
        $cardExpiryEncrypted = null;
        $cardLastFour = null;
        $cardBrand = null;

        // 如果通过选择已保存卡片，从卡片获取信息
        if ($userCard) {
            $cardLastFour = $userCard->last_four;
            $cardBrand = $userCard->card_brand;
            // 使用已保存卡片时不需要重新加密卡号
        } elseif (!empty($data['card_number']) && !empty($data['card_expiry'])) {
            // 用户手动输入卡片信息（兼容旧流程）
            $cardNumber = preg_replace('/\s+/', '', $data['card_number']);
            $cardLastFour = substr($cardNumber, -4);
            $cardBrand = $this->detectCardBrand($cardNumber);

            $encryptionKey = config('app.encryption_key', 'bbo_secret_key_2024');
            $cardNumberEncrypted = base64_encode(openssl_encrypt($cardNumber, 'AES-256-CBC', $encryptionKey, 0, substr($encryptionKey, 0, 16)));
            $cardExpiryEncrypted = base64_encode(openssl_encrypt($data['card_expiry'], 'AES-256-CBC', $encryptionKey, 0, substr($encryptionKey, 0, 16)));
        }

        // 创建申请
        $application = new CreditApplication();
        $application->application_no = CreditApplication::generateApplicationNo();
        $application->user_id = $this->userId;
        $application->user_card_id = $data['user_card_id'] ?? null;

        // 身份信息
        $application->full_name = $data['full_name'];
        $application->id_type = $data['id_type'];
        $application->id_number = $data['id_number'];
        $application->id_front_image = $data['id_front_image'];
        $application->id_back_image = $data['id_back_image'] ?? null;
        $application->selfie_image = $data['selfie_image'] ?? null;
        $application->birth_date = $data['birth_date'] ?? null;
        $application->nationality = $data['nationality'] ?? null;

        // 联系信息
        $application->phone = $data['phone'];
        $application->email = $data['email'];
        $application->address = $data['address'];
        $application->city = $data['city'] ?? null;
        $application->country = $data['country'] ?? null;
        $application->postal_code = $data['postal_code'] ?? null;

        // 信用卡信息
        $application->card_holder_name = $data['card_holder_name'] ?? null;
        $application->card_number_encrypted = $cardNumberEncrypted;
        $application->card_last_four = $cardLastFour;
        $application->card_brand = $cardBrand;
        $application->card_expiry_encrypted = $cardExpiryEncrypted;

        // 账单地址：如果没有单独提供，则从联系信息组合
        if (!empty($data['billing_address'])) {
            $application->billing_address = $data['billing_address'];
        } else {
            // 组合完整的账单地址
            $addressParts = [];
            if (!empty($data['address'])) {
                $addressParts[] = $data['address'];
            }
            if (!empty($data['city'])) {
                $addressParts[] = $data['city'];
            }
            if (!empty($data['country'])) {
                $addressParts[] = $data['country'];
            }
            if (!empty($data['postal_code'])) {
                $addressParts[] = $data['postal_code'];
            }
            $application->billing_address = !empty($addressParts) ? implode(', ', $addressParts) : null;
        }

        // 附加信息
        $application->monthly_income = $data['monthly_income'] ?? null;
        $application->employment_status = $data['employment_status'] ?? null;
        $application->employer_name = $data['employer_name'] ?? null;
        $application->income_proof_image = $data['income_proof_image'] ?? null;
        $application->requested_limit = $data['requested_limit'] ?? null;

        // 信用卡账单图片（JSON存储）
        if (!empty($data['statement_images']) && is_array($data['statement_images'])) {
            $application->statement_images = json_encode($data['statement_images']);
        }

        $application->status = CreditApplication::STATUS_PENDING;
        $application->save();

        return $this->success([
            'application_no' => $application->application_no,
            'status' => $application->status,
            'status_text' => $application->status_text,
            'message' => 'Application submitted successfully. We will review it within 1-3 business days.',
        ]);
    }

    /**
     * 查看申请状态
     * GET /credit/application
     */
    public function application(): Response
    {
        $application = CreditApplication::getLatestByUser($this->userId);

        if (!$application) {
            return $this->error('No application found', 404);
        }

        return $this->success([
            'application_no' => $application->application_no,
            'status' => $application->status,
            'status_text' => $application->status_text,
            'full_name' => $application->full_name,
            'id_type' => $application->id_type,
            'id_type_text' => $application->id_type_text,
            'card_last_four' => $application->card_last_four,
            'card_brand' => $application->card_brand,
            'requested_limit' => $application->requested_limit,
            'approved_limit' => $application->approved_limit,
            'reject_reason' => $application->reject_reason,
            'supplement_request' => $application->supplement_request,
            'created_at' => $application->created_at,
            'reviewed_at' => $application->reviewed_at,
        ]);
    }

    /**
     * 补充资料
     * POST /credit/supplement
     */
    public function supplement(): Response
    {
        $application = CreditApplication::where('user_id', $this->userId)
            ->where('status', CreditApplication::STATUS_SUPPLEMENT)
            ->find();

        if (!$application) {
            return $this->error('No application requiring supplement found');
        }

        $data = input();

        // 更新提供的字段
        $allowedFields = [
            'id_front_image', 'id_back_image', 'selfie_image',
            'income_proof_image', 'billing_address'
        ];

        foreach ($allowedFields as $field) {
            if (!empty($data[$field])) {
                $application->$field = $data[$field];
            }
        }

        $application->status = CreditApplication::STATUS_PENDING;
        $application->supplement_request = null;
        $application->save();

        return $this->success([
            'message' => 'Supplementary information submitted successfully',
            'status' => $application->status,
            'status_text' => $application->status_text,
        ]);
    }

    /**
     * 获取我的分期订单列表
     * GET /credit/orders
     */
    public function orders(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = InstallmentOrder::where('user_id', $this->userId)
            ->order('created_at', 'desc');

        $status = input('status');
        if ($status !== null && $status !== '') {
            $query->where('status', (int)$status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        $result = [];
        foreach ($list as $order) {
            $result[] = [
                'id' => $order->id,
                'installment_no' => $order->installment_no,
                'total_amount' => $order->total_amount,
                'financed_amount' => $order->financed_amount,
                'periods' => $order->periods,
                'paid_periods' => $order->paid_periods,
                'period_amount' => $order->period_amount,
                'currency' => $order->currency,
                'status' => $order->status,
                'status_text' => $order->status_text,
                'next_due_date' => $order->next_due_date,
                'overdue_days' => $order->overdue_days,
                'created_at' => $order->created_at,
            ];
        }

        return $this->paginate($result, $total, $page, $pageSize);
    }

    /**
     * 获取分期订单详情
     * GET /credit/orders/:id
     */
    public function orderDetail(int $id): Response
    {
        $order = InstallmentOrder::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        // 获取还款计划
        $schedules = InstallmentSchedule::where('installment_id', $order->id)
            ->order('period', 'asc')
            ->select();

        $scheduleList = [];
        foreach ($schedules as $schedule) {
            $scheduleList[] = [
                'id' => $schedule->id,
                'period' => $schedule->period,
                'principal' => $schedule->principal,
                'interest' => $schedule->interest,
                'fee' => $schedule->fee,
                'late_fee' => $schedule->late_fee,
                'amount' => $schedule->amount,
                'paid_amount' => $schedule->paid_amount,
                'remaining_amount' => $schedule->remaining_amount,
                'due_date' => $schedule->due_date,
                'paid_at' => $schedule->paid_at,
                'status' => $schedule->status,
                'status_text' => $schedule->status_text,
            ];
        }

        return $this->success([
            'id' => $order->id,
            'installment_no' => $order->installment_no,
            'order_id' => $order->order_id,
            'total_amount' => $order->total_amount,
            'down_payment' => $order->down_payment,
            'financed_amount' => $order->financed_amount,
            'total_interest' => $order->total_interest,
            'total_fee' => $order->total_fee,
            'periods' => $order->periods,
            'paid_periods' => $order->paid_periods,
            'period_amount' => $order->period_amount,
            'currency' => $order->currency,
            'status' => $order->status,
            'status_text' => $order->status_text,
            'next_due_date' => $order->next_due_date,
            'overdue_days' => $order->overdue_days,
            'auto_deduct' => $order->auto_deduct,
            'created_at' => $order->created_at,
            'completed_at' => $order->completed_at,
            'schedules' => $scheduleList,
        ]);
    }

    /**
     * 检测信用卡品牌
     */
    private function detectCardBrand(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        if (preg_match('/^4/', $cardNumber)) {
            return 'visa';
        } elseif (preg_match('/^5[1-5]/', $cardNumber) || preg_match('/^2[2-7]/', $cardNumber)) {
            return 'mastercard';
        } elseif (preg_match('/^3[47]/', $cardNumber)) {
            return 'amex';
        } elseif (preg_match('/^6(?:011|5)/', $cardNumber)) {
            return 'discover';
        } elseif (preg_match('/^35/', $cardNumber)) {
            return 'jcb';
        }

        return 'unknown';
    }
}
