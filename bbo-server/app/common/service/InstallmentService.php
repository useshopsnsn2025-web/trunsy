<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Order;
use app\common\model\InstallmentOrder;
use app\common\model\InstallmentSchedule;
use app\common\model\InstallmentPlan;
use app\common\model\UserCreditLimit;
use think\facade\Db;
use think\facade\Log;

/**
 * 分期付款服务
 */
class InstallmentService
{
    /**
     * 创建分期订单（审核通过时调用）
     * @param Order $order 商品订单
     * @return array
     */
    public function createInstallmentOrder(Order $order): array
    {
        // 验证订单类型
        if ($order->payment_type !== Order::PAYMENT_TYPE_INSTALLMENT) {
            return ['success' => false, 'error' => 'Not an installment order'];
        }

        // 获取分期方案
        $planId = $order->installment_id;
        if (!$planId) {
            return ['success' => false, 'error' => 'No installment plan specified'];
        }

        $plan = InstallmentPlan::find($planId);
        if (!$plan || !$plan->is_enabled) {
            return ['success' => false, 'error' => 'Invalid installment plan'];
        }

        // 计算分期详情
        $detail = InstallmentPlan::calculateInstallmentDetail($order->total_amount, $planId);
        if (!$detail) {
            return ['success' => false, 'error' => 'Failed to calculate installment detail'];
        }

        // 检查用户信用额度
        $creditLimit = UserCreditLimit::getByUser($order->buyer_id);
        if (!$creditLimit) {
            return ['success' => false, 'error' => 'User has no credit limit'];
        }

        if ($creditLimit->available_limit < $order->total_amount) {
            return ['success' => false, 'error' => 'Insufficient credit limit'];
        }

        Db::startTrans();
        try {
            // 冻结用户额度
            $creditLimit->freezeLimit($order->total_amount);

            // 创建分期订单
            $installmentNo = InstallmentOrder::generateInstallmentNo();
            $installmentOrder = InstallmentOrder::create([
                'installment_no' => $installmentNo,
                'user_id' => $order->buyer_id,
                'order_id' => $order->id,
                'plan_id' => $planId,
                'periods' => $detail['periods'],
                'original_amount' => $detail['original_amount'],
                'financed_amount' => $detail['original_amount'],  // 融资金额 = 原始金额
                'total_amount' => $detail['total_amount'],
                'period_amount' => $detail['period_amount'],
                'total_fee' => $detail['total_fee'],
                'total_interest' => $detail['total_interest'],
                'paid_periods' => 0,
                'status' => InstallmentOrder::STATUS_ACTIVE,
                'next_due_date' => date('Y-m-d', strtotime('+1 month')),
                'currency' => $order->currency,
            ]);

            // 创建还款计划
            $firstPeriodAmount = 0;
            $secondPeriodDueDate = null;
            foreach ($detail['schedules'] as $index => $schedule) {
                $isPaid = ($schedule['period'] == 1); // 第一期在审核通过时直接标记为已付

                $scheduleRecord = InstallmentSchedule::create([
                    'installment_id' => $installmentOrder->id,
                    'period' => $schedule['period'],
                    'principal' => $schedule['principal'],
                    'interest' => $schedule['interest'],
                    'fee' => $schedule['fee'],
                    'amount' => $schedule['amount'],
                    'due_date' => $schedule['due_date'],
                    'status' => $isPaid ? InstallmentSchedule::STATUS_PAID : InstallmentSchedule::STATUS_PENDING,
                    'paid_amount' => $isPaid ? $schedule['amount'] : 0,
                    'paid_at' => $isPaid ? date('Y-m-d H:i:s') : null,
                    'late_fee' => 0,
                ]);

                if ($schedule['period'] == 1) {
                    $firstPeriodAmount = $schedule['amount'];
                }
                if ($schedule['period'] == 2) {
                    $secondPeriodDueDate = $schedule['due_date'];
                }
            }

            // 更新分期订单：第一期已付
            $installmentOrder->paid_periods = 1;
            $installmentOrder->next_due_date = $secondPeriodDueDate ?: date('Y-m-d', strtotime('+2 months'));
            $installmentOrder->save();

            // 更新商品订单
            $order->installment_id = $installmentOrder->id;  // 关联分期订单（之前存的是plan_id，现在改为分期订单ID）
            $order->status = Order::STATUS_PENDING_SHIPMENT;
            $order->paid_at = date('Y-m-d H:i:s');
            // 分期付款：审核通过即完成第一期付款
            $order->paid_amount = $firstPeriodAmount;
            $order->save();

            Db::commit();

            Log::info("Installment order created: {$installmentNo} for order {$order->order_no}");

            return [
                'success' => true,
                'installment_id' => $installmentOrder->id,
                'installment_no' => $installmentNo,
                'periods' => $detail['periods'],
                'period_amount' => $detail['period_amount'],
                'total_amount' => $detail['total_amount'],
                'first_due_date' => $detail['schedules'][0]['due_date'] ?? null,
            ];

        } catch (\Exception $e) {
            Db::rollback();
            Log::error("Failed to create installment order: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 取消分期订单（订单取消时调用）
     * @param int $orderId 商品订单ID
     * @return array
     */
    public function cancelInstallmentOrder(int $orderId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        if ($order->payment_type !== Order::PAYMENT_TYPE_INSTALLMENT) {
            return ['success' => true];  // 非分期订单，直接返回成功
        }

        $installmentOrder = InstallmentOrder::where('order_id', $orderId)->find();
        if (!$installmentOrder) {
            return ['success' => true];  // 没有分期订单，直接返回成功
        }

        // 检查是否有已还款期数
        if ($installmentOrder->paid_periods > 0) {
            return ['success' => false, 'error' => 'Cannot cancel installment with paid periods'];
        }

        Db::startTrans();
        try {
            // 释放用户额度
            $creditLimit = UserCreditLimit::getByUser($installmentOrder->user_id);
            if ($creditLimit) {
                $creditLimit->releaseLimit($installmentOrder->financed_amount);
            }

            // 删除还款计划
            InstallmentSchedule::where('installment_id', $installmentOrder->id)->delete();

            // 更新分期订单状态
            $installmentOrder->status = InstallmentOrder::STATUS_CANCELLED;
            $installmentOrder->save();

            Db::commit();

            return ['success' => true];

        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
