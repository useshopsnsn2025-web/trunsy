<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Order;
use app\common\model\PaymentTransaction;
use think\facade\Db;
use think\facade\Log;

/**
 * 预授权服务
 * 处理COD订单的预授权、扣款、取消等操作
 */
class PreAuthService
{
    /**
     * 预授权有效期（天）
     */
    const PREAUTH_EXPIRE_DAYS = 7;

    /**
     * 创建预授权
     * @param int $orderId 订单ID
     * @param float $amount 预授权金额
     * @param array $cardInfo 支付卡信息
     * @return array
     */
    public function createPreAuth(int $orderId, float $amount, array $cardInfo = []): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        Db::startTrans();
        try {
            // TODO: 调用支付网关创建预授权
            // PayPal: Authorization, Stripe: PaymentIntent with capture_method=manual
            // 这里先模拟成功，实际需要集成支付网关
            $gatewayResult = $this->callPaymentGateway('authorize', [
                'amount' => $amount,
                'currency' => $order->currency,
                'card' => $cardInfo,
                'order_no' => $order->order_no,
                'description' => "Pre-authorization for COD order #{$order->order_no}",
            ]);

            if (!$gatewayResult['success']) {
                Db::rollback();
                return $gatewayResult;
            }

            // 更新订单预授权信息
            $order->preauth_transaction_id = $gatewayResult['transaction_id'];
            $order->preauth_amount = $amount;
            $order->preauth_status = Order::PREAUTH_STATUS_AUTHORIZED;
            $order->preauth_expires_at = date('Y-m-d H:i:s', strtotime('+' . self::PREAUTH_EXPIRE_DAYS . ' days'));
            $order->cod_status = Order::COD_STATUS_AUTHORIZED;
            $order->status = Order::STATUS_PENDING_SHIPMENT;
            $order->save();

            // 记录交易
            PaymentTransaction::create([
                'transaction_no' => PaymentTransaction::generateTransactionNo(),
                'order_id' => $orderId,
                'order_no' => $order->order_no,
                'user_id' => $order->buyer_id,
                'payment_method' => $order->payment_method ?? 'card',
                'gateway_transaction_id' => $gatewayResult['transaction_id'],
                'amount' => $amount,
                'currency' => $order->currency,
                'type' => PaymentTransaction::TYPE_PREAUTH,
                'status' => PaymentTransaction::STATUS_SUCCESS,
                'gateway_response' => $gatewayResult,
            ]);

            Db::commit();

            Log::info("PreAuth created for order {$order->order_no}, amount: {$amount}");

            return [
                'success' => true,
                'transaction_id' => $gatewayResult['transaction_id'],
                'preauth_amount' => $amount,
                'expires_at' => $order->preauth_expires_at,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error("PreAuth failed for order {$orderId}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 完成预授权扣款（用户确认收货时调用）
     * @param int $orderId
     * @return array
     */
    public function capturePreAuth(int $orderId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        if ($order->preauth_status !== Order::PREAUTH_STATUS_AUTHORIZED) {
            return ['success' => false, 'error' => 'Invalid preauth status'];
        }

        // 检查预授权是否过期
        if ($order->preauth_expires_at && strtotime($order->preauth_expires_at) < time()) {
            return ['success' => false, 'error' => 'Preauth expired'];
        }

        Db::startTrans();
        try {
            // 调用支付网关完成扣款
            $gatewayResult = $this->callPaymentGateway('capture', [
                'transaction_id' => $order->preauth_transaction_id,
                'amount' => $order->preauth_amount,
            ]);

            if (!$gatewayResult['success']) {
                Db::rollback();
                return $gatewayResult;
            }

            // 更新订单状态
            $order->preauth_status = Order::PREAUTH_STATUS_CAPTURED;
            $order->cod_status = Order::COD_STATUS_COLLECTED;
            $order->paid_amount = $order->paid_amount + $order->preauth_amount;
            $order->paid_at = date('Y-m-d H:i:s');
            $order->status = Order::STATUS_COMPLETED;
            $order->completed_at = date('Y-m-d H:i:s');
            $order->save();

            // 记录交易
            PaymentTransaction::create([
                'transaction_no' => PaymentTransaction::generateTransactionNo(),
                'order_id' => $orderId,
                'order_no' => $order->order_no,
                'user_id' => $order->buyer_id,
                'payment_method' => $order->payment_method ?? 'card',
                'gateway_transaction_id' => $gatewayResult['capture_id'] ?? $order->preauth_transaction_id,
                'amount' => $order->preauth_amount,
                'currency' => $order->currency,
                'type' => PaymentTransaction::TYPE_PREAUTH_CAPTURE,
                'status' => PaymentTransaction::STATUS_SUCCESS,
                'paid_at' => date('Y-m-d H:i:s'),
                'gateway_response' => $gatewayResult,
            ]);

            Db::commit();

            Log::info("PreAuth captured for order {$order->order_no}, amount: {$order->preauth_amount}");

            return [
                'success' => true,
                'captured_amount' => $order->preauth_amount,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error("PreAuth capture failed for order {$orderId}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 取消预授权（用户拒收时调用）
     * @param int $orderId
     * @param string $reason 取消原因
     * @return array
     */
    public function voidPreAuth(int $orderId, string $reason = ''): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        if ($order->preauth_status !== Order::PREAUTH_STATUS_AUTHORIZED) {
            return ['success' => false, 'error' => 'Invalid preauth status'];
        }

        Db::startTrans();
        try {
            // 调用支付网关取消预授权
            $gatewayResult = $this->callPaymentGateway('void', [
                'transaction_id' => $order->preauth_transaction_id,
            ]);

            if (!$gatewayResult['success']) {
                Db::rollback();
                return $gatewayResult;
            }

            // 更新预授权状态（不改变订单主状态，由调用方决定）
            $order->preauth_status = Order::PREAUTH_STATUS_VOIDED;
            $order->save();

            // 记录交易
            PaymentTransaction::create([
                'transaction_no' => PaymentTransaction::generateTransactionNo(),
                'order_id' => $orderId,
                'order_no' => $order->order_no,
                'user_id' => $order->buyer_id,
                'payment_method' => $order->payment_method ?? 'card',
                'gateway_transaction_id' => $order->preauth_transaction_id,
                'amount' => $order->preauth_amount,
                'currency' => $order->currency,
                'type' => PaymentTransaction::TYPE_PREAUTH_VOID,
                'status' => PaymentTransaction::STATUS_SUCCESS,
                'gateway_response' => $gatewayResult,
            ]);

            Db::commit();

            Log::info("PreAuth voided for order {$order->order_no}, reason: {$reason}");

            return [
                'success' => true,
                'voided_amount' => $order->preauth_amount,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error("PreAuth void failed for order {$orderId}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 调用支付网关（模拟实现，实际需要集成具体支付网关）
     * @param string $action
     * @param array $params
     * @return array
     */
    protected function callPaymentGateway(string $action, array $params): array
    {
        // TODO: 集成实际支付网关（PayPal, Stripe等）
        // 这里返回模拟结果用于开发测试

        switch ($action) {
            case 'authorize':
                return [
                    'success' => true,
                    'transaction_id' => 'PREAUTH_' . strtoupper(uniqid()),
                    'amount' => $params['amount'],
                    'currency' => $params['currency'],
                ];

            case 'capture':
                return [
                    'success' => true,
                    'capture_id' => 'CAP_' . strtoupper(uniqid()),
                    'amount' => $params['amount'],
                ];

            case 'void':
                return [
                    'success' => true,
                    'void_id' => 'VOID_' . strtoupper(uniqid()),
                ];

            default:
                return ['success' => false, 'error' => 'Unknown action'];
        }
    }
}
