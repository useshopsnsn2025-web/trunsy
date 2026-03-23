<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\model\Game as GameModel;
use app\common\model\GameTranslation;
use app\common\model\GamePrize;
use app\common\model\GamePrizeTranslation;
use app\common\model\DailyPrizeStats;
use app\common\model\UserGameLog;

/**
 * 游戏管理控制器
 */
class Game extends Base
{
    /**
     * 游戏列表
     */
    public function index()
    {
        $games = GameModel::order('sort', 'asc')->select()->toArray();

        $result = [];
        foreach ($games as $game) {
            $gameModel = GameModel::find($game['id']);
            $item = $gameModel->toAdminArray();

            // 获取翻译
            $item['translations'] = GameTranslation::getByGameId($game['id']);

            // 获取奖品数量
            $item['prize_count'] = GamePrize::where('game_id', $game['id'])->count();

            // 获取今日统计
            $todayStats = DailyPrizeStats::getGameDailyStats($game['id']);
            $item['today_stats'] = [
                'play_count' => $todayStats['total_count'],
                'issued_value' => $todayStats['total_value'],
            ];

            $result[] = $item;
        }

        return $this->success($result);
    }

    /**
     * 游戏详情
     */
    public function read($id)
    {
        $id = (int)$id;
        $game = GameModel::find($id);
        if (!$game) {
            return $this->error('Game not found', 404);
        }

        $result = $game->toAdminArray();
        $result['translations'] = GameTranslation::getByGameId($id);

        return $this->success($result);
    }

    /**
     * 更新游戏配置
     */
    public function update($id)
    {
        $id = (int)$id;
        $game = GameModel::find($id);
        if (!$game) {
            return $this->error('Game not found', 404);
        }

        $data = request()->post();

        // 更新基本信息
        if (isset($data['icon'])) {
            $game->icon = $data['icon'];
        }
        if (isset($data['bg_image'])) {
            $game->bg_image = $data['bg_image'];
        }
        if (isset($data['config'])) {
            $game->config = $data['config'];
        }
        if (isset($data['sort'])) {
            $game->sort = (int)$data['sort'];
        }
        if (isset($data['start_time'])) {
            $game->start_time = $data['start_time'] ?: null;
        }
        if (isset($data['end_time'])) {
            $game->end_time = $data['end_time'] ?: null;
        }

        $game->save();

        // 更新翻译
        if (isset($data['translations']) && is_array($data['translations'])) {
            GameTranslation::updateTranslations($id, $data['translations']);
        }

        return $this->success($game->toAdminArray());
    }

    /**
     * 切换游戏状态
     */
    public function status($id)
    {
        $id = (int)$id;
        $game = GameModel::find($id);
        if (!$game) {
            return $this->error('Game not found', 404);
        }

        $game->status = $game->status == 1 ? 0 : 1;
        $game->save();

        return $this->success([
            'id' => $game->id,
            'status' => (int)$game->status,
        ]);
    }

    /**
     * 获取游戏奖品列表
     */
    public function prizes($id)
    {
        $id = (int)$id;
        $game = GameModel::find($id);
        if (!$game) {
            return $this->error('Game not found', 404);
        }

        $prizes = GamePrize::where('game_id', $id)
            ->order('sort', 'asc')
            ->select();

        $result = [];
        $totalProbability = 0;

        foreach ($prizes as $prize) {
            $item = $prize->toAdminArray();
            $item['translations'] = GamePrizeTranslation::getByPrizeId($prize->id);
            $result[] = $item;

            if ($prize->status == 1) {
                $totalProbability += $prize->probability;
            }
        }

        return $this->success([
            'prizes' => $result,
            'total_probability' => round($totalProbability * 100, 2),
            'config' => $game->config,
        ]);
    }

    /**
     * 添加奖品
     */
    public function addPrize($id)
    {
        $id = (int)$id;
        $game = GameModel::find($id);
        if (!$game) {
            return $this->error('Game not found', 404);
        }

        $data = request()->post();

        // 验证必填字段
        if (!isset($data['type']) || !isset($data['probability'])) {
            return $this->error('Type and probability are required');
        }

        // 概率转换（前端传百分比，存储为小数）
        $probability = (float)$data['probability'] / 100;

        // 检查概率是否超过 1
        $currentTotal = GamePrize::where('game_id', $id)
            ->where('status', 1)
            ->sum('probability');

        if ($currentTotal + $probability > 1.0001) { // 允许小误差
            return $this->error('Total probability cannot exceed 100%');
        }

        $prize = GamePrize::create([
            'game_id' => $id,
            'type' => $data['type'],
            'value' => $data['value'] ?? 0,
            'coupon_id' => $data['coupon_id'] ?? null,
            'goods_id' => $data['goods_id'] ?? null,
            'probability' => $probability,
            'stock' => $data['stock'] ?? -1,
            'daily_limit' => $data['daily_limit'] ?? -1,
            'user_daily_limit' => $data['user_daily_limit'] ?? -1,
            'image' => $data['image'] ?? '',
            'color' => $data['color'] ?? '#FFD700',
            'sort' => $data['sort'] ?? 0,
            'status' => $data['status'] ?? 1,
        ]);

        // 添加翻译
        if (isset($data['translations']) && is_array($data['translations'])) {
            GamePrizeTranslation::updateTranslations($prize->id, $data['translations']);
        }

        $result = $prize->toAdminArray();
        $result['translations'] = GamePrizeTranslation::getByPrizeId($prize->id);

        return $this->success($result);
    }

    /**
     * 更新奖品
     */
    public function updatePrize($id, $prizeId)
    {
        $id = (int)$id;
        $prizeId = (int)$prizeId;
        $prize = GamePrize::where('id', $prizeId)
            ->where('game_id', $id)
            ->find();

        if (!$prize) {
            return $this->error('Prize not found', 404);
        }

        $data = request()->post();

        // 概率转换
        if (isset($data['probability'])) {
            $newProbability = (float)$data['probability'] / 100;

            // 检查概率是否超过 1
            $currentTotal = GamePrize::where('game_id', $id)
                ->where('status', 1)
                ->where('id', '<>', $prizeId)
                ->sum('probability');

            $statusToCheck = $data['status'] ?? $prize->status;
            if ($statusToCheck == 1 && $currentTotal + $newProbability > 1.0001) {
                return $this->error('Total probability cannot exceed 100%');
            }

            $prize->probability = $newProbability;
        }

        // 更新其他字段
        if (isset($data['type'])) {
            $prize->type = $data['type'];
        }
        if (isset($data['value'])) {
            $prize->value = $data['value'];
        }
        if (isset($data['coupon_id'])) {
            $prize->coupon_id = $data['coupon_id'] ?: null;
        }
        if (isset($data['goods_id'])) {
            $prize->goods_id = $data['goods_id'] ?: null;
        }
        if (isset($data['stock'])) {
            $prize->stock = (int)$data['stock'];
        }
        if (isset($data['daily_limit'])) {
            $prize->daily_limit = (int)$data['daily_limit'];
        }
        if (isset($data['user_daily_limit'])) {
            $prize->user_daily_limit = (int)$data['user_daily_limit'];
        }
        if (isset($data['image'])) {
            $prize->image = $data['image'];
        }
        if (isset($data['color'])) {
            $prize->color = $data['color'];
        }
        if (isset($data['sort'])) {
            $prize->sort = (int)$data['sort'];
        }
        if (isset($data['status'])) {
            $prize->status = (int)$data['status'];
        }

        $prize->save();

        // 更新翻译
        if (isset($data['translations']) && is_array($data['translations'])) {
            GamePrizeTranslation::updateTranslations($prize->id, $data['translations']);
        }

        $result = $prize->toAdminArray();
        $result['translations'] = GamePrizeTranslation::getByPrizeId($prize->id);

        return $this->success($result);
    }

    /**
     * 删除奖品
     */
    public function deletePrize($id, $prizeId)
    {
        $id = (int)$id;
        $prizeId = (int)$prizeId;
        $prize = GamePrize::where('id', $prizeId)
            ->where('game_id', $id)
            ->find();

        if (!$prize) {
            return $this->error('Prize not found', 404);
        }

        // 删除翻译
        GamePrizeTranslation::deleteByPrizeId($prizeId);

        // 删除奖品
        $prize->delete();

        return $this->success(['message' => 'Prize deleted']);
    }

    /**
     * 批量更新奖品排序
     */
    public function sortPrizes($id)
    {
        $id = (int)$id;
        $data = request()->post();

        if (!isset($data['sorts']) || !is_array($data['sorts'])) {
            return $this->error('Invalid sort data');
        }

        foreach ($data['sorts'] as $prizeId => $sort) {
            GamePrize::where('id', $prizeId)
                ->where('game_id', $id)
                ->update(['sort' => (int)$sort]);
        }

        return $this->success(['message' => 'Sort updated']);
    }

    /**
     * 验证概率
     */
    public function validateProbability($id)
    {
        $id = (int)$id;
        $game = GameModel::find($id);
        if (!$game) {
            return $this->error('Game not found', 404);
        }

        $total = GamePrize::where('game_id', $id)
            ->where('status', 1)
            ->sum('probability');

        $totalPercent = round($total * 100, 2);

        return $this->success([
            'total' => $totalPercent,
            'valid' => abs($totalPercent - 100) < 0.01,
            'message' => abs($totalPercent - 100) < 0.01
                ? 'Probability configuration is valid'
                : ($totalPercent < 100
                    ? "Missing " . round(100 - $totalPercent, 2) . "%"
                    : "Exceeds by " . round($totalPercent - 100, 2) . "%"),
        ]);
    }

    /**
     * 模拟抽奖
     */
    public function simulate($id)
    {
        $id = (int)$id;
        $game = GameModel::find($id);
        if (!$game) {
            return $this->error('Game not found', 404);
        }

        $times = (int)request()->post('times', 1000);
        $times = min(max($times, 100), 10000); // 限制 100-10000 次

        $prizes = GamePrize::where('game_id', $id)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();

        if ($prizes->isEmpty()) {
            return $this->error('No prizes configured');
        }

        // 构建概率区间
        $ranges = [];
        $cumulative = 0;
        foreach ($prizes as $prize) {
            $cumulative += $prize->probability;
            $ranges[] = [
                'prize' => $prize,
                'threshold' => $cumulative,
            ];
        }

        // 模拟抽奖
        $results = [];
        $totalCost = 0;

        for ($i = 0; $i < $times; $i++) {
            $random = mt_rand(1, 10000) / 10000;

            foreach ($ranges as $range) {
                if ($random <= $range['threshold']) {
                    $prize = $range['prize'];
                    $prizeId = $prize->id;

                    if (!isset($results[$prizeId])) {
                        $results[$prizeId] = [
                            'id' => $prizeId,
                            'name' => $prize->getTranslated('name', 'en-us'),
                            'type' => $prize->type,
                            'value' => (float)$prize->value,
                            'expected_probability' => round($prize->probability * 100, 2),
                            'count' => 0,
                        ];
                    }

                    $results[$prizeId]['count']++;

                    // 计算成本
                    if ($prize->type === 'cash') {
                        $totalCost += $prize->value;
                    } elseif ($prize->type === 'points') {
                        $totalCost += $prize->value / 1000; // 1000积分=$1
                    }

                    break;
                }
            }
        }

        // 计算实际概率
        foreach ($results as &$result) {
            $result['actual_probability'] = round($result['count'] / $times * 100, 2);
            $result['deviation'] = round($result['actual_probability'] - $result['expected_probability'], 2);
        }

        return $this->success([
            'times' => $times,
            'results' => array_values($results),
            'total_cost' => round($totalCost, 2),
            'avg_cost' => round($totalCost / $times, 4),
        ]);
    }

    /**
     * 获取游戏统计
     */
    public function stats($id)
    {
        $id = (int)$id;
        $game = GameModel::find($id);
        if (!$game) {
            return $this->error('Game not found', 404);
        }

        $startDate = request()->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = request()->get('end_date', date('Y-m-d'));

        // 每日统计
        $dailyStats = DailyPrizeStats::getStatsRange($id, $startDate, $endDate);

        // 总统计
        $totalPlays = UserGameLog::where('game_id', $id)
            ->where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->count();

        $totalValue = 0;
        foreach ($dailyStats as $stat) {
            $totalValue += $stat['total_value'];
        }

        // 奖品分布
        $prizeDistribution = UserGameLog::where('game_id', $id)
            ->where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->field('prize_type, prize_name, COUNT(*) as count, SUM(prize_value) as total_value')
            ->group('prize_type, prize_name')
            ->select()
            ->toArray();

        return $this->success([
            'total_plays' => $totalPlays,
            'total_value' => round($totalValue, 2),
            'daily_stats' => $dailyStats,
            'prize_distribution' => $prizeDistribution,
        ]);
    }
}
