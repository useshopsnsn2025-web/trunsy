<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\TreasureBox as TreasureBoxModel;
use app\common\model\TreasureBoxPrize;
use app\common\model\UserTreasureBox;

/**
 * 宝箱控制器
 */
class TreasureBox extends Base
{
    /**
     * 获取宝箱列表
     */
    public function index()
    {
        $boxes = TreasureBoxModel::getActiveBoxes();

        $result = [];
        foreach ($boxes as $box) {
            $boxModel = TreasureBoxModel::find($box['id']);
            if ($boxModel) {
                $boxData = $boxModel->toApiArray($this->locale);
                // 获取奖品列表
                $prizes = TreasureBoxPrize::where('box_id', $box['id'])
                    ->where('status', 1)
                    ->order('sort', 'asc')
                    ->select();
                $boxData['prizes'] = [];
                foreach ($prizes as $prize) {
                    $boxData['prizes'][] = $prize->toApiArray($this->locale);
                }
                $result[] = $boxData;
            }
        }

        return $this->success($result);
    }

    /**
     * 获取宝箱详情
     */
    public function detail(string $code)
    {
        $box = TreasureBoxModel::getByCode($code);
        if (!$box) {
            return $this->error('Treasure box not found', 404);
        }

        $result = $box->toApiArray($this->locale);

        // 获取奖品列表
        $prizes = TreasureBoxPrize::where('box_id', $box->id)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();

        $result['prizes'] = [];
        foreach ($prizes as $prize) {
            $result['prizes'][] = $prize->toApiArray($this->locale);
        }

        return $this->success($result);
    }

    /**
     * 获取我的宝箱
     */
    public function myBoxes()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $boxes = UserTreasureBox::getPendingBoxes($this->userId);
        $counts = UserTreasureBox::getBoxCounts($this->userId);

        // 转换数据格式
        $result = [];
        foreach ($boxes as $box) {
            $boxConfig = TreasureBoxModel::getByCode($box['box_code']);
            $result[] = [
                'id' => $box['id'],
                'box_code' => $box['box_code'],
                'box_name' => $boxConfig ? $boxConfig->getTranslated('name', $this->locale) : $box['box_code'],
                'box_type' => $boxConfig ? $boxConfig->type : 'unknown',
                'bg_color' => $boxConfig ? $boxConfig->bg_color : '#C0C0C0',
                'source' => $box['source'],
                'status' => $box['status'],
                'expired_at' => $box['expired_at'],
                'created_at' => $box['created_at'],
            ];
        }

        return $this->success([
            'boxes' => $result,
            'counts' => $counts,
        ]);
    }

    /**
     * 开启宝箱
     */
    public function open(int $id)
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $userBox = UserTreasureBox::find($id);
        if (!$userBox) {
            return $this->error('Treasure box not found', 404);
        }

        if ($userBox->user_id !== $this->userId) {
            return $this->error('Access denied', 403);
        }

        if ($userBox->status !== UserTreasureBox::STATUS_PENDING) {
            return $this->error('Treasure box already opened or expired', 400);
        }

        $result = $userBox->open($this->userId);
        if (!$result) {
            return $this->error('Failed to open treasure box', 500);
        }

        // 获取奖品详情
        $prize = TreasureBoxPrize::find($result['prize_id']);
        $prizeData = $prize ? $prize->toApiArray($this->locale) : null;

        return $this->success([
            'prize' => $prizeData,
            'prize_type' => $result['prize_type'],
            'prize_value' => $result['prize_value'],
            'remaining_boxes' => UserTreasureBox::getBoxCounts($this->userId),
        ]);
    }

    /**
     * 获取宝箱历史记录
     */
    public function history()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('page_size', 20);

        $result = UserTreasureBox::getHistory($this->userId, $page, $pageSize);

        // 转换数据格式
        $list = [];
        foreach ($result['list'] as $box) {
            $boxConfig = TreasureBoxModel::getByCode($box['box_code']);
            $prizeInfo = null;
            if ($box['prize_id']) {
                $prize = TreasureBoxPrize::find($box['prize_id']);
                if ($prize) {
                    $prizeInfo = $prize->toApiArray($this->locale);
                }
            }

            $list[] = [
                'id' => $box['id'],
                'box_code' => $box['box_code'],
                'box_name' => $boxConfig ? $boxConfig->getTranslated('name', $this->locale) : $box['box_code'],
                'source' => $box['source'],
                'status' => $box['status'],
                'prize' => $prizeInfo,
                'opened_at' => $box['opened_at'],
                'created_at' => $box['created_at'],
            ];
        }

        return $this->success([
            'list' => $list,
            'total' => $result['total'],
            'page' => $result['page'],
            'page_size' => $result['page_size'],
        ]);
    }
}
