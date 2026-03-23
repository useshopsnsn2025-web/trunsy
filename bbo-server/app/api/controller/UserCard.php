<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\UserCard as UserCardModel;
use app\common\model\UserAddress;

/**
 * 用户银行卡控制器
 */
class UserCard extends Base
{
    /**
     * 获取用户银行卡列表
     * @return Response
     */
    public function index(): Response
    {
        // 获取所有非删除的卡（包括 active, disabled, rejected, pending）
        // status > 0 排除已删除的卡 (status = 0)
        $cards = UserCardModel::where('user_id', $this->userId)
            ->where('status', '>', 0)
            ->order('is_default', 'desc')
            ->order('id', 'desc')
            ->select();

        $list = [];
        foreach ($cards as $card) {
            $list[] = $card->toApiArray();
        }

        return $this->success($list);
    }

    /**
     * 添加银行卡
     * @return Response
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证必填字段
        if (empty($data['card_number'])) {
            return $this->error(lang('Card number is required'));
        }
        if (empty($data['expiry'])) {
            return $this->error(lang('Expiry date is required'));
        }
        if (empty($data['cvv'])) {
            return $this->error(lang('CVV is required'));
        }
        if (empty($data['billing_address_id'])) {
            return $this->error(lang('Billing address is required'));
        }

        // 验证 CVV 格式（3-4位数字）
        $cvv = trim($data['cvv']);
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            return $this->error(lang('Invalid CVV'));
        }

        // 清理卡号（移除空格）
        $cardNumber = preg_replace('/\s+/', '', $data['card_number']);

        // 验证卡号长度
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return $this->error(lang('Invalid card number'));
        }

        // Luhn 算法验证卡号
        if (!$this->luhnCheck($cardNumber)) {
            return $this->error(lang('Invalid card number'));
        }

        // 解析有效期 (MM/YY)
        $expiryParts = explode('/', $data['expiry']);
        if (count($expiryParts) !== 2) {
            return $this->error(lang('Invalid expiry date'));
        }

        $expiryMonth = (int) $expiryParts[0];
        $expiryYear = (int) $expiryParts[1];

        // 转换两位数年份
        if ($expiryYear < 100) {
            $expiryYear += 2000;
        }

        // 验证月份
        if ($expiryMonth < 1 || $expiryMonth > 12) {
            return $this->error(lang('Invalid expiry date'));
        }

        // 验证是否过期
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        if ($expiryYear < $currentYear ||
            ($expiryYear === $currentYear && $expiryMonth < $currentMonth)) {
            return $this->error(lang('Card has expired'));
        }

        // 验证账单地址是否属于该用户
        $billingAddressId = (int) $data['billing_address_id'];
        $address = UserAddress::where('id', $billingAddressId)
            ->where('user_id', $this->userId)
            ->find();

        if (!$address) {
            return $this->error(lang('Invalid billing address'));
        }

        // 检测卡类型
        $cardTypeInfo = UserCardModel::detectCardType($cardNumber);

        // 获取卡号后四位
        $lastFour = substr($cardNumber, -4);

        try {
            // 如果设为默认，取消其他默认
            $isDefault = !empty($data['is_default']) ? 1 : 0;
            if ($isDefault) {
                UserCardModel::where('user_id', $this->userId)
                    ->update(['is_default' => 0]);
            }

            // 如果是第一张卡，自动设为默认
            $cardCount = UserCardModel::where('user_id', $this->userId)
                ->where('status', 1)
                ->count();
            if ($cardCount === 0) {
                $isDefault = 1;
            }

            // 创建卡记录
            $card = new UserCardModel();
            $card->user_id = $this->userId;
            $card->card_number = $cardNumber;
            $card->card_type = $cardTypeInfo['type'];
            $card->card_brand = $cardTypeInfo['brand'];
            $card->last_four = $lastFour;
            $card->expiry_month = $expiryMonth;
            $card->expiry_year = $expiryYear;
            $card->cvv = $cvv;
            // 持卡人名字使用账单地址的收件人名字
            $card->cardholder_name = $address->getData('name');
            $card->billing_address_id = $billingAddressId;
            $card->is_default = $isDefault;
            $card->status = 1;
            $card->save();

            return $this->success($card->toApiArray(), lang('Card added successfully'));

        } catch (\Exception $e) {
            return $this->error(lang('Failed to add card'));
        }
    }

    /**
     * 获取银行卡详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        // 获取所有非删除状态的卡片（status > 0）
        $card = UserCardModel::where('id', $id)
            ->where('user_id', $this->userId)
            ->where('status', '>', 0)
            ->find();

        if (!$card) {
            return $this->error(lang('Card not found'), 404);
        }

        return $this->success($card->toApiArray());
    }

    /**
     * 更新银行卡
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        // 获取所有非删除状态的卡片（status > 0）
        $card = UserCardModel::where('id', $id)
            ->where('user_id', $this->userId)
            ->where('status', '>', 0)
            ->find();

        if (!$card) {
            return $this->error(lang('Card not found'), 404);
        }

        $data = input('post.');

        try {
            // 如果设为默认，取消其他默认
            if (!empty($data['is_default'])) {
                UserCardModel::where('user_id', $this->userId)
                    ->where('id', '<>', $id)
                    ->update(['is_default' => 0]);
                $card->is_default = 1;
            }

            // 更新账单地址
            if (!empty($data['billing_address_id'])) {
                $billingAddressId = (int) $data['billing_address_id'];
                $address = UserAddress::where('id', $billingAddressId)
                    ->where('user_id', $this->userId)
                    ->find();

                if ($address) {
                    $card->billing_address_id = $billingAddressId;
                }
            }

            // 更新持卡人姓名
            if (isset($data['cardholder_name'])) {
                $card->cardholder_name = $data['cardholder_name'];
            }

            $card->save();

            return $this->success($card->toApiArray(), lang('Card updated successfully'));

        } catch (\Exception $e) {
            return $this->error(lang('Failed to update card'));
        }
    }

    /**
     * 删除银行卡
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $card = UserCardModel::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$card) {
            return $this->error(lang('Card not found'), 404);
        }

        try {
            // 软删除（设置状态为禁用）
            $card->status = 0;
            $card->save();

            // 如果删除的是默认卡，自动将最新的一张卡设为默认
            if ($card->is_default === 1) {
                $newDefault = UserCardModel::where('user_id', $this->userId)
                    ->where('status', 1)
                    ->order('id', 'desc')
                    ->find();

                if ($newDefault) {
                    $newDefault->is_default = 1;
                    $newDefault->save();
                }
            }

            return $this->success([], lang('Card deleted successfully'));

        } catch (\Exception $e) {
            return $this->error(lang('Failed to delete card'));
        }
    }

    /**
     * 设置默认银行卡
     * @param int $id
     * @return Response
     */
    public function setDefault(int $id): Response
    {
        $card = UserCardModel::where('id', $id)
            ->where('user_id', $this->userId)
            ->where('status', 1)
            ->find();

        if (!$card) {
            return $this->error(lang('Card not found'), 404);
        }

        try {
            // 取消其他默认
            UserCardModel::where('user_id', $this->userId)
                ->where('id', '<>', $id)
                ->update(['is_default' => 0]);

            // 设置当前卡为默认
            $card->is_default = 1;
            $card->save();

            return $this->success($card->toApiArray(), lang('Default card set'));

        } catch (\Exception $e) {
            return $this->error(lang('Operation failed'));
        }
    }

    /**
     * Luhn 算法验证卡号
     * @param string $cardNumber
     * @return bool
     */
    private function luhnCheck(string $cardNumber): bool
    {
        $sum = 0;
        $length = strlen($cardNumber);
        $parity = $length % 2;

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $cardNumber[$i];

            if ($i % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
